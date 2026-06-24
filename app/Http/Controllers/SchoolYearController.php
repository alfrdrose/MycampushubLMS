<?php

namespace App\Http\Controllers;

use App\Models\SectionModel;
use App\Models\SchoolYear;
use App\Services\SectionStudentRoster;
use App\Services\SchoolYearPromotionService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class SchoolYearController extends Controller
{
    public function __construct(
        private readonly SchoolYearPromotionService $promotionService,
        private readonly SectionStudentRoster $sectionStudentRoster
    ) {
    }

    public function index(Request $request)
    {
        return $this->renderPage($request);
    }

    public function store(Request $request)
    {
        $schoolYears = SchoolYear::query()
            ->orderBy('start_year')
            ->orderBy('end_year')
            ->get();

        $latestSchoolYear = $schoolYears->last();

        $validator = Validator::make($request->all(), [
            'start_year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'end_year' => ['required', 'integer', 'min:2001', 'max:2101'],
        ], [
            'start_year.required' => 'Enter the starting year for the new school year.',
            'end_year.required' => 'Enter the ending year for the new school year.',
        ]);

        $validator->after(function ($validator) use ($request, $latestSchoolYear): void {
            $startYear = (int) $request->input('start_year');
            $endYear = (int) $request->input('end_year');

            if ($endYear !== $startYear + 1) {
                $validator->errors()->add('end_year', 'The ending year must be exactly one year after the starting year.');
            }

            $exists = SchoolYear::query()
                ->where('start_year', $startYear)
                ->where('end_year', $endYear)
                ->exists();

            if ($exists) {
                $validator->errors()->add('start_year', 'This school year already exists.');
            }

            if ($latestSchoolYear && $startYear !== (int) $latestSchoolYear->end_year) {
                $validator->errors()->add(
                    'start_year',
                    'Additions must continue directly after ' . $latestSchoolYear->label . '.'
                );
            }
        });

        if ($validator->fails()) {
            $exception = new ValidationException($validator);
            $exception->errorBag = 'schoolYearStore';

            throw $exception;
        }

        $validated = $validator->validated();

        $schoolYear = SchoolYear::create([
            'start_year' => (int) $validated['start_year'],
            'end_year' => (int) $validated['end_year'],
            'is_active' => ! SchoolYear::query()->active()->exists(),
            'teacher_audit_enabled' => true,
        ]);

        return redirect()
            ->route('admin.school-year', ['selected_school_year' => $schoolYear->id])
            ->with('success', 'School year ' . $schoolYear->label . ' added successfully.');
    }

    public function storeDecade(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'target_decade' => ['required', 'integer', 'min:2000', 'max:2090'],
            'selected_decade' => ['nullable', 'integer', 'min:1990', 'max:2090'],
        ], [
            'target_decade.required' => 'Choose a decade to add.',
        ]);

        $validator->after(function ($validator) use ($request): void {
            $targetDecade = (int) $request->input('target_decade');

            if ($targetDecade % 10 !== 0) {
                $validator->errors()->add('target_decade', 'The selected decade is invalid.');
                return;
            }

            $hasYearsInTargetDecade = SchoolYear::query()
                ->whereBetween('start_year', [$targetDecade, $targetDecade + 9])
                ->exists();

            if ($hasYearsInTargetDecade) {
                $validator->errors()->add('target_decade', 'That decade has already been seeded.');
            }
        });

        if ($validator->fails()) {
            $exception = new ValidationException($validator);
            $exception->errorBag = 'schoolYearDecadeStore';

            throw $exception;
        }

        $validated = $validator->validated();
        $targetDecade = (int) $validated['target_decade'];
        $hasActiveSchoolYear = SchoolYear::query()->active()->exists();
        $firstCreatedSchoolYear = null;

        for ($startYear = $targetDecade; $startYear <= $targetDecade + 9; $startYear++) {
            $schoolYear = SchoolYear::create([
                'start_year' => $startYear,
                'end_year' => $startYear + 1,
                'is_active' => ! $hasActiveSchoolYear && $firstCreatedSchoolYear === null,
                'teacher_audit_enabled' => true,
            ]);

            $firstCreatedSchoolYear ??= $schoolYear;
        }

        return redirect()
            ->route('admin.school-year', ['selected_school_year' => $firstCreatedSchoolYear?->id])
            ->with('success', 'School years for the ' . $targetDecade . 's were added successfully.');
    }

    public function activate(Request $request, SchoolYear $schoolYear)
    {
        $schoolYear->activate();

        return redirect()
            ->route('admin.school-year', ['selected_school_year' => $schoolYear->id])
            ->with('success', $schoolYear->label . ' is now the active school year.');
    }

    public function toggleAudit(Request $request, SchoolYear $schoolYear)
    {
        $schoolYear->toggleTeacherAudit();

        $redirectSelectedYear = $schoolYear->is_archived
            ? $this->defaultSelectedSchoolYearId()
            : $schoolYear->id;

        $redirect = redirect()->route('admin.school-year', array_filter([
            'selected_school_year' => $redirectSelectedYear,
        ]));

        return $redirect->with(
            'success',
            'Teacher audit mode ' . ($schoolYear->fresh()->teacher_audit_enabled ? 'enabled' : 'disabled')
            . ' for ' . $schoolYear->label . '.'
        );
    }

    public function archive(Request $request, SchoolYear $schoolYear)
    {
        if ($schoolYear->is_active) {
            return redirect()
                ->route('admin.school-year', ['selected_school_year' => $schoolYear->id])
                ->with('error', 'The active school year cannot be archived.');
        }

        if ($schoolYear->is_archived) {
            return redirect()
                ->route('admin.school-year')
                ->with('error', $schoolYear->label . ' is already archived.');
        }

        $schoolYear->archive();

        $fallbackSchoolYearId = $this->defaultSelectedSchoolYearId();

        return redirect()
            ->route('admin.school-year', array_filter([
                'selected_school_year' => $fallbackSchoolYearId,
            ]))
            ->with('success', $schoolYear->label . ' was moved to the archive.');
    }

    public function previewPromotions(Request $request)
    {
        $validated = $this->validatePromotionRequest($request);
        $sourceSchoolYear = SchoolYear::findOrFail($validated['source_school_year_id']);
        $targetSchoolYear = SchoolYear::findOrFail($validated['target_school_year_id']);
        $sourceSection = ! empty($validated['source_section_id'])
            ? SectionModel::findOrFail($validated['source_section_id'])
            : null;
        $selectedSchoolYear = $this->selectedSchoolYearFromPayload($validated, $targetSchoolYear);

        $preview = $this->promotionService->preview(
            $sourceSchoolYear,
            $targetSchoolYear,
            $validated['actions'] ?? [],
            $sourceSection,
            $validated['target_section_ids'] ?? []
        );

        return $this->renderPage($request, [
            'selectedSchoolYear' => $selectedSchoolYear,
            'promotionPreview' => $preview,
            'promotionForm' => $validated,
        ]);
    }

    public function executePromotions(Request $request)
    {
        $validated = $this->validatePromotionRequest($request);
        $sourceSchoolYear = SchoolYear::findOrFail($validated['source_school_year_id']);
        $targetSchoolYear = SchoolYear::findOrFail($validated['target_school_year_id']);
        $sourceSection = ! empty($validated['source_section_id'])
            ? SectionModel::findOrFail($validated['source_section_id'])
            : null;
        $selectedSchoolYear = $this->selectedSchoolYearFromPayload($validated, $targetSchoolYear);

        try {
            $this->promotionService->execute(
                $sourceSchoolYear,
                $targetSchoolYear,
                $validated['actions'] ?? [],
                false,
                $sourceSection,
                $validated['target_section_ids'] ?? []
            );
        } catch (ValidationException $exception) {
            return $this->renderPage($request, [
                'selectedSchoolYear' => $selectedSchoolYear,
                'promotionPreview' => $this->promotionService->preview(
                    $sourceSchoolYear,
                    $targetSchoolYear,
                    $validated['actions'] ?? [],
                    $sourceSection,
                    $validated['target_section_ids'] ?? []
                ),
                'promotionForm' => $validated,
                'promotionExecutionError' => collect($exception->errors())->flatten()->first(),
            ]);
        }

        return redirect()
            ->route('admin.school-year', ['selected_school_year' => $targetSchoolYear->id])
            ->with('success', 'Student promotion completed for ' . $targetSchoolYear->label . '.');
    }

    private function renderPage(Request $request, array $overrides = [])
    {
        $allSchoolYears = SchoolYear::query()
            ->orderBy('start_year')
            ->orderBy('end_year')
            ->get();

        $schoolYears = $allSchoolYears->where('is_archived', false)->values();
        $archivedSchoolYears = $allSchoolYears->where('is_archived', true)->values();

        $selectedSchoolYear = $overrides['selectedSchoolYear']
            ?? $this->resolveSelectedSchoolYear($request, $schoolYears);

        $selectedIndex = $selectedSchoolYear
            ? $schoolYears->search(fn (SchoolYear $schoolYear) => $schoolYear->id === $selectedSchoolYear->id)
            : false;

        $selectedIndex = $selectedIndex === false ? null : $selectedIndex;

        $previousSchoolYear = $selectedIndex !== null && $selectedIndex > 0
            ? $schoolYears->get($selectedIndex - 1)
            : null;

        $nextSchoolYear = $selectedIndex !== null && $selectedIndex < $schoolYears->count() - 1
            ? $schoolYears->get($selectedIndex + 1)
            : null;

        $candidateStartYear = old(
            'start_year',
            $schoolYears->last()?->end_year ?? (int) now()->format('Y')
        );
        $candidateEndYear = old('end_year', (int) $candidateStartYear + 1);
        $sectionOverview = $this->buildSectionOverview($selectedSchoolYear);
        $targetSectionOverview = $this->buildSectionOverview($nextSchoolYear);
        $defaultSourceSectionId = $sectionOverview->first()['id'] ?? null;

        $promotionForm = array_merge([
            'source_school_year_id' => old('source_school_year_id', $selectedSchoolYear?->id),
            'target_school_year_id' => old(
                'target_school_year_id',
                $this->defaultTargetSchoolYearId($schoolYears, $selectedSchoolYear)
            ),
            'selected_school_year_id' => old('selected_school_year_id', $selectedSchoolYear?->id),
            'source_section_id' => old('source_section_id', $defaultSourceSectionId),
            'activate_target_school_year' => false,
            'actions' => [],
            'target_section_ids' => [],
        ], $overrides['promotionForm'] ?? []);

        $validSourceSectionId = $sectionOverview->contains(
            fn (array $section): bool => (int) $section['id'] === (int) ($promotionForm['source_section_id'] ?? 0)
        )
            ? (int) $promotionForm['source_section_id']
            : $defaultSourceSectionId;

        $promotionForm['source_section_id'] = $validSourceSectionId;
        $promotionForm['source_school_year_id'] = $selectedSchoolYear?->id;
        $promotionForm['target_school_year_id'] = $nextSchoolYear?->id;
        $promotionForm['selected_school_year_id'] = $selectedSchoolYear?->id;

        $promotionPreview = $overrides['promotionPreview'] ?? $this->buildDefaultPromotionPreview(
            $selectedSchoolYear,
            $nextSchoolYear,
            $promotionForm
        );

        return view('pages.admin.dashboard.schoolyear', [
            'title' => 'My Campus LMS | School Year',
            'preloader' => false,
            'darkmode' => true,
            'scrollup' => true,
            'banner' => false,
            'sidebar' => true,
            'schoolYears' => $schoolYears,
            'schoolYearPayload' => $schoolYears->map(function (SchoolYear $schoolYear): array {
                return [
                    'id' => $schoolYear->id,
                    'start' => (int) $schoolYear->start_year,
                    'end' => (int) $schoolYear->end_year,
                    'label' => $schoolYear->label,
                    'is_active' => (bool) $schoolYear->is_active,
                    'teacher_audit_enabled' => (bool) $schoolYear->teacher_audit_enabled,
                    'url' => route('admin.school-year', ['selected_school_year' => $schoolYear->id]),
                    'toggle_audit_url' => route('admin.school-year.audit.toggle', $schoolYear),
                    'archive_url' => route('admin.school-year.archive', $schoolYear),
                ];
            })->values(),
            'archivedSchoolYearPayload' => $archivedSchoolYears->map(function (SchoolYear $schoolYear): array {
                return [
                    'id' => $schoolYear->id,
                    'label' => $schoolYear->label,
                    'teacher_audit_enabled' => (bool) $schoolYear->teacher_audit_enabled,
                    'archived_at_label' => $schoolYear->archived_at?->format('M d, Y'),
                    'toggle_audit_url' => route('admin.school-year.audit.toggle', $schoolYear),
                ];
            })->values(),
            'selectedSchoolYear' => $selectedSchoolYear,
            'previousSchoolYear' => $previousSchoolYear,
            'nextSchoolYear' => $nextSchoolYear,
            'candidateStartYear' => (int) $candidateStartYear,
            'candidateEndYear' => (int) $candidateEndYear,
            'promotionForm' => $promotionForm,
            'promotionPreview' => $promotionPreview,
            'promotionExecutionError' => $overrides['promotionExecutionError'] ?? null,
            'promotionActionLabels' => SchoolYearPromotionService::actionLabels(),
            'sectionOverviewPayload' => $sectionOverview,
            'targetSectionPayload' => $targetSectionOverview,
        ]);
    }

    private function resolveSelectedSchoolYear(Request $request, Collection $schoolYears): ?SchoolYear
    {
        $selectedId = $request->query('selected_school_year');
        if (! $selectedId && old('selected_school_year_id')) {
            $selectedId = old('selected_school_year_id');
        }

        if ($selectedId && is_numeric($selectedId)) {
            $selected = $schoolYears->firstWhere('id', (int) $selectedId);
            if ($selected) {
                return $selected;
            }
        }

        return $schoolYears->firstWhere('is_active', true) ?: $schoolYears->last();
    }

    private function defaultTargetSchoolYearId(Collection $schoolYears, ?SchoolYear $selectedSchoolYear): ?int
    {
        if (! $selectedSchoolYear) {
            return null;
        }

        $selectedIndex = $schoolYears->search(fn (SchoolYear $schoolYear) => $schoolYear->id === $selectedSchoolYear->id);
        if ($selectedIndex === false) {
            return null;
        }

        return $schoolYears->get($selectedIndex + 1)?->id;
    }

    private function defaultSelectedSchoolYearId(): ?int
    {
        $visibleSchoolYears = SchoolYear::query()
            ->visible()
            ->orderBy('start_year')
            ->orderBy('end_year')
            ->get();

        return $visibleSchoolYears->firstWhere('is_active', true)?->id
            ?? $visibleSchoolYears->last()?->id;
    }

    private function validatePromotionRequest(Request $request): array
    {
        $validator = Validator::make($request->all(), [
            'source_school_year_id' => ['required', 'integer', 'exists:school_years,id'],
            'target_school_year_id' => ['required', 'integer', 'exists:school_years,id'],
            'selected_school_year_id' => ['nullable', 'integer', 'exists:school_years,id'],
            'source_section_id' => ['nullable', 'integer', 'exists:section_models,id'],
            'activate_target_school_year' => ['nullable', 'boolean'],
            'actions' => ['sometimes', 'array'],
            'actions.*' => ['nullable', 'in:' . implode(',', array_keys(SchoolYearPromotionService::actionLabels()))],
            'target_section_ids' => ['sometimes', 'array'],
            'target_section_ids.*' => ['nullable', 'integer', 'exists:section_models,id'],
        ]);

        $validator->after(function ($validator) use ($request): void {
            if ((int) $request->input('source_school_year_id') === (int) $request->input('target_school_year_id')) {
                $validator->errors()->add('target_school_year_id', 'Choose a different target school year.');
            }

            if ($request->filled('source_section_id')) {
                $section = SectionModel::find((int) $request->input('source_section_id'));

                if (! $section || (int) $section->school_year_id !== (int) $request->input('source_school_year_id')) {
                    $validator->errors()->add('source_section_id', 'Choose a section that belongs to the selected source school year.');
                }
            }

            foreach ($request->input('target_section_ids', []) as $studentId => $sectionId) {
                if ($sectionId === null || $sectionId === '') {
                    continue;
                }

                $section = SectionModel::find((int) $sectionId);

                if (! $section || (int) $section->school_year_id !== (int) $request->input('target_school_year_id')) {
                    $validator->errors()->add(
                        'target_section_ids.' . $studentId,
                        'Choose a target section that belongs to the target school year.'
                    );
                }
            }
        });

        if ($validator->fails()) {
            $exception = new ValidationException($validator);
            $exception->errorBag = 'promotionPreview';

            throw $exception;
        }

        return $validator->validated();
    }

    private function selectedSchoolYearFromPayload(array $validated, SchoolYear $fallback): SchoolYear
    {
        if (! empty($validated['selected_school_year_id'])) {
            return SchoolYear::findOrFail($validated['selected_school_year_id']);
        }

        return $fallback;
    }

    private function buildSectionOverview(?SchoolYear $selectedSchoolYear): Collection
    {
        if (! $selectedSchoolYear) {
            return collect();
        }

        $sections = SectionModel::query()
            ->where('school_year_id', $selectedSchoolYear->id)
            ->with([
                'subjects' => fn ($query) => $query->orderBy('subject_name'),
                'assignedTeachers.teacher' => fn ($query) => $query
                    ->orderBy('last_name')
                    ->orderBy('first_name'),
            ])
            ->orderBy('grade_level')
            ->orderBy('section_rank')
            ->orderBy('section_name')
            ->get();

        return $sections->map(function (SectionModel $section): array {
            $teacherAssignments = $section->assignedTeachers->groupBy('subject_model_id');
            $displayName = trim((string) $section->section_name) !== ''
                ? (string) $section->section_name
                : 'Section ' . ($section->section_rank ?? 'Unassigned');
            $students = $this->sectionStudentRoster
                ->studentsQuery($section)
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get();

            return [
                'id' => $section->id,
                'grade_level' => (int) $section->grade_level,
                'section_name' => $displayName,
                'section_rank' => $section->section_rank !== null ? (int) $section->section_rank : null,
                'label' => 'Grade ' . $section->grade_level . ' - ' . $displayName,
                'students_count' => (int) $students->count(),
                'subjects_count' => (int) $section->subjects->count(),
                'subjects' => $section->subjects->map(function ($subject) use ($teacherAssignments): array {
                    $teacherNames = collect($teacherAssignments->get($subject->id, collect()))
                        ->map(function ($assignment): ?string {
                            $teacher = $assignment->teacher;
                            if (! $teacher) {
                                return null;
                            }

                            return trim($teacher->first_name . ' ' . $teacher->last_name);
                        })
                        ->filter()
                        ->unique()
                        ->values();

                    return [
                        'id' => $subject->id,
                        'name' => $subject->subject_name,
                        'teacher_names' => $teacherNames->all(),
                        'teacher_summary' => $teacherNames->isNotEmpty()
                            ? $teacherNames->implode(', ')
                            : 'No teacher assigned',
                    ];
                })->values()->all(),
                'students' => $students->map(fn ($student): array => [
                    'id' => $student->id,
                    'name' => trim($student->last_name . ', ' . $student->first_name),
                    'grade' => $student->grade ?: (string) $section->grade_level,
                ])->values()->all(),
            ];
        })->values();
    }

    private function buildDefaultPromotionPreview(
        ?SchoolYear $selectedSchoolYear,
        ?SchoolYear $nextSchoolYear,
        array $promotionForm
    ): ?array {
        if (! $selectedSchoolYear || ! $nextSchoolYear || empty($promotionForm['source_section_id'])) {
            return null;
        }

        $sourceSection = SectionModel::query()
            ->where('school_year_id', $selectedSchoolYear->id)
            ->find($promotionForm['source_section_id']);

        if (! $sourceSection) {
            return null;
        }

        return $this->promotionService->preview(
            $selectedSchoolYear,
            $nextSchoolYear,
            $promotionForm['actions'] ?? [],
            $sourceSection,
            $promotionForm['target_section_ids'] ?? []
        );
    }
}
