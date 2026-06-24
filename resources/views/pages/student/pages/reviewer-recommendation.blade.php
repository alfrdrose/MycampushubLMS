@extends('pages.student.layout')

@section('content')
<div class="bg-[#FFFFFF] dark:bg-[#1E1E2D] dark:border-[#5F2DED] shadow-lg border p-8 rounded-lg">
    <div class="border-b border-[#D4D4D8] dark:border-[#5F2DED] pb-4">
        <h2 class="text-2xl font-semibold text-[#373737] dark:text-white">Reviewer Recommendation</h2>
        @php
            $tabs = [
                ['key' => 'analytics', 'label' => 'Analytics'],
                ['key' => 'reviewer-bank', 'label' => 'Reviewer Bank'],
                ['key' => 'recommended', 'label' => 'Recommended'],
            ];
            $base = 'font-semibold rounded-xl px-3 sm:px-4 md:px-5 lg:px-6 py-1 sm:py-1.5 md:py-2 whitespace-nowrap flex-shrink-0 transition text-xs sm:text-sm md:text-base lg:text-base';
            $activeClasses = 'bg-[#5F2DED] text-white';
            $inactiveClasses = 'text-[#5E5F60] dark:text-gray-300 hover:text-[#5F2DED] dark:hover:text-[#A88BFF]';
        @endphp

        <div class="mt-4 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">

            <!-- Tabs -->
            <div class="inline-block max-w-full bg-[#F1F5F9] dark:bg-[#2A2A3B] rounded-xl p-2 overflow-x-auto no-scrollbar">
                <div class="flex lg:gap-6 sm:gap-4 md:gap-4 min-w-max">
                    @foreach ($tabs as $tab)
                        @php
                            $isActive = $activeTab === $tab['key'];
                        @endphp
                        <a
                            href="{{ route('student.reviewer.recommendation', ['tab' => $tab['key']]) }}"
                            class="{{ $base }} {{ $isActive ? $activeClasses : $inactiveClasses }}">
                            {{ $tab['label'] }}
                        </a>
                    @endforeach
                </div>
            </div>

            <!-- Generated Reviewers Button -->
            <div class="w-full lg:w-auto">
                <a href="{{ route('student.reviewer.generated.index') }}"
                class="w-full lg:w-auto flex items-center justify-center gap-2
                        px-5 py-2
                        bg-white border border-[#5F2DED]
                        text-[#5F2DED] font-semibold
                        rounded-lg shadow-sm
                        hover:bg-[#5F2DED] hover:text-white
                        transition">

                    <i class="icofont-notebook text-lg"></i>
                    <span>Generated Reviewers</span>
                </a>
            </div>

        </div>
        
    </div>

    @php
        $reviewers = $reviewers ?? collect();
        $reviewerTags = $reviewerTags ?? collect();
        $recommendedReviewers = $recommendedReviewers ?? collect();
        $recommendedLessonCollections = $recommendedLessonCollections ?? collect();
        $lowPerformanceTags = $lowPerformanceTags ?? collect();
        $lowPerformanceTagInsights = $lowPerformanceTagInsights ?? [];
        $shouldRecommend = $shouldRecommend ?? false;
        $lessonCardVisibleTagLimit = 6;
    @endphp

    @if ($activeTab === 'analytics')
        <div class="mt-6">
            <div class="border border-[#E2E8F0] dark:border-[#334155] rounded-lg p-4 bg-[#F8FAFC] dark:bg-[#0B1220]">
                <h3 class="text-xl font-semibold text-[#373737] dark:text-white mb-3">
                    Performance Analytics
                </h3>
                <div class="w-full max-w-2xl mx-auto" data-chart-container="reviewer-analytics">
                    <canvas id="reviewerAnalyticsChart"></canvas>
                </div>
                <p class="text-sm text-[#5E5F60] dark:text-gray-300" data-empty-message="reviewer-analytics">
                    No graded classwork yet.
                </p>
            </div>
        </div>
    @elseif ($activeTab === 'reviewer-bank')
        <div class="mt-6">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                <div class="lg:col-span-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-[#373737] dark:text-white">Sort by tag</h3>
                    </div>
                    <div class="mt-3">
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                                <i class="icofont-search-1"></i>
                            </span>
                            <input
                                type="text"
                                id="reviewerBankTagSearch"
                                placeholder="Search tags..."
                                class="w-full pl-10 pr-3 py-2 rounded-full border border-[#ABB7C2] text-[#5E5F60] dark:text-white bg-transparent focus:outline-none focus:ring-2 focus:ring-[#5F2DED]"
                            />
                        </div>
                    </div>

                    <div id="reviewerBankTagList" class="mt-4 space-y-2">
                        @forelse ($reviewerTags as $tag)
                            <label
                                class="reviewer-bank-tag-option flex items-center gap-3 px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer"
                                data-tag-id="{{ $tag->id }}"
                                data-tag-name="{{ $tag->name }}"
                            >
                                <input type="checkbox"
                                    class="reviewer-bank-tag-checkbox h-4 w-4 rounded border-gray-300"
                                    value="{{ $tag->id }}">
                                <span class="text-sm text-[#5E5F60] dark:text-gray-300">{{ $tag->name }}</span>
                            </label>
                        @empty
                            <p class="text-sm text-gray-500 dark:text-gray-300">No tags available.</p>
                        @endforelse
                    </div>

                    <p id="reviewerBankTagEmpty" class="mt-3 text-sm text-gray-500 dark:text-gray-300 hidden">
                        No tags match your search.
                    </p>

                    <div id="reviewerBankTagPagination" class="mt-4 flex items-center justify-between">
                        <button
                            type="button"
                            id="reviewerBankTagPrevPage"
                            class="px-3 py-1.5 border border-[#ABB7C2] rounded-lg text-sm text-[#5E5F60] dark:text-white hover:bg-gray-100 dark:hover:bg-gray-800 disabled:opacity-50 disabled:cursor-not-allowed">
                            Prev
                        </button>
                        <p id="reviewerBankTagPageInfo" class="text-sm text-gray-500 dark:text-gray-300">Page 1 of 1</p>
                        <button
                            type="button"
                            id="reviewerBankTagNextPage"
                            class="px-3 py-1.5 border border-[#ABB7C2] rounded-lg text-sm text-[#5E5F60] dark:text-white hover:bg-gray-100 dark:hover:bg-gray-800 disabled:opacity-50 disabled:cursor-not-allowed">
                            Next
                        </button>
                    </div>
                </div>

                <div class="lg:col-span-8">
                    <div class="flex justify-end mb-3">
                        <div class="relative w-full sm:w-72">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                                <i class="icofont-search-1"></i>
                            </span>
                            <input
                                type="text"
                                id="reviewerBankTitleSearch"
                                placeholder="Search by reviewer title"
                                class="w-full pl-10 pr-3 py-2 rounded-full border border-[#ABB7C2] text-[#5E5F60] dark:text-white bg-transparent focus:outline-none focus:ring-2 focus:ring-[#5F2DED]"
                            />
                        </div>
                    </div>

                    <div class="border border-[#E2E8F0] dark:border-[#334155] rounded-lg p-6 bg-[#F8FAFC] dark:bg-[#0B1220]">
                        @if($reviewers->isEmpty())
                            <p class="text-[#5E5F60] dark:text-gray-300">No reviewers available yet.</p>
                        @else
                            <div class="grid grid-cols-1 gap-4" id="reviewerBankCards">
                                @foreach($reviewers as $reviewer)
                                    @php
                                        $isH5p = $reviewer instanceof \App\Models\H5pReviewer;
                                        $reviewerLabel = $isH5p ? 'H5P File' : 'Reviewer';
                                        $reviewerRoute = $isH5p
                                            ? route('student.reviewer.h5p.show', $reviewer->id)
                                            : route('student.reviewer.show', $reviewer->id);
                                    @endphp
                                    <div class="reviewer-bank-card border border-[#5F2DED] dark:border-[#A88BFF] rounded-lg p-4 cursor-pointer hover:bg-gray-50 dark:hover:bg-[#2A2A3C] transition"
                                         data-title="{{ strtolower($reviewer->title) }}"
                                         data-tag-ids="{{ $reviewer->tags->pluck('id')->implode(',') }}"
                                         data-tag-names="{{ $reviewer->tags->pluck('name')->map(fn ($name) => strtolower($name))->implode('|') }}"
                                         onclick="window.location='{{ $reviewerRoute }}'">
                                        <div class="border-l-4 border-[#5F2DED] dark:border-[#A88BFF] p-4">
                                            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2">
                                                <p class="text-lg font-semibold text-[#373737] dark:text-white">
                                                    {{ $reviewer->title }}
                                                </p>
                                                <span class="text-xs px-2 py-1 rounded-full border border-[#ABB7C2] text-[#5E5F60] dark:text-gray-300">
                                                    {{ $reviewerLabel }}
                                                </span>
                                            </div>
                                            <div class="mt-2 flex flex-wrap gap-2 reviewer-card-tag-group">
                                                @forelse($reviewer->tags as $tag)
                                                    <span class="reviewer-card-tag-chip text-xs px-2 py-0.5 rounded-full border border-[#ABB7C2] text-[#5E5F60] dark:text-gray-300">
                                                        {{ $tag->name }}
                                                    </span>
                                                @empty
                                                    <span class="reviewer-card-tag-empty text-xs text-gray-400">No tags</span>
                                                @endforelse
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <p id="reviewerBankEmpty" class="mt-4 text-sm text-gray-500 dark:text-gray-300 hidden">
                                No reviewers match your filters.
                            </p>
                            <div id="reviewerBankPagination" class="mt-4 flex items-center justify-between">
                                <button
                                    type="button"
                                    id="reviewerBankPrevPage"
                                    class="px-3 py-1.5 border border-[#ABB7C2] rounded-lg text-sm text-[#5E5F60] dark:text-white hover:bg-gray-100 dark:hover:bg-gray-800 disabled:opacity-50 disabled:cursor-not-allowed">
                                    Prev
                                </button>
                                <p id="reviewerBankPageInfo" class="text-sm text-gray-500 dark:text-gray-300">Page 1 of 1</p>
                                <button
                                    type="button"
                                    id="reviewerBankNextPage"
                                    class="px-3 py-1.5 border border-[#ABB7C2] rounded-lg text-sm text-[#5E5F60] dark:text-white hover:bg-gray-100 dark:hover:bg-gray-800 disabled:opacity-50 disabled:cursor-not-allowed">
                                    Next
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="mt-6">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                <div class="lg:col-span-4 self-start">
                    <div class="border border-[#E2E8F0] dark:border-[#334155] rounded-lg p-4 bg-[#F8FAFC] dark:bg-[#0B1220] flex flex-col">
                        <h3 class="text-sm font-semibold text-[#373737] dark:text-white">Low-performance tags</h3>
                        <div class="mt-3 flex flex-wrap gap-2" id="lowPerformanceTagChips">
                            @forelse ($lowPerformanceTags as $tag)
                                <button
                                    type="button"
                                    class="low-performance-tag-chip text-xs px-2 py-1 rounded-full border border-[#ABB7C2] dark:border-[#475569] bg-white dark:bg-transparent text-[#5E5F60] dark:text-gray-300 hover:border-[#5F2DED] dark:hover:border-[#A88BFF] hover:text-[#5F2DED] dark:hover:text-[#D5C7FF] transition"
                                    data-tag-id="{{ $tag->id }}"
                                    data-tag-name="{{ $tag->name }}"
                                    data-tag-weakness="{{ $tag->weakness_percent ?? 0 }}"
                                    data-tag-missed="{{ $tag->missed_points ?? 0 }}"
                                    data-tag-exposure="{{ $tag->exposure_points ?? 0 }}"
                                    data-tag-need="{{ $tag->need_score ?? 0 }}">
                                    {{ $tag->name }}
                                </button>
                            @empty
                                <p class="text-sm text-gray-500 dark:text-gray-300">No low-performance tags yet.</p>
                            @endforelse
                        </div>
                        <div id="lowPerformanceTagDetails" class="mt-4 hidden rounded-lg border border-[#E2E8F0] dark:border-[#334155] bg-[#F8FAFC] dark:bg-[#0B1220] p-3">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p id="lowPerformanceTagTitle" class="text-sm font-semibold text-[#373737] dark:text-white"></p>
                                    <p id="lowPerformanceTagStats" class="mt-1 text-[11px] text-[#5E5F60] dark:text-gray-300"></p>
                                </div>
                                <button type="button" id="lowPerformanceTagClose" class="h-6 w-6 inline-flex items-center justify-center rounded-full border border-[#CBD5E1] dark:border-[#334155] bg-white dark:bg-[#1E293B] text-[#5E5F60] dark:text-white hover:bg-gray-100 dark:hover:bg-gray-800 hover:text-[#5F2DED] dark:hover:text-[#A88BFF]" aria-label="Close details">
                                    <i class="icofont-close-line text-sm"></i>
                                </button>
                            </div>
                            <div id="lowPerformanceTagQuizRows" class="mt-3 space-y-2 max-h-64 overflow-y-auto pr-1"></div>
                        </div>
                        <div class="mt-auto pt-6">
                            <form id="recommendForm" method="GET" action="{{ route('student.reviewer.recommendation') }}">
                                <input type="hidden" name="tab" value="recommended">
                                <input type="hidden" name="recommend" value="1">
                                <button id="recommendButton" type="submit"
                                    class="w-full px-3 py-2 rounded-lg text-sm font-semibold border border-[#ABB7C2] text-[#5E5F60] dark:text-white hover:bg-gray-100 dark:hover:bg-gray-800 disabled:opacity-50 disabled:cursor-not-allowed"
                                    @if($lowPerformanceTags->isEmpty()) disabled @endif>
                                    Recommend
                                </button>
                            </form>
                        </div>
                    </div>
                    @if(!empty($lowPerformanceTags) && count($lowPerformanceTags) > 0)

                        <div class="mt-4">
                            <button 
                            id="openGenerateModal"
                            class="w-full flex items-center justify-center gap-2
                                    px-6 py-3
                                    bg-[#E1D6FF] text-primaryColor font-light
                                    rounded-lg hover:opacity-90
                                    transition">

                                <i class="icofont-book-alt text-lg"></i>
                                <span>Generate Reviewer</span>

                            </button>
                        </div>
                        <!-- Generate Reviewer Modal -->
                        <div id="generateModal"
                            class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">

                            <div class="bg-white dark:bg-[#1E1E2D] rounded-lg shadow-xl w-full max-w-md p-6">

                                <h3 class="text-xl font-bold mb-4 text-[#373737] dark:text-white">
                                    Generate Reviewer?
                                </h3>

                                <p class="text-gray-600 dark:text-gray-300 mb-6">
                                    This will analyze your incorrect answers and generate a personalized reviewer.
                                </p>

                                <div class="flex justify-end gap-3">

                                    <button id="cancelGenerate"
                                        class="px-4 py-2 rounded-lg border text-gray-600 hover:bg-gray-100">
                                        Cancel
                                    </button>

                                    <button id="confirmGenerate"
                                        class="px-4 py-2 rounded-lg bg-[#5F2DED] text-white hover:bg-[#4c24c4] flex items-center gap-2">

                                        <span id="generateText">Generate</span>

                                        <svg id="generateLoader"
                                            class="hidden animate-spin h-5 w-5"
                                            xmlns="http://www.w3.org/2000/svg"
                                            fill="none"
                                            viewBox="0 0 24 24">
                                            <circle class="opacity-25"
                                                cx="12"
                                                cy="12"
                                                r="10"
                                                stroke="currentColor"
                                                stroke-width="4"></circle>
                                            <path class="opacity-75"
                                                fill="currentColor"
                                                d="M4 12a8 8 0 018-8v8H4z"></path>
                                        </svg>

                                    </button>

                                </div>

                            </div>
                        </div>
                    @endif

                </div>
{{-- test  --}}
                <div class="lg:col-span-8">
                    <div class="border border-[#E2E8F0] dark:border-[#334155] rounded-lg p-6 bg-[#F8FAFC] dark:bg-[#0B1220]">
                        <h3 class="text-lg font-semibold text-[#373737] dark:text-white mb-4">Recommended Reviewers</h3>
                        @if(!$shouldRecommend)
                            <div id="recommendPrompt" class="text-[#5E5F60] dark:text-gray-300">
                                <span id="recommendPromptText">Click recommend to view matching reviewers.</span>
                                <span id="recommendLoading" class="hidden items-center gap-2">
                                    <span class="inline-flex h-4 w-4 rounded-full border-2 border-[#5F2DED]/30 border-t-[#5F2DED] animate-spin"></span>
                                    <span>Loading recommendations...</span>
                                </span>
                            </div>
                        @elseif($recommendedReviewers->isEmpty())
                            <p class="text-[#5E5F60] dark:text-gray-300">No recommended reviewers yet.</p>
                        @else
                            <div id="recommendedReviewerCards" class="grid grid-cols-1 gap-4">
                                @foreach($recommendedReviewers as $reviewer)
                                    @php
                                        $isH5p = $reviewer instanceof \App\Models\H5pReviewer;
                                        $reviewerLabel = $isH5p ? 'H5P File' : 'Reviewer';
                                        $reviewerRoute = $isH5p
                                            ? route('student.reviewer.h5p.show', $reviewer->id)
                                            : route('student.reviewer.show', $reviewer->id);
                                        $compatibilityDecimals = (int) config('reviewers.compatibility_percent_decimals', 1);
                                        $compatibilityPercent = isset($reviewer->compatibility_percent)
                                            ? (float) $reviewer->compatibility_percent
                                            : round(((float) ($reviewer->match_score ?? 0)) * 100, $compatibilityDecimals);
                                    @endphp
                                    <div class="recommended-reviewer-card border border-[#5F2DED] dark:border-[#A88BFF] rounded-lg p-4 cursor-pointer hover:bg-gray-50 dark:hover:bg-[#2A2A3C] transition"
                                         onclick="window.location='{{ $reviewerRoute }}'">
                                        <div class="border-l-4 border-[#5F2DED] dark:border-[#A88BFF] p-4">
                                            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2">
                                                <p class="text-lg font-semibold text-[#373737] dark:text-white">
                                                    {{ $reviewer->title }}
                                                </p>
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <span class="text-xs px-2 py-1 rounded-full border border-[#5F2DED] text-[#5F2DED] dark:border-[#A88BFF] dark:text-[#D5C7FF]">
                                                        {{ number_format($compatibilityPercent, $compatibilityDecimals) }}% Match
                                                    </span>
                                                    <span class="text-xs px-2 py-1 rounded-full border border-[#ABB7C2] text-[#5E5F60] dark:text-gray-300">
                                                        {{ $reviewerLabel }}
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="mt-2 flex flex-wrap gap-2 reviewer-card-tag-group">
                                                @forelse($reviewer->tags as $tag)
                                                    <span class="reviewer-card-tag-chip text-xs px-2 py-0.5 rounded-full border border-[#ABB7C2] text-[#5E5F60] dark:text-gray-300">
                                                        {{ $tag->name }}
                                                    </span>
                                                @empty
                                                    <span class="reviewer-card-tag-empty text-xs text-gray-400">No tags</span>
                                                @endforelse
                                            </div>
                                        </div>
                                    </div>
                                @endforeach

                            </div>
                            <div id="recommendedReviewerPagination" class="mt-4 flex items-center justify-between hidden">
                                <button
                                    type="button"
                                    id="recommendedReviewerPrevPage"
                                    class="px-3 py-1.5 border border-[#ABB7C2] rounded-lg text-sm text-[#5E5F60] dark:text-white hover:bg-gray-100 dark:hover:bg-gray-800 disabled:opacity-50 disabled:cursor-not-allowed">
                                    Prev
                                </button>
                                <p id="recommendedReviewerPageInfo" class="text-sm text-gray-500 dark:text-gray-300">Page 1 of 1</p>
                                <button
                                    type="button"
                                    id="recommendedReviewerNextPage"
                                    class="px-3 py-1.5 border border-[#ABB7C2] rounded-lg text-sm text-[#5E5F60] dark:text-white hover:bg-gray-100 dark:hover:bg-gray-800 disabled:opacity-50 disabled:cursor-not-allowed">
                                    Next
                                </button>
                            </div>
                        @endif
                    </div>

                    <div class="mt-6 border border-[#E2E8F0] dark:border-[#334155] rounded-lg p-6 bg-[#F8FAFC] dark:bg-[#0B1220]">
                        <h3 class="text-lg font-semibold text-[#373737] dark:text-white mb-4">Lesson Recommendations</h3>
                        @if(!$shouldRecommend)
                            <div class="text-[#5E5F60] dark:text-gray-300">
                                <span id="lessonRecommendPromptText">Click recommend to view matching lessons.</span>
                                <span id="lessonRecommendLoading" class="hidden items-center gap-2">
                                    <span class="inline-flex h-4 w-4 rounded-full border-2 border-[#5F2DED]/30 border-t-[#5F2DED] animate-spin"></span>
                                    <span>Loading lesson recommendations...</span>
                                </span>
                            </div>
                        @elseif($recommendedLessonCollections->isEmpty())
                            <p class="text-[#5E5F60] dark:text-gray-300">No recommended lessons yet.</p>
                        @else
                            <div id="recommendedLessonCards" class="grid grid-cols-1 gap-4">
                                @foreach($recommendedLessonCollections as $lessonCollection)
                                    @php
                                        $lessonTags = collect($lessonCollection['tags'] ?? [])->values();
                                        $visibleLessonTags = $lessonTags->take($lessonCardVisibleTagLimit);
                                        $remainingLessonTagCount = max($lessonTags->count() - $visibleLessonTags->count(), 0);
                                        $compatibilityDecimals = (int) config('reviewers.compatibility_percent_decimals', 1);
                                        $compatibilityPercent = isset($lessonCollection['compatibility_percent'])
                                            ? (float) $lessonCollection['compatibility_percent']
                                            : round(((float) ($lessonCollection['score'] ?? 0)) * 100, $compatibilityDecimals);
                                    @endphp
                                    <div class="recommended-lesson-card border border-[#5F2DED] dark:border-[#A88BFF] rounded-lg p-4 cursor-pointer hover:bg-gray-50 dark:hover:bg-[#2A2A3C] transition"
                                         onclick="window.location='{{ route('student.analytics', ['section_id' => $lessonCollection['section_id'], 'subject_id' => $lessonCollection['subject_id']]) }}'">
                                        <div class="border-l-4 border-[#5F2DED] dark:border-[#A88BFF] p-4">
                                            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2">
                                                <div>
                                                    <p class="text-lg font-semibold text-[#373737] dark:text-white">
                                                        {{ $lessonCollection['subject_name'] }}
                                                    </p>
                                                    <p class="text-sm text-[#5E5F60] dark:text-gray-300">
                                                        {{ $lessonCollection['lesson_count'] }} lesson{{ $lessonCollection['lesson_count'] === 1 ? '' : 's' }} recommended
                                                    </p>
                                                </div>
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <span class="text-xs px-2 py-1 rounded-full border border-[#5F2DED] text-[#5F2DED] dark:border-[#A88BFF] dark:text-[#D5C7FF]">
                                                        {{ number_format($compatibilityPercent, $compatibilityDecimals) }}% Match
                                                    </span>
                                                    <span class="text-xs px-2 py-1 rounded-full border border-[#ABB7C2] text-[#5E5F60] dark:text-gray-300">
                                                        Lesson
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="mt-2 flex flex-wrap gap-2">
                                                @forelse($visibleLessonTags as $tag)
                                                    <span class="text-xs px-2 py-0.5 rounded-full border border-[#ABB7C2] text-[#5E5F60] dark:text-gray-300">
                                                        {{ $tag->name ?? (string) $tag }}
                                                    </span>
                                                @empty
                                                    <span class="text-xs text-gray-400">No tags</span>
                                                @endforelse
                                                @if($remainingLessonTagCount > 0)
                                                    <span class="text-xs font-semibold tracking-wide text-[#5F2DED] dark:text-[#A88BFF]">
                                                        +{{ $remainingLessonTagCount }}
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach

                            </div>
                            <div id="recommendedLessonPagination" class="mt-4 flex items-center justify-between hidden">
                                <button
                                    type="button"
                                    id="recommendedLessonPrevPage"
                                    class="px-3 py-1.5 border border-[#ABB7C2] rounded-lg text-sm text-[#5E5F60] dark:text-white hover:bg-gray-100 dark:hover:bg-gray-800 disabled:opacity-50 disabled:cursor-not-allowed">
                                    Prev
                                </button>
                                <p id="recommendedLessonPageInfo" class="text-sm text-gray-500 dark:text-gray-300">Page 1 of 1</p>
                                <button
                                    type="button"
                                    id="recommendedLessonNextPage"
                                    class="px-3 py-1.5 border border-[#ABB7C2] rounded-lg text-sm text-[#5E5F60] dark:text-white hover:bg-gray-100 dark:hover:bg-gray-800 disabled:opacity-50 disabled:cursor-not-allowed">
                                    Next
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection

@section('scripts')
@if ($activeTab === 'analytics')

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0"></script>
<script>
    (() => {
        const chartData = @json($chartData);
        const labels = Array.isArray(chartData.labels) ? chartData.labels : [];
        const values = Array.isArray(chartData.values) ? chartData.values : [];

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

        const isDarkMode = () =>
    document.documentElement.classList.contains('dark')
    || document.body.classList.contains('dark');

const getLabelColor = () => isDarkMode() ? '#E5E7EB' : '#5E5F60';
const getGridColor  = () => isDarkMode() ? '#1F2937' : '#E2E8F0';


        const container = document.querySelector('[data-chart-container="reviewer-analytics"]');
        const emptyMessage = document.querySelector('[data-empty-message="reviewer-analytics"]');
        const hasData = labels.length > 0 && values.length > 0;

        if (container) {
            container.style.display = hasData ? '' : 'none';
            if (hasData) {
                const baseHeight = 240;
                const perItemHeight = 24;
                const desiredHeight = Math.max(baseHeight, labels.length * perItemHeight);
                container.style.height = `${desiredHeight}px`;
            }
        }

        if (emptyMessage) {
            emptyMessage.style.display = hasData ? 'none' : '';
        }

        if (!hasData) {
            return;
        }

        const ctx = document.getElementById('reviewerAnalyticsChart');
        if (!ctx) {
            return;
        }

        const formatValue = (value) => {
            const num = Number(value || 0);
            if (Number.isNaN(num)) {
                return '0';
            }
            return Number.isInteger(num) ? `${num}` : num.toFixed(2);
        };

        const compactBars = labels.length > 12;

        // FORCE Chart.js global text colors (this is the missing piece)
Chart.defaults.color = getLabelColor();
Chart.defaults.borderColor = getGridColor();


        new Chart(ctx, {
            type: 'bar',
            plugins: [ChartDataLabels],
            data: {
                labels,
                datasets: [{
                    data: values,
                    backgroundColor: labels.map((_, index) => palette[index % palette.length]),
                    borderRadius: 6,
                    barThickness: compactBars ? 10 : 14,
                    maxBarThickness: compactBars ? 12 : 18
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
                        color: () => getLabelColor(),
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
                                color: () => getGridColor()
                            },
                            ticks: {
                                color: () => getLabelColor(),
                                maxTicksLimit: 6
                            }
                        },
                        y: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                color: () => getLabelColor(),
                                autoSkip: false
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
    })();
</script>
@elseif ($activeTab === 'reviewer-bank')
<script>
    (() => {
        const tagSearchInput = document.getElementById('reviewerBankTagSearch');
        const titleSearchInput = document.getElementById('reviewerBankTitleSearch');
        const tagOptions = Array.from(document.querySelectorAll('.reviewer-bank-tag-option'));
        const tagCheckboxes = Array.from(document.querySelectorAll('.reviewer-bank-tag-checkbox'));
        const tagEmptyMessage = document.getElementById('reviewerBankTagEmpty');
        const tagPagination = document.getElementById('reviewerBankTagPagination');
        const tagPrevPageBtn = document.getElementById('reviewerBankTagPrevPage');
        const tagNextPageBtn = document.getElementById('reviewerBankTagNextPage');
        const tagPageInfo = document.getElementById('reviewerBankTagPageInfo');
        const reviewerCards = Array.from(document.querySelectorAll('.reviewer-bank-card'));
        const reviewerEmptyMessage = document.getElementById('reviewerBankEmpty');
        const reviewerPagination = document.getElementById('reviewerBankPagination');
        const reviewerPrevPageBtn = document.getElementById('reviewerBankPrevPage');
        const reviewerNextPageBtn = document.getElementById('reviewerBankNextPage');
        const reviewerPageInfo = document.getElementById('reviewerBankPageInfo');
        const reviewerTagVisibleLimit = 5;

        const initializeReviewerTagOverflow = (maxVisibleTags = 5) => {
            const tagGroups = Array.from(document.querySelectorAll('.reviewer-card-tag-group'));

            tagGroups.forEach((group) => {
                const chips = Array.from(group.querySelectorAll('.reviewer-card-tag-chip'));
                if (chips.length <= maxVisibleTags) {
                    return;
                }
                if (group.querySelector('.reviewer-tag-toggle')) {
                    return;
                }

                const hiddenChipCount = chips.length - maxVisibleTags;
                const toggleBtn = document.createElement('button');
                toggleBtn.type = 'button';
                toggleBtn.className = 'reviewer-tag-toggle text-xs font-semibold text-[#5F2DED] hover:underline focus:outline-none';
                toggleBtn.setAttribute('aria-expanded', 'false');

                const setCollapsedState = (collapsed) => {
                    chips.forEach((chip, index) => {
                        const shouldHide = collapsed && index >= maxVisibleTags;
                        chip.classList.toggle('hidden', shouldHide);
                        chip.hidden = shouldHide;
                    });

                    toggleBtn.textContent = collapsed
                        ? `Show more (+${hiddenChipCount})`
                        : 'Show less';
                    toggleBtn.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
                };

                toggleBtn.addEventListener('click', (event) => {
                    event.preventDefault();
                    event.stopPropagation();
                    const isExpanded = toggleBtn.getAttribute('aria-expanded') === 'true';
                    setCollapsedState(isExpanded);
                });

                setCollapsedState(true);
                group.appendChild(toggleBtn);
            });
        };

        const tagsPerPage = 6;
        let currentTagPage = 1;
        let filteredTagOptions = [...tagOptions];
        const reviewersPerPage = 4;
        let currentReviewerPage = 1;
        let filteredReviewerCards = [...reviewerCards];

        const getTagTotalPages = () => {
            if (filteredTagOptions.length === 0) return 0;
            return Math.ceil(filteredTagOptions.length / tagsPerPage);
        };

        const applyTagPagination = () => {
            if (tagOptions.length === 0) {
                if (tagEmptyMessage) {
                    tagEmptyMessage.classList.add('hidden');
                    tagEmptyMessage.hidden = true;
                }
                if (tagPagination) {
                    tagPagination.classList.add('hidden');
                    tagPagination.hidden = true;
                }
                return;
            }

            const totalPages = getTagTotalPages();
            if (currentTagPage > totalPages) {
                currentTagPage = totalPages || 1;
            }

            const start = (currentTagPage - 1) * tagsPerPage;
            const end = start + tagsPerPage;
            const visibleOptions = new Set(filteredTagOptions.slice(start, end));

            tagOptions.forEach((option) => {
                const shouldHide = !visibleOptions.has(option);
                option.classList.toggle('hidden', shouldHide);
                option.hidden = shouldHide;
            });

            if (tagPageInfo) {
                tagPageInfo.textContent = totalPages > 0
                    ? `Page ${currentTagPage} of ${totalPages}`
                    : 'Page 0 of 0';
            }
            if (tagPagination) {
                const shouldHidePagination = totalPages <= 1;
                tagPagination.classList.toggle('hidden', shouldHidePagination);
                tagPagination.hidden = shouldHidePagination;
            }
            if (tagPrevPageBtn) {
                tagPrevPageBtn.disabled = currentTagPage <= 1;
            }
            if (tagNextPageBtn) {
                tagNextPageBtn.disabled = currentTagPage >= totalPages;
            }
            if (tagEmptyMessage) {
                const hasMatches = filteredTagOptions.length > 0;
                tagEmptyMessage.classList.toggle('hidden', hasMatches);
                tagEmptyMessage.hidden = hasMatches;
            }
        };

        const filterTags = () => {
            const term = (tagSearchInput?.value || '').trim().toLowerCase();
            filteredTagOptions = tagOptions.filter((option) => {
                const name = (option.dataset.tagName || '').toLowerCase();
                return !term || name.includes(term);
            });
            currentTagPage = 1;
            applyTagPagination();
        };

        const getSelectedTagIds = () => {
            return tagCheckboxes
                .filter((checkbox) => checkbox.checked)
                .map((checkbox) => checkbox.value);
        };

        const getReviewerTotalPages = () => {
            if (filteredReviewerCards.length === 0) return 0;
            return Math.ceil(filteredReviewerCards.length / reviewersPerPage);
        };

        const applyReviewerPagination = () => {
            if (reviewerCards.length === 0) {
                if (reviewerPagination) {
                    reviewerPagination.classList.add('hidden');
                    reviewerPagination.hidden = true;
                }
                return;
            }

            const totalPages = getReviewerTotalPages();
            if (currentReviewerPage > totalPages) {
                currentReviewerPage = totalPages || 1;
            }

            const start = (currentReviewerPage - 1) * reviewersPerPage;
            const end = start + reviewersPerPage;
            const visibleCards = new Set(filteredReviewerCards.slice(start, end));

            reviewerCards.forEach((card) => {
                const shouldShow = visibleCards.has(card);
                card.classList.toggle('hidden', !shouldShow);
                card.hidden = !shouldShow;
            });

            if (reviewerPageInfo) {
                reviewerPageInfo.textContent = totalPages > 0
                    ? `Page ${currentReviewerPage} of ${totalPages}`
                    : 'Page 0 of 0';
            }
            if (reviewerPagination) {
                const shouldHidePagination = totalPages <= 1;
                reviewerPagination.classList.toggle('hidden', shouldHidePagination);
                reviewerPagination.hidden = shouldHidePagination;
            }
            if (reviewerPrevPageBtn) {
                reviewerPrevPageBtn.disabled = currentReviewerPage <= 1;
            }
            if (reviewerNextPageBtn) {
                reviewerNextPageBtn.disabled = currentReviewerPage >= totalPages;
            }
            if (reviewerEmptyMessage) {
                const hasVisible = filteredReviewerCards.length > 0;
                reviewerEmptyMessage.classList.toggle('hidden', hasVisible);
                reviewerEmptyMessage.hidden = hasVisible;
            }
        };

        const applyReviewerFilters = (resetPage = false) => {
            const titleTerm = (titleSearchInput?.value || '').trim().toLowerCase();
            const tagSearchTerm = (tagSearchInput?.value || '').trim().toLowerCase();
            const selectedTags = new Set(getSelectedTagIds());

            filteredReviewerCards = reviewerCards.filter((card) => {
                const title = card.dataset.title || '';
                const tagIds = (card.dataset.tagIds || '')
                    .split(',')
                    .map((value) => value.trim())
                    .filter(Boolean);
                const tagNames = (card.dataset.tagNames || '');

                const matchesTitle = !titleTerm || title.includes(titleTerm);
                const matchesTagSearch = !tagSearchTerm || tagNames.includes(tagSearchTerm);
                const matchesTags = selectedTags.size === 0
                    ? true
                    : tagIds.some((id) => selectedTags.has(id));

                return matchesTitle && matchesTags && matchesTagSearch;
            });

            if (resetPage) {
                currentReviewerPage = 1;
            }
            applyReviewerPagination();
        };

        tagSearchInput?.addEventListener('input', () => {
            filterTags();
            applyReviewerFilters(true);
        });

        tagPrevPageBtn?.addEventListener('click', () => {
            if (currentTagPage <= 1) return;
            currentTagPage -= 1;
            applyTagPagination();
        });

        tagNextPageBtn?.addEventListener('click', () => {
            const totalPages = getTagTotalPages();
            if (currentTagPage >= totalPages) return;
            currentTagPage += 1;
            applyTagPagination();
        });

        tagCheckboxes.forEach((checkbox) => {
            checkbox.addEventListener('change', () => applyReviewerFilters(true));
        });

        titleSearchInput?.addEventListener('input', () => applyReviewerFilters(true));

        reviewerPrevPageBtn?.addEventListener('click', () => {
            if (currentReviewerPage <= 1) return;
            currentReviewerPage -= 1;
            applyReviewerPagination();
        });

        reviewerNextPageBtn?.addEventListener('click', () => {
            const totalPages = getReviewerTotalPages();
            if (currentReviewerPage >= totalPages) return;
            currentReviewerPage += 1;
            applyReviewerPagination();
        });

        initializeReviewerTagOverflow(reviewerTagVisibleLimit);
        filterTags();
        applyReviewerFilters(true);
    })();
</script>
@elseif ($activeTab === 'recommended')
<script>
    (() => {
        const recommendForm = document.getElementById('recommendForm');
        const recommendButton = document.getElementById('recommendButton');
        const lowPerformanceTagInsights = @json($lowPerformanceTagInsights);
        const lowPerformanceTagChips = Array.from(document.querySelectorAll('.low-performance-tag-chip'));
        const lowPerformanceTagDetails = document.getElementById('lowPerformanceTagDetails');
        const lowPerformanceTagTitle = document.getElementById('lowPerformanceTagTitle');
        const lowPerformanceTagStats = document.getElementById('lowPerformanceTagStats');
        const lowPerformanceTagQuizRows = document.getElementById('lowPerformanceTagQuizRows');
        const lowPerformanceTagClose = document.getElementById('lowPerformanceTagClose');

        const loadingStates = [
            {
                prompt: document.getElementById('recommendPromptText'),
                loading: document.getElementById('recommendLoading'),
            },
            {
                prompt: document.getElementById('lessonRecommendPromptText'),
                loading: document.getElementById('lessonRecommendLoading'),
            },
        ].filter((state) => state.prompt && state.loading);

        const reviewerTagVisibleLimit = 5;
        const escapeHtml = (value) => String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
        const formatPoints = (value) => {
            const number = Number(value ?? 0);
            if (!Number.isFinite(number)) return '0';
            return number.toLocaleString(undefined, {
                minimumFractionDigits: 0,
                maximumFractionDigits: 2,
            });
        };
        const initializeReviewerTagOverflow = (maxVisibleTags = 5) => {
            const tagGroups = Array.from(document.querySelectorAll('.reviewer-card-tag-group'));

            tagGroups.forEach((group) => {
                const chips = Array.from(group.querySelectorAll('.reviewer-card-tag-chip'));
                if (chips.length <= maxVisibleTags) {
                    return;
                }
                if (group.querySelector('.reviewer-tag-toggle')) {
                    return;
                }

                const hiddenChipCount = chips.length - maxVisibleTags;
                const toggleBtn = document.createElement('button');
                toggleBtn.type = 'button';
                toggleBtn.className = 'reviewer-tag-toggle text-xs font-semibold text-[#5F2DED] hover:underline focus:outline-none';
                toggleBtn.setAttribute('aria-expanded', 'false');

                const setCollapsedState = (collapsed) => {
                    chips.forEach((chip, index) => {
                        const shouldHide = collapsed && index >= maxVisibleTags;
                        chip.classList.toggle('hidden', shouldHide);
                        chip.hidden = shouldHide;
                    });

                    toggleBtn.textContent = collapsed
                        ? `Show more (+${hiddenChipCount})`
                        : 'Show less';
                    toggleBtn.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
                };

                toggleBtn.addEventListener('click', (event) => {
                    event.preventDefault();
                    event.stopPropagation();
                    const isExpanded = toggleBtn.getAttribute('aria-expanded') === 'true';
                    setCollapsedState(isExpanded);
                });

                setCollapsedState(true);
                group.appendChild(toggleBtn);
            });
        };

        const initPagination = ({
            cardsSelector,
            paginationId,
            prevPageId,
            nextPageId,
            pageInfoId,
            cardsPerPage = 5,
        }) => {
            const cards = Array.from(document.querySelectorAll(cardsSelector));
            const pagination = document.getElementById(paginationId);
            const prevBtn = document.getElementById(prevPageId);
            const nextBtn = document.getElementById(nextPageId);
            const pageInfo = document.getElementById(pageInfoId);

            if (cards.length === 0) {
                if (pagination) {
                    pagination.classList.add('hidden');
                    pagination.hidden = true;
                }
                return;
            }

            let currentPage = 1;
            let visibleCards = [...cards];

            const getTotalPages = () => {
                return Math.ceil(visibleCards.length / cardsPerPage);
            };

            const applyPagination = () => {
                const totalPages = getTotalPages();
                if (currentPage > totalPages) {
                    currentPage = totalPages || 1;
                }

                const start = (currentPage - 1) * cardsPerPage;
                const end = start + cardsPerPage;
                const cardsForPage = new Set(visibleCards.slice(start, end));

                cards.forEach((card) => {
                    const shouldShow = cardsForPage.has(card);
                    card.classList.toggle('hidden', !shouldShow);
                    card.hidden = !shouldShow;
                });

                if (pageInfo) {
                    pageInfo.textContent = totalPages > 0
                        ? `Page ${currentPage} of ${totalPages}`
                        : 'Page 0 of 0';
                }
                if (prevBtn) {
                    prevBtn.disabled = currentPage <= 1;
                }
                if (nextBtn) {
                    nextBtn.disabled = currentPage >= totalPages;
                }
                if (pagination) {
                    const shouldHidePagination = totalPages <= 1;
                    pagination.classList.toggle('hidden', shouldHidePagination);
                    pagination.hidden = shouldHidePagination;
                }
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
        };

        initPagination({
            cardsSelector: '.recommended-reviewer-card',
            paginationId: 'recommendedReviewerPagination',
            prevPageId: 'recommendedReviewerPrevPage',
            nextPageId: 'recommendedReviewerNextPage',
            pageInfoId: 'recommendedReviewerPageInfo',
            cardsPerPage: 5,
        });

        initializeReviewerTagOverflow(reviewerTagVisibleLimit);

        initPagination({
            cardsSelector: '.recommended-lesson-card',
            paginationId: 'recommendedLessonPagination',
            prevPageId: 'recommendedLessonPrevPage',
            nextPageId: 'recommendedLessonNextPage',
            pageInfoId: 'recommendedLessonPageInfo',
            cardsPerPage: 5,
        });

        const hideLowPerformanceTagDetails = () => {
            if (!lowPerformanceTagDetails) return;
            lowPerformanceTagDetails.classList.add('hidden');
            lowPerformanceTagChips.forEach((chip) => {
                chip.classList.remove('border-[#5F2DED]', 'dark:border-[#A88BFF]', 'text-[#5F2DED]', 'dark:text-[#D5C7FF]');
            });
        };

        const renderLowPerformanceTagRows = (rows) => {
            if (!lowPerformanceTagQuizRows) return;
            if (!Array.isArray(rows) || rows.length === 0) {
                lowPerformanceTagQuizRows.innerHTML = `
                    <p class="text-xs text-gray-500 dark:text-gray-300">No classwork evidence found for this tag.</p>
                `;
                return;
            }

            lowPerformanceTagQuizRows.innerHTML = rows.map((row) => {
                const contribution = Math.max(0, Number(row?.contribution_percent ?? 0));
                const barWidth = Math.min(100, contribution);
                const classworkType = String(row?.classwork_type ?? 'Classwork');
                const classworkTitle = String(row?.classwork_title ?? 'Untitled item');
                const gradingPeriod = String(row?.grading_period ?? 'N/A');
                const isQuizType = classworkType.toLowerCase() === 'quiz';
                const sourceQuestion = String(row?.source_question ?? row?.question_preview ?? '').trim();
                const sourceTags = Array.isArray(row?.source_tags)
                    ? row.source_tags.map((tag) => String(tag ?? '').trim()).filter(Boolean)
                    : [];
                const hoverLines = [];
                if (isQuizType && sourceQuestion !== '') {
                    hoverLines.push(`Question: ${sourceQuestion}`);
                }
                if (isQuizType && sourceTags.length > 0) {
                    hoverLines.push(`Tags: ${sourceTags.join(', ')}`);
                }
                const hoverTitle = hoverLines.join('\n');
                return `
                    <div class="rounded-xl border border-[#E2E8F0] dark:border-[#334155] bg-[#F8FAFC] dark:bg-[#0B1220] p-4 transition-colors hover:bg-[#EEF2F7] dark:hover:bg-[#111A2C]" title="${escapeHtml(hoverTitle)}">
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0 flex-1">
                                <p class="text-[11px] font-medium uppercase tracking-wide text-[#64748B] dark:text-gray-400">
                                    ${escapeHtml(row?.subject_name ?? 'Unknown Subject')}
                                </p>
                            </div>
                            <span class="shrink-0 text-xs font-semibold text-[#5E5F60] dark:text-gray-300" title="Contribution to this tag">
                                ${formatPoints(contribution)}%
                            </span>
                        </div>
                        <div class="mt-2 flex flex-wrap items-center gap-2 text-xs text-[#64748B] dark:text-gray-400">
                            <span class="inline-flex items-center rounded-full border border-[#CBD5E1] dark:border-[#334155] bg-white dark:bg-[#111827] px-2 py-0.5 leading-none">
                                ${escapeHtml(classworkType)}
                            </span>
                            <span>Grading: ${escapeHtml(gradingPeriod)}</span>
                        </div>
                        <p class="mt-2 text-sm font-semibold leading-5 text-[#334155] dark:text-white break-words">
                            ${escapeHtml(classworkTitle)}
                        </p>
                        <div class="mt-3 h-2 w-full rounded-full bg-[#E2E8F0] dark:bg-[#334155] overflow-hidden">
                            <span class="block h-full bg-[#5F2DED] dark:bg-[#A88BFF]" style="width: ${barWidth}%;"></span>
                        </div>
                        <div class="mt-3 flex items-center justify-between gap-2">
                            <p class="text-sm font-semibold text-[#5E5F60] dark:text-gray-200">
                                ${formatPoints(row?.earned_points)} / ${formatPoints(row?.total_points)}
                            </p>
                        </div>
                    </div>
                `;
            }).join('');
        };

        const showLowPerformanceTagDetails = (chip) => {
            if (!lowPerformanceTagDetails || !chip) return;
            const tagId = String(chip.dataset.tagId || '').trim();
            if (!tagId) return;

            const insight = lowPerformanceTagInsights?.[tagId] ?? lowPerformanceTagInsights?.[Number(tagId)] ?? {};
            const tagName = chip.dataset.tagName || 'Tag';
            const weakness = Number(chip.dataset.tagWeakness ?? 0);
            const missed = Number(chip.dataset.tagMissed ?? 0);
            const exposure = Number(chip.dataset.tagExposure ?? 0);

            if (lowPerformanceTagTitle) {
                lowPerformanceTagTitle.textContent = `${tagName} details`;
            }
            if (lowPerformanceTagStats) {
                lowPerformanceTagStats.textContent = `Weakness ${formatPoints(weakness)}% - Missed ${formatPoints(missed)} / ${formatPoints(exposure)} points`;
            }

            renderLowPerformanceTagRows(insight?.rows || []);
            lowPerformanceTagDetails.classList.remove('hidden');

            lowPerformanceTagChips.forEach((item) => {
                const isActive = item === chip;
                item.classList.toggle('border-[#5F2DED]', isActive);
                item.classList.toggle('dark:border-[#A88BFF]', isActive);
                item.classList.toggle('text-[#5F2DED]', isActive);
                item.classList.toggle('dark:text-[#D5C7FF]', isActive);
            });
        };

        lowPerformanceTagChips.forEach((chip) => {
            chip.addEventListener('click', () => {
                const isCurrentlyOpen = !lowPerformanceTagDetails?.classList.contains('hidden')
                    && chip.classList.contains('border-[#5F2DED]');
                if (isCurrentlyOpen) {
                    hideLowPerformanceTagDetails();
                    return;
                }
                showLowPerformanceTagDetails(chip);
            });
        });

        lowPerformanceTagClose?.addEventListener('click', hideLowPerformanceTagDetails);

        if (recommendForm && recommendButton) {
            recommendForm.addEventListener('submit', () => {
                loadingStates.forEach(({ prompt, loading }) => {
                    prompt.classList.add('hidden');
                    loading.classList.remove('hidden');
                    loading.classList.add('inline-flex');
                });
                recommendButton.disabled = true;
            });
        }
    })();
</script>
<script>
    const openBtn = document.getElementById('openGenerateModal');
    const modal = document.getElementById('generateModal');
    const cancelBtn = document.getElementById('cancelGenerate');
    const confirmBtn = document.getElementById('confirmGenerate');
    const loader = document.getElementById('generateLoader');
    const text = document.getElementById('generateText');

    openBtn.addEventListener('click', () => {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    });

    cancelBtn.addEventListener('click', () => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    });

    confirmBtn.addEventListener('click', async function handleGenerateClick() {

    // Prevent re-entry
    if (confirmBtn.dataset.mode === 'view') {
        return;
    }

    text.classList.add('hidden');
    loader.classList.remove('hidden');
    confirmBtn.disabled = true;

    try {
        const response = await fetch("{{ route('student.reviewer.generate') }}", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": "{{ csrf_token() }}"
            },
            body: JSON.stringify({})
        });

        if (!response.ok) {
            throw new Error('Server error');
        }

        const data = await response.json();

        loader.classList.add('hidden');

        if (data.success) {

    // Switch button mode
    confirmBtn.dataset.mode = 'view';
    confirmBtn.type = 'button';

    modal.querySelector('h3').innerText = "Reviewer Generated!";
    modal.querySelector('p').innerText =
        "Your reviewer has been successfully created.";

    confirmBtn.innerHTML = `<span>View Reviewer</span>`;
    confirmBtn.disabled = false;

    confirmBtn.onclick = () => {
        const urlTemplate = "{{ route('student.reviewer.generated.view', ['generatedReviewer' => '__ID__']) }}";
        window.location.href = urlTemplate.replace('__ID__', data.reviewer_id);
    };

    if (cancelBtn) cancelBtn.remove();

    // Create Close button (dark mode compatible)
    const closeBtn = document.createElement('button');
    closeBtn.innerText = "Close";
    closeBtn.className = `
        px-4 py-2 rounded-lg border
        text-gray-700 bg-gray-100 hover:bg-gray-200
        dark:text-gray-300 dark:bg-[#2A2A3B] dark:border-[#3A3A4D] dark:hover:bg-[#3A3A4D]
        transition
    `;

    closeBtn.onclick = () => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    };

    // Insert on the left side of the View Reviewer button
    confirmBtn.parentElement.insertBefore(closeBtn, confirmBtn);
}

    } catch (error) {
        console.error(error);
        loader.classList.add('hidden');
        text.classList.remove('hidden');
        confirmBtn.disabled = false;
        alert("Something went wrong.");
    }
});
</script>
@endif
@endsection
