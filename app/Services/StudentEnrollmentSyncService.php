<?php

namespace App\Services;

use App\Models\SchoolYear;
use App\Models\SectionModel;
use App\Models\StudentEnrollment;
use App\Models\StudentModel;
use Illuminate\Support\Facades\DB;

class StudentEnrollmentSyncService
{
    public function syncCurrentEnrollment(StudentModel $student, ?SchoolYear $fallbackSchoolYear = null): ?StudentEnrollment
    {
        $student->loadMissing('section.schoolYear');
        $section = $student->section;
        $schoolYear = $section?->schoolYear ?? $fallbackSchoolYear ?? SchoolYear::activeYear();

        if (! $schoolYear) {
            return null;
        }

        $gradeLevel = $this->resolveGradeLevel($student, $section);

        return DB::transaction(function () use ($student, $schoolYear, $section, $gradeLevel): StudentEnrollment {
            StudentEnrollment::query()
                ->where('student_id', $student->id)
                ->where('school_year_id', '!=', $schoolYear->id)
                ->where('is_current', true)
                ->update(['is_current' => false]);

            return StudentEnrollment::query()->updateOrCreate(
                [
                    'student_id' => $student->id,
                    'school_year_id' => $schoolYear->id,
                ],
                [
                    'section_id' => $section?->id,
                    'grade_level' => $gradeLevel,
                    'status' => 'active',
                    'is_current' => true,
                    'ended_at' => null,
                ]
            );
        });
    }

    public function syncCurrentRosterForSchoolYear(SchoolYear $schoolYear, ?SectionModel $section = null): void
    {
        StudentModel::query()
            ->when(
                $section,
                fn ($query) => $query->where('section_id', $section->id),
                fn ($query) => $query->whereHas('section', fn ($sectionQuery) => $sectionQuery->where('school_year_id', $schoolYear->id))
            )
            ->with('section.schoolYear')
            ->get()
            ->each(function (StudentModel $student) use ($schoolYear): void {
                if ((int) $student->section?->school_year_id !== (int) $schoolYear->id) {
                    return;
                }

                $this->syncCurrentEnrollment($student, $schoolYear);
            });
    }

    private function resolveGradeLevel(StudentModel $student, ?SectionModel $section): ?int
    {
        $gradeLevel = filter_var($student->grade, FILTER_VALIDATE_INT);

        if ($gradeLevel !== false) {
            return (int) $gradeLevel;
        }

        if ($section?->grade_level !== null) {
            return (int) $section->grade_level;
        }

        return null;
    }
}
