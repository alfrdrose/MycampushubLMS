@extends('pages.teacher.layout')

@section('content')
@php
    $isEdit = isset($reviewer);
@endphp
<div class="bg-[#FFFFFF] dark:bg-[#1E1E2D] dark:border-[#5F2DED] shadow-lg border p-8 rounded-lg">
    <div class="border-b border-[#D4D4D8] dark:border-[#5F2DED] pb-4">
        <div class="flex items-start gap-3 min-w-0">
            <button onclick="window.location='{{ route('teacher.reviewer.index') }}'"
                class="mt-1 shrink-0"
                aria-label="Return">
                <i class="icofont-arrow-left text-primaryColor text-4xl"></i>
            </button>
            <input
                type="text"
                id="reviewerTitleHeader"
                name="title"
                form="reviewerFormMain"
                value="{{ old('title', $prefill['title'] ?? '') }}"
                placeholder="Reviewer title"
                class="w-full border-none text-[#373737] font-bold text-3xl dark:text-white bg-transparent focus:outline-none focus:ring-transparent p-0"
                required
            />
        </div>
        <p id="reviewerTitleError" class="text-xs text-red-500 mt-2 ml-12 hidden">
            Title is required.
        </p>
    </div>

    <form id="reviewerFormMain" method="POST" action="{{ $isEdit ? route('teacher.reviewer.update', $reviewer->id) : route('teacher.reviewer.store') }}" class="mt-6" novalidate>
        @csrf
        @if($isEdit)
            @method('PUT')
        @endif

        <input type="hidden" id="reviewerSourceType" name="source_classwork_type" value="{{ old('source_classwork_type', $prefill['source_type'] ?? '') }}">
        <input type="hidden" id="reviewerSourceId" name="source_classwork_id" value="{{ old('source_classwork_id', $prefill['source_id'] ?? '') }}">
        @php
            $tagValues = old('tag_ids', $prefill['tag_ids'] ?? []);
            if (!is_array($tagValues)) {
                $tagValues = array_filter(explode(',', (string) $tagValues));
            }
            $tagValues = array_values(array_unique(array_map('intval', $tagValues)));
            $selectedTags = $tags->whereIn('id', $tagValues);
            $defaultTagRelevance = strtolower((string) config('reviewers.default_tag_relevance', 'medium'));
            $tagRelevanceValues = old('tag_relevance', $prefill['tag_relevance'] ?? []);
            $tagCoverageValues = old('tag_coverage', $prefill['tag_coverage'] ?? []);
            $tagMetricPrefill = $prefill['tag_metrics'] ?? [];
            $tagRelevanceValues = is_array($tagRelevanceValues) ? $tagRelevanceValues : [];
            $tagCoverageValues = is_array($tagCoverageValues) ? $tagCoverageValues : [];
            $tagMetricPrefill = is_array($tagMetricPrefill) ? $tagMetricPrefill : [];

            $selectedTagSettings = $selectedTags->mapWithKeys(function ($tag) use ($tagRelevanceValues, $tagCoverageValues, $tagMetricPrefill, $defaultTagRelevance) {
                $tagId = (int) $tag->id;
                $tagKey = (string) $tag->id;
                $prefillMetric = $tagMetricPrefill[$tagId] ?? $tagMetricPrefill[$tagKey] ?? [];
                $prefillMetric = is_array($prefillMetric) ? $prefillMetric : [];

                $relevance = $tagRelevanceValues[$tagId]
                    ?? $tagRelevanceValues[$tagKey]
                    ?? ($prefillMetric['relevance'] ?? $defaultTagRelevance);

                $coverage = $tagCoverageValues[$tagId]
                    ?? $tagCoverageValues[$tagKey]
                    ?? ($prefillMetric['coverage'] ?? '');

                return [
                    $tagKey => [
                        'relevance' => strtolower((string) $relevance),
                        'coverage' => $coverage === null ? '' : $coverage,
                    ],
                ];
            });
        @endphp

        <div class="mb-6">
            <label for="reviewerLearningObjective" class="text-xl font-semibold text-[#5E5F60] dark:text-white">
                Objective
            </label>
            <textarea id="reviewerLearningObjective" name="learning_objective" rows="2" required
                class="w-full mt-2 px-0 py-2 border-0 border-b border-[#D4D4D8] dark:border-[#5F2DED] bg-transparent text-[#373737] dark:text-white rounded-none focus:outline-none focus:ring-0 focus:border-[#5F2DED]">{{ old('learning_objective', $prefill['learning_objective'] ?? '') }}</textarea>
            <p id="reviewerObjectiveError" class="text-xs text-red-500 mt-1 hidden">
                Objective is required.
            </p>
        </div>

        <div class="mb-6">
            <label for="reviewerLessonBody" class="text-xl font-semibold text-[#5E5F60] dark:text-white">
                Lesson
            </label>
            <textarea id="reviewerLessonBody" name="mini_lesson" rows="4" required
                class="w-full mt-2 px-0 py-2 border-0 border-b border-[#D4D4D8] dark:border-[#5F2DED] bg-transparent text-[#373737] dark:text-white rounded-none focus:outline-none focus:ring-0 focus:border-[#5F2DED]">{{ old('mini_lesson', $prefill['mini_lesson'] ?? '') }}</textarea>
            <p id="reviewerLessonError" class="text-xs text-red-500 mt-1 hidden">
                Lesson is required.
            </p>
        </div>

        <div class="mb-5">
            <h2 class="text-xl font-semibold text-[#5E5F60] dark:text-white">Concept</h2>
            <div class="mt-2 border border-gray-200 dark:border-gray-700 rounded-lg p-4">

            <div class="mt-4">
                <p class="text-sm text-[#373737] dark:text-white">From classwork</p>
                <div class="mt-2 flex flex-wrap items-center gap-2">
                <div id="reviewerClassworkSummary" class="text-sm text-gray-600 dark:text-gray-300"></div>
                    <button type="button" id="reviewerOpenClassworkModal"
                        class="inline-flex items-center gap-2 px-3 py-1 rounded-full border border-dashed border-[#5F2DED] text-[#5F2DED] hover:bg-[#5F2DED]/10 transition">
                        <i class="icofont-ui-add text-sm"></i>
                        <span class="text-sm font-medium">Select Classwork</span>
                    </button>
                    <button type="button" id="reviewerClearClassworkBtn"
                        class="inline-flex items-center gap-2 px-3 py-1 rounded-full border border-red-300 text-red-500 hover:bg-red-50 transition">
                        <i class="icofont-close text-sm"></i>
                        <span class="text-sm font-medium">Clear</span>
                    </button>
                </div>
            </div>

            <div class="mt-4">
                <p class="text-sm text-[#373737] dark:text-white">Tag</p>
                <div id="reviewerTagInputs">
                    @foreach($selectedTags as $tag)
                        @php
                            $tagSetting = $selectedTagSettings[(string) $tag->id] ?? null;
                        @endphp
                        <input type="hidden" name="tag_ids[]" value="{{ $tag->id }}">
                        <input type="hidden" name="tag_relevance[{{ $tag->id }}]" value="{{ $tagSetting['relevance'] ?? $defaultTagRelevance }}">
                        <input type="hidden" name="tag_coverage[{{ $tag->id }}]" value="{{ $tagSetting['coverage'] ?? '' }}">
                    @endforeach
                </div>
                <div class="mt-2 flex flex-wrap items-center gap-2">
                    <div id="reviewerTagPills" class="flex flex-wrap items-center gap-2">
                        @foreach($selectedTags as $tag)
                            <button type="button"
                                class="reviewer-tag-pill inline-flex items-center gap-2 px-3 py-1 rounded-full border border-[#ABB7C2] text-[#5E5F60] dark:text-white hover:bg-gray-100 dark:hover:bg-gray-800 transition"
                                data-tag-id="{{ $tag->id }}">
                                <span class="text-sm">{{ $tag->name }}</span>
                                <span class="reviewer-tag-remove text-xs text-gray-400 hover:text-red-500">x</span>
                            </button>
                        @endforeach
                    </div>
                    <p id="reviewerNoTagMessage" class="text-sm text-gray-500 dark:text-gray-300 {{ $selectedTags->isNotEmpty() ? 'hidden' : '' }}"></p>
                </div>
                <p id="reviewerTagDerivedNote" class="text-xs text-gray-500 mt-1 hidden"></p>
                <div class="mt-2 flex items-center gap-2">
                    <button type="button" id="reviewerOpenTagsModal"
                        class="inline-flex items-center gap-2 px-3 py-1 rounded-full border border-dashed border-[#5F2DED] text-[#5F2DED] hover:bg-[#5F2DED]/10 transition">
                        <i class="icofont-ui-add text-sm"></i>
                        <span class="text-sm font-medium">Add Tag</span>
                    </button>
                    <button type="button" id="reviewerClearTagBtn"
                        data-loading-text="Clearing..."
                        data-loading-text-class="text-xs"
                        class="inline-flex items-center gap-2 px-3 py-1 rounded-full border border-red-300 text-red-500 hover:bg-red-50 transition">
                        <i class="icofont-close text-sm"></i>
                        <span class="text-sm font-medium">Clear</span>
                    </button>
                </div>
                <p id="reviewerTagError"
                class="text-xs text-red-500 mt-1 hidden">
                    Please select at least one tag.
                </p>

            </div>
            </div>
        </div>

        @php
            $quizQuestions = old('quiz_questions', $prefill['quiz_questions'] ?? []);
            if (empty($quizQuestions)) {
                $quizQuestions = [
                    ['question' => '', 'answer' => '']
                ];
            }
        @endphp

        <div class="mt-8 pt-6 border-t-2 border-[#D4D4D8] dark:border-[#5F2DED]">
            <h2 class="text-xl font-semibold text-[#5E5F60] dark:text-white">Guide Questions</h2>
            <div class="mt-2 border border-gray-200 dark:border-gray-700 rounded-lg p-5 bg-[#F9FAFB] dark:bg-[#14141f]">
                <p id="reviewerGuideQuestionsError" class="text-xs text-red-500 mt-1 hidden">
                    Add at least one guide question.
                </p>
                <div id="reviewerQuestionsContainer" class="mt-3 flex flex-col gap-3" data-next-index="{{ count($quizQuestions) }}">
                    @foreach($quizQuestions as $index => $item)
                        <div class="reviewer-question bg-white dark:bg-[#2A2A3C] border border-gray-200 dark:border-gray-600 rounded-lg p-4 shadow-sm relative transition-all duration-200 hover:shadow-md hover:bg-gray-50 dark:hover:bg-[#3A3A4F]">
                            <button type="button"
                                class="reviewer-remove-question absolute top-3 right-3 text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 transition-colors text-lg font-bold"
                                aria-label="Remove guide question">
                                &times;
                            </button>
                            <input type="text"
                                name="quiz_questions[{{ $index }}][question]"
                                value="{{ $item['question'] ?? '' }}"
                                class="block w-full max-w-xl mb-1 p-2 border border-gray-300 dark:border-gray-600 rounded-md
                                    bg-white dark:bg-[#1E1E2D] text-gray-800 dark:text-white reviewer-question-input"
                                placeholder="Enter guide question">

                            <p class="text-xs text-red-500 hidden reviewer-question-error">
                                Guide question is required.
                            </p>

                            <input type="text"
                                name="quiz_questions[{{ $index }}][answer]"
                                value="{{ $item['answer'] ?? '' }}"
                                class="block w-full mt-2 mb-1 max-w-md p-2 border border-gray-300 dark:border-gray-600 rounded-md
                                    bg-white dark:bg-[#1E1E2D] text-gray-800 dark:text-white reviewer-answer-input"
                                placeholder="Enter answer">

                            <p class="text-xs text-red-500 hidden reviewer-answer-error">
                                Answer is required.
                            </p>

                        </div>
                    @endforeach
                </div>
                <div class="flex justify-center mt-4 items-center gap-3">
                    <button type="button" id="reviewerAddQuestion"
                        title="Add Guide Question"
                        class="flex items-center justify-center w-10 h-10 bg-white dark:bg-purple-600
                            text-purple-600 dark:text-white rounded-full shadow-md
                            hover:bg-gray-100 dark:hover:bg-purple-700 transition-all duration-200
                            focus:outline-none focus:ring-2 focus:ring-purple-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-2 mt-6">
            <button type="button"
                onclick="window.location='{{ route('teacher.reviewer.index') }}'"
                class="px-4 py-2 rounded-lg border">
                Cancel
            </button>
            <button type="submit"
                id="reviewerSubmitBtn"
                class="px-4 py-2 rounded-lg bg-[#5F2DED] text-white flex items-center gap-2">
                <span data-label>
                    {{ $isEdit ? 'Update Reviewer' : 'Save Reviewer' }}
                </span>
                <span data-spinner class="hidden">
                    <svg class="animate-spin h-4 w-4 text-white"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10"
                                stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor"
                            d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                    </svg>
                </span>
            </button>

        </div>
    </form>
</div>

@php
    $periodLabels = [
        '1st' => '1st Quarter',
        '2nd' => '2nd Quarter',
        '3rd' => '3rd Quarter',
        '4th' => '4th Quarter',
    ];
@endphp

<div id="reviewerTagsModal"
     class="fixed inset-0 items-center justify-center bg-black bg-opacity-50 hidden z-50">
    <div class="bg-white dark:bg-[#1E1E2D] p-6 rounded-lg w-full max-w-2xl mx-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <h2 class="text-xl font-bold text-gray-800 dark:text-white">Select Tags</h2>
            <div class="flex items-center gap-2 sm:justify-end w-full sm:w-auto">
                <div class="relative w-full sm:w-52">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                        <i class="icofont-search-1 text-sm"></i>
                    </span>
                    <input
                        type="text"
                        id="reviewerTagSearchInput"
                        placeholder="Search tags..."
                        class="w-full pl-10 pr-3 py-2 text-sm rounded-full border border-[#ABB7C2] text-[#5E5F60] dark:text-white bg-transparent focus:outline-none focus:ring-2 focus:ring-[#5F2DED]"
                    />
                </div>
                <button type="button" id="reviewerCloseTagsModal"
                    class="text-gray-500 hover:text-gray-700 dark:text-gray-300">
                    <i class="icofont-close text-xl"></i>
                </button>
            </div>
        </div>

        <div class="mt-4 flex items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <span class="text-sm text-gray-500 dark:text-gray-300">Category:</span>
                <button type="button" id="reviewerOpenCategoryPicker"
                    class="inline-flex items-center gap-2 px-3 py-1.5 rounded-md border border-[#ABB7C2] bg-gray-50 dark:bg-[#2A2A3C] text-sm font-medium text-[#5E5F60] dark:text-white hover:border-[#5F2DED] hover:bg-[#F6F2FF] dark:hover:bg-[#34344A] transition-colors">
                    <span id="reviewerCurrentCategoryLabel">All categories</span>
                    <i class="icofont-rounded-down text-sm"></i>
                </button>
                <div id="reviewerMetricModeToggle"
                    class="relative inline-grid grid-cols-2 h-9 w-56 rounded-full border border-[#ABB7C2] bg-gray-50 dark:bg-[#2A2A3C] overflow-hidden">
                    <span id="reviewerMetricModeSlider"
                        class="pointer-events-none absolute inset-y-0 left-0 w-1/2 bg-[#5F2DED] rounded-full transition-transform duration-200 opacity-0"></span>
                    <button type="button" id="reviewerModeRelevancyBtn"
                        class="relative z-10 text-xs font-semibold transition-colors">
                        Relevance
                    </button>
                    <button type="button" id="reviewerModeCoverageBtn"
                        class="relative z-10 text-xs font-semibold transition-colors">
                        Coverage
                    </button>
                </div>
            </div>
            <button type="button" id="reviewerBackToTags"
                class="hidden inline-flex h-8 w-8 items-center justify-center rounded-full border border-[#ABB7C2] text-[#5F2DED] hover:bg-[#F6F2FF] dark:border-[#5F2DED] dark:hover:bg-[#2A2A3C]"
                title="Back to tags"
                aria-label="Back to tags">
                <i class="icofont-simple-left text-sm"></i>
            </button>
        </div>

        <div id="reviewerTagListContainer">
            <div class="mt-4 max-h-72 overflow-y-auto border border-gray-200 dark:border-gray-700 rounded-lg divide-y divide-gray-100 dark:divide-gray-800">
                @forelse ($tags as $tag)
                    @php
                        $tagSetting = $selectedTagSettings[(string) $tag->id] ?? null;
                    @endphp
                    <label
                        class="reviewer-tag-option flex items-center gap-3 px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer"
                        data-tag-id="{{ $tag->id }}"
                        data-tag-name="{{ $tag->name }}"
                        data-tag-category="{{ $tag->category }}"
                        data-tag-relevance="{{ $tagSetting['relevance'] ?? $defaultTagRelevance }}"
                        data-tag-coverage="{{ $tagSetting['coverage'] ?? '' }}"
                    >
                        <input type="checkbox"
                            class="reviewer-tag-checkbox h-4 w-4 rounded border-gray-300"
                            value="{{ $tag->id }}"
                            {{ in_array($tag->id, $tagValues, true) ? 'checked' : '' }}>
                        <div class="flex flex-col">
                            <span class="text-sm font-medium text-[#5E5F60] dark:text-white">{{ $tag->name }}</span>
                            <span class="text-xs text-gray-500 dark:text-gray-300">{{ $tag->category }}</span>
                        </div>
                    </label>
                @empty
                    <div class="px-4 py-6 text-sm text-gray-500 dark:text-gray-300">
                        No tags available.
                    </div>
                @endforelse
            </div>
            <p id="reviewerTagListEmpty" class="mt-3 text-sm text-gray-500 dark:text-gray-300 hidden">
                No tags match your filters.
            </p>
            <div id="reviewerTagPagination" class="mt-4 flex items-center justify-between">
                <button
                    type="button"
                    id="reviewerTagPrevPage"
                    class="px-3 py-1.5 border border-[#ABB7C2] rounded-lg text-sm text-[#5E5F60] dark:text-white hover:bg-gray-100 dark:hover:bg-gray-800 disabled:opacity-50 disabled:cursor-not-allowed">
                    Prev
                </button>
                <p id="reviewerTagPageInfo" class="text-sm text-gray-500 dark:text-gray-300">Page 1 of 1</p>
                <button
                    type="button"
                    id="reviewerTagNextPage"
                    class="px-3 py-1.5 border border-[#ABB7C2] rounded-lg text-sm text-[#5E5F60] dark:text-white hover:bg-gray-100 dark:hover:bg-gray-800 disabled:opacity-50 disabled:cursor-not-allowed">
                    Next
                </button>
            </div>
        </div>

        <div id="reviewerTagCategoryPicker" class="hidden mt-4 border border-gray-200 dark:border-gray-700 rounded-md divide-y divide-gray-100 dark:divide-gray-800">
            <button type="button"
                class="reviewer-tag-category-option w-full text-left px-4 py-2.5 border-l-4 border-transparent hover:bg-gray-50 dark:hover:bg-gray-800 text-sm font-medium text-[#5E5F60] dark:text-white transition-colors"
                data-tag-category="">
                All categories
            </button>
            @forelse ($tagCategories as $category)
                <button type="button"
                    class="reviewer-tag-category-option w-full text-left px-4 py-2.5 border-l-4 border-transparent hover:bg-gray-50 dark:hover:bg-gray-800 text-sm font-medium text-[#5E5F60] dark:text-white transition-colors"
                    data-tag-category="{{ $category }}">
                    {{ $category }}
                </button>
            @empty
                <div class="px-4 py-6 text-sm text-gray-500 dark:text-gray-300">
                    No categories available.
                </div>
            @endforelse
        </div>
        <p id="reviewerCategoryListEmpty" class="mt-3 text-sm text-gray-500 dark:text-gray-300 hidden">
            Category not found.
        </p>
        <div id="reviewerTagCategoryPagination" class="hidden mt-4 flex items-center justify-between">
            <button
                type="button"
                id="reviewerTagCategoryPrevPage"
                class="px-3 py-1.5 border border-[#ABB7C2] rounded-lg text-sm text-[#5E5F60] dark:text-white hover:bg-gray-100 dark:hover:bg-gray-800 disabled:opacity-50 disabled:cursor-not-allowed">
                Prev
            </button>
            <p id="reviewerTagCategoryPageInfo" class="text-sm text-gray-500 dark:text-gray-300">Page 1 of 1</p>
            <button
                type="button"
                id="reviewerTagCategoryNextPage"
                class="px-3 py-1.5 border border-[#ABB7C2] rounded-lg text-sm text-[#5E5F60] dark:text-white hover:bg-gray-100 dark:hover:bg-gray-800 disabled:opacity-50 disabled:cursor-not-allowed">
                Next
            </button>
        </div>

        <div id="reviewerTagRelevancyContainer" class="hidden mt-4">
            <div class="rounded-lg border border-[#E2E8F0] dark:border-[#334155] p-3 bg-[#F8FAFC] dark:bg-[#0B1220]">
                <div id="reviewerTagMetricsEditor" class="space-y-2"></div>
                <div id="reviewerRelevancyPagination" class="hidden mt-3 flex items-center justify-between">
                    <button
                        type="button"
                        id="reviewerRelevancyPrevPage"
                        class="px-3 py-1.5 border border-[#ABB7C2] rounded-lg text-sm text-[#5E5F60] dark:text-white hover:bg-gray-100 dark:hover:bg-gray-800 disabled:opacity-50 disabled:cursor-not-allowed">
                        Prev
                    </button>
                    <p id="reviewerRelevancyPageInfo" class="text-sm text-gray-500 dark:text-gray-300">Page 1 of 1</p>
                    <button
                        type="button"
                        id="reviewerRelevancyNextPage"
                        class="px-3 py-1.5 border border-[#ABB7C2] rounded-lg text-sm text-[#5E5F60] dark:text-white hover:bg-gray-100 dark:hover:bg-gray-800 disabled:opacity-50 disabled:cursor-not-allowed">
                        Next
                    </button>
                </div>
            </div>
        </div>

        <div class="mt-6 flex justify-end">
            <button
                type="button"
                id="reviewerConfirmTagsModal"
                data-loading-text="Saving..."
                class="px-4 py-2 bg-primaryColor text-white rounded hover:opacity-90">
                Confirm
            </button>
        </div>
    </div>
</div>


@endsection

<div id="reviewerClassworkModal"
     class="fixed inset-0 items-center justify-center bg-black bg-opacity-50 hidden z-50">
    <div class="bg-white dark:bg-[#1E1E2D] p-6 rounded-lg w-full max-w-2xl mx-4">
        <div class="flex flex-col sm:flex-row sm:items-center gap-3">
            <h2 class="text-xl font-bold text-gray-800 dark:text-white">
                Select Classwork
            </h2>

            <div class="flex items-center gap-2 w-full sm:w-auto ml-auto">
                <div class="relative w-full sm:w-56 md:w-50">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                        <i class="icofont-search-1 text-sm"></i>
                    </span>
                    <input
                        type="text"
                        id="reviewerClassworkSearch"
                        placeholder="Search classwork..."
                        class="w-full pl-10 pr-3 py-2 text-sm rounded-full border border-[#ABB7C2]
                            text-[#5E5F60] dark:text-white bg-transparent
                            focus:outline-none focus:ring-2 focus:ring-[#5F2DED]"
                    />
                </div>

                <button type="button" id="reviewerCloseClassworkModal"
                    class="text-gray-500 hover:text-gray-700 dark:text-gray-300">
                    <i class="icofont-close text-xl"></i>
                </button>
            </div>
        </div>



        <div class="mt-4 flex flex-wrap items-center gap-3">
            <div class="flex items-center gap-2">
                <span class="text-sm text-gray-500 dark:text-gray-300">Type:</span>
                <select id="reviewerClassworkTypeFilter"
                    class="border border-[#ABB7C2] cursor-pointer rounded-lg px-4 py-1.5 text-sm text-[#5E5F60] dark:text-white bg-transparent">
                    <option value="">All types</option>
                    <option value="quiz">Quiz</option>
                    <option value="activity">Assignment</option>
                    <option value="evaluation">Evaluation</option>
                </select>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-sm text-gray-500 dark:text-gray-300">Quarter:</span>
                <select id="reviewerClassworkPeriodFilter"
                    class="border border-[#ABB7C2] cursor-pointer rounded-lg px-4 py-1.5 text-sm text-[#5E5F60] dark:text-white bg-transparent min-w-[9rem]">
                    <option value="">All quarters</option>
                    <option value="1st">1st Quarter</option>
                    <option value="2nd">2nd Quarter</option>
                    <option value="3rd">3rd Quarter</option>
                    <option value="4th">4th Quarter</option>
                </select>
            </div>
        </div>

        <div id="reviewerClassworkListContainer" class="mt-4 max-h-72 overflow-y-auto border border-gray-200 dark:border-gray-700 rounded-lg divide-y divide-gray-100 dark:divide-gray-800">
            @forelse ($classworkOptions as $index => $item)
                <button type="button"
                    class="reviewer-classwork-option w-full text-left px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer"
                    data-index="{{ $index }}"
                    data-title="{{ strtolower($item['title']) }}"
                    data-type="{{ $item['type'] }}"
                    data-period="{{ $item['grading_period'] }}">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                        <div>
                            <p class="text-sm font-semibold text-[#5E5F60] dark:text-white">{{ $item['title'] }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-300">
                                {{ $item['type_label'] }} &bull; {{ $periodLabels[$item['grading_period']] ?? $item['grading_period'] }}
                            </p>
                            <div class="mt-2 flex flex-wrap gap-1">
                                @forelse ($item['tags'] as $tag)
                                    <span class="text-xs px-2 py-0.5 rounded-full border border-[#ABB7C2] text-[#5E5F60] dark:text-gray-300">
                                        {{ $tag['name'] }}
                                    </span>
                                @empty
                                    <span class="text-xs text-gray-400">No tags</span>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </button>
            @empty
                <div class="px-4 py-6 text-sm text-gray-500 dark:text-gray-300">
                    No classwork available.
                </div>
            @endforelse
        </div>
        <p id="reviewerClassworkListEmpty" class="mt-3 text-sm text-gray-500 dark:text-gray-300 hidden">
            No classwork match your filters.
        </p>
        <div id="reviewerClassworkPagination" class="mt-4 flex items-center justify-between">
            <button
                type="button"
                id="reviewerClassworkPrevPage"
                class="px-3 py-1.5 border border-[#ABB7C2] rounded-lg text-sm text-[#5E5F60] dark:text-white hover:bg-gray-100 dark:hover:bg-gray-800 disabled:opacity-50 disabled:cursor-not-allowed">
                Prev
            </button>
            <p id="reviewerClassworkPageInfo" class="text-sm text-gray-500 dark:text-gray-300">Page 1 of 1</p>
            <button
                type="button"
                id="reviewerClassworkNextPage"
                class="px-3 py-1.5 border border-[#ABB7C2] rounded-lg text-sm text-[#5E5F60] dark:text-white hover:bg-gray-100 dark:hover:bg-gray-800 disabled:opacity-50 disabled:cursor-not-allowed">
                Next
            </button>
        </div>
    </div>
</div>

@section('scripts')
<style>
    .reviewer-pill-tone-very-high {
        border-color: #16a34a;
        background-color: #dcfce7;
        color: #14532d;
    }

    .reviewer-pill-tone-very-high:hover {
        background-color: #bbf7d0;
    }

    .reviewer-pill-tone-high {
        border-color: #14b8a6;
        background-color: #ccfbf1;
        color: #134e4a;
    }

    .reviewer-pill-tone-high:hover {
        background-color: #99f6e4;
    }

    .reviewer-pill-tone-medium {
        border-color: #84cc16;
        background-color: #f7fee7;
        color: #4d7c0f;
    }

    .reviewer-pill-tone-medium:hover {
        background-color: #ecfccb;
    }

    .reviewer-pill-tone-low {
        border-color: #0ea5e9;
        background-color: #f0f9ff;
        color: #0c4a6e;
    }

    .reviewer-pill-tone-low:hover {
        background-color: #bae6fd;
    }

    .reviewer-pill-tone-very-low {
        border-color: #1d4ed8;
        background-color: #dbeafe;
        color: #172554;
    }

    .reviewer-pill-tone-very-low:hover {
        background-color: #93c5fd;
    }

    .dark .reviewer-pill-tone-very-high {
        border-color: #4ade80;
        background-color: rgba(22, 163, 74, 0.35);
        color: #dcfce7;
    }

    .dark .reviewer-pill-tone-very-high:hover {
        background-color: rgba(22, 163, 74, 0.5);
    }

    .dark .reviewer-pill-tone-high {
        border-color: #2dd4bf;
        background-color: rgba(15, 118, 110, 0.4);
        color: #ccfbf1;
    }

    .dark .reviewer-pill-tone-high:hover {
        background-color: rgba(20, 184, 166, 0.5);
    }

    .dark .reviewer-pill-tone-medium {
        border-color: #a3e635;
        background-color: rgba(132, 204, 22, 0.12);
        color: #d9f99d;
    }

    .dark .reviewer-pill-tone-medium:hover {
        background-color: rgba(132, 204, 22, 0.2);
    }

    .dark .reviewer-pill-tone-low {
        border-color: #38bdf8;
        background-color: rgba(3, 105, 161, 0.38);
        color: #bae6fd;
    }

    .dark .reviewer-pill-tone-low:hover {
        background-color: rgba(14, 165, 233, 0.48);
    }

    .dark .reviewer-pill-tone-very-low {
        border-color: #3b82f6;
        background-color: rgba(29, 78, 216, 0.58);
        color: #dbeafe;
    }

    .dark .reviewer-pill-tone-very-low:hover {
        background-color: rgba(29, 78, 216, 0.7);
    }

    .reviewer-gradient-slider {
        -webkit-appearance: none;
        appearance: none;
        width: 100%;
        height: 10px;
        border-radius: 9999px;
        background: linear-gradient(90deg, #3b82f6 0%, #22c55e 100%);
        outline: none;
    }

    .reviewer-gradient-slider::-webkit-slider-runnable-track {
        height: 10px;
        border-radius: 9999px;
        background: linear-gradient(90deg, #3b82f6 0%, #22c55e 100%);
    }

    .reviewer-gradient-slider::-webkit-slider-thumb {
        -webkit-appearance: none;
        appearance: none;
        height: 16px;
        width: 16px;
        border-radius: 9999px;
        background: #ffffff;
        border: 2px solid #5f2ded;
        margin-top: -3px;
        cursor: pointer;
    }

    .reviewer-gradient-slider::-moz-range-track {
        height: 10px;
        border-radius: 9999px;
        background: linear-gradient(90deg, #3b82f6 0%, #22c55e 100%);
    }

    .reviewer-gradient-slider::-moz-range-thumb {
        height: 16px;
        width: 16px;
        border-radius: 9999px;
        background: #ffffff;
        border: 2px solid #5f2ded;
        cursor: pointer;
    }
</style>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        

        const reviewerForm = document.getElementById('reviewerFormMain');
        const submitBtn = document.getElementById('reviewerSubmitBtn');
        const titleInput = document.getElementById('reviewerTitleHeader');
        const objectiveInput = document.getElementById('reviewerLearningObjective');
        const lessonInput = document.getElementById('reviewerLessonBody');
        const titleError = document.getElementById('reviewerTitleError');
        const objectiveError = document.getElementById('reviewerObjectiveError');
        const lessonError = document.getElementById('reviewerLessonError');
        const guideQuestionsError = document.getElementById('reviewerGuideQuestionsError');

        const validateRequiredField = (input, errorElement) => {
            if (!input || !errorElement) return false;
            const isEmpty = !String(input.value || '').trim();
            errorElement.classList.toggle('hidden', !isEmpty);
            input.setAttribute('aria-invalid', isEmpty ? 'true' : 'false');
            return isEmpty;
        };

        reviewerForm?.addEventListener('submit', (e) => {
            let hasError = false;

            if (validateRequiredField(titleInput, titleError)) {
                hasError = true;
            }
            if (validateRequiredField(objectiveInput, objectiveError)) {
                hasError = true;
            }
            if (validateRequiredField(lessonInput, lessonError)) {
                hasError = true;
            }

            const tagError = document.getElementById('reviewerTagError');
            const selectedTagsCount = document.querySelectorAll(
                '#reviewerTagInputs input[name="tag_ids[]"]'
            ).length;

            tagError?.classList.add('hidden');

            if (selectedTagsCount === 0) {
                tagError?.classList.remove('hidden');
                hasError = true;
            }

            const questionRows = Array.from(document.querySelectorAll('.reviewer-question'));
            guideQuestionsError?.classList.add('hidden');
            if (questionRows.length === 0) {
                guideQuestionsError?.classList.remove('hidden');
                hasError = true;
            }

            questionRows.forEach((row) => {
                const questionInput = row.querySelector('.reviewer-question-input');
                const answerInput = row.querySelector('.reviewer-answer-input');
                const questionError = row.querySelector('.reviewer-question-error');
                const answerError = row.querySelector('.reviewer-answer-error');

                questionError?.classList.add('hidden');
                answerError?.classList.add('hidden');

                const q = questionInput?.value.trim() || '';
                const a = answerInput?.value.trim() || '';

                if (!q) {
                    questionError?.classList.remove('hidden');
                    hasError = true;
                }

                if (!a) {
                    answerError?.classList.remove('hidden');
                    hasError = true;
                }
            });

            if (hasError) {
                e.preventDefault();
                return;
            }

            submitBtn.disabled = true;
            submitBtn.classList.add('opacity-70', 'cursor-not-allowed');
            submitBtn.querySelector('[data-label]')?.classList.add('hidden');
            submitBtn.querySelector('[data-spinner]')?.classList.remove('hidden');
        });

        document.addEventListener('input', (e) => {
            if (e.target.classList.contains('reviewer-question-input')) {
                e.target
                    .closest('.reviewer-question')
                    ?.querySelector('.reviewer-question-error')
                    ?.classList.toggle('hidden', !!e.target.value.trim());
            }

            if (e.target.classList.contains('reviewer-answer-input')) {
                e.target
                    .closest('.reviewer-question')
                    ?.querySelector('.reviewer-answer-error')
                    ?.classList.toggle('hidden', !!e.target.value.trim());
            }

            if (e.target.id === 'reviewerTitleHeader') {
                validateRequiredField(titleInput, titleError);
            }

            if (e.target.id === 'reviewerLearningObjective') {
                validateRequiredField(objectiveInput, objectiveError);
            }

            if (e.target.id === 'reviewerLessonBody') {
                validateRequiredField(lessonInput, lessonError);
            }
        });

        const container = document.getElementById('reviewerQuestionsContainer');
        const addBtn = document.getElementById('reviewerAddQuestion');
        if (container && addBtn) {
            let nextIndex = parseInt(container.dataset.nextIndex || '0', 10);
            const maxQuestions = 5;

            const createRow = (index) => {
                const wrapper = document.createElement('div');
                wrapper.className = 'reviewer-question bg-white dark:bg-[#2A2A3C] border border-gray-200 dark:border-gray-600 rounded-lg p-4 shadow-sm relative transition-all duration-200 hover:shadow-md hover:bg-gray-50 dark:hover:bg-[#3A3A4F]';
                wrapper.innerHTML = `
                    <button type="button"
                        class="reviewer-remove-question absolute top-3 right-3 text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 transition-colors text-lg font-bold"
                        aria-label="Remove guide question">
                        &times;
                    </button>
                    <input type="text"
                        name="quiz_questions[${index}][question]"
                        class="block w-full max-w-xl mb-1 p-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-[#1E1E2D] text-gray-800 dark:text-white reviewer-question-input"
                        placeholder="Enter guide question">

                    <p class="text-xs text-red-500 hidden reviewer-question-error">
                        Guide question is required.
                    </p>

                    <input type="text"
                        name="quiz_questions[${index}][answer]"
                        class="block w-full mt-2 mb-1 max-w-md p-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-[#1E1E2D] text-gray-800 dark:text-white reviewer-answer-input"
                        placeholder="Enter answer">

                    <p class="text-xs text-red-500 hidden reviewer-answer-error">
                        Answer is required.
                    </p>

                `;
                return wrapper;
            };

            const updateAddQuestionState = () => {
                const isAtMax = container.children.length >= maxQuestions;
                addBtn.disabled = isAtMax;
                addBtn.classList.toggle('opacity-50', isAtMax);
                addBtn.classList.toggle('cursor-not-allowed', isAtMax);
            };

            addBtn.addEventListener('click', () => {
                if (container.children.length >= maxQuestions) return;
                const row = createRow(nextIndex);
                nextIndex += 1;
                container.appendChild(row);
                guideQuestionsError?.classList.add('hidden');
                updateAddQuestionState();
            });

            container.addEventListener('click', (event) => {
                const target = event.target;
                if (!(target instanceof Element)) return;
                const removeBtn = target.closest('.reviewer-remove-question');
                if (!removeBtn) return;
                const row = removeBtn.closest('.reviewer-question');
                row?.remove();
                if (container.children.length > 0) {
                    guideQuestionsError?.classList.add('hidden');
                }
                updateAddQuestionState();
            });

            updateAddQuestionState();
        }

        const escapeHtml = (value) => {
            return String(value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        };

        const tagInputsContainer = document.getElementById('reviewerTagInputs');
        const tagPills = document.getElementById('reviewerTagPills');
        const noTagMessage = document.getElementById('reviewerNoTagMessage');
        const openTagsModalBtn = document.getElementById('reviewerOpenTagsModal');
        const clearTagBtn = document.getElementById('reviewerClearTagBtn');
        const tagsModal = document.getElementById('reviewerTagsModal');
        const closeTagsModalBtn = document.getElementById('reviewerCloseTagsModal');
        const confirmTagsModalBtn = document.getElementById('reviewerConfirmTagsModal');
        const tagSearchInput = document.getElementById('reviewerTagSearchInput');
        const tagListContainer = document.getElementById('reviewerTagListContainer');
        const tagListEmpty = document.getElementById('reviewerTagListEmpty');
        const tagPagination = document.getElementById('reviewerTagPagination');
        const tagPrevPageBtn = document.getElementById('reviewerTagPrevPage');
        const tagNextPageBtn = document.getElementById('reviewerTagNextPage');
        const tagPageInfo = document.getElementById('reviewerTagPageInfo');
        const openCategoryPickerBtn = document.getElementById('reviewerOpenCategoryPicker');
        const currentCategoryLabel = document.getElementById('reviewerCurrentCategoryLabel');
        const backToTagsBtn = document.getElementById('reviewerBackToTags');
        const tagCategoryPicker = document.getElementById('reviewerTagCategoryPicker');
        const categoryPagination = document.getElementById('reviewerTagCategoryPagination');
        const categoryPrevPageBtn = document.getElementById('reviewerTagCategoryPrevPage');
        const categoryNextPageBtn = document.getElementById('reviewerTagCategoryNextPage');
        const categoryPageInfo = document.getElementById('reviewerTagCategoryPageInfo');
        const categoryListEmpty = document.getElementById('reviewerCategoryListEmpty');
        const metricModeRelevancyBtn = document.getElementById('reviewerModeRelevancyBtn');
        const metricModeCoverageBtn = document.getElementById('reviewerModeCoverageBtn');
        const metricModeSlider = document.getElementById('reviewerMetricModeSlider');
        const tagRelevancyContainer = document.getElementById('reviewerTagRelevancyContainer');
        const tagMetricsEditor = document.getElementById('reviewerTagMetricsEditor');
        const relevancyPagination = document.getElementById('reviewerRelevancyPagination');
        const relevancyPrevPageBtn = document.getElementById('reviewerRelevancyPrevPage');
        const relevancyNextPageBtn = document.getElementById('reviewerRelevancyNextPage');
        const relevancyPageInfo = document.getElementById('reviewerRelevancyPageInfo');
        const derivedTagNote = document.getElementById('reviewerTagDerivedNote');

        const tagOptions = Array.from(document.querySelectorAll('.reviewer-tag-option'));
        const tagCheckboxes = Array.from(document.querySelectorAll('.reviewer-tag-checkbox'));
        const categoryOptions = Array.from(document.querySelectorAll('.reviewer-tag-category-option'));

        const tagsPerPage = 6;
        const categoriesPerPage = 6;
        const relevancyCardsPerPage = 3;
        let currentTagPage = 1;
        let currentCategoryPage = 1;
        let currentRelevancyPage = 1;
        let filteredTagOptions = [...tagOptions];
        let filteredCategoryOptions = [...categoryOptions];
        let currentCategory = '';
        let isCategoryMode = false;
        let isRelevancyMode = false;
        let currentMetricMode = 'relevance';
        let focusedMetricTagId = null;
        let isApplyingTagAction = false;
        let derivedTagIds = new Set();
        const minTagLoadingMs = 250;
        const categoryActiveClasses = ['bg-[#F3EEFF]', 'dark:bg-[#34255F]', 'border-[#5F2DED]', 'text-[#5F2DED]', 'dark:text-white'];
        const categoryInactiveClasses = ['bg-transparent', 'border-transparent', 'text-[#5E5F60]', 'dark:text-white'];
        const relevanceLevels = ['very_high', 'high', 'medium', 'low', 'very_low'];
        const relevanceScale = ['very_low', 'low', 'medium', 'high', 'very_high'];
        const relevanceLabels = {
            very_high: 'Very High',
            high: 'High',
            medium: 'Medium',
            low: 'Low',
            very_low: 'Very Low',
        };
        const relevanceRank = {
            very_high: 0,
            high: 1,
            medium: 2,
            low: 3,
            very_low: 4,
        };
        const relevancePillToneClasses = {
            very_high: 'reviewer-pill-tone-very-high',
            high: 'reviewer-pill-tone-high',
            medium: 'reviewer-pill-tone-medium',
            low: 'reviewer-pill-tone-low',
            very_low: 'reviewer-pill-tone-very-low',
        };
        const defaultRelevance = @json(config('reviewers.default_tag_relevance', 'medium'));
        const tagMetrics = {};
        const selectedTagIds = new Set(
            Array.from(tagInputsContainer?.querySelectorAll('input[name="tag_ids[]"]') || [])
                .map((input) => String(input.value))
        );

        const getCheckedTagIds = () => {
            return tagCheckboxes
                .filter((checkbox) => checkbox.checked)
                .map((checkbox) => String(checkbox.value));
        };

        const getMetricTargetTagIds = () => {
            if (focusedMetricTagId && selectedTagIds.has(focusedMetricTagId)) {
                return [focusedMetricTagId];
            }
            return getCheckedTagIds();
        };

        const getTagNameById = (tagId) => {
            const option = tagOptions.find((item) => item.dataset.tagId === String(tagId));
            return option?.dataset.tagName || 'Concept';
        };

        const normalizeRelevance = (value) => {
            const normalized = String(value || '').trim().toLowerCase();
            if (relevanceLevels.includes(normalized)) {
                return normalized;
            }
            return relevanceLevels.includes(defaultRelevance) ? defaultRelevance : 'medium';
        };

        const normalizeCoverage = (value) => {
            if (value === null || value === undefined || value === '') {
                return '5';
            }
            const parsed = Number(value);
            if (!Number.isFinite(parsed)) {
                return '5';
            }
            const bounded = Math.min(100, Math.max(5, parsed));
            const snapped = Math.round(bounded / 5) * 5;
            return String(snapped);
        };

        tagOptions.forEach((option) => {
            const tagId = String(option.dataset.tagId || '').trim();
            if (!tagId) return;
            tagMetrics[tagId] = {
                relevance: normalizeRelevance(option.dataset.tagRelevance),
                coverage: normalizeCoverage(option.dataset.tagCoverage),
            };
        });

        const syncTagInputs = () => {
            if (!tagInputsContainer) return;
            tagInputsContainer.innerHTML = '';
            Array.from(selectedTagIds).forEach((tagId) => {
                if (!tagMetrics[tagId]) {
                    tagMetrics[tagId] = {
                        relevance: normalizeRelevance(defaultRelevance),
                        coverage: '5',
                    };
                }

                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'tag_ids[]';
                input.value = tagId;
                tagInputsContainer.appendChild(input);

                const relevanceInput = document.createElement('input');
                relevanceInput.type = 'hidden';
                relevanceInput.name = `tag_relevance[${tagId}]`;
                relevanceInput.value = tagMetrics[tagId].relevance;
                tagInputsContainer.appendChild(relevanceInput);

                const coverageInput = document.createElement('input');
                coverageInput.type = 'hidden';
                coverageInput.name = `tag_coverage[${tagId}]`;
                coverageInput.value = tagMetrics[tagId].coverage;
                tagInputsContainer.appendChild(coverageInput);
            });
        };

        const updateDerivedTagNote = () => {
            if (!derivedTagNote) return;
            if (derivedTagIds.size > 0) {
                derivedTagNote.classList.remove('hidden');
            } else {
                derivedTagNote.classList.add('hidden');
            }
        };

        const renderTagPills = () => {
            if (!tagPills) return;
            tagPills.innerHTML = '';
            const tagEntries = Array.from(selectedTagIds)
                .map((tagId) => {
                    const metric = tagMetrics[tagId] || {
                        relevance: normalizeRelevance(defaultRelevance),
                        coverage: '5',
                    };
                    const relevance = normalizeRelevance(metric.relevance);
                    const coverage = Number.parseInt(normalizeCoverage(metric.coverage), 10);
                    const normalizedCoverage = Number.isFinite(coverage) ? coverage : 5;
                    return {
                        id: tagId,
                        name: getTagNameById(tagId),
                        relevance,
                        coverage: normalizedCoverage,
                        relevanceRank: relevanceRank[relevance] ?? relevanceRank.medium,
                    };
                })
                .sort((a, b) => {
                    if (a.relevanceRank !== b.relevanceRank) {
                        return a.relevanceRank - b.relevanceRank;
                    }
                    if (a.coverage !== b.coverage) {
                        return b.coverage - a.coverage;
                    }
                    return a.name.localeCompare(b.name);
                });

            if (tagEntries.length === 0) {
                noTagMessage?.classList.remove('hidden');
                updateDerivedTagNote();
                return;
            }

            noTagMessage?.classList.add('hidden');
            tagEntries.forEach((tag) => {
                const pill = document.createElement('button');
                pill.type = 'button';
                const toneClass = relevancePillToneClasses[tag.relevance] || relevancePillToneClasses.medium;
                pill.className = `reviewer-tag-pill inline-flex items-center gap-1.5 px-3 py-1 rounded-full border transition ${toneClass}`;
                pill.dataset.tagId = String(tag.id);
                pill.title = 'Edit relevance';
                pill.innerHTML = `
                    <span class="text-sm">${escapeHtml(tag.name)}</span>
                    <span class="ml-1 text-[10px] leading-none opacity-80">${tag.coverage}%</span>
                    <span class="reviewer-tag-remove text-xs leading-none opacity-70 hover:opacity-100 hover:text-red-500">x</span>
                `;
                tagPills.appendChild(pill);
            });
            updateDerivedTagNote();
        };

        const clearTagSelection = () => {
            selectedTagIds.clear();
            focusedMetricTagId = null;
            tagCheckboxes.forEach((checkbox) => {
                checkbox.checked = false;
            });
            syncTagInputs();
            renderTagPills();
        };

        const setTagSelections = (tagIds) => {
            selectedTagIds.clear();
            tagIds.forEach((tagId) => {
                if (tagId !== null && tagId !== '') {
                    selectedTagIds.add(String(tagId));
                }
            });
            if (focusedMetricTagId && !selectedTagIds.has(focusedMetricTagId)) {
                focusedMetricTagId = null;
            }
            tagCheckboxes.forEach((checkbox) => {
                checkbox.checked = selectedTagIds.has(String(checkbox.value));
            });
            syncTagInputs();
            renderTagPills();
        };

        if (selectedTagIds.size > 0) {
            syncTagInputs();
            renderTagPills();
        }

        const toggleTagsModal = (show) => {
            if (!tagsModal) return;
            tagsModal.classList.toggle('hidden', !show);
            tagsModal.classList.toggle('flex', show);
            document.body.classList.toggle('modal-open', show);
        };

        const updateSearchPlaceholder = () => {
            if (!tagSearchInput) return;
            if (isRelevancyMode) {
                tagSearchInput.placeholder = 'Search selected tags...';
                return;
            }
            tagSearchInput.placeholder = isCategoryMode ? 'Search categories...' : 'Search tags...';
        };

        const setMetricModeSwitchState = () => {
            const isCoverageMode = currentMetricMode === 'coverage';
            if (metricModeSlider) {
                metricModeSlider.style.opacity = isRelevancyMode ? '1' : '0';
                metricModeSlider.style.transform = isCoverageMode ? 'translateX(100%)' : 'translateX(0)';
            }

            const setButtonState = (button, isActive) => {
                if (!button) return;
                button.classList.toggle('text-white', isRelevancyMode && isActive);
                button.classList.toggle('text-[#5E5F60]', !(isRelevancyMode && isActive));
                button.classList.toggle('dark:text-gray-300', !(isRelevancyMode && isActive));
            };

            setButtonState(metricModeRelevancyBtn, !isCoverageMode);
            setButtonState(metricModeCoverageBtn, isCoverageMode);
        };

        const getRelevancyTotalPages = (selectedIds) => {
            if (!Array.isArray(selectedIds) || selectedIds.length === 0) {
                return 0;
            }
            return Math.ceil(selectedIds.length / relevancyCardsPerPage);
        };

        const getFilteredRelevancyTagIds = (selectedIds) => {
            if (!Array.isArray(selectedIds)) {
                return [];
            }
            const term = (tagSearchInput?.value || '').trim().toLowerCase();
            if (!term) {
                return selectedIds;
            }

            return selectedIds.filter((id) => {
                const tagId = String(id);
                const option = tagOptions.find((item) => item.dataset.tagId === tagId);
                const name = String(option?.dataset.tagName || '').toLowerCase();
                const category = String(option?.dataset.tagCategory || '').toLowerCase();
                return name.includes(term) || category.includes(term);
            });
        };

        const syncRelevancyPaginationState = (totalPages) => {
            if (!relevancyPagination || !relevancyPrevPageBtn || !relevancyNextPageBtn || !relevancyPageInfo) {
                return;
            }

            const hasPages = totalPages > 0;
            const clampedPages = hasPages ? totalPages : 1;
            const page = Math.min(clampedPages, Math.max(1, currentRelevancyPage));
            currentRelevancyPage = page;

            relevancyPageInfo.textContent = `Page ${page} of ${clampedPages}`;
            relevancyPrevPageBtn.disabled = page <= 1;
            relevancyNextPageBtn.disabled = page >= clampedPages;
            relevancyPagination.classList.toggle('hidden', !isRelevancyMode || totalPages <= 1);
        };

        const setCategoryButtonState = (button, isActive) => {
            if (!button) return;
            if (isActive) {
                categoryInactiveClasses.forEach((cls) => button.classList.remove(cls));
                categoryActiveClasses.forEach((cls) => button.classList.add(cls));
                return;
            }
            categoryActiveClasses.forEach((cls) => button.classList.remove(cls));
            categoryInactiveClasses.forEach((cls) => button.classList.add(cls));
        };

        const refreshCategoryButtonStates = () => {
            const selectedCategory = String(currentCategory || '').trim().toLowerCase();
            categoryOptions.forEach((button) => {
                const buttonCategory = String(button.dataset.tagCategory || '').trim().toLowerCase();
                setCategoryButtonState(button, buttonCategory === selectedCategory);
            });
        };

        const setTagActionLoading = (button, fallbackText) => {
            if (!button) return;
            if (window.AdminModalLoading && typeof window.AdminModalLoading.setLoading === 'function') {
                window.AdminModalLoading.setLoading(button, {
                    text: button.dataset.loadingText || fallbackText,
                });
                return;
            }
            button.disabled = true;
            button.classList.add('opacity-75', 'cursor-wait');
        };

        const clearTagActionLoading = (button) => {
            if (!button) return;
            if (window.AdminModalLoading && typeof window.AdminModalLoading.clearLoading === 'function') {
                window.AdminModalLoading.clearLoading(button);
                return;
            }
            button.disabled = false;
            button.classList.remove('opacity-75', 'cursor-wait');
        };

        const finishTagAction = (triggerBtn, startedAt) => {
            const elapsed = Date.now() - startedAt;
            const remaining = Math.max(0, minTagLoadingMs - elapsed);

            window.setTimeout(() => {
                clearTagActionLoading(triggerBtn);
                if (confirmTagsModalBtn && triggerBtn !== confirmTagsModalBtn) {
                    confirmTagsModalBtn.disabled = false;
                }
                if (clearTagBtn && triggerBtn !== clearTagBtn) {
                    clearTagBtn.disabled = false;
                }
                isApplyingTagAction = false;
            }, remaining);
        };

        const toggleCategoryPicker = (show) => {
            if (isRelevancyMode) {
                return;
            }
            isCategoryMode = show;
            tagCategoryPicker?.classList.toggle('hidden', !show);
            tagListContainer?.classList.toggle('hidden', show);
            tagRelevancyContainer?.classList.add('hidden');
            backToTagsBtn?.classList.toggle('hidden', !(show || isRelevancyMode));
            if (tagPagination) {
                tagPagination.classList.toggle('hidden', show);
            }
            if (categoryPagination) {
                categoryPagination.classList.toggle('hidden', !show);
            }
            categoryListEmpty?.classList.add('hidden');
            updateSearchPlaceholder();
            if (show) refreshCategoryButtonStates();
        };

        const toggleRelevancyMode = (show, mode = currentMetricMode) => {
            currentMetricMode = mode === 'coverage' ? 'coverage' : 'relevance';
            isRelevancyMode = show;

            if (show) {
                currentRelevancyPage = 1;
                isCategoryMode = false;
                tagCategoryPicker?.classList.add('hidden');
                tagListContainer?.classList.add('hidden');
                tagRelevancyContainer?.classList.remove('hidden');
                backToTagsBtn?.classList.remove('hidden');
                tagPagination?.classList.add('hidden');
                categoryPagination?.classList.add('hidden');
                categoryListEmpty?.classList.add('hidden');
                tagListEmpty?.classList.add('hidden');
                renderTagMetricsEditor(getMetricTargetTagIds());
            } else {
                tagRelevancyContainer?.classList.add('hidden');
                syncRelevancyPaginationState(0);
                toggleCategoryPicker(false);
                filterTags();
            }

            updateSearchPlaceholder();
            setMetricModeSwitchState();
        };

        const getTagTotalPages = () => {
            if (filteredTagOptions.length === 0) return 0;
            return Math.ceil(filteredTagOptions.length / tagsPerPage);
        };

        const getCategoryTotalPages = () => {
            if (filteredCategoryOptions.length === 0) return 0;
            return Math.ceil(filteredCategoryOptions.length / categoriesPerPage);
        };

        const applyTagPagination = () => {
            if (tagOptions.length === 0) {
                tagListEmpty?.classList.add('hidden');
                if (tagPagination) tagPagination.classList.add('hidden');
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
                option.classList.toggle('hidden', !visibleOptions.has(option));
            });

            if (tagPageInfo) {
                tagPageInfo.textContent = totalPages > 0
                    ? `Page ${currentTagPage} of ${totalPages}`
                    : 'Page 0 of 0';
            }
            if (tagPagination) {
                tagPagination.classList.toggle('hidden', totalPages <= 1);
            }
            if (tagPrevPageBtn) {
                tagPrevPageBtn.disabled = currentTagPage <= 1;
            }
            if (tagNextPageBtn) {
                tagNextPageBtn.disabled = currentTagPage >= totalPages;
            }
            if (tagListEmpty) {
                tagListEmpty.classList.toggle('hidden', filteredTagOptions.length > 0);
            }
        };

        const applyCategoryPagination = () => {
            if (categoryOptions.length === 0) {
                categoryPagination?.classList.add('hidden');
                categoryListEmpty?.classList.add('hidden');
                return;
            }

            const totalPages = getCategoryTotalPages();
            if (currentCategoryPage > totalPages) {
                currentCategoryPage = totalPages || 1;
            }

            const start = (currentCategoryPage - 1) * categoriesPerPage;
            const end = start + categoriesPerPage;
            const visibleOptions = new Set(filteredCategoryOptions.slice(start, end));

            categoryOptions.forEach((option) => {
                option.classList.toggle('hidden', !visibleOptions.has(option));
            });

            if (categoryPageInfo) {
                categoryPageInfo.textContent = totalPages > 0
                    ? `Page ${currentCategoryPage} of ${totalPages}`
                    : 'Page 0 of 0';
            }
            if (categoryPagination) {
                categoryPagination.classList.toggle('hidden', totalPages <= 1);
            }
            if (categoryPrevPageBtn) {
                categoryPrevPageBtn.disabled = currentCategoryPage <= 1;
            }
            if (categoryNextPageBtn) {
                categoryNextPageBtn.disabled = currentCategoryPage >= totalPages;
            }
            if (categoryListEmpty) {
                categoryListEmpty.classList.toggle('hidden', filteredCategoryOptions.length > 0);
            }
            refreshCategoryButtonStates();
        };

        const filterTags = () => {
            const query = (tagSearchInput?.value || '').toLowerCase().trim();
            const categoryValue = currentCategory.toLowerCase();
            filteredTagOptions = tagOptions.filter((option) => {
                const name = (option.dataset.tagName || '').toLowerCase();
                const category = (option.dataset.tagCategory || '').toLowerCase();
                const matchesCategory = !categoryValue || category === categoryValue;
                const matchesQuery = !query || name.includes(query) || category.includes(query);
                return matchesCategory && matchesQuery;
            });

            currentTagPage = 1;
            applyTagPagination();
        };

        const filterCategories = () => {
            const query = (tagSearchInput?.value || '').toLowerCase().trim();
            filteredCategoryOptions = categoryOptions.filter((option) => {
                const label = (option.dataset.tagCategory || 'All categories').toLowerCase();
                return !query || label.includes(query);
            });
            currentCategoryPage = 1;
            applyCategoryPagination();
        };

        const renderTagMetricsEditor = (selectedIds) => {
            if (!tagMetricsEditor) return;

            if (!selectedIds.length) {
                tagMetricsEditor.innerHTML = '<p class="text-xs text-gray-500 dark:text-gray-300">Select at least one tag to configure relevance and coverage.</p>';
                syncRelevancyPaginationState(0);
                return;
            }

            const filteredSelectedIds = getFilteredRelevancyTagIds(selectedIds);
            if (!filteredSelectedIds.length) {
                tagMetricsEditor.innerHTML = '<p class="text-xs text-gray-500 dark:text-gray-300">No selected tags match your search.</p>';
                syncRelevancyPaginationState(0);
                return;
            }

            const totalPages = getRelevancyTotalPages(filteredSelectedIds);
            if (currentRelevancyPage > totalPages) {
                currentRelevancyPage = totalPages || 1;
            }

            const start = (currentRelevancyPage - 1) * relevancyCardsPerPage;
            const end = start + relevancyCardsPerPage;
            const visibleSelectedIds = filteredSelectedIds.slice(start, end);

            const rows = visibleSelectedIds.map((id) => {
                const tagId = String(id);
                const option = tagOptions.find((item) => item.dataset.tagId === tagId);
                const tagName = option?.dataset.tagName || 'Tag';
                const metric = tagMetrics[tagId] || {
                    relevance: normalizeRelevance(defaultRelevance),
                    coverage: '5',
                };
                const currentRelevance = normalizeRelevance(metric.relevance);
                const relevanceIndex = Math.max(0, relevanceScale.indexOf(currentRelevance));
                const currentCoverage = normalizeCoverage(metric.coverage);
                const isCoverageMode = currentMetricMode === 'coverage';

                return `
                    <div class="border border-[#94A3B8] dark:border-[#475569] rounded-none p-3 transition-colors hover:bg-[#EFF6FF] dark:hover:bg-[#12243D] focus-within:bg-[#DBEAFE] dark:focus-within:bg-[#1E3A5F]">
                        <p class="text-sm text-[#373737] dark:text-white font-medium">${escapeHtml(tagName)}</p>
                        <div class="mt-2 ${isCoverageMode ? 'hidden' : ''}" data-metric-relevance-panel="${tagId}">
                            <div class="flex items-center gap-3">
                                <div class="flex-1" style="padding-right: 0.5in;">
                                    <input type="range"
                                        min="0" max="4" step="1"
                                        value="${relevanceIndex}"
                                        data-metric-relevance-slider="${tagId}"
                                        class="reviewer-gradient-slider w-full">
                                </div>
                                <span data-metric-relevance-current="${tagId}" style="width: 100px; flex: 0 0 100px;" class="shrink-0 whitespace-nowrap text-right text-sm font-semibold font-poppins text-[#334155] dark:text-white">
                                    ${escapeHtml(relevanceLabels[currentRelevance] || 'Medium')}
                                </span>
                            </div>
                        </div>
                        <div class="mt-2 ${isCoverageMode ? '' : 'hidden'}" data-metric-coverage-panel="${tagId}">
                            <div class="flex items-center gap-3">
                                <div class="flex-1" style="padding-right: 0.5in;">
                                    <input type="range"
                                        min="5" max="100" step="5"
                                        value="${currentCoverage}"
                                        data-metric-coverage-slider="${tagId}"
                                        class="reviewer-gradient-slider w-full">
                                </div>
                                <span data-metric-coverage-current="${tagId}" style="width: 100px; flex: 0 0 100px;" class="shrink-0 whitespace-nowrap text-right text-sm font-semibold font-poppins text-[#334155] dark:text-white">
                                    ${currentCoverage}%
                                </span>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');

            tagMetricsEditor.innerHTML = rows;

            tagMetricsEditor.querySelectorAll('[data-metric-relevance-slider]').forEach((element) => {
                element.addEventListener('input', (event) => {
                    const target = event.target;
                    if (!(target instanceof HTMLInputElement)) return;
                    const tagId = String(target.getAttribute('data-metric-relevance-slider') || '');
                    if (!tagId) return;
                    const rawIndex = Number.parseInt(target.value, 10);
                    const clampedIndex = Number.isFinite(rawIndex)
                        ? Math.min(4, Math.max(0, rawIndex))
                        : 0;
                    target.value = String(clampedIndex);
                    const relevanceValue = normalizeRelevance(relevanceScale[clampedIndex] || defaultRelevance);

                    if (!tagMetrics[tagId]) {
                        tagMetrics[tagId] = { relevance: normalizeRelevance(defaultRelevance), coverage: '5' };
                    }
                    tagMetrics[tagId].relevance = relevanceValue;
                    const relevanceLabel = tagMetricsEditor.querySelector(`[data-metric-relevance-current="${tagId}"]`);
                    if (relevanceLabel) {
                        relevanceLabel.textContent = relevanceLabels[relevanceValue] || 'Medium';
                    }
                    syncTagInputs();
                    renderTagPills();
                });
            });

            tagMetricsEditor.querySelectorAll('[data-metric-coverage-slider]').forEach((element) => {
                element.addEventListener('input', (event) => {
                    const target = event.target;
                    if (!(target instanceof HTMLInputElement)) return;
                    const tagId = String(target.getAttribute('data-metric-coverage-slider') || '');
                    if (!tagId) return;
                    if (!tagMetrics[tagId]) {
                        tagMetrics[tagId] = { relevance: normalizeRelevance(defaultRelevance), coverage: '5' };
                    }
                    const normalizedCoverage = normalizeCoverage(target.value);
                    target.value = normalizedCoverage;
                    tagMetrics[tagId].coverage = normalizedCoverage;
                    const coverageLabel = tagMetricsEditor.querySelector(`[data-metric-coverage-current="${tagId}"]`);
                    if (coverageLabel) {
                        coverageLabel.textContent = `${normalizedCoverage}%`;
                    }
                    syncTagInputs();
                    renderTagPills();
                });
            });

            syncRelevancyPaginationState(totalPages);
        };

        const updateTagErrorState = () => {
            const tagError = document.getElementById('reviewerTagError');
            const count = document.querySelectorAll(
                '#reviewerTagInputs input[name="tag_ids[]"]'
            ).length;

            if (count > 0) {
                tagError?.classList.add('hidden');
            }
        };

        const runTagAction = (triggerBtn, fallbackText, action) => {
            if (isApplyingTagAction) return;
            isApplyingTagAction = true;
            const actionStartAt = Date.now();

            setTagActionLoading(triggerBtn, fallbackText);
            if (confirmTagsModalBtn && triggerBtn !== confirmTagsModalBtn) {
                confirmTagsModalBtn.disabled = true;
            }
            if (clearTagBtn && triggerBtn !== clearTagBtn) {
                clearTagBtn.disabled = true;
            }

            requestAnimationFrame(() => {
                try {
                    action();
                } finally {
                    finishTagAction(triggerBtn, actionStartAt);
                }
            });
        };

        openTagsModalBtn?.addEventListener('click', () => {
            focusedMetricTagId = null;
            if (tagSearchInput) {
                tagSearchInput.value = '';
            }
            toggleRelevancyMode(false);
            toggleCategoryPicker(false);
            filterTags();
            tagCheckboxes.forEach((checkbox) => {
                checkbox.checked = selectedTagIds.has(String(checkbox.value));
            });
            toggleTagsModal(true);
        });

        closeTagsModalBtn?.addEventListener('click', () => {
            focusedMetricTagId = null;
            toggleRelevancyMode(false);
            toggleCategoryPicker(false);
            toggleTagsModal(false);
        });

        confirmTagsModalBtn?.addEventListener('click', () => {
            runTagAction(confirmTagsModalBtn, 'Saving...', () => {
                const selected = tagCheckboxes
                    .filter((checkbox) => checkbox.checked)
                    .map((checkbox) => checkbox.value);
                setTagSelections(selected);
                updateTagErrorState();
                renderTagMetricsEditor(Array.from(selectedTagIds));
                focusedMetricTagId = null;
                toggleCategoryPicker(false);
                toggleRelevancyMode(false);
                toggleTagsModal(false);
            });
        });

        tagSearchInput?.addEventListener('input', () => {
            if (isRelevancyMode) {
                currentRelevancyPage = 1;
                renderTagMetricsEditor(getMetricTargetTagIds());
                return;
            }
            if (isCategoryMode) {
                filterCategories();
            } else {
                filterTags();
            }
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

        categoryPrevPageBtn?.addEventListener('click', () => {
            if (currentCategoryPage <= 1) return;
            currentCategoryPage -= 1;
            applyCategoryPagination();
        });

        categoryNextPageBtn?.addEventListener('click', () => {
            const totalPages = getCategoryTotalPages();
            if (currentCategoryPage >= totalPages) return;
            currentCategoryPage += 1;
            applyCategoryPagination();
        });

        metricModeRelevancyBtn?.addEventListener('click', () => {
            toggleRelevancyMode(true, 'relevance');
        });

        metricModeCoverageBtn?.addEventListener('click', () => {
            toggleRelevancyMode(true, 'coverage');
        });

        relevancyPrevPageBtn?.addEventListener('click', () => {
            if (currentRelevancyPage <= 1) return;
            currentRelevancyPage -= 1;
            renderTagMetricsEditor(getMetricTargetTagIds());
        });

        relevancyNextPageBtn?.addEventListener('click', () => {
            const totalPages = getRelevancyTotalPages(getFilteredRelevancyTagIds(getMetricTargetTagIds()));
            if (currentRelevancyPage >= totalPages) return;
            currentRelevancyPage += 1;
            renderTagMetricsEditor(getMetricTargetTagIds());
        });

        openCategoryPickerBtn?.addEventListener('click', () => {
            if (isRelevancyMode) {
                toggleRelevancyMode(false);
            }
            currentCategoryPage = 1;
            toggleCategoryPicker(true);
            filterCategories();
        });

        backToTagsBtn?.addEventListener('click', () => {
            if (isRelevancyMode) {
                focusedMetricTagId = null;
                toggleRelevancyMode(false);
                return;
            }
            toggleCategoryPicker(false);
            filterTags();
        });

        categoryOptions.forEach((button) => {
            button.addEventListener('click', () => {
                currentCategory = button.dataset.tagCategory || '';
                if (currentCategoryLabel) {
                    currentCategoryLabel.textContent = currentCategory || 'All categories';
                }
                refreshCategoryButtonStates();
                toggleCategoryPicker(false);
                filterTags();
            });
        });

        tagPills?.addEventListener('click', (event) => {
            const target = event.target;
            if (!(target instanceof Element)) return;
            const pill = target.closest('.reviewer-tag-pill');
            if (!pill) return;
            const tagId = String(pill.dataset.tagId || '');
            if (!tagId) return;

            const removeBtn = target.closest('.reviewer-tag-remove');
            if (removeBtn) {
                selectedTagIds.delete(tagId);
                if (focusedMetricTagId === tagId) {
                    focusedMetricTagId = null;
                }
                tagCheckboxes.forEach((checkbox) => {
                    checkbox.checked = selectedTagIds.has(String(checkbox.value));
                });
                syncTagInputs();
                renderTagPills();
                return;
            }

            if (!selectedTagIds.has(tagId)) {
                return;
            }

            focusedMetricTagId = tagId;
            currentMetricMode = 'relevance';
            currentRelevancyPage = 1;
            if (tagSearchInput) {
                tagSearchInput.value = '';
            }
            toggleCategoryPicker(false);
            filterTags();
            tagCheckboxes.forEach((checkbox) => {
                checkbox.checked = selectedTagIds.has(String(checkbox.value));
            });
            toggleTagsModal(true);
            toggleRelevancyMode(true, 'relevance');
        });

        tagCheckboxes.forEach((checkbox) => {
            checkbox.addEventListener('change', () => {
                if (!isRelevancyMode) return;
                currentRelevancyPage = 1;
                renderTagMetricsEditor(getMetricTargetTagIds());
            });
        });

        clearTagBtn?.addEventListener('click', () => {
            runTagAction(clearTagBtn, 'Clearing...', () => {
                clearTagSelection();
            });
        });

        const classworkOptions = @json($classworkOptions);
        const classworkSummary = document.getElementById('reviewerClassworkSummary');
        const openClassworkModalBtn = document.getElementById('reviewerOpenClassworkModal');
        const closeClassworkModalBtn = document.getElementById('reviewerCloseClassworkModal');
        const classworkModal = document.getElementById('reviewerClassworkModal');
        const classworkSearchInput = document.getElementById('reviewerClassworkSearch');
        const classworkTypeFilter = document.getElementById('reviewerClassworkTypeFilter');
        const classworkPeriodFilter = document.getElementById('reviewerClassworkPeriodFilter');
        const classworkListEmpty = document.getElementById('reviewerClassworkListEmpty');
        const classworkPagination = document.getElementById('reviewerClassworkPagination');
        const classworkPrevPageBtn = document.getElementById('reviewerClassworkPrevPage');
        const classworkNextPageBtn = document.getElementById('reviewerClassworkNextPage');
        const classworkPageInfo = document.getElementById('reviewerClassworkPageInfo');
        const classworkOptionEls = Array.from(document.querySelectorAll('.reviewer-classwork-option'));
        const sourceTypeInput = document.getElementById('reviewerSourceType');
        const sourceIdInput = document.getElementById('reviewerSourceId');
        const clearClassworkBtn = document.getElementById('reviewerClearClassworkBtn');

        const periodLabels = {
            '1st': '1st Quarter',
            '2nd': '2nd Quarter',
            '3rd': '3rd Quarter',
            '4th': '4th Quarter',
        };
        const typeLabels = {
            'quiz': 'Quiz',
            'activity': 'Assignment',
            'evaluation': 'Evaluation',
        };

        const classworkPerPage = 6;
        let currentClassworkPage = 1;
        let filteredClassworkOptionEls = [...classworkOptionEls];

        const toggleClassworkModal = (show) => {
            if (!classworkModal) return;
            classworkModal.classList.toggle('hidden', !show);
            classworkModal.classList.toggle('flex', show);
        };

        const updateClassworkSummary = (item) => {
            if (!classworkSummary) return;
            if (!item) {
                classworkSummary.textContent = '';
                return;
            }
            const periodLabel = periodLabels[item.grading_period] || item.grading_period || '';
            const typeLabel = item.type_label || typeLabels[item.type] || item.type;
            const tags = Array.isArray(item.tags) ? item.tags : [];
            const tagHtml = tags.length
                ? tags.map((tag) => (
                    `<span class="text-xs px-2 py-0.5 rounded-full border border-[#ABB7C2] text-[#5E5F60] dark:text-gray-300">${escapeHtml(tag.name)}</span>`
                )).join('')
                : '<span class="text-xs text-gray-400">No tags</span>';
            classworkSummary.innerHTML = `
                <div class="flex flex-col gap-1">
                    <span class="font-medium">${escapeHtml(item.title)}</span>
                    <span class="text-xs text-gray-500 dark:text-gray-300">${escapeHtml(typeLabel)} &bull; ${escapeHtml(periodLabel)}</span>
                    <div class="flex flex-wrap gap-1">${tagHtml}</div>
                </div>
            `;
        };

        const highlightSelectedClasswork = () => {
            const currentType = sourceTypeInput?.value;
            const currentId = sourceIdInput?.value;
            classworkOptionEls.forEach((el) => {
                const index = parseInt(el.dataset.index || '-1', 10);
                const item = classworkOptions[index];
                const isSelected = item && String(item.type) === String(currentType) && String(item.id) === String(currentId);
                el.classList.toggle('bg-gray-50', isSelected);
            });
        };

        const updateDerivedTagIds = (item) => {
            derivedTagIds = new Set();
            if (item && Array.isArray(item.tags)) {
                item.tags.forEach((tag) => {
                    derivedTagIds.add(String(tag.id));
                });
            }
            updateDerivedTagNote();
            renderTagPills();
        };

        const applyDerivedTags = (item) => {
            if (!item || !Array.isArray(item.tags) || item.tags.length === 0) {
                updateDerivedTagIds(item);
                return;
            }
            item.tags.forEach((tag) => {
                const tagId = String(tag.id);
                selectedTagIds.add(tagId);
                if (!tagMetrics[tagId]) {
                    tagMetrics[tagId] = {
                        relevance: normalizeRelevance(defaultRelevance),
                        coverage: '5',
                    };
                }
                if (tag.relevance !== undefined && tag.relevance !== null) {
                    tagMetrics[tagId].relevance = normalizeRelevance(tag.relevance);
                }
                if (tag.coverage !== undefined && tag.coverage !== null && tag.coverage !== '') {
                    tagMetrics[tagId].coverage = normalizeCoverage(tag.coverage);
                }
            });
            tagCheckboxes.forEach((checkbox) => {
                checkbox.checked = selectedTagIds.has(String(checkbox.value));
            });
            syncTagInputs();
            updateDerivedTagIds(item);
        };

        const selectClassworkByIndex = (index, deriveTag = true) => {
            const item = classworkOptions[index];
            if (!item) return;
            if (sourceTypeInput) sourceTypeInput.value = item.type;
            if (sourceIdInput) sourceIdInput.value = item.id;
            updateClassworkSummary(item);
            highlightSelectedClasswork();
            updateDerivedTagIds(item);
            if (deriveTag) {
                applyDerivedTags(item);
            }
        };

        const getClassworkTotalPages = () => {
            if (filteredClassworkOptionEls.length === 0) return 0;
            return Math.ceil(filteredClassworkOptionEls.length / classworkPerPage);
        };

        const applyClassworkPagination = () => {
            if (classworkOptionEls.length === 0) {
                classworkListEmpty?.classList.add('hidden');
                classworkPagination?.classList.add('hidden');
                return;
            }

            const totalPages = getClassworkTotalPages();
            if (currentClassworkPage > totalPages) {
                currentClassworkPage = totalPages || 1;
            }

            const start = (currentClassworkPage - 1) * classworkPerPage;
            const end = start + classworkPerPage;
            const visibleOptions = new Set(filteredClassworkOptionEls.slice(start, end));

            classworkOptionEls.forEach((el) => {
                el.classList.toggle('hidden', !visibleOptions.has(el));
            });

            if (classworkPageInfo) {
                classworkPageInfo.textContent = totalPages > 0
                    ? `Page ${currentClassworkPage} of ${totalPages}`
                    : 'Page 0 of 0';
            }
            if (classworkPagination) {
                classworkPagination.classList.toggle('hidden', totalPages <= 1);
            }
            if (classworkPrevPageBtn) {
                classworkPrevPageBtn.disabled = currentClassworkPage <= 1;
            }
            if (classworkNextPageBtn) {
                classworkNextPageBtn.disabled = currentClassworkPage >= totalPages;
            }
            if (classworkListEmpty) {
                classworkListEmpty.classList.toggle('hidden', filteredClassworkOptionEls.length > 0);
            }
        };

        const filterClasswork = () => {
            const query = (classworkSearchInput?.value || '').toLowerCase().trim();
            const typeFilter = classworkTypeFilter?.value || '';
            const periodFilter = classworkPeriodFilter?.value || '';
            filteredClassworkOptionEls = classworkOptionEls.filter((el) => {
                const title = el.dataset.title || '';
                const type = el.dataset.type || '';
                const period = el.dataset.period || '';
                const matchesQuery = !query || title.includes(query);
                const matchesType = !typeFilter || typeFilter === type;
                const matchesPeriod = !periodFilter || periodFilter === period;
                return matchesQuery && matchesType && matchesPeriod;
            });

            currentClassworkPage = 1;
            applyClassworkPagination();
        };

        const initClassworkSelection = () => {
            const sourceType = sourceTypeInput?.value;
            const sourceId = sourceIdInput?.value;
            if (!sourceType || !sourceId) {
                updateClassworkSummary(null);
                highlightSelectedClasswork();
                updateDerivedTagIds(null);
                return;
            }
            const index = classworkOptions.findIndex((item) => (
                String(item.type) === String(sourceType) && String(item.id) === String(sourceId)
            ));
            if (index === -1) {
                updateClassworkSummary(null);
                highlightSelectedClasswork();
                updateDerivedTagIds(null);
                return;
            }
            updateClassworkSummary(classworkOptions[index]);
            highlightSelectedClasswork();
            updateDerivedTagIds(classworkOptions[index]);
            applyDerivedTags(classworkOptions[index]);
        };

        initClassworkSelection();

        openClassworkModalBtn?.addEventListener('click', () => {
            filterClasswork();
            toggleClassworkModal(true);
        });

        closeClassworkModalBtn?.addEventListener('click', () => {
            toggleClassworkModal(false);
        });

        classworkSearchInput?.addEventListener('input', () => {
            filterClasswork();
        });

        classworkTypeFilter?.addEventListener('change', () => {
            filterClasswork();
        });

        classworkPeriodFilter?.addEventListener('change', () => {
            filterClasswork();
        });

        classworkPrevPageBtn?.addEventListener('click', () => {
            if (currentClassworkPage <= 1) return;
            currentClassworkPage -= 1;
            applyClassworkPagination();
        });

        classworkNextPageBtn?.addEventListener('click', () => {
            const totalPages = getClassworkTotalPages();
            if (currentClassworkPage >= totalPages) return;
            currentClassworkPage += 1;
            applyClassworkPagination();
        });

        classworkOptionEls.forEach((el) => {
            el.addEventListener('click', () => {
                const index = parseInt(el.dataset.index || '-1', 10);
                selectClassworkByIndex(index, true);
                toggleClassworkModal(false);
            });
        });

        clearClassworkBtn?.addEventListener('click', () => {
            if (sourceTypeInput) sourceTypeInput.value = '';
            if (sourceIdInput) sourceIdInput.value = '';
            updateClassworkSummary(null);
            highlightSelectedClasswork();
            updateDerivedTagIds(null);
        });

        setMetricModeSwitchState();
    });
</script>
@endsection

