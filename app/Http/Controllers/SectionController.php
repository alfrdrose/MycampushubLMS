<?php

namespace App\Http\Controllers;

use App\Models\SchoolYear;
use App\Models\SectionModel;
use App\Models\StudentModel;
use App\Models\SubjectModel;
use App\Models\SubjectTeacher;
use App\Services\StudentEnrollmentSyncService;
use App\Support\AdminActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SectionController extends Controller
{
    public function __construct(
        private readonly StudentEnrollmentSyncService $studentEnrollmentSyncService
    ) {
    }

    /**
     * Display a listing of the resource.
     */
    public function destroy($id)
    {
        $section = SectionModel::findOrFail($id);

        /* =========================
        1️⃣ Unassign students
        ========================= */
        StudentModel::where('section_id', $id)
            ->update(['section_id' => null]);

        /* =========================
        2️⃣ Remove adviser
        ========================= */
        \App\Models\TeacherModel::where('section', $id)
            ->update(['section' => null]);

        /* =========================
        3️⃣ Remove subject-teacher assignments
        ========================= */
        SubjectTeacher::where('section_model_id', $id)->delete();

        /* =========================
        4️⃣ Detach subjects (removes schedules too)
        ========================= */
        $section->subjects()->detach();

        /* =========================
        5️⃣ Log activity
        ========================= */
        AdminActivityLog::log('deleted', $section, [
            'module' => "Section Management - Delete Section",
        ]);

        /* =========================
        6️⃣ Delete section itself
        ========================= */
        $section->delete();

        return redirect()
            ->route('admin.sections')
            ->with('success', 'Section deleted successfully.');
    }

    public function index(Request $request)
{
    $search = $request->input('search');
    $grade_level = $request->input('grade_level', 1);
    $activeSchoolYear = SchoolYear::activeYear();
    $schoolYears = SchoolYear::query()
        ->orderByDesc('start_year')
        ->orderByDesc('end_year')
        ->get();
    $selectedSchoolYear = is_numeric($request->input('school_year'))
        ? $schoolYears->firstWhere('id', (int) $request->input('school_year'))
        : null;

    $selectedSchoolYear ??= $activeSchoolYear;

    $sections = SectionModel::query()
        ->when(
            $selectedSchoolYear,
            fn ($query) => $query->where('school_year_id', $selectedSchoolYear->id),
            fn ($query) => $query->whereRaw('1 = 0')
        )
        ->when($search, function ($query) use ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('section_name', 'like', "%{$search}%")
                  ->orWhere('section_rank', 'like', "%{$search}%");
            });
        })
        ->when($grade_level, function ($query, $grade_level) {
            $query->where('grade_level', $grade_level);
        })
        ->orderBy('section_rank', 'asc')
        ->paginate(10)
        ->withQueryString();

    $columns = Schema::getColumnListing('section_models');
    $exclude = ['section', 'created_at', 'updated_at'];
    $columns = array_diff($columns, $exclude);
    $columns[] = 'actions';

    return view('pages.admin.dashboard.sections', [
        'title'        => 'My Campus LMS | Class Sections',
        'preloader'    => false,
        'darkmode'     => true,
        'scrollup'     => true,
        'sidebar'      => true,
        'banner'       => false,
        'sections'     => $sections,
        'columns'      => $columns,
        'grade_level'  => $grade_level,
        'activeSchoolYear' => $activeSchoolYear,
        'schoolYears' => $schoolYears,
        'selectedSchoolYear' => $selectedSchoolYear,
    ]);
}


    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {   
         $grade_level = $request->get('grade_level', null);
         $activeSchoolYear = SchoolYear::activeYear();

        return view('pages.admin.dashboard.addsection', compact('grade_level', 'activeSchoolYear'), [
        'title' => 'My Campus LMS | Add Section',
        'preloader' => false,
        'darkmode'  => true,
        'scrollup'  => true,
        'sidebar'   => true,
        'banner'    => false,
    ]);
    }

    /**
     * Store a newly created resource in storage.
     */
public function store(Request $request)
{
    $request->validate([
        'grade_level' => 'required|integer|min:1|max:12',
        'section_name' => 'required|string|max:255',
    ]);

    $schoolYear = SchoolYear::activeYear();

    if (! $schoolYear) {
        return back()
            ->withErrors([
                'school_year' => 'Activate a school year in School Year Management before adding sections.',
            ])
            ->withInput();
    }

    $nextRank = SectionModel::where('grade_level', $request->grade_level)
        ->where('school_year_id', $schoolYear->id)
        ->max('section_rank');

    $nextRank = $nextRank ? $nextRank + 1 : 1; // start at 1 if none exist

    SectionModel::create([
        'grade_level'   => $request->grade_level,
        'section_rank'  => $nextRank, 
        'section_name'  => $request->section_name,
        'start_year'    => $schoolYear->start_year,
        'end_year'      => $schoolYear->end_year,
        'school_year_id' => $schoolYear->id,
    ]);

    return redirect()->route('admin.sections', [
        'grade_level' => $request->grade_level
    ])->with('success', 'Section added successfully!');
}



    /**
     * Display the specified resource.
     */
    public function show($sectionId)
    {
        $section = SectionModel::find($sectionId);
        
        // Get subjects assigned to this section through the pivot table
        $subjects = $section->subjects()->get();
        
        // Get teachers for subjects in THIS SECTION ONLY
        $subjectTeachers = SubjectTeacher::where('section_model_id', $sectionId)->get();
        
        return view('section.show', compact('section', 'subjects', 'subjectTeachers'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
       $section = SectionModel::findOrFail($id);

// Count related data
$totalStudents = $section->students()->count();
$totalSubjects = $section->subjects()->count();
$adviser = $section->adviser;

// Get subjects with pivot schedule and assigned teachers
$subjects = $section->subjects()
    ->with('teachers')
    ->get()
    ->sortBy(function($subject) {
        // Sort by start_time in pivot table
        return $subject->pivot->start_time ?? '00:00';
    });

return view('pages.admin.dashboard.updatesection', [
    'title' => 'My Campus LMS | Edit Section',
    'preloader' => false,
    'darkmode'  => true,
    'scrollup'  => true,
    'sidebar'   => true,
    'banner'    => false,
    'section'   => $section,
    'totalStudents'=> $totalStudents,
    'totalSubjects'=> $totalSubjects,
    'adviser'      => $adviser,
    'subjects'     => $subjects,
    'sectionReadOnly' => ! $section->schoolYear?->is_active,
]);

    }


    public function adviser($id) {
        $section = SectionModel::findOrFail($id);

        // Adviser info
        $adviser = $section->adviser;

        return view('pages.admin.section.adviser', compact('section'))->with([
            'title' => 'Section Adviser',
            'sidebar' => true,
            'darkmode'  => true,
            'scrollup'  => true,
            'banner'    => false,
            'section'   => $section,
            'adviser'      => $adviser,
            'sectionReadOnly' => ! $section->schoolYear?->is_active,
        ]);
    }

    public function addadviser(string $id) {
    $section = SectionModel::findOrFail($id);

    // Fetch adviser if already assigned
    $adviser = \App\Models\TeacherModel::where('section', $id)->first();

    // Fetch teachers who are not yet assigned to any section
    $teachers = \App\Models\TeacherModel::whereNull('section')->paginate(10)->withQueryString();

    return view('pages.admin.section.addadviser', [
        'title' => 'Section Adviser',
        'preloader' => false,
        'darkmode' => true,
        'scrollup' => true,
        'sidebar' => true,
        'banner' => false,
        'section' => $section,
        'adviser' => $adviser,
        'teachers' => $teachers,
        'sectionReadOnly' => ! $section->schoolYear?->is_active,
    ]);
}


    public function assignAdviser(Request $request, $id)
{
    $teacherId = $request->input('teacher_id');
    $section = SectionModel::findOrFail($id);

    // Remove adviser from any other teacher that had this section
    \App\Models\TeacherModel::where('section', $id)->update(['section' => null]);

    // Assign new adviser
    $teacher = \App\Models\TeacherModel::findOrFail($teacherId);
    $teacher->disableLogging();
    $teacher->section = $id;
    $teacher->save();

    AdminActivityLog::log('updated', $section, [
        'module' => "Section Management - Class Advisory, Teacher-{$teacherId}",
    ]);

    return redirect()->route('admin.section.adviser', $id);
}

public function removeAdviser($id)
{
    // Find the section
    $section = SectionModel::findOrFail($id);

    // Find the current adviser assigned to this section
    $adviser = \App\Models\TeacherModel::where('section', $id)->first();

    if ($adviser) {
        // Remove the adviser's assigned section
        $adviser->disableLogging();
        $adviser->section = null;
        $adviser->save();

        AdminActivityLog::log('deleted', $section, [
            'module' => "Section Management - Class Advisory, Teacher-{$adviser->id}",
        ]);
    }

    return redirect()
        ->route('admin.section.adviser', $id)
        ->with('success', 'Adviser removed successfully.');
}

// show students in section
public function students($id)
{
    $section = SectionModel::findOrFail($id);

    // Fetch all students that belong to this section
    $students = \App\Models\StudentModel::where('section_id', $id)
        ->orderBy('last_name')
        ->paginate(10)
        ->withQueryString();

    return view('pages.admin.section.students', [
        'title' => 'Students in ' . $section->section_name,
        'preloader' => false,
        'darkmode'  => true,
        'scrollup'  => true,
        'sidebar'   => true,
        'banner'    => false,
        'section'   => $section,
        'students'  => $students,
        'sectionReadOnly' => ! $section->schoolYear?->is_active,
    ]);
}
public function addStudent($sectionId)
{
    $section = SectionModel::findOrFail($sectionId);

    // Get students not yet assigned to a section
    $students = StudentModel::whereNull('section_id')
        ->where('grade', $section->grade_level)
        ->get();

   return view('pages.admin.section.addstudent', [
    'title' => 'My Campus LMS | Add Students to ' . $section->section_name,
    'preloader' => false,
    'darkmode'  => true,
    'scrollup'  => true,
    'sidebar'   => true,
    'banner'    => false,
    'section'   => $section,
    'students'  => $students,
    'sectionReadOnly' => ! $section->schoolYear?->is_active,
]);

}


public function addStudents(Request $request, $sectionId)
{
    $section = SectionModel::findOrFail($sectionId);
    $studentIds = json_decode($request->student_ids, true) ?: [];

    DB::transaction(function () use ($studentIds, $section, $sectionId): void {
        foreach ($studentIds as $id) {
            $student = \App\Models\StudentModel::find($id);
            if (! $student) {
                continue;
            }

            $student->disableLogging();
            $student->section_id = $sectionId;
            $student->save();
            $student->setRelation('section', $section);

            $this->studentEnrollmentSyncService->syncCurrentEnrollment($student, $section->schoolYear);

            AdminActivityLog::log('updated', $section, [
                'module' => "Section Management - Class List, Student-{$student->id}",
            ]);
        }
    });

    return redirect()->route('admin.section.students', $sectionId)
                     ->with('success', 'Students added successfully.');
}


public function removeStudent($sectionId, $studentId)
{
    $section = SectionModel::findOrFail($sectionId);

    // Find the student who belongs to this section
    $student = \App\Models\StudentModel::where('id', $studentId)
                      ->where('section_id', $sectionId)  // Change from 'section' to 'section_id'
                      ->firstOrFail();

    DB::transaction(function () use ($student, $section): void {
        // Remove the section assignment
        $student->disableLogging();
        $student->section_id = null;  // Change from 'section' to 'section_id'
        $student->save();
        $student->unsetRelation('section');

        $this->studentEnrollmentSyncService->syncCurrentEnrollment($student, $section->schoolYear);

        AdminActivityLog::log('deleted', $section, [
            'module' => "Section Management - Class List, Student-{$student->id}",
        ]);
    });

    return redirect()->back()->with('success', 'Student removed from section successfully.');
}


// show subjects in section
public function subjects($id)
{
    $section = SectionModel::findOrFail($id);

    $subjects = $section->subjects()
        ->with(['teachers' => function($query) use ($id) {
            // ✅ Filter teachers by THIS section only
            $query->wherePivot('section_model_id', $id);
        }])
        ->orderBy('subject_name')
        ->paginate(10)
        ->withQueryString();

    return view('pages.admin.section.subjects', [
        'title' => 'Subjects in ' . $section->section_name,
        'section' => $section,
        'subjects' => $subjects,
        'preloader' => false,
        'darkmode'  => true,
        'scrollup'  => true,
        'sidebar'   => true,
        'banner'    => false,
        'sectionReadOnly' => ! $section->schoolYear?->is_active,
    ]);
}



// Show the Add Subject page
public function addSubject($sectionId)
{
    $section = SectionModel::findOrFail($sectionId);

    // Get subjects NOT already assigned to this section
    $subjects = SubjectModel::where('grade_level', $section->grade_level)
        ->whereNotIn('id', function($query) use ($sectionId) {
            $query->select('subject_model_id')
                ->from('section_subject')
                ->where('section_model_id', $sectionId);
        })
        ->get();

    return view('pages.admin.section.addsubject', [
        'title' => 'My Campus LMS | Add Subjects to ' . $section->section_name,
        'preloader' => false,
        'darkmode'  => true,
        'scrollup'  => true,
        'sidebar'   => true,
        'banner'    => false,
        'section'   => $section,
        'subjects'  => $subjects,
        'sectionReadOnly' => ! $section->schoolYear?->is_active,
    ]);
}

// Add selected subjects to section
public function addSubjects(Request $request, $sectionId)
{
    $section = SectionModel::findOrFail($sectionId);
    $subjectIds = json_decode($request->subject_ids, true);

    // Validate subject IDs exist
    $validSubjectIds = SubjectModel::whereIn('id', $subjectIds)
        ->pluck('id')
        ->toArray();

    // Attach or sync the subjects to this section
    $section->subjects()->syncWithoutDetaching($validSubjectIds);

    return redirect()
        ->route('admin.section.subjects', $sectionId)
        ->with('success', 'Subjects added successfully.');
}

// Remove a subject from a section
public function removeSubject($sectionId, $subjectId)
{
    $section = SectionModel::findOrFail($sectionId);
    
    // Simply detach the subject from the section using the pivot table
    $section->subjects()->detach($subjectId);

    AdminActivityLog::log('deleted', $section, [
        'module' => "Section Management - Class Subject-{$subjectId}",
    ]);

    return redirect()->back()->with('success', 'Subject removed from section successfully.');
}

public function assignTeacher($section_id, $subject_id)
{
    $section = SectionModel::findOrFail($section_id);
    $subject = SubjectModel::findOrFail($subject_id);

    // Get all teachers (paginate)
    $teachers = \App\Models\TeacherModel::orderBy('first_name')->paginate(10);

    // Pass everything to the view
    return view('pages.admin.section.assignteacher', [
        'title' => 'Assign Teacher to ' . $subject->subject_name,
        'section' => $section,
        'subject' => $subject,
        'teachers' => $teachers,
        'preloader' => false,
        'darkmode'  => true,
        'scrollup'  => true,
        'sidebar'   => true,
        'banner'    => false,
        'sectionReadOnly' => ! $section->schoolYear?->is_active,
    ]);
}

public function saveAssignment($section_id, $subject_id, $teacher_id)
    {
        $section = SectionModel::findOrFail($section_id);
        $subject = SubjectModel::findOrFail($subject_id);
        $teacher = \App\Models\TeacherModel::findOrFail($teacher_id);

        // ✅ Check if already assigned for this section
        $alreadyAssigned = $subject->teachers()
            ->wherePivot('section_model_id', $section_id)
            ->where('teacher_model_id', $teacher_id)
            ->exists();

        if ($alreadyAssigned) {
            return redirect()
                ->route('admin.section.subjects', ['id' => $section_id])
                ->with('info', 'This teacher is already assigned to this subject.');
        }

        // ✅ Optional: Remove previous assignment for this subject in the same section
        $subject->teachers()->wherePivot('section_model_id', $section_id)->detach();

        // ✅ Assign new teacher
        $subject->teachers()->attach($teacher_id, ['section_model_id' => $section_id]);

        AdminActivityLog::log('updated', $section, [
            'module' => "Section Management - Class Subject-{$subject_id}, Teacher-{$teacher_id}",
        ]);

        return redirect()
            ->route('admin.section.subjects', ['id' => $section_id])
            ->with('success', 'Teacher assigned successfully.');
    }
public function searchTeachers($section_id, $subject_id, Request $request)
{
    $search = $request->get('search');

    $teachers = \App\Models\TeacherModel::query()
        ->when($search, function ($q) use ($search) {
            $q->where('first_name', 'like', "%{$search}%")
              ->orWhere('last_name', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%");
        })
        ->orderBy('first_name')
        ->limit(10)
        ->get();

    return response()->json($teachers);
}
public function removeAssignment($section_id, $subject_id, $teacher_id)
{
    $assignment = SubjectTeacher::where('section_model_id', $section_id)
        ->where('subject_model_id', $subject_id)
        ->where('teacher_model_id', $teacher_id)
        ->first();

    if ($assignment) {
        $assignment->disableLogging();
        $assignment->delete();

        $section = SectionModel::findOrFail($section_id);
        AdminActivityLog::log('deleted', $section, [
            'module' => "Section Management - Class Subject-{$subject_id}, Teacher-{$teacher_id}",
        ]);
    }

    return back()->with('success', 'Teacher removed successfully.');
}
public function saveSchedule(Request $request, $sectionId, $subjectId)
{
    $request->validate([
        'days' => 'required|array|min:1',
        'start_time' => 'required',
        'end_time' => 'required',
    ]);

    $section = SectionModel::findOrFail($sectionId);
    $subject = SubjectModel::with('teachers')->findOrFail($subjectId);

    /* ================= SUBJECT CONFLICT (same section) ================= */
    foreach ($request->days as $day) {
        $conflict = $section->subjects()
            ->where('subject_models.id', '!=', $subjectId)
            ->whereNotNull('section_subject.start_time')
            ->whereNotNull('section_subject.end_time')
            ->where('section_subject.days', 'like', '%"' . $day . '"%')
            ->where('section_subject.start_time', '<', $request->end_time)
            ->where('section_subject.end_time', '>', $request->start_time)
            ->exists();

        if ($conflict) {
            return back()->withErrors([
                'schedule' =>
                    "Schedule conflict on {$day} ({$request->start_time}–{$request->end_time})."
            ]);
        }
    }

    /* ================= GLOBAL TEACHER CONFLICT (REAL FIX) ================= */

    foreach ($subject->teachers as $teacher) {

        $teacherConflict = \DB::table('subject_teacher')
            ->join('section_subject', function ($join) {
                $join->on(
                    'section_subject.subject_model_id',
                    '=',
                    'subject_teacher.subject_model_id'
                )
                ->on(
                    'section_subject.section_model_id',
                    '=',
                    'subject_teacher.section_model_id'
                );
            })
            ->where('subject_teacher.teacher_model_id', $teacher->id)

            // ignore current subject+section (edit case)
            ->where(function ($q) use ($subjectId, $sectionId) {
                $q->where('subject_teacher.subject_model_id', '!=', $subjectId)
                  ->orWhere('subject_teacher.section_model_id', '!=', $sectionId);
            })

            // time overlap
            ->where('section_subject.start_time', '<', $request->end_time)
            ->where('section_subject.end_time', '>', $request->start_time)

            // day overlap (OR)
            ->where(function ($q) use ($request) {
                foreach ($request->days as $day) {
                    $q->orWhere('section_subject.days', 'like', '%"' . $day . '"%');
                }
            })
            ->exists();

        if ($teacherConflict) {
            return back()->withErrors([
                'schedule' =>
                    'Teacher ' . $teacher->first_name .
                    ' has already been assigned during this time.'
            ]);
        }
    }

    /* ================= SAVE ================= */
    $section->subjects()->syncWithoutDetaching([
        $subjectId => [
            'days' => json_encode($request->days),
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
        ]
    ]);

    AdminActivityLog::log('updated', $section, [
        'module' => "Section Management - Class Schedule, Subject-{$subjectId}",
    ]);

    return back()->with('success', 'Schedule saved successfully.');
}













public function removeSchedule($sectionId, $subjectId)
{
    $section = SectionModel::findOrFail($sectionId);

    // Remove schedule from pivot table
    $section->subjects()->updateExistingPivot($subjectId, [
        'days' => null,
        'start_time' => null,
        'end_time' => null,
    ]);

    AdminActivityLog::log('deleted', $section, [
        'module' => "Section Management - Class Schedule, Subject-{$subjectId}",
    ]);

    return redirect()->back()->with('success', 'Schedule removed successfully!');
}


}
