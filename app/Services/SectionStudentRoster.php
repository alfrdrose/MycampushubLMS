<?php

namespace App\Services;

use App\Models\SchoolYear;
use App\Models\SectionModel;
use App\Models\StudentEnrollment;
use App\Models\StudentModel;
use Illuminate\Database\Eloquent\Builder;

class SectionStudentRoster
{
    public function studentsQuery(SectionModel $section): Builder
    {
        if (! $this->usesHistoricalEnrollments($section)) {
            return StudentModel::query()->where('section_id', $section->id);
        }

        return StudentModel::query()->whereIn('id', StudentEnrollment::query()
            ->select('student_id')
            ->where('school_year_id', $section->school_year_id)
            ->where('section_id', $section->id)
            ->distinct());
    }

    public function studentIds(SectionModel $section): array
    {
        return $this->studentsQuery($section)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    public function studentUserIds(SectionModel $section): array
    {
        return $this->studentsQuery($section)
            ->whereNotNull('user_id')
            ->pluck('user_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    public function count(SectionModel $section): int
    {
        return (int) $this->studentsQuery($section)->count();
    }

    public function containsStudent(SectionModel $section, StudentModel $student): bool
    {
        return $this->studentsQuery($section)
            ->whereKey($student->id)
            ->exists();
    }

    public function isAuditOnly(SectionModel $section): bool
    {
        $activeSchoolYearId = SchoolYear::activeId();

        if ($activeSchoolYearId === null || $section->school_year_id === null) {
            return false;
        }

        return (int) $section->school_year_id !== $activeSchoolYearId;
    }

    private function usesHistoricalEnrollments(SectionModel $section): bool
    {
        return $this->isAuditOnly($section);
    }
}
