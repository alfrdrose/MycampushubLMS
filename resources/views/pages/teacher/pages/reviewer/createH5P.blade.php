@extends('pages.teacher.layout')

@section('content')
<div class="bg-[#FFFFFF] dark:bg-[#1E1E2D] dark:border-[#5F2DED] shadow-lg border p-8 rounded-lg">
    <div class="flex items-center gap-4 border-b border-[#D4D4D8] dark:border-[#5F2DED] pb-4">
        <button onclick="window.location='{{ route('teacher.reviewer.index') }}'"
                class="flex items-center gap-2">
            <i class="icofont-arrow-left text-primaryColor text-4xl"></i>
        </button>
        <div>
            <h2 class="text-2xl font-semibold text-[#373737] dark:text-white">Create H5P Reviewer</h2>
            <p class="text-sm text-gray-500 dark:text-gray-300">
                Create an H5P activity in WordPress, then paste the embed link here and save it as a reviewer.
            </p>
        </div>
    </div>

    @php
        $tags = $tags ?? collect();
        $tagCategories = $tagCategories ?? collect();
        $tagValues = old('tag_ids', []);
        if (!is_array($tagValues)) {
            $tagValues = array_filter(explode(',', (string) $tagValues));
        }
        $tagValues = array_values(array_unique(array_map('intval', $tagValues)));
        $selectedTags = $tags->whereIn('id', $tagValues);
        $defaultTagRelevance = strtolower((string) config('reviewers.default_tag_relevance', 'medium'));
        $tagRelevanceValues = old('tag_relevance', []);
        $tagCoverageValues = old('tag_coverage', []);
        $tagRelevanceValues = is_array($tagRelevanceValues) ? $tagRelevanceValues : [];
        $tagCoverageValues = is_array($tagCoverageValues) ? $tagCoverageValues : [];

        $selectedTagSettings = $selectedTags->mapWithKeys(function ($tag) use ($tagRelevanceValues, $tagCoverageValues, $defaultTagRelevance) {
            $tagId = (int) $tag->id;
            $tagKey = (string) $tag->id;

            $relevance = $tagRelevanceValues[$tagId]
                ?? $tagRelevanceValues[$tagKey]
                ?? $defaultTagRelevance;

            $coverage = $tagCoverageValues[$tagId]
                ?? $tagCoverageValues[$tagKey]
                ?? '';

            return [
                $tagKey => [
                    'relevance' => strtolower((string) $relevance),
                    'coverage' => $coverage === null ? '' : $coverage,
                ],
            ];
        });
    @endphp

    <div class="mt-6 space-y-6">

        {{-- H5P CREATOR BUTTON --}}
        <div class="p-4 border rounded-lg dark:border-[#5F2DED] bg-gray-50 dark:bg-[#14141f] flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-[#373737] dark:text-white">H5P Activity Creator</p>
                <p class="text-xs text-gray-500 dark:text-gray-300 mt-1">
                    Opens the WordPress H5P editor in a new tab (recommended).
                </p>
            </div>

            <a href="http://localhost/h5p/wp-admin/admin.php?page=h5p_new"
               target="_blank"
               class="text-sm px-4 py-2 rounded-lg bg-[#5F2DED] text-white hover:opacity-90 transition">
                Open Creator
            </a>
        </div>

        {{-- SAVE FORM --}}
        <form action="{{ route('teacher.reviewer.h5p.save') }}" method="POST" class="space-y-6">
            @csrf

            <div class="p-4 border rounded-lg dark:border-[#5F2DED] bg-gray-50 dark:bg-[#14141f]">
                <h3 class="text-lg font-semibold text-[#373737] dark:text-white">Save as Reviewer</h3>
                <p class="text-sm text-gray-500 dark:text-gray-300 mt-1">
                    Paste ONLY the iframe <b>src</b> link (not the full iframe code).
                </p>

                <div class="mt-4 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-[#373737] dark:text-white mb-1">
                            Reviewer Title
                        </label>
                        <input type="text"
                               name="title"
                               value="{{ old('title') }}"
                               class="w-full p-3 border rounded-lg dark:bg-[#1E1E2D] dark:text-white dark:border-[#5F2DED]"
                               required>
                        @error('title')
                            <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-[#373737] dark:text-white mb-1">
                            H5P Embed URL (iframe src)
                        </label>
                        <input type="text"
                               name="embed_code"
                               value="{{ old('embed_code') }}"
                               placeholder="http://localhost/h5p/wp-admin/admin-ajax.php?action=h5p_embed&id=1"
                               class="w-full p-3 border rounded-lg dark:bg-[#1E1E2D] dark:text-white dark:border-[#5F2DED]"
                               required>
                        @error('embed_code')
                            <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="p-4 border rounded-lg dark:border-[#5F2DED] bg-gray-50 dark:bg-[#14141f]">
                <div id="h5pTagsHeader"
                    class="w-full mb-2 flex flex-row justify-between hover:bg-gray-100 dark:hover:bg-gray-800 text-[#5E5F60] dark:text-white rounded cursor-pointer py-2 px-1 transition-all">
                    <h4 class="text-lg font-bold">Tags</h4>
                    <i id="h5pTagsArrow" class="icofont-rounded-down text-xl transition-all duration-300"></i>
                </div>

                <div id="h5pTagsContent" class="flex flex-col overflow-hidden transition-all duration-300 border-b pb-6">
                <div id="h5pTagInputs">
                    @foreach($selectedTags as $tag)
                        @php
                            $tagSetting = $selectedTagSettings[(string) $tag->id] ?? null;
                        @endphp
                        <input type="hidden" name="tag_ids[]" value="{{ $tag->id }}">
                        <input type="hidden" name="tag_relevance[{{ $tag->id }}]" value="{{ $tagSetting['relevance'] ?? $defaultTagRelevance }}">
                        <input type="hidden" name="tag_coverage[{{ $tag->id }}]" value="{{ $tagSetting['coverage'] ?? '' }}">
                    @endforeach
                </div>
                    <div class="flex flex-wrap items-center gap-2 pt-2">
                        <div id="h5pTagPills" class="flex flex-wrap items-center gap-2">
                            @foreach($selectedTags as $tag)
                                <button type="button"
                                    class="h5p-tag-pill inline-flex items-center gap-2 px-3 py-1 rounded-full border border-[#ABB7C2] text-[#5E5F60] dark:text-white hover:bg-gray-100 dark:hover:bg-gray-800 transition"
                                    data-tag-id="{{ $tag->id }}">
                                    <span class="text-sm">{{ $tag->name }}</span>
                                    <span class="h5p-tag-remove text-xs text-gray-400 hover:text-red-500">x</span>
                                </button>
                            @endforeach
                        </div>
                    </div>
                    <div class="mt-2 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <p id="h5pNoTagsMessage" class="text-sm text-gray-500 dark:text-gray-300 {{ $selectedTags->isEmpty() ? '' : 'hidden' }}">
                                No tags assigned yet.
                            </p>
                            <button type="button" id="h5pOpenTagsModal"
                                class="inline-flex items-center gap-2 px-3 py-1 rounded-full border border-dashed border-[#5F2DED] text-[#5F2DED] hover:bg-[#5F2DED]/10 transition">
                                <i class="icofont-ui-add text-sm"></i>
                                <span class="text-sm font-medium">Add Tags</span>
                            </button>
                        </div>
                        <button type="button" id="h5pClearTagsBtn"
                            data-loading-text="Clearing..."
                            data-loading-text-class="text-xs"
                            class="inline-flex items-center gap-2 px-3 py-1 rounded-full border border-red-300 text-red-500 hover:bg-red-50 transition">
                            <i class="icofont-close text-sm"></i>
                            <span class="text-sm font-medium">Clear</span>
                        </button>
                    </div>
                    @error('tag_ids')
                        <p class="text-xs text-red-500 mt-2">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <button type="submit"
                    class="px-6 py-2 bg-[#5F2DED] text-white rounded-lg hover:opacity-90 transition">
                Save Reviewer
            </button>
        </form>

    </div>

    <div id="h5pTagsModal"
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
                            id="h5pTagSearchInput"
                            placeholder="Search tags..."
                            class="w-full pl-9 pr-3 py-1.5 text-sm rounded-full border border-[#ABB7C2] text-[#5E5F60] dark:text-white bg-transparent focus:outline-none focus:ring-2 focus:ring-[#5F2DED]"
                        />
                    </div>
                    <button type="button" id="h5pCloseTagsModal"
                        class="text-gray-500 hover:text-gray-700 dark:text-gray-300">
                        <i class="icofont-close text-xl"></i>
                    </button>
                </div>
            </div>

            <div class="mt-4 flex items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                    <span class="text-sm text-gray-500 dark:text-gray-300">Category:</span>
                    <button type="button" id="h5pOpenCategoryPicker"
                        class="inline-flex items-center gap-2 px-3 py-1.5 rounded-md border border-[#ABB7C2] bg-gray-50 dark:bg-[#2A2A3C] text-sm font-medium text-[#5E5F60] dark:text-white hover:border-[#5F2DED] hover:bg-[#F6F2FF] dark:hover:bg-[#34344A] transition-colors">
                        <span id="h5pCurrentCategoryLabel">All categories</span>
                        <i class="icofont-rounded-down text-sm"></i>
                    </button>
                    <div id="h5pMetricModeToggle"
                        class="relative inline-grid grid-cols-2 h-9 w-56 rounded-full border border-[#ABB7C2] bg-gray-50 dark:bg-[#2A2A3C] overflow-hidden">
                    <span id="h5pMetricModeSlider"
                        class="pointer-events-none absolute inset-y-0 left-0 w-1/2 bg-[#5F2DED] rounded-full transition-transform duration-200 opacity-0"></span>
                    <button type="button" id="h5pModeRelevancyBtn"
                        class="relative z-10 text-xs font-semibold transition-colors">
                        Relevance
                    </button>
                    <button type="button" id="h5pModeCoverageBtn"
                        class="relative z-10 text-xs font-semibold transition-colors">
                        Coverage
                    </button>
                </div>
            </div>
            <button type="button" id="h5pBackToTags"
                class="hidden inline-flex h-8 w-8 items-center justify-center rounded-full border border-[#ABB7C2] text-[#5F2DED] hover:bg-[#F6F2FF] dark:border-[#5F2DED] dark:hover:bg-[#2A2A3C]"
                title="Back to tags"
                aria-label="Back to tags">
                <i class="icofont-simple-left text-sm"></i>
            </button>
        </div>

            <div id="h5pTagListContainer">
                <div class="mt-4 max-h-72 overflow-y-auto border border-gray-200 dark:border-gray-700 rounded-lg divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse ($tags as $tag)
                        @php
                            $tagSetting = $selectedTagSettings[(string) $tag->id] ?? null;
                        @endphp
                        <label
                            class="h5p-tag-option flex items-center gap-3 px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer"
                            data-tag-id="{{ $tag->id }}"
                            data-tag-name="{{ $tag->name }}"
                            data-tag-category="{{ $tag->category }}"
                            data-tag-relevance="{{ $tagSetting['relevance'] ?? $defaultTagRelevance }}"
                            data-tag-coverage="{{ $tagSetting['coverage'] ?? '' }}"
                        >
                            <input type="checkbox"
                                class="h5p-tag-checkbox h-4 w-4 rounded border-gray-300"
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
                <p id="h5pTagListEmpty" class="mt-3 text-sm text-gray-500 dark:text-gray-300 hidden">
                    No tags match your filters.
                </p>
                <div id="h5pTagPagination" class="mt-4 flex items-center justify-between">
                    <button
                        type="button"
                        id="h5pTagPrevPage"
                        class="px-3 py-1.5 border border-[#ABB7C2] rounded-lg text-sm text-[#5E5F60] dark:text-white hover:bg-gray-100 dark:hover:bg-gray-800 disabled:opacity-50 disabled:cursor-not-allowed">
                        Prev
                    </button>
                    <p id="h5pTagPageInfo" class="text-sm text-gray-500 dark:text-gray-300">Page 1 of 1</p>
                    <button
                        type="button"
                        id="h5pTagNextPage"
                        class="px-3 py-1.5 border border-[#ABB7C2] rounded-lg text-sm text-[#5E5F60] dark:text-white hover:bg-gray-100 dark:hover:bg-gray-800 disabled:opacity-50 disabled:cursor-not-allowed">
                        Next
                    </button>
                </div>
            </div>

            <div id="h5pTagCategoryPicker" class="hidden mt-4 border border-gray-200 dark:border-gray-700 rounded-md divide-y divide-gray-100 dark:divide-gray-800">
                <button type="button"
                    class="h5p-tag-category-option w-full text-left px-4 py-2.5 border-l-4 border-transparent hover:bg-gray-50 dark:hover:bg-gray-800 text-sm font-medium text-[#5E5F60] dark:text-white transition-colors"
                    data-tag-category="">
                    All categories
                </button>
                @forelse ($tagCategories as $category)
                    <button type="button"
                        class="h5p-tag-category-option w-full text-left px-4 py-2.5 border-l-4 border-transparent hover:bg-gray-50 dark:hover:bg-gray-800 text-sm font-medium text-[#5E5F60] dark:text-white transition-colors"
                        data-tag-category="{{ $category }}">
                        {{ $category }}
                    </button>
                @empty
                    <div class="px-4 py-6 text-sm text-gray-500 dark:text-gray-300">
                        No categories available.
                    </div>
                @endforelse
            </div>
            <p id="h5pCategoryListEmpty" class="mt-3 text-sm text-gray-500 dark:text-gray-300 hidden">
                Category not found.
            </p>
            <div id="h5pTagCategoryPagination" class="hidden mt-4 flex items-center justify-between">
                <button
                    type="button"
                    id="h5pTagCategoryPrevPage"
                    class="px-3 py-1.5 border border-[#ABB7C2] rounded-lg text-sm text-[#5E5F60] dark:text-white hover:bg-gray-100 dark:hover:bg-gray-800 disabled:opacity-50 disabled:cursor-not-allowed">
                    Prev
                </button>
                <p id="h5pTagCategoryPageInfo" class="text-sm text-gray-500 dark:text-gray-300">Page 1 of 1</p>
                <button
                    type="button"
                    id="h5pTagCategoryNextPage"
                    class="px-3 py-1.5 border border-[#ABB7C2] rounded-lg text-sm text-[#5E5F60] dark:text-white hover:bg-gray-100 dark:hover:bg-gray-800 disabled:opacity-50 disabled:cursor-not-allowed">
                    Next
                </button>
            </div>

            <div id="h5pTagRelevancyContainer" class="hidden mt-4">
                <div class="rounded-lg border border-[#E2E8F0] dark:border-[#334155] p-3 bg-[#F8FAFC] dark:bg-[#0B1220]">
                    <div id="h5pTagMetricsEditor" class="space-y-2"></div>
                    <div id="h5pRelevancyPagination" class="hidden mt-3 flex items-center justify-between">
                        <button
                            type="button"
                            id="h5pRelevancyPrevPage"
                            class="px-3 py-1.5 border border-[#ABB7C2] rounded-lg text-sm text-[#5E5F60] dark:text-white hover:bg-gray-100 dark:hover:bg-gray-800 disabled:opacity-50 disabled:cursor-not-allowed">
                            Prev
                        </button>
                        <p id="h5pRelevancyPageInfo" class="text-sm text-gray-500 dark:text-gray-300">Page 1 of 1</p>
                        <button
                            type="button"
                            id="h5pRelevancyNextPage"
                            class="px-3 py-1.5 border border-[#ABB7C2] rounded-lg text-sm text-[#5E5F60] dark:text-white hover:bg-gray-100 dark:hover:bg-gray-800 disabled:opacity-50 disabled:cursor-not-allowed">
                            Next
                        </button>
                    </div>
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <button
                    type="button"
                    id="h5pConfirmTagsModal"
                    data-loading-text="Saving..."
                    class="px-4 py-2 bg-primaryColor text-white rounded hover:opacity-90">
                    Confirm
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

<x-lmsmodal />

@section('scripts')
<style>
.h5p-pill-tone-very-high {
        border-color: #16a34a;
        background-color: #dcfce7;
        color: #14532d;
    }

    .h5p-pill-tone-very-high:hover {
        background-color: #bbf7d0;
    }

    .h5p-pill-tone-high {
        border-color: #14b8a6;
        background-color: #ccfbf1;
        color: #134e4a;
    }

    .h5p-pill-tone-high:hover {
        background-color: #99f6e4;
    }

    .h5p-pill-tone-medium {
        border-color: #84cc16;
        background-color: #f7fee7;
        color: #4d7c0f;
    }

    .h5p-pill-tone-medium:hover {
        background-color: #ecfccb;
    }

    .h5p-pill-tone-low {
        border-color: #0ea5e9;
        background-color: #f0f9ff;
        color: #0c4a6e;
    }

    .h5p-pill-tone-low:hover {
        background-color: #bae6fd;
    }

    .h5p-pill-tone-very-low {
        border-color: #1d4ed8;
        background-color: #dbeafe;
        color: #172554;
    }

    .h5p-pill-tone-very-low:hover {
        background-color: #93c5fd;
    }

    .dark .h5p-pill-tone-very-high {
        border-color: #4ade80;
        background-color: rgba(22, 163, 74, 0.35);
        color: #dcfce7;
    }

    .dark .h5p-pill-tone-very-high:hover {
        background-color: rgba(22, 163, 74, 0.5);
    }

    .dark .h5p-pill-tone-high {
        border-color: #2dd4bf;
        background-color: rgba(15, 118, 110, 0.4);
        color: #ccfbf1;
    }

    .dark .h5p-pill-tone-high:hover {
        background-color: rgba(20, 184, 166, 0.5);
    }

    .dark .h5p-pill-tone-medium {
        border-color: #a3e635;
        background-color: rgba(132, 204, 22, 0.12);
        color: #d9f99d;
    }

    .dark .h5p-pill-tone-medium:hover {
        background-color: rgba(132, 204, 22, 0.2);
    }

    .dark .h5p-pill-tone-low {
        border-color: #38bdf8;
        background-color: rgba(3, 105, 161, 0.38);
        color: #bae6fd;
    }

    .dark .h5p-pill-tone-low:hover {
        background-color: rgba(14, 165, 233, 0.48);
    }

    .dark .h5p-pill-tone-very-low {
        border-color: #3b82f6;
        background-color: rgba(29, 78, 216, 0.58);
        color: #dbeafe;
    }

    .dark .h5p-pill-tone-very-low:hover {
        background-color: rgba(29, 78, 216, 0.7);
    }

    .h5p-gradient-slider {
        -webkit-appearance: none;
        appearance: none;
        width: 100%;
        height: 10px;
        border-radius: 9999px;
        background: linear-gradient(90deg, #3b82f6 0%, #22c55e 100%);
        outline: none;
    }

    .h5p-gradient-slider::-webkit-slider-runnable-track {
        height: 10px;
        border-radius: 9999px;
        background: linear-gradient(90deg, #3b82f6 0%, #22c55e 100%);
    }

    .h5p-gradient-slider::-webkit-slider-thumb {
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

    .h5p-gradient-slider::-moz-range-track {
        height: 10px;
        border-radius: 9999px;
        background: linear-gradient(90deg, #3b82f6 0%, #22c55e 100%);
    }

    .h5p-gradient-slider::-moz-range-thumb {
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
        const tagsHeader = document.getElementById('h5pTagsHeader');
        const tagsContent = document.getElementById('h5pTagsContent');
        const tagsArrow = document.getElementById('h5pTagsArrow');
        let tagsOpen = true;

        const setSectionState = (content, arrow, isOpen) => {
            if (!content || !arrow) return;
            content.style.maxHeight = isOpen ? content.scrollHeight + 'px' : '0px';
            arrow.classList.toggle('rotate-180', !isOpen);
        };

        const resizeTagsSection = () => {
            if (!tagsContent || !tagsOpen) return;
            tagsContent.style.maxHeight = tagsContent.scrollHeight + 'px';
        };

        const setTagsState = () => {
            if (!tagsContent || !tagsArrow) return;
            setSectionState(tagsContent, tagsArrow, tagsOpen);
            tagsContent.style.paddingBottom = tagsOpen ? '' : '0px';
            tagsContent.style.borderBottomWidth = tagsOpen ? '' : '0px';
        };

        if (tagsHeader && tagsContent && tagsArrow) {
            setTagsState();
            tagsHeader.addEventListener('click', () => {
                tagsOpen = !tagsOpen;
                setTagsState();
            });
        }

        const escapeHtml = (value) => {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        };

        const tagInputsContainer = document.getElementById('h5pTagInputs');
                const tagPills = document.getElementById('h5pTagPills');
                const noTagMessage = document.getElementById('h5pNoTagsMessage');
                const openTagsModalBtn = document.getElementById('h5pOpenTagsModal');
                const clearTagsBtn = document.getElementById('h5pClearTagsBtn');
                const tagsModal = document.getElementById('h5pTagsModal');
                const closeTagsModalBtn = document.getElementById('h5pCloseTagsModal');
                const confirmTagsModalBtn = document.getElementById('h5pConfirmTagsModal');
                const tagSearchInput = document.getElementById('h5pTagSearchInput');
                const tagListContainer = document.getElementById('h5pTagListContainer');
                const tagListEmpty = document.getElementById('h5pTagListEmpty');
                const tagPagination = document.getElementById('h5pTagPagination');
                const tagPrevPageBtn = document.getElementById('h5pTagPrevPage');
                const tagNextPageBtn = document.getElementById('h5pTagNextPage');
                const tagPageInfo = document.getElementById('h5pTagPageInfo');
                const openCategoryPickerBtn = document.getElementById('h5pOpenCategoryPicker');
                const currentCategoryLabel = document.getElementById('h5pCurrentCategoryLabel');
                const backToTagsBtn = document.getElementById('h5pBackToTags');
                const tagCategoryPicker = document.getElementById('h5pTagCategoryPicker');
                const categoryPagination = document.getElementById('h5pTagCategoryPagination');
                const categoryPrevPageBtn = document.getElementById('h5pTagCategoryPrevPage');
                const categoryNextPageBtn = document.getElementById('h5pTagCategoryNextPage');
                const categoryPageInfo = document.getElementById('h5pTagCategoryPageInfo');
                const categoryListEmpty = document.getElementById('h5pCategoryListEmpty');
                const metricModeRelevancyBtn = document.getElementById('h5pModeRelevancyBtn');
                const metricModeCoverageBtn = document.getElementById('h5pModeCoverageBtn');
                const metricModeSlider = document.getElementById('h5pMetricModeSlider');
                const tagRelevancyContainer = document.getElementById('h5pTagRelevancyContainer');
                const tagMetricsEditor = document.getElementById('h5pTagMetricsEditor');
                const relevancyPagination = document.getElementById('h5pRelevancyPagination');
                const relevancyPrevPageBtn = document.getElementById('h5pRelevancyPrevPage');
                const relevancyNextPageBtn = document.getElementById('h5pRelevancyNextPage');
                const relevancyPageInfo = document.getElementById('h5pRelevancyPageInfo');
                const derivedTagNote = document.getElementById('h5pTagDerivedNote');
        
                const tagOptions = Array.from(document.querySelectorAll('.h5p-tag-option'));
                const tagCheckboxes = Array.from(document.querySelectorAll('.h5p-tag-checkbox'));
                const categoryOptions = Array.from(document.querySelectorAll('.h5p-tag-category-option'));
        
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
                    very_high: 'h5p-pill-tone-very-high',
                    high: 'h5p-pill-tone-high',
                    medium: 'h5p-pill-tone-medium',
                    low: 'h5p-pill-tone-low',
                    very_low: 'h5p-pill-tone-very-low',
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
                        pill.className = `h5p-tag-pill inline-flex items-center gap-1.5 px-3 py-1 rounded-full border transition ${toneClass}`;
                        pill.dataset.tagId = String(tag.id);
                        pill.title = 'Edit relevance';
                        pill.innerHTML = `
                            <span class="text-sm">${escapeHtml(tag.name)}</span>
                            <span class="ml-1 text-[10px] leading-none opacity-80">${tag.coverage}%</span>
                            <span class="h5p-tag-remove text-xs leading-none opacity-70 hover:opacity-100 hover:text-red-500">x</span>
                        `;
                        tagPills.appendChild(pill);
                    });
                    updateDerivedTagNote();
                    resizeTagsSection();
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
                        if (clearTagsBtn && triggerBtn !== clearTagsBtn) {
                            clearTagsBtn.disabled = false;
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
                                                class="h5p-gradient-slider w-full">
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
                                                class="h5p-gradient-slider w-full">
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
                    const tagError = document.getElementById('h5pTagError');
                    const count = document.querySelectorAll(
                        '#h5pTagInputs input[name="tag_ids[]"]'
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
                    if (clearTagsBtn && triggerBtn !== clearTagsBtn) {
                        clearTagsBtn.disabled = true;
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
                    const pill = target.closest('.h5p-tag-pill');
                    if (!pill) return;
                    const tagId = String(pill.dataset.tagId || '');
                    if (!tagId) return;
        
                    const removeBtn = target.closest('.h5p-tag-remove');
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
        
                clearTagsBtn?.addEventListener('click', () => {
                    runTagAction(clearTagsBtn, 'Clearing...', () => {
                        clearTagSelection();
                    });
                });

        setMetricModeSwitchState();
        window.addEventListener('resize', resizeTagsSection);
    });
</script>
@endsection

