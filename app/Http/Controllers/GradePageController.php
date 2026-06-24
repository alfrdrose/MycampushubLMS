<?php

namespace App\Http\Controllers;

use App\Models\Grade;
use App\Models\SchoolYear;
use App\Models\SectionModel;
use App\Models\SectionSubject;
use App\Models\StudentModel;
use App\Models\SubjectModel;
use Illuminate\Support\Facades\Auth;

class GradePageController extends Controller
{
    /**
     * Show all grades of the logged-in student
     */
    public function index()
    {
        // Get logged-in student
        $student = StudentModel::where('user_id', Auth::id())->firstOrFail();
        $activeSchoolYearId = SchoolYear::activeId();

        // Get student's section
        $section = SectionModel::query()
            ->whereKey($student->section_id)
            ->when($activeSchoolYearId, function ($query, $activeSchoolYearId) {
                $query->where('school_year_id', $activeSchoolYearId);
            }, function ($query) {
                $query->whereRaw('1 = 0');
            })
            ->first();

        if (! $section) {
            return view('pages.student.pages.grades', [
                'title' => 'My Grades',
                'student' => $student,
                'section' => null,
                'subjects' => collect(),
                'preloader' => false,
                'darkmode' => true,
                'scrollup' => true,
                'banner' => true,
                'sidebar' => true,
            ]);
        }

        // Get all subjects under this section
        $sectionSubjects = SectionSubject::with('subject')
            ->where('section_model_id', $section->id)
            ->get();

        // Get all grades of this student for the section
        $grades = Grade::where('student_id', $student->id)
            ->whereIn(
                'section_subject_id',
                $sectionSubjects->pluck('id')
            )
            ->get()
            ->groupBy('section_subject_id');

        $subjectsWithGrades = $sectionSubjects->map(function ($ss) use ($grades) {
            $gradeRecords = $grades->get($ss->id, collect());

            return [
                'subject_id' => $ss->subject_model_id,
                'subject_name' => $ss->subject->subject_name ?? 'Unknown Subject',
                'grades' => $gradeRecords
                    ->keyBy('grading_period')
                    ->map(fn ($g) => $g->grade),
            ];
        });

        return view('pages.student.pages.grades', [
            'title' => 'My Grades',
            'student' => $student,
            'section' => $section,
            'subjects' => $subjectsWithGrades,

            'preloader' => false,
            'darkmode' => true,
            'scrollup' => true,
            'banner' => true,
            'sidebar' => true,
        ]);
    }
}
