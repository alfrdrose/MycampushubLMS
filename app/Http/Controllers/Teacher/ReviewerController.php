<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Concerns\HandlesTagMetrics;
use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Evaluation;
use App\Models\H5pReviewer;
use App\Models\Quiz;
use App\Models\Reviewer;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReviewerController extends Controller
{
    use HandlesTagMetrics;

    public function index()
    {
        $teacher = Auth::user()?->teacher;
        if (!$teacher) {
            abort(404);
        }

        $reviewers = Reviewer::with(['tags', 'creator'])
            ->orderBy('created_at', 'desc')
            ->get();

        $reviewerTags = Tag::query()
            ->orderBy('name')
            ->get();

        $h5pReviewers = H5pReviewer::with(['tags', 'creator', 'teacher'])
            ->where('teacher_id', $teacher->id)
            ->latest()
            ->get();

        $createOptions = collect([
            [
                'label' => 'Basic Reviewer',
                'url' => route('teacher.reviewer.create'),
            ],
        ]);

        return view('pages.teacher.pages.reviewer.index', [
            'title' => 'Reviewer Bank',
            'reviewers' => $reviewers,
            'h5pReviewers' => $h5pReviewers,
            'reviewerTags' => $reviewerTags,
            'currentTeacherId' => $teacher->id,
            'createOptions' => $createOptions,
            'preloader' => false,
            'darkmode' => true,
            'scrollup' => true,
            'banner' => true,
            'sidebar' => true,
        ]);
    }

    private function buildReviewerFormData(): array
    {
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

        $quizzes = Quiz::with('tags:id,name,category')
            ->orderBy('created_at', 'desc')
            ->get(['id', 'title', 'grading_period']);

        $activities = Activity::with('tags:id,name,category')
            ->orderBy('created_at', 'desc')
            ->get(['id', 'title', 'grading_period']);

        $evaluations = Evaluation::with('tags:id,name,category')
            ->orderBy('created_at', 'desc')
            ->get(['id', 'title', 'grading_period']);

        $classworkOptions = collect();
        foreach ($quizzes as $quiz) {
            $classworkOptions->push([
                'id' => $quiz->id,
                'title' => $quiz->title,
                'grading_period' => $quiz->grading_period,
                'type' => 'quiz',
                'type_label' => 'Quiz',
                'tags' => $quiz->tags
                    ->map(fn ($tag) => [
                        'id' => $tag->id,
                        'name' => $tag->name,
                        'category' => $tag->category,
                        'relevance' => strtolower((string) (optional($tag->pivot)->relevance_level ?? $this->defaultTagRelevanceLevel())),
                        'coverage' => optional($tag->pivot)->coverage_ratio !== null
                            ? round(((float) optional($tag->pivot)->coverage_ratio) * 100, 2)
                            : null,
                    ])
                    ->values(),
            ]);
        }
        foreach ($activities as $activity) {
            $classworkOptions->push([
                'id' => $activity->id,
                'title' => $activity->title,
                'grading_period' => $activity->grading_period,
                'type' => 'activity',
                'type_label' => 'Assignment',
                'tags' => $activity->tags
                    ->map(fn ($tag) => [
                        'id' => $tag->id,
                        'name' => $tag->name,
                        'category' => $tag->category,
                        'relevance' => strtolower((string) (optional($tag->pivot)->relevance_level ?? $this->defaultTagRelevanceLevel())),
                        'coverage' => optional($tag->pivot)->coverage_ratio !== null
                            ? round(((float) optional($tag->pivot)->coverage_ratio) * 100, 2)
                            : null,
                    ])
                    ->values(),
            ]);
        }
        foreach ($evaluations as $evaluation) {
            $classworkOptions->push([
                'id' => $evaluation->id,
                'title' => $evaluation->title,
                'grading_period' => $evaluation->grading_period,
                'type' => 'evaluation',
                'type_label' => 'Evaluation',
                'tags' => $evaluation->tags
                    ->map(fn ($tag) => [
                        'id' => $tag->id,
                        'name' => $tag->name,
                        'category' => $tag->category,
                        'relevance' => strtolower((string) (optional($tag->pivot)->relevance_level ?? $this->defaultTagRelevanceLevel())),
                        'coverage' => optional($tag->pivot)->coverage_ratio !== null
                            ? round(((float) optional($tag->pivot)->coverage_ratio) * 100, 2)
                            : null,
                    ])
                    ->values(),
            ]);
        }

        return [
            'tags' => $tags,
            'tagCategories' => $tagCategories,
            'classworkOptions' => $classworkOptions,
        ];
    }

    public function create(Request $request)
    {
        $formData = $this->buildReviewerFormData();

        $queryTagIds = $request->input('tag_ids', []);
        if (!is_array($queryTagIds)) {
            $queryTagIds = array_filter(explode(',', (string) $queryTagIds));
        }

        $tagIds = array_values(array_filter($queryTagIds, fn ($value) => $value !== null && $value !== ''));
        if (empty($tagIds)) {
            $tagId = $request->input('tag_id');
            if (!is_null($tagId) && $tagId !== '') {
                $tagIds = [$tagId];
            }
        }

        return view('pages.teacher.pages.reviewer.create', [
            'title' => 'Create Reviewer',
            ...$formData,
            'prefill' => [
                'title' => $request->input('title'),
                'learning_objective' => $request->input('learning_objective'),
                'mini_lesson' => $request->input('mini_lesson'),
                'tag_ids' => $tagIds,
                'tag_relevance' => (array) $request->input('tag_relevance', []),
                'tag_coverage' => (array) $request->input('tag_coverage', []),
                'tag_metrics' => [],
                'source_type' => $request->input('source_type'),
                'source_id' => $request->input('source_id'),
            ],
            'preloader' => false,
            'darkmode' => true,
            'scrollup' => true,
            'banner' => true,
            'sidebar' => true,
        ]);
    }

    public function edit(Request $request, Reviewer $reviewer)
    {
        $teacher = Auth::user()?->teacher;
        if (!$teacher) {
            abort(404);
        }

        if ((int) $reviewer->created_by_teacher_id !== (int) $teacher->id) {
            abort(403);
        }

        $formData = $this->buildReviewerFormData();
        $tagIds = $reviewer->tags->pluck('id')->values()->all();
        $tagMetrics = $reviewer->tags
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

        return view('pages.teacher.pages.reviewer.create', [
            'title' => 'Edit Reviewer',
            'reviewer' => $reviewer,
            ...$formData,
            'prefill' => [
                'title' => $reviewer->title,
                'learning_objective' => $reviewer->learning_objective,
                'mini_lesson' => $reviewer->mini_lesson,
                'tag_ids' => $tagIds,
                'tag_metrics' => $tagMetrics,
                'source_type' => $reviewer->source_classwork_type,
                'source_id' => $reviewer->source_classwork_id,
                'quiz_questions' => $reviewer->mini_quiz ?? [],
            ],
            'preloader' => false,
            'darkmode' => true,
            'scrollup' => true,
            'banner' => true,
            'sidebar' => true,
        ]);
    }

    public function show(Reviewer $reviewer)
    {
        $reviewer->load(['tags', 'creator']);

        return view('pages.teacher.pages.reviewer.show', [
            'title' => 'Reviewer Preview',
            'reviewer' => $reviewer,
            'preloader' => false,
            'darkmode' => true,
            'scrollup' => true,
            'banner' => true,
            'sidebar' => true,
        ]);
    }

    public function store(Request $request)
    {
        $teacher = Auth::user()?->teacher;
        if (!$teacher) {
            abort(403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'learning_objective' => 'required|string|max:1000',
            'mini_lesson' => 'required|string',
            'tag_ids' => 'required|array|min:1',
            'tag_ids.*' => 'exists:tags,id',
            'tag_relevance' => 'nullable|array',
            'tag_relevance.*' => 'nullable|string',
            'tag_coverage' => 'nullable|array',
            'tag_coverage.*' => 'nullable',
            'quiz_questions' => 'required|array',
            'quiz_questions.*.question' => 'nullable|string',
            'quiz_questions.*.answer' => 'nullable|string',
            'source_classwork_type' => 'nullable|string|max:50',
            'source_classwork_id' => 'nullable|integer|min:1',
        ]);

        $tagIds = array_values(array_unique(array_map('intval', $validated['tag_ids'])));
        $tagSyncPayload = $this->buildTagSyncPayload(
            $tagIds,
            (array) $request->input('tag_relevance', []),
            (array) $request->input('tag_coverage', [])
        );

        $quizItems = [];
        foreach ($validated['quiz_questions'] as $item) {
            $question = trim((string) ($item['question'] ?? ''));
            $answer = trim((string) ($item['answer'] ?? ''));
            if ($question === '' && $answer === '') {
                continue;
            }
            if ($question === '' || $answer === '') {
                return back()->withErrors([
                    'quiz_questions' => 'Each quiz item must include both a question and an answer.',
                ])->withInput();
            }
            $quizItems[] = [
                'question' => $question,
                'answer' => $answer,
            ];
        }

        if (count($quizItems) === 0) {
            return back()->withErrors([
                'quiz_questions' => 'Add at least one mini-quiz question.',
            ])->withInput();
        }

        if (count($quizItems) > 5) {
            return back()->withErrors([
                'quiz_questions' => 'Limit the mini-quiz to 5 questions or fewer.',
            ])->withInput();
        }

        $reviewer = Reviewer::create([
            'title' => $validated['title'],
            'learning_objective' => $validated['learning_objective'],
            'mini_lesson' => $validated['mini_lesson'],
            'mini_quiz' => $quizItems,
            'source_classwork_type' => $validated['source_classwork_type'] ?? null,
            'source_classwork_id' => $validated['source_classwork_id'] ?? null,
            'created_by_teacher_id' => $teacher?->id,
        ]);

        $reviewer->tags()->sync($tagSyncPayload);

        return redirect()
            ->route('teacher.reviewer.edit', $reviewer->id)
            ->with('success', 'Reviewer created successfully!');
    }

    public function update(Request $request, Reviewer $reviewer)
    {
        $teacher = Auth::user()?->teacher;
        if (!$teacher) {
            abort(404);
        }

        if ((int) $reviewer->created_by_teacher_id !== (int) $teacher->id) {
            abort(403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'learning_objective' => 'required|string|max:1000',
            'mini_lesson' => 'required|string',
            'tag_ids' => 'required|array|min:1',
            'tag_ids.*' => 'exists:tags,id',
            'tag_relevance' => 'nullable|array',
            'tag_relevance.*' => 'nullable|string',
            'tag_coverage' => 'nullable|array',
            'tag_coverage.*' => 'nullable',
            'quiz_questions' => 'required|array',
            'quiz_questions.*.question' => 'nullable|string',
            'quiz_questions.*.answer' => 'nullable|string',
            'source_classwork_type' => 'nullable|string|max:50',
            'source_classwork_id' => 'nullable|integer|min:1',
        ]);

        $tagIds = array_values(array_unique(array_map('intval', $validated['tag_ids'])));
        $tagSyncPayload = $this->buildTagSyncPayload(
            $tagIds,
            (array) $request->input('tag_relevance', []),
            (array) $request->input('tag_coverage', [])
        );

        $quizItems = [];
        foreach ($validated['quiz_questions'] as $item) {
            $question = trim((string) ($item['question'] ?? ''));
            $answer = trim((string) ($item['answer'] ?? ''));
            if ($question === '' && $answer === '') {
                continue;
            }
            if ($question === '' || $answer === '') {
                return back()->withErrors([
                    'quiz_questions' => 'Each quiz item must include both a question and an answer.',
                ])->withInput();
            }
            $quizItems[] = [
                'question' => $question,
                'answer' => $answer,
            ];
        }

        if (count($quizItems) === 0) {
            return back()->withErrors([
                'quiz_questions' => 'Add at least one mini-quiz question.',
            ])->withInput();
        }

        if (count($quizItems) > 5) {
            return back()->withErrors([
                'quiz_questions' => 'Limit the mini-quiz to 5 questions or fewer.',
            ])->withInput();
        }

        $reviewer->update([
            'title' => $validated['title'],
            'learning_objective' => $validated['learning_objective'],
            'mini_lesson' => $validated['mini_lesson'],
            'mini_quiz' => $quizItems,
            'source_classwork_type' => $validated['source_classwork_type'] ?? null,
            'source_classwork_id' => $validated['source_classwork_id'] ?? null,
        ]);

        $reviewer->tags()->sync($tagSyncPayload);

        return redirect()
            ->route('teacher.reviewer.edit', $reviewer->id)
            ->with('success', 'Reviewer updated successfully!');
    }

    public function destroy(Request $request, Reviewer $reviewer)
    {
        $teacher = Auth::user()?->teacher;
        if (!$teacher) {
            abort(404);
        }

        $canDelete = $reviewer->created_by_teacher_id !== null
            && (int) $reviewer->created_by_teacher_id === (int) $teacher->id;

        if (!$canDelete) {
            abort(403);
        }

        $reviewer->tags()->detach();
        $reviewer->delete();

        // If the request included a tab hint, keep the user on that tab after redirect
        $tab = $request->input('tab');
        if ($tab === 'your-reviewers') {
            return redirect()->route('teacher.reviewer.index', ['tab' => 'your-reviewers'])
                ->with('success', 'Reviewer deleted successfully!');
        }

        return back()->with('success', 'Reviewer deleted successfully!');
    }
    public function h5pIndex()
    {
        $teacher = Auth::user()?->teacher;
        if (!$teacher) {
            abort(404);
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

        return view('pages.teacher.pages.reviewer.createH5P', [
            'title' => 'H5P Integration',
            'tags' => $tags,
            'tagCategories' => $tagCategories,
            'preloader' => false,
            'darkmode' => true,
            'scrollup' => true,
            'banner' => true,
            'sidebar' => true,
        ]);
    }
    public function h5pSave(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'embed_code' => ['required', 'string'],
            'tag_ids' => ['required', 'array', 'min:1'],
            'tag_ids.*' => ['exists:tags,id'],
            'tag_relevance' => ['nullable', 'array'],
            'tag_relevance.*' => ['nullable', 'string'],
            'tag_coverage' => ['nullable', 'array'],
            'tag_coverage.*' => ['nullable'],
        ]);

        $raw = trim($validated['embed_code']);

        // Extract iframe src="..."
        $embedUrl = null;

        if (preg_match('/src="([^"]+)"/i', $raw, $matches)) {
            $embedUrl = $matches[1];
        }

        // If they pasted ONLY a URL, accept it too
        if (!$embedUrl && filter_var($raw, FILTER_VALIDATE_URL)) {
            $embedUrl = $raw;
        }

        if ($embedUrl) {
            $embedUrl = html_entity_decode($embedUrl, ENT_QUOTES);
        }

        if (!$embedUrl) {
            return back()->withErrors([
                'embed_code' => 'Invalid H5P embed code. Please paste the iframe embed code or the direct embed URL.',
            ])->withInput();
        }

        // Optional: only allow h5p_embed links
        if (!str_contains($embedUrl, 'action=h5p_embed')) {
            return back()->withErrors([
                'embed_code' => 'That does not look like a valid H5P embed link.',
            ])->withInput();
        }

        $teacher = Auth::user()?->teacher;

        if (!$teacher) {
            abort(403, 'Only teachers can save H5P reviewers.');
        }

        $teacherId = $teacher->id;

        $reviewer = H5pReviewer::create([
            'teacher_id' => $teacherId,
            'title' => $validated['title'],
            'embed_url' => $embedUrl, // Save only the clean src URL
        ]);

        $tagIds = array_values(array_unique(array_map('intval', $validated['tag_ids'] ?? [])));
        if (!empty($tagIds)) {
            $tagSyncPayload = $this->buildTagSyncPayload(
                $tagIds,
                (array) $request->input('tag_relevance', []),
                (array) $request->input('tag_coverage', [])
            );
            $reviewer->tags()->sync($tagSyncPayload);
        }

        return redirect()
            ->route('teacher.reviewers.h5p.show', $reviewer->id)
            ->with('success', 'H5P Reviewer saved successfully!');
    }

    public function h5pShow(H5pReviewer $reviewer)
    {
        return view('pages.teacher.pages.reviewer.showH5P', [
            'title' => $reviewer->title,
            'reviewer' => $reviewer,
            'preloader' => false,
            'darkmode' => true,
            'scrollup' => true,
            'banner' => true,
            'sidebar' => true,
        ]);
    }

    public function h5pDestroy(Request $request, H5pReviewer $reviewer)
    {
        $teacher = Auth::user()?->teacher;
        if (!$teacher) {
            abort(404);
        }

        // Only allow the owning teacher to delete their H5P reviewers
        if ((int) $reviewer->teacher_id !== (int) $teacher->id) {
            abort(403);
        }

        // detach tags and delete
        if (method_exists($reviewer, 'tags')) {
            try {
                $reviewer->tags()->detach();
            } catch (\Throwable $e) {
                // ignore detach errors
            }
        }

        $reviewer->delete();

        $tab = $request->input('tab');
        if ($tab === 'your-reviewers') {
            return redirect()->route('teacher.reviewer.index', ['tab' => 'your-reviewers'])
                ->with('success', 'H5P reviewer deleted successfully!');
        }

        return back()->with('success', 'H5P reviewer deleted successfully!');
    }

}
