<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Concerns\HandlesTagMetrics;
use App\Http\Controllers\Controller;
use App\Models\LessonModel;
use App\Models\Quiz;
use App\Models\Response as QuizResponse;
use App\Models\SchoolYear;
use App\Models\SectionModel;
use App\Models\SubjectModel;
use App\Models\SubjectTeacher;
use App\Services\SectionStudentRoster;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\AnnouncementModel;
use App\Models\Attendance;
use App\Models\StudentModel as Student;
use App\Models\AttendanceRecord;
use App\Models\Activity;
use App\Models\ActivitySubmission;
use App\Models\AdminAnnouncement;
use App\Models\Tag;
use App\Models\AttendanceScore;
use App\Models\AttendanceType;
use App\Models\Evaluation;
use App\Models\EvaluationScore;
use App\Models\Grade;
use App\Models\SectionSubject;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use App\Models\GradeSetup;
use App\Models\SubjectGrading;
use Illuminate\Support\Facades\Log;
use Database\Seeders\AnalyticsDemoSeeder;
use Illuminate\Pagination\LengthAwarePaginator;
use PhpParser\Node\Expr\Eval_;

class TeacherClassController extends Controller
{   
    use HandlesTagMetrics;

    private function activeSchoolYearId(): ?int
    {
        return SchoolYear::activeId();
    }

    private function activeAssignmentsQuery(int $teacherId)
    {
        $query = SubjectTeacher::query()
            ->where('teacher_model_id', $teacherId);

        return $query->whereHas('section.schoolYear', function ($schoolYearQuery) {
            $schoolYearQuery->where(function ($query) {
                $query->where('is_active', true)
                    ->orWhere('teacher_audit_enabled', true);
            });
        });
    }

    private function roster(): SectionStudentRoster
    {
        return app(SectionStudentRoster::class);
    }

    private function sectionStudentsQuery(SectionModel $section)
    {
        return $this->roster()->studentsQuery($section);
    }

    private function sectionStudentIds(SectionModel $section): array
    {
        return $this->roster()->studentIds($section);
    }

    private function sectionStudentUserIds(SectionModel $section): array
    {
        return $this->roster()->studentUserIds($section);
    }

    private function sectionStudentCount(SectionModel $section): int
    {
        return $this->roster()->count($section);
    }

    private function isAuditOnlySection(SectionModel $section): bool
    {
        return $this->roster()->isAuditOnly($section);
    }

    public function dashboard(Request $request)
{
    $teacher = Auth::user()->teacher;

    /* ===============================
       ANNOUNCEMENTS
    =============================== */
    $announcements = AdminAnnouncement::query()
        ->forActiveSchoolYear()
        ->orderBy('date_posted', 'desc')
        ->paginate(4);

    if ($request->ajax()) {
        return view('pages.teacher.pages.announcement-list', compact('announcements'))->render();
    }

    /* ===============================
       TEACHER ASSIGNMENTS
    =============================== */
    $assignments = $this->activeAssignmentsQuery($teacher->id)
        ->with('section')
        ->get();
    $totalClasses = $assignments->count();

    /* ===============================
       TOTAL STUDENTS (UNIQUE)
    =============================== */
    $sections = $assignments->pluck('section')->filter()->unique('id')->values();
    $sectionIds = $sections->pluck('id');

    $studentCountsBySection = $sections->mapWithKeys(function ($section) {
        return [$section->id => $this->sectionStudentCount($section)];
    });

    $totalStudents = $sections
        ->flatMap(fn ($section) => $this->sectionStudentIds($section))
        ->unique()
        ->count();

    /* ===============================
       TASKS (ACTIVITIES + QUIZZES)
    =============================== */
    $tasks = collect();

    $sectionSubjects = SectionSubject::whereIn('section_model_id', $sectionIds)->get();

    /* ---------- ACTIVITIES ---------- */
    foreach ($sectionSubjects as $sectionSubject) {
        $studentsCount = (int) ($studentCountsBySection[$sectionSubject->section_model_id] ?? 0);

        foreach ($sectionSubject->activities as $activity) {
            $completed = ActivitySubmission::where('activity_id', $activity->id)
                ->where('is_latest', 1)
                ->distinct('student_id')
                ->count('student_id');

            $tasks->push([
                'section'   => $sectionSubject->section->section_name,
                'title'     => $activity->title,
                'completed' => $completed,
                'total'     => $studentsCount,
            ]);
        }
    }

    /* ---------- QUIZZES ---------- */
    $quizzes = Quiz::whereIn('section_model_id', $sectionIds)->get();

    foreach ($quizzes as $quiz) {
        $studentsCount = (int) ($studentCountsBySection[$quiz->section_model_id] ?? 0);

        $completed = QuizResponse::where('quiz_id', $quiz->id)
            ->distinct('user_id')
            ->count('user_id');

        $tasks->push([
            'section'   => SectionModel::find($quiz->section_model_id)?->section_name,
            'title'     => $quiz->title,
            'completed' => $completed,
            'total'     => $studentsCount,
        ]);
    }

    /* ===============================
       PAGINATE TASKS
    =============================== */
    $perPage = 5;
    $currentPage = LengthAwarePaginator::resolveCurrentPage('task_page');

    $paginatedTasks = new LengthAwarePaginator(
        $tasks->slice(($currentPage - 1) * $perPage, $perPage)->values(),
        $tasks->count(),
        $perPage,
        $currentPage,
        [
            'path' => request()->url(),
            'pageName' => 'task_page',
        ]
    );

    /* ===============================
       RETURN VIEW
    =============================== */
    return view('pages.teacher.pages.dashboard', [
        'title' => 'Teacher Dashboard',
        'preloader' => false,
        'darkmode' => true,
        'scrollup' => true,
        'banner' => true,
        'sidebar' => true,

        'announcements' => $announcements,
        'totalStudents' => $totalStudents,
        'totalClasses' => $totalClasses,
        'tasks' => $paginatedTasks,
    ]);
}

    public function index(Request $request)
{
    $teacher = Auth::user()->teacher;

    /* ===============================
       INPUTS
    =============================== */
    $selectedGrade = $request->filled('grade')
        ? (int) $request->query('grade')
        : null;

    $search = trim($request->query('search', ''));

    /* ===============================
       BASE QUERY
    =============================== */
    $query = SubjectTeacher::with(['subject', 'section.schoolYear'])
        ->whereIn('id', $this->activeAssignmentsQuery($teacher->id)->select('id'));

    /* ===============================
       FILTER BY GRADE
    =============================== */
    if ($selectedGrade) {
        $query->whereHas('section', function ($q) use ($selectedGrade) {
            $q->where('grade_level', $selectedGrade);
        });
    }

    /* ===============================
       SEARCH (SUBJECT / SECTION)
    =============================== */
    if ($search !== '') {
        $query->where(function ($q) use ($search) {
            $q->whereHas('subject', function ($sq) use ($search) {
                $sq->where('subject_name', 'like', "%{$search}%");
            })
            ->orWhereHas('section', function ($sq) use ($search) {
                $sq->where('section_name', 'like', "%{$search}%");
            });
        });
    }

    /* ===============================
       LOAD + ENRICH DATA
    =============================== */
    $assignments = $query->get()
        ->map(function ($assignment) {

            $schedule = DB::table('section_subject')
                ->where('section_model_id', $assignment->section_model_id)
                ->where('subject_model_id', $assignment->subject_model_id)
                ->first();

            /* ---------- DAYS NORMALIZATION ---------- */
            $daysLabel = '—';

            if ($schedule) {
                $rawDays =
                    $schedule->days
                    ?? $schedule->day
                    ?? $schedule->days_of_week
                    ?? null;

                if ($rawDays) {
                    if (is_string($rawDays) && str_starts_with($rawDays, '[')) {
                        $days = json_decode($rawDays, true);
                    } elseif (is_string($rawDays)) {
                        $days = array_map('trim', explode(',', $rawDays));
                    } elseif (is_array($rawDays)) {
                        $days = $rawDays;
                    }

                    $daysLabel = collect($days ?? [])
                        ->map(fn ($d) => ucfirst(substr($d, 0, 3)))
                        ->join(', ');
                }
            }

            $assignment->schedule = $schedule;
            $assignment->days_label = $daysLabel;
            $assignment->is_audit_only = $assignment->section
                ? $this->roster()->isAuditOnly($assignment->section)
                : false;

            return $assignment;
        })
        /* ===============================
           SORT: GRADE → START TIME
        =============================== */
        ->sortBy(function ($assignment) {
            return [
                $assignment->section->grade_level ?? 0,
                $assignment->schedule?->start_time ?? '99:99:99',
            ];
        })
        ->values();

    /* ===============================
       AVAILABLE GRADES
    =============================== */
    $availableGrades = $this->activeAssignmentsQuery($teacher->id)
        ->with('section')
        ->get()
        ->pluck('section.grade_level')
        ->filter()
        ->unique()
        ->sort()
        ->values();

    /* ===============================
       RETURN VIEW
    =============================== */
    return view('pages.teacher.pages.class', [
        'title' => 'My Classes',
        'preloader' => false,
        'darkmode' => true,
        'scrollup' => true,
        'banner' => true,
        'sidebar' => true,

        'assignments' => $assignments,
        'availableGrades' => $availableGrades,
        'selectedGrade' => $selectedGrade,
        'search' => $search,
    ]);
}




    /**
     * Display the specified resource.
     */
    public function show($section_model_id, $subject_model_id)
{
    $section = SectionModel::findOrFail($section_model_id);
    $subject = SubjectModel::findOrFail($subject_model_id);

    // Assignment (teacher-subject-section)
    $assignment = $section->assignedTeachers()
        ->where('subject_model_id', $subject_model_id)
        ->first();

    // Schedule
    $schedule = DB::table('section_subject')
        ->where('section_model_id', $section_model_id)
        ->where('subject_model_id', $subject_model_id)
        ->first();

    // Students
    $students = $this->sectionStudentsQuery($section)
        ->orderBy('last_name')
        ->orderBy('first_name')
        ->get();
    $studentCount = $students->count();

    // Lessons count
    $lessonCount = LessonModel::where('section_model_id', $section_model_id)
        ->where('subject_model_id', $subject_model_id)
        ->count();

    // Classwork counts
    $activityCount = Activity::where('section_subject_id', $schedule->id ?? 0)->count();

    $quizCount = Quiz::where('section_model_id', $section_model_id)
        ->where('subject_model_id', $subject_model_id)
        ->count();

    $evaluationCount = Evaluation::where('section_subject_id', $schedule->id ?? 0)->count();

    $classworkCount = $activityCount + $quizCount + $evaluationCount;

    // Announcements
    $announcements = AnnouncementModel::with(['teacher', 'replies.user'])
        ->where('section_subject_id', $schedule->id ?? 0)
        ->latest()
        ->get();

    return view('pages.teacher.pages.sectionsubject', [
        'title' => $subject->subject_name . ' - ' . $section->section_name,
        'preloader' => false,
        'darkmode' => true,
        'scrollup' => true,
        'banner' => true,
        'sidebar' => true,

        'section' => $section,
        'subject' => $subject,
        'assignment' => $assignment,
        'schedule' => $schedule,

        // ✅ DYNAMIC DATA
        'students' => $students,
        'studentCount' => $studentCount,
        'lessonCount' => $lessonCount,
        'classworkCount' => $classworkCount,

        'announcements' => $announcements,
    ]);
}




    public function showStudents(Request $request, $section_model_id, $subject_model_id)
{
    $section = SectionModel::findOrFail($section_model_id);
    $subject = SubjectModel::findOrFail($subject_model_id);

    /* ===============================
       INPUTS
    =============================== */
    $search = trim($request->query('search', ''));

    /* ===============================
       STUDENTS QUERY
    =============================== */
    $studentsQuery = $this->sectionStudentsQuery($section);

    if ($search !== '') {
        $studentsQuery->where(function ($q) use ($search) {
            $q->where('first_name', 'like', "%{$search}%")
              ->orWhere('last_name', 'like', "%{$search}%");
        });
    }

    $students = $studentsQuery
        ->orderBy('last_name')
        ->paginate(10)
        ->withQueryString();

    /* ===============================
       AJAX RESPONSE (LIVE SEARCH)
    =============================== */
    if ($request->ajax()) {
        return view('pages.teacher.partials.student-list', [
            'students' => $students
        ]);
    }

    /* ===============================
       NORMAL PAGE LOAD
    =============================== */
    return view('pages.teacher.pages.subjectstudents', [
        'section' => $section,
        'subject' => $subject,
        'students' => $students,
        'search' => $search,

        // UI
        'preloader' => false,
        'darkmode' => true,
        'scrollup' => true,
        'banner' => true,
        'sidebar' => true,
    ]);
}


    public function updateCover(Request $request, $sectionId, $subjectId)
{
    $request->validate([
        'coverImage' => 'required|image|max:2048', // 2MB
    ]);

    // Get pivot row
    $pivot = DB::table('section_subject')
        ->where('section_model_id', $sectionId)
        ->where('subject_model_id', $subjectId)
        ->first();

    if (!$pivot) {
        return back()->with('error', 'Assignment not found.');
    }

    /* =========================================
     | Store image in storage/app/public
     ========================================= */

    $path = $request->file('coverImage')->store(
        'class-covers',
        'public'
    );

    /* =========================================
     | Optional: delete old cover image
     ========================================= */
    if (!empty($pivot->coverImage) && Storage::disk('public')->exists($pivot->coverImage)) {
        Storage::disk('public')->delete($pivot->coverImage);
    }

    /* =========================================
     | Update pivot
     ========================================= */
    DB::table('section_subject')
        ->where('section_model_id', $sectionId)
        ->where('subject_model_id', $subjectId)
        ->update([
            'coverImage' => $path, // class-covers/filename.jpg
            'updated_at' => now(),
        ]);

    return back()->with('success', 'Cover image updated!');
}
    public function showLessons($section_id, $subject_id)
{
    $section = SectionModel::findOrFail($section_id);
    $subject = SubjectModel::findOrFail($subject_id);

    $assignment = $section->assignedTeachers()
        ->where('subject_model_id', $subject_id)
        ->first();

    // Fetch lessons sorted by grading period and then by custom position
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
        ->orderBy('position', 'asc') // 👈 THIS is the important part
        ->get();

    $gradingPeriods = $this->lessonGradingPeriods();

    $selectedGradingPeriod = (string) request()->query('grading_period', '1st');

    if (!array_key_exists($selectedGradingPeriod, $gradingPeriods)) {
        $selectedGradingPeriod = '1st';
    }

    return view('pages.teacher.pages.lesson', [
        'title' => $subject->subject_name . ' - ' . $section->section_name . ' Lessons',
        'preloader' => false,
        'darkmode' => true,
        'scrollup' => true,
        'banner' => true,
        'sidebar' => true,
        'section' => $section,
        'subject' => $subject,
        'assignment' => $assignment,
        'lessons' => $lessons,
        'selectedGradingPeriod' => $selectedGradingPeriod,
        'gradingPeriods' => $gradingPeriods,
        'isAuditOnly' => $this->isAuditOnlySection($section),
    ]);
}



public function reorder(Request $request)
{
    $request->validate([
        'positions' => 'required|array',
        'positions.*' => 'integer|min:1'
    ]);

    foreach ($request->positions as $lessonId => $position) {
        LessonModel::where('id', $lessonId)
            ->update(['position' => $position]);
    }

    return back()->with('success', 'Lesson order updated!');
}

    public function createLesson(Request $request, $section_id, $subject_id)
    {
        $section = SectionModel::findOrFail($section_id);
        $subject = SubjectModel::findOrFail($subject_id);
        $gradingPeriods = $this->lessonGradingPeriods();

        $selectedGradingPeriod = (string) $request->query('grading_period', '1st');
        if (!array_key_exists($selectedGradingPeriod, $gradingPeriods)) {
            $selectedGradingPeriod = '1st';
        }

        $tags = Tag::query()
            ->orderBy('name')
            ->get(['id', 'name', 'category']);

        return view('pages.teacher.pages.lesson-detail', [
            'title' => $subject->subject_name . ' - ' . $section->section_name . ' Lessons',
            'preloader' => false,
            'darkmode' => true,
            'scrollup' => true,
            'banner' => false,
            'sidebar' => true,
            'section' => $section,
            'subject' => $subject,
            'lesson' => null,
            'isCreate' => true,
            'gradingPeriods' => $gradingPeriods,
            'selectedGradingPeriod' => $selectedGradingPeriod,
            'tags' => $tags,
            'assignedTagIds' => [],
        ]);
    }

    public function viewLesson(Request $request, $section_id, $subject_id, $lesson_id)
    {
        $section = SectionModel::findOrFail($section_id);
        $subject = SubjectModel::findOrFail($subject_id);
        $lesson = LessonModel::with('tags')
            ->where('id', $lesson_id)
            ->where('section_model_id', $section_id)
            ->where('subject_model_id', $subject_id)
            ->firstOrFail();

        $gradingPeriods = $this->lessonGradingPeriods();
        $selectedGradingPeriod = (string) $request->query('grading_period', ($lesson->grading_period ?: '1st'));
        if (!array_key_exists($selectedGradingPeriod, $gradingPeriods)) {
            $selectedGradingPeriod = $lesson->grading_period ?: '1st';
        }

        $tags = Tag::query()
            ->orderBy('name')
            ->get(['id', 'name', 'category']);

        return view('pages.teacher.pages.lesson-detail', [
            'title' => $subject->subject_name . ' - ' . $section->section_name . ' Lessons',
            'preloader' => false,
            'darkmode' => true,
            'scrollup' => true,
            'banner' => false,
            'sidebar' => true,
            'section' => $section,
            'subject' => $subject,
            'lesson' => $lesson,
            'isCreate' => false,
            'gradingPeriods' => $gradingPeriods,
            'selectedGradingPeriod' => $selectedGradingPeriod,
            'tags' => $tags,
            'assignedTagIds' => $lesson->tags->pluck('id')->all(),
        ]);
    }

    public function uploadLesson(Request $request, LessonModel $lesson)
    {
        $request->validate([
            'attachments' => 'required|array|min:1',
            'attachments.*' => 'file|max:204800',
        ]);

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $lesson->addMedia($file)
                    ->toMediaCollection('attachments');
            }
        }

        return redirect()->back()->with('success', 'Files uploaded successfully!');
    }

    public function storeLesson(Request $request, $section, $subject)
    {
        SectionModel::findOrFail($section);
        SubjectModel::findOrFail($subject);

        $validated = $this->validateLessonRequest($request);
        $tagIds = array_values(array_unique(array_map('intval', $validated['tag_ids'] ?? [])));
        $tagSyncPayload = $this->buildTagSyncPayload(
            $tagIds,
            (array) $request->input('tag_relevance', []),
            (array) $request->input('tag_coverage', [])
        );

        // Get the next position (scoped per section + subject + grading period)
        $lastPosition = LessonModel::where('section_model_id', (int) $section)
            ->where('subject_model_id', (int) $subject)
            ->where('grading_period', $validated['grading_period'])
            ->max('position');

        $nextPosition = $lastPosition ? $lastPosition + 1 : 1;

        $lesson = LessonModel::create([
            'lesson_title' => $validated['lesson_title'],
            'description' => $validated['description'] ?? null,
            'grading_period' => $validated['grading_period'],
            'section_model_id' => (int) $section,
            'subject_model_id' => (int) $subject,
            'visible' => array_key_exists('visible', $validated)
                ? (bool) $validated['visible']
                : true,
            'open_date' => null,
            'open_time' => null,
            'position' => $nextPosition, // 👈 ordering column
        ]);
        $lesson->tags()->sync($tagSyncPayload);

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $lesson->addMedia($file)->toMediaCollection('attachments');
            }
        }

        return redirect()->route('teacher.lesson.show', [
            'section_id' => $section,
            'subject_id' => $subject,
            'lesson_id' => $lesson->id,
            'grading_period' => $lesson->grading_period,
        ])->with('success', 'Lesson created!');
    }

    public function updateLesson(Request $request, $section, $subject, $lesson_id)
    {
        SectionModel::findOrFail($section);
        SubjectModel::findOrFail($subject);

        $lesson = LessonModel::where('id', $lesson_id)
            ->where('section_model_id', $section)
            ->where('subject_model_id', $subject)
            ->firstOrFail();

        $validated = $this->validateLessonRequest($request);
        $tagIds = array_values(array_unique(array_map('intval', $validated['tag_ids'] ?? [])));
        $tagSyncPayload = $this->buildTagSyncPayload(
            $tagIds,
            (array) $request->input('tag_relevance', []),
            (array) $request->input('tag_coverage', [])
        );

        $lesson->update([
            'lesson_title' => $validated['lesson_title'],
            'description' => $validated['description'] ?? null,
            'grading_period' => $validated['grading_period'],
            'visible' => array_key_exists('visible', $validated)
                ? (bool) $validated['visible']
                : true,
            'open_date' => null,
            'open_time' => null,
        ]);
        $lesson->tags()->sync($tagSyncPayload);

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $lesson->addMedia($file)->toMediaCollection('attachments');
            }
        }

        return redirect()->route('teacher.lesson.show', [
            'section_id' => $section,
            'subject_id' => $subject,
            'lesson_id' => $lesson->id,
            'grading_period' => $lesson->grading_period,
        ])->with('success', 'Lesson updated successfully!');
    }

    public function removeLessonFile($lesson_id, $media_id)
    {
        $lesson = LessonModel::findOrFail($lesson_id);
        $media = Media::query()
            ->where('id', $media_id)
            ->where('model_type', LessonModel::class)
            ->where('model_id', $lesson->id)
            ->firstOrFail();

        $media->delete();

        return back()->with('success', 'File removed successfully.');
    }

    public function deleteLesson($lesson_id)
    {
        $lesson = LessonModel::findOrFail($lesson_id);
        $sectionId = $lesson->section_model_id;
        $subjectId = $lesson->subject_model_id;

        // delete attachments (if using Spatie)
        if ($lesson->hasMedia('attachments')) {
            $lesson->clearMediaCollection('attachments');
        }
        $lesson->tags()->detach();

        $lesson->delete();

        return redirect()->route('teacher.lesson', [
            'section_id' => $sectionId,
            'subject_id' => $subjectId,
            'grading_period' => request()->query('grading_period', '1st'),
        ])->with('success', 'Lesson deleted successfully!');
    }

    private function lessonGradingPeriods(): array
    {
        return [
            '1st' => '1st Quarter',
            '2nd' => '2nd Quarter',
            '3rd' => '3rd Quarter',
            '4th' => '4th Quarter',
        ];
    }

    private function validateLessonRequest(Request $request): array
    {
        return $request->validate([
            'lesson_title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'grading_period' => 'required|in:1st,2nd,3rd,4th',
            'visible' => 'nullable|boolean',
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'integer|exists:tags,id',
            'tag_relevance' => 'nullable|array',
            'tag_relevance.*' => 'nullable|string',
            'tag_coverage' => 'nullable|array',
            'tag_coverage.*' => 'nullable',
            'attachments.*' => 'nullable|file|max:204800',
        ]);
    }
    public function showQuestions($section_id, $subject_id)
    {
        return redirect()->route('teacher.questions', [
            'subject_id' => $subject_id,
        ]);
    }
    public function attendance($section_id, $subject_id)
{
    $section = SectionModel::findOrFail($section_id);
    $subject = SubjectModel::findOrFail($subject_id);

    $sectionSubject = DB::table('section_subject')
        ->where('section_model_id', $section_id)
        ->where('subject_model_id', $subject_id)
        ->firstOrFail();

    $gradingPeriods = [
        '1st' => '1st Quarter',
        '2nd' => '2nd Quarter',
        '3rd' => '3rd Quarter',
        '4th' => '4th Quarter',
    ];

    $attendance = Attendance::where('section_subject_id', $sectionSubject->id)
        ->orderBy('id', 'asc')
        ->get();

    return view('pages.teacher.pages.attendance', [
        'title' => $subject->subject_name . ' - ' . $section->section_name . ' Attendance',
        'section' => $section,
        'subject' => $subject,
        'gradingPeriods' => $gradingPeriods,
        'groupedAttendance' => $attendance->groupBy('grading_period'),
        'existingAttendanceDates' => $attendance->pluck('date')->toArray(),
        'isAuditOnly' => $this->isAuditOnlySection($section),
        'preloader' => false,
        'darkmode'  => true,
        'scrollup'  => true,
        'banner'    => true,
        'sidebar'   => true,
    ]);
}


    public function attendanceScore(Request $request, $section_id, $subject_id)
{
    $gradingPeriod = $request->query('grading_period', '1st');

    $section = SectionModel::findOrFail($section_id);
    $subject = SubjectModel::findOrFail($subject_id);

    $sectionSubject = DB::table('section_subject')
        ->where('section_model_id', $section_id)
        ->where('subject_model_id', $subject_id)
        ->firstOrFail();

    $attendances = Attendance::with('records.student')
        ->where('section_subject_id', $sectionSubject->id)
        ->where('grading_period', $gradingPeriod)
        ->get();

    $studentStats = [];

    foreach ($attendances as $attendance) {
        foreach ($attendance->records as $record) {

            $studentId = $record->student_id;

            if (!isset($studentStats[$studentId])) {
                $studentStats[$studentId] = [
                    'student_id' => $studentId,
                    'name' => $record->student->last_name . ', ' . $record->student->first_name,
                    'present' => 0,
                    'late' => 0,
                    'excused' => 0,
                    'absent' => 0,
                    'earned' => 0,
                    'total' => 0,
                ];
            }

            if (isset($studentStats[$studentId][$record->status])) {
                $studentStats[$studentId][$record->status]++;
            }
        }
    }

    // Compute earned/total dynamically
    foreach ($studentStats as &$s) {

        $total =
            $s['present'] +
            $s['late'] +
            $s['excused'] +
            $s['absent'];

        $earned =
            ($s['present'] * 1) +
            ($s['late'] * 0.5) +
            ($s['excused'] * 1);

        $s['earned'] = round($earned, 2);
        $s['total']  = $total;
    }

    $classworkTypes = SubjectGrading::where('subject_id', $subject->id)->get();

    $selectedAttendanceType = AttendanceType::where('section_subject_id', $sectionSubject->id)
        ->where('grading_period', $gradingPeriod)
        ->first();

    return view('pages.teacher.pages.attendancescores', [
        'section' => $section,
        'subject' => $subject,
        'gradingPeriod' => $gradingPeriod,
        'classworkTypes' => $classworkTypes,
        'selectedAttendanceType' => $selectedAttendanceType,
        'studentStats' => $studentStats,
        'isAuditOnly' => $this->isAuditOnlySection($section),
        'preloader' => false,
        'darkmode' => true,
        'scrollup' => true,
        'banner' => true,
        'sidebar' => true,
    ]);
}


    public function storeAttendanceConfiguration(Request $request, $section_id, $subject_id)
{
    $request->validate([
        'grading_period' => 'required|in:1st,2nd,3rd,4th',
        'subject_grading_id' => 'required|exists:subject_grading,id',
    ]);

    $gradingPeriod = $request->grading_period;

    $sectionSubject = DB::table('section_subject')
        ->where('section_model_id', $section_id)
        ->where('subject_model_id', $subject_id)
        ->firstOrFail();

    // Only save which grading group attendance belongs to
    AttendanceType::updateOrCreate(
        [
            'section_subject_id' => $sectionSubject->id,
            'grading_period' => $gradingPeriod,
        ],
        [
            'subject_grading_id' => $request->subject_grading_id,
        ]
    );

    return back()->with('success', 'Attendance group saved successfully.');
}




    public function storeAttendance(Request $request, $section_id, $subject_id)
{
    $sectionSubject = DB::table('section_subject')
        ->where('section_model_id', $section_id)
        ->where('subject_model_id', $subject_id)
        ->firstOrFail();

    $request->validate([
        'date' => [
            'required',
            'date',
            function ($attribute, $value, $fail) use ($sectionSubject) {
                $exists = Attendance::where('section_subject_id', $sectionSubject->id)
                    ->whereDate('date', $value)
                    ->exists();

                if ($exists) {
                    $fail('Attendance for this date already exists.');
                }
            }
        ],
        'grading_period' => 'required|in:1st,2nd,3rd,4th',
    ]);

    Attendance::create([
        'section_subject_id' => $sectionSubject->id,
        'day' => 'Day ' . (Attendance::where('section_subject_id', $sectionSubject->id)->count() + 1),
        'date' => $request->date,
        'grading_period' => $request->grading_period,
    ]);

    return back()->with('success', 'Attendance added successfully!');
}

    public function attendanceRecord($attendance_id)
    {
        $attendance = Attendance::with(['records.student', 'sectionSubject.section'])->findOrFail($attendance_id);

        // Sort students by last name
        $students = $this->sectionStudentsQuery($attendance->sectionSubject->section)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        $section = $attendance->sectionSubject->section;
        $subject = $attendance->sectionSubject->subject; // make sure you have a relation

        return view('pages.teacher.pages.attendancerecord', [
            'attendance' => $attendance,
            'students' => $students,
            'section' => $section,
            'subject' => $subject,
            'isAuditOnly' => $this->isAuditOnlySection($section),
            'preloader' => false,
            'darkmode' => true,
            'scrollup' => true,
            'banner' => true,
            'sidebar' => true,
        ]);
    }




    public function saveAttendanceStatus(Request $request, Attendance $attendance)
    {
        $request->validate([
            'statuses' => 'required|array',
            'statuses.*' => 'required|in:not marked,present,absent,late,excused',
        ]);

        foreach ($request->statuses as $studentId => $status) {
            AttendanceRecord::updateOrCreate(
                [
                    'attendance_id' => $attendance->id,
                    'student_id' => $studentId,
                ],
                ['status' => $status]
            );
        }

        return back()->with('success', 'Attendance saved successfully!');
    }

    public function classwork($section_id, $subject_id)
{
    $section = SectionModel::findOrFail($section_id);
    $subject = SubjectModel::findOrFail($subject_id);

    $sectionSubject = SectionSubject::where('section_model_id', $section_id)
        ->where('subject_model_id', $subject_id)
        ->firstOrFail();

    /* ===============================
       TOTAL STUDENTS
    =============================== */
    $totalStudents = $this->sectionStudentCount($section);

    /* ===============================
       ACTIVITIES (NOW USING POSITION)
    =============================== */
    $activities = $sectionSubject->activities()
        ->withCount([
            'submissions as completed_count' => function ($q) {
                $q->where('is_latest', 1)
                  ->distinct('student_id');
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
        ->orderBy('position', 'asc') // ✅ CHANGED
        ->get()
        ->groupBy('grading_period');

    /* ===============================
       QUIZZES (NOW USING POSITION)
    =============================== */
    $quizzes = Quiz::withCount([
            'responses as completed_count' => function ($q) {
                $q->distinct('user_id');
            },
            'questions',
        ])
        ->where('section_model_id', $section->id)
        ->where('subject_model_id', $subject->id)
        ->orderByRaw("
            CASE grading_period
                WHEN '1st' THEN 1
                WHEN '2nd' THEN 2
                WHEN '3rd' THEN 3
                WHEN '4th' THEN 4
            END
        ")
        ->orderBy('position', 'asc') // ✅ CHANGED
        ->get();

    /* ===============================
       EVALUATIONS (NOW USING POSITION)
    =============================== */
    $evaluations = Evaluation::withCount([
            'scores as completed_count' => function ($q) {
                $q->distinct('student_id');
            }
        ])
        ->where('section_subject_id', $sectionSubject->id)
        ->orderByRaw("
            CASE grading_period
                WHEN '1st' THEN 1
                WHEN '2nd' THEN 2
                WHEN '3rd' THEN 3
                WHEN '4th' THEN 4
            END
        ")
        ->orderBy('position', 'asc') // ✅ CHANGED
        ->get();

    $classworkTypes = SubjectGrading::where('subject_id', $subject->id)->get();

    return view('pages.teacher.pages.classwork', [
        'title' => $subject->subject_name . ' - ' . $section->section_name . ' Classwork',
        'preloader' => false,
        'darkmode' => true,
        'scrollup' => true,
        'banner' => true,
        'sidebar' => true,

        'section' => $section,
        'subject' => $subject,
        'sectionSubject' => $sectionSubject,

        'activities' => $activities,
        'quizzes' => $quizzes,
        'evaluations' => $evaluations,
        'classworkTypes' => $classworkTypes,

        'totalStudents' => $totalStudents,
        'isAuditOnly' => $this->isAuditOnlySection($section),
    ]);
}
public function reorderClasswork(Request $request)
{
    $request->validate([
        'positions' => 'required|array',
        'grading_period' => 'required|in:1st,2nd,3rd,4th',
    ]);

    $positions = $request->positions;

    foreach ($positions as $key => $position) {

        // Skip invalid values
        if (!is_numeric($position) || $position < 1) {
            continue;
        }

        // Expecting format: type_id (ex: activity_5)
        if (!str_contains($key, '_')) {
            continue;
        }

        [$type, $id] = explode('_', $key);

        switch ($type) {
            case 'activity':
                \App\Models\Activity::where('id', $id)
                    ->where('grading_period', $request->grading_period)
                    ->update(['position' => $position]);
                break;

            case 'quiz':
                \App\Models\Quiz::where('id', $id)
                    ->where('grading_period', $request->grading_period)
                    ->update(['position' => $position]);
                break;

            case 'evaluation':
                \App\Models\Evaluation::where('id', $id)
                    ->where('grading_period', $request->grading_period)
                    ->update(['position' => $position]);
                break;
        }
    }

    return back()->with('success', 'Classwork order updated successfully!');
}

    public function storeActivity(Request $request)
{
    $request->validate([
        'section_subject_id' => 'required|exists:section_subject,id',
        'title' => 'required|string|max:255',
        'grading_period' => 'required|in:1st,2nd,3rd,4th',
        'classwork_type' => 'required|exists:subject_grading,id',
    ]);

    // Get next position within same section_subject + grading period
    $maxPosition = Activity::where('section_subject_id', $request->section_subject_id)
        ->where('grading_period', $request->grading_period)
        ->max('position');

    Activity::create([
        'section_subject_id' => $request->section_subject_id,

        'title' => $request->title,
        'description' => null,

        'time_posted' => now(),
        'due_date' => null,

        'open_date' => null,
        'open_time' => null,

        'visible' => 0,
        'accepting' => 1,

        'accept_files' => 0,
        'accept_text' => 0,

        'max_number_files' => 1,
        'max_attempt' => 1,
        'total_score' => 0,

        'grading_period' => $request->grading_period,

        'classwork_type' => $request->classwork_type,

        // 🔥 NEW
        'position' => ($maxPosition ?? 0) + 1,

        'max_words' => null,
        'min_words' => null,
        'max_file_size' => null,
    ]);

    return back()->with('success', 'Activity created successfully!');
}

    public function viewSelected($section_id, $subject_id, $activity_id)
    {
        // Load section + subject
        $section = SectionModel::findOrFail($section_id);
        $subject = SubjectModel::findOrFail($subject_id);

        // Load the section_subject pivot entry
        $sectionSubject = \App\Models\SectionSubject::where('section_model_id', $section_id)
            ->where('subject_model_id', $subject_id)
            ->firstOrFail();

        // Load the specific activity
        $activity = \App\Models\Activity::where('id', $activity_id)
            ->where('section_subject_id', $sectionSubject->id)
            ->firstOrFail();

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
        $assignedTags = $activity->tags()
            ->orderBy('name')
            ->get(['tags.id', 'tags.name', 'tags.category']);
        $assignedTagIds = $assignedTags->pluck('id')->all();

        return view('pages.teacher.pages.viewselected', [
            'title' => $subject->subject_name . ' - ' . $section->section_name . ' Classwork',
            'preloader' => false,
            'darkmode' => true,
            'scrollup' => true,
            'banner' => false,
            'sidebar' => true,

            'section' => $section,
            'subject' => $subject,
            'sectionSubject' => $sectionSubject,
            'activity' => $activity,
            'tags' => $tags,
            'tagCategories' => $tagCategories,
            'assignedTags' => $assignedTags,
            'assignedTagIds' => $assignedTagIds,
            'isAuditOnly' => $this->isAuditOnlySection($section),
        ]);
    }
//     public function updateActivity(Request $request, $activity_id)
// {
//     return response()->json([
//         'all' => $request->all(),
//         'files_raw' => $request->files->all(),
//         'has_attachments' => $request->hasFile('attachments'),
//         'attachments' => $request->file('attachments'),
//     ]);
//     $activity = Activity::findOrFail($activity_id);

//     $visible = $request->boolean('visible');
//     $isOpen = $visible ? ($request->is_open ?? 0) : 0;

//     $activity->update([
//         'title' => $request->title,
//         'description' => $request->description,

//         'open_date' => $request->open_date ?: null,
//         'open_time' => $request->open_time ?: null,

//         'due_date' => ($request->due_date && $request->due_time)
//             ? $request->due_date . ' ' . $request->due_time
//             : null,

//         'visible' => $visible,
//         'is_open' => $isOpen,

//         'accept_files' => $request->allow_files ?? 0,
//         'max_number_files' => $request->max_files ?: 1,
//         'max_file_size' => $request->max_size,

//         'accept_text' => $request->allow_text ?? 0,
//         'min_words' => $request->min_words,
//         'max_words' => $request->max_words,

//         'total_score' => $request->total_score,
//         'accepting' => $request->accepting,
//         'max_attempt' => $request->max_attempt,
//     ]);

//     if ($request->hasFile('attachments')) {
//     foreach ($request->file('attachments') as $file) {
//         $activity
//             ->addMedia($file)
//             ->toMediaCollection('attachments');
//     }
// }


//     return response()->json([
//         'status' => 'success',
//         'message' => 'Activity updated successfully'
//     ]);
// }
public function updateActivity(Request $request, $activity_id)
{
    $activity = Activity::findOrFail($activity_id);
    $validatedTags = $request->validate([
        'tag_ids' => 'nullable|array',
        'tag_ids.*' => 'integer|exists:tags,id',
        'tag_relevance' => 'nullable|array',
        'tag_relevance.*' => 'nullable|string',
        'tag_coverage' => 'nullable|array',
        'tag_coverage.*' => 'nullable',
    ]);

    $visible = $request->boolean('visible');
    $isOpen  = $visible ? ($request->is_open ?? 0) : 0;

    $activity->update([
        'title' => $request->title,
        'description' => $request->description,

        'open_date' => $request->open_date ?: null,
        'open_time' => $request->open_time ?: null,

        'due_date' => ($request->due_date && $request->due_time)
            ? $request->due_date . ' ' . $request->due_time
            : null,

        'visible' => $visible,
        'is_open' => $isOpen,

        'accept_files' => $request->allow_files ?? 0,
        'max_number_files' => $request->max_files ?: 1,
        'max_file_size' => $request->max_size,

        'accept_text' => $request->allow_text ?? 0,
        'min_words' => $request->min_words,
        'max_words' => $request->max_words,

        'total_score' => $request->total_score,
        'accepting' => $request->accepting,
        'max_attempt' => $request->max_attempt,
    ]);

    // ✅ SAVE MULTIPLE FILES (APPEND, NOT REPLACE)
    if ($request->hasFile('attachments')) {
        foreach ($request->file('attachments') as $file) {
            $activity
                ->addMedia($file)
                ->toMediaCollection('attachments');
        }
    }
    $tagIds = array_values(array_unique(array_map('intval', $validatedTags['tag_ids'] ?? [])));
    $tagSyncPayload = $this->buildTagSyncPayload(
        $tagIds,
        (array) $request->input('tag_relevance', []),
        (array) $request->input('tag_coverage', [])
    );
    $activity->tags()->sync($tagSyncPayload);

    return response()->json([
    'success' => true,
    'message' => 'Activity updated successfully',
]);

}



    public function updateActivityTags(Request $request, $section_id, $subject_id, Activity $activity)
    {
        $validated = $request->validate([
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'integer|exists:tags,id',
            'tag_relevance' => 'nullable|array',
            'tag_relevance.*' => 'nullable|string',
            'tag_coverage' => 'nullable|array',
            'tag_coverage.*' => 'nullable',
        ]);

        $tagIds = array_values(array_unique(array_map('intval', $validated['tag_ids'] ?? [])));
        $tagSyncPayload = $this->buildTagSyncPayload(
            $tagIds,
            (array) $request->input('tag_relevance', []),
            (array) $request->input('tag_coverage', [])
        );
        $activity->tags()->sync($tagSyncPayload);

        return response()->json([
            'success' => true,
            'tag_ids' => $tagIds,
        ]);
    }




    public function removeActivityFile($mediaId)
    {
        $media = Media::findOrFail($mediaId);

        // Optional: Check that the authenticated teacher owns the activity
        $activity = $media->model;
        // if ($activity->teacher_id !== auth()->id()) abort(403);

        $media->delete();

        return back()->with('success', 'File removed successfully.');
    }

    public function destroyActivity($id)
    {
        $activity = Activity::findOrFail($id);

        // Get section + subject before deleting
        $sectionId = $activity->sectionSubject->section_model_id;
        $subjectId = $activity->sectionSubject->subject_model_id;

        // Delete attachments
        $activity->clearMediaCollection('attachments');

        // Delete submissions
        if (method_exists($activity, 'submissions')) {
            $activity->submissions()->delete();
        }

        $activity->tags()->detach();
        // Delete the activity itself
        $activity->delete();

        return redirect()->route('teacher.classwork.view', [
            $sectionId,
            $subjectId
        ])->with('success', 'Activity deleted successfully.');
    }
   public function viewSubmissions($section_id, $subject_id, $activity_id)
{
    
    // load section and subject
    $section = SectionModel::findOrFail($section_id);
    $subject = SubjectModel::findOrFail($subject_id);

    // find section_subject pivot
    $sectionSubject = SectionSubject::where('section_model_id', $section_id)
        ->where('subject_model_id', $subject_id)
        ->firstOrFail();

    // ensure teacher
    $teacher = Auth::user()->teacher ?? null;
    if (!$teacher) {
        abort(403, 'Unauthorized: not a teacher.');
    }

    $assigned = SubjectTeacher::where('section_model_id', $section_id)
        ->where('subject_model_id', $subject_id)
        ->where('teacher_model_id', $teacher->id)
        ->exists();

    if (!$assigned) {
        abort(403, 'You are not assigned to this class/subject.');
    }

    // load activity
    $activity = Activity::where('id', $activity_id)
        ->where('section_subject_id', $sectionSubject->id)
        ->firstOrFail();

        

    /*
    |--------------------------------------------------------------------------
    | STEP 1: Get ONLY latest valid submissions
    |--------------------------------------------------------------------------
    */
    $latestSubmissions = ActivitySubmission::latestValid()
        ->where('activity_id', $activity->id)
        ->with(['student', 'essay', 'files'])
        ->orderBy('student_id')
        ->get();

    /*
    |--------------------------------------------------------------------------
    | STEP 2: Attach attempts per student (history)
    |--------------------------------------------------------------------------
    */
    $grouped = $latestSubmissions->keyBy('student_id')->map(function ($latest) use ($activity) {

        $attempts = ActivitySubmission::active()
            ->where('activity_id', $activity->id)
            ->where('student_id', $latest->student_id)
            ->orderByDesc('attempt_number')
            ->get();

        return (object) [
            'student' => $latest->student,
            'latest' => $latest,
            'attempts' => $attempts,
            'attempt_count' => $attempts->count(),
        ];
    });

    $students = $this->sectionStudentsQuery($section)
        ->orderBy('last_name')
        ->orderBy('first_name')
        ->get();

    return view('pages.teacher.pages.activitysubmissions', [
        'section' => $section,
        'subject' => $subject,
        'activity' => $activity,
        'students' => $students,
        'groupedSubmissions' => $grouped,
        'totalSubmissions' => $grouped->count(),
        'isAuditOnly' => $this->isAuditOnlySection($section),

        // UI flags
        'title' => $activity->title . ' - Submissions',
        'preloader' => false,
        'darkmode' => true,
        'scrollup' => true,
        'banner' => false,
        'sidebar' => true,
    ]);
}


    public function gradeSubmission(Request $request, ActivitySubmission $submission)
    {
        // Only teachers may grade
        $teacher = Auth::user()->teacher ?? null;
        if (!$teacher) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        // Validate incoming score
        $request->validate([
            'score' => 'required|numeric|min:0',
        ]);

        // Load related activity & section_subject to authorize teacher
        $activity = $submission->activity()->with('sectionSubject')->first();

        if (!$activity || !$activity->sectionSubject) {
            return response()->json(['message' => 'Activity or section/subject link missing.'], 404);
        }

        $sectionId = $activity->sectionSubject->section_model_id;
        $subjectId = $activity->sectionSubject->subject_model_id;

        // Check that this teacher is assigned to this section+subject
        $assigned = SubjectTeacher::where('section_model_id', $sectionId)
            ->where('subject_model_id', $subjectId)
            ->where('teacher_model_id', $teacher->id)
            ->exists();

        if (!$assigned) {
            return response()->json(['message' => 'Forbidden: you are not assigned to this section/subject.'], 403);
        }

        // Ensure score does not exceed activity total_score (if defined)
        $activityTotal = $activity->total_score ?? null;
        if (!is_null($activityTotal) && $request->score > $activityTotal) {
            return response()->json([
                'message' => "Score cannot exceed activity total of {$activityTotal}.",
                'errors' => ['score' => ["Score cannot exceed {$activityTotal}."]]
            ], 422);
        }

        // Persist the grade
        $submission->score = $request->score;
        $submission->status = 'graded';
        $submission->save();

        return response()->json([
            'message' => 'Score updated.',
            'score' => $submission->score,
        ]);
    }
    public function removeGrade(ActivitySubmission $submission)
    {
        $teacher = Auth::user()->teacher ?? null;
        if (!$teacher) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        // Load activity + pivot to validate teacher ownership
        $activity = $submission->activity()->with('sectionSubject')->first();

        if (!$activity || !$activity->sectionSubject) {
            return response()->json(['message' => 'Activity or section/subject link missing.'], 404);
        }

        $sectionId = $activity->sectionSubject->section_model_id;
        $subjectId = $activity->sectionSubject->subject_model_id;

        $assigned = SubjectTeacher::where('section_model_id', $sectionId)
            ->where('subject_model_id', $subjectId)
            ->where('teacher_model_id', $teacher->id)
            ->exists();

        if (!$assigned) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        // 🔥 Clear grade
        $submission->score = null;
        $submission->status = 'submitted'; // optional, or keep 'graded'
        $submission->save();

        return response()->json([
            'message' => 'Grade removed successfully.'
        ]);
    }

    public function gradePage(Request $request, $section_model_id, $subject_model_id)
    {
        $section = SectionModel::findOrFail($section_model_id);
        $subject = SubjectModel::findOrFail($subject_model_id);

        // Default grading period
        $gradingPeriod = $request->get('grading_period', '1st');

        // Get pivot
        $sectionSubject = SectionSubject::where('section_model_id', $section_model_id)
            ->where('subject_model_id', $subject_model_id)
            ->firstOrFail();

        // Fetch students FIRST
        $students = $this->sectionStudentsQuery($section)
            ->orderBy('last_name')
            ->get();

        // Fetch grades for this section+subject+period
        $grades = Grade::where('section_subject_id', $sectionSubject->id)
            ->where('grading_period', $gradingPeriod)
            ->get()
            ->keyBy('student_id'); // IMPORTANT

        return view('pages.teacher.pages.grade', [
            'title' => $subject->subject_name . ' - ' . $section->section_name,
            'section' => $section,
            'subject' => $subject,
            'students' => $students,
            'grades' => $grades,
            'gradingPeriod' => $gradingPeriod,

            // UI flags
            'preloader' => false,
            'darkmode' => true,
            'scrollup' => true,
            'banner' => true,
            'sidebar' => true,
        ]);
    }



    public function analytics(Request $request, $section_id, $subject_id)
    {
        $section = SectionModel::findOrFail($section_id);
        $subject = SubjectModel::findOrFail($subject_id);
        $sectionSubject = SectionSubject::where('section_model_id', $section_id)
            ->where('subject_model_id', $subject_id)
            ->firstOrFail();

        $classLabelParts = [];

        if (!empty($section->section_rank)) {
            $classLabelParts[] = 'Section ' . $section->section_rank;
        }

        if (!empty($section->section_name)) {
            $classLabelParts[] = $section->section_name;
        }

        if (empty($classLabelParts) && !empty($section->grade_level)) {
            $classLabelParts[] = 'Grade ' . $section->grade_level;
        }

        $classLabel = implode(' - ', $classLabelParts);

        $periodOptions = ['all', '1st', '2nd', '3rd', '4th'];
        $gradingPeriod = (string) $request->query('grading_period', 'all');
        if (!in_array($gradingPeriod, $periodOptions, true)) {
            $gradingPeriod = 'all';
        }

        $topOptions = ['all', '5', '10', '20'];
        $topSelection = (string) $request->query('top', '10');
        if (!in_array($topSelection, $topOptions, true)) {
            $topSelection = '10';
        }

        $studentIds = $this->sectionStudentIds($section);
        $studentUserIds = $this->sectionStudentUserIds($section);

        $analyticsData = (function () use (
            $section_id,
            $subject_id,
            $sectionSubject,
            $periodOptions,
            $studentIds,
            $studentUserIds
        ) {
            $studentCount = count($studentIds);
            $tagAverage = [];
            $classworkTypeAverage = [];
            $tagTypeBreakdown = [];

            $latestQuizResponses = DB::table('responses')
                ->select('quiz_id', 'user_id', DB::raw('MAX(id) as response_id'))
                ->groupBy('quiz_id', 'user_id');

            $evaluationConfig = null;
            $evaluationClass = Evaluation::class;
            $evaluationScoreClass = EvaluationScore::class;
            $schema = DB::getSchemaBuilder();

            if (class_exists($evaluationClass) && class_exists($evaluationScoreClass)) {
                $evaluationTable = (new $evaluationClass())->getTable();
                $evaluationScoreTable = (new $evaluationScoreClass())->getTable();

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
                            'class' => $evaluationClass,
                            'table' => $evaluationTable,
                            'score_table' => $evaluationScoreTable,
                            'scope' => $scope,
                            'has_grading_period' => $schema->hasColumn($evaluationTable, 'grading_period'),
                            'has_classwork_type' => $schema->hasColumn($evaluationTable, 'classwork_type'),
                        ];
                    }
                }
            }

            foreach ($periodOptions as $period) {
                $activityPoints = DB::table('taggables as tg')
                    ->join('activities as a', 'a.id', '=', 'tg.taggable_id')
                    ->join('activity_submissions as s', function ($join) {
                        $join->on('s.activity_id', '=', 'a.id')
                            ->where('s.is_latest', 1)
                            ->where('s.status', 'graded')
                            ->whereNotNull('s.score');
                    })
                    ->where('tg.taggable_type', Activity::class)
                    ->where('a.section_subject_id', $sectionSubject->id)
                    ->whereIn('s.student_id', $studentIds)
                    ->select('tg.tag_id', DB::raw('s.score as points'));

                if ($period !== 'all') {
                    $activityPoints->where('a.grading_period', $period);
                }

                $quizPoints = DB::table('taggables as tg')
                    ->join('quizzes as q', 'q.id', '=', 'tg.taggable_id')
                    ->joinSub($latestQuizResponses, 'latest_responses', function ($join) {
                        $join->on('latest_responses.quiz_id', '=', 'q.id');
                    })
                    ->join('responses as r', 'r.id', '=', 'latest_responses.response_id')
                    ->where('tg.taggable_type', Quiz::class)
                    ->where('q.section_model_id', $section_id)
                    ->where('q.subject_model_id', $subject_id)
                    ->whereIn('latest_responses.user_id', $studentUserIds)
                    ->whereNotNull('r.score')
                    ->select('tg.tag_id', DB::raw('r.score as points'));

                if ($period !== 'all') {
                    $quizPoints->where('q.grading_period', $period);
                }

                $pointsUnion = $activityPoints->unionAll($quizPoints);

                if ($evaluationConfig) {
                    $evaluationPoints = DB::table('taggables as tg')
                        ->join($evaluationConfig['table'] . ' as e', 'e.id', '=', 'tg.taggable_id')
                        ->join($evaluationConfig['score_table'] . ' as es', 'es.evaluation_id', '=', 'e.id')
                        ->where('tg.taggable_type', $evaluationConfig['class'])
                        ->whereIn('es.student_id', $studentIds)
                        ->whereNotNull('es.score');

                    if ($evaluationConfig['scope'] === 'section_subject') {
                        $evaluationPoints->where('e.section_subject_id', $sectionSubject->id);
                    } else {
                        $evaluationPoints->where('e.section_model_id', $section_id)
                            ->where('e.subject_model_id', $subject_id);
                    }

                    if ($evaluationConfig['has_grading_period'] && $period !== 'all') {
                        $evaluationPoints->where('e.grading_period', $period);
                    }

                    $evaluationPoints->select('tg.tag_id', DB::raw('es.score as points'));
                    $pointsUnion->unionAll($evaluationPoints);
                }

                $tagPointTotals = DB::query()
                    ->fromSub($pointsUnion, 'u')
                    ->join('tags as t', 't.id', '=', 'u.tag_id')
                    ->select('t.id', 't.name', DB::raw('SUM(u.points) as total_points'))
                    ->groupBy('t.id', 't.name')
                    ->orderByDesc('total_points')
                    ->get();

                $tagAverage[$period] = [
                    'labels' => $tagPointTotals->pluck('name')->values(),
                    'values' => $studentCount > 0
                        ? $tagPointTotals->map(function ($row) use ($studentCount) {
                            return round(((float) $row->total_points) / $studentCount, 2);
                        })->values()
                        : collect(),
                ];

                $activityTagTypePoints = DB::table('taggables as tg')
                    ->join('activities as a', 'a.id', '=', 'tg.taggable_id')
                    ->join('subject_grading as sg', 'sg.id', '=', 'a.classwork_type')
                    ->join('activity_submissions as s', function ($join) {
                        $join->on('s.activity_id', '=', 'a.id')
                            ->where('s.is_latest', 1)
                            ->where('s.status', 'graded')
                            ->whereNotNull('s.score');
                    })
                    ->where('tg.taggable_type', Activity::class)
                    ->where('a.section_subject_id', $sectionSubject->id)
                    ->whereIn('s.student_id', $studentIds)
                    ->select('tg.tag_id', 'sg.classwork_type as type_label', DB::raw('s.score as points'));

                if ($period !== 'all') {
                    $activityTagTypePoints->where('a.grading_period', $period);
                }

                $quizTagTypePoints = DB::table('taggables as tg')
                    ->join('quizzes as q', 'q.id', '=', 'tg.taggable_id')
                    ->join('subject_grading as sg', 'sg.id', '=', 'q.classwork_type')
                    ->joinSub($latestQuizResponses, 'latest_responses', function ($join) {
                        $join->on('latest_responses.quiz_id', '=', 'q.id');
                    })
                    ->join('responses as r', 'r.id', '=', 'latest_responses.response_id')
                    ->where('tg.taggable_type', Quiz::class)
                    ->where('q.section_model_id', $section_id)
                    ->where('q.subject_model_id', $subject_id)
                    ->whereIn('latest_responses.user_id', $studentUserIds)
                    ->whereNotNull('r.score')
                    ->select('tg.tag_id', 'sg.classwork_type as type_label', DB::raw('r.score as points'));

                if ($period !== 'all') {
                    $quizTagTypePoints->where('q.grading_period', $period);
                }

                $tagTypeUnion = $activityTagTypePoints->unionAll($quizTagTypePoints);

                if ($evaluationConfig && $evaluationConfig['has_classwork_type']) {
                    $evaluationTagTypePoints = DB::table('taggables as tg')
                        ->join($evaluationConfig['table'] . ' as e', 'e.id', '=', 'tg.taggable_id')
                        ->join('subject_grading as sg', 'sg.id', '=', 'e.classwork_type')
                        ->join($evaluationConfig['score_table'] . ' as es', 'es.evaluation_id', '=', 'e.id')
                        ->where('tg.taggable_type', $evaluationConfig['class'])
                        ->whereIn('es.student_id', $studentIds)
                        ->whereNotNull('es.score')
                        ->select('tg.tag_id', 'sg.classwork_type as type_label', DB::raw('es.score as points'));

                    if ($evaluationConfig['scope'] === 'section_subject') {
                        $evaluationTagTypePoints->where('e.section_subject_id', $sectionSubject->id);
                    } else {
                        $evaluationTagTypePoints->where('e.section_model_id', $section_id)
                            ->where('e.subject_model_id', $subject_id);
                    }

                    if ($evaluationConfig['has_grading_period'] && $period !== 'all') {
                        $evaluationTagTypePoints->where('e.grading_period', $period);
                    }

                    $tagTypeUnion->unionAll($evaluationTagTypePoints);
                }

                $tagTypeTotals = DB::query()
                    ->fromSub($tagTypeUnion, 'u')
                    ->join('tags as t', 't.id', '=', 'u.tag_id')
                    ->select('t.name as tag', 'u.type_label as type', DB::raw('SUM(u.points) as total_points'))
                    ->groupBy('t.name', 'u.type_label')
                    ->orderByDesc('total_points')
                    ->get();

                $tagTypeBreakdown[$period] = $tagTypeTotals->map(function ($row) use ($studentCount) {
                    $value = $studentCount > 0 ? ((float) $row->total_points) / $studentCount : 0;
                    return [
                        'tag' => $row->tag,
                        'type' => $row->type,
                        'value' => round($value, 2),
                    ];
                })->values();

                $activityTypePoints = DB::table('activity_submissions as s')
                    ->join('activities as a', 'a.id', '=', 's.activity_id')
                    ->join('subject_grading as sg', 'sg.id', '=', 'a.classwork_type')
                    ->where('a.section_subject_id', $sectionSubject->id)
                    ->whereIn('s.student_id', $studentIds)
                    ->where('s.is_latest', 1)
                    ->where('s.status', 'graded')
                    ->whereNotNull('s.score')
                    ->select('sg.classwork_type as label', DB::raw('s.score as points'));

                if ($period !== 'all') {
                    $activityTypePoints->where('a.grading_period', $period);
                }

                $quizTypePoints = DB::table('quizzes as q')
                    ->join('subject_grading as sg', 'sg.id', '=', 'q.classwork_type')
                    ->joinSub($latestQuizResponses, 'latest_responses', function ($join) {
                        $join->on('latest_responses.quiz_id', '=', 'q.id');
                    })
                    ->join('responses as r', 'r.id', '=', 'latest_responses.response_id')
                    ->where('q.section_model_id', $section_id)
                    ->where('q.subject_model_id', $subject_id)
                    ->whereIn('latest_responses.user_id', $studentUserIds)
                    ->whereNotNull('r.score')
                    ->select('sg.classwork_type as label', DB::raw('r.score as points'));

                if ($period !== 'all') {
                    $quizTypePoints->where('q.grading_period', $period);
                }

                $classworkTypeUnion = $activityTypePoints->unionAll($quizTypePoints);

                if ($evaluationConfig && $evaluationConfig['has_classwork_type']) {
                    $evaluationTypePoints = DB::table($evaluationConfig['table'] . ' as e')
                        ->join('subject_grading as sg', 'sg.id', '=', 'e.classwork_type')
                        ->join($evaluationConfig['score_table'] . ' as es', 'es.evaluation_id', '=', 'e.id')
                        ->whereIn('es.student_id', $studentIds)
                        ->whereNotNull('es.score')
                        ->select('sg.classwork_type as label', DB::raw('es.score as points'));

                    if ($evaluationConfig['scope'] === 'section_subject') {
                        $evaluationTypePoints->where('e.section_subject_id', $sectionSubject->id);
                    } else {
                        $evaluationTypePoints->where('e.section_model_id', $section_id)
                            ->where('e.subject_model_id', $subject_id);
                    }

                    if ($evaluationConfig['has_grading_period'] && $period !== 'all') {
                        $evaluationTypePoints->where('e.grading_period', $period);
                    }

                    $classworkTypeUnion->unionAll($evaluationTypePoints);
                }

                $classworkTypeTotals = DB::query()
                    ->fromSub($classworkTypeUnion, 'u')
                    ->select('u.label', DB::raw('SUM(u.points) as total_points'))
                    ->groupBy('u.label')
                    ->orderByDesc('total_points')
                    ->get();

                $classworkTypeAverage[$period] = [
                    'labels' => $classworkTypeTotals->pluck('label')->values(),
                    'values' => $studentCount > 0
                        ? $classworkTypeTotals->map(function ($row) use ($studentCount) {
                            return round(((float) $row->total_points) / $studentCount, 2);
                        })->values()
                        : collect(),
                ];
            }

            $tagAverage = collect($tagAverage)->map(function ($value) {
                return [
                    'labels' => $value['labels']->values(),
                    'values' => $value['values'] instanceof \Illuminate\Support\Collection
                        ? $value['values']->values()
                        : collect($value['values'])->values(),
                ];
            })->toArray();

            $classworkTypeAverage = collect($classworkTypeAverage)->map(function ($value) {
                return [
                    'labels' => $value['labels']->values(),
                    'values' => $value['values'] instanceof \Illuminate\Support\Collection
                        ? $value['values']->values()
                        : collect($value['values'])->values(),
                ];
            })->toArray();

            return [
                'tagAverage' => $tagAverage,
                'classworkTypeAverage' => $classworkTypeAverage,
                'tagTypeBreakdown' => $tagTypeBreakdown,
                'studentCount' => $studentCount,
            ];
        })();

        return response()
            ->view('pages.teacher.pages.analytics', [
                'title' => 'Class: ' . $classLabel . ' - ' . $subject->subject_name,
                'section' => $section,
                'subject' => $subject,
                'classLabel' => $classLabel,
                'analyticsData' => $analyticsData,
                'gradingPeriod' => $gradingPeriod,
                'topSelection' => $topSelection,
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

    public function seedAnalyticsData($section_id, $subject_id)
    {
        $section = SectionModel::findOrFail($section_id);
        $subject = SubjectModel::findOrFail($subject_id);

        $seeder = new AnalyticsDemoSeeder();
        $seeder->runForSectionSubject($section, $subject);

        return back()->with('success', 'Analytics test data generated.');
    }

    public function clearAnalyticsData($section_id, $subject_id)
    {
        $section = SectionModel::findOrFail($section_id);
        $subject = SubjectModel::findOrFail($subject_id);

        $seeder = new AnalyticsDemoSeeder();
        $seeder->clearForSectionSubject($section, $subject);

        return back()->with('success', 'Analytics test data cleared.');
    }

    public function gradeSetup($section_id, $subject_id)
    {
        $section = SectionModel::findOrFail($section_id);
        $subject = SubjectModel::findOrFail($subject_id);

        $sectionSubject = SectionSubject::where('section_model_id', $section_id)
            ->where('subject_model_id', $subject_id)
            ->firstOrFail();

        /* ===============================
        GRADING PERIOD
        =============================== */
        $gradingPeriod = request('grading_period', '1st');

        /* ===============================
        SUBJECT GRADING GROUPS
        =============================== */
        $classworkTypes = SubjectGrading::where('subject_id', $subject_id)->get();

        /* ===============================
        ACTIVITIES (THIS PERIOD ONLY)
        =============================== */
        $activities = Activity::where('section_subject_id', $sectionSubject->id)
            ->where('grading_period', $gradingPeriod)
            ->get();

        $activitiesByType = $activities->groupBy('classwork_type');

        /* ===============================
        QUIZZES (THIS PERIOD ONLY)
        =============================== */
        $quizzes = Quiz::where('section_model_id', $section->id)
            ->where('subject_model_id', $subject->id)
            ->where('grading_period', $gradingPeriod)
            ->get();

        $quizzesByType = $quizzes->groupBy('classwork_type');

        /* ===============================
        EVALUATIONS (THIS PERIOD ONLY)
        =============================== */
        $evaluations = Evaluation::where('section_subject_id', $sectionSubject->id)
            ->where('grading_period', $gradingPeriod)
            ->get();

        $evaluationsByType = $evaluations->groupBy('classwork_type');

        /* ===============================
        ATTENDANCE TYPE (GROUP)
        =============================== */
        $attendanceType = AttendanceType::where('section_subject_id', $sectionSubject->id)
            ->where('grading_period', $gradingPeriod)
            ->first();

        $attendanceGroupId = $attendanceType?->subject_grading_id;


        /* ===============================
        CAN COMPUTE FLAG
        =============================== */
        $canCompute = true;

        foreach ($classworkTypes as $type) {

            $activityItems   = $activitiesByType[$type->id] ?? collect();
            $quizItems       = $quizzesByType[$type->id] ?? collect();
            $evaluationItems = $evaluationsByType[$type->id] ?? collect();
            $hasAttendance   = $attendanceGroupId == $type->id;

            // If this group has NO content at all
            if (
                $activityItems->isEmpty() &&
                $quizItems->isEmpty() &&
                $evaluationItems->isEmpty() &&
                !$hasAttendance
            ) {
                $canCompute = false;
                break;
            }
        }

        /* ===============================
        RETURN VIEW
        =============================== */
        return view('pages.teacher.pages.gradesetup', [
            'section'            => $section,
            'subject'            => $subject,
            'classworkTypes'     => $classworkTypes,
            'activitiesByType'   => $activitiesByType,
            'quizzesByType'      => $quizzesByType,

            // ⭐ add evaluations
            'evaluationsByType' => $evaluationsByType,


            // ⭐ add evaluation grade setups

            // ⭐ Attendance-related
            'attendanceGroupId'  => $attendanceGroupId,

            'canCompute'         => $canCompute,
            'gradingPeriod'      => $gradingPeriod,

            // UI flags
            'preloader' => false,
            'darkmode'  => true,
            'scrollup'  => true,
            'banner'    => true,
            'sidebar'   => true,
        ]);
    }


   public function computeGrades(Request $request, $sectionId, $subjectId)
{
    $gradingPeriod = (string) ($request->grading_period ?? '1st');

    $sectionSubject = SectionSubject::where('section_model_id', $sectionId)
        ->where('subject_model_id', $subjectId)
        ->firstOrFail();

    $section = SectionModel::findOrFail($sectionId);
    $students = $this->sectionStudentsQuery($section)->get();

    $groups = SubjectGrading::where('subject_id', $subjectId)->get();

    if ($groups->sum('percentage') != 100) {
        throw new \Exception("Subject grading percentages must total 100%.");
    }

    /* ===============================
       LOAD CONTENT
    =============================== */
    $activities = Activity::where('section_subject_id', $sectionSubject->id)
        ->where('grading_period', $gradingPeriod)
        ->get()
        ->groupBy('classwork_type');

    $quizzes = Quiz::where('section_model_id', $sectionId)
        ->where('subject_model_id', $subjectId)
        ->where('grading_period', $gradingPeriod)
        ->get()
        ->groupBy('classwork_type');

    $evaluations = Evaluation::where('section_subject_id', $sectionSubject->id)
        ->where('grading_period', $gradingPeriod)
        ->get()
        ->groupBy('classwork_type');

    $attendanceType = AttendanceType::where('section_subject_id', $sectionSubject->id)
        ->where('grading_period', $gradingPeriod)
        ->first();

    $attendanceGroupId = $attendanceType?->subject_grading_id;

    $attendanceScores = AttendanceScore::where('section_subject_id', $sectionSubject->id)
        ->where('grading_period', $gradingPeriod)
        ->get()
        ->keyBy('student_id');

    foreach ($students as $student) {

        $initialGrade = 0;

        foreach ($groups as $group) {

            $groupWeight = (int) $group->percentage;
            $earned = 0;
            $possible = 0;

            $groupActivities  = $activities[$group->id] ?? collect();
            $groupQuizzes     = $quizzes[$group->id] ?? collect();
            $groupEvaluations = $evaluations[$group->id] ?? collect();

            /* ===== ATTENDANCE ===== */
            if ($attendanceGroupId == $group->id) {

                $attendance = $attendanceScores[$student->id] ?? null;

                if ($attendance) {

                    $totalDays =
                        $attendance->present +
                        $attendance->absent +
                        $attendance->excused +
                        $attendance->late;

                    if ($totalDays > 0) {

                        // What counts as earned attendance
                        $earnedDays =
                            $attendance->present +
                            $attendance->excused +
                            $attendance->late; // remove late if it should not count fully

                        $possible += $totalDays;
                        $earned += $earnedDays;
                    }
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

                if ($submission) {
                    $earned += max(0, $submission->score);
                }
            }

            /* ===== QUIZZES ===== */
            foreach ($groupQuizzes as $quiz) {

                if ($quiz->total_score <= 0) continue;

                $possible += $quiz->total_score;

                if (!$student->user_id) continue;

                $response = QuizResponse::where('quiz_id', $quiz->id)
                    ->where('user_id', $student->user_id)
                    ->latest()
                    ->first();

                if ($response && $response->score !== null) {
                    $earned += max(0, $response->score);
                }
            }

            /* ===== EVALUATIONS ===== */
            foreach ($groupEvaluations as $evaluation) {

                if ($evaluation->total_score <= 0) continue;

                $possible += $evaluation->total_score;

                $scoreRow = EvaluationScore::where('evaluation_id', $evaluation->id)
                    ->where('student_id', $student->id)
                    ->first();

                if ($scoreRow) {
                    $earned += max(0, $scoreRow->score);
                }
            }

            if ($possible > 0) {
                $ps = ($earned / $possible) * 100;
                $ws = ($ps * $groupWeight) / 100;
                $initialGrade += $ws;
            }
        }

        $transmuted = $this->transmuteGrade($initialGrade);

        Grade::updateOrCreate(
            [
                'student_id'         => $student->id,
                'section_subject_id' => $sectionSubject->id,
                'grading_period'     => $gradingPeriod,
            ],
            [
                'grade' => $transmuted,
            ]
        );
    }

    return response()->json([
        'status' => 'success',
        'message' => 'Grades computed successfully.',
    ]);
}

    public function previewGrades(Request $request, $sectionId, $subjectId)
{
    $gradingPeriod = (string) ($request->grading_period ?? '1st');

    $sectionSubject = SectionSubject::where('section_model_id', $sectionId)
        ->where('subject_model_id', $subjectId)
        ->firstOrFail();

    $section = SectionModel::findOrFail($sectionId);
    $students = $this->sectionStudentsQuery($section)->get();

    /* ===============================
       LOAD GROUPS
    =============================== */
    $groups = SubjectGrading::where('subject_id', $subjectId)->get();

    /* ===============================
       LOAD CONTENT
    =============================== */
    $activities = Activity::where('section_subject_id', $sectionSubject->id)
        ->where('grading_period', $gradingPeriod)
        ->get()
        ->groupBy('classwork_type');

    $quizzes = Quiz::where('section_model_id', $sectionId)
        ->where('subject_model_id', $subjectId)
        ->where('grading_period', $gradingPeriod)
        ->get()
        ->groupBy('classwork_type');

    $evaluations = Evaluation::where('section_subject_id', $sectionSubject->id)
        ->where('grading_period', $gradingPeriod)
        ->get()
        ->groupBy('classwork_type');

    /* ===============================
       ATTENDANCE TYPE (IMPORTANT FIX)
    =============================== */
    $attendanceType = AttendanceType::where('section_subject_id', $sectionSubject->id)
        ->where('grading_period', $gradingPeriod)
        ->first();

    $attendanceGroupId = $attendanceType?->subject_grading_id;

    $attendanceScores = AttendanceScore::where('section_subject_id', $sectionSubject->id)
        ->where('grading_period', $gradingPeriod)
        ->get()
        ->keyBy('student_id');

    /* ===============================
       COMPUTATION
    =============================== */
    $results = [];

    foreach ($students as $student) {

        $initialGrade = 0;
        $groupBreakdown = [];

        foreach ($groups as $group) {

            $groupWeight = (int) $group->percentage;
            $groupName   = $group->classwork_type;

            $earned = 0;
            $possible = 0;
            $items = [];

            $groupActivities  = $activities[$group->id] ?? collect();
            $groupQuizzes     = $quizzes[$group->id] ?? collect();
            $groupEvaluations = $evaluations[$group->id] ?? collect();

            /* ===== ATTENDANCE ===== */
            if ($attendanceGroupId == $group->id) {

                $attendance = $attendanceScores[$student->id] ?? null;

                if ($attendance) {

                    $totalDays =
                        $attendance->present +
                        $attendance->absent +
                        $attendance->excused +
                        $attendance->late;

                    if ($totalDays > 0) {

                        $earnedDays =
                            $attendance->present +
                            $attendance->excused +
                            $attendance->late;

                        $possible += $totalDays;
                        $earned += $earnedDays;

                        $items[] = [
                            'label' => 'Attendance',
                            'score' => "{$earnedDays} / {$totalDays}"
                        ];
                    }
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
                    'group'     => $groupName,
                    'weight'    => $groupWeight,
                    'earned'    => round($earned, 2),
                    'possible'  => round($possible, 2),
                    'ps'        => round($ps, 2),
                    'ws'        => round($ws, 2),
                    'items'     => $items
                ];
            }
        }

        $transmuted = $this->transmuteGrade($initialGrade);

        $results[] = [
            'student_id' => $student->id,
            'student'    => $student->last_name . ', ' . $student->first_name,
            'initial'    => round($initialGrade, 2),
            'transmuted' => $transmuted,
            'groups'     => $groupBreakdown
        ];
    }

    return response()->json([
        'grading_period' => $gradingPeriod,
        'results'        => $results,
    ]);
}

private function transmuteGrade($grade)
{
    if ($grade >= 100) return 100;

    if ($grade >= 98.40) return 99;
    if ($grade >= 96.80) return 98;
    if ($grade >= 95.20) return 97;
    if ($grade >= 93.60) return 96;
    if ($grade >= 92.00) return 95;
    if ($grade >= 90.40) return 94;
    if ($grade >= 88.80) return 93;
    if ($grade >= 87.20) return 92;
    if ($grade >= 85.60) return 91;
    if ($grade >= 84.00) return 90;
    if ($grade >= 82.40) return 89;
    if ($grade >= 80.80) return 88;
    if ($grade >= 79.20) return 87;
    if ($grade >= 77.60) return 86;
    if ($grade >= 76.00) return 85;
    if ($grade >= 74.40) return 84;
    if ($grade >= 72.80) return 83;
    if ($grade >= 71.20) return 82;
    if ($grade >= 69.60) return 81;
    if ($grade >= 68.00) return 80;
    if ($grade >= 66.40) return 79;
    if ($grade >= 64.80) return 78;
    if ($grade >= 63.20) return 77;
    if ($grade >= 61.60) return 76;
    if ($grade >= 60.00) return 75;
    if ($grade >= 56.00) return 74;
    if ($grade >= 52.00) return 73;
    if ($grade >= 48.00) return 72;
    if ($grade >= 44.00) return 71;
    if ($grade >= 40.00) return 70;
    if ($grade >= 36.00) return 69;
    if ($grade >= 32.00) return 68;
    if ($grade >= 28.00) return 67;
    if ($grade >= 24.00) return 66;
    if ($grade >= 20.00) return 65;
    if ($grade >= 16.00) return 64;
    if ($grade >= 12.00) return 63;
    if ($grade >= 8.00) return 62;
    if ($grade >= 4.00) return 61;

    return 60;
}








}
