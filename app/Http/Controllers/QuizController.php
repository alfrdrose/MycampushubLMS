<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesTagMetrics;
use App\Enums\QuizStatus;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\Response as QuizResponse;
use App\Models\SectionModel;
use App\Models\StudentModel;
use App\Models\SubjectModel;
use App\Models\SubjectGrading;
use App\Models\Tag;
use App\Services\QuestionContentNormalizer;
use App\Services\SectionStudentRoster;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class QuizController extends Controller
{
    use HandlesTagMetrics;

    public function __construct(private readonly QuestionContentNormalizer $questionContentNormalizer)
    {
    }

    private function maybeShuffleOnPublishTransition(Quiz $quiz, bool $wasPublished, bool $nowPublished): void
    {
        if ($wasPublished || !$nowPublished) {
            return;
        }

        $settingRaw = $quiz->setting;
        if (is_string($settingRaw)) {
            $decoded = json_decode($settingRaw, true);
            $setting = is_array($decoded) ? $decoded : [];
        } elseif (is_array($settingRaw)) {
            $setting = $settingRaw;
        } else {
            $setting = [];
        }

        $shuffleEnabled = (bool)($setting['shuffle_questions_on_publish'] ?? false);
        if (!$shuffleEnabled) {
            return;
        }

        $pageSize = (int)($setting['pagination'] ?? 10);
        if ($pageSize < 1) $pageSize = 1;
        if ($pageSize > 25) $pageSize = 25;

        $this->shuffleQuestionsWithinPages($quiz->fresh(), $pageSize, 10);
    }

    private function shuffleQuestionsWithinPages(Quiz $quiz, int $pageSize, int $maxPages = 10): void
    {
        $size = max(1, min(25, (int)$pageSize));
        $pages = max(1, min(10, (int)$maxPages));

        DB::transaction(function () use ($quiz, $size, $pages) {
            $questions = $quiz->questions()->orderBy('order')->get();
            if ($questions->isEmpty()) {
                return;
            }

            $groups = [];
            foreach ($questions as $q) {
                $order = (int)($q->order ?? 0);
                if ($order < 1) {
                    continue;
                }
                $page = (int)floor(($order - 1) / $size) + 1;
                if ($page < 1 || $page > $pages) {
                    continue;
                }
                $groups[$page] ??= [];
                $groups[$page][] = $q;
            }

            foreach ($groups as $page => $qs) {
                if (count($qs) <= 1) {
                    continue;
                }

                shuffle($qs);
                $start = (($page - 1) * $size) + 1;

                foreach ($qs as $i => $q) {
                    $q->order = $start + $i;
                    $q->save();
                }
            }
        });
    }

    private function normalizeQuestionTagIds($tagIds): array
    {
        if (!is_array($tagIds)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map(static function ($tagId) {
            if (!is_numeric($tagId)) {
                return null;
            }

            $normalized = (int) $tagId;
            return $normalized > 0 ? $normalized : null;
        }, $tagIds))));
    }

    private function syncQuizTagCoverageFromQuestionTags(Quiz $quiz, bool $forceZeroWhenNoAssignments = false): void
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

                $relevanceLevel = $this->normalizeTagRelevanceLevel(optional($tag->pivot)->relevance_level);
                $coverageRatio = 0.0;
                if ($totalAssignments > 0) {
                    $coverageRatio = max(0.0, min(1.0, ((int) ($questionTagCounts[$tagId] ?? 0)) / $totalAssignments));
                }

                $syncPayload[$tagId] = [
                    'relevance_level' => $relevanceLevel,
                    'coverage_ratio' => $coverageRatio,
                ];
            }

            if (!empty($syncPayload)) {
                $question->tags()->syncWithoutDetaching($syncPayload);
            }
        }
    }

    public function create($section_id, $subject_id)
    {
        $section = SectionModel::findOrFail($section_id);
        $subject = SubjectModel::findOrFail($subject_id);
        $classworkTypes = SubjectGrading::where('subject_id', $subject->id)->get();

        return view('pages.teacher.pages.classwork.quiz.create', [
            'title' => 'Create Quiz',
            'section' => $section,
            'subject' => $subject,
            'classworkTypes' => $classworkTypes,
            'preloader' => false,
            'darkmode' => true,
            'scrollup' => true,
            'banner' => true,
            'sidebar' => true,
        ]);
    }

    public function store(Request $request, $section_id, $subject_id)
{
    $section = SectionModel::findOrFail($section_id);
    $subject = SubjectModel::findOrFail($subject_id);

    $request->merge([
        'title' => trim((string) $request->input('title')),
    ]);

    $validated = $request->validate([
        'title' => [
            'required',
            'string',
            'max:255',
            Rule::unique('quizzes', 'title')->where(function ($query) use ($section, $subject) {
                return $query
                    ->where('section_model_id', $section->id)
                    ->where('subject_model_id', $subject->id);
            }),
        ],
        'description' => 'nullable|string',
        'grading_period' => 'required|string',
        'classwork_type' => 'required|exists:subject_grading,id',
        'accepting' => 'boolean',
        'max_attempt' => 'integer|min:1',
        'total_score' => 'integer|min:0',
    ], [
        'title.unique' => 'A quiz with this title already exists for this class.',
    ]);

    // 🔥 Get next position within same section + subject + grading period
    $maxPosition = Quiz::where('section_model_id', $section->id)
        ->where('subject_model_id', $subject->id)
        ->where('grading_period', $validated['grading_period'])
        ->max('position');

    Quiz::create([
        'title' => $validated['title'],
        'description' => $validated['description'] ?? null,
        'grading_period' => $validated['grading_period'],
        'classwork_type' => $validated['classwork_type'],
        'status' => $validated['status'] ?? 'unpublished',
        'visible' => false,
        'is_open' => false,
        'open_date' => null,
        'open_time' => null,
        'accepting' => $validated['accepting'] ?? false,
        'max_attempt' => $validated['max_attempt'] ?? 1,
        'total_score' => $validated['total_score'] ?? 0,
        'questions' => json_encode([]),
        'section_model_id' => $section->id,
        'subject_model_id' => $subject->id,
        'setting' => json_encode([]),

        // ✅ NEW
        'position' => ($maxPosition ?? 0) + 1,
    ]);

    return redirect()->route('teacher.classwork.view', [
        'section_id' => $section->id,
        'subject_id' => $subject->id
    ])->with('success', 'Quiz created successfully!');
}

    public function update(Request $request, $section_id, $subject_id, $quizId)
    {
        $quiz = Quiz::findOrFail($quizId);

        $wasPublished = $quiz->status === QuizStatus::PUBLISHED;

        $request->merge([
            'title' => trim((string) $request->input('title')),
        ]);

        $validated = $request->validate([
            'title' => [
                'required',
                'string',
                'max:255',
                Rule::unique('quizzes', 'title')
                    ->where(function ($query) use ($section_id, $subject_id) {
                        return $query
                            ->where('section_model_id', $section_id)
                            ->where('subject_model_id', $subject_id);
                    })
                    ->ignore($quiz->id),
            ],
            'description' => 'nullable|string',
            'grading_period' => 'required|string',
            'status' => 'required|in:unpublished,published,closed',
            // 'due_date' => 'nullable|date',
            'accepting' => 'boolean',
            'max_attempt' => 'integer|min:1',
            'total_score' => 'integer|min:0',
        ], [
            'title.unique' => 'A quiz with this title already exists for this class.',
        ]);

        $updateData = $validated;
        if ($validated['status'] === 'published' && !$quiz->time_posted) {
            $updateData['time_posted'] = now();
        }

        $quiz->update($updateData);

        $nowPublished = ($validated['status'] === 'published');
        $this->maybeShuffleOnPublishTransition($quiz->fresh(), $wasPublished, $nowPublished);

        return redirect()->route('teacher.classwork.view', [
            'section_id' => $section_id,
            'subject_id' => $subject_id
        ])->with('success', 'Quiz updated successfully!');
    }

    public function add($section_id, $subject_id, Quiz $quiz)
    {
        $section = SectionModel::findOrFail($section_id);
        $subject = SubjectModel::findOrFail($subject_id);
        $questions = $quiz->questions()->with('tags:id,name,category')->orderBy('order')->get();
        $availableTags = Tag::query()
            ->orderBy('name')
            ->get(['id', 'name', 'category']);
        $quizTagMetrics = $quiz->tags()
            ->orderBy('name')
            ->get(['tags.id', 'tags.name'])
            ->mapWithKeys(function ($tag) {
                return [
                    (string) $tag->id => [
                        'relevance' => strtolower((string) (optional($tag->pivot)->relevance_level ?? $this->defaultTagRelevanceLevel())),
                        'coverage' => optional($tag->pivot)->coverage_ratio !== null
                            ? round(((float) optional($tag->pivot)->coverage_ratio) * 100, 2)
                            : null,
                    ],
                ];
            })
            ->all();

        return view('pages.teacher.pages.classwork.quiz.add_question', [
            'title' => $quiz->title . ' - Edit',
            'section' => $section,
            'subject' => $subject,
            'quiz' => $quiz,
            'questions' => $questions,
            'availableTags' => $availableTags,
            'quizTagMetrics' => $quizTagMetrics,
            'preloader' => false,
            'darkmode' => true,
            'scrollup' => true,
            'banner' => true,
            'sidebar' => true,
        ]);
    }

    public function edit($section_id, $subject_id, $quizId)
    {
        $section = SectionModel::findOrFail($section_id);
        $subject = SubjectModel::findOrFail($subject_id);
        $quiz = Quiz::findOrFail($quizId);

        $questions = $quiz->questions()->with('tags:id,name,category')->orderBy('order')->get();
        $availableTags = Tag::query()
            ->orderBy('name')
            ->get(['id', 'name', 'category']);
        $quizTagMetrics = $quiz->tags()
            ->orderBy('name')
            ->get(['tags.id', 'tags.name'])
            ->mapWithKeys(function ($tag) {
                return [
                    (string) $tag->id => [
                        'relevance' => strtolower((string) (optional($tag->pivot)->relevance_level ?? $this->defaultTagRelevanceLevel())),
                        'coverage' => optional($tag->pivot)->coverage_ratio !== null
                            ? round(((float) optional($tag->pivot)->coverage_ratio) * 100, 2)
                            : null,
                    ],
                ];
            })
            ->all();

        return view('pages.teacher.pages.classwork.quiz.edit_question', [
            'title' => $quiz->title . ' - Edit',
            'section' => $section,
            'subject' => $subject,
            'quiz' => $quiz,
            'questions' => $questions,
            'availableTags' => $availableTags,
            'quizTagMetrics' => $quizTagMetrics,
            'preloader' => false,
            'darkmode' => true,
            'scrollup' => true,
            'banner' => true,
            'sidebar' => true,
        ]);
    }

    public function settings($section_id, $subject_id, $quizId)
    {
        $section = SectionModel::findOrFail($section_id);
        $subject = SubjectModel::findOrFail($subject_id);
        $quiz = Quiz::findOrFail($quizId);
        $this->syncQuizTagCoverageFromQuestionTags($quiz->fresh(), true);
        $quiz = $quiz->fresh();
        $assignedTags = $quiz->tags()
            ->orderBy('name')
            ->get(['tags.id', 'tags.name', 'tags.category']);

        return view('pages.teacher.pages.classwork.quiz.settings', [
            'title' => $quiz->title . ' - Settings',
            'section' => $section,
            'subject' => $subject,
            'quiz' => $quiz,
            'assignedTags' => $assignedTags,
            'preloader' => false,
            'darkmode' => true,
            'scrollup' => true,
            'banner' => true,
            'sidebar' => true,
        ]);
    }

    public function settingsUpdate(Request $request, $section_id, $subject_id, $quizId)
    {
        $quiz = Quiz::findOrFail($quizId);

        $wasPublished = ((int) ($quiz->visible ?? 0) === 1) && ((int) ($quiz->is_open ?? 0) === 1);
        $maxAttemptRaw = $request->input('max_attempt', $quiz->max_attempt ?? 1);
        if (is_numeric($maxAttemptRaw)) {
            $request->merge([
                'max_attempt' => (int)$maxAttemptRaw,
            ]);
        }

        $validated = $request->validate([
            'visible' => 'required|boolean',
            'is_open' => 'nullable|boolean',
            'open_date' => 'nullable|date|required_if:visible,1|required_with:open_time',
            'open_time' => 'nullable|date_format:H:i|required_if:visible,1|required_with:open_date',
            'due_date' => 'nullable|date|required_if:visible,1|required_with:due_time',
            'due_time' => 'nullable|date_format:H:i|required_if:visible,1|required_with:due_date',
            'total_score' => 'nullable|integer|min:1',
            'accepting' => 'required|boolean',
            'max_attempt' => 'required|integer|min:1',
            'pagination' => 'nullable|integer|min:1|max:25',
            'shuffle_questions_on_publish' => 'nullable|boolean',
            'student_can_review' => 'required|boolean',
            'student_can_view_score' => 'required|boolean',
            'student_can_view_correct_answers' => 'required|boolean',
        ], [
            'open_date.required_if' => 'Open date is required when the quiz is visible.',
            'open_time.required_if' => 'Open time is required when the quiz is visible.',
            'open_date.required_with' => 'Open date is required when open time is set.',
            'open_time.required_with' => 'Open time is required when open date is set.',
            'due_date.required_if' => 'Due date is required when the quiz is visible.',
            'due_time.required_if' => 'Due time is required when the quiz is visible.',
            'due_date.required_with' => 'Due date is required when due time is set.',
            'due_time.required_with' => 'Due time is required when due date is set.',
            'max_attempt.required' => 'Maximum attempts is required.',
            'max_attempt.integer' => 'Maximum attempts must be a whole number.',
            'max_attempt.min' => 'Maximum attempts must be at least 1.',
        ]);

        $updateData = $validated;
        $visible = (bool) ($validated['visible'] ?? false);
        $isOpen = $visible ? (int) ($validated['is_open'] ?? 0) : 0;

        // Settings form currently doesn't submit total_score; keep existing value.
        if (!array_key_exists('total_score', $validated) || $validated['total_score'] === null) {
            $updateData['total_score'] = $quiz->total_score;
        }

        $updateData['open_date'] = $validated['open_date'] ?? null;
        $updateData['open_time'] = $validated['open_time'] ?? null;
        $updateData['due_date'] = (($validated['due_date'] ?? null) && ($validated['due_time'] ?? null))
            ? Carbon::parse($validated['due_date'] . ' ' . $validated['due_time'])
            : null;
        $updateData['time_posted'] = (($validated['open_date'] ?? null) && ($validated['open_time'] ?? null))
            ? Carbon::parse($validated['open_date'] . ' ' . $validated['open_time'])
            : null;

        $updateData['visible'] = $visible;
        $updateData['is_open'] = $isOpen;
        $updateData['status'] = !$visible
            ? QuizStatus::UNPUBLISHED->value
            : ($isOpen ? QuizStatus::PUBLISHED->value : QuizStatus::SCHEDULED->value);

        if (($validated['open_date'] ?? null) && ($validated['open_time'] ?? null) && $updateData['due_date']) {
            $openAt = Carbon::parse($validated['open_date'] . ' ' . $validated['open_time']);
            if ($updateData['due_date']->lt($openAt)) {
                return back()->withErrors([
                    'due_date' => 'Due date and time must be on or after the open date and time.',
                ])->withInput();
            }
        }

        unset(
            $updateData['due_time']
        );

        $settingRaw = $quiz->setting ?? [];
        if (is_string($settingRaw)) {
            $decoded = json_decode($settingRaw, true);
            $setting = is_array($decoded) ? $decoded : [];
        } elseif (is_array($settingRaw)) {
            $setting = $settingRaw;
        } else {
            $setting = [];
        }
        $previousShuffleEnabled = (bool)($setting['shuffle_questions_on_publish'] ?? false);
        $pagination = isset($validated['pagination']) ? (int)$validated['pagination'] : (int)($setting['pagination'] ?? 10);
        if ($pagination < 1) $pagination = 1;
        if ($pagination > 25) $pagination = 25;
        $setting['pagination'] = $pagination;

        $setting['shuffle_questions_on_publish'] = (bool)($validated['shuffle_questions_on_publish'] ?? ($setting['shuffle_questions_on_publish'] ?? false));
        $setting['student_can_review'] = (bool)($validated['student_can_review'] ?? true);
        $setting['student_can_view_score'] = (bool)($validated['student_can_view_score'] ?? true);
        $setting['student_can_view_correct_answers'] = (bool)($validated['student_can_view_correct_answers'] ?? true);
        $updateData['setting'] = json_encode($setting);

        $quiz->update($updateData);
        $this->syncQuizTagCoverageFromQuestionTags($quiz->fresh(), true);

        $quizFresh = $quiz->fresh();
        $nowPublished = ((int) ($quizFresh->visible ?? 0) === 1) && ((int) ($quizFresh->is_open ?? 0) === 1);

        // Shuffle if newly published or if shuffle was just enabled on an already published quiz.
        $newShuffleEnabled = (bool)($setting['shuffle_questions_on_publish'] ?? false);
        $shouldShuffleNow = $newShuffleEnabled && $nowPublished && (
            !$wasPublished || ($wasPublished && !$previousShuffleEnabled)
        );

        if ($shouldShuffleNow) {
            $this->shuffleQuestionsWithinPages($quizFresh, $pagination, 10);
        } else {
            $this->maybeShuffleOnPublishTransition($quizFresh, $wasPublished, $nowPublished);
        }

        return redirect()->route('teacher.quiz.settings', [
            'section' => $section_id,
            'subject' => $subject_id,
            'quiz' => $quizFresh->id,
        ])->with('success', 'Quiz settings updated successfully!');
    }

    public function updateTags(Request $request, $section_id, $subject_id, Quiz $quiz)
    {
        $this->syncQuizTagCoverageFromQuestionTags($quiz->fresh(), true);
        $quizFresh = $quiz->fresh();
        $tagIds = $quizFresh->tags()
            ->pluck('tags.id')
            ->map(static fn ($id) => (int) $id)
            ->all();

        return response()->json([
            'success' => true,
            'read_only' => true,
            'tag_ids' => $tagIds,
        ]);
    }

    

    public function updateQuestions(Request $request, $section_id, $subject_id, $quizId)
    {
        $section = SectionModel::findOrFail($section_id);
        $subject = SubjectModel::findOrFail($subject_id);
        $quiz = Quiz::findOrFail($quizId);

        $wasPublished = $quiz->status === QuizStatus::PUBLISHED;

        $validated = $request->validate([
            'description' => 'nullable|string',
            'questions' => 'required|array|min:1',
            'questions.*.id' => 'nullable|integer|exists:questions,id',
            'questions.*.order' => 'required|integer|min:1',
            'questions.*.page_hint' => 'nullable|integer|min:1',
            'questions.*.type' => 'required|string|in:multiple_choice,short_answer,true_false,matching,essay,numerical',
            'questions.*.text' => 'required|string',

            'questions.*.option' => 'nullable|array',
            'questions.*.option.*' => 'nullable|string',
            'questions.*.answer_key' => 'nullable',
            // matching answer_key (new) - keep nested pairs in validated payload
            'questions.*.answer_key.*.left' => 'nullable|string',
            'questions.*.answer_key.*.right' => 'nullable|string',

            'questions.*.pairs' => 'nullable|array',
            'questions.*.pairs.*.left' => 'nullable|string',
            'questions.*.pairs.*.right' => 'nullable|string',

            // matching pool (new)
            'questions.*.match_left' => 'nullable|array',
            'questions.*.match_left.*' => 'nullable|string',
            'questions.*.match_right' => 'nullable|array',
            'questions.*.match_right.*' => 'nullable|string',

            'questions.*.use_pair_points' => 'nullable|boolean',
            'questions.*.pair_points' => 'nullable|array',
            'questions.*.pair_points.*' => 'nullable|integer|min:0',

            'questions.*.required' => 'nullable|boolean',
            'questions.*.points' => 'nullable|integer|min:0',
            'questions.*.tag_ids' => 'nullable|array',
            'questions.*.tag_ids.*' => 'integer|exists:tags,id',
            'questions.*.tag_relevance' => 'nullable|array',
            'questions.*.tag_relevance.*' => ['nullable', 'string', Rule::in($this->allowedTagRelevanceLevels())],
        ], [
            'questions.*.text.required' => 'Please enter a question.',
        ]);

        // Keep access to the raw submitted payload (nested arrays)
        // because Laravel validation may drop unvalidated nested keys.
        $rawQuestions = $request->input('questions', []);

        // Pagination value is needed to derive a fallback page_hint when not provided by the client.
        $settingRaw = $quiz->setting ?? [];
        if (is_string($settingRaw)) {
            $decoded = json_decode($settingRaw, true);
            $setting = is_array($decoded) ? $decoded : [];
        } elseif (is_array($settingRaw)) {
            $setting = $settingRaw;
        } else {
            $setting = [];
        }
        $pagination = (int)($request->input('pagination', $setting['pagination'] ?? 10));
        if ($pagination < 1) $pagination = 1;
        if ($pagination > 25) $pagination = 25;

        $existingQuestionIds = $quiz->questions()->pluck('id')->toArray();
        $updatedQuestionIds = [];
        $totalPoints = 0;

        foreach ($validated['questions'] as $i => $q) {
            $rawQ = (is_array($rawQuestions) && array_key_exists($i, $rawQuestions) && is_array($rawQuestions[$i]))
                ? $rawQuestions[$i]
                : [];
            $normalizedQuestion = $this->questionContentNormalizer->normalize($q, $rawQ, "questions.$i");
            $points = (int) ($normalizedQuestion['points'] ?? 1);

            $pageHint = isset($q['page_hint']) ? (int) $q['page_hint'] : (int) ceil($q['order'] / max(1, $pagination));
            if ($pageHint < 1) {
                $pageHint = 1;
            }

            $payload = [
                'order' => $q['order'],
                'page_hint' => $pageHint,
                'type' => $normalizedQuestion['type'],
                'text' => $normalizedQuestion['text'],
                'option' => $normalizedQuestion['option'],
                'setting' => $normalizedQuestion['setting'],
                'answer_key' => $normalizedQuestion['answer_key'],
                'required' => $normalizedQuestion['required'],
                'points' => $points,
            ];
            $questionTagIds = $this->normalizeQuestionTagIds($rawQ['tag_ids'] ?? ($q['tag_ids'] ?? []));
            $rawTagRelevance = (isset($rawQ['tag_relevance']) && is_array($rawQ['tag_relevance']))
                ? $rawQ['tag_relevance']
                : ((isset($q['tag_relevance']) && is_array($q['tag_relevance'])) ? $q['tag_relevance'] : []);
            $questionTagSyncPayload = [];
            $fallbackCoverageRatio = $this->fallbackCoverageRatio(max(1, count($questionTagIds)));
            foreach ($questionTagIds as $tagId) {
                $relevanceRaw = $rawTagRelevance[$tagId] ?? $rawTagRelevance[(string) $tagId] ?? null;
                $questionTagSyncPayload[$tagId] = [
                    'relevance_level' => $this->normalizeTagRelevanceLevel($relevanceRaw === null ? null : (string) $relevanceRaw),
                    'coverage_ratio' => $fallbackCoverageRatio,
                ];
            }

            if (!empty($q['id']) && in_array($q['id'], $existingQuestionIds)) {
                $question = $quiz->questions()->find($q['id']);
                $question->update($payload);
            } else {
                $question = $quiz->questions()->create($payload);
            }

            $question->tags()->sync($questionTagSyncPayload);

            $totalPoints += $points;
            $updatedQuestionIds[] = $question->id;
        }

        $questionsToDelete = array_diff($existingQuestionIds, $updatedQuestionIds);
        if (!empty($questionsToDelete)) {
            DB::table('taggables')
                ->where('taggable_type', Question::class)
                ->whereIn('taggable_id', $questionsToDelete)
                ->delete();
            $quiz->questions()->whereIn('id', $questionsToDelete)->delete();
        }

        // Persist the pagination value we clamped earlier.
        $setting['pagination'] = $pagination;
        $description = $validated['description'] ?? $quiz->description;

        $quiz->update([
            'questions' => $updatedQuestionIds,
            'status' => 'published',
            'time_posted' => $quiz->time_posted ?? now(),
            'total_score' => $totalPoints,
            'setting' => $setting,
            'description' => $description,
        ]);
        $quizFresh = $quiz->fresh();
        $this->syncQuestionTagCoverageFromQuestionTags($quizFresh);
        $this->syncQuizTagCoverageFromQuestionTags($quizFresh->fresh(), true);

        $this->maybeShuffleOnPublishTransition($quiz->fresh(), $wasPublished, true);

        return redirect()->route('teacher.quiz.settings', [$section->id, $subject->id, $quiz->id])
            ->with('success', 'Questions updated successfully.');
    }


    public function storeQuestions(Request $request, $section_id, $subject_id, $quizId)
    {
        $quiz = Quiz::findOrFail($quizId);

        $wasPublished = $quiz->status === QuizStatus::PUBLISHED;

        // base validation (structure)
        $validated = $request->validate([
            'description' => 'nullable|string',
            'questions' => 'required|array|min:1',
            'questions.*.order' => 'required|integer|min:1',
            'questions.*.page_hint' => 'nullable|integer|min:1',
            'questions.*.type' => 'required|string|in:multiple_choice,short_answer,true_false,matching,essay,numerical',
            'questions.*.text' => 'required|string',

            // allow these to exist, but we will validate PER TYPE below
            'questions.*.option' => 'nullable|array',
            'questions.*.option.*' => 'nullable|string',

            'questions.*.answer_key' => 'nullable', // could be scalar or array depending on type
            // matching answer_key (new) - keep nested pairs in validated payload
            'questions.*.answer_key.*.left' => 'nullable|string',
            'questions.*.answer_key.*.right' => 'nullable|string',

            'questions.*.pairs' => 'nullable|array',
            'questions.*.pairs.*.left' => 'nullable|string',
            'questions.*.pairs.*.right' => 'nullable|string',

            // matching pool (new)
            'questions.*.match_left' => 'nullable|array',
            'questions.*.match_left.*' => 'nullable|string',
            'questions.*.match_right' => 'nullable|array',
            'questions.*.match_right.*' => 'nullable|string',

            'questions.*.use_pair_points' => 'nullable|boolean',
            'questions.*.pair_points' => 'nullable|array',
            'questions.*.pair_points.*' => 'nullable|integer|min:0',

            'questions.*.required' => 'nullable|boolean',
            'questions.*.points' => 'nullable|integer|min:0',
            'questions.*.tag_ids' => 'nullable|array',
            'questions.*.tag_ids.*' => 'integer|exists:tags,id',
            'questions.*.tag_relevance' => 'nullable|array',
            'questions.*.tag_relevance.*' => ['nullable', 'string', Rule::in($this->allowedTagRelevanceLevels())],
        ], [
            'questions.*.text.required' => 'Please enter a question.',
        ]);

        // ✅ TYPE-AWARE VALIDATION + NORMALIZATION
        // If invalid: return with errors (THIS is the line you asked for)
        // return back()->withErrors([...])->withInput();
        $errors = [];

        // Keep access to the raw submitted payload (nested arrays)
        // because Laravel validation may drop unvalidated nested keys.
        $rawQuestions = $request->input('questions', []);

        // Pagination value is needed to derive a fallback page_hint when not provided by the client.
        $settingRaw = $quiz->setting ?? [];
        if (is_string($settingRaw)) {
            $decoded = json_decode($settingRaw, true);
            $setting = is_array($decoded) ? $decoded : [];
        } elseif (is_array($settingRaw)) {
            $setting = $settingRaw;
        } else {
            $setting = [];
        }
        $pagination = (int)($request->input('pagination', $setting['pagination'] ?? 10));
        if ($pagination < 1) $pagination = 1;
        if ($pagination > 25) $pagination = 25;

        // Persist the pagination value we clamped earlier.
        $setting['pagination'] = $pagination;

        $questionIds = [];
        $totalPoints = 0;

        foreach ($validated['questions'] as $i => $q) {
            $rawQ = (is_array($rawQuestions) && array_key_exists($i, $rawQuestions) && is_array($rawQuestions[$i]))
                ? $rawQuestions[$i]
                : [];
            $normalizedQuestion = $this->questionContentNormalizer->normalize($q, $rawQ, "questions.$i");
            $points = (int) ($normalizedQuestion['points'] ?? 1);
            $errors = [];

            if (true) {
            } elseif ($type === 'true_false') {
                if (!isset($q['answer_key']) || !in_array($q['answer_key'], ['true','false'], true)) {
                    $errors["questions.$i.answer_key"] = "Please choose True or False.";
                }
                $answerKey = isset($q['answer_key'])
                    ? filter_var($q['answer_key'], FILTER_VALIDATE_BOOLEAN)
                    : null;

            } elseif ($type === 'numerical') {
                if (isset($q['answer_key']) && $q['answer_key'] !== '' && !is_numeric($q['answer_key'])) {
                    $errors["questions.$i.answer_key"] = "Numerical answer must be a number.";
                }
                $answerKey = (isset($q['answer_key']) && is_numeric($q['answer_key']))
                    ? (float)$q['answer_key']
                    : null;

            } elseif ($type === 'matching') {
                if (count($matchLeft) < 1) {
                    $errors["questions.$i.match_left"] = "Matching needs at least 1 left item.";
                }
                if (count($matchRight) < 1) {
                    $errors["questions.$i.match_right"] = "Matching needs at least 1 right item.";
                }

                // Left items must be unique within left pool only; same for right pool.
                $leftNorm = array_map(fn($v) => strtolower(trim((string)$v)), $matchLeft);
                if (count($leftNorm) !== count(array_unique($leftNorm))) {
                    $errors["questions.$i.match_left"] = "Matching left items cannot be the same.";
                }

                $rightNorm = array_map(fn($v) => strtolower(trim((string)$v)), $matchRight);
                if (count($rightNorm) !== count(array_unique($rightNorm))) {
                    $errors["questions.$i.match_right"] = "Matching right items cannot be the same.";
                }

                $selectedRaw = $rawQ['answer_key'] ?? ($q['answer_key'] ?? $pairs); // backward compat
                $selectedRaw = is_array($selectedRaw) ? array_values($selectedRaw) : [];

                $completePairs = array_filter($selectedRaw, function ($p) use ($matchLeft, $matchRight) {
                    if (!is_array($p)) return false;
                    if (!isset($p['left'], $p['right'])) return false;
                    $l = trim((string)$p['left']);
                    $r = trim((string)$p['right']);
                    if ($l === '' || $r === '') return false;
                    if (!empty($matchLeft) && !in_array($l, $matchLeft, true)) return false;
                    if (!empty($matchRight) && !in_array($r, $matchRight, true)) return false;
                    return true;
                });

                if (count($completePairs) < 1) {
                    $errors["questions.$i.answer_key"] = "Please select at least 1 match.";
                }

                $pairs = [
                    'left' => array_values($matchLeft),
                    'right' => array_values($matchRight),
                ];
                $answerKey = array_values($completePairs);

            } elseif ($type === 'essay' || $type === 'short_answer') {
                // optional, but should be scalar
                if (isset($q['answer_key']) && is_array($q['answer_key'])) {
                    $errors["questions.$i.answer_key"] = "Answer key must be text.";
                }
                $answerKey = $q['answer_key'] ?? null;

            } else {
                $answerKey = $q['answer_key'] ?? null;
            }

            if (!empty($errors)) {
                // ✅ THIS IS THE EXACT LINE that returns the error message back to Blade
                return back()->withErrors($errors)->withInput();
            }

            $pageHint = isset($q['page_hint']) ? (int) $q['page_hint'] : (int) ceil($q['order'] / max(1, $pagination));
            if ($pageHint < 1) {
                $pageHint = 1;
            }
            $questionTagIds = $this->normalizeQuestionTagIds($rawQ['tag_ids'] ?? ($q['tag_ids'] ?? []));
            $rawTagRelevance = (isset($rawQ['tag_relevance']) && is_array($rawQ['tag_relevance']))
                ? $rawQ['tag_relevance']
                : ((isset($q['tag_relevance']) && is_array($q['tag_relevance'])) ? $q['tag_relevance'] : []);
            $questionTagSyncPayload = [];
            $fallbackCoverageRatio = $this->fallbackCoverageRatio(max(1, count($questionTagIds)));
            foreach ($questionTagIds as $tagId) {
                $relevanceRaw = $rawTagRelevance[$tagId] ?? $rawTagRelevance[(string) $tagId] ?? null;
                $questionTagSyncPayload[$tagId] = [
                    'relevance_level' => $this->normalizeTagRelevanceLevel($relevanceRaw === null ? null : (string) $relevanceRaw),
                    'coverage_ratio' => $fallbackCoverageRatio,
                ];
            }

            $question = $quiz->questions()->create([
                'order' => $q['order'],
                'page_hint' => $pageHint,
                'type' => $normalizedQuestion['type'],
                'text' => $normalizedQuestion['text'],
                'option' => $normalizedQuestion['option'],
                'answer_key' => $normalizedQuestion['answer_key'],
                'required' => $normalizedQuestion['required'],
                'points' => $points,
                'setting' => $normalizedQuestion['setting'],
            ]);
            $question->tags()->sync($questionTagSyncPayload);

            $totalPoints += $points;
            $questionIds[] = $question->id;
        }

        // Saving questions should NOT automatically publish the quiz.
        // Publishing is controlled via the Settings page.
        $quiz->update([
            'questions' => $questionIds,
            'total_score' => $totalPoints,
            'setting' => $setting,
            'description' => $validated['description'] ?? $quiz->description,
        ]);
        $quizFresh = $quiz->fresh();
        $this->syncQuestionTagCoverageFromQuestionTags($quizFresh);
        $this->syncQuizTagCoverageFromQuestionTags($quizFresh->fresh(), true);

        $this->maybeShuffleOnPublishTransition(
            $quizFresh,
            $wasPublished,
            ($quizFresh->status === QuizStatus::PUBLISHED)
        );

        return redirect()
            ->route('teacher.quiz.settings', [$section_id, $subject_id, $quiz->id])
            ->with('success', 'Questions added successfully.');
    }

    private function calculateAutoScore(?QuizResponse $response): int
    {
        if (!$response) {
            return 0;
        }

        $response->loadMissing('answers.question');

        $score = 0;
        foreach ($response->answers as $answer) {
            $question = $answer->question;
            if (!$question) {
                continue;
            }
            if (method_exists($answer, 'scoreValue')) {
                $score += max(0, (int) $answer->scoreValue());
            } elseif (method_exists($answer, 'isCorrect') && $answer->isCorrect()) {
                $score += max(0, (int) ($question->points ?? 1));
            }
        }

        return $score;
    }

    public function viewSubmissions(
    Request $request,
    SectionModel $section,
    SubjectModel $subject,
    Quiz $quiz
)
{
    $search = trim((string) $request->get('q', ''));

    $studentsQuery = app(SectionStudentRoster::class)
        ->studentsQuery($section)
        ->orderBy('last_name')
        ->orderBy('first_name');

    if ($search !== '') {
        $studentsQuery->where(function ($q) use ($search) {
            $q->where('first_name', 'like', '%' . $search . '%')
              ->orWhere('last_name', 'like', '%' . $search . '%');
        });
    }

    $students = $studentsQuery->get();

    $responses = $quiz->responses()
        ->with('user')
        ->orderByDesc('created_at')
        ->get();

    $responsesByUser = $responses
        ->unique('user_id')
        ->keyBy('user_id');

    return view('pages.teacher.pages.quiz.submissions', [
        'title' => 'Quiz Submissions',
        'section' => $section,
        'subject' => $subject,
        'quiz' => $quiz,
        'students' => $students,
        'responsesByUser' => $responsesByUser,
        'search' => $search,
        'preloader' => false,
        'darkmode' => true,
        'scrollup' => true,
        'banner' => true,
        'sidebar' => true,
    ]);
}


    public function viewSubmissionDetail(
    Request $request,
    SectionModel $section,
    SubjectModel $subject,
    Quiz $quiz,
    StudentModel $student
)
{
    $roster = app(SectionStudentRoster::class);

    if (! $roster->containsStudent($section, $student)) {
        abort(404, 'Student does not belong to this section.');
    }

    $students = $roster->studentsQuery($section)
        ->orderBy('last_name')
        ->orderBy('first_name')
        ->get();

    $currentIndex = $students->search(
        fn ($s) => (int) $s->id === (int) $student->id
    );

    $prevStudent = ($currentIndex !== false && $currentIndex > 0)
        ? $students->get($currentIndex - 1)
        : null;

    $nextStudent = ($currentIndex !== false && $currentIndex < $students->count() - 1)
        ? $students->get($currentIndex + 1)
        : null;

    $response = $quiz->responses()
        ->with(['answers.question'])
        ->where('user_id', $student->user_id)
        ->orderByDesc('created_at')
        ->first();

    $autoScore = $this->calculateAutoScore($response);
    $meta = $response ? (is_array($response->meta) ? $response->meta : []) : [];
    $manualAdjustment = $response ? (float) (($response->score ?? 0) - $autoScore) : 0.0;
    $manualNote = $meta['manual_note'] ?? '';
    $accessOverride = $meta['access_override'] ?? null;
    $essayPoints = $meta['essay_points'] ?? [];
    $timeSpentSeconds = (int) ($meta['time_spent_seconds'] ?? 0);

    $quizSetting = is_array($quiz->setting) ? $quiz->setting : [];
    $paginationValue = (int) ($quizSetting['pagination'] ?? 10);

    if ($paginationValue < 1) $paginationValue = 1;
    if ($paginationValue > 25) $paginationValue = 25;

    $submittedAt = $response?->created_at ? Carbon::parse($response->created_at) : null;
    $updatedAt = $response?->updated_at ? Carbon::parse($response->updated_at) : null;
    $dueAt = $quiz->due_date ? Carbon::parse($quiz->due_date) : null;
    $isLate = $submittedAt && $dueAt ? $submittedAt->greaterThan($dueAt) : false;

    return view('pages.teacher.pages.quiz.submission_show', [
        'title' => 'Submission Details',
        'section' => $section,
        'subject' => $subject,
        'quiz' => $quiz,
        'student' => $student,
        'response' => $response,
        'autoScore' => $autoScore,
        'manualAdjustment' => $manualAdjustment,
        'manualNote' => $manualNote,
        'accessOverride' => $accessOverride,
        'essayPoints' => $essayPoints,
        'timeSpentSeconds' => $timeSpentSeconds,
        'paginationValue' => $paginationValue,
        'submittedAt' => $submittedAt,
        'updatedAt' => $updatedAt,
        'dueAt' => $dueAt,
        'isLate' => $isLate,
        'prevStudent' => $prevStudent,
        'nextStudent' => $nextStudent,
        'students' => $students,
        'preloader' => false,
        'darkmode' => true,
        'scrollup' => true,
        'banner' => true,
        'sidebar' => true,
    ]);
}


    public function updateSubmissionDetail(Request $request, $section_id, $subject_id, $quizId, $studentId)
    {
        $section = SectionModel::findOrFail($section_id);
        $subject = SubjectModel::findOrFail($subject_id);
        $quiz = Quiz::findOrFail($quizId);
        $student = StudentModel::findOrFail($studentId);

        if (! app(SectionStudentRoster::class)->containsStudent($section, $student)) {
            abort(404, 'Student does not belong to this section.');
        }

        $response = $quiz->responses()
            ->with(['answers.question'])
            ->where('user_id', $student->user_id)
            ->orderByDesc('created_at')
            ->first();

        if (!$response) {
            return redirect()
                ->route('teacher.quiz.submissions.show', [$section->id, $subject->id, $quiz->id, $student->id])
                ->with('error', 'No submission found for this student.');
        }

        $validated = $request->validate([
            'additional_points' => 'nullable|numeric',
            'deduction_points' => 'nullable|numeric',
            'override_score' => 'nullable|numeric',
            'manual_note' => 'nullable|string|max:500',
            'access_action' => 'nullable|in:open,reopen,close',
            'essay_points' => 'nullable|array',
            'essay_points.*' => 'nullable|integer|min:0',
        ]);

        $autoScore = $this->calculateAutoScore($response);
        $additional = (float) ($validated['additional_points'] ?? 0);
        $deduction = (float) ($validated['deduction_points'] ?? 0);
        $overrideScore = $validated['override_score'] ?? null;

        $essayPointsInput = (array) ($validated['essay_points'] ?? []);

        $questionMaxById = $quiz->questions()
            ->get(['id', 'points'])
            ->mapWithKeys(function ($q) {
                return [(string) $q->id => max(0, (int) ($q->points ?? 0))];
            });

        $clampedEssayPoints = [];
        $essayPointsSum = 0;

        foreach ($essayPointsInput as $questionId => $rawPoints) {
            $qid = (int) $questionId;
            if ($qid <= 0) {
                continue;
            }

            if ($rawPoints === null || (is_string($rawPoints) && trim($rawPoints) === '')) {
                continue;
            }

            $qidKey = (string) $qid;
            if (!$questionMaxById->has($qidKey)) {
                // Ignore injected keys for questions not on this quiz.
                continue;
            }

            $max = (int) $questionMaxById->get($qidKey, 0);
            $n = (int) $rawPoints;

            if ($n < 0) $n = 0;
            if ($n > $max) $n = $max;

            $clampedEssayPoints[$qidKey] = $n;
            $essayPointsSum += $n;
        }

        $finalScore = $overrideScore !== null
            ? (float) $overrideScore
            : $autoScore + $essayPointsSum + $additional - $deduction;

        $finalScore = max(0, $finalScore);

        $meta = is_array($response->meta) ? $response->meta : [];
        $meta['auto_score'] = $autoScore;
        $meta['manual_adjustment'] = $additional - $deduction;
        $meta['manual_note'] = $validated['manual_note'] ?? null;
        $meta['essay_points'] = $clampedEssayPoints;

        if (!empty($validated['access_action'])) {
            $meta['access_override'] = $validated['access_action'];
        }

        $response->meta = $meta;
        $response->score = $finalScore;
        $response->save();

        return redirect()
            ->route('teacher.quiz.submissions.show', [$section->id, $subject->id, $quiz->id, $student->id])
            ->with('success', 'Submission updated.');
    }

    public function destroy($section_id, $subject_id, Quiz $quiz)
    {
        $sectionId = $quiz->section_model_id ?? $section_id;
        $subjectId = $quiz->subject_model_id ?? $subject_id;

        // Remove responses and their answers
        $quiz->responses()->each(function ($response) {
            $response->answers()->delete();
            $response->delete();
        });

        // Remove questions
        $questionIds = $quiz->questions()->pluck('id');
        if ($questionIds->isNotEmpty()) {
            DB::table('taggables')
                ->where('taggable_type', Question::class)
                ->whereIn('taggable_id', $questionIds)
                ->delete();
        }
        $quiz->questions()->delete();

        $quiz->tags()->detach();
        $quiz->delete();

        return redirect()->route('teacher.classwork.view', [$sectionId, $subjectId])
            ->with('success', 'Quiz deleted successfully.');
    }
}
