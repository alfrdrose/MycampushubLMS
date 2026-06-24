@extends('pages.student.layout')

@section('content')
<div class="bg-[#FFFFFF] dark:bg-[#1E1E2D] dark:border-[#5F2DED]
            shadow-lg border p-4 sm:p-6 lg:p-8 rounded-lg">

    {{-- HEADER --}}
    <div class="border-b border-[#D4D4D8] dark:border-[#5F2DED] pb-4">
        <h1 class="text-xl sm:text-2xl lg:text-3xl font-bold
                   text-[#5E5F60] dark:text-white">
            {{ $subject->subject_name }}
        </h1>

        <p class="mb-4 text-sm sm:text-base
                  text-[#5E5F60] dark:text-gray-300">
            Section {{ $section->section_rank }} - {{ $section->section_name }}
        </p>

        <x-student-nav active="analytics"
                       :sectionId="$section->id"
                       :subjectId="$subject->id"/>
    </div>

    {{-- CONTENT --}}
    <div class="mt-6">
        @php
            $recommendedLessons = $recommendedLessons ?? collect();
            $lowPerformanceLessonTags = $lowPerformanceLessonTags ?? collect();
        @endphp

        <div class="border border-[#E2E8F0] dark:border-[#334155]
                    rounded-lg p-4 sm:p-6
                    bg-[#F8FAFC] dark:bg-[#0B1220]">

            <h3 class="text-lg sm:text-xl font-semibold
                       text-[#373737] dark:text-white mb-3">
                Performance Analytics
            </h3>

            {{-- CHART CONTAINER --}}
            <div class="w-full overflow-x-auto">
                <div class="w-full max-w-3xl mx-auto
                            min-h-[240px]"
                     data-chart-container="performance-analytics">
                    <canvas id="performanceAnalyticsChart"></canvas>
                </div>
            </div>

            {{-- EMPTY MESSAGE --}}
            <p class="text-sm mt-2
                      text-[#5E5F60] dark:text-gray-400"
               data-empty-message="performance-analytics">
                No graded classwork yet.
            </p>
        </div>

        <div class="mt-6 border border-[#E2E8F0] dark:border-[#334155]
                    rounded-lg p-4 sm:p-6 bg-[#F8FAFC] dark:bg-[#0B1220]">
            <h3 class="text-lg sm:text-xl font-semibold text-[#373737] dark:text-white">
                Recommended Lessons
            </h3>
            @php
                $tagPillClass = 'text-xs px-2 py-0.5 rounded-full border border-[#ABB7C2] text-[#5E5F60] dark:text-gray-300';
                $compatibilityDecimals = (int) config('reviewers.compatibility_percent_decimals', 1);
            @endphp

            <p class="text-sm text-[#5E5F60] dark:text-gray-300 mt-3">
                Lessons matched to your low-performance tags.
            </p>

            <div id="recommendedLessonsGrid" class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-2">
                @forelse($recommendedLessons as $lesson)
                    @php
                        $lessonPreview = \Illuminate\Support\Str::limit(
                            \Illuminate\Support\Str::squish(strip_tags($lesson->description ?? '')),
                            90
                        );
                        $compatibilityPercent = isset($lesson->compatibility_percent)
                            ? (float) $lesson->compatibility_percent
                            : round(((float) ($lesson->match_score ?? 0)) * 100, $compatibilityDecimals);
                    @endphp

                    <a href="{{ route('student.lesson.show', [
                        'section_id' => $section->id,
                        'subject_id' => $subject->id,
                        'lesson_id' => $lesson->id,
                        'grading_period' => $lesson->grading_period,
                    ]) }}"
                       title="{{ $lesson->lesson_title }}"
                       data-recommended-lesson-card="true"
                       class="group block border border-l-4 border-l-[#531B8A] dark:border-[#334155] dark:border-l-[#A88BFF] rounded-none bg-white dark:bg-[#1E1E2D] px-2.5 py-2 hover:border-[#5F2DED] dark:hover:border-[#A88BFF] hover:bg-[#F9F6FF] dark:hover:bg-[#242438] transition">
                        <div class="flex items-stretch gap-2">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-col gap-1 sm:flex-row sm:items-start sm:justify-between sm:gap-2">
                                    <p class="text-xs font-semibold text-[#373737] dark:text-white truncate">
                                        {{ $lesson->lesson_title }}
                                    </p>
                                    <span class="shrink-0 self-start text-[10px] px-2 py-1 rounded-full border border-[#5F2DED] text-[#5F2DED] dark:border-[#A88BFF] dark:text-[#D5C7FF]">
                                        {{ number_format($compatibilityPercent, $compatibilityDecimals) }}% Match
                                    </span>
                                </div>

                                @if($lessonPreview !== '')
                                    <p class="mt-1 text-[11px] leading-4 text-[#5E5F60] dark:text-gray-300 overflow-hidden break-words"
                                       style="display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow-wrap:anywhere;">
                                        {{ $lessonPreview }}
                                    </p>
                                @endif

                                <div class="mt-2 flex flex-wrap gap-1">
                                    @forelse($lesson->tags as $tag)
                                        <span class="{{ $tagPillClass }}">
                                            {{ $tag->name }}
                                        </span>
                                    @empty
                                        <span class="text-[11px] text-gray-400">No tags</span>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </a>
                @empty
                    <p class="text-sm text-gray-500 dark:text-gray-300">
                        No recommended lessons yet.
                    </p>
                @endforelse

            </div>

            <div id="recommendedLessonsPagination" class="mt-3 flex items-center justify-between hidden">
                <button
                    type="button"
                    id="recommendedLessonsPrevPage"
                    class="px-3 py-1.5 border border-[#ABB7C2] rounded-lg text-sm text-[#5E5F60] dark:text-white hover:bg-gray-100 dark:hover:bg-gray-800 disabled:opacity-50 disabled:cursor-not-allowed">
                    Prev
                </button>
                <p id="recommendedLessonsPageInfo" class="text-sm text-gray-500 dark:text-gray-300">Page 1 of 1</p>
                <button
                    type="button"
                    id="recommendedLessonsNextPage"
                    class="px-3 py-1.5 border border-[#ABB7C2] rounded-lg text-sm text-[#5E5F60] dark:text-white hover:bg-gray-100 dark:hover:bg-gray-800 disabled:opacity-50 disabled:cursor-not-allowed">
                    Next
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0"></script>

<script>
(() => {
    const chartData = @json($chartData);
    const labels = Array.isArray(chartData.labels) ? chartData.labels : [];
    const values = Array.isArray(chartData.values) ? chartData.values : [];

    const palette = [
        '#5F2DED','#2864FF','#0EA5E9','#16A34A','#F97316',
        '#F43F5E','#14B8A6','#A855F7','#F59E0B','#EF4444'
    ];

    let chartInstance = null;

    const isDarkMode = () =>
        document.documentElement.classList.contains('dark');

    const labelColor = () => isDarkMode() ? '#FFFFFF' : '#1F2937';
    const gridColor  = () => isDarkMode() ? '#1F2937' : '#E5E7EB';

    const container = document.querySelector('[data-chart-container="performance-analytics"]');
    const emptyMessage = document.querySelector('[data-empty-message="performance-analytics"]');

    const hasData = labels.length > 0 && values.length > 0;

    if (container) {
        container.style.display = hasData ? '' : 'none';
        if (hasData) {
            const baseHeight = 240;
            const perItemHeight = 28;
            const desiredHeight = Math.max(baseHeight, labels.length * perItemHeight);
            container.style.height = `${desiredHeight}px`;
        }
    }

    if (emptyMessage) {
        emptyMessage.style.display = hasData ? 'none' : '';
    }

    if (!hasData) return;

    const ctx = document.getElementById('performanceAnalyticsChart');
    if (!ctx) return;

    const formatValue = (value) => {
        const num = Number(value || 0);
        if (Number.isNaN(num)) return '0';
        return Number.isInteger(num) ? `${num}` : num.toFixed(2);
    };

    const compactBars = labels.length > 12;

    const buildChart = () => {
        if (chartInstance) {
            chartInstance.destroy();
        }

        chartInstance = new Chart(ctx, {
            type: 'bar',
            plugins: [ChartDataLabels],
            data: {
                labels,
                datasets: [{
                    data: values,
                    backgroundColor: labels.map((_, i) => palette[i % palette.length]),
                    borderRadius: 6,
                    barThickness: compactBars ? 10 : 14,
                    maxBarThickness: compactBars ? 12 : 18
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: {
                    legend: { display: false },
                    datalabels: {
                        anchor: 'end',
                        align: 'end',
                        offset: 6,
                        color: labelColor,
                        font: {
                            size: 11,
                            weight: '600'
                        },
                        formatter: (value) => `${formatValue(value)}%`
                    },
                    tooltip: {
                        callbacks: {
                            label: (context) => {
                                const value = formatValue(context.parsed.x ?? 0);
                                return `${context.label}: ${value}%`;
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
                            autoSkip: false
                        }
                    }
                },
                layout: {
                    padding: { right: 56 }
                }
            }
        });
    };

    // Initial render
    buildChart();

    // 👇 Watch for Tailwind dark-mode toggle
    const observer = new MutationObserver(() => {
        buildChart();
    });

    observer.observe(document.documentElement, {
        attributes: true,
        attributeFilter: ['class']
    });

})();
</script>

<script>
(() => {
    const cards = Array.from(document.querySelectorAll('[data-recommended-lesson-card="true"]'));
    const pagination = document.getElementById('recommendedLessonsPagination');
    const prevBtn = document.getElementById('recommendedLessonsPrevPage');
    const nextBtn = document.getElementById('recommendedLessonsNextPage');
    const pageInfo = document.getElementById('recommendedLessonsPageInfo');

    if (!cards.length) {
        pagination?.classList.add('hidden');
        return;
    }

    const cardsPerPage = 6;
    let currentPage = 1;

    const getTotalPages = () => Math.max(1, Math.ceil(cards.length / cardsPerPage));

    const applyPagination = () => {
        const totalPages = getTotalPages();
        if (currentPage > totalPages) currentPage = totalPages;

        const start = (currentPage - 1) * cardsPerPage;
        const end = start + cardsPerPage;
        const visibleCards = new Set(cards.slice(start, end));

        cards.forEach((card) => {
            card.classList.toggle('hidden', !visibleCards.has(card));
        });

        if (pageInfo) {
            pageInfo.textContent = `Page ${currentPage} of ${totalPages}`;
        }

        if (prevBtn) prevBtn.disabled = currentPage <= 1;
        if (nextBtn) nextBtn.disabled = currentPage >= totalPages;
        pagination?.classList.toggle('hidden', totalPages <= 1);
    };

    prevBtn?.addEventListener('click', () => {
        if (currentPage <= 1) return;
        currentPage -= 1;
        applyPagination();
    });

    nextBtn?.addEventListener('click', () => {
        const totalPages = getTotalPages();
        if (currentPage >= totalPages) return;
        currentPage += 1;
        applyPagination();
    });

    applyPagination();
})();
</script>
@endsection
