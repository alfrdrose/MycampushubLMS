<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Concerns\HandlesTagMetrics;
use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\QuestionBankItem;
use App\Models\Quiz;
use App\Models\SectionModel;
use App\Models\SubjectTeacher;
use App\Models\SubjectModel;
use App\Models\Tag;
use App\Models\TeacherModel;
use App\Services\QuestionContentNormalizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class QuestionBankController extends Controller
{
    use HandlesTagMetrics;

    public function __construct(private readonly QuestionContentNormalizer $questionContentNormalizer)
    {
    }

    public function page(Request $request)
    {
        $teacher = auth()->user()?->teacher;
        if (!$teacher) {
            abort(403);
        }

        $subjects = $this->questionBankSubjectsForTeacher($teacher);
        $requestedSubjectId = $request->query('subject_id');
        $subject = null;

        if ($requestedSubjectId !== null && $requestedSubjectId !== '') {
            $subject = $subjects->firstWhere('id', (int) $requestedSubjectId);

            if (!$subject) {
                abort(403);
            }
        } else {
            $subject = $subjects->first();
        }

        $tags = Tag::query()
            ->orderBy('name')
            ->get(['id', 'name', 'category']);

        $tagCategories = Tag::query()
            ->select('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category')
            ->filter()
            ->values();

        return view('pages.teacher.pages.questions', [
            'title' => $subject
                ? $this->questionBankSubjectLabel($subject) . ' Question Bank'
                : 'Question Bank',
            'preloader' => false,
            'darkmode' => true,
            'scrollup' => true,
            'banner' => true,
            'sidebar' => true,
            'subject' => $subject,
            'subjectOptions' => $subjects->map(fn (SubjectModel $item) => $this->serializeQuestionBankSubject($item))->values(),
            'subjectAssignments' => $this->questionBankSubjectAssignmentsForTeacher($teacher),
            'tags' => $tags,
            'tagCategories' => $tagCategories,
        ]);
    }

    public function index(Request $request, int $section_id, int $subject_id): JsonResponse
    {
        [$teacher, $section, $subject] = $this->resolveContext($section_id, $subject_id);

        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', 'string', Rule::in($this->allowedQuestionTypes())],
            'scope' => ['nullable', 'string', Rule::in(['all', 'subject', 'section'])],
            'state' => ['nullable', 'string', Rule::in(['active', 'archived', 'all'])],
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['integer', 'exists:tags,id'],
            'all' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $scope = (string) ($validated['scope'] ?? 'all');
        $state = (string) ($validated['state'] ?? 'active');
        $search = trim((string) ($validated['search'] ?? ''));
        $perPage = (int) ($validated['per_page'] ?? 15);
        $loadAll = (bool) ($validated['all'] ?? false);
        $tagIds = array_values(array_unique(array_map('intval', $validated['tag_ids'] ?? [])));

        $query = QuestionBankItem::query()
            ->with([
                'tags' => fn ($tagQuery) => $tagQuery->orderBy('name'),
                'creator:id,first_name,last_name',
                'subject:id,subject_name,grade_level',
                'section:id,section_name',
            ])
            ->where('created_by_teacher_id', $teacher->id)
            ->where('subject_model_id', $subject->id);

        if ($scope === 'subject') {
            $query->whereNull('section_model_id');
        } elseif ($scope === 'section') {
            $query->where('section_model_id', $section->id);
        } else {
            $query->where(function ($scopeQuery) use ($section) {
                $scopeQuery->whereNull('section_model_id')
                    ->orWhere('section_model_id', $section->id);
            });
        }

        if ($state === 'active') {
            $query->where('is_archived', false);
        } elseif ($state === 'archived') {
            $query->where('is_archived', true);
        }

        if ($search !== '') {
            $query->where(function ($searchQuery) use ($search) {
                $searchQuery->where('text', 'like', '%' . $search . '%')
                    ->orWhereHas('tags', function ($tagQuery) use ($search) {
                        $tagQuery->where('name', 'like', '%' . $search . '%')
                            ->orWhere('category', 'like', '%' . $search . '%');
                    });
            });
        }

        if (!empty($validated['type'])) {
            $query->where('type', $validated['type']);
        }

        if (!empty($tagIds)) {
            $query->whereHas('tags', function ($tagQuery) use ($tagIds) {
                $tagQuery->whereIn('tags.id', $tagIds);
            });
        }

        $query
            ->orderByDesc('updated_at')
            ->orderByDesc('id');

        if ($loadAll) {
            $items = $query->get()->map(fn (QuestionBankItem $item) => $this->serializeListItem($item))->values();
            $total = $items->count();

            return response()->json([
                'data' => $items,
                'meta' => [
                    'current_page' => 1,
                    'per_page' => $total,
                    'total' => $total,
                    'last_page' => 1,
                ],
                'context' => [
                    'section_id' => $section->id,
                    'subject_id' => $subject->id,
                    'subject_name' => $subject->subject_name,
                    'grade_level' => $subject->grade_level,
                ],
            ]);
        }

        $items = $query
            ->paginate($perPage)
            ->through(fn (QuestionBankItem $item) => $this->serializeListItem($item));

        return response()->json([
            'data' => $items->items(),
            'meta' => [
                'current_page' => $items->currentPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
                'last_page' => $items->lastPage(),
            ],
            'context' => [
                'section_id' => $section->id,
                'subject_id' => $subject->id,
                'subject_name' => $subject->subject_name,
                'grade_level' => $subject->grade_level,
            ],
        ]);
    }

    public function subjectIndex(Request $request, int $subject_id): JsonResponse
    {
        [$teacher, $subject] = $this->resolveSubjectContext($subject_id);

        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', 'string', Rule::in($this->allowedQuestionTypes())],
            'state' => ['nullable', 'string', Rule::in(['active', 'archived', 'all'])],
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['integer', 'exists:tags,id'],
            'all' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $state = (string) ($validated['state'] ?? 'active');
        $search = trim((string) ($validated['search'] ?? ''));
        $perPage = (int) ($validated['per_page'] ?? 15);
        $loadAll = (bool) ($validated['all'] ?? false);
        $tagIds = array_values(array_unique(array_map('intval', $validated['tag_ids'] ?? [])));

        $query = QuestionBankItem::query()
            ->with([
                'tags' => fn ($tagQuery) => $tagQuery->orderBy('name'),
                'creator:id,first_name,last_name',
                'subject:id,subject_name,grade_level',
                'section:id,section_name',
            ])
            ->where('created_by_teacher_id', $teacher->id)
            ->where('subject_model_id', $subject->id)
            ->whereNull('section_model_id');

        if ($state === 'active') {
            $query->where('is_archived', false);
        } elseif ($state === 'archived') {
            $query->where('is_archived', true);
        }

        if ($search !== '') {
            $query->where(function ($searchQuery) use ($search) {
                $searchQuery->where('text', 'like', '%' . $search . '%')
                    ->orWhereHas('tags', function ($tagQuery) use ($search) {
                        $tagQuery->where('name', 'like', '%' . $search . '%')
                            ->orWhere('category', 'like', '%' . $search . '%');
                    });
            });
        }

        if (!empty($validated['type'])) {
            $query->where('type', $validated['type']);
        }

        if (!empty($tagIds)) {
            $query->whereHas('tags', function ($tagQuery) use ($tagIds) {
                $tagQuery->whereIn('tags.id', $tagIds);
            });
        }

        $query
            ->orderByDesc('updated_at')
            ->orderByDesc('id');

        if ($loadAll) {
            $items = $query->get()->map(fn (QuestionBankItem $item) => $this->serializeListItem($item))->values();
            $total = $items->count();

            return response()->json([
                'data' => $items,
                'meta' => [
                    'current_page' => 1,
                    'per_page' => $total,
                    'total' => $total,
                    'last_page' => 1,
                ],
                'context' => [
                    'subject_id' => $subject->id,
                    'subject_name' => $subject->subject_name,
                    'grade_level' => $subject->grade_level,
                ],
            ]);
        }

        $items = $query
            ->paginate($perPage)
            ->through(fn (QuestionBankItem $item) => $this->serializeListItem($item));

        return response()->json([
            'data' => $items->items(),
            'meta' => [
                'current_page' => $items->currentPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
                'last_page' => $items->lastPage(),
            ],
            'context' => [
                'subject_id' => $subject->id,
                'subject_name' => $subject->subject_name,
                'grade_level' => $subject->grade_level,
            ],
        ]);
    }

    public function store(Request $request, int $section_id, int $subject_id): JsonResponse
    {
        [$teacher, $section, $subject] = $this->resolveContext($section_id, $subject_id);

        $validated = $request->validate($this->questionBankRules(), $this->questionBankMessages());
        $normalized = $this->normalizeQuestionPayload($validated, $request->all());
        $tagSyncPayload = $this->buildQuestionBankTagSyncPayload(
            array_values(array_unique(array_map('intval', $validated['tag_ids'] ?? []))),
            (array) $request->input('tag_relevance', [])
        );

        $item = QuestionBankItem::create([
            'created_by_teacher_id' => $teacher->id,
            'subject_model_id' => $subject->id,
            'section_model_id' => $this->resolveScopedSectionId((string) ($validated['scope'] ?? 'subject'), $section),
            'type' => $normalized['type'],
            'text' => $normalized['text'],
            'option' => $normalized['option'],
            'setting' => $normalized['setting'],
            'answer_key' => $normalized['answer_key'],
            'required' => $normalized['required'],
            'default_points' => $normalized['points'],
        ]);

        $item->tags()->sync($tagSyncPayload);
        $item->loadMissing(['tags', 'creator', 'subject', 'section']);

        return response()->json([
            'message' => 'Question bank item created successfully.',
            'data' => $this->serializeDetailItem($item),
        ], 201);
    }

    public function subjectStore(Request $request, int $subject_id): JsonResponse
    {
        [$teacher, $subject] = $this->resolveSubjectContext($subject_id);

        $validated = $request->validate($this->questionBankRules(false), $this->questionBankMessages());
        $normalized = $this->normalizeQuestionPayload($validated, $request->all());
        $tagSyncPayload = $this->buildQuestionBankTagSyncPayload(
            array_values(array_unique(array_map('intval', $validated['tag_ids'] ?? []))),
            (array) $request->input('tag_relevance', [])
        );

        $item = QuestionBankItem::create([
            'created_by_teacher_id' => $teacher->id,
            'subject_model_id' => $subject->id,
            'section_model_id' => null,
            'type' => $normalized['type'],
            'text' => $normalized['text'],
            'option' => $normalized['option'],
            'setting' => $normalized['setting'],
            'answer_key' => $normalized['answer_key'],
            'required' => $normalized['required'],
            'default_points' => $normalized['points'],
        ]);

        $item->tags()->sync($tagSyncPayload);
        $item->loadMissing(['tags', 'creator', 'subject', 'section']);

        return response()->json([
            'message' => 'Question bank item created successfully.',
            'data' => $this->serializeDetailItem($item),
        ], 201);
    }

    public function quizzes(Request $request, int $section_id, int $subject_id): JsonResponse
    {
        [, $section, $subject] = $this->resolveContext($section_id, $subject_id);

        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $search = trim((string) ($validated['search'] ?? ''));
        $perPage = (int) ($validated['per_page'] ?? 12);

        $quizzes = Quiz::query()
            ->where('section_model_id', $section->id)
            ->where('subject_model_id', $subject->id)
            ->with('section:id,section_name')
            ->withCount('questions');

        if ($search !== '') {
            $quizzes->where(function ($query) use ($search) {
                $query->where('title', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%');
            });
        }

        $quizzes = $quizzes
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->through(fn (Quiz $quiz) => $this->serializeQuizListItem($quiz));

        return response()->json([
            'data' => $quizzes->items(),
            'meta' => [
                'current_page' => $quizzes->currentPage(),
                'per_page' => $quizzes->perPage(),
                'total' => $quizzes->total(),
                'last_page' => $quizzes->lastPage(),
            ],
        ]);
    }

    public function subjectQuizzes(Request $request, int $subject_id): JsonResponse
    {
        [$teacher, $subject] = $this->resolveSubjectContext($subject_id);

        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'all' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $sectionIds = $this->accessibleSectionIdsForSubject($teacher, $subject);
        $search = trim((string) ($validated['search'] ?? ''));
        $perPage = (int) ($validated['per_page'] ?? 12);
        $loadAll = (bool) ($validated['all'] ?? false);

        $quizzes = Quiz::query()
            ->where('subject_model_id', $subject->id)
            ->whereIn('section_model_id', $sectionIds)
            ->with('section:id,section_name')
            ->withCount('questions');

        if ($search !== '') {
            $quizzes->where(function ($query) use ($search) {
                $query->where('title', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%');
            });
        }

        $quizzes
            ->orderByDesc('updated_at')
            ->orderByDesc('id');

        if ($loadAll) {
            $items = $quizzes->get()->map(fn (Quiz $quiz) => $this->serializeQuizListItem($quiz))->values();
            $total = $items->count();

            return response()->json([
                'data' => $items,
                'meta' => [
                    'current_page' => 1,
                    'per_page' => $total,
                    'total' => $total,
                    'last_page' => 1,
                ],
            ]);
        }

        $quizzes = $quizzes
            ->paginate($perPage)
            ->through(fn (Quiz $quiz) => $this->serializeQuizListItem($quiz));

        return response()->json([
            'data' => $quizzes->items(),
            'meta' => [
                'current_page' => $quizzes->currentPage(),
                'per_page' => $quizzes->perPage(),
                'total' => $quizzes->total(),
                'last_page' => $quizzes->lastPage(),
            ],
        ]);
    }

    public function importToQuiz(Request $request, int $section_id, int $subject_id): JsonResponse
    {
        [$teacher, $section, $subject] = $this->resolveContext($section_id, $subject_id);

        $validated = $request->validate([
            'quiz_id' => ['required', 'integer'],
            'item_ids' => ['required', 'array', 'min:1'],
            'item_ids.*' => ['integer', 'distinct'],
        ]);

        $quiz = Quiz::query()
            ->where('id', (int) $validated['quiz_id'])
            ->where('section_model_id', $section->id)
            ->where('subject_model_id', $subject->id)
            ->firstOrFail();

        $itemIds = array_values(array_unique(array_filter(array_map('intval', $validated['item_ids'] ?? []))));
        $items = QuestionBankItem::query()
            ->with(['tags' => fn ($tagQuery) => $tagQuery->orderBy('name')])
            ->whereIn('id', $itemIds)
            ->where('created_by_teacher_id', $teacher->id)
            ->where('subject_model_id', $subject->id)
            ->where('is_archived', false)
            ->where(function ($scopeQuery) use ($section) {
                $scopeQuery->whereNull('section_model_id')
                    ->orWhere('section_model_id', $section->id);
            })
            ->get()
            ->sortBy(fn (QuestionBankItem $item) => array_search((int) $item->id, $itemIds, true))
            ->values();

        if ($items->count() !== count($itemIds)) {
            throw ValidationException::withMessages([
                'item_ids' => 'One or more selected saved questions are unavailable for import.',
            ]);
        }

        $createdQuestionIds = [];

        DB::transaction(function () use ($quiz, $items, &$createdQuestionIds) {
            $existingQuestions = $quiz->questions()->orderBy('order')->get(['id', 'order']);
            $currentQuestionIds = $existingQuestions->pluck('id')->map(fn ($id) => (int) $id)->all();
            $currentMaxOrder = max(
                $existingQuestions->count(),
                (int) $existingQuestions->max(fn (Question $question) => (int) ($question->order ?? 0))
            );

            $pagination = (int) data_get($quiz->setting, 'pagination', 10);
            if ($pagination < 1) {
                $pagination = 10;
            }

            foreach ($items as $offset => $item) {
                $order = $currentMaxOrder + $offset + 1;
                $pageHint = (int) ceil($order / max(1, $pagination));
                if ($pageHint < 1) {
                    $pageHint = 1;
                }

                $question = $quiz->questions()->create([
                    'order' => $order,
                    'page_hint' => $pageHint,
                    'type' => $item->type,
                    'text' => $item->text,
                    'option' => $item->option,
                    'setting' => $item->setting,
                    'answer_key' => $item->answer_key,
                    'required' => (bool) $item->required,
                    'points' => (int) ($item->default_points ?? 1),
                ]);

                $tagSyncPayload = $this->buildImportedQuestionTagSyncPayload($item);
                if (!empty($tagSyncPayload)) {
                    $question->tags()->sync($tagSyncPayload);
                }

                $createdQuestionIds[] = (int) $question->id;
            }

            $quiz->update([
                'questions' => [...$currentQuestionIds, ...$createdQuestionIds],
                'total_score' => (int) $quiz->questions()->sum('points'),
            ]);

            $quizFresh = $quiz->fresh();
            $this->syncQuestionTagCoverageFromQuestionTags($quizFresh);
            $this->syncQuizTagCoverageFromQuestionTags($quizFresh->fresh());
        });

        $quizFresh = $quiz->fresh()->loadCount('questions');

        return response()->json([
            'message' => sprintf(
                'Imported %d question(s) into "%s".',
                count($createdQuestionIds),
                $quizFresh->title
            ),
            'data' => [
                'quiz' => $this->serializeQuizListItem($quizFresh),
                'imported_count' => count($createdQuestionIds),
                'question_ids' => $createdQuestionIds,
            ],
        ]);
    }

    public function subjectImportToQuiz(Request $request, int $subject_id): JsonResponse
    {
        [$teacher, $subject] = $this->resolveSubjectContext($subject_id);

        $validated = $request->validate([
            'quiz_id' => ['required', 'integer'],
            'item_ids' => ['required', 'array', 'min:1'],
            'item_ids.*' => ['integer', 'distinct'],
        ]);

        $sectionIds = $this->accessibleSectionIdsForSubject($teacher, $subject);

        $quiz = Quiz::query()
            ->where('id', (int) $validated['quiz_id'])
            ->where('subject_model_id', $subject->id)
            ->whereIn('section_model_id', $sectionIds)
            ->firstOrFail();

        $itemIds = array_values(array_unique(array_filter(array_map('intval', $validated['item_ids'] ?? []))));
        $items = QuestionBankItem::query()
            ->with(['tags' => fn ($tagQuery) => $tagQuery->orderBy('name')])
            ->whereIn('id', $itemIds)
            ->where('created_by_teacher_id', $teacher->id)
            ->where('subject_model_id', $subject->id)
            ->whereNull('section_model_id')
            ->where('is_archived', false)
            ->get()
            ->sortBy(fn (QuestionBankItem $item) => array_search((int) $item->id, $itemIds, true))
            ->values();

        if ($items->count() !== count($itemIds)) {
            throw ValidationException::withMessages([
                'item_ids' => 'One or more selected saved questions are unavailable for import.',
            ]);
        }

        $createdQuestionIds = [];

        DB::transaction(function () use ($quiz, $items, &$createdQuestionIds) {
            $existingQuestions = $quiz->questions()->orderBy('order')->get(['id', 'order']);
            $currentQuestionIds = $existingQuestions->pluck('id')->map(fn ($id) => (int) $id)->all();
            $currentMaxOrder = max(
                $existingQuestions->count(),
                (int) $existingQuestions->max(fn (Question $question) => (int) ($question->order ?? 0))
            );

            $pagination = (int) data_get($quiz->setting, 'pagination', 10);
            if ($pagination < 1) {
                $pagination = 10;
            }

            foreach ($items as $offset => $item) {
                $order = $currentMaxOrder + $offset + 1;
                $pageHint = (int) ceil($order / max(1, $pagination));
                if ($pageHint < 1) {
                    $pageHint = 1;
                }

                $question = $quiz->questions()->create([
                    'order' => $order,
                    'page_hint' => $pageHint,
                    'type' => $item->type,
                    'text' => $item->text,
                    'option' => $item->option,
                    'setting' => $item->setting,
                    'answer_key' => $item->answer_key,
                    'required' => (bool) $item->required,
                    'points' => (int) ($item->default_points ?? 1),
                ]);

                $tagSyncPayload = $this->buildImportedQuestionTagSyncPayload($item);
                if (!empty($tagSyncPayload)) {
                    $question->tags()->sync($tagSyncPayload);
                }

                $createdQuestionIds[] = (int) $question->id;
            }

            $quiz->update([
                'questions' => [...$currentQuestionIds, ...$createdQuestionIds],
                'total_score' => (int) $quiz->questions()->sum('points'),
            ]);

            $quizFresh = $quiz->fresh();
            $this->syncQuestionTagCoverageFromQuestionTags($quizFresh);
            $this->syncQuizTagCoverageFromQuestionTags($quizFresh->fresh());
        });

        $quizFresh = $quiz->fresh()->load('section:id,section_name')->loadCount('questions');

        return response()->json([
            'message' => sprintf(
                'Imported %d question(s) into "%s".',
                count($createdQuestionIds),
                $quizFresh->title
            ),
            'data' => [
                'quiz' => $this->serializeQuizListItem($quizFresh),
                'imported_count' => count($createdQuestionIds),
                'question_ids' => $createdQuestionIds,
            ],
        ]);
    }

    public function show(int $section_id, int $subject_id, int $item): JsonResponse
    {
        [$teacher, $section, $subject] = $this->resolveContext($section_id, $subject_id);
        $questionBankItem = $this->resolveItemForContext($teacher, $subject, $section, $item);

        return response()->json([
            'data' => $this->serializeDetailItem($questionBankItem),
        ]);
    }

    public function subjectShow(int $subject_id, int $item): JsonResponse
    {
        [$teacher, $subject] = $this->resolveSubjectContext($subject_id);
        $questionBankItem = $this->resolveSubjectItemForContext($teacher, $subject, $item);

        return response()->json([
            'data' => $this->serializeDetailItem($questionBankItem),
        ]);
    }

    public function update(Request $request, int $section_id, int $subject_id, int $item): JsonResponse
    {
        [$teacher, $section, $subject] = $this->resolveContext($section_id, $subject_id);
        $questionBankItem = $this->resolveItemForContext($teacher, $subject, $section, $item);

        $validated = $request->validate($this->questionBankRules(), $this->questionBankMessages());
        $normalized = $this->normalizeQuestionPayload($validated, $request->all());
        $tagSyncPayload = $this->buildQuestionBankTagSyncPayload(
            array_values(array_unique(array_map('intval', $validated['tag_ids'] ?? []))),
            (array) $request->input('tag_relevance', [])
        );

        $questionBankItem->update([
            'section_model_id' => $this->resolveScopedSectionId((string) ($validated['scope'] ?? 'subject'), $section),
            'type' => $normalized['type'],
            'text' => $normalized['text'],
            'option' => $normalized['option'],
            'setting' => $normalized['setting'],
            'answer_key' => $normalized['answer_key'],
            'required' => $normalized['required'],
            'default_points' => $normalized['points'],
        ]);

        $questionBankItem->tags()->sync($tagSyncPayload);
        $questionBankItem->loadMissing(['tags', 'creator', 'subject', 'section']);

        return response()->json([
            'message' => 'Question bank item updated successfully.',
            'data' => $this->serializeDetailItem($questionBankItem->fresh(['tags', 'creator', 'subject', 'section'])),
        ]);
    }

    public function subjectUpdate(Request $request, int $subject_id, int $item): JsonResponse
    {
        [$teacher, $subject] = $this->resolveSubjectContext($subject_id);
        $questionBankItem = $this->resolveSubjectItemForContext($teacher, $subject, $item);

        $validated = $request->validate($this->questionBankRules(false), $this->questionBankMessages());
        $normalized = $this->normalizeQuestionPayload($validated, $request->all());
        $tagSyncPayload = $this->buildQuestionBankTagSyncPayload(
            array_values(array_unique(array_map('intval', $validated['tag_ids'] ?? []))),
            (array) $request->input('tag_relevance', [])
        );

        $questionBankItem->update([
            'section_model_id' => null,
            'type' => $normalized['type'],
            'text' => $normalized['text'],
            'option' => $normalized['option'],
            'setting' => $normalized['setting'],
            'answer_key' => $normalized['answer_key'],
            'required' => $normalized['required'],
            'default_points' => $normalized['points'],
        ]);

        $questionBankItem->tags()->sync($tagSyncPayload);
        $questionBankItem->loadMissing(['tags', 'creator', 'subject', 'section']);

        return response()->json([
            'message' => 'Question bank item updated successfully.',
            'data' => $this->serializeDetailItem($questionBankItem->fresh(['tags', 'creator', 'subject', 'section'])),
        ]);
    }

    public function archive(int $section_id, int $subject_id, int $item): JsonResponse
    {
        [$teacher, $section, $subject] = $this->resolveContext($section_id, $subject_id);
        $questionBankItem = $this->resolveItemForContext($teacher, $subject, $section, $item);

        $questionBankItem->update([
            'is_archived' => true,
            'archived_at' => now(),
        ]);

        return response()->json([
            'message' => 'Question bank item archived successfully.',
            'data' => [
                'id' => $questionBankItem->id,
                'is_archived' => true,
                'archived_at' => optional($questionBankItem->fresh()->archived_at)?->toIso8601String(),
            ],
        ]);
    }

    public function subjectArchive(int $subject_id, int $item): JsonResponse
    {
        [$teacher, $subject] = $this->resolveSubjectContext($subject_id);
        $questionBankItem = $this->resolveSubjectItemForContext($teacher, $subject, $item);

        $questionBankItem->update([
            'is_archived' => true,
            'archived_at' => now(),
        ]);

        return response()->json([
            'message' => 'Question bank item archived successfully.',
            'data' => [
                'id' => $questionBankItem->id,
                'is_archived' => true,
                'archived_at' => optional($questionBankItem->fresh()->archived_at)?->toIso8601String(),
            ],
        ]);
    }

    public function restore(int $section_id, int $subject_id, int $item): JsonResponse
    {
        [$teacher, $section, $subject] = $this->resolveContext($section_id, $subject_id);
        $questionBankItem = $this->resolveItemForContext($teacher, $subject, $section, $item);

        $questionBankItem->update([
            'is_archived' => false,
            'archived_at' => null,
        ]);

        return response()->json([
            'message' => 'Question bank item restored successfully.',
            'data' => [
                'id' => $questionBankItem->id,
                'is_archived' => false,
                'archived_at' => null,
            ],
        ]);
    }

    public function subjectRestore(int $subject_id, int $item): JsonResponse
    {
        [$teacher, $subject] = $this->resolveSubjectContext($subject_id);
        $questionBankItem = $this->resolveSubjectItemForContext($teacher, $subject, $item);

        $questionBankItem->update([
            'is_archived' => false,
            'archived_at' => null,
        ]);

        return response()->json([
            'message' => 'Question bank item restored successfully.',
            'data' => [
                'id' => $questionBankItem->id,
                'is_archived' => false,
                'archived_at' => null,
            ],
        ]);
    }

    private function resolveContext(int $sectionId, int $subjectId): array
    {
        $teacher = auth()->user()?->teacher;
        if (!$teacher) {
            abort(403);
        }

        $section = SectionModel::findOrFail($sectionId);
        $subject = SubjectModel::findOrFail($subjectId);

        $subjectIsAssignedToSection = $section->subjects()
            ->where('subject_models.id', $subject->id)
            ->exists();

        $teacherOwnsContext = SubjectTeacher::query()
            ->where('teacher_model_id', $teacher->id)
            ->where('section_model_id', $section->id)
            ->where('subject_model_id', $subject->id)
            ->exists();

        if (!$subjectIsAssignedToSection || !$teacherOwnsContext) {
            abort(403);
        }

        return [$teacher, $section, $subject];
    }

    private function resolveSubjectContext(int $subjectId): array
    {
        $teacher = auth()->user()?->teacher;
        if (!$teacher) {
            abort(403);
        }

        $subject = SubjectModel::findOrFail($subjectId);

        $hasAccessibleAssignment = $this->accessibleAssignmentsQuery($teacher->id)
            ->where('subject_model_id', $subject->id)
            ->exists();

        if (!$hasAccessibleAssignment) {
            abort(403);
        }

        return [$teacher, $subject];
    }

    private function resolveItemForContext(
        TeacherModel $teacher,
        SubjectModel $subject,
        SectionModel $section,
        int $itemId
    ): QuestionBankItem {
        return QuestionBankItem::query()
            ->with([
                'tags' => fn ($tagQuery) => $tagQuery->orderBy('name'),
                'creator:id,first_name,last_name',
                'subject:id,subject_name,grade_level',
                'section:id,section_name',
            ])
            ->where('id', $itemId)
            ->where('created_by_teacher_id', $teacher->id)
            ->where('subject_model_id', $subject->id)
            ->where(function ($scopeQuery) use ($section) {
                $scopeQuery->whereNull('section_model_id')
                    ->orWhere('section_model_id', $section->id);
            })
            ->firstOrFail();
    }

    private function resolveSubjectItemForContext(
        TeacherModel $teacher,
        SubjectModel $subject,
        int $itemId
    ): QuestionBankItem {
        return QuestionBankItem::query()
            ->with([
                'tags' => fn ($tagQuery) => $tagQuery->orderBy('name'),
                'creator:id,first_name,last_name',
                'subject:id,subject_name,grade_level',
                'section:id,section_name',
            ])
            ->where('id', $itemId)
            ->where('created_by_teacher_id', $teacher->id)
            ->where('subject_model_id', $subject->id)
            ->whereNull('section_model_id')
            ->firstOrFail();
    }

    private function questionBankRules(bool $allowSectionScope = true): array
    {
        $rules = [
            'type' => ['required', 'string', Rule::in($this->allowedQuestionTypes())],
            'text' => ['required', 'string'],
            'option' => ['nullable', 'array'],
            'option.*' => ['nullable', 'string'],
            'answer_key' => ['nullable'],
            'answer_key.*.left' => ['nullable', 'string'],
            'answer_key.*.right' => ['nullable', 'string'],
            'pairs' => ['nullable', 'array'],
            'pairs.*.left' => ['nullable', 'string'],
            'pairs.*.right' => ['nullable', 'string'],
            'match_left' => ['nullable', 'array'],
            'match_left.*' => ['nullable', 'string'],
            'match_right' => ['nullable', 'array'],
            'match_right.*' => ['nullable', 'string'],
            'use_pair_points' => ['nullable', 'boolean'],
            'pair_points' => ['nullable', 'array'],
            'pair_points.*' => ['nullable', 'integer', 'min:0'],
            'required' => ['nullable', 'boolean'],
            'default_points' => ['nullable', 'integer', 'min:0'],
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['integer', 'exists:tags,id'],
            'tag_relevance' => ['nullable', 'array'],
            'tag_relevance.*' => ['nullable', 'string', Rule::in($this->allowedTagRelevanceLevels())],
        ];

        if ($allowSectionScope) {
            $rules['scope'] = ['nullable', 'string', Rule::in(['subject', 'section'])];
        }

        return $rules;
    }

    private function questionBankMessages(): array
    {
        return [
            'text.required' => 'Please enter a question.',
        ];
    }

    private function normalizeQuestionPayload(array $validated, array $rawPayload): array
    {
        $questionPayload = $validated;
        $questionPayload['points'] = $validated['default_points'] ?? 1;

        return $this->questionContentNormalizer->normalize($questionPayload, $rawPayload, '');
    }

    private function resolveScopedSectionId(string $scope, SectionModel $section): ?int
    {
        return $scope === 'section' ? (int) $section->id : null;
    }

    private function accessibleAssignmentsQuery(int $teacherId)
    {
        return SubjectTeacher::query()
            ->where('teacher_model_id', $teacherId)
            ->whereHas('section.schoolYear', function ($schoolYearQuery) {
                $schoolYearQuery->where(function ($query) {
                    $query->where('is_active', true)
                        ->orWhere('teacher_audit_enabled', true);
                });
            });
    }

    private function questionBankSubjectsForTeacher(TeacherModel $teacher)
    {
        return SubjectModel::query()
            ->select('subject_models.id', 'subject_models.subject_name', 'subject_models.grade_level')
            ->whereIn('subject_models.id', $this->accessibleAssignmentsQuery($teacher->id)->select('subject_model_id'))
            ->orderBy('subject_models.subject_name')
            ->orderBy('subject_models.grade_level')
            ->get();
    }

    private function questionBankSubjectAssignmentsForTeacher(TeacherModel $teacher)
    {
        return $this->accessibleAssignmentsQuery($teacher->id)
            ->with([
                'section:id,section_name,grade_level',
                'subject:id,subject_name,grade_level',
            ])
            ->get()
            ->filter(fn (SubjectTeacher $assignment) => $assignment->section && $assignment->subject)
            ->map(function (SubjectTeacher $assignment) {
                $gradeLevel = (string) ($assignment->section->grade_level ?? $assignment->subject->grade_level ?? '');

                return [
                    'grade_level' => $gradeLevel,
                    'grade_label' => $gradeLevel === '' ? 'No grade level' : 'Grade ' . $gradeLevel,
                    'section_id' => (int) $assignment->section->id,
                    'section_name' => $assignment->section->section_name,
                    'subject_id' => (int) $assignment->subject->id,
                    'subject_name' => $assignment->subject->subject_name,
                    'subject_label' => $this->questionBankSubjectLabel($assignment->subject),
                ];
            })
            ->unique(fn (array $assignment) => $assignment['section_id'] . ':' . $assignment['subject_id'])
            ->sortBy(fn (array $assignment) => sprintf(
                '%05s|%s|%s',
                preg_replace('/\D+/', '', $assignment['grade_level']) ?: '99999',
                mb_strtolower($assignment['section_name']),
                mb_strtolower($assignment['subject_name'])
            ))
            ->values();
    }

    private function accessibleSectionIdsForSubject(TeacherModel $teacher, SubjectModel $subject): array
    {
        return $this->accessibleAssignmentsQuery($teacher->id)
            ->where('subject_model_id', $subject->id)
            ->pluck('section_model_id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    private function serializeQuestionBankSubject(SubjectModel $subject): array
    {
        return [
            'id' => (int) $subject->id,
            'name' => $subject->subject_name,
            'grade_level' => $subject->grade_level,
            'label' => $this->questionBankSubjectLabel($subject),
        ];
    }

    private function questionBankSubjectLabel(SubjectModel $subject): string
    {
        return (string) $subject->subject_name;
    }

    private function buildQuestionBankTagSyncPayload(array $tagIds, array $tagRelevance = []): array
    {
        $tagIds = array_values(array_unique(array_filter(array_map('intval', $tagIds))));

        $payload = [];
        foreach ($tagIds as $tagId) {
            $tagIdKey = (string) $tagId;
            $relevanceRaw = $tagRelevance[$tagId] ?? $tagRelevance[$tagIdKey] ?? null;

            $payload[$tagId] = [
                'relevance_level' => $this->normalizeTagRelevanceLevel($relevanceRaw === null ? null : (string) $relevanceRaw),
                'coverage_ratio' => null,
            ];
        }

        return $payload;
    }

    private function buildImportedQuestionTagSyncPayload(QuestionBankItem $item): array
    {
        $tagIds = $item->tags
            ->pluck('id')
            ->map(fn ($tagId) => (int) $tagId)
            ->filter(fn ($tagId) => $tagId > 0)
            ->values()
            ->all();

        if (empty($tagIds)) {
            return [];
        }

        $fallbackCoverageRatio = $this->fallbackCoverageRatio(count($tagIds));
        $payload = [];

        foreach ($item->tags as $tag) {
            $tagId = (int) $tag->id;
            if ($tagId <= 0) {
                continue;
            }

            $payload[$tagId] = [
                'relevance_level' => $this->normalizeTagRelevanceLevel(optional($tag->pivot)->relevance_level),
                'coverage_ratio' => $fallbackCoverageRatio,
            ];
        }

        return $payload;
    }

    private function allowedQuestionTypes(): array
    {
        return ['multiple_choice', 'short_answer', 'true_false', 'matching', 'essay', 'numerical'];
    }

    private function syncQuizTagCoverageFromQuestionTags(Quiz $quiz): void
    {
        $questionTagCounts = [];
        $questionTagRelevanceCounts = [];

        $questions = $quiz->questions()->with('tags:id')->get(['id']);
        foreach ($questions as $question) {
            foreach ($question->tags as $tag) {
                $tagId = (int) $tag->id;
                if ($tagId <= 0) {
                    continue;
                }

                $questionTagCounts[$tagId] = ($questionTagCounts[$tagId] ?? 0) + 1;
                $relevanceLevel = $this->normalizeTagRelevanceLevel(optional($tag->pivot)->relevance_level);
                $questionTagRelevanceCounts[$tagId][$relevanceLevel] = ($questionTagRelevanceCounts[$tagId][$relevanceLevel] ?? 0) + 1;
            }
        }

        if (empty($questionTagCounts)) {
            $quiz->tags()->sync([]);
            return;
        }

        $totalAssignments = array_sum($questionTagCounts);
        if ($totalAssignments <= 0) {
            $quiz->tags()->sync([]);
            return;
        }

        $syncPayload = [];
        foreach ($questionTagCounts as $tagId => $count) {
            $tagRelevanceVotes = $questionTagRelevanceCounts[$tagId] ?? [];
            $aggregatedRelevanceLevel = $this->defaultTagRelevanceLevel();
            $selectedVotes = -1;
            $selectedWeight = -INF;

            foreach ($tagRelevanceVotes as $level => $votes) {
                $voteCount = (int) $votes;
                $weight = $this->resolveTagRelevanceWeight((string) $level);
                if ($voteCount > $selectedVotes || ($voteCount === $selectedVotes && $weight > $selectedWeight)) {
                    $selectedVotes = $voteCount;
                    $selectedWeight = $weight;
                    $aggregatedRelevanceLevel = $this->normalizeTagRelevanceLevel((string) $level);
                }
            }

            $syncPayload[$tagId] = [
                'relevance_level' => $aggregatedRelevanceLevel,
                'coverage_ratio' => max(0.0, min(1.0, ((int) $count) / $totalAssignments)),
            ];
        }

        $quiz->tags()->sync($syncPayload);
    }

    private function syncQuestionTagCoverageFromQuestionTags(Quiz $quiz): void
    {
        $questions = $quiz->questions()->with('tags:id')->get(['id']);
        if ($questions->isEmpty()) {
            return;
        }

        $questionTagCounts = [];
        foreach ($questions as $question) {
            foreach ($question->tags as $tag) {
                $tagId = (int) $tag->id;
                if ($tagId <= 0) {
                    continue;
                }

                $questionTagCounts[$tagId] = ($questionTagCounts[$tagId] ?? 0) + 1;
            }
        }

        $totalAssignments = array_sum($questionTagCounts);

        foreach ($questions as $question) {
            $syncPayload = [];
            foreach ($question->tags as $tag) {
                $tagId = (int) $tag->id;
                if ($tagId <= 0) {
                    continue;
                }

                $syncPayload[$tagId] = [
                    'relevance_level' => $this->normalizeTagRelevanceLevel(optional($tag->pivot)->relevance_level),
                    'coverage_ratio' => $totalAssignments > 0
                        ? max(0.0, min(1.0, ((int) ($questionTagCounts[$tagId] ?? 0)) / $totalAssignments))
                        : 0.0,
                ];
            }

            if (!empty($syncPayload)) {
                $question->tags()->syncWithoutDetaching($syncPayload);
            }
        }
    }

    private function serializeListItem(QuestionBankItem $item): array
    {
        return [
            'id' => $item->id,
            'type' => $item->type,
            'text' => $item->text,
            'required' => (bool) $item->required,
            'default_points' => (int) ($item->default_points ?? 1),
            'scope' => $item->section_model_id ? 'section' : 'subject',
            'is_archived' => (bool) $item->is_archived,
            'archived_at' => optional($item->archived_at)?->toIso8601String(),
            'subject' => $item->subject ? [
                'id' => $item->subject->id,
                'name' => $item->subject->subject_name,
                'grade_level' => $item->subject->grade_level,
            ] : null,
            'section' => $item->section ? [
                'id' => $item->section->id,
                'name' => $item->section->section_name,
            ] : null,
            'tags' => $this->serializeTags($item),
            'created_at' => optional($item->created_at)?->toIso8601String(),
            'updated_at' => optional($item->updated_at)?->toIso8601String(),
        ];
    }

    private function serializeDetailItem(QuestionBankItem $item): array
    {
        return [
            ...$this->serializeListItem($item),
            'option' => $item->option ?? [],
            'setting' => $item->setting ?? null,
            'answer_key' => $item->answer_key,
            'creator' => $item->creator ? [
                'id' => $item->creator->id,
                'first_name' => $item->creator->first_name,
                'last_name' => $item->creator->last_name,
            ] : null,
        ];
    }

    private function serializeQuizListItem(Quiz $quiz): array
    {
        $status = $quiz->status;
        if ($status instanceof \BackedEnum) {
            $status = $status->value;
        }

        return [
            'id' => $quiz->id,
            'title' => $quiz->title,
            'status' => strtolower((string) $status),
            'visible' => (bool) $quiz->visible,
            'questions_count' => (int) ($quiz->questions_count ?? 0),
            'total_score' => (int) ($quiz->total_score ?? 0),
            'section' => $quiz->section ? [
                'id' => (int) $quiz->section->id,
                'name' => $quiz->section->section_name,
            ] : null,
            'updated_at' => optional($quiz->updated_at)?->toIso8601String(),
            'edit_url' => route('teacher.quiz.edit', [
                'section' => $quiz->section_model_id,
                'subject' => $quiz->subject_model_id,
                'quiz' => $quiz->id,
            ]),
            'settings_url' => route('teacher.quiz.settings', [
                'section' => $quiz->section_model_id,
                'subject' => $quiz->subject_model_id,
                'quiz' => $quiz->id,
            ]),
        ];
    }

    private function serializeTags(QuestionBankItem $item): array
    {
        return $item->tags
            ->map(function (Tag $tag) {
                return [
                    'id' => $tag->id,
                    'name' => $tag->name,
                    'category' => $tag->category,
                    'relevance' => strtolower((string) (optional($tag->pivot)->relevance_level ?? $this->defaultTagRelevanceLevel())),
                ];
            })
            ->values()
            ->all();
    }
}
