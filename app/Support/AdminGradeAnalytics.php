<?php

namespace App\Support;

use App\Models\SectionModel;
use Illuminate\Support\Facades\DB;

class AdminGradeAnalytics
{
    private const ALL_OPTION = 'all';

    private const SPECIFIC_GRADING_PERIODS = ['1st', '2nd', '3rd', '4th'];

    private const PASSING_GRADE = 75;

    private const LOW_COVERAGE_THRESHOLD = 70.0;

    private const LOW_SAMPLE_SIZE_THRESHOLD = 10;

    private const DECIMALS = 1;

    public function build(array $filters = []): array
    {
        $schoolYearOptions = $this->schoolYearOptions();
        $selectedSchoolYear = $this->normalizeSchoolYear(
            (string) ($filters['school_year'] ?? ''),
            $schoolYearOptions
        );

        $selectedGradingPeriod = $this->normalizeGradingPeriod(
            $filters['grading_period'] ?? null,
            $selectedSchoolYear
        );

        $gradeLevelOptions = $this->gradeLevelOptions($selectedSchoolYear);
        $selectedGradeLevel = $this->normalizeGradeLevel(
            $filters['grade_level'] ?? null,
            $gradeLevelOptions
        );

        $sectionOptions = $this->sectionOptions($selectedSchoolYear, $selectedGradeLevel);
        $selectedSectionId = $this->normalizeSectionId(
            $filters['section_id'] ?? null,
            $sectionOptions
        );

        $selectedSection = collect($sectionOptions)->firstWhere('value', (string) $selectedSectionId);
        $expectedContext = $this->expectedContext($selectedSchoolYear, $selectedGradingPeriod);

        return [
            'filters' => [
                'school_years' => $schoolYearOptions,
                'grading_periods' => $this->gradingPeriodOptions(),
                'grade_levels' => $gradeLevelOptions,
                'sections' => $sectionOptions,
                'selected' => [
                    'school_year' => $selectedSchoolYear,
                    'school_year_label' => $this->schoolYearLabel($selectedSchoolYear),
                    'grading_period' => $selectedGradingPeriod,
                    'grading_period_label' => $this->gradingPeriodLabel($selectedGradingPeriod),
                    'grade_level' => $selectedGradeLevel === null ? '' : (string) $selectedGradeLevel,
                    'section_id' => $selectedSectionId === null ? '' : (string) $selectedSectionId,
                    'grade_level_label' => $selectedGradeLevel === null
                        ? 'No grade level selected'
                        : 'Grade ' . $selectedGradeLevel,
                    'section_label' => $selectedSection['label'] ?? 'No section selected',
                    'is_cumulative' => $selectedGradingPeriod === self::ALL_OPTION,
                ],
                'notes' => [
                    'scope' => 'The default view uses the latest school year and one grading period.',
                    'coverage' => 'Coverage uses current section rosters because historical enrollment snapshots are not stored yet.',
                ],
            ],
            'summary' => $this->summary($selectedSchoolYear, $selectedGradingPeriod, $expectedContext),
            'charts' => [
                'grade_levels' => $this->gradeLevelChart(
                    $selectedSchoolYear,
                    $selectedGradingPeriod,
                    $expectedContext
                ),
                'sections' => $this->sectionChart(
                    $selectedSchoolYear,
                    $selectedGradingPeriod,
                    $selectedGradeLevel,
                    $expectedContext
                ),
                'subjects' => $this->subjectChart(
                    $selectedSchoolYear,
                    $selectedGradingPeriod,
                    $selectedSectionId,
                    $expectedContext
                ),
            ],
        ];
    }

    private function gradingPeriodOptions(): array
    {
        $options = collect(self::SPECIFIC_GRADING_PERIODS)
            ->map(function (string $period): array {
                return [
                    'value' => $period,
                    'label' => $period . ' Grading',
                ];
            })
            ->all();

        $options[] = [
            'value' => self::ALL_OPTION,
            'label' => 'All Grading Period',
        ];

        return $options;
    }

    private function schoolYearOptions(): array
    {
        $specificYears = SectionModel::query()
            ->select('start_year', 'end_year')
            ->distinct()
            ->orderByDesc('end_year')
            ->orderByDesc('start_year')
            ->get()
            ->map(function ($section): array {
                $value = $this->formatSchoolYearValue(
                    (int) $section->start_year,
                    (int) $section->end_year
                );

                return [
                    'value' => $value,
                    'label' => 'SY ' . $value,
                ];
            })
            ->values()
            ->all();

        $specificYears[] = [
            'value' => self::ALL_OPTION,
            'label' => 'All School Years',
        ];

        return $specificYears;
    }

    private function gradeLevelOptions(string $schoolYear): array
    {
        return $this->applySchoolYearFilter(
                SectionModel::query()->select('grade_level')->distinct(),
                $schoolYear,
                'section_models'
            )
            ->orderBy('grade_level')
            ->pluck('grade_level')
            ->filter(static fn ($gradeLevel) => $gradeLevel !== null)
            ->map(function ($gradeLevel): array {
                return [
                    'value' => (string) $gradeLevel,
                    'label' => 'Grade ' . $gradeLevel,
                ];
            })
            ->values()
            ->all();
    }

    private function sectionOptions(string $schoolYear, ?int $gradeLevel): array
    {
        if ($gradeLevel === null) {
            return [];
        }

        return $this->applySchoolYearFilter(
                SectionModel::query()
                    ->select('id', 'section_rank', 'section_name')
                    ->where('grade_level', $gradeLevel),
                $schoolYear,
                'section_models'
            )
            ->orderByRaw('CASE WHEN section_rank IS NULL THEN 1 ELSE 0 END')
            ->orderBy('section_rank')
            ->orderBy('section_name')
            ->get()
            ->map(function ($section): array {
                return [
                    'value' => (string) $section->id,
                    'label' => $this->formatSectionLabel($section),
                ];
            })
            ->values()
            ->all();
    }

    private function summary(string $schoolYear, string $gradingPeriod, array $expectedContext): array
    {
        $stats = $this->baseGradeQuery($schoolYear, $gradingPeriod)
            ->selectRaw('AVG(g.grade) as overall_average')
            ->selectRaw('COUNT(g.id) as computed_grade_count')
            ->selectRaw('COUNT(DISTINCT g.student_id) as covered_student_count')
            ->selectRaw('SUM(CASE WHEN g.grade >= ' . self::PASSING_GRADE . ' THEN 1 ELSE 0 END) as pass_count')
            ->selectRaw('COUNT(DISTINCT sec.id) as section_count')
            ->selectRaw('COUNT(DISTINCT sub.id) as subject_count')
            ->first();

        $computedGradeCount = (int) ($stats?->computed_grade_count ?? 0);
        $coveredStudentCount = (int) ($stats?->covered_student_count ?? 0);
        $passCount = (int) ($stats?->pass_count ?? 0);
        $expectedGradeCount = (int) ($expectedContext['total_expected_grade_count'] ?? 0);
        $expectedStudentCount = (int) ($expectedContext['total_expected_students'] ?? 0);
        $coverageRate = $this->rate($computedGradeCount, $expectedGradeCount);
        $passRate = $this->rate($passCount, $computedGradeCount);

        return [
            'overall_average' => $stats?->overall_average === null
                ? null
                : round((float) $stats->overall_average, self::DECIMALS),
            'pass_rate' => $passRate,
            'coverage_rate' => $coverageRate,
            'computed_grade_count' => $computedGradeCount,
            'expected_grade_count' => $expectedGradeCount,
            'students_covered' => $coveredStudentCount,
            'expected_students' => $expectedStudentCount,
            'section_count' => (int) ($stats?->section_count ?? 0),
            'subject_count' => (int) ($stats?->subject_count ?? 0),
            'warning' => $this->warningText(
                $coverageRate,
                $coveredStudentCount,
                $expectedGradeCount,
                $computedGradeCount
            ),
        ];
    }

    private function gradeLevelChart(
        string $schoolYear,
        string $gradingPeriod,
        array $expectedContext
    ): array {
        $rows = $this->baseGradeQuery($schoolYear, $gradingPeriod)
            ->select('sec.grade_level')
            ->selectRaw('AVG(g.grade) as average_grade')
            ->selectRaw('COUNT(g.id) as computed_grade_count')
            ->selectRaw('COUNT(DISTINCT g.student_id) as covered_student_count')
            ->selectRaw('SUM(CASE WHEN g.grade >= ' . self::PASSING_GRADE . ' THEN 1 ELSE 0 END) as pass_count')
            ->groupBy('sec.grade_level')
            ->orderBy('sec.grade_level')
            ->get()
            ->keyBy(fn ($row) => (string) $row->grade_level);

        $entities = collect($this->gradeLevelOptions($schoolYear))
            ->map(function (array $option) use ($rows, $expectedContext): array {
                $gradeLevel = (int) $option['value'];
                $group = $expectedContext['section_subjects']->where('grade_level', $gradeLevel);

                return $this->buildEntityMetrics(
                    $option['label'],
                    $rows->get((string) $gradeLevel),
                    (int) $group->sum('expected_grade_count'),
                    (int) $group->unique('section_id')->sum('expected_student_count')
                );
            })
            ->all();

        return $this->formatChartData($entities);
    }

    private function sectionChart(
        string $schoolYear,
        string $gradingPeriod,
        ?int $gradeLevel,
        array $expectedContext
    ): array {
        if ($gradeLevel === null) {
            return $this->emptyChart();
        }

        $rows = $this->baseGradeQuery($schoolYear, $gradingPeriod)
            ->where('sec.grade_level', $gradeLevel)
            ->select('sec.id', 'sec.section_rank', 'sec.section_name')
            ->selectRaw('AVG(g.grade) as average_grade')
            ->selectRaw('COUNT(g.id) as computed_grade_count')
            ->selectRaw('COUNT(DISTINCT g.student_id) as covered_student_count')
            ->selectRaw('SUM(CASE WHEN g.grade >= ' . self::PASSING_GRADE . ' THEN 1 ELSE 0 END) as pass_count')
            ->groupBy('sec.id', 'sec.section_rank', 'sec.section_name')
            ->orderByRaw('CASE WHEN sec.section_rank IS NULL THEN 1 ELSE 0 END')
            ->orderBy('sec.section_rank')
            ->orderBy('sec.section_name')
            ->get()
            ->keyBy(fn ($row) => (string) $row->id);

        $entities = collect($this->sectionOptions($schoolYear, $gradeLevel))
            ->map(function (array $option) use ($rows, $expectedContext): array {
                $sectionId = (int) $option['value'];
                $group = $expectedContext['section_subjects']->where('section_id', $sectionId);

                return $this->buildEntityMetrics(
                    $option['label'],
                    $rows->get((string) $sectionId),
                    (int) $group->sum('expected_grade_count'),
                    (int) $group->unique('section_id')->sum('expected_student_count')
                );
            })
            ->all();

        return $this->formatChartData($entities);
    }

    private function subjectChart(
        string $schoolYear,
        string $gradingPeriod,
        ?int $sectionId,
        array $expectedContext
    ): array {
        if ($sectionId === null) {
            return $this->emptyChart();
        }

        $rows = $this->baseGradeQuery($schoolYear, $gradingPeriod)
            ->where('sec.id', $sectionId)
            ->select('sub.id', 'sub.subject_name')
            ->selectRaw('AVG(g.grade) as average_grade')
            ->selectRaw('COUNT(g.id) as computed_grade_count')
            ->selectRaw('COUNT(DISTINCT g.student_id) as covered_student_count')
            ->selectRaw('SUM(CASE WHEN g.grade >= ' . self::PASSING_GRADE . ' THEN 1 ELSE 0 END) as pass_count')
            ->groupBy('sub.id', 'sub.subject_name')
            ->orderBy('sub.subject_name')
            ->get()
            ->keyBy(fn ($row) => (string) $row->id);

        $entities = $expectedContext['section_subjects']
            ->where('section_id', $sectionId)
            ->sortBy('subject_name')
            ->map(function (array $subject) use ($rows, $expectedContext, $sectionId): array {
                $group = $expectedContext['section_subjects']
                    ->where('section_id', $sectionId)
                    ->where('subject_id', $subject['subject_id']);

                return $this->buildEntityMetrics(
                    $subject['subject_name'],
                    $rows->get((string) $subject['subject_id']),
                    (int) $group->sum('expected_grade_count'),
                    (int) $group->unique('section_id')->sum('expected_student_count')
                );
            })
            ->unique('label')
            ->values()
            ->all();

        return $this->formatChartData($entities);
    }

    private function baseGradeQuery(string $schoolYear, string $gradingPeriod)
    {
        $query = DB::table('grades as g')
            ->join('section_subject as ss', 'ss.id', '=', 'g.section_subject_id')
            ->join('section_models as sec', 'sec.id', '=', 'ss.section_model_id')
            ->join('subject_models as sub', 'sub.id', '=', 'ss.subject_model_id');

        $this->applySchoolYearFilter($query, $schoolYear, 'sec');

        if ($gradingPeriod !== self::ALL_OPTION) {
            $query->where('g.grading_period', $gradingPeriod);
        }

        return $query;
    }

    private function expectedContext(string $schoolYear, string $gradingPeriod): array
    {
        $sectionSubjects = $this->sectionSubjectRows($schoolYear);
        $sectionStudentCounts = $this->studentCountMap($sectionSubjects->pluck('section_id')->unique()->values()->all());
        $periodCounts = $this->sectionSubjectPeriodCountMap(
            $sectionSubjects->pluck('section_subject_id')->all(),
            $gradingPeriod
        );

        $sectionSubjects = $sectionSubjects
            ->map(function (array $sectionSubject) use ($sectionStudentCounts, $periodCounts): array {
                $expectedStudentCount = (int) ($sectionStudentCounts[$sectionSubject['section_id']] ?? 0);
                $periodCount = (int) ($periodCounts[$sectionSubject['section_subject_id']] ?? 0);

                $sectionSubject['expected_student_count'] = $expectedStudentCount;
                $sectionSubject['expected_grade_count'] = $expectedStudentCount * $periodCount;

                return $sectionSubject;
            })
            ->values();

        $sectionStudentScope = $sectionSubjects
            ->unique('section_id')
            ->mapWithKeys(static function (array $section): array {
                return [(int) $section['section_id'] => (int) $section['expected_student_count']];
            })
            ->all();

        return [
            'section_subjects' => $sectionSubjects,
            'section_student_counts' => $sectionStudentScope,
            'total_expected_students' => array_sum($sectionStudentScope),
            'total_expected_grade_count' => (int) $sectionSubjects->sum('expected_grade_count'),
        ];
    }

    private function sectionSubjectRows(string $schoolYear)
    {
        return $this->applySchoolYearFilter(
                DB::table('section_subject as ss')
                    ->join('section_models as sec', 'sec.id', '=', 'ss.section_model_id')
                    ->join('subject_models as sub', 'sub.id', '=', 'ss.subject_model_id')
                    ->select(
                        'ss.id as section_subject_id',
                        'sec.id as section_id',
                        'sec.grade_level',
                        'sec.section_rank',
                        'sec.section_name',
                        'sub.id as subject_id',
                        'sub.subject_name'
                    ),
                $schoolYear,
                'sec'
            )
            ->orderBy('sec.grade_level')
            ->orderByRaw('CASE WHEN sec.section_rank IS NULL THEN 1 ELSE 0 END')
            ->orderBy('sec.section_rank')
            ->orderBy('sub.subject_name')
            ->get()
            ->map(function ($row): array {
                return [
                    'section_subject_id' => (int) $row->section_subject_id,
                    'section_id' => (int) $row->section_id,
                    'grade_level' => (int) $row->grade_level,
                    'section_rank' => $row->section_rank === null ? null : (int) $row->section_rank,
                    'section_name' => (string) ($row->section_name ?? ''),
                    'subject_id' => (int) $row->subject_id,
                    'subject_name' => (string) ($row->subject_name ?? 'Unknown Subject'),
                ];
            })
            ->values();
    }

    private function studentCountMap(array $sectionIds): array
    {
        if (empty($sectionIds)) {
            return [];
        }

        return DB::table('student_models')
            ->select('section_id', DB::raw('COUNT(*) as total_students'))
            ->whereIn('section_id', $sectionIds)
            ->groupBy('section_id')
            ->pluck('total_students', 'section_id')
            ->map(static fn ($count) => (int) $count)
            ->all();
    }

    private function sectionSubjectPeriodCountMap(array $sectionSubjectIds, string $gradingPeriod): array
    {
        if (empty($sectionSubjectIds)) {
            return [];
        }

        if ($gradingPeriod !== self::ALL_OPTION) {
            return collect($sectionSubjectIds)
                ->mapWithKeys(static fn ($id): array => [(int) $id => 1])
                ->all();
        }

        return DB::table('grades')
            ->select('section_subject_id', DB::raw('COUNT(DISTINCT grading_period) as period_count'))
            ->whereIn('section_subject_id', $sectionSubjectIds)
            ->groupBy('section_subject_id')
            ->pluck('period_count', 'section_subject_id')
            ->map(static fn ($count) => (int) $count)
            ->all();
    }

    private function buildEntityMetrics(
        string $label,
        $stats,
        int $expectedGradeCount,
        int $expectedStudentCount
    ): array {
        $computedGradeCount = (int) ($stats?->computed_grade_count ?? 0);
        $coveredStudentCount = (int) ($stats?->covered_student_count ?? 0);
        $passCount = (int) ($stats?->pass_count ?? 0);
        $averageGrade = $stats?->average_grade === null
            ? null
            : round((float) $stats->average_grade, self::DECIMALS);
        $coverageRate = $this->rate($computedGradeCount, $expectedGradeCount);
        $passRate = $this->rate($passCount, $computedGradeCount);
        $lowCoverage = $computedGradeCount > 0
            && (
                ($coverageRate !== null && $coverageRate < self::LOW_COVERAGE_THRESHOLD)
                || $coveredStudentCount < self::LOW_SAMPLE_SIZE_THRESHOLD
            );

        return [
            'label' => $label,
            'average_grade' => $averageGrade,
            'pass_rate' => $passRate,
            'coverage_rate' => $coverageRate,
            'computed_grade_count' => $computedGradeCount,
            'expected_grade_count' => $expectedGradeCount,
            'covered_student_count' => $coveredStudentCount,
            'expected_student_count' => $expectedStudentCount,
            'low_coverage' => $lowCoverage,
            'warning' => $this->warningText(
                $coverageRate,
                $coveredStudentCount,
                $expectedGradeCount,
                $computedGradeCount
            ),
        ];
    }

    private function formatChartData(array $entities): array
    {
        $collection = collect($entities);
        $visibleEntities = $collection
            ->filter(static fn (array $entity) => $entity['average_grade'] !== null)
            ->values();

        return [
            'labels' => $visibleEntities->pluck('label')->all(),
            'values' => $visibleEntities->pluck('average_grade')->all(),
            'pass_rates' => $visibleEntities->pluck('pass_rate')->all(),
            'coverage_rates' => $visibleEntities->pluck('coverage_rate')->all(),
            'computed_counts' => $visibleEntities->pluck('computed_grade_count')->all(),
            'expected_counts' => $visibleEntities->pluck('expected_grade_count')->all(),
            'covered_students' => $visibleEntities->pluck('covered_student_count')->all(),
            'expected_students' => $visibleEntities->pluck('expected_student_count')->all(),
            'low_coverage_flags' => $visibleEntities->pluck('low_coverage')->all(),
            'warnings' => $collection
                ->filter(static fn (array $entity) => $entity['warning'] !== null)
                ->map(static fn (array $entity) => $entity['label'] . ': ' . $entity['warning'])
                ->values()
                ->all(),
        ];
    }

    private function emptyChart(): array
    {
        return [
            'labels' => [],
            'values' => [],
            'pass_rates' => [],
            'coverage_rates' => [],
            'computed_counts' => [],
            'expected_counts' => [],
            'covered_students' => [],
            'expected_students' => [],
            'low_coverage_flags' => [],
            'warnings' => [],
        ];
    }

    private function applySchoolYearFilter($query, string $schoolYear, string $tableAlias)
    {
        $range = $this->parseSchoolYear($schoolYear);
        if ($range === null) {
            return $query;
        }

        return $query
            ->where($tableAlias . '.start_year', $range['start_year'])
            ->where($tableAlias . '.end_year', $range['end_year']);
    }

    private function normalizeSchoolYear(string $selectedSchoolYear, array $options): string
    {
        $validValues = collect($options)->pluck('value')->all();
        if (in_array($selectedSchoolYear, $validValues, true)) {
            return $selectedSchoolYear;
        }

        $latestSpecific = collect($options)
            ->pluck('value')
            ->first(static fn ($value) => $value !== self::ALL_OPTION);

        return $latestSpecific ?: self::ALL_OPTION;
    }

    private function normalizeGradingPeriod($selectedGradingPeriod, string $schoolYear): string
    {
        $validValues = array_merge(self::SPECIFIC_GRADING_PERIODS, [self::ALL_OPTION]);
        if (is_string($selectedGradingPeriod) && in_array($selectedGradingPeriod, $validValues, true)) {
            return $selectedGradingPeriod;
        }

        return $this->latestAvailableGradingPeriod($schoolYear);
    }

    private function normalizeGradeLevel($selectedGradeLevel, array $options): ?int
    {
        $validValues = collect($options)
            ->pluck('value')
            ->map(static fn ($value) => (int) $value)
            ->all();

        $normalized = filter_var($selectedGradeLevel, FILTER_VALIDATE_INT);
        if ($normalized !== false && in_array($normalized, $validValues, true)) {
            return (int) $normalized;
        }

        return empty($validValues) ? null : (int) $validValues[0];
    }

    private function normalizeSectionId($selectedSectionId, array $options): ?int
    {
        $validValues = collect($options)
            ->pluck('value')
            ->map(static fn ($value) => (int) $value)
            ->all();

        $normalized = filter_var($selectedSectionId, FILTER_VALIDATE_INT);
        if ($normalized !== false && in_array($normalized, $validValues, true)) {
            return (int) $normalized;
        }

        return empty($validValues) ? null : (int) $validValues[0];
    }

    private function parseSchoolYear(string $schoolYear): ?array
    {
        if ($schoolYear === '' || $schoolYear === self::ALL_OPTION) {
            return null;
        }

        if (!preg_match('/^(?<start>\d{4})-(?<end>\d{4})$/', $schoolYear, $matches)) {
            return null;
        }

        return [
            'start_year' => (int) $matches['start'],
            'end_year' => (int) $matches['end'],
        ];
    }

    private function formatSchoolYearValue(int $startYear, int $endYear): string
    {
        return $startYear . '-' . $endYear;
    }

    private function schoolYearLabel(string $schoolYear): string
    {
        return $schoolYear === self::ALL_OPTION
            ? 'All School Years'
            : 'SY ' . $schoolYear;
    }

    private function gradingPeriodLabel(string $gradingPeriod): string
    {
        return $gradingPeriod === self::ALL_OPTION
            ? 'All Grading Period'
            : $gradingPeriod . ' Grading';
    }

    private function latestAvailableGradingPeriod(string $schoolYear): string
    {
        $latest = $this->baseGradeQuery($schoolYear, self::ALL_OPTION)
            ->select('g.grading_period as grading_period')
            ->distinct()
            ->orderByRaw($this->gradingPeriodOrderSql('g.grading_period') . ' DESC')
            ->value('grading_period');

        if (is_string($latest) && in_array($latest, self::SPECIFIC_GRADING_PERIODS, true)) {
            return $latest;
        }

        return self::SPECIFIC_GRADING_PERIODS[0];
    }

    private function gradingPeriodOrderSql(string $column): string
    {
        return "CASE {$column}
            WHEN '1st' THEN 1
            WHEN '2nd' THEN 2
            WHEN '3rd' THEN 3
            WHEN '4th' THEN 4
            ELSE 0
        END";
    }

    private function rate(int $numerator, int $denominator): ?float
    {
        if ($denominator <= 0) {
            return null;
        }

        return round(($numerator / $denominator) * 100, self::DECIMALS);
    }

    private function warningText(
        ?float $coverageRate,
        int $coveredStudentCount,
        int $expectedGradeCount,
        int $computedGradeCount
    ): ?string {
        if ($expectedGradeCount > 0 && $computedGradeCount === 0) {
            return 'No computed grades yet.';
        }

        if ($computedGradeCount <= 0) {
            return null;
        }

        $coverageIsLow = $coverageRate !== null && $coverageRate < self::LOW_COVERAGE_THRESHOLD;
        $sampleIsLow = $coveredStudentCount < self::LOW_SAMPLE_SIZE_THRESHOLD;

        if ($coverageIsLow && $sampleIsLow) {
            return 'Low coverage: below 70% coverage and fewer than 10 students.';
        }

        if ($coverageIsLow) {
            return 'Low coverage: below 70% of expected grades are computed.';
        }

        if ($sampleIsLow) {
            return 'Low coverage: fewer than 10 students are represented.';
        }

        return null;
    }

    private function formatSectionLabel($section): string
    {
        $parts = [];

        if (!empty($section->section_rank)) {
            $parts[] = 'Section ' . $section->section_rank;
        }

        if (!empty($section->section_name)) {
            $parts[] = $section->section_name;
        }

        return implode(' - ', $parts) ?: 'Section ' . $section->id;
    }
}
