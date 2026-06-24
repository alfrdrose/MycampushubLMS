<?php

namespace App\Services;

use App\Models\SchoolYear;
use App\Models\SectionModel;
use App\Models\StudentEnrollment;
use App\Models\StudentModel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SchoolYearPromotionService
{
    public function __construct(
        private readonly StudentEnrollmentSyncService $studentEnrollmentSyncService
    ) {
    }

    public const ACTION_PROMOTED = 'promoted';
    public const ACTION_RETAINED = 'retained';
    public const ACTION_GRADUATED = 'graduated';
    public const ACTION_TRANSFERRED_OUT = 'transferred_out';

    public static function actionLabels(): array
    {
        return [
            self::ACTION_PROMOTED => 'Promoted',
            self::ACTION_RETAINED => 'Retained',
            self::ACTION_GRADUATED => 'Graduated',
            self::ACTION_TRANSFERRED_OUT => 'Transferred Out',
        ];
    }

    public function preview(
        SchoolYear $sourceSchoolYear,
        SchoolYear $targetSchoolYear,
        array $actions = [],
        ?SectionModel $sourceSection = null,
        array $targetSectionIds = []
    ): array
    {
        // Repair missing current-year enrollment rows for students already assigned
        // to the selected source roster so promotion preview stays complete.
        $this->studentEnrollmentSyncService->syncCurrentRosterForSchoolYear($sourceSchoolYear, $sourceSection);

        $sourceEnrollments = StudentEnrollment::query()
            ->with([
                'section',
                'student.section',
                'student.enrollments' => function ($query) use ($sourceSchoolYear, $targetSchoolYear) {
                    $query->whereIn('school_year_id', [$sourceSchoolYear->id, $targetSchoolYear->id]);
                },
            ])
            ->where('school_year_id', $sourceSchoolYear->id)
            ->when($sourceSection, fn ($query) => $query->where('section_id', $sourceSection->id))
            ->get()
            ->filter(fn (StudentEnrollment $enrollment) => $enrollment->student !== null)
            ->sortBy([
                fn (StudentEnrollment $enrollment) => $enrollment->student?->last_name ?? '',
                fn (StudentEnrollment $enrollment) => $enrollment->student?->first_name ?? '',
            ])
            ->values();

        $targetSections = SectionModel::query()
            ->where('school_year_id', $targetSchoolYear->id)
            ->orderBy('grade_level')
            ->orderBy('section_rank')
            ->orderBy('section_name')
            ->get();

        $rows = $sourceEnrollments->map(function (StudentEnrollment $sourceEnrollment) use ($actions, $sourceSchoolYear, $targetSchoolYear, $targetSections, $targetSectionIds): array {
            $student = $sourceEnrollment->student;
            $currentSection = $sourceEnrollment->section ?? $student?->section;
            $currentGrade = $this->resolveSourceGrade($sourceEnrollment, $student, $currentSection);
            $action = $this->normalizeAction($actions[$student->id] ?? null, $currentGrade);
            $targetGrade = $this->targetGradeForAction($action, $currentGrade);
            $manualTargetSectionId = isset($targetSectionIds[$student->id]) && is_numeric($targetSectionIds[$student->id])
                ? (int) $targetSectionIds[$student->id]
                : null;
            $targetSection = $currentSection && $targetGrade !== null
                ? $this->resolveTargetSection($currentSection, $targetSections, $targetGrade, $manualTargetSectionId)
                : null;

            $blockingReason = null;
            $blockingReasonCode = null;
            if ($action === self::ACTION_GRADUATED && $currentGrade < 12) {
                $blockingReason = 'Only Grade 12 students can be marked as graduated.';
                $blockingReasonCode = 'invalid_graduation';
            }

            if (in_array($action, [self::ACTION_PROMOTED, self::ACTION_RETAINED], true) && ! $targetSection) {
                $blockingReason = 'Missing matching target section in ' . $targetSchoolYear->label . '.';
                $blockingReasonCode = 'missing_target_section';
            }

            if (
                in_array($action, [self::ACTION_PROMOTED, self::ACTION_RETAINED], true)
                && $manualTargetSectionId
                && $targetSection
                && (int) $targetSection->grade_level !== (int) $targetGrade
            ) {
                $blockingReason = 'The selected target section does not match the required grade level.';
                $blockingReasonCode = 'target_grade_mismatch';
            }

            if ($student->enrollments->contains('school_year_id', $targetSchoolYear->id)) {
                $blockingReason = 'Student already has an enrollment in ' . $targetSchoolYear->label . '.';
                $blockingReasonCode = 'existing_target_enrollment';
            }

            return [
                'student_id' => $student->id,
                'student_name' => trim($student->last_name . ', ' . $student->first_name),
                'current_grade' => $currentGrade,
                'current_section_id' => $currentSection?->id,
                'current_section' => $currentSection
                    ? 'Grade ' . $currentSection->grade_level . ' - Section ' . ($currentSection->section_rank ?: $currentSection->section_name)
                    : 'Unassigned',
                'current_section_name' => $currentSection?->section_name,
                'action' => $action,
                'action_label' => self::actionLabels()[$action],
                'target_grade' => $targetGrade,
                'target_section_id' => $targetSection?->id,
                'target_section' => $targetSection
                    ? 'Grade ' . $targetSection->grade_level . ' - Section ' . ($targetSection->section_rank ?: $targetSection->section_name)
                    : 'No target section',
                'is_manual_target' => $manualTargetSectionId !== null,
                'blocking_reason_code' => $blockingReasonCode,
                'status_note' => $blockingReason ?: $this->statusNote($action, $targetSection, $sourceSchoolYear, $targetSchoolYear),
                'is_blocking' => $blockingReason !== null,
            ];
        })->values();

        return [
            'source_school_year' => $sourceSchoolYear,
            'target_school_year' => $targetSchoolYear,
            'rows' => $rows,
            'summary' => [
                'total_students' => $rows->count(),
                'promoted' => $rows->where('action', self::ACTION_PROMOTED)->count(),
                'retained' => $rows->where('action', self::ACTION_RETAINED)->count(),
                'graduated' => $rows->where('action', self::ACTION_GRADUATED)->count(),
                'transferred_out' => $rows->where('action', self::ACTION_TRANSFERRED_OUT)->count(),
                'blocking' => $rows->where('is_blocking', true)->count(),
            ],
            'has_blocking' => $rows->contains(fn (array $row) => $row['is_blocking']),
        ];
    }

    public function execute(
        SchoolYear $sourceSchoolYear,
        SchoolYear $targetSchoolYear,
        array $actions = [],
        bool $activateTargetSchoolYear = true,
        ?SectionModel $sourceSection = null,
        array $targetSectionIds = []
    ): array {
        $preview = $this->preview($sourceSchoolYear, $targetSchoolYear, $actions, $sourceSection, $targetSectionIds);

        if ($preview['rows']->isEmpty()) {
            throw ValidationException::withMessages([
                'promotion' => $sourceSection
                    ? 'No students are currently assigned to the selected section in the source school year.'
                    : 'No students are currently assigned to the selected source school year.',
            ]);
        }

        if ($preview['has_blocking']) {
            throw ValidationException::withMessages([
                'promotion' => 'Resolve all blocking rows before executing the promotion batch.',
            ]);
        }

        DB::transaction(function () use ($preview, $sourceSchoolYear, $targetSchoolYear, $activateTargetSchoolYear): void {
            if ($activateTargetSchoolYear) {
                $targetSchoolYear->activate();
            }

            foreach ($preview['rows'] as $row) {
                $student = StudentModel::query()
                    ->with([
                        'section',
                        'enrollments' => function ($query) use ($sourceSchoolYear, $targetSchoolYear) {
                            $query->whereIn('school_year_id', [$sourceSchoolYear->id, $targetSchoolYear->id]);
                        },
                    ])
                    ->lockForUpdate()
                    ->findOrFail($row['student_id']);

                $currentEnrollment = $student->enrollments
                    ->firstWhere('school_year_id', $sourceSchoolYear->id);
                $currentSection = $currentEnrollment?->section ?? $student->section;
                $currentGrade = $this->resolveSourceGrade($currentEnrollment, $student, $currentSection);

                if (! $currentEnrollment) {
                    $currentEnrollment = StudentEnrollment::create([
                        'student_id' => $student->id,
                        'school_year_id' => $sourceSchoolYear->id,
                        'section_id' => $currentSection?->id,
                        'grade_level' => $currentGrade,
                        'status' => $row['action'],
                        'is_current' => false,
                        'ended_at' => now(),
                    ]);
                } else {
                    $currentEnrollment->update([
                        'section_id' => $currentEnrollment->section_id ?: $currentSection?->id,
                        'grade_level' => $currentEnrollment->grade_level ?: $currentGrade,
                        'status' => $row['action'],
                        'is_current' => false,
                        'ended_at' => now(),
                    ]);
                }

                if (in_array($row['action'], [self::ACTION_PROMOTED, self::ACTION_RETAINED], true)) {
                    $targetSection = SectionModel::findOrFail($row['target_section_id']);

                    StudentEnrollment::create([
                        'student_id' => $student->id,
                        'school_year_id' => $targetSchoolYear->id,
                        'section_id' => $targetSection->id,
                        'grade_level' => $row['target_grade'],
                        'status' => 'active',
                        'is_current' => true,
                        'previous_enrollment_id' => $currentEnrollment->id,
                    ]);

                    $student->update([
                        'section_id' => $targetSection->id,
                        'grade' => (string) $row['target_grade'],
                    ]);

                    continue;
                }

                $student->update([
                    'section_id' => null,
                    'grade' => null,
                ]);
            }
        });

        return $preview;
    }

    private function normalizeAction(mixed $action, int $currentGrade): string
    {
        $action = is_string($action) ? $action : null;

        if ($action && array_key_exists($action, self::actionLabels())) {
            return $action;
        }

        return $currentGrade >= 12
            ? self::ACTION_GRADUATED
            : self::ACTION_PROMOTED;
    }

    private function targetGradeForAction(string $action, int $currentGrade): ?int
    {
        return match ($action) {
            self::ACTION_PROMOTED => min($currentGrade + 1, 12),
            self::ACTION_RETAINED => $currentGrade,
            default => null,
        };
    }

    private function resolveTargetSection(
        SectionModel $currentSection,
        Collection $targetSections,
        int $targetGrade,
        ?int $manualTargetSectionId = null
    ): ?SectionModel {
        if ($manualTargetSectionId !== null) {
            return $targetSections->firstWhere('id', $manualTargetSectionId);
        }

        $exactRankMatch = $targetSections
            ->first(function (SectionModel $section) use ($currentSection, $targetGrade): bool {
                return (int) $section->grade_level === $targetGrade
                    && $section->section_rank !== null
                    && (int) $section->section_rank === (int) $currentSection->section_rank;
            });

        if ($exactRankMatch) {
            return $exactRankMatch;
        }

        return $targetSections
            ->first(function (SectionModel $section) use ($currentSection, $targetGrade): bool {
                return (int) $section->grade_level === $targetGrade
                    && strcasecmp((string) $section->section_name, (string) $currentSection->section_name) === 0;
            });
    }

    private function resolveCurrentGrade(StudentModel $student, ?SectionModel $currentSection): int
    {
        $grade = filter_var($student->grade, FILTER_VALIDATE_INT);

        if ($grade !== false) {
            return (int) $grade;
        }

        return (int) ($currentSection?->grade_level ?? 0);
    }

    private function resolveSourceGrade(
        ?StudentEnrollment $sourceEnrollment,
        StudentModel $student,
        ?SectionModel $currentSection
    ): int {
        if ($sourceEnrollment && $sourceEnrollment->grade_level !== null) {
            return (int) $sourceEnrollment->grade_level;
        }

        return $this->resolveCurrentGrade($student, $currentSection);
    }

    private function statusNote(
        string $action,
        ?SectionModel $targetSection,
        SchoolYear $sourceSchoolYear,
        SchoolYear $targetSchoolYear
    ): string {
        return match ($action) {
            self::ACTION_PROMOTED => 'Will move from ' . $sourceSchoolYear->label . ' to ' . $targetSchoolYear->label
                . ' and be assigned to ' . ($targetSection?->section_name ?? 'a matching section') . '.',
            self::ACTION_RETAINED => 'Will stay in the same grade for ' . $targetSchoolYear->label . '.',
            self::ACTION_GRADUATED => 'Will be closed out as graduated with no new section assignment.',
            self::ACTION_TRANSFERRED_OUT => 'Will be removed from the active roster with no new section assignment.',
            default => 'No change prepared.',
        };
    }
}
