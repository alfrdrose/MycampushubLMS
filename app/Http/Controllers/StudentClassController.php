<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesTagMetrics;
use App\Models\Activity;
use App\Models\ActivitySubmission;
use App\Models\AnnouncementModel;
use App\Models\Evaluation;
use App\Models\EvaluationScore;
use App\Models\GeneratedReviewer;
use App\Models\H5pReviewer;
use App\Models\LessonModel;
use App\Models\Quiz;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Models\Reviewer;
use App\Models\ReviewerInteraction;
use App\Models\Response;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use App\Models\Response as QuizResponse;
use App\Models\Answer as QuizAnswer;
use App\Models\AttendanceScore;
use App\Models\Grade;
use App\Models\GradeSetup;
use Illuminate\Support\Str;
use App\Models\SchoolYear;
use App\Models\SectionModel;
use App\Models\SectionSubject;
use App\Models\StudentModel;
use App\Models\SubjectModel;
use App\Models\SubjectTeacher;
use App\Models\TeacherModel;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\AdminAnnouncement;
use App\Models\Attendance;
use App\Models\AttendanceType;
use App\Models\SubjectGrading;

class StudentClassController extends Controller
{
    use HandlesTagMetrics;

    private function resolveVisibleSection(StudentModel $student): ?SectionModel
    {
        if (! $student->section_id) {
            return null;
        }

        $student->loadMissing('section.schoolYear');
        $section = $student->section;
        $activeSchoolYearId = SchoolYear::activeId();

        if (! $section || ! $activeSchoolYearId) {
            return null;
        }

        if ((int) $section->school_year_id !== $activeSchoolYearId) {
            return null;
        }

        return $section;
    }

    // Student dashboard
    // Student dashboard
public function dashboard()
{
    $student = StudentModel::where('user_id', Auth::id())->firstOrFail();

    $todoItems = $this->buildTodoItems($student);
    $calendarEvents = $this->buildCalendarEvents($student);

    // ✅ Load announcements (paginate)
    $announcements = AdminAnnouncement::query()
        ->forActiveSchoolYear()
        ->orderBy('date_posted', 'desc')
        ->paginate(5, ['*'], 'announcements_page');


    return view('pages.student.pages.dashboard', [
        'title' => 'Student Dashboard',
        'preloader' => false,
        'darkmode' => true,
        'scrollup' => true,
        'banner' => true,
        'sidebar' => true,

        // ✅ pass todo list
        'todoItems' => $todoItems,
        'calendarEvents' => $calendarEvents,

        // ✅ pass announcements
        'announcements' => $announcements,
    ]);
}


    private function buildTodoItems(StudentModel $student, int $perPage = 2): LengthAwarePaginator
{
    $sectionId = (int) optional($this->resolveVisibleSection($student))->id;
    if (!$sectionId) {
        return $this->emptyTodoPaginator($perPage);
    }

    $subjectIds = SectionSubject::where('section_model_id', $sectionId)
        ->pluck('subject_model_id');

    /* =======================
     * Activities
     * ======================= */
    $todoActivities = Activity::with('sectionSubject.subject')
        ->where('is_open', 1)
        ->where('visible', 1)
        ->whereHas('sectionSubject', function ($query) use ($sectionId, $subjectIds) {
            $query->where('section_model_id', $sectionId);

            if ($subjectIds->isNotEmpty()) {
                $query->whereIn('subject_model_id', $subjectIds);
            }
        })
        ->whereDoesntHave('submissions', function ($q) use ($student) {
            $q->where('student_id', $student->id);
        })
        ->orderBy('due_date', 'asc')
        ->get();

    $activityItems = $todoActivities->map(function ($activity) {
        if (!$activity->due_date) {
            return null;
        }

        $dueAt = Carbon::parse($activity->due_date);

        return [
            'type' => 'activity',
            'type_label' => 'Activity',
            'title' => $activity->title,
            'subject' => optional(optional($activity->sectionSubject)->subject)->subject_name ?? 'No Subject',
            'due_at' => $dueAt,
            'due_date' => $dueAt->format('M d, Y'),
            'due_time' => $dueAt->format('h:i A'),
            'url' => route('student.classwork.view.activity', [
                'section_id' => $activity->sectionSubject->section_model_id ?? null,
                'subject_id' => $activity->sectionSubject->subject_model_id ?? null,
                'activity_id' => $activity->id,
            ]),
            'icon' => 'icofont-notepad',
            'icon_color' => 'text-primaryColor',
        ];
    })->filter()->values()->toBase();

    /* =======================
     * Quizzes
     * ======================= */
    $todoQuizzesQuery = Quiz::with('subject')
        ->where('visible', 1)
        ->where('is_open', 1)
        ->whereDoesntHave('responses', function ($q) use ($student) {
            $q->where('user_id', $student->user_id);
        })
        ->orderBy('due_date', 'asc');

    if ($sectionId) {
        $todoQuizzesQuery->where('section_model_id', $sectionId);
    }

    if ($subjectIds->isNotEmpty()) {
        $todoQuizzesQuery->whereIn('subject_model_id', $subjectIds);
    }

    $todoQuizzes = $todoQuizzesQuery->get();

    $now = now();
    $quizItems = $todoQuizzes->map(function ($quiz) use ($now) {
        $dueAt = $quiz->due_date ? Carbon::parse($quiz->due_date) : null;
        $openAt = $this->getQuizOpenAt($quiz);
        $isPastDue = $dueAt && $now->greaterThan($dueAt) && !$quiz->accepting;
        $sortAt = $dueAt ?? $openAt;

        if ($isPastDue || !$sortAt) {
            return null;
        }

        return [
            'type' => 'quiz',
            'type_label' => 'Quiz',
            'title' => $quiz->title,
            'subject' => optional($quiz->subject)->subject_name ?? 'No Subject',
            'due_at' => $sortAt,
            'due_date' => $dueAt ? $dueAt->format('M d, Y') : 'Not assigned yet',
            'due_time' => $dueAt ? $dueAt->format('h:i A') : $openAt?->format('h:i A'),
            'url' => route('student.quiz.preview', [
                'section_id' => $quiz->section_model_id,
                'subject_id' => $quiz->subject_model_id,
                'quiz_id' => $quiz->id,
            ]),
            'icon' => 'icofont-light-bulb',
            'icon_color' => 'text-[#5F2DED]',
        ];
    })->filter()->values()->toBase();

    /* =======================
     * Merge + paginate
     * ======================= */
    $items = collect($activityItems)
        ->merge($quizItems)
        ->sortBy('due_at')
        ->values();

    // 🔑 CUSTOM page name
    $page = LengthAwarePaginator::resolveCurrentPage('todo_page');

    $pageItems = $items
        ->forPage($page, $perPage)
        ->values();

    return new LengthAwarePaginator(
        $pageItems,
        $items->count(),
        $perPage,
        $page,
        [
            'path'     => LengthAwarePaginator::resolveCurrentPath(),
            'pageName' => 'todo_page',
        ]
    );
}

    private function emptyTodoPaginator(int $perPage): LengthAwarePaginator
    {
        $page = LengthAwarePaginator::resolveCurrentPage('todo_page');

        return new LengthAwarePaginator(
            collect(),
            0,
            $perPage,
            $page,
            [
                'path' => LengthAwarePaginator::resolveCurrentPath(),
                'pageName' => 'todo_page',
            ]
        );
    }



    private function buildCalendarEvents(StudentModel $student)
{
    $sectionId = (int) optional($this->resolveVisibleSection($student))->id;
    if (!$sectionId) {
        return collect();
    }

    $subjectIds = SectionSubject::where('section_model_id', $sectionId)->pluck('subject_model_id');

    $activities = Activity::with(['sectionSubject.subject', 'submissions' => function ($q) use ($student) {
            $q->where('student_id', $student->id);
        }])
        ->where('visible', 1)
        ->whereHas('sectionSubject', function ($query) use ($sectionId, $subjectIds) {
            $query->where('section_model_id', $sectionId);

            if ($subjectIds->isNotEmpty()) {
                $query->whereIn('subject_model_id', $subjectIds);
            }
        })
        ->orderBy('due_date', 'asc')
        ->get();

    $activityEvents = $activities->map(function ($activity) {
        if (!$activity->due_date) {
            return null;
        }

        $due = Carbon::parse($activity->due_date);
        $hasSubmission = $activity->submissions->isNotEmpty();

        $status = $hasSubmission
            ? 'submitted'
            : ($due->isPast() ? 'missed' : 'upcoming');

        return [
            'title' => $activity->title,
            'start' => $due->toDateString(),
            'subject' => optional(optional($activity->sectionSubject)->subject)->subject_name ?? 'No Subject',
            'due_time' => $due->format('h:i A'),
            'status' => $status,
            'type' => 'activity',
            'url' => $activity->sectionSubject
                ? route('student.classwork.view.activity', [
                    'section_id' => $activity->sectionSubject->section_model_id,
                    'subject_id' => $activity->sectionSubject->subject_model_id,
                    'activity_id' => $activity->id,
                ])
                : null,
        ];
    })->filter()->values()->toBase();

    $quizQuery = Quiz::with(['subject', 'responses' => function ($q) use ($student) {
            $q->where('user_id', $student->user_id);
        }])
        ->where('visible', 1)
        ->where('section_model_id', $sectionId)
        ->orderBy('due_date', 'asc');

    if ($subjectIds->isNotEmpty()) {
        $quizQuery->whereIn('subject_model_id', $subjectIds);
    }

    $quizEvents = $quizQuery->get()->map(function ($quiz) {
        $due = $quiz->due_date ? Carbon::parse($quiz->due_date) : null;
        $openAt = $this->getQuizOpenAt($quiz);
        $startAt = $due ?? $openAt;

        if (!$startAt) {
            return null;
        }

        $hasResponse = $quiz->responses->isNotEmpty();

        $status = $hasResponse
            ? 'submitted'
            : (($due && $due->isPast()) ? 'missed' : 'upcoming');

        return [
            'title' => $quiz->title,
            'start' => $startAt->toDateString(),
            'subject' => optional($quiz->subject)->subject_name ?? 'No Subject',
            'due_time' => $due ? $due->format('h:i A') : $startAt->format('h:i A'),
            'status' => $status,
            'type' => 'quiz',
            'url' => $hasResponse
                ? route('student.classwork.quiz.response', [
                    'section_id' => $quiz->section_model_id,
                    'subject_id' => $quiz->subject_model_id,
                    'quiz_id' => $quiz->id,
                ])
                : route('student.quiz.preview', [
                    'section_id' => $quiz->section_model_id,
                    'subject_id' => $quiz->subject_model_id,
                    'quiz_id' => $quiz->id,
                ]),
        ];
    })->filter()->values()->toBase();

        return collect($activityEvents)
        ->merge($quizEvents)
        ->sortBy('start')
        ->values();
}

    private function getQuizOpenAt(Quiz $quiz): ?Carbon
    {
        if (!$quiz->open_date) {
            return null;
        }

        $datePart = Carbon::parse($quiz->open_date)->format('Y-m-d');
        $timePart = $quiz->open_time
            ? Carbon::parse($quiz->open_time)->format('H:i:s')
            : '00:00:00';

        return Carbon::parse($datePart . ' ' . $timePart);
    }

    private function getActivityOpenAt(Activity $activity): ?Carbon
    {
        if (!$activity->open_date) {
            return null;
        }

        $datePart = Carbon::parse($activity->open_date)->format('Y-m-d');
        $timePart = $activity->open_time
            ? Carbon::parse($activity->open_time)->format('H:i:s')
            : '00:00:00';

        return Carbon::parse($datePart . ' ' . $timePart);
    }

    private function ensureActivityAccessibleForStudent(Activity $activity): void
    {
        $now = now();
        $isVisible = ((int) ($activity->visible ?? 0) === 1);
        $isOpen = ((int) ($activity->is_open ?? 0) === 1);
        $isAccepting = ((int) ($activity->accepting ?? 1) === 1);
        $openAt = $this->getActivityOpenAt($activity);
        $dueAt = $activity->due_date ? Carbon::parse($activity->due_date) : null;
        $isPastDue = $dueAt && $now->greaterThan($dueAt);

        if (!$isVisible) {
            abort(403, 'This activity is not published yet.');
        }

        if (!$isOpen) {
            if ($openAt && $now->lessThan($openAt)) {
                abort(403, 'This activity opens on ' . $openAt->format('F j, Y \\a\\t h:i A') . '.');
            }

            if ($isPastDue && !(bool) ($activity->accepting ?? false)) {
                abort(403, 'The deadline for this activity has passed.');
            }

            abort(403, 'This activity is currently closed.');
        }

        if (!$isAccepting) {
            if ($isPastDue) {
                abort(403, 'The deadline for this activity has passed.');
            }

            abort(403, 'This activity is currently closed.');
        }

    }

    private function ensureStudentInSection(StudentModel $student, $sectionId): void
    {
        if (!$student || (int) $student->section_id !== (int) $sectionId) {
            abort(403, 'You are not enrolled in this section.');
        }
    }

    private function ensureStudentInSectionSubject(StudentModel $student, $sectionId, $subjectId): SectionSubject
    {
        $this->ensureStudentInSection($student, $sectionId);

        $sectionSubject = SectionSubject::where('section_model_id', $sectionId)
            ->where('subject_model_id', $subjectId)
            ->first();

        if (!$sectionSubject) {
            abort(403, 'You are not enrolled in this subject.');
        }

        return $sectionSubject;
    }


    /* =========================================================
     |  MY CLASSES
     ========================================================= */
    public function index()
    {
        $student = Auth::user()->student;
        if (!$student) {
            abort(404, 'Student not found.');
        }

        $section = $this->resolveVisibleSection($student);
        $subjects = collect();

        if ($section) {
            $subjects = DB::table('section_subject')
                ->where('section_subject.section_model_id', $section->id)
                ->join('subject_models', 'section_subject.subject_model_id', '=', 'subject_models.id')
                ->leftJoin('subject_teacher', function ($join) use ($section) {
                    $join->on('subject_teacher.subject_model_id', '=', 'section_subject.subject_model_id')
                        ->where('subject_teacher.section_model_id', '=', $section->id);
                })
                ->leftJoin('teacher_models', 'subject_teacher.teacher_model_id', '=', 'teacher_models.id')
                ->select(
                    'subject_models.id as subject_id',
                    'subject_models.subject_name',
                    'section_subject.coverImage',
                    'section_subject.start_time',
                    'section_subject.end_time',
                    'section_subject.days',
                    'teacher_models.first_name',
                    'teacher_models.last_name'
                )
                ->get();
        }

        foreach ($subjects as $subject) {
            $subject->teacher_name = ($subject->first_name && $subject->last_name)
                ? $subject->first_name . ' ' . $subject->last_name
                : 'N/A';
        }

        return view('pages.student.pages.class', [
            'title' => 'My Classes',
            'student' => $student,
            'section' => $section,
            'subjects' => $subjects,
            'preloader' => false,
            'darkmode' => true,
            'scrollup' => true,
            'banner' => true,
            'sidebar' => true,
        ]);
    }
    public function calendar()
    {
        $student = StudentModel::where('user_id', Auth::id())->firstOrFail();
        $calendarEvents = $this->buildCalendarEvents($student);

        return view('pages.student.pages.calendar', [
            'title' => 'Student Calendar',
            'preloader' => false,
            'darkmode' => true,
            'scrollup' => true,
            'banner' => true,
            'sidebar' => true,

            'calendarEvents' => $calendarEvents,
        ]);
    }

    public function reviewerRecommendation(Request $request)
    {
        $student = StudentModel::where('user_id', Auth::id())->firstOrFail();
        $section = SectionModel::findOrFail($student->section_id);

        $tabOptions = ['analytics', 'reviewer-bank', 'recommended'];
        $activeTab = (string) $request->query('tab', 'analytics');
        if (!in_array($activeTab, $tabOptions, true)) {
            $activeTab = 'analytics';
        }

        $basicReviewers = collect();
        $h5pReviewers = collect();
        $reviewers = collect();
        $reviewerTags = collect();
        $recommendedReviewers = collect();
        $recommendedLessonCollections = collect();
        $lowPerformanceTags = collect();
        $lowPerformanceTagInsights = [];
        $shouldRecommend = $request->boolean('recommend');
        $completedReviewerIds = ReviewerInteraction::where('student_id', $student->id)
            ->whereNotNull('completed_at')
            ->pluck('reviewer_id')
            ->all();

        $subjectTeachers = SubjectTeacher::query()
            ->where('section_model_id', $section->id)
            ->get(['subject_model_id', 'teacher_model_id']);

        $teacherIds = $subjectTeachers->pluck('teacher_model_id')
            ->filter()
            ->unique()
            ->values();

        $teacherUserMap = collect();
        if ($teacherIds->isNotEmpty()) {
            $teacherUserMap = TeacherModel::whereIn('id', $teacherIds)
                ->pluck('user_id', 'id');
        }

        $teacherUserIds = $teacherUserMap
            ->values()
            ->filter()
            ->unique()
            ->values();

        $allowedTeacherIds = $teacherIds
            ->merge($teacherUserIds)
            ->unique()
            ->values();

        if ($allowedTeacherIds->isNotEmpty()) {
            $h5pReviewers = H5pReviewer::with('tags')
                ->whereIn('teacher_id', $allowedTeacherIds)
                ->orderBy('created_at', 'desc')
                ->get();
        }

        $reviewerQuery = Reviewer::with('tags');

        $basicReviewers = $reviewerQuery
            ->orderBy('created_at', 'desc')
            ->get();

        $reviewers = $basicReviewers
            ->concat($h5pReviewers)
            ->sortByDesc('created_at')
            ->values();

        $reviewerTags = Tag::query()
            ->orderBy('name')
            ->get();

        if ($activeTab === 'recommended') {
            $sectionSubjects = SectionSubject::with('subject')
                ->where('section_model_id', $section->id)
                ->get();
            $subjectNeeds = $sectionSubjects->map(function ($sectionSubject) use ($student) {
                [$tagNeed, $attemptedTagIds, $tagCoverage, $tagSimple] = $this->computeReviewerNeeds($student, $sectionSubject);

                return [
                    'section_subject' => $sectionSubject,
                    'tag_need' => $tagNeed,
                    'attempted_tag_ids' => $attemptedTagIds,
                    'tag_coverage' => $tagCoverage,
                    'tag_simple' => $tagSimple,
                ];
            });

            $tagNeedTotals = [];
            $tagCoverageTotals = [];
            $tagCoverageCounts = [];
            $tagSimpleMissedTotals = [];
            $tagSimpleExposureTotals = [];
            foreach ($subjectNeeds as $subjectNeed) {
                $tagNeed = $subjectNeed['tag_need'];
                foreach ($tagNeed as $tagId => $weight) {
                    $tagNeedTotals[$tagId] = ($tagNeedTotals[$tagId] ?? 0) + $weight;
                }
                $tagCoverage = $subjectNeed['tag_coverage'] ?? [];
                foreach ($tagCoverage as $tagId => $coverageRatio) {
                    $tagCoverageTotals[$tagId] = ($tagCoverageTotals[$tagId] ?? 0.0) + (float) $coverageRatio;
                    $tagCoverageCounts[$tagId] = ($tagCoverageCounts[$tagId] ?? 0) + 1;
                }
                $tagSimple = $subjectNeed['tag_simple'] ?? [];
                foreach ($tagSimple as $tagId => $simple) {
                    $tagSimpleMissedTotals[$tagId] = ($tagSimpleMissedTotals[$tagId] ?? 0.0) + (float) ($simple['missed'] ?? 0.0);
                    $tagSimpleExposureTotals[$tagId] = ($tagSimpleExposureTotals[$tagId] ?? 0.0) + (float) ($simple['exposure'] ?? 0.0);
                }
            }

            if (!empty($tagNeedTotals)) {
                $lowPerformanceTags = Tag::whereIn('id', array_keys($tagNeedTotals))
                    ->get()
                    ->sort(function ($a, $b) use ($tagNeedTotals, $tagCoverageTotals, $tagCoverageCounts, $tagSimpleMissedTotals, $tagSimpleExposureTotals) {
                        $needA = (float) ($tagNeedTotals[$a->id] ?? 0.0);
                        $needB = (float) ($tagNeedTotals[$b->id] ?? 0.0);
                        if ($needA !== $needB) {
                            return $needB <=> $needA;
                        }

                        $exposureA = (float) ($tagSimpleExposureTotals[$a->id] ?? 0.0);
                        $exposureB = (float) ($tagSimpleExposureTotals[$b->id] ?? 0.0);
                        $missedA = (float) ($tagSimpleMissedTotals[$a->id] ?? 0.0);
                        $missedB = (float) ($tagSimpleMissedTotals[$b->id] ?? 0.0);
                        $weaknessA = $exposureA > 0 ? ($missedA / $exposureA) : 0.0;
                        $weaknessB = $exposureB > 0 ? ($missedB / $exposureB) : 0.0;
                        if ($weaknessA !== $weaknessB) {
                            return $weaknessB <=> $weaknessA;
                        }
                        if ($missedA !== $missedB) {
                            return $missedB <=> $missedA;
                        }

                        $coverageA = ($tagCoverageCounts[$a->id] ?? 0) > 0
                            ? ((float) ($tagCoverageTotals[$a->id] ?? 0.0) / (float) $tagCoverageCounts[$a->id])
                            : 0.0;
                        $coverageB = ($tagCoverageCounts[$b->id] ?? 0) > 0
                            ? ((float) ($tagCoverageTotals[$b->id] ?? 0.0) / (float) $tagCoverageCounts[$b->id])
                            : 0.0;
                        if ($coverageA !== $coverageB) {
                            return $coverageB <=> $coverageA;
                        }

                        return strcasecmp((string) ($a->name ?? ''), (string) ($b->name ?? ''));
                    })
                    ->values()
                    ->map(function ($tag) use ($tagSimpleMissedTotals, $tagSimpleExposureTotals, $tagNeedTotals, $tagCoverageTotals, $tagCoverageCounts) {
                        $tagId = (int) $tag->id;
                        $exposure = (float) ($tagSimpleExposureTotals[$tagId] ?? 0.0);
                        $missed = (float) ($tagSimpleMissedTotals[$tagId] ?? 0.0);
                        $weakness = $exposure > 0 ? ($missed / $exposure) : 0.0;
                        $avgCoverage = ($tagCoverageCounts[$tagId] ?? 0) > 0
                            ? ((float) ($tagCoverageTotals[$tagId] ?? 0.0) / (float) $tagCoverageCounts[$tagId])
                            : 0.0;

                        $tag->need_score = (float) ($tagNeedTotals[$tagId] ?? 0.0);
                        $tag->weakness_ratio = max(0.0, min(1.0, $weakness));
                        $tag->weakness_percent = round($tag->weakness_ratio * 100, 2);
                        $tag->missed_points = round($missed, 2);
                        $tag->exposure_points = round($exposure, 2);
                        $tag->coverage_ratio = max(0.0, min(1.0, $avgCoverage));
                        $tag->coverage_percent = round($tag->coverage_ratio * 100, 2);
                        return $tag;
                    })
                    ->values();
            }

            if ($lowPerformanceTags->isNotEmpty()) {
                $lowPerformanceTagInsights = $this->buildQuizTagEvidenceForSectionSubjects(
                    $student,
                    $sectionSubjects,
                    $lowPerformanceTags->pluck('id')->all()
                );
            }

            if ($shouldRecommend) {
                $maxRecommendations = (int) config('reviewers.max_recommendations', 5);
                $recommendationPool = $basicReviewers->concat($h5pReviewers);

                if ($recommendationPool->isNotEmpty()) {
                    $recommendedReviewers = $subjectNeeds
                        ->flatMap(function ($subjectNeed) use ($recommendationPool, $completedReviewerIds, $maxRecommendations) {
                            return $this->buildReviewerRecommendationsFromNeeds(
                                $subjectNeed['tag_need'],
                                $subjectNeed['attempted_tag_ids'],
                                $recommendationPool,
                                $completedReviewerIds,
                                $maxRecommendations
                            );
                        })
                        ->unique(function ($reviewer) {
                            return $reviewer instanceof H5pReviewer
                                ? 'h5p-' . $reviewer->id
                                : 'basic-' . $reviewer->id;
                        })
                        ->sortByDesc('match_score')
                        ->values();
                }

                $recommendedLessonCollections = $subjectNeeds
                    ->map(function ($subjectNeed) use ($section, $maxRecommendations) {
                        $sectionSubject = $subjectNeed['section_subject'];
                        $tagNeed = $subjectNeed['tag_need'];
                        $attemptedTagIds = $subjectNeed['attempted_tag_ids'];

                        if (empty($tagNeed)) {
                            return null;
                        }

                        $lessonPool = LessonModel::with('tags')
                            ->where('section_model_id', $section->id)
                            ->where('subject_model_id', $sectionSubject->subject_model_id)
                            ->orderByRaw("
                                CASE grading_period
                                    WHEN '1st' THEN 1
                                    WHEN '2nd' THEN 2
                                    WHEN '3rd' THEN 3
                                    WHEN '4th' THEN 4
                                END
                            ")
                            ->orderBy('created_at', 'asc')
                            ->get()
                            ->filter(function ($lesson) {
                                return $this->isLessonAvailableForStudent($lesson);
                            })
                            ->values();

                        if ($lessonPool->isEmpty()) {
                            return null;
                        }

                        $recommendedLessons = $this->buildLessonRecommendationsFromNeeds(
                            $tagNeed,
                            $attemptedTagIds,
                            $lessonPool,
                            $maxRecommendations
                        );

                        if ($recommendedLessons->isEmpty()) {
                            return null;
                        }

                        $tagPool = $recommendedLessons
                            ->flatMap(function ($lesson) {
                                return $lesson->tags ?? collect();
                            })
                            ->unique('id')
                            ->sortBy('name')
                            ->values();

                        $topLessonMatchScore = (float) ($recommendedLessons->max('match_score') ?? 0);

                        return [
                            'section_id' => (int) $section->id,
                            'subject_id' => (int) $sectionSubject->subject_model_id,
                            'subject_name' => trim((string) optional($sectionSubject->subject)->subject_name) ?: 'Unknown Subject',
                            'lesson_count' => $recommendedLessons->count(),
                            'tags' => $tagPool,
                            'score' => $topLessonMatchScore,
                            'compatibility_percent' => round(
                                $topLessonMatchScore * 100,
                                (int) config('reviewers.compatibility_percent_decimals', 1)
                            ),
                        ];
                    })
                    ->filter()
                    ->sortByDesc('score')
                    ->values();
            }
        }

        $chartData = [
            'labels' => [],
            'values' => [],
        ];

        if ($activeTab === 'analytics') {
            $sectionSubjects = SectionSubject::where('section_model_id', $section->id)
                ->get(['id', 'subject_model_id']);
            $sectionSubjectIds = $sectionSubjects->pluck('id')->filter()->values();
            $subjectIds = $sectionSubjects->pluck('subject_model_id')->filter()->values();

            $studentId = $student->id;
            $userId = $student->user_id;

            $latestQuizResponses = DB::table('responses')
                ->select('quiz_id', 'user_id', DB::raw('MAX(id) as response_id'))
                ->where('user_id', $userId)
                ->groupBy('quiz_id', 'user_id');

            $activityPoints = DB::table('taggables as tg')
                ->join('activities as a', 'a.id', '=', 'tg.taggable_id')
                ->join('section_subject as ss', 'ss.id', '=', 'a.section_subject_id')
                ->join('subject_models as sm', 'sm.id', '=', 'ss.subject_model_id')
                ->join('activity_submissions as s', function ($join) use ($studentId) {
                    $join->on('s.activity_id', '=', 'a.id')
                        ->where('s.is_latest', 1)
                        ->where('s.status', 'graded')
                        ->where('s.student_id', $studentId)
                        ->whereNotNull('s.score');
                })
                ->where('tg.taggable_type', Activity::class)
                ->whereNotNull('a.total_score')
                ->where('a.total_score', '>', 0)
                ->select('tg.tag_id', 'sm.subject_name', DB::raw('(s.score / NULLIF(a.total_score, 0)) * 100 as points'));

            if ($sectionSubjectIds->isNotEmpty()) {
                $activityPoints->whereIn('a.section_subject_id', $sectionSubjectIds);
            } else {
                $activityPoints->whereRaw('1=0');
            }

            $quizPoints = DB::table('taggables as tg')
                ->join('quizzes as q', 'q.id', '=', 'tg.taggable_id')
                ->join('subject_models as sm', 'sm.id', '=', 'q.subject_model_id')
                ->joinSub($latestQuizResponses, 'latest_responses', function ($join) {
                    $join->on('latest_responses.quiz_id', '=', 'q.id');
                })
                ->join('responses as r', 'r.id', '=', 'latest_responses.response_id')
                ->where('tg.taggable_type', Quiz::class)
                ->where('q.section_model_id', $section->id)
                ->whereNotNull('r.score')
                ->whereNotNull('q.total_score')
                ->where('q.total_score', '>', 0)
                ->select('tg.tag_id', 'sm.subject_name', DB::raw('(r.score / NULLIF(q.total_score, 0)) * 100 as points'));

            if ($subjectIds->isNotEmpty()) {
                $quizPoints->whereIn('q.subject_model_id', $subjectIds);
            } else {
                $quizPoints->whereRaw('1=0');
            }

            $pointsUnion = $activityPoints->unionAll($quizPoints);

            $evaluationConfig = null;
            $schema = DB::getSchemaBuilder();

            $evaluationTable = (new Evaluation())->getTable();
            $evaluationScoreTable = (new EvaluationScore())->getTable();

            if (
                $schema->hasTable($evaluationTable)
                && $schema->hasTable($evaluationScoreTable)
                && $schema->hasColumn($evaluationScoreTable, 'evaluation_id')
                && $schema->hasColumn($evaluationScoreTable, 'student_id')
                && $schema->hasColumn($evaluationScoreTable, 'score')
            ) {
                $scope = null;
                if ($schema->hasColumn($evaluationTable, 'section_subject_id')) {
                    $scope = 'section_subject';
                } elseif (
                    $schema->hasColumn($evaluationTable, 'section_model_id')
                    && $schema->hasColumn($evaluationTable, 'subject_model_id')
                ) {
                    $scope = 'section_subject_model';
                }

                if ($scope) {
                    $evaluationConfig = [
                        'class' => Evaluation::class,
                        'table' => $evaluationTable,
                        'score_table' => $evaluationScoreTable,
                        'scope' => $scope,
                    ];
                }
            }

            if ($evaluationConfig) {
                $evaluationPoints = DB::table('taggables as tg')
                    ->join($evaluationConfig['table'] . ' as e', 'e.id', '=', 'tg.taggable_id')
                    ->join($evaluationConfig['score_table'] . ' as es', 'es.evaluation_id', '=', 'e.id')
                    ->where('tg.taggable_type', $evaluationConfig['class'])
                    ->where('es.student_id', $studentId)
                    ->whereNotNull('es.score')
                    ->whereNotNull('e.total_score')
                    ->where('e.total_score', '>', 0);

                if ($evaluationConfig['scope'] === 'section_subject') {
                    $evaluationPoints->join('section_subject as ss', 'ss.id', '=', 'e.section_subject_id')
                        ->join('subject_models as sm', 'sm.id', '=', 'ss.subject_model_id');
                    if ($sectionSubjectIds->isNotEmpty()) {
                        $evaluationPoints->whereIn('e.section_subject_id', $sectionSubjectIds);
                    } else {
                        $evaluationPoints->whereRaw('1=0');
                    }
                } else {
                    $evaluationPoints->join('subject_models as sm', 'sm.id', '=', 'e.subject_model_id')
                        ->where('e.section_model_id', $section->id);
                    if ($subjectIds->isNotEmpty()) {
                        $evaluationPoints->whereIn('e.subject_model_id', $subjectIds);
                    } else {
                        $evaluationPoints->whereRaw('1=0');
                    }
                }

                $evaluationPoints->select('tg.tag_id', 'sm.subject_name', DB::raw('(es.score / NULLIF(e.total_score, 0)) * 100 as points'));
                $pointsUnion->unionAll($evaluationPoints);
            }

            $tagTotals = DB::query()
                ->fromSub($pointsUnion, 'u')
                ->join('tags as t', 't.id', '=', 'u.tag_id')
                ->select('t.name', 'u.subject_name', DB::raw('AVG(u.points) as average_points'))
                ->groupBy('t.name', 'u.subject_name')
                ->orderByDesc('average_points')
                ->get();

            $chartData = [
                'labels' => $tagTotals->map(function ($row) {
                    $subjectName = trim((string) ($row->subject_name ?? '')) ?: 'Unknown Subject';
                    return $row->name . ' (' . $subjectName . ')';
                })->values(),
                'values' => $tagTotals->map(function ($row) {
                    return round((float) $row->average_points, 2);
                })->values(),
            ];
        }

        return view('pages.student.pages.reviewer-recommendation', [
            'title' => 'Reviewer Recommendation',
            'section' => $section,
            'activeTab' => $activeTab,
            'chartData' => $chartData,
            'reviewers' => $reviewers,
            'reviewerTags' => $reviewerTags,
            'recommendedReviewers' => $recommendedReviewers,
            'recommendedLessonCollections' => $recommendedLessonCollections,
            'completedReviewerIds' => $completedReviewerIds,
            'lowPerformanceTags' => $lowPerformanceTags,
            'lowPerformanceTagInsights' => $lowPerformanceTagInsights,
            'shouldRecommend' => $shouldRecommend,
            'preloader' => false,
            'darkmode' => true,
            'scrollup' => true,
            'banner' => true,
            'sidebar' => true,
        ]);
    }

     public function reviewerGenerate()
{
    $user = Auth::user();

    if (!$user) {
        return response()->json([
            'success' => false,
            'message' => 'Unauthorized'
        ], 401);
    }

    // Load latest responses per quiz
    $responses = Response::with([
        'quiz',
        'quiz.subject',
        'answers.question',
        'answers.question.tags'
    ])
    ->where('user_id', $user->id)
    ->orderByDesc('created_at')
    ->get()
    ->unique('quiz_id');

    $grouped = [];
    $seenQuestions = [];

    foreach ($responses as $response) {

        $quiz = $response->quiz;
        if (!$quiz) continue;

        $subjectName = optional($quiz->subject)->subject_name ?? 'Unknown Subject';

        $uniqueAnswers = $response->answers->unique('question_id');

        foreach ($uniqueAnswers as $answer) {

            if ($answer->isCorrect()) continue;

            $question = $answer->question;
            if (!$question) continue;

            // Content-based deduplication
            $signature = md5(
                trim($question->text) . '|' . json_encode($question->answer_key)
            );

            if (isset($seenQuestions[$signature])) continue;

            $seenQuestions[$signature] = true;

            $tags = $question->tags;

            if ($tags->isEmpty()) {
                $grouped[$subjectName]['General'][] = [
                    'question' => $question->text,
                    'correct_answer' => $this->formatAnswer($question->answer_key),
                ];
                continue;
            }

            foreach ($tags as $tag) {
                $grouped[$subjectName][$tag->name][] = [
                    'question' => $question->text,
                    'correct_answer' => $this->formatAnswer($question->answer_key),
                ];
            }
        }
    }

    // 🔥 If nothing to save
    if (empty($grouped)) {
        return response()->json([
            'success' => false,
            'message' => 'No incorrect answers found.'
        ]);
    }

    // 🔥 Save reviewer
    $nextNumber = GeneratedReviewer::where('user_id', $user->id)->count() + 1;

    $generatedReviewer = GeneratedReviewer::create([
        'user_id' => $user->id,
        'reviewer_number' => $nextNumber,
        'data' => $grouped,
    ]);

    return response()->json([
        'success' => true,
        'reviewer_id' => $generatedReviewer->id,
        'reviewer_number' => $generatedReviewer->reviewer_number,
    ]);
}
    
    public function generatedReviewers()
{
    $reviewers = GeneratedReviewer::where('user_id', Auth::id())
        ->orderBy('created_at', 'asc')
        ->paginate(10);

    return view('pages.student.pages.generatedReviewers', [
        'reviewers' => $reviewers,
        'preloader' => false,
        'darkmode' => true,
        'scrollup' => true,
        'banner' => true,
        'sidebar' => true,
    ]);
}

public function generatedView(GeneratedReviewer $generatedReviewer)
{
    if ($generatedReviewer->user_id != Auth::id()) {
        abort(403);
    }

    return view('pages.student.pages.generatedReviewerView', [
        'reviewer' => $generatedReviewer, // ✅ pass full model
        'preloader' => false,
        'darkmode' => true,
        'scrollup' => true,
        'banner' => true,
        'sidebar' => true,
    ]);
}

public function completeGenerated($id)
{
    $reviewer = GeneratedReviewer::where('id', $id)
        ->where('user_id', auth()->id())
        ->firstOrFail();

    if (!$reviewer->completed_at) {
        $reviewer->completed_at = now();
        $reviewer->save();
    }

    return back()->with('success', 'Reviewer marked as completed.');
}
public function deleteGenerated(GeneratedReviewer $reviewer)
{
    if ($reviewer->user_id != auth()->id()) {
        abort(403);
    }

    $reviewer->delete();

    return redirect()
        ->route('student.reviewer.generated.index')
        ->with('success', 'Reviewer deleted successfully.');
}
    /**
     * Format answer_key into readable text
     */
    private function formatAnswer($answerKey)
    {
        if (is_null($answerKey)) {
            return 'N/A';
        }

        // Decode JSON string if needed
        if (is_string($answerKey)) {
            $decoded = json_decode($answerKey, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $answerKey = $decoded;
            } else {
                return $answerKey;
            }
        }

        // Matching type (array of left/right pairs)
        if (is_array($answerKey)) {

            // If associative array of matching pairs
            if (isset($answerKey[0]['left']) && isset($answerKey[0]['right'])) {
                return collect($answerKey)
                    ->map(function ($pair) {
                        return ($pair['left'] ?? '') . ' → ' . ($pair['right'] ?? '');
                    })
                    ->implode(', ');
            }

            // Multiple correct answers
            return implode(', ', $answerKey);
        }

        return (string) $answerKey;
    }
    /* =========================================================
     |  SUBJECT DASHBOARD
     ========================================================= */
    public function show($section_id, $subject_id)
    {
        $student = Auth::user()->student;
        if (!$student) {
            abort(403, 'Student not found.');
        }

        $sectionSubject = $this->ensureStudentInSectionSubject($student, $section_id, $subject_id);
        $section = SectionModel::findOrFail($section_id);
        $subject = SubjectModel::findOrFail($subject_id);

        $schedule = $sectionSubject;

        $teacher = DB::table('subject_teacher')
            ->join('teacher_models', 'teacher_models.id', '=', 'subject_teacher.teacher_model_id')
            ->where('subject_teacher.section_model_id', $section_id)
            ->where('subject_teacher.subject_model_id', $subject_id)
            ->select('teacher_models.*')
            ->first();

        $announcements = AnnouncementModel::with([
            'teacher',
            'replies.user.teacher',
            'replies.user.student',
        ])
            ->where('section_subject_id', $sectionSubject->id ?? 0)
            ->latest()
            ->get();

        $now = now();

        $activityUpcoming = Activity::where('section_subject_id', $sectionSubject->id)
            ->where('visible', 1)
            ->whereNotNull('due_date')
            ->get()
            ->map(function ($activity) {
                $dueAt = Carbon::parse($activity->due_date);

            if (!empty($activity->due_time)) {
                $dueAt->setTimeFromTimeString($activity->due_time);
            }


                    return [
                        'type' => 'Activity',
                        'title' => $activity->title,
                        'due_at' => $dueAt,
                        'due_label' => $dueAt->format('M d, Y - h:i A'),
                        'icon' => 'icofont-notepad',
                        'icon_color' => 'text-[#03A9F4]',
                    ];
                })
                ->filter(fn ($item) => $item['due_at']->greaterThanOrEqualTo($now))
                ->values()
                ->toBase();

            $quizUpcoming = Quiz::where('section_model_id', $section_id)
                ->where('subject_model_id', $subject_id)
                ->where('visible', 1)
                ->whereNotNull('due_date')
                ->get()
                ->map(function ($quiz) {
                    $dueAt = Carbon::parse($quiz->due_date);

                    return [
                        'type' => 'Quiz',
                        'title' => $quiz->title,
                        'due_at' => $dueAt,
                        'due_label' => $dueAt->format('M d, Y - h:i A'),
                        'icon' => 'icofont-light-bulb',
                        'icon_color' => 'text-[#5F2DED]',
                    ];
                })
                ->filter(fn ($item) => $item['due_at']->greaterThanOrEqualTo($now))
                ->values()
                ->toBase();

            $upcomingItems = $activityUpcoming
                ->merge($quizUpcoming)
                ->sortBy('due_at')
                ->take(2)
                ->values();

            return view('pages.student.pages.subject', [
                'title' => $subject->subject_name,
                'student' => $student,
                'section' => $section,
                'subject' => $subject,
                'schedule' => $schedule,
                'teacher' => $teacher,
                'announcements' => $announcements,
                'upcomingItems' => $upcomingItems,
                'preloader' => false,
                'darkmode' => true,
                'scrollup' => true,
                'banner' => true,
                'sidebar' => true,
            ]);
    }

        /* =========================================================
        |  CLASSWORK LIST
        ========================================================= */
        public function showClasswork($section_id, $subject_id)
        {
            $student = Auth::user()->student;
            if (!$student) {
                abort(404);
            }

            $sectionSubject = $this->ensureStudentInSectionSubject($student, $section_id, $subject_id);
            $section = SectionModel::findOrFail($section_id);
            $subject = SubjectModel::findOrFail($subject_id);

            /* ===============================
            ACTIVITIES
            =============================== */
            $activities = $sectionSubject->activities()
                ->with([
                    'submissions' => function ($q) use ($student) {
                        $q->where('student_id', $student->id)
                        ->whereNull('deleted_at');
                    }
                ])
                ->orderByRaw("
                    CASE grading_period
                        WHEN '1st' THEN 1
                        WHEN '2nd' THEN 2
                        WHEN '3rd' THEN 3
                        WHEN '4th' THEN 4
                    END
                ")
            ->orderByRaw('COALESCE(position, 9999) ASC')// ✅ changed
                ->get()
                ->groupBy('grading_period');

            /* ===============================
            QUIZZES
            =============================== */
            $quizzes = Quiz::withCount('questions')
                ->with(['responses' => function ($q) use ($student) {
                    $userId = $student->user_id;
                    if ($userId) {
                        $q->where('user_id', $userId)
                        ->orderByDesc('created_at');
                    } else {
                        $q->whereRaw('1=0');
                    }
                }])
                ->where('section_model_id', $section->id)
                ->where('subject_model_id', $subject->id)
                ->where('visible', 1)
                ->orderByRaw("
                    CASE grading_period
                        WHEN '1st' THEN 1
                        WHEN '2nd' THEN 2
                        WHEN '3rd' THEN 3
                        WHEN '4th' THEN 4
                    END
                ")
                ->orderByRaw('COALESCE(position, 9999) ASC') // ✅ changed
                ->get();

            /* ===============================
            EVALUATIONS (VISIBLE ONLY)
            =============================== */
            $evaluations = \App\Models\Evaluation::where('section_subject_id', $sectionSubject->id)
                ->where('visible', 1)
                ->orderByRaw("
                    CASE grading_period
                        WHEN '1st' THEN 1
                        WHEN '2nd' THEN 2
                        WHEN '3rd' THEN 3
                        WHEN '4th' THEN 4
                    END
                ")
                ->orderByRaw('COALESCE(position, 9999) ASC')// ✅ changed
                ->get();

            $evaluationScores = \App\Models\EvaluationScore::where('student_id', $student->id)
                ->whereIn('evaluation_id', $evaluations->pluck('id'))
                ->get()
                ->keyBy('evaluation_id');

            return view('pages.student.pages.classwork', [
                'section' => $section,
                'subject' => $subject,
                'sectionSubject' => $sectionSubject,
                'activities' => $activities,
                'quizzes' => $quizzes,
                'evaluations' => $evaluations,
                'evaluationScores' => $evaluationScores,
                'preloader' => false,
                'darkmode' => true,
                'scrollup' => true,
                'banner' => true,
                'sidebar' => true,
            ]);
        }

    public function analytics($section_id, $subject_id)
    {
        $student = Auth::user()->student;
        if (!$student || (int) $student->section_id !== (int) $section_id) {
            abort(403);
        }

        $section = SectionModel::findOrFail($section_id);
        $subject = SubjectModel::findOrFail($subject_id);

        $sectionSubject = $this->ensureStudentInSectionSubject($student, $section_id, $subject_id);

        $studentId = $student->id;
        $userId = $student->user_id;

        $latestQuizResponses = DB::table('responses')
            ->select('quiz_id', 'user_id', DB::raw('MAX(id) as response_id'))
            ->where('user_id', $userId)
            ->groupBy('quiz_id', 'user_id');

        $activityPoints = DB::table('taggables as tg')
            ->join('activities as a', 'a.id', '=', 'tg.taggable_id')
            ->join('section_subject as ss', 'ss.id', '=', 'a.section_subject_id')
            ->join('subject_models as sm', 'sm.id', '=', 'ss.subject_model_id')
            ->join('activity_submissions as s', function ($join) use ($studentId) {
                $join->on('s.activity_id', '=', 'a.id')
                    ->where('s.is_latest', 1)
                    ->where('s.status', 'graded')
                    ->where('s.student_id', $studentId)
                    ->whereNotNull('s.score');
            })
            ->where('tg.taggable_type', Activity::class)
            ->where('a.section_subject_id', $sectionSubject->id)
            ->whereNotNull('a.total_score')
            ->where('a.total_score', '>', 0)
            ->select('tg.tag_id', 'sm.subject_name', DB::raw('(s.score / NULLIF(a.total_score, 0)) * 100 as points'));

        $quizPoints = DB::table('taggables as tg')
            ->join('quizzes as q', 'q.id', '=', 'tg.taggable_id')
            ->join('subject_models as sm', 'sm.id', '=', 'q.subject_model_id')
            ->joinSub($latestQuizResponses, 'latest_responses', function ($join) {
                $join->on('latest_responses.quiz_id', '=', 'q.id');
            })
            ->join('responses as r', 'r.id', '=', 'latest_responses.response_id')
            ->where('tg.taggable_type', Quiz::class)
            ->where('q.section_model_id', $section_id)
            ->where('q.subject_model_id', $subject_id)
            ->whereNotNull('r.score')
            ->whereNotNull('q.total_score')
            ->where('q.total_score', '>', 0)
            ->select('tg.tag_id', 'sm.subject_name', DB::raw('(r.score / NULLIF(q.total_score, 0)) * 100 as points'));

        $pointsUnion = $activityPoints->unionAll($quizPoints);

        $evaluationConfig = null;
        $schema = DB::getSchemaBuilder();

        $evaluationTable = (new Evaluation())->getTable();
        $evaluationScoreTable = (new EvaluationScore())->getTable();

        if (
            $schema->hasTable($evaluationTable)
            && $schema->hasTable($evaluationScoreTable)
            && $schema->hasColumn($evaluationScoreTable, 'evaluation_id')
            && $schema->hasColumn($evaluationScoreTable, 'student_id')
            && $schema->hasColumn($evaluationScoreTable, 'score')
        ) {
            $scope = null;
            if ($schema->hasColumn($evaluationTable, 'section_subject_id')) {
                $scope = 'section_subject';
            } elseif (
                $schema->hasColumn($evaluationTable, 'section_model_id')
                && $schema->hasColumn($evaluationTable, 'subject_model_id')
            ) {
                $scope = 'section_subject_model';
            }

            if ($scope) {
                $evaluationConfig = [
                    'class' => Evaluation::class,
                    'table' => $evaluationTable,
                    'score_table' => $evaluationScoreTable,
                    'scope' => $scope,
                ];
            }
        }

        if ($evaluationConfig) {
            $evaluationPoints = DB::table('taggables as tg')
                ->join($evaluationConfig['table'] . ' as e', 'e.id', '=', 'tg.taggable_id')
                ->join($evaluationConfig['score_table'] . ' as es', 'es.evaluation_id', '=', 'e.id')
                ->where('tg.taggable_type', $evaluationConfig['class'])
                ->where('es.student_id', $studentId)
                ->whereNotNull('es.score')
                ->whereNotNull('e.total_score')
                ->where('e.total_score', '>', 0);

            if ($evaluationConfig['scope'] === 'section_subject') {
                $evaluationPoints->join('section_subject as ss', 'ss.id', '=', 'e.section_subject_id')
                    ->join('subject_models as sm', 'sm.id', '=', 'ss.subject_model_id')
                    ->where('e.section_subject_id', $sectionSubject->id);
            } else {
                $evaluationPoints->join('subject_models as sm', 'sm.id', '=', 'e.subject_model_id')
                    ->where('e.section_model_id', $section_id)
                    ->where('e.subject_model_id', $subject_id);
            }

            $evaluationPoints->select('tg.tag_id', 'sm.subject_name', DB::raw('(es.score / NULLIF(e.total_score, 0)) * 100 as points'));
            $pointsUnion->unionAll($evaluationPoints);
        }

        $tagTotals = DB::query()
            ->fromSub($pointsUnion, 'u')
            ->join('tags as t', 't.id', '=', 'u.tag_id')
            ->select('t.name', 'u.subject_name', DB::raw('AVG(u.points) as average_points'))
            ->groupBy('t.name', 'u.subject_name')
            ->orderByDesc('average_points')
            ->get();

        $chartData = [
            'labels' => $tagTotals->map(function ($row) {
                $subjectName = trim((string) ($row->subject_name ?? '')) ?: 'Unknown Subject';
                return $row->name . ' (' . $subjectName . ')';
            })->values(),
            'values' => $tagTotals->map(function ($row) {
                return round((float) $row->average_points, 2);
            })->values(),
        ];

        [$tagNeed, $attemptedTagIds, $tagCoverage, $tagSimple] = $this->computeReviewerNeeds($student, $sectionSubject);
        $recommendedLessons = collect();
        $lowPerformanceLessonTags = collect();

        if (!empty($tagNeed)) {
            $lowPerformanceLessonTags = Tag::whereIn('id', array_keys($tagNeed))
                ->get()
                ->sort(function ($a, $b) use ($tagNeed, $tagCoverage, $tagSimple) {
                    $needA = (float) ($tagNeed[$a->id] ?? $tagNeed[(string) $a->id] ?? 0.0);
                    $needB = (float) ($tagNeed[$b->id] ?? $tagNeed[(string) $b->id] ?? 0.0);
                    if ($needA !== $needB) {
                        return $needB <=> $needA;
                    }

                    $simpleA = $tagSimple[$a->id] ?? $tagSimple[(string) $a->id] ?? ['weakness' => 0.0, 'missed' => 0.0];
                    $simpleB = $tagSimple[$b->id] ?? $tagSimple[(string) $b->id] ?? ['weakness' => 0.0, 'missed' => 0.0];
                    $weaknessA = (float) ($simpleA['weakness'] ?? 0.0);
                    $weaknessB = (float) ($simpleB['weakness'] ?? 0.0);
                    if ($weaknessA !== $weaknessB) {
                        return $weaknessB <=> $weaknessA;
                    }

                    $missedA = (float) ($simpleA['missed'] ?? 0.0);
                    $missedB = (float) ($simpleB['missed'] ?? 0.0);
                    if ($missedA !== $missedB) {
                        return $missedB <=> $missedA;
                    }

                    $coverageA = (float) ($tagCoverage[$a->id] ?? $tagCoverage[(string) $a->id] ?? 0.0);
                    $coverageB = (float) ($tagCoverage[$b->id] ?? $tagCoverage[(string) $b->id] ?? 0.0);
                    if ($coverageA !== $coverageB) {
                        return $coverageB <=> $coverageA;
                    }

                    return strcasecmp((string) ($a->name ?? ''), (string) ($b->name ?? ''));
                })
                ->values();

            $lessonPool = LessonModel::with('tags')
                ->where('section_model_id', $section_id)
                ->where('subject_model_id', $subject_id)
                ->orderByRaw("
                    CASE grading_period
                        WHEN '1st' THEN 1
                        WHEN '2nd' THEN 2
                        WHEN '3rd' THEN 3
                        WHEN '4th' THEN 4
                    END
                ")
                ->orderBy('created_at', 'asc')
                ->get()
                ->filter(function ($lesson) {
                    return $this->isLessonAvailableForStudent($lesson);
                })
                ->values();

            $recommendedLessons = $this->buildLessonRecommendationsFromNeeds(
                $tagNeed,
                $attemptedTagIds,
                $lessonPool,
                (int) config('reviewers.max_recommendations', 5)
            );
        }

        return response()
            ->view('pages.student.pages.analytics', [
                'title' => $subject->subject_name . ' - Performance Analytics',
                'section' => $section,
                'subject' => $subject,
                'chartData' => $chartData,
                'recommendedLessons' => $recommendedLessons,
                'lowPerformanceLessonTags' => $lowPerformanceLessonTags,
                'preloader' => false,
                'darkmode' => true,
                'scrollup' => true,
                'banner' => true,
                'sidebar' => true,
            ])
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }


    /* =========================================================
     |  VIEW ACTIVITY
     ========================================================= */
   public function viewActivity($section_id, $subject_id, $activity_id)
{
    $student = Auth::user()->student;
    if (!$student) {
        abort(404);
    }

    $sectionSubject = $this->ensureStudentInSectionSubject($student, $section_id, $subject_id);
    $section = SectionModel::findOrFail($section_id);
    $subject = SubjectModel::findOrFail($subject_id);

    $activity = Activity::where('id', $activity_id)
        ->where('section_subject_id', $sectionSubject->id)
        ->firstOrFail();

    $this->ensureActivityAccessibleForStudent($activity);

    // ✅ ACTIVE submissions only (for UI)
    $submissions = ActivitySubmission::where('activity_id', $activity->id)
        ->where('student_id', $student->id)
        ->whereNull('deleted_at')
        ->orderBy('attempt_number', 'asc')
        ->get();

    // ✅ Current submission (latest, not deleted)
    $submission = $submissions->where('is_latest', true)->first();

    // ✅ COUNT ALL ATTEMPTS (including soft-deleted)
    $usedAttempts = ActivitySubmission::withTrashed()
        ->where('activity_id', $activity->id)
        ->where('student_id', $student->id)
        ->count();

    $maxAttempts = $activity->max_attempt ?? 0;

    $attemptsRemaining = $maxAttempts > 0
        ? max($maxAttempts - $usedAttempts, 0)
        : PHP_INT_MAX;

    return view('pages.student.pages.viewActivity', [
        'section' => $section,
        'subject' => $subject,
        'sectionSubject' => $sectionSubject,
        'activity' => $activity,
        'submission' => $submission,
        'submissions' => $submissions,
        'attemptsRemaining' => $attemptsRemaining,
        'student' => $student,
        'preloader' => false,
        'darkmode' => true,
        'scrollup' => true,
        'banner' => false,
        'sidebar' => true,
    ]);
}


    /* =========================================================
     |  LESSONS
     ========================================================= */
    public function showLessons($section_id, $subject_id)
{
    $student = Auth::user()->student;
    if (!$student || $student->section_id != $section_id) {
        abort(403);
    }

    $section = SectionModel::findOrFail($section_id);
    $subject = SubjectModel::findOrFail($subject_id);

    $this->ensureStudentInSectionSubject($student, $section_id, $subject_id);

    $lessons = LessonModel::with('tags')
        ->where('section_model_id', $section_id)
        ->where('subject_model_id', $subject_id)
        ->orderByRaw("
            CASE grading_period
                WHEN '1st' THEN 1
                WHEN '2nd' THEN 2
                WHEN '3rd' THEN 3
                WHEN '4th' THEN 4
            END
        ")
        ->orderByRaw('COALESCE(position, 9999) ASC') // ✅ ORDER BY POSITION
        ->get()
        ->filter(function ($lesson) {
            return $this->isLessonAvailableForStudent($lesson);
        })
        ->values();

    return view('pages.student.pages.lesson', [
        'title' => "{$subject->subject_name} - {$section->section_name} Lessons",
        'section' => $section,
        'subject' => $subject,
        'lessons' => $lessons,
        'preloader' => false,
        'darkmode' => true,
        'scrollup' => true,
        'banner' => true,
        'sidebar' => true,
    ]);
}

    public function showLesson($section_id, $subject_id, $lesson_id)
    {
        $student = Auth::user()->student;
        if (!$student || $student->section_id != $section_id) {
            abort(403);
        }

        $this->ensureStudentInSectionSubject($student, $section_id, $subject_id);
        $section = SectionModel::findOrFail($section_id);
        $subject = SubjectModel::findOrFail($subject_id);

        $lesson = LessonModel::with('tags')
            ->where('id', $lesson_id)
            ->where('section_model_id', $section_id)
            ->where('subject_model_id', $subject_id)
            ->firstOrFail();

        if (!$this->isLessonAvailableForStudent($lesson)) {
            abort(403, 'This lesson is not yet available.');
        }

        return view('pages.student.pages.lesson-view', [
            'title' => "{$subject->subject_name} - Lesson",
            'section' => $section,
            'subject' => $subject,
            'lesson' => $lesson,
            'preloader' => false,
            'darkmode' => true,
            'scrollup' => true,
            'banner' => false,
            'sidebar' => true,
        ]);
    }

    public function showStudents($section_id, $subject_id)
    {
        $student = Auth::user()->student;
        if (!$student) {
            abort(404, 'Student not found.');
        }

        $this->ensureStudentInSectionSubject($student, $section_id, $subject_id);

        $section = SectionModel::findOrFail($section_id);
        $subject = SubjectModel::findOrFail($subject_id);

        $students = StudentModel::where('section_id', $section_id)
    ->orderBy('last_name')
    ->orderBy('first_name')
    ->paginate(10); // 👈 change number if needed


        return view('pages.student.pages.students', [
            'title' => $subject->subject_name . ' - Students',
            'section' => $section,
            'subject' => $subject,
            'students' => $students,
            'preloader' => false,
            'darkmode' => true,
            'scrollup' => true,
            'banner' => true,
            'sidebar' => true,
        ]);
    }

    public function showReviewer(Reviewer $reviewer)
    {
        $student = Auth::user()->student;
        if (!$student) {
            abort(403);
        }

        $reviewer->load('tags');

        $completed = ReviewerInteraction::where('student_id', $student->id)
            ->where('reviewer_id', $reviewer->id)
            ->whereNotNull('completed_at')
            ->exists();

        return view('pages.student.pages.reviewer.show', [
            'title' => "Reviewer: {$reviewer->title}",
            'reviewer' => $reviewer,
            'completedReviewer' => $completed,
            'preloader' => false,
            'darkmode' => true,
            'scrollup' => true,
            'banner' => true,
            'sidebar' => true,
        ]);
    }

    public function showH5pReviewer(H5pReviewer $reviewer)
    {
        $student = Auth::user()->student;
        if (!$student) {
            abort(403);
        }

        $sectionId = $student->section_id;
        if (!$sectionId) {
            abort(403);
        }

        $teacherIds = SubjectTeacher::where('section_model_id', $sectionId)
            ->pluck('teacher_model_id')
            ->filter()
            ->unique()
            ->values();

        if ($teacherIds->isEmpty()) {
            abort(403);
        }

        $teacherUserIds = TeacherModel::whereIn('id', $teacherIds)
            ->pluck('user_id')
            ->filter()
            ->unique()
            ->values();

        $allowedTeacherIds = $teacherIds->merge($teacherUserIds)->unique();
        if (!$allowedTeacherIds->contains($reviewer->teacher_id)) {
            abort(403);
        }

        $reviewer->load('tags');

        return view('pages.student.pages.reviewer.showH5P', [
            'title' => "H5P Reviewer: {$reviewer->title}",
            'reviewer' => $reviewer,
            'preloader' => false,
            'darkmode' => true,
            'scrollup' => true,
            'banner' => true,
            'sidebar' => true,
        ]);
    }

    private function buildQuizTagEvidenceForSectionSubjects($student, $sectionSubjects, array $targetTagIds): array
    {
        $normalizedTagIds = collect($targetTagIds)
            ->map(static fn ($id) => (int) $id)
            ->filter(static fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();
        $targetTagLookup = array_fill_keys($normalizedTagIds, true);

        if (empty($normalizedTagIds) || !$student || !$student->user_id || !$sectionSubjects || $sectionSubjects->isEmpty()) {
            return [];
        }

        $sectionId = (int) ($sectionSubjects->first()->section_model_id ?? 0);
        if ($sectionId <= 0) {
            return [];
        }

        $sectionSubjectMetaById = $sectionSubjects
            ->mapWithKeys(function ($sectionSubject) {
                $sectionSubjectId = (int) ($sectionSubject->id ?? 0);
                $subjectId = (int) ($sectionSubject->subject_model_id ?? 0);
                if ($sectionSubjectId <= 0 || $subjectId <= 0) {
                    return [];
                }

                $subjectName = trim((string) optional($sectionSubject->subject)->subject_name);
                return [
                    $sectionSubjectId => [
                        'subject_id' => $subjectId,
                        'subject_name' => $subjectName !== '' ? $subjectName : 'Unknown Subject',
                    ],
                ];
            })
            ->all();

        if (empty($sectionSubjectMetaById)) {
            return [];
        }

        $subjectNamesById = collect($sectionSubjectMetaById)
            ->mapWithKeys(static function ($meta) {
                $subjectId = (int) ($meta['subject_id'] ?? 0);
                if ($subjectId <= 0) {
                    return [];
                }

                return [$subjectId => (string) ($meta['subject_name'] ?? 'Unknown Subject')];
            })
            ->all();

        $sectionSubjectIds = array_keys($sectionSubjectMetaById);

        $insights = [];
        $normalizeGradingPeriod = static function ($value): string {
            $label = trim((string) ($value ?? ''));
            return $label !== '' ? $label : 'N/A';
        };
        $addEvidenceRow = function (
            int $tagId,
            string $subjectName,
            string $classworkType,
            string $classworkTitle,
            string $gradingPeriod,
            string $preview,
            float $earned,
            float $total,
            float $missed,
            ?string $relevanceLevel,
            ?string $sourceQuestion = null,
            array $sourceTags = []
        ) use (&$insights, $targetTagLookup) {
            if ($tagId <= 0 || !isset($targetTagLookup[$tagId]) || $total <= 0 || $missed <= 0) {
                return;
            }

            $relevanceWeight = $this->resolveTagRelevanceWeight($relevanceLevel);
            $contributionValue = $missed * $relevanceWeight;
            if ($contributionValue <= 0) {
                return;
            }

            $normalizedSourceTags = collect($sourceTags)
                ->map(static fn ($tag) => trim((string) $tag))
                ->filter(static fn ($tag) => $tag !== '')
                ->unique()
                ->values()
                ->all();

            if (!isset($insights[$tagId])) {
                $insights[$tagId] = [
                    'rows' => [],
                    'total_contribution' => 0.0,
                    'total_missed' => 0.0,
                    'total_exposure' => 0.0,
                ];
            }

            $insights[$tagId]['rows'][] = [
                'subject_name' => $subjectName,
                'classwork_type' => $classworkType,
                'classwork_title' => $classworkTitle,
                'grading_period' => $gradingPeriod,
                'question_preview' => $preview,
                'earned_points' => round($earned, 2),
                'total_points' => round($total, 2),
                'missed_points' => round($missed, 2),
                'contribution_value' => round($contributionValue, 6),
                'source_question' => $sourceQuestion !== null
                    ? Str::limit(Str::squish(strip_tags($sourceQuestion)), 240)
                    : null,
                'source_tags' => $normalizedSourceTags,
            ];
            $insights[$tagId]['total_contribution'] += $contributionValue;
            $insights[$tagId]['total_missed'] += $missed;
            $insights[$tagId]['total_exposure'] += $total;
        };

        // Quiz evidence (question-level)
        $quizzes = Quiz::query()
            ->where('section_model_id', $sectionId)
            ->whereIn('subject_model_id', array_keys($subjectNamesById))
            ->get(['id', 'title', 'subject_model_id', 'grading_period']);

        $quizIds = $quizzes->pluck('id')->filter()->values()->all();

        $latestResponsesByQuizId = collect();
        $questionsByQuizId = collect();
        if (!empty($quizIds)) {
            $latestResponsesByQuizId = QuizResponse::query()
                ->whereIn('quiz_id', $quizIds)
                ->where('user_id', $student->user_id)
                ->with('answers')
                ->orderByDesc('created_at')
                ->get()
                ->groupBy('quiz_id')
                ->map(fn ($items) => $items->first());

            $questionsByQuizId = \App\Models\Question::query()
                ->whereIn('quiz_id', $quizIds)
                ->with('tags')
                ->orderBy('order')
                ->get()
                ->groupBy('quiz_id');
        }

        foreach ($quizzes as $quiz) {
            $response = $latestResponsesByQuizId->get($quiz->id);
            if (!$response) {
                continue;
            }

            $subjectName = $subjectNamesById[(int) $quiz->subject_model_id] ?? 'Unknown Subject';
            $quizTitle = trim((string) ($quiz->title ?? 'Untitled quiz')) ?: 'Untitled quiz';
            $gradingPeriod = $normalizeGradingPeriod($quiz->grading_period ?? null);
            $answersByQuestionId = $response->answers->keyBy('question_id');
            $quizQuestions = $questionsByQuizId->get((int) $quiz->id, collect());
            if ($quizQuestions->isEmpty()) {
                continue;
            }

            foreach ($quizQuestions as $question) {
                if (!$question->tags || $question->tags->isEmpty()) {
                    continue;
                }

                $questionTotal = max(0.0, (float) ($question->points ?? 0));
                if ($questionTotal <= 0) {
                    continue;
                }

                $answer = $answersByQuestionId->get($question->id);
                $earned = 0.0;
                if ($answer) {
                    $answer->setRelation('question', $question);
                    $earned = max(0.0, (float) $answer->scoreValue());
                }

                $earned = min($earned, $questionTotal);
                $missed = max(0.0, $questionTotal - $earned);
                if ($missed <= 0) {
                    continue;
                }

                $questionPreview = Str::limit(Str::squish(strip_tags((string) ($question->text ?? 'Untitled question'))), 140);
                $questionTagNames = $question->tags
                    ->map(static fn ($questionTag) => trim((string) ($questionTag->name ?? '')))
                    ->filter(static fn ($name) => $name !== '')
                    ->values()
                    ->all();

                foreach ($question->tags as $tag) {
                    $tagId = (int) ($tag->id ?? 0);
                    if ($tagId <= 0) {
                        continue;
                    }

                    $addEvidenceRow(
                        $tagId,
                        $subjectName,
                        'Quiz',
                        $quizTitle,
                        $gradingPeriod,
                        $questionPreview,
                        $earned,
                        $questionTotal,
                        $missed,
                        optional($tag->pivot)->relevance_level,
                        (string) ($question->text ?? ''),
                        $questionTagNames
                    );
                }
            }
        }

        // Activity evidence (classwork-level)
        $activities = Activity::query()
            ->whereIn('section_subject_id', $sectionSubjectIds)
            ->with(['tags' => function ($query) use ($normalizedTagIds) {
                $query->whereIn('tags.id', $normalizedTagIds);
            }])
            ->get(['id', 'title', 'description', 'section_subject_id', 'grading_period', 'total_score']);
        $activityIds = $activities->pluck('id')->filter()->values()->all();
        $activitySubmissionsByActivityId = collect();
        if (!empty($activityIds)) {
            $activitySubmissionsByActivityId = ActivitySubmission::query()
                ->whereIn('activity_id', $activityIds)
                ->where('student_id', $student->id)
                ->where('is_latest', 1)
                ->where('status', 'graded')
                ->whereNotNull('score')
                ->get()
                ->keyBy('activity_id');
        }

        foreach ($activities as $activity) {
            if (!$activity->tags || $activity->tags->isEmpty()) {
                continue;
            }

            $meta = $sectionSubjectMetaById[(int) ($activity->section_subject_id ?? 0)] ?? null;
            if (!$meta) {
                continue;
            }

            $total = max(0.0, (float) ($activity->total_score ?? 0));
            if ($total <= 0) {
                continue;
            }

            $submission = $activitySubmissionsByActivityId->get($activity->id);
            if (!$submission || $submission->score === null) {
                continue;
            }

            $earned = max(0.0, min((float) $submission->score, $total));
            $missed = max(0.0, $total - $earned);
            if ($missed <= 0) {
                continue;
            }

            $title = trim((string) ($activity->title ?? 'Untitled activity')) ?: 'Untitled activity';
            $previewSeed = trim((string) ($activity->description ?? ''));
            if ($previewSeed === '') {
                $previewSeed = $title;
            }
            $preview = Str::limit(Str::squish(strip_tags($previewSeed)), 140);
            $gradingPeriod = $normalizeGradingPeriod($activity->grading_period ?? null);

            foreach ($activity->tags as $tag) {
                $tagId = (int) ($tag->id ?? 0);
                if ($tagId <= 0) {
                    continue;
                }

                $addEvidenceRow(
                    $tagId,
                    (string) ($meta['subject_name'] ?? 'Unknown Subject'),
                    'Activity',
                    $title,
                    $gradingPeriod,
                    $preview,
                    $earned,
                    $total,
                    $missed,
                    optional($tag->pivot)->relevance_level
                );
            }
        }

        // Evaluation evidence (classwork-level)
        $evaluations = Evaluation::query()
            ->whereIn('section_subject_id', $sectionSubjectIds)
            ->with(['tags' => function ($query) use ($normalizedTagIds) {
                $query->whereIn('tags.id', $normalizedTagIds);
            }])
            ->get(['id', 'title', 'description', 'section_subject_id', 'grading_period', 'total_score']);
        $evaluationIds = $evaluations->pluck('id')->filter()->values()->all();
        $evaluationScoresByEvaluationId = collect();
        if (!empty($evaluationIds)) {
            $evaluationScoresByEvaluationId = EvaluationScore::query()
                ->whereIn('evaluation_id', $evaluationIds)
                ->where('student_id', $student->id)
                ->whereNotNull('score')
                ->orderByDesc('id')
                ->get()
                ->groupBy('evaluation_id')
                ->map(fn ($items) => $items->first());
        }

        foreach ($evaluations as $evaluation) {
            if (!$evaluation->tags || $evaluation->tags->isEmpty()) {
                continue;
            }

            $meta = $sectionSubjectMetaById[(int) ($evaluation->section_subject_id ?? 0)] ?? null;
            if (!$meta) {
                continue;
            }

            $total = max(0.0, (float) ($evaluation->total_score ?? 0));
            if ($total <= 0) {
                continue;
            }

            $scoreRow = $evaluationScoresByEvaluationId->get($evaluation->id);
            if (!$scoreRow || $scoreRow->score === null) {
                continue;
            }

            $earned = max(0.0, min((float) $scoreRow->score, $total));
            $missed = max(0.0, $total - $earned);
            if ($missed <= 0) {
                continue;
            }

            $title = trim((string) ($evaluation->title ?? 'Untitled evaluation')) ?: 'Untitled evaluation';
            $previewSeed = trim((string) ($evaluation->description ?? ''));
            if ($previewSeed === '') {
                $previewSeed = $title;
            }
            $preview = Str::limit(Str::squish(strip_tags($previewSeed)), 140);
            $gradingPeriod = $normalizeGradingPeriod($evaluation->grading_period ?? null);

            foreach ($evaluation->tags as $tag) {
                $tagId = (int) ($tag->id ?? 0);
                if ($tagId <= 0) {
                    continue;
                }

                $addEvidenceRow(
                    $tagId,
                    (string) ($meta['subject_name'] ?? 'Unknown Subject'),
                    'Evaluation',
                    $title,
                    $gradingPeriod,
                    $preview,
                    $earned,
                    $total,
                    $missed,
                    optional($tag->pivot)->relevance_level
                );
            }
        }

        foreach ($insights as $tagId => $payload) {
            $rows = $payload['rows'] ?? [];
            usort($rows, static function ($a, $b) {
                $contributionCompare = ((float) ($b['contribution_value'] ?? 0.0)) <=> ((float) ($a['contribution_value'] ?? 0.0));
                if ($contributionCompare !== 0) {
                    return $contributionCompare;
                }

                return ((float) ($b['missed_points'] ?? 0.0)) <=> ((float) ($a['missed_points'] ?? 0.0));
            });

            $rows = array_slice($rows, 0, 20);
            $totalContribution = max(0.0, (float) ($payload['total_contribution'] ?? 0.0));

            foreach ($rows as &$row) {
                $rowContribution = max(0.0, (float) ($row['contribution_value'] ?? 0.0));
                $row['contribution_percent'] = $totalContribution > 0
                    ? round(($rowContribution / $totalContribution) * 100, 2)
                    : 0.0;
            }
            unset($row);

            $totalMissed = max(0.0, (float) ($payload['total_missed'] ?? 0.0));
            $totalExposure = max(0.0, (float) ($payload['total_exposure'] ?? 0.0));

            $insights[$tagId] = [
                'rows' => $rows,
                'missed_points' => round($totalMissed, 2),
                'exposure_points' => round($totalExposure, 2),
            ];
        }

        return $insights;
    }

    private function buildReviewerRecommendations($student, $sectionSubject, $reviewers, array $completedReviewerIds)
    {
        [$tagNeed, $attemptedTagIds] = $this->computeReviewerNeeds($student, $sectionSubject);

        return $this->buildReviewerRecommendationsFromNeeds(
            $tagNeed,
            $attemptedTagIds,
            $reviewers,
            $completedReviewerIds,
            (int) config('reviewers.max_recommendations', 5)
        );
    }

    private function buildReviewerRecommendationsFromNeeds(
        array $tagNeed,
        array $attemptedTagIds,
        $reviewers,
        array $completedReviewerIds,
        int $limit = 5
    ) {
        if (empty($tagNeed)) {
            return collect();
        }

        $needMagnitude = 0.0;
        foreach ($tagNeed as $value) {
            $needMagnitude += $value * $value;
        }
        $needMagnitude = sqrt($needMagnitude);
        if ($needMagnitude <= 0) {
            return collect();
        }

        $recommendations = [];
        foreach ($reviewers as $reviewer) {
            if ($reviewer instanceof Reviewer && in_array($reviewer->id, $completedReviewerIds, true)) {
                continue;
            }

            $reviewerTagCount = max(1, $reviewer->tags->count());
            $reviewerTagWeights = [];
            $reviewerMagnitudeSquared = 0.0;
            foreach ($reviewer->tags as $tag) {
                $tagIdKey = (string) $tag->id;
                $relevanceWeight = $this->resolveTagRelevanceWeight(optional($tag->pivot)->relevance_level);
                $coverageRatio = $this->normalizeCoverageRatio(optional($tag->pivot)->coverage_ratio, $reviewerTagCount);
                $itemWeight = (float) ($relevanceWeight * $coverageRatio);
                if ($itemWeight <= 0) {
                    continue;
                }

                $reviewerTagWeights[$tagIdKey] = $itemWeight;
                $reviewerMagnitudeSquared += $itemWeight * $itemWeight;
            }

            if (empty($reviewerTagWeights)) {
                continue;
            }

            $reviewerMagnitude = sqrt($reviewerMagnitudeSquared);
            if ($reviewerMagnitude <= 0) {
                continue;
            }

            $dot = 0.0;
            $hasAttempted = false;
            foreach ($reviewerTagWeights as $tagId => $itemWeight) {
                if (!isset($attemptedTagIds[$tagId])) {
                    continue;
                }
                $hasAttempted = true;
                $needWeight = (float) ($tagNeed[$tagId] ?? $tagNeed[(int) $tagId] ?? 0.0);
                if ($needWeight <= 0) {
                    continue;
                }

                $dot += $needWeight * $itemWeight;
            }

            if (!$hasAttempted || $dot <= 0) {
                continue;
            }

            $score = $dot / ($needMagnitude * $reviewerMagnitude);
            $reviewer->match_score = round($score, 4);
            $reviewer->compatibility_percent = round(
                $reviewer->match_score * 100,
                (int) config('reviewers.compatibility_percent_decimals', 1)
            );
            $recommendations[] = $reviewer;
        }

        return collect($recommendations)
            ->sortByDesc('match_score')
            ->take(max(1, $limit))
            ->values();
    }

    private function buildLessonRecommendationsFromNeeds(
        array $tagNeed,
        array $attemptedTagIds,
        $lessons,
        int $limit = 5
    ) {
        if (empty($tagNeed)) {
            return collect();
        }

        $needMagnitude = 0.0;
        foreach ($tagNeed as $value) {
            $needMagnitude += $value * $value;
        }
        $needMagnitude = sqrt($needMagnitude);
        if ($needMagnitude <= 0) {
            return collect();
        }

        $compatibilityDecimals = (int) config('reviewers.compatibility_percent_decimals', 1);
        $recommendations = [];
        foreach ($lessons as $lesson) {
            $lessonTagCount = max(1, $lesson->tags->count());
            $lessonTagWeights = [];
            $lessonMagnitudeSquared = 0.0;
            foreach ($lesson->tags as $tag) {
                $tagIdKey = (string) $tag->id;
                $relevanceWeight = $this->resolveTagRelevanceWeight(optional($tag->pivot)->relevance_level);
                $coverageRatio = $this->normalizeCoverageRatio(optional($tag->pivot)->coverage_ratio, $lessonTagCount);
                $itemWeight = (float) ($relevanceWeight * $coverageRatio);
                if ($itemWeight <= 0) {
                    continue;
                }

                $lessonTagWeights[$tagIdKey] = $itemWeight;
                $lessonMagnitudeSquared += $itemWeight * $itemWeight;
            }

            if (empty($lessonTagWeights)) {
                continue;
            }

            $lessonMagnitude = sqrt($lessonMagnitudeSquared);
            if ($lessonMagnitude <= 0) {
                continue;
            }

            $dot = 0.0;
            $hasAttempted = false;
            foreach ($lessonTagWeights as $tagId => $itemWeight) {
                if (!isset($attemptedTagIds[$tagId])) {
                    continue;
                }
                $hasAttempted = true;
                $needWeight = (float) ($tagNeed[$tagId] ?? $tagNeed[(int) $tagId] ?? 0.0);
                if ($needWeight <= 0) {
                    continue;
                }

                $dot += $needWeight * $itemWeight;
            }

            if (!$hasAttempted || $dot <= 0) {
                continue;
            }

            $lesson->match_score = round($dot / ($needMagnitude * $lessonMagnitude), 4);
            $lesson->compatibility_percent = round(
                $lesson->match_score * 100,
                $compatibilityDecimals
            );
            $recommendations[] = $lesson;
        }

        return collect($recommendations)
            ->sortByDesc('match_score')
            ->take(max(1, $limit))
            ->values();
    }

    private function isLessonAvailableForStudent(LessonModel $lesson): bool
    {
        if ((int) ($lesson->visible ?? 1) !== 1) {
            return false;
        }

        if (!$lesson->open_date) {
            return true;
        }

        $datePart = Carbon::parse($lesson->open_date)->format('Y-m-d');
        $timePart = $lesson->open_time
            ? Carbon::parse($lesson->open_time)->format('H:i:s')
            : '00:00:00';

        $openAt = Carbon::parse($datePart . ' ' . $timePart);

        return now()->greaterThanOrEqualTo($openAt);
    }

    private function computeReviewerNeeds($student, $sectionSubject): array
    {
        $tagNeed = [];
        $tagCoverage = [];
        $tagSimple = [];
        $attemptedTagIds = [];
        $tagCredit = [];
        $tagDebt = [];
        $tagExposure = [];
        $tagRawMissed = [];
        $tagRawExposure = [];
        $totalExposure = 0.0;

        $applyTagPerformance = function ($tags, float $earnedScore, float $maxScore) use (
            &$attemptedTagIds,
            &$tagCredit,
            &$tagDebt,
            &$tagExposure,
            &$tagRawMissed,
            &$tagRawExposure,
            &$totalExposure
        ) {
            if ($maxScore <= 0 || !$tags || $tags->isEmpty()) {
                return;
            }

            $cappedEarned = max(0.0, min($earnedScore, $maxScore));
            $missedScore = max(0.0, $maxScore - $cappedEarned);

            foreach ($tags as $tag) {
                $tagId = (int) ($tag->id ?? 0);
                if ($tagId <= 0) {
                    continue;
                }

                $attemptedTagIds[(string) $tagId] = true;

                $relevanceWeight = $this->resolveTagRelevanceWeight(optional($tag->pivot)->relevance_level);
                $creditContribution = $cappedEarned * $relevanceWeight;
                $debtContribution = $missedScore * $relevanceWeight;

                $tagCredit[$tagId] = ($tagCredit[$tagId] ?? 0.0) + $creditContribution;
                $tagDebt[$tagId] = ($tagDebt[$tagId] ?? 0.0) + $debtContribution;

                $tagExposure[$tagId] = ($tagExposure[$tagId] ?? 0.0) + $maxScore;
                $tagRawExposure[$tagId] = ($tagRawExposure[$tagId] ?? 0.0) + $maxScore;
                $tagRawMissed[$tagId] = ($tagRawMissed[$tagId] ?? 0.0) + $missedScore;
                $totalExposure += $maxScore;
            }
        };

        $activities = Activity::with('tags')
            ->where('section_subject_id', $sectionSubject->id)
            ->get();
        $activityIds = $activities->pluck('id')->all();
        $activitySubmissions = [];
        if (!empty($activityIds)) {
            $activitySubmissions = ActivitySubmission::whereIn('activity_id', $activityIds)
                ->where('student_id', $student->id)
                ->where('is_latest', 1)
                ->where('status', 'graded')
                ->get()
                ->keyBy('activity_id');
        }

        foreach ($activities as $activity) {
            $totalScore = (float) ($activity->total_score ?? 0);
            if ($totalScore <= 0) {
                continue;
            }
            $submission = $activitySubmissions[$activity->id] ?? null;
            if (!$submission || $submission->score === null) {
                continue;
            }
            $earnedScore = max(0.0, min((float) $submission->score, $totalScore));
            $applyTagPerformance($activity->tags, $earnedScore, $totalScore);
        }

        $quizzes = Quiz::query()
            ->where('section_model_id', $sectionSubject->section_model_id)
            ->where('subject_model_id', $sectionSubject->subject_model_id)
            ->get();
        $quizIds = $quizzes->pluck('id')->all();
        $quizQuestionsByQuizId = collect();
        if (!empty($quizIds)) {
            $quizQuestionsByQuizId = \App\Models\Question::with('tags')
                ->whereIn('quiz_id', $quizIds)
                ->orderBy('order')
                ->get()
                ->groupBy('quiz_id');
        }
        $quizResponses = [];
        if ($student->user_id && !empty($quizIds)) {
            $quizResponses = QuizResponse::whereIn('quiz_id', $quizIds)
                ->where('user_id', $student->user_id)
                ->with('answers')
                ->orderByDesc('created_at')
                ->get()
                ->groupBy('quiz_id')
                ->map(function ($items) {
                    return $items->first();
                });
        }

        foreach ($quizzes as $quiz) {
            $response = $quizResponses[$quiz->id] ?? null;
            if (!$response) {
                continue;
            }

            $answersByQuestionId = $response->answers->keyBy('question_id');
            $quizQuestions = $quizQuestionsByQuizId->get((int) $quiz->id, collect());

            foreach ($quizQuestions as $question) {
                if ($question->tags->isEmpty()) {
                    continue;
                }

                $questionMaxScore = max(0.0, (float) ($question->points ?? 0));
                if ($questionMaxScore <= 0) {
                    continue;
                }

                $answer = $answersByQuestionId[$question->id] ?? null;
                $earnedScore = 0.0;
                if ($answer) {
                    $answer->setRelation('question', $question);
                    $earnedScore = max(0.0, (float) $answer->scoreValue());
                }

                $applyTagPerformance($question->tags, $earnedScore, $questionMaxScore);
            }
        }

        $evaluations = Evaluation::with('tags')
            ->where('section_subject_id', $sectionSubject->id)
            ->get();
        $evaluationIds = $evaluations->pluck('id')->all();
        $evaluationScores = [];
        if (!empty($evaluationIds)) {
            $evaluationScores = EvaluationScore::whereIn('evaluation_id', $evaluationIds)
                ->where('student_id', $student->id)
                ->get()
                ->keyBy('evaluation_id');
        }

        foreach ($evaluations as $evaluation) {
            $totalScore = (float) ($evaluation->total_score ?? 0);
            if ($totalScore <= 0) {
                continue;
            }
            $scoreRow = $evaluationScores[$evaluation->id] ?? null;
            if (!$scoreRow || $scoreRow->score === null) {
                continue;
            }
            $earnedScore = max(0.0, min((float) $scoreRow->score, $totalScore));
            $applyTagPerformance($evaluation->tags, $earnedScore, $totalScore);
        }

        $allTagIds = collect(array_merge(
            array_keys($tagCredit),
            array_keys($tagDebt),
            array_keys($tagExposure),
            array_keys($tagRawExposure),
            array_keys($tagRawMissed)
        ))
            ->unique()
            ->values()
            ->all();

        foreach ($allTagIds as $tagId) {
            $tagId = (int) $tagId;
            $credit = (float) ($tagCredit[$tagId] ?? 0.0);
            $debt = (float) ($tagDebt[$tagId] ?? 0.0);
            $balance = $credit - $debt;
            $baseNeed = max(0.0, -$balance);
            $coverageRatio = $totalExposure > 0
                ? max(0.0, min(1.0, ((float) ($tagExposure[$tagId] ?? 0.0)) / $totalExposure))
                : 0.0;
            $rawExposure = (float) ($tagRawExposure[$tagId] ?? 0.0);
            $rawMissed = (float) ($tagRawMissed[$tagId] ?? 0.0);
            $weakness = $rawExposure > 0 ? max(0.0, min(1.0, $rawMissed / $rawExposure)) : 0.0;

            $tagCoverage[$tagId] = $coverageRatio;
            $tagSimple[$tagId] = [
                'weakness' => $weakness,
                'missed' => $rawMissed,
                'exposure' => $rawExposure,
            ];

            if ($baseNeed <= 0) {
                continue;
            }

            $priority = $baseNeed * sqrt($coverageRatio);
            // Tiny deterministic tie-breaker: if priorities are effectively equal, higher coverage wins.
            $priority += $coverageRatio * 1.0e-6;

            if ($priority > 0) {
                $tagNeed[$tagId] = $priority;
            }
        }

        return [$tagNeed, $attemptedTagIds, $tagCoverage, $tagSimple];
    }

    public function completeReviewer(Reviewer $reviewer)
    {
        $student = Auth::user()->student;
        if (!$student) {
            abort(403);
        }
        // Reviewers are now global, so no subject validation required.

        ReviewerInteraction::updateOrCreate(
            [
                'reviewer_id' => $reviewer->id,
                'student_id' => $student->id,
            ],
            [
                'status' => 'completed',
                'completed_at' => now(),
            ]
        );

        return back()->with('success', 'Reviewer marked as completed.');
    }


    public function attendance($section_id, $subject_id)
    {
        $student = Auth::user()->student;
        if (!$student) {
            abort(404);
        }

        $sectionSubject = $this->ensureStudentInSectionSubject($student, $section_id, $subject_id);
        $section = SectionModel::findOrFail($section_id);
        $subject = SubjectModel::findOrFail($subject_id);

        $attendanceRecords = \App\Models\AttendanceRecord::where('student_id', $student->id)
            ->join('attendances', 'attendance_records.attendance_id', '=', 'attendances.id')
            ->where('attendances.section_subject_id', $sectionSubject->id)
            ->orderBy('attendances.day', 'asc')

            ->select(
                'attendance_records.*',
                'attendances.date',
                'attendances.day',
                'attendances.grading_period'
            )
            ->get();

        return view('pages.student.pages.attendance', [
            'title' => 'My Attendance',
            'student' => $student,
            'section' => $section,
            'subject' => $subject,
            'attendanceRecords' => $attendanceRecords,
            'preloader' => false,
            'darkmode' => true,
            'scrollup' => true,
            'sidebar' => true,
            'banner' => false,
        ]);
    }

    public function previewQuiz($section_id, $subject_id, $quiz_id)
    {
        $student = Auth::user()->student;
        if (!$student) {
            abort(404);
        }

        $this->ensureStudentInSectionSubject($student, $section_id, $subject_id);
        $section = SectionModel::findOrFail($section_id);
        $subject = SubjectModel::findOrFail($subject_id);

        $quiz = Quiz::where('id', $quiz_id)
            ->where('section_model_id', $section_id)
            ->where('subject_model_id', $subject_id)
            ->firstOrFail();

        $now = now();
        $isVisible = ((int) ($quiz->visible ?? 0) === 1);
        $isOpen = ((int) ($quiz->is_open ?? 0) === 1);
        $openAt = $this->getQuizOpenAt($quiz);
        $dueAt = $quiz->due_date ? Carbon::parse($quiz->due_date) : null;
        $isPastDue = $dueAt && $now->greaterThan($dueAt);

        if (!$isVisible) {
            abort(403, 'This quiz is not published yet.');
        }

        if (!$isOpen) {
            if ($openAt && $now->lessThan($openAt)) {
                abort(403, 'This quiz opens on ' . $openAt->format('F j, Y \\a\\t h:i A') . '.');
            }
            if ($isPastDue && !$quiz->accepting) {
                abort(403, 'The deadline for this quiz has passed.');
            }
            abort(403, 'This quiz is not open yet.');
        }

        if ($isPastDue && !$quiz->accepting) {
            abort(403, 'The deadline for this quiz has passed.');
        }

        $openedAt = $openAt;

        return view('pages.student.pages.classwork.quiz.preview', [
            'title' => $quiz->title . ' - Quiz Details',
            'section' => $section,
            'subject' => $subject,
            'quiz' => $quiz,
            'openedAt' => $openedAt,
            'dueAt' => $dueAt,
            'attemptUrl' => route('student.quiz.take', [$section->id, $subject->id, $quiz->id]),
            'preloader' => false,
            'darkmode' => true,
            'scrollup' => true,
            'sidebar' => true,
            'banner' => false,
        ]);
    }

    public function attemptQuiz($section_id, $subject_id, $quiz_id)
    {
        $student = Auth::user()->student;
        if (!$student) {
            abort(404);
        }

        $this->ensureStudentInSectionSubject($student, $section_id, $subject_id);
        $section = SectionModel::findOrFail($section_id);
        $subject = SubjectModel::findOrFail($subject_id);

        $quiz = Quiz::where('id', $quiz_id)
            ->where('section_model_id', $section_id)
            ->where('subject_model_id', $subject_id)
            ->firstOrFail();

        // Check access status
        $now = now();
        $isVisible = ((int) ($quiz->visible ?? 0) === 1);
        $isOpen = ((int) ($quiz->is_open ?? 0) === 1);
        $openAt = $this->getQuizOpenAt($quiz);
        $dueAt = $quiz->due_date ? Carbon::parse($quiz->due_date) : null;
        $isPastDue = $dueAt && $now->greaterThan($dueAt);

        if (!$isVisible) {
            abort(403, 'This quiz is not published yet.');
        }

        if (!$isOpen) {
            if ($openAt && $now->lessThan($openAt)) {
                abort(403, 'This quiz opens on ' . $openAt->format('F j, Y \\a\\t h:i A') . '.');
            }
            if ($isPastDue && !$quiz->accepting) {
                abort(403, 'The deadline for this quiz has passed.');
            }
            abort(403, 'This quiz is not open yet.');
        }

        if ($isPastDue && !$quiz->accepting) {
            abort(403, 'The deadline for this quiz has passed.');
        }

        $questions = $quiz->questions()->orderBy('order')->get();

        return view('pages.student.pages.classwork.quiz.attempt_quiz', [
            'title' => $quiz->title . ' - Attempt Quiz',
            'section' => $section,
            'subject' => $subject,
            'quiz' => $quiz,
            'questions' => $questions,
            'preloader' => false,
            'darkmode' => true,
            'scrollup' => true,
            'sidebar' => true,
            'banner' => false,
        ]);
    }

    public function submitQuiz(Request $request, $section_id, $subject_id, $quiz_id)
    {
        $student = auth()->user()->student;
        if (!$student) {
            abort(404, 'Student record not found.');
        }

        $this->ensureStudentInSectionSubject($student, $section_id, $subject_id);
        $section = SectionModel::findOrFail($section_id);
        $subject = SubjectModel::findOrFail($subject_id);

        $quiz = Quiz::where('id', $quiz_id)
            ->where('section_model_id', $section_id)
            ->where('subject_model_id', $subject_id)
            ->firstOrFail();

        // Check access status
        $now = now();
        $isVisible = ((int) ($quiz->visible ?? 0) === 1);
        $isOpen = ((int) ($quiz->is_open ?? 0) === 1);
        $openAt = $this->getQuizOpenAt($quiz);
        $dueAt = $quiz->due_date ? Carbon::parse($quiz->due_date) : null;
        $isPastDue = $dueAt && $now->greaterThan($dueAt);

        if (!$isVisible) {
            abort(403, 'This quiz is not published yet.');
        }

        if (!$isOpen) {
            if ($openAt && $now->lessThan($openAt)) {
                abort(403, 'This quiz opens on ' . $openAt->format('F j, Y \\a\\t h:i A') . '.');
            }
            if ($isPastDue && !$quiz->accepting) {
                abort(403, 'The deadline for this quiz has passed.');
            }
            abort(403, 'This quiz is not open yet.');
        }

        if ($isPastDue && !$quiz->accepting) {
            abort(403, 'The deadline for this quiz has passed.');
        }

        $validated = $request->validate([
            'answers' => 'required|array',
            'start_time' => 'nullable|string',
        ]);

        $answersInput = $validated['answers'];

        // load questions keyed by id for quick lookup
        $questions = $quiz->questions()->get()->keyBy('id');

        // Required question validation
        $errors = [];
        foreach ($questions as $question) {
            $qId = $question->id;
            $required = (bool) ($question->required ?? false);
            if (!$required) {
                continue;
            }

            $answerValue = $answersInput[$qId] ?? null;
            $questionType = (string) ($question->type ?? '');

            $hasAnswer = false;
            if ($questionType === 'matching') {
                $pairs = [];
                $options = $question->option ?? [];
                if (is_string($options)) {
                    $options = json_decode($options, true) ?? [];
                }
                $leftPool = (is_array($options) && isset($options['left']) && is_array($options['left']))
                    ? array_values($options['left'])
                    : [];
                $rightPool = (is_array($options) && isset($options['right']) && is_array($options['right']))
                    ? array_values($options['right'])
                    : [];

                if (is_array($answerValue) && isset($answerValue['match_map']) && is_array($answerValue['match_map'])) {
                    foreach ($answerValue['match_map'] as $rowIndex => $colIndex) {
                        if (!is_numeric($rowIndex) || !is_numeric($colIndex)) {
                            continue;
                        }
                        $r = (int) $rowIndex;
                        $c = (int) $colIndex;
                        if (!array_key_exists($r, $leftPool) || !array_key_exists($c, $rightPool)) {
                            continue;
                        }
                        $l = trim((string) ($leftPool[$r] ?? ''));
                        $rr = trim((string) ($rightPool[$c] ?? ''));
                        if ($l === '' || $rr === '') {
                            continue;
                        }
                        $pairs[] = ['left' => $l, 'right' => $rr];
                    }
                } elseif (is_array($answerValue)) {
                    foreach (array_values($answerValue) as $p) {
                        if (!is_array($p) || !isset($p['left'], $p['right'])) continue;
                        $l = trim((string) $p['left']);
                        $r = trim((string) $p['right']);
                        if ($l === '' || $r === '') continue;
                        $pairs[] = ['left' => $l, 'right' => $r];
                    }
                }
                $hasAnswer = count($pairs) > 0;
            } elseif ($questionType === 'multiple_choice') {
                $vals = is_array($answerValue) ? array_filter($answerValue, fn($v) => $v !== null && $v !== '') : [$answerValue];
                $vals = array_filter($vals, fn($v) => trim((string) $v) !== '');
                $hasAnswer = count($vals) > 0;
            } elseif ($questionType === 'true_false') {
                if ($answerValue === true || $answerValue === false || $answerValue === 'true' || $answerValue === 'false' || $answerValue === 1 || $answerValue === 0 || $answerValue === '1' || $answerValue === '0') {
                    $hasAnswer = true;
                }
            } else {
                $text = is_array($answerValue) ? '' : trim((string) $answerValue);
                $hasAnswer = $text !== '';
            }

            if (!$hasAnswer) {
                $errors["answers.$qId"] = "Question \"" . (trim((string) $question->text) ?: '#'.$qId) . "\" is required.";
            }
        }

        if (!empty($errors)) {
            return back()->withErrors($errors)->withInput();
        }

        // prevent duplicate attempt (if responses.user_id enforced)
        $existing = QuizResponse::where('quiz_id', $quiz->id)
            ->where('user_id', auth()->id())
            ->orderByDesc('created_at')
            ->first();
        if ($existing) {
            $meta = is_array($existing->meta ?? null) ? $existing->meta : [];
            $allowRetake = in_array(($meta['access_override'] ?? null), ['reopen', 'open'], true);

            if (!$allowRetake) {
                return redirect()->route('student.quiz.take', [$section->id, $subject->id, $quiz->id])
                    ->with('error', 'You have already submitted this quiz.');
            }
        }

        $startTimeRaw = $validated['start_time'] ?? null;
        $startedAt = $startTimeRaw ? \Carbon\Carbon::parse($startTimeRaw) : now();
        $submittedAt = now();
        $timeSpentSeconds = max(0, $startedAt->diffInSeconds($submittedAt));

        DB::beginTransaction();
        try {
            $response = QuizResponse::create([
                'quiz_id' => $quiz->id,
                'session_token' => Str::random(40),
                'user_id' => auth()->id(),
                'meta' => [
                    'start_time' => $startedAt->toIso8601String(),
                    'time_spent_seconds' => $timeSpentSeconds,
                ],
            ]);

            $score = 0;
            foreach ($answersInput as $qId => $answerValue) {
                $question = $questions->get((int) $qId);
                if (!$question)
                    continue; // ignore unknown ids

                $questionType = (string) ($question->type ?? '');

                // Options are casted to array on the model, but keep backward-compat for JSON strings.
                $options = $question->option ?? [];
                if (is_string($options)) {
                    $options = json_decode($options, true) ?? [];
                }

                // Matching questions submit nested match_map (rowIndex => colIndex).
                // Store as array of pairs: [ ['left' => '...', 'right' => '...'], ... ]
                if ($questionType === 'matching') {
                    $leftPool = (is_array($options) && isset($options['left']) && is_array($options['left']))
                        ? array_values($options['left'])
                        : [];
                    $rightPool = (is_array($options) && isset($options['right']) && is_array($options['right']))
                        ? array_values($options['right'])
                        : [];

                    $pairs = [];

                    if (is_array($answerValue) && isset($answerValue['match_map']) && is_array($answerValue['match_map'])) {
                        foreach ($answerValue['match_map'] as $rowIndex => $colIndex) {
                            if (!is_numeric($rowIndex) || !is_numeric($colIndex)) {
                                continue;
                            }
                            $r = (int) $rowIndex;
                            $c = (int) $colIndex;
                            if (!array_key_exists($r, $leftPool) || !array_key_exists($c, $rightPool)) {
                                continue;
                            }
                            $l = trim((string) ($leftPool[$r] ?? ''));
                            $rr = trim((string) ($rightPool[$c] ?? ''));
                            if ($l === '' || $rr === '') {
                                continue;
                            }
                            $pairs[] = ['left' => $l, 'right' => $rr];
                        }
                    } elseif (is_array($answerValue)) {
                        // Backward-compat: accept direct array of pairs
                        foreach (array_values($answerValue) as $p) {
                            if (!is_array($p) || !isset($p['left'], $p['right'])) continue;
                            $l = trim((string) $p['left']);
                            $rr = trim((string) $p['right']);
                            if ($l === '' || $rr === '') continue;
                            $pairs[] = ['left' => $l, 'right' => $rr];
                        }
                    }

                    $answerModel = QuizAnswer::create([
                        'response_id' => $response->id,
                        'question_id' => $question->id,
                        'answer' => array_values($pairs),
                    ]);

                    $answerModel->setRelation('question', $question);
                    if (method_exists($answerModel, 'scoreValue')) {
                        $score += max(0, (int) $answerModel->scoreValue());
                    } else {
                        $score += max(0, (int) ($question->points ?? 1));
                    }

                    continue;
                }

                // normalize to array
                $storedAnswer = is_array($answerValue) ? array_values($answerValue) : [$answerValue];

                $mapped = [];
                foreach ($storedAnswer as $ans) {
                    // if ans is numeric and corresponds to an option index, map it
                    if (is_numeric($ans) && array_key_exists((int) $ans, $options)) {
                        $opt = $options[(int) $ans];
                        if (is_array($opt)) {
                            $mapped[] = (string) ($opt['value'] ?? $opt['id'] ?? $opt['text'] ?? json_encode($opt));
                        } else {
                            $mapped[] = (string) $opt;
                        }
                    } else {
                        // otherwise assume it's already the option value/text
                        $mapped[] = (string) $ans;
                    }
                }

                $answerModel = QuizAnswer::create([
                    'response_id' => $response->id,
                    'question_id' => $question->id,
                    'answer' => $mapped,
                ]);

                // grading: compare to question->answer_key (casted to array)
                $correctKey = $question->answer_key ?? [];
                if (is_string($correctKey)) {
                    $decoded = json_decode($correctKey, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $correctKey = $decoded;
                    }
                }

                $userAnswer = $answerModel->answer ?? [];
                $questionType = (string) ($question->type ?? '');
                $normalizeValue = function ($value) use ($questionType) {
                    if ($questionType === 'true_false') {
                        if ($value === true || $value === 'true' || $value === 1 || $value === '1') {
                            return 'true';
                        }
                        if ($value === false || $value === 'false' || $value === 0 || $value === '0') {
                            return 'false';
                        }
                    }

                    if (is_bool($value)) {
                        return $value ? 'true' : 'false';
                    }
                    if (is_numeric($value)) {
                        return (string) (0 + $value);
                    }
                    return trim((string)$value);
                };

                $correct = is_array($correctKey) ? array_map($normalizeValue, $correctKey) : [$normalizeValue($correctKey)];
                $user = is_array($userAnswer) ? array_map($normalizeValue, $userAnswer) : [$normalizeValue($userAnswer)];

                $correct = array_values(array_unique($correct));
                $user = array_values(array_unique($user));

                // simple array-equality check
                if (!empty($correct) && empty(array_diff($correct, $user)) && empty(array_diff($user, $correct))) {
                    $score += max(0, (int) ($question->points ?? 1));
                }
            }

            // store score if responses table has score column
            if (Schema::hasColumn('responses', 'score')) {
                $response->score = $score;
                $meta = is_array($response->meta) ? $response->meta : [];
                $meta['time_spent_seconds'] = $meta['time_spent_seconds'] ?? $timeSpentSeconds;
                $meta['start_time'] = $meta['start_time'] ?? $startedAt->toIso8601String();
                $response->meta = $meta;
                $response->save();
            }

            DB::commit();

            return redirect()->route('student.classwork', [$section->id, $subject->id])
                ->with('success', 'Quiz submitted successfully. Score: ' . $score);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to submit quiz: ' . $e->getMessage());
        }
    }

    public function showQuizResponse($section_id, $subject_id, $quiz_id)
    {
        $user = auth()->user();
        $student = $user->student;
        if (!$student) {
            abort(404, 'Student record not found.');
        }

        $this->ensureStudentInSectionSubject($student, $section_id, $subject_id);
        $section = SectionModel::findOrFail($section_id);
        $subject = SubjectModel::findOrFail($subject_id);
        $quiz = Quiz::where('id', $quiz_id)
            ->where('section_model_id', $section_id)
            ->where('subject_model_id', $subject_id)
            ->firstOrFail();

        if ((int) ($quiz->visible ?? 0) !== 1) {
            abort(403, 'This quiz is not published yet.');
        }

        $response = QuizResponse::where('quiz_id', $quiz->id)
            ->where('user_id', $user->id)
            ->with(['answers.question'])
            ->latest()
            ->first();

        if (!$response) {
            return redirect()->route('student.classwork', [$section->id, $subject->id])
                ->with('error', 'No submission found for this quiz.');
        }

        $quizSettingRaw = $quiz->setting ?? [];
        if (is_string($quizSettingRaw)) {
            $decoded = json_decode($quizSettingRaw, true);
            $quizSetting = is_array($decoded) ? $decoded : [];
        } elseif (is_array($quizSettingRaw)) {
            $quizSetting = $quizSettingRaw;
        } else {
            $quizSetting = [];
        }

        $studentCanReview = array_key_exists('student_can_review', $quizSetting)
            ? (bool)$quizSetting['student_can_review']
            : true;
        $studentCanViewScore = array_key_exists('student_can_view_score', $quizSetting)
            ? (bool)$quizSetting['student_can_view_score']
            : true;
        $studentCanViewCorrectAnswers = array_key_exists('student_can_view_correct_answers', $quizSetting)
            ? (bool)$quizSetting['student_can_view_correct_answers']
            : true;

        if (!$studentCanReview && !$studentCanViewScore) {
            return redirect()->route('student.classwork', [$section->id, $subject->id])
                ->with('error', 'Quiz results are not available for this quiz.');
        }

        $answers = $response->answers->map(function ($a) use ($studentCanViewCorrectAnswers) {
            $question = $a->question;

            if (($question->type ?? '') === 'matching') {
                $formatPairs = function ($pairs) {
                    $pairs = is_array($pairs) ? array_values($pairs) : [];
                    $out = [];
                    foreach ($pairs as $p) {
                        if (!is_array($p)) continue;
                        $l = trim((string) ($p['left'] ?? ''));
                        $r = trim((string) ($p['right'] ?? ''));
                        if ($l === '' || $r === '') continue;
                        $out[] = $l . ' -> ' . $r;
                    }
                    return $out;
                };

                return [
                    'question_id' => $a->question_id,
                    'question_text' => optional($question)->text,
                    'saved_answer' => $formatPairs($a->answer ?? []),
                    'is_correct' => $studentCanViewCorrectAnswers && method_exists($a, 'isCorrect') ? $a->isCorrect() : null,
                    'correct_answer' => $studentCanViewCorrectAnswers ? $formatPairs($question->answer_key ?? []) : [],
                ];
            }

            // normalize correct key
            $correctKey = $question->answer_key ?? [];
            if (is_string($correctKey)) {
                $correctKey = json_decode($correctKey, true) ?? [$correctKey];
            }
            $correctKey = (array) $correctKey;

            // normalize options
            $options = $question->option ?? [];
            if (is_string($options)) {
                $options = json_decode($options, true) ?? [];
            }

            $correct = [];
            foreach ($correctKey as $ck) {
                $found = null;
                foreach ($options as $opt) {
                    if (is_array($opt)) {
                        if ((isset($opt['value']) && (string) $opt['value'] === (string) $ck) || (isset($opt['id']) && (string) $opt['id'] === (string) $ck) || (isset($opt['text']) && (string) $opt['text'] === (string) $ck)) {
                            $found = $opt['text'] ?? ($opt['value'] ?? ($opt['id'] ?? json_encode($opt)));
                            break;
                        }
                    } else {
                        if ((string) $opt === (string) $ck) {
                            $found = $opt;
                            break;
                        }
                    }
                }
                $correct[] = $found ?? (string) $ck;
            }

            return [
                'question_id' => $a->question_id,
                'question_text' => optional($question)->text,
                'saved_answer' => $a->answer,
                'is_correct' => $studentCanViewCorrectAnswers && method_exists($a, 'isCorrect') ? $a->isCorrect() : null,
                'correct_answer' => $studentCanViewCorrectAnswers ? $correct : [],
            ];
        });

        $paginationValue = (int) ($quizSetting['pagination'] ?? 10);
        if ($paginationValue < 1) $paginationValue = 1;
        if ($paginationValue > 25) $paginationValue = 25;

        return view('pages.student.pages.classwork.quiz.response', [
            'title' => $quiz->title . ' - Quiz Response',
            'section' => $section,
            'subject' => $subject,
            'quiz' => $quiz,
            'response' => $response,
            'answers' => $answers,
            'timeSpentSeconds' => (int) (($response->meta['time_spent_seconds'] ?? 0)),
            'paginationValue' => $paginationValue,
            'studentResultAccess' => [
                'canReview' => $studentCanReview,
                'canViewScore' => $studentCanViewScore,
                'showCorrectAnswers' => ($studentCanReview && $studentCanViewCorrectAnswers),
            ],
            'preloader' => false,
            'darkmode' => true,
            'scrollup' => true,
            'sidebar' => true,
            'banner' => true,
        ]);
    }

    

public function grade(Request $request, $section_model_id, $subject_model_id)
{
    $section = SectionModel::findOrFail($section_model_id);
    $subject = SubjectModel::findOrFail($subject_model_id);

    $student = Auth::user()->student;

    // Safety check
    if (!$student || $student->section_id != $section->id) {
        abort(403, 'You are not enrolled in this section.');
    }

    $gradingPeriod = $request->get('grading_period', '1st');

    $sectionSubject = SectionSubject::where('section_model_id', $section_model_id)
        ->where('subject_model_id', $subject_model_id)
        ->firstOrFail();

    $grade = Grade::where('section_subject_id', $sectionSubject->id)
        ->where('grading_period', $gradingPeriod)
        ->where('student_id', $student->id)
        ->first();

    return view('pages.student.pages.grade', [
        'title' => $subject->subject_name . ' - ' . $section->section_name,
        'section' => $section,
        'subject' => $subject,
        'student' => $student,
        'grade' => $grade,
        'gradingPeriod' => $gradingPeriod,

        'preloader' => false,
        'darkmode' => true,
        'scrollup' => true,
        'banner' => true,
        'sidebar' => true,
    ]);
}
public function gradePreview(Request $request, $section_id, $subject_id)
{
    $gradingPeriod = (string) ($request->grading_period ?? '1st');

    $student = Auth::user()->student;
    if (!$student) {
        abort(403, 'Student not found.');
    }

    $sectionSubject = SectionSubject::where('section_model_id', $section_id)
        ->where('subject_model_id', $subject_id)
        ->firstOrFail();

    /* ===============================
       LOAD GROUPS
    =============================== */
    $groups = SubjectGrading::where('subject_id', $subject_id)->get();

    /* ===============================
       LOAD CONTENT GROUPED
    =============================== */
    $activities = Activity::where('section_subject_id', $sectionSubject->id)
        ->where('grading_period', $gradingPeriod)
        ->get()
        ->groupBy('classwork_type');

    $quizzes = Quiz::where('section_model_id', $section_id)
        ->where('subject_model_id', $subject_id)
        ->where('grading_period', $gradingPeriod)
        ->get()
        ->groupBy('classwork_type');

    $evaluations = Evaluation::where('section_subject_id', $sectionSubject->id)
        ->where('grading_period', $gradingPeriod)
        ->get()
        ->groupBy('classwork_type');

    /* ===============================
       ATTENDANCE GROUP
    =============================== */
    $attendanceType = AttendanceType::where('section_subject_id', $sectionSubject->id)
        ->where('grading_period', $gradingPeriod)
        ->first();

    $attendanceGroupId = $attendanceType?->subject_grading_id;

    $attendances = Attendance::with('records')
        ->where('section_subject_id', $sectionSubject->id)
        ->where('grading_period', $gradingPeriod)
        ->get();

    /* ===============================
       COMPUTATION
    =============================== */
    $groupBreakdown = [];
    $initialGrade = 0;

    foreach ($groups as $group) {

        $groupWeight = (int) $group->percentage;
        $earned = 0;
        $possible = 0;
        $items = [];

        $groupActivities  = $activities[$group->id] ?? collect();
        $groupQuizzes     = $quizzes[$group->id] ?? collect();
        $groupEvaluations = $evaluations[$group->id] ?? collect();

        /* ===== ATTENDANCE ===== */
        if ($attendanceGroupId == $group->id) {

            $present = 0;
            $late = 0;
            $excused = 0;
            $absent = 0;

            foreach ($attendances as $attendance) {
                foreach ($attendance->records as $record) {
                    if ($record->student_id != $student->id) continue;

                    if ($record->status === 'present') $present++;
                    if ($record->status === 'late') $late++;
                    if ($record->status === 'excused') $excused++;
                    if ($record->status === 'absent') $absent++;
                }
            }

            $totalDays = $present + $late + $excused + $absent;

            if ($totalDays > 0) {

                $earnedDays =
                    ($present * 1) +
                    ($late * 0.5) +
                    ($excused * 1);

                $possible += $totalDays;
                $earned += $earnedDays;

                $items[] = [
                    'label' => 'Attendance',
                    'score' => round($earnedDays, 2) . " / " . $totalDays
                ];
            }
        }

        /* ===== ACTIVITIES ===== */
        foreach ($groupActivities as $activity) {

            if ($activity->total_score <= 0) continue;

            $possible += $activity->total_score;

            $submission = ActivitySubmission::where('activity_id', $activity->id)
                ->where('student_id', $student->id)
                ->where('is_latest', 1)
                ->where('status', 'graded')
                ->first();

            $score = $submission?->score ?? 0;
            $earned += max(0, $score);

            $items[] = [
                'label' => $activity->title,
                'score' => "{$score} / {$activity->total_score}"
            ];
        }

        /* ===== QUIZZES ===== */
        foreach ($groupQuizzes as $quiz) {

            if ($quiz->total_score <= 0) continue;

            $possible += $quiz->total_score;

            $score = 0;

            if ($student->user_id) {
                $response = QuizResponse::where('quiz_id', $quiz->id)
                    ->where('user_id', $student->user_id)
                    ->latest()
                    ->first();

                $score = $response?->score ?? 0;
            }

            $earned += max(0, $score);

            $items[] = [
                'label' => 'Quiz: ' . $quiz->title,
                'score' => "{$score} / {$quiz->total_score}"
            ];
        }

        /* ===== EVALUATIONS ===== */
        foreach ($groupEvaluations as $evaluation) {

            if ($evaluation->total_score <= 0) continue;

            $possible += $evaluation->total_score;

            $scoreRow = EvaluationScore::where('evaluation_id', $evaluation->id)
                ->where('student_id', $student->id)
                ->first();

            $score = $scoreRow?->score ?? 0;

            $earned += max(0, $score);

            $items[] = [
                'label' => 'Evaluation: ' . $evaluation->title,
                'score' => "{$score} / {$evaluation->total_score}"
            ];
        }

        /* ===== PS & WS ===== */
        if ($possible > 0) {

            $ps = ($earned / $possible) * 100;
            $ws = ($ps * $groupWeight) / 100;

            $initialGrade += $ws;

            $groupBreakdown[] = [
                'group'     => $group->classwork_type,
                'weight'    => $groupWeight,
                'earned'    => round($earned, 2),
                'possible'  => round($possible, 2),
                'ps'        => round($ps, 2),
                'ws'        => round($ws, 2),
                'items'     => $items
            ];
        }
    }

    return response()->json([
        'student' => $student->last_name . ', ' . $student->first_name,
        'initial' => round($initialGrade, 2),
        'groups'  => $groupBreakdown
    ]);
}

}
