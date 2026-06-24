@extends('pages.teacher.layout')

@section('content')
    <div class="bg-[#FFFFFF] dark:bg-[#0F1724] dark:border-[#5F2DED] shadow-lg border p-8 rounded-lg">
        <div class="border-b border-[#D4D4D8] dark:border-[#5F2DED]">
            <h1 class="text-3xl font-bold text-[#5E5F60] dark:text-white">
                {{ $subject->subject_name }}
            </h1>
            <p class="mb-4 text-[#5E5F60] dark:text-white">
                Section {{ $section->section_rank }} - {{ $section->section_name }}
            </p>
            <x-teacher-nav active="analytics" :sectionId="$section->id" :subjectId="$subject->id"/>
        </div>

        <div class="mt-4 flex flex-col lg:flex-row lg:items-center gap-3">
            <h2 class="text-2xl font-semibold text-[#373737] dark:text-white">
                Classwork Performance Analytics
            </h2>
            <form method="GET"
                  action="{{ route('teacher.analytics', [$section->id, $subject->id]) }}"
                  class="flex flex-col sm:flex-row flex-wrap items-start sm:items-center gap-2 text-sm w-full lg:w-auto"
                  >
                <label class="flex items-center gap-2 text-[#5E5F60] dark:text-gray-300">
                    <span>Grading:</span>
                    <select name="grading_period"
                            data-grading-select
                            class="w-full sm:w-[80px] lg:px-2 px-8 border cursor-pointer border-[#ABB7C2] dark:bg-[#0F1724] dark:text-white rounded-lg  py-0.5 text-sm">
                        <option value="all" @selected($gradingPeriod === 'all')>All</option>
                        @foreach (['1st','2nd','3rd','4th'] as $period)
                            <option value="{{ $period }}" @selected($gradingPeriod === $period)>
                                {{ $period }}
                            </option>
                        @endforeach
                    </select>
                </label>
                <label class="flex items-center gap-2 text-[#5E5F60] dark:text-gray-300">
                    <span>Top:</span>
                    <select name="top"
                            data-top-select
                            class="w-full sm:w-[80px] cursor-pointer border border-[#ABB7C2] dark:bg-[#0F1724] dark:text-white rounded-lg lg:px-2 px-8 py-0.5 text-sm">
                        <option value="all" @selected($topSelection === 'all')>All</option>
                        @foreach ([5,10,20] as $top)
                            <option value="{{ $top }}" @selected((string) $topSelection === (string) $top)>
                                {{ $top }}
                            </option>
                        @endforeach
                    </select>
                </label>
                <label class="flex items-center gap-2 text-[#5E5F60] dark:text-gray-300">
                    <span>Search:</span>
                    <input type="text"
                           name="tag_search"
                           data-tag-search
                           class="w-full sm:w-[120px] border border-[#ABB7C2] dark:bg-[#0F1724] dark:text-white rounded-lg px-2 py-0.5 text-sm" />
                </label>
            </form>
        </div>

        <div class="mt-4">
            <div class="border border-[#E2E8F0] dark:border-[#334155] rounded-lg p-4 bg-[#F8FAFC] dark:bg-[#0B1220]">
                <h3 class="text-xl font-semibold text-[#373737] dark:text-white mb-3">
                    Average Earned Points by Tag
                </h3>
                <div class="w-full max-w-2xl mx-auto min-h-[240px] sm:min-h-[280px] relative overflow-x-auto" data-chart-container="tag-average">
                    <canvas id="classworkTagAverageBar"></canvas>
                </div>
                <p class="text-sm text-[#5E5F60] dark:text-gray-300" data-empty-message="tag-average">
                    No graded classwork yet.
                </p>
            </div>
        </div>

        <div class="mt-4">
            <div class="border border-[#E2E8F0] dark:border-[#334155] rounded-lg p-4 bg-[#F8FAFC] dark:bg-[#0B1220]">
                <h3 class="text-xl font-semibold text-[#373737] dark:text-white mb-3">
                    Classwork Type
                </h3>
                <div class="w-full min-h-[240px] sm:min-h-[280px] max-w-2xl mx-auto" data-chart-container="classwork-type">
                    <canvas id="classworkTypeChart"></canvas>
                </div>
                <p class="text-sm text-[#5E5F60] dark:text-gray-300" data-empty-message="classwork-type">
                    No graded classwork yet.
                </p>
            </div>
        </div>

        <div class="mt-6 flex flex-col sm:flex-row sm:justify-end gap-2">

            <form method="POST" action="{{ route('teacher.analytics.seed', [$section->id, $subject->id]) }}">
                @csrf
                <button type="submit"
                    class="px-4 py-2 text-sm rounded-lg border border-[#5F2DED] text-[#5F2DED] hover:bg-[#5F2DED] hover:text-white transition">
                    Generate Test Data
                </button>
            </form>
            <form method="POST" action="{{ route('teacher.analytics.clear', [$section->id, $subject->id]) }}">
                @csrf
                @method('DELETE')
                <button type="submit"
                    class="px-4 py-2 text-sm rounded-lg border border-[#B83330] text-[#B83330] hover:bg-[#B83330] hover:text-white transition">
                    Clear Test Data
                </button>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0"></script>
<script>
    (() => {
        const analyticsData = @json($analyticsData);
        const initialPeriod = @json($gradingPeriod);
        const initialTop = @json($topSelection);

        const palette = [
            '#5F2DED',
            '#2864FF',
            '#0EA5E9',
            '#16A34A',
            '#F97316',
            '#F43F5E',
            '#14B8A6',
            '#A855F7',
            '#F59E0B',
            '#EF4444'
        ];

        const isDark = document.documentElement.classList.contains('dark')
            || document.body.classList.contains('dark');
        const labelColor = isDark ? '#E2E8F0' : '#5E5F60';
        const gridColor = isDark ? '#1F2937' : '#E2E8F0';

        const buildBarChart = (canvasId) => {
            const ctx = document.getElementById(canvasId);
            if (!ctx) {
                return null;
            }

            return new Chart(ctx, {
                type: 'bar',
                plugins: [ChartDataLabels],
                data: {
                    labels: [],
                    datasets: [{
                        data: [],
                        backgroundColor: [],
                        borderRadius: 6,
                        barThickness: 14,
                        maxBarThickness: 18
                    }]
                },
                options: {
                    maintainAspectRatio: false,
                    indexAxis: 'y',
                    responsive: true,
                    plugins: {
                        legend: {
                            display: false
                        },
                        datalabels: {
                            anchor: 'end',
                            align: 'end',
                            offset: 6,
                            color: labelColor,
                            font: {
                                size: 11,
                                weight: '600'
                            },
                            clip: false,
                            clamp: true,
                            formatter: (value, context) => {
                                const data = context.chart.data.datasets[0].data || [];
                                const total = data.reduce((sum, item) => sum + Number(item || 0), 0) || 1;
                                const percent = (Number(value || 0) / total) * 100;
                                return `${percent.toFixed(2)}%`;
                            },
                            display: true
                        },
                        tooltip: {
                            callbacks: {
                                label: (context) => {
                                    const value = Number(context.parsed.x ?? 0);
                                    return `${context.label}: ${value.toFixed(2)} avg points`;
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                color: gridColor
                            },
                            ticks: {
                                color: labelColor,
                                maxTicksLimit: 6
                            }
                        },
                        y: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                color: labelColor,
                                autoSkip: false,
                                callback: function(value) {
                                    const label = this.getLabelForValue(value);
                                    return label.length > 18 ? label.slice(0, 18) + '…' : label;
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

        const getPeriodData = (key, period) => {
            const group = analyticsData[key] || {};
            const periodData = group[period] || { labels: [], values: [] };
            const labels = Array.isArray(periodData.labels) ? periodData.labels : [];
            const values = Array.isArray(periodData.values) ? periodData.values : [];
            return { labels, values };
        };

        const getPeriodRows = (key, period) => {
            const group = analyticsData[key] || {};
            const rows = group[period];
            return Array.isArray(rows) ? rows : [];
        };

        const applyTopLimit = (data, topSelection) => {
            if (topSelection === 'all') {
                return data;
            }

            const limit = Number.parseInt(topSelection, 10);
            if (!Number.isFinite(limit) || limit <= 0) {
                return data;
            }

            return {
                labels: data.labels.slice(0, limit),
                values: data.values.slice(0, limit)
            };
        };

        const applyTagSearch = (data, query) => {
            if (!query) {
                return data;
            }

            const lowerQuery = query.toLowerCase();
            const filtered = {
                labels: [],
                values: []
            };

            data.labels.forEach((label, index) => {
                const text = String(label || '').toLowerCase();
                if (text.includes(lowerQuery)) {
                    filtered.labels.push(label);
                    filtered.values.push(data.values[index]);
                }
            });

            return filtered;
        };

        const buildTypeDataFromTags = (rows, query) => {
            if (!query) {
                return null;
            }

            const lowerQuery = query.toLowerCase();
            const totals = new Map();

            rows.forEach((row) => {
                const tagText = String(row.tag || '').toLowerCase();
                if (!tagText.includes(lowerQuery)) {
                    return;
                }

                const typeLabel = row.type || 'Unknown';
                const value = Number(row.value || 0);
                totals.set(typeLabel, (totals.get(typeLabel) || 0) + value);
            });

            const entries = Array.from(totals.entries())
                .sort((a, b) => b[1] - a[1]);

            return {
                labels: entries.map(([label]) => label),
                values: entries.map(([, value]) => value)
            };
        };

        const updateChart = (chart, canvasId, labels, values, containerKey) => {
            if (!chart) {
                return;
            }

            const ctx = document.getElementById(canvasId);
            const container = document.querySelector(`[data-chart-container="${containerKey}"]`);
            const emptyMessage = document.querySelector(`[data-empty-message="${containerKey}"]`);
            const hasData = labels.length > 0 && values.length > 0;

            if (container) {
                container.style.display = hasData ? '' : 'none';
            }
            if (emptyMessage) {
                emptyMessage.style.display = hasData ? 'none' : '';
            }

            if (hasData && container) {
                const baseHeight = 240;
                const perItemHeight = 24;
                const desiredHeight = Math.max(baseHeight, labels.length * perItemHeight);
                container.style.height = `${desiredHeight}px`;
            }

            const compactBars = labels.length > 12;

            chart.data.labels = labels;
            chart.data.datasets[0].data = values;
            chart.data.datasets[0].backgroundColor = labels.map((_, index) => palette[index % palette.length]);
            chart.data.datasets[0].barThickness = compactBars ? 10 : 14;
            chart.data.datasets[0].maxBarThickness = compactBars ? 12 : 18;
            chart.update();
        };

        const tagChart = buildBarChart('classworkTagAverageBar');
        const typeChart = buildBarChart('classworkTypeChart');
        const gradingSelect = document.querySelector('[data-grading-select]');
        const topSelect = document.querySelector('[data-top-select]');
        const tagSearch = document.querySelector('[data-tag-search]');

        const syncUrl = (period, top) => {
            if (!gradingSelect || !topSelect) {
                return;
            }

            const url = new URL(window.location.href);
            url.searchParams.set('grading_period', period);
            url.searchParams.set('top', top);
            window.history.replaceState({}, '', url);
        };

        const applyFilters = () => {
            const selectedPeriod = gradingSelect ? gradingSelect.value : initialPeriod;
            const selectedTop = topSelect ? topSelect.value : initialTop;
            const searchQuery = tagSearch ? tagSearch.value.trim() : '';

            const tagData = applyTopLimit(
                applyTagSearch(getPeriodData('tagAverage', selectedPeriod), searchQuery),
                selectedTop
            );

            const tagTypeRows = getPeriodRows('tagTypeBreakdown', selectedPeriod);
            const derivedTypeData = buildTypeDataFromTags(tagTypeRows, searchQuery);
            const baseTypeData = getPeriodData('classworkTypeAverage', selectedPeriod);
            const typeData = applyTopLimit(derivedTypeData || baseTypeData, selectedTop);

            updateChart(tagChart, 'classworkTagAverageBar', tagData.labels, tagData.values, 'tag-average');
            updateChart(typeChart, 'classworkTypeChart', typeData.labels, typeData.values, 'classwork-type');

            const tagEmptyMessage = document.querySelector('[data-empty-message="tag-average"]');
            if (tagEmptyMessage) {
                tagEmptyMessage.textContent = searchQuery ? 'No matching tags.' : 'No graded classwork yet.';
            }

            const typeEmptyMessage = document.querySelector('[data-empty-message="classwork-type"]');
            if (typeEmptyMessage) {
                typeEmptyMessage.textContent = searchQuery
                    ? 'No matching classwork types.'
                    : 'No graded classwork yet.';
            }

            syncUrl(selectedPeriod, selectedTop);
        };

        if (gradingSelect) {
            gradingSelect.addEventListener('change', applyFilters);
        }

        if (topSelect) {
            topSelect.addEventListener('change', applyFilters);
        }

        if (tagSearch) {
            tagSearch.addEventListener('input', applyFilters);
        }

        applyFilters();
    })();
</script>
@endpush
