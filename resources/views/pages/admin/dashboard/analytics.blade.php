@extends('layouts.dashboard')

@section('content')
@php
    $filters = $analytics['filters'];
    $selected = $filters['selected'];
    $summary = $analytics['summary'];
    $charts = $analytics['charts'];
@endphp

<div class="bg-whiteColor dark:bg-darkblack shadow-lg rounded-2xl p-4 sm:p-6 lg:p-8">
    <div class="border-b border-[#D4D4D8] dark:border-[#334155] pb-5">
        <div class="flex flex-col gap-2">
            <h1 class="text-2xl sm:text-3xl font-bold text-[#5E5F60] dark:text-white">
                Analytics
            </h1>
            <p class="text-sm sm:text-base text-[#5E5F60] dark:text-gray-300">
                Monitoring view for computed student grades, scoped to a school year and grading period.
            </p>
        </div>

        <form method="GET"
              action="{{ route('admin.analytics') }}"
              class="mt-5 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-3">
            <label class="flex flex-col gap-1 text-sm text-[#5E5F60] dark:text-gray-300">
                <span>School Year</span>
                <select name="school_year"
                        class="border border-[#ABB7C2] dark:border-[#334155] rounded-lg px-4 py-2 bg-white dark:bg-[#0F1724] dark:text-white cursor-pointer">
                    @foreach ($filters['school_years'] as $option)
                        <option value="{{ $option['value'] }}" @selected($selected['school_year'] === $option['value'])>
                            {{ $option['label'] }}
                        </option>
                    @endforeach
                </select>
            </label>

            <label class="flex flex-col gap-1 text-sm text-[#5E5F60] dark:text-gray-300">
                <span>Grading Period</span>
                <select name="grading_period"
                        class="border border-[#ABB7C2] dark:border-[#334155] rounded-lg px-4 py-2 bg-white dark:bg-[#0F1724] dark:text-white cursor-pointer">
                    @foreach ($filters['grading_periods'] as $option)
                        <option value="{{ $option['value'] }}" @selected($selected['grading_period'] === $option['value'])>
                            {{ $option['label'] }}
                        </option>
                    @endforeach
                </select>
            </label>

            <label class="flex flex-col gap-1 text-sm text-[#5E5F60] dark:text-gray-300">
                <span>Grade Level</span>
                <select name="grade_level"
                        class="border border-[#ABB7C2] dark:border-[#334155] rounded-lg px-4 py-2 bg-white dark:bg-[#0F1724] dark:text-white cursor-pointer"
                        @disabled(empty($filters['grade_levels']))>
                    @forelse ($filters['grade_levels'] as $option)
                        <option value="{{ $option['value'] }}" @selected($selected['grade_level'] === $option['value'])>
                            {{ $option['label'] }}
                        </option>
                    @empty
                        <option value="">No grade levels</option>
                    @endforelse
                </select>
            </label>

            <label class="flex flex-col gap-1 text-sm text-[#5E5F60] dark:text-gray-300">
                <span>Section</span>
                <select name="section_id"
                        class="border border-[#ABB7C2] dark:border-[#334155] rounded-lg px-4 py-2 bg-white dark:bg-[#0F1724] dark:text-white cursor-pointer"
                        @disabled(empty($filters['sections']))>
                    @forelse ($filters['sections'] as $option)
                        <option value="{{ $option['value'] }}" @selected($selected['section_id'] === $option['value'])>
                            {{ $option['label'] }}
                        </option>
                    @empty
                        <option value="">No sections</option>
                    @endforelse
                </select>
            </label>

            <div class="flex items-end gap-3">
                <button type="submit"
                        class="w-full md:w-auto px-4 py-2 rounded-lg border border-[#5F2DED] text-[#5F2DED] hover:bg-[#5F2DED] hover:text-white transition">
                    Apply
                </button>
                <a href="{{ route('admin.analytics') }}"
                   class="w-full md:w-auto px-4 py-2 rounded-lg border border-[#ABB7C2] text-center text-[#5E5F60] dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#111827] transition">
                    Reset
                </a>
            </div>
        </form>

        @if ($selected['is_cumulative'])
            <div class="mt-4 text-xs sm:text-sm">
                <p class="text-[#B45309] dark:text-[#FBBF24]">
                    Cumulative mode blends multiple grading periods. Read it as trend data, not current-period performance.
                </p>
            </div>
        @endif
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-5 gap-4 mt-6">
        <div class="rounded-lg border border-[#E1D6FF] px-4 py-5 bg-[#FCFBFF] dark:bg-[#0B1220]">
            <p class="text-sm text-[#5E5F60] dark:text-gray-300">Overall Average</p>
            <p class="text-3xl font-bold text-[#373737] dark:text-white mt-2">
                {{ $summary['overall_average'] === null ? 'No Data' : number_format($summary['overall_average'], 1) . '%' }}
            </p>
            <p class="text-xs text-[#5E5F60] dark:text-gray-400 mt-2">
                Excludes missing grades.
            </p>
        </div>

        <div class="rounded-lg border border-[#E1D6FF] px-4 py-5 bg-[#FCFBFF] dark:bg-[#0B1220]">
            <p class="text-sm text-[#5E5F60] dark:text-gray-300">Pass Rate</p>
            <p class="text-3xl font-bold text-[#373737] dark:text-white mt-2">
                {{ $summary['pass_rate'] === null ? 'No Data' : number_format($summary['pass_rate'], 1) . '%' }}
            </p>
            <p class="text-xs text-[#5E5F60] dark:text-gray-400 mt-2">
                Passing threshold: 75.
            </p>
        </div>

        <div class="rounded-lg px-4 py-5 bg-[#FCFBFF] dark:bg-[#0B1220] {{ $summary['warning'] ? 'border border-[#F5C16C] dark:border-[#D6A34A] dark:bg-[#171106]' : 'border border-[#E1D6FF]' }}">
            <div class="flex items-center justify-between gap-3">
                <p class="text-sm text-[#5E5F60] dark:text-gray-200">Coverage</p>
                @if ($summary['warning'])
                    <span class="group relative inline-flex items-center" tabindex="0">
                        <svg class="w-4 h-4 shrink-0 text-[#D97706] dark:text-[#FCD34D]" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M12 3 1 21h22L12 3Z"></path>
                            <path d="M12 9v5" stroke="#FFFFFF" stroke-width="2" stroke-linecap="round"></path>
                            <circle cx="12" cy="17" r="1.3" fill="#FFFFFF"></circle>
                        </svg>
                        <span class="sr-only">{{ $summary['warning'] }}</span>
                        <span class="pointer-events-none absolute right-0 top-full z-20 mt-2 w-72 max-w-[80vw] rounded-lg border border-[#F5C16C] bg-[#FFF7E6] px-3 py-2 text-xs leading-5 text-[#8A5A00] shadow-lg opacity-0 invisible transition duration-150 group-hover:visible group-hover:opacity-100 group-focus-within:visible group-focus-within:opacity-100 dark:border-[#D6A34A] dark:bg-[#221707] dark:text-[#F6E3B1]">
                            {{ $summary['warning'] }}
                        </span>
                    </span>
                @endif
            </div>
            <p class="text-3xl font-bold text-[#373737] dark:text-white mt-2">
                {{ $summary['coverage_rate'] === null ? 'N/A' : number_format($summary['coverage_rate'], 1) . '%' }}
            </p>
            <p class="text-xs text-[#5E5F60] dark:text-gray-300 mt-2">
                {{ number_format($summary['computed_grade_count']) }} of {{ number_format($summary['expected_grade_count']) }} expected grade records.
            </p>
        </div>

        <div class="rounded-lg border border-[#E1D6FF] px-4 py-5 bg-[#FCFBFF] dark:bg-[#0B1220]">
            <p class="text-sm text-[#5E5F60] dark:text-gray-300">Students Covered</p>
            <p class="text-3xl font-bold text-[#373737] dark:text-white mt-2">
                {{ number_format($summary['students_covered']) }} / {{ number_format($summary['expected_students']) }}
            </p>
            <p class="text-xs text-[#5E5F60] dark:text-gray-400 mt-2">
                Based on current student rosters in scope.
            </p>
        </div>

        <div class="rounded-lg border border-[#E1D6FF] px-4 py-5 bg-[#FCFBFF] dark:bg-[#0B1220]">
            <p class="text-sm text-[#5E5F60] dark:text-gray-300">Computed Grades</p>
            <p class="text-3xl font-bold text-[#373737] dark:text-white mt-2">
                {{ number_format($summary['computed_grade_count']) }}
            </p>
            <p class="text-xs text-[#5E5F60] dark:text-gray-400 mt-2">
                Across {{ number_format($summary['section_count']) }} sections and {{ number_format($summary['subject_count']) }} subjects.
            </p>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-5 mt-6">
        <div class="border border-[#E2E8F0] dark:border-[#334155] rounded-lg p-4 sm:p-5 bg-[#F8FAFC] dark:bg-[#0B1220]">
            <div class="flex flex-col gap-1 mb-3">
                <div class="flex items-center gap-2">
                    <h2 class="text-lg sm:text-xl font-semibold text-[#373737] dark:text-white">
                        Average by Grade Level
                    </h2>
                    @if (!empty($charts['grade_levels']['warnings']))
                        <span class="group relative inline-flex items-center" tabindex="0">
                            <svg class="w-4 h-4 shrink-0 text-[#D97706] dark:text-[#FCD34D]" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="M12 3 1 21h22L12 3Z"></path>
                                <path d="M12 9v5" stroke="#FFFFFF" stroke-width="2" stroke-linecap="round"></path>
                                <circle cx="12" cy="17" r="1.3" fill="#FFFFFF"></circle>
                            </svg>
                            <span class="sr-only">{{ implode(' ', $charts['grade_levels']['warnings']) }}</span>
                            <span class="pointer-events-none absolute left-0 top-full z-20 mt-2 w-72 max-w-[80vw] rounded-lg border border-[#F5C16C] bg-[#FFF7E6] px-3 py-2 text-xs leading-5 text-[#8A5A00] shadow-lg opacity-0 invisible transition duration-150 group-hover:visible group-hover:opacity-100 group-focus-within:visible group-focus-within:opacity-100 dark:border-[#D6A34A] dark:bg-[#221707] dark:text-[#F6E3B1]">
                                @foreach ($charts['grade_levels']['warnings'] as $warning)
                                    <span class="block">{{ $warning }}</span>
                                @endforeach
                            </span>
                        </span>
                    @endif
                </div>
                <p class="text-sm text-[#5E5F60] dark:text-gray-300">
                    Compare grade-level averages. Hover for pass rate, coverage, and roster details.
                </p>
            </div>

            <div class="w-full overflow-x-auto">
                <div class="w-full max-w-4xl mx-auto min-h-[240px]" data-chart-container="grade-levels">
                    <canvas id="adminGradeLevelChart"></canvas>
                </div>
            </div>

            <p class="text-sm text-[#5E5F60] dark:text-gray-300" data-empty-message="grade-levels">
                No grade records found for the selected scope.
            </p>
        </div>

        <div class="border border-[#E2E8F0] dark:border-[#334155] rounded-lg p-4 sm:p-5 bg-[#F8FAFC] dark:bg-[#0B1220]">
            <div class="flex flex-col gap-1 mb-3">
                <div class="flex items-center gap-2">
                    <h2 class="text-lg sm:text-xl font-semibold text-[#373737] dark:text-white">
                        Average by Section
                    </h2>
                    @if (!empty($charts['sections']['warnings']))
                        <span class="group relative inline-flex items-center" tabindex="0">
                            <svg class="w-4 h-4 shrink-0 text-[#D97706] dark:text-[#FCD34D]" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="M12 3 1 21h22L12 3Z"></path>
                                <path d="M12 9v5" stroke="#FFFFFF" stroke-width="2" stroke-linecap="round"></path>
                                <circle cx="12" cy="17" r="1.3" fill="#FFFFFF"></circle>
                            </svg>
                            <span class="sr-only">{{ implode(' ', $charts['sections']['warnings']) }}</span>
                            <span class="pointer-events-none absolute left-0 top-full z-20 mt-2 w-72 max-w-[80vw] rounded-lg border border-[#F5C16C] bg-[#FFF7E6] px-3 py-2 text-xs leading-5 text-[#8A5A00] shadow-lg opacity-0 invisible transition duration-150 group-hover:visible group-hover:opacity-100 group-focus-within:visible group-focus-within:opacity-100 dark:border-[#D6A34A] dark:bg-[#221707] dark:text-[#F6E3B1]">
                                @foreach ($charts['sections']['warnings'] as $warning)
                                    <span class="block">{{ $warning }}</span>
                                @endforeach
                            </span>
                        </span>
                    @endif
                </div>
                <p class="text-sm text-[#5E5F60] dark:text-gray-300">
                    Sections under {{ $selected['grade_level_label'] }}.
                </p>
            </div>

            <div class="w-full overflow-x-auto">
                <div class="w-full max-w-4xl mx-auto min-h-[240px]" data-chart-container="sections">
                    <canvas id="adminSectionChart"></canvas>
                </div>
            </div>

            <p class="text-sm text-[#5E5F60] dark:text-gray-300" data-empty-message="sections">
                No section averages found for the selected grade level.
            </p>
        </div>

        <div class="border border-[#E2E8F0] dark:border-[#334155] rounded-lg p-4 sm:p-5 bg-[#F8FAFC] dark:bg-[#0B1220]">
            <div class="flex flex-col gap-1 mb-3">
                <div class="flex items-center gap-2">
                    <h2 class="text-lg sm:text-xl font-semibold text-[#373737] dark:text-white">
                        Average by Subject
                    </h2>
                    @if (!empty($charts['subjects']['warnings']))
                        <span class="group relative inline-flex items-center" tabindex="0">
                            <svg class="w-4 h-4 shrink-0 text-[#D97706] dark:text-[#FCD34D]" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="M12 3 1 21h22L12 3Z"></path>
                                <path d="M12 9v5" stroke="#FFFFFF" stroke-width="2" stroke-linecap="round"></path>
                                <circle cx="12" cy="17" r="1.3" fill="#FFFFFF"></circle>
                            </svg>
                            <span class="sr-only">{{ implode(' ', $charts['subjects']['warnings']) }}</span>
                            <span class="pointer-events-none absolute left-0 top-full z-20 mt-2 w-72 max-w-[80vw] rounded-lg border border-[#F5C16C] bg-[#FFF7E6] px-3 py-2 text-xs leading-5 text-[#8A5A00] shadow-lg opacity-0 invisible transition duration-150 group-hover:visible group-hover:opacity-100 group-focus-within:visible group-focus-within:opacity-100 dark:border-[#D6A34A] dark:bg-[#221707] dark:text-[#F6E3B1]">
                                @foreach ($charts['subjects']['warnings'] as $warning)
                                    <span class="block">{{ $warning }}</span>
                                @endforeach
                            </span>
                        </span>
                    @endif
                </div>
                <p class="text-sm text-[#5E5F60] dark:text-gray-300">
                    Subjects under {{ $selected['section_label'] }}.
                </p>
            </div>

            <div class="w-full overflow-x-auto">
                <div class="w-full max-w-4xl mx-auto min-h-[240px]" data-chart-container="subjects">
                    <canvas id="adminSubjectChart"></canvas>
                </div>
            </div>

            <p class="text-sm text-[#5E5F60] dark:text-gray-300" data-empty-message="subjects">
                No subject averages found for the selected section.
            </p>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0"></script>

<script>
(() => {
    const charts = @json($charts);
    const palette = [
        '#5F2DED', '#2864FF', '#0EA5E9', '#16A34A', '#F97316',
        '#F43F5E', '#14B8A6', '#A855F7', '#F59E0B', '#EF4444'
    ];
    const lowCoverageColor = '#F59E0B';
    const instances = {};

    const isDarkMode = () => document.documentElement.classList.contains('dark');
    const labelColor = () => isDarkMode() ? '#E2E8F0' : '#374151';
    const gridColor = () => isDarkMode() ? '#1F2937' : '#E5E7EB';

    const formatValue = (value) => {
        const num = Number(value);
        if (Number.isNaN(num)) {
            return 'N/A';
        }

        return num.toFixed(1);
    };

    const setContainerState = (key, labels) => {
        const container = document.querySelector(`[data-chart-container="${key}"]`);
        const emptyMessage = document.querySelector(`[data-empty-message="${key}"]`);
        const hasData = Array.isArray(labels) && labels.length > 0;

        if (container) {
            container.style.display = hasData ? '' : 'none';
            if (hasData) {
                const desiredHeight = Math.max(240, labels.length * 34);
                container.style.height = `${desiredHeight}px`;
            }
        }

        if (emptyMessage) {
            emptyMessage.style.display = hasData ? 'none' : '';
        }
    };

    const buildChart = (canvasId, key, data) => {
        const labels = Array.isArray(data?.labels) ? data.labels : [];
        const values = Array.isArray(data?.values) ? data.values : [];
        const passRates = Array.isArray(data?.pass_rates) ? data.pass_rates : [];
        const coverageRates = Array.isArray(data?.coverage_rates) ? data.coverage_rates : [];
        const computedCounts = Array.isArray(data?.computed_counts) ? data.computed_counts : [];
        const expectedCounts = Array.isArray(data?.expected_counts) ? data.expected_counts : [];
        const coveredStudents = Array.isArray(data?.covered_students) ? data.covered_students : [];
        const expectedStudents = Array.isArray(data?.expected_students) ? data.expected_students : [];
        const lowCoverageFlags = Array.isArray(data?.low_coverage_flags) ? data.low_coverage_flags : [];
        const canvas = document.getElementById(canvasId);

        setContainerState(key, labels);

        if (!canvas || !labels.length || !values.length) {
            if (instances[key]) {
                instances[key].destroy();
                instances[key] = null;
            }
            return;
        }

        if (instances[key]) {
            instances[key].destroy();
        }

        instances[key] = new Chart(canvas, {
            type: 'bar',
            plugins: [ChartDataLabels],
            data: {
                labels,
                datasets: [{
                    data: values,
                    backgroundColor: labels.map((_, index) => lowCoverageFlags[index]
                        ? lowCoverageColor
                        : palette[index % palette.length]),
                    borderRadius: 6,
                    barThickness: labels.length > 12 ? 10 : 14,
                    maxBarThickness: labels.length > 12 ? 12 : 18
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: {
                    legend: {
                        display: false
                    },
                    datalabels: {
                        anchor: 'end',
                        align: 'end',
                        offset: 6,
                        color: () => labelColor(),
                        font: {
                            size: 11,
                            weight: '600'
                        },
                        formatter: (value) => `${formatValue(value)}%`
                    },
                    tooltip: {
                        callbacks: {
                            label: (context) => {
                                const index = context.dataIndex;
                                const lines = [
                                    `Average: ${formatValue(context.parsed.x)}%`,
                                    `Pass rate: ${formatValue(passRates[index])}%`,
                                    `Coverage: ${formatValue(coverageRates[index])}%`,
                                    `Students covered: ${coveredStudents[index] ?? 0} / ${expectedStudents[index] ?? 0}`,
                                    `Computed grades: ${computedCounts[index] ?? 0} / ${expectedCounts[index] ?? 0}`,
                                ];

                                if (lowCoverageFlags[index]) {
                                    lines.push('Coverage attention applies to this bar.');
                                }

                                return lines;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        min: 0,
                        max: 100,
                        grid: {
                            color: () => gridColor()
                        },
                        ticks: {
                            color: () => labelColor(),
                            callback: (value) => `${value}%`,
                            maxTicksLimit: 6
                        }
                    },
                    y: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: () => labelColor(),
                            autoSkip: false,
                            callback: function (value) {
                                const label = this.getLabelForValue(value);
                                return label.length > 26 ? `${label.slice(0, 26)}...` : label;
                            }
                        }
                    }
                },
                layout: {
                    padding: {
                        right: 56
                    }
                }
            }
        });
    };

    const renderCharts = () => {
        buildChart('adminGradeLevelChart', 'grade-levels', charts.grade_levels);
        buildChart('adminSectionChart', 'sections', charts.sections);
        buildChart('adminSubjectChart', 'subjects', charts.subjects);
    };

    renderCharts();

    const observer = new MutationObserver(() => {
        renderCharts();
    });

    observer.observe(document.documentElement, {
        attributes: true,
        attributeFilter: ['class']
    });
})();
</script>
@endsection
