@extends('pages.teacher.layout')

@section('content')
<div class="bg-[#FFFFFF] dark:bg-[#1E1E2D] dark:border-[#5F2DED] shadow-lg border p-8 rounded-lg"> 
    <div class="pb-2 flex flex-row gap-4 items-center"> 
        <button onclick="window.location='{{ route('teacher.classwork.view', [$section->id, $subject->id]) }}'"
                class="flex items-center gap-2">
            <i class="icofont-arrow-left text-primaryColor text-4xl mb-2"></i>
        </button>

        <x-evaluation-nav 
            active="Settings" 
            :section-id="$section->id" 
            :subject-id="$subject->id" 
            :evaluation-id="$evaluation->id"
        />
        <div class="ml-auto mt-2 flex gap-2">
            <button 
                id="openDeleteModal"
                type="button"
                class="px-5 py-2 bg-red-600 text-white text-sm rounded-lg">
                Delete evaluation
            </button>
        </div>
    </div>

    {{-- UPDATE FORM --}}
    <form id="evaluationSettingsForm"
          method="POST"
          action="{{ route('teacher.evaluation.update', [
              'section_id' => $section->id,
              'subject_id' => $subject->id,
              'evaluation_id' => $evaluation->id
          ]) }}">
        @csrf
        @method('PUT')
        <div id="evaluationTagInputs">
            @foreach ($assignedTagIds as $tagId)
                <input type="hidden" name="tag_ids[]" value="{{ $tagId }}">
            @endforeach
        </div>

        <div class="border-b border-[#D4D4D8] dark:border-[#5F2DED] pb-4">
            {{-- Title --}}
            <input 
                type="text" 
                id="evaluation_title"
                name="title"
                value="{{ $evaluation->title }}"
                class="w-full border-none
                       text-[#373737] font-bold text-3xl pl-[-5%] dark:text-white bg-transparent
                       focus:outline-none focus:ring-transparent "
            />

            <div class="border-t  dark:border-[#5F2DED] pt-2 mb-2"> 
                <p class="text-[#373737] dark:text-white font-semibold text-lg">Evaluation Settings</p>
            </div>
            {{-- Total Score --}}
            <div class="flex flex-row gap-2 items-center justify-between  p-4  border rounded-lg dark:border-[#5F2DED]"> 
                
                
                <div>
                    <p class="font-semibold text-[#373737] dark:text-white">Total Evaluation Score</p>
                    <p class="text-sm text-gray-500 dark:text-gray-300">
                        Set the maximum score for this evaluation.
                    </p>
                </div>

                <input type="number"
                       name="total_score"
                       value="{{ $evaluation->total_score ?? 0 }}"
                       min="0"
                       class="ml-2 w-28 p-2 text-center border rounded bg-white dark:bg-[#2A2A3C] dark:text-white">
            </div>

            {{-- Visible to Students --}}
            <div class="flex items-center justify-between mt-4 p-4 border rounded-lg dark:border-[#5F2DED]">
                <div>
                    <p class="font-semibold text-[#373737] dark:text-white">Visible to students</p>
                    <p class="text-sm text-gray-500 dark:text-gray-300">
                        If enabled, students can view this evaluation (only their score).
                    </p>
                </div>

                {{-- important: submit 0 when unchecked --}}
                <input type="hidden" name="visible" value="0">

                <label class="inline-flex items-center cursor-pointer">
                    <input type="checkbox"
                           name="visible"
                           value="1"
                           class="sr-only peer"
                           {{ $evaluation->visible ? 'checked' : '' }}>
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700
                                peer-checked:bg-[#5F2DED] relative
                                after:content-[''] after:absolute after:top-[2px] after:left-[2px]
                                after:bg-white after:border after:rounded-full after:h-5 after:w-5 after:transition-all
                                peer-checked:after:translate-x-full">
                    </div>
                </label>
            </div>
        </div>

        {{-- Tags --}}
        <div id="evaluationTagsHeader"
            class="w-full mb-2 mt-4 flex flex-row justify-between hover:bg-gray-100 dark:hover:bg-gray-800 
                text-[#5E5F60] dark:text-white rounded cursor-pointer py-2 px-1 transition-all">
            <h1 class="text-lg font-bold">Tags</h1>
            <i id="evaluationTagsArrow" class="icofont-rounded-down text-xl transition-all duration-300"></i>
        </div>

        <div id="evaluationTagsContent" class="flex flex-col overflow-hidden transition-all duration-300 border-b pb-4">
            <div class="flex flex-wrap items-center gap-2 pt-2">
                <div id="evaluationTagPills" class="flex flex-wrap items-center gap-2">
                    @foreach ($assignedTags as $tag)
                        <button type="button"
                            class="tag-pill inline-flex items-center gap-2 px-3 py-1 rounded-full border border-[#ABB7C2] text-[#5E5F60] dark:text-white hover:bg-gray-100 dark:hover:bg-gray-800 transition"
                            data-tag-pill="true"
                            data-tag-id="{{ $tag->id }}">
                            <span class="text-sm">{{ $tag->name }}</span>
                            <span class="tag-pill-remove text-xs text-gray-400 hover:text-red-500">x</span>
                        </button>
                    @endforeach
                </div>
            </div>
            <div class="mt-2 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <p id="evaluationNoTagsMessage" class="text-sm text-gray-500 dark:text-gray-300 {{ $assignedTags->isEmpty() ? '' : 'hidden' }}">
                        No tags assigned yet.
                    </p>
                    <button type="button" id="evaluationOpenTagsModal"
                        class="inline-flex items-center gap-2 px-3 py-1 rounded-full border border-dashed border-[#5F2DED] text-[#5F2DED] hover:bg-[#5F2DED]/10 transition">
                        <i class="icofont-ui-add text-sm"></i>
                        <span class="text-sm font-medium">Add Tags</span>
                    </button>
                </div>
                <button type="button" id="evaluationClearTagsBtn"
                    data-loading-text="Clearing..."
                    data-loading-text-class="text-xs"
                    class="inline-flex items-center gap-2 px-3 py-1 rounded-full border border-red-300 text-red-500 hover:bg-red-50 transition">
                    <i class="icofont-close text-sm"></i>
                    <span class="text-sm font-medium">Clear</span>
                </button>
            </div>
        </div>

        {{-- Description
        <div class="my-6 text-[#373737]">
            <input id="activity_description"
                   name="description"
                   type="hidden"
                   value="{{ $evaluation->description }}">

            <trix-editor 
                input="activity_description"
                placeholder="Write your evaluation description here..."
                class="
                    p-3
                    w-full
                    overflow-y-auto
                    max-h-[600px]
                    bg-white dark:bg-[#1E1E2D] 
                    text-[#373737] dark:text-white
                    border-none
                    rounded-lg
                "
            ></trix-editor>
        </div> --}}

        <div class="w-full flex justify-end"> 
            <button 
                type="button"
                id="openConfirmSaveModal"
                class="px-6 py-3 bg-primaryColor text-white rounded-lg mt-6 hover:opacity-90">
                Save Changes
            </button>
        </div>
    </form>
</div>

{{-- DELETE MODAL --}}
<div id="deleteModal" 
     class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden z-50">

    <div class="bg-white dark:bg-[#1E1E2D] p-6 rounded-lg w-96">
        <h2 class="text-xl font-bold mb-4 text-red-600">Delete evaluation?</h2>
        <p class="text-[#373737] dark:text-white mb-6">
            This action cannot be undone. Students will lose access and all submissions will be deleted.
        </p>

        <div class="flex justify-end gap-3">
            <div> 
                <button 
                id="cancelDelete"
                type="button"
                class="px-4 py-2 bg-gray-300 rounded dark:bg-gray-600 dark:text-white">
                Cancel
            </button>
            </div>
            

            <form id="deleteevaluationForm"
                action="{{ route('teacher.evaluation.delete', [
                    'section_id' => $section->id,
                    'subject_id' => $subject->id,
                    'evaluation_id' => $evaluation->id
                ]) }}"
                method="POST">
                @csrf
                @method('DELETE')

                <button 
                    id="confirmDeleteBtn"
                    type="submit"
                    class="px-4 py-2 bg-red-600 text-white rounded">
                    Delete
                </button>
            </form>
        </div>
    </div>
</div>

{{-- CONFIRM SAVE MODAL --}}
<div id="confirmSaveModal" 
    class="fixed inset-0 bg-black bg-opacity-50 hidden justify-center items-center z-50">

    <div class="bg-white dark:bg-[#1E1E2D] p-6 rounded-lg shadow-lg w-96">
        <h2 class="text-xl font-bold mb-3 text-primaryColor">Save Changes?</h2>

        <p class="text-[#373737] dark:text-white mb-6">
            Are you sure you want to save all changes to this evaluation?
        </p>

        <div class="flex justify-end gap-3">
            <button id="cancelSaveBtn"
                type="button"
                class="px-4 py-2 bg-gray-300 dark:bg-gray-600 dark:text-white rounded">
                Cancel
            </button>

            <button id="confirmSaveBtn"
                type="button"
                data-loading-text="Saving..."
                class="px-4 py-2 bg-primaryColor text-white rounded">
                Save
            </button>
        </div>
    </div>
</div>



<x-lmsmodal/>
@endsection

<div id="evaluationTagsModal"
     class="fixed top-0 left-0 w-screen h-screen flex items-center justify-center
            bg-black bg-opacity-50 hidden z-[9999]">

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
                        id="evaluationTagSearchInput"
                        placeholder="Search tags..."
                        class="w-full pl-9 pr-3 py-1.5 text-sm rounded-full border border-[#ABB7C2] text-[#5E5F60] dark:text-white bg-transparent focus:outline-none focus:ring-2 focus:ring-[#5F2DED]"
                    />
                </div>
                <button type="button" id="evaluationCloseTagsModal"
                    class="text-gray-500 hover:text-gray-700 dark:text-gray-300">
                    <i class="icofont-close text-xl"></i>
                </button>
            </div>
        </div>

        <div class="mt-4 flex items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <span class="text-sm text-gray-500 dark:text-gray-300">Category:</span>
                <button type="button" id="evaluationOpenCategoryPicker"
                    class="inline-flex items-center gap-2 px-3 py-1.5 rounded-md border border-[#ABB7C2] bg-gray-50 dark:bg-[#2A2A3C] text-sm font-medium text-[#5E5F60] dark:text-white hover:border-[#5F2DED] hover:bg-[#F6F2FF] dark:hover:bg-[#34344A] transition-colors">
                    <span id="evaluationCurrentCategoryLabel">All categories</span>
                    <i class="icofont-rounded-down text-sm"></i>
                </button>
                <div id="evaluationMetricModeToggle"
                    class="relative inline-grid grid-cols-2 h-9 w-56 rounded-full border border-[#ABB7C2] bg-gray-50 dark:bg-[#2A2A3C] overflow-hidden">
                    <span id="evaluationMetricModeSlider"
                        class="pointer-events-none absolute inset-y-0 left-0 w-1/2 bg-[#5F2DED] rounded-full transition-transform duration-200 opacity-0"></span>
                    <button type="button" id="evaluationModeRelevancyBtn"
                        class="relative z-10 text-xs font-semibold transition-colors">
                        Relevance
                    </button>
                    <button type="button" id="evaluationModeCoverageBtn"
                        class="relative z-10 text-xs font-semibold transition-colors">
                        Coverage
                    </button>
                </div>
            </div>
            <button type="button" id="evaluationBackToTags"
                class="hidden inline-flex h-8 w-8 items-center justify-center rounded-full border border-[#ABB7C2] text-[#5F2DED] hover:bg-[#F6F2FF] dark:border-[#5F2DED] dark:hover:bg-[#2A2A3C]"
                title="Back to tags"
                aria-label="Back to tags">
                <i class="icofont-simple-left text-sm"></i>
            </button>
        </div>

        @php
            $assignedTagSettings = collect($assignedTags ?? [])->mapWithKeys(function ($tag) {
                return [
                    (string) $tag->id => [
                        'relevance' => strtolower((string) ($tag->pivot->relevance_level ?? config('reviewers.default_tag_relevance', 'medium'))),
                        'coverage' => $tag->pivot->coverage_ratio !== null
                            ? round(((float) $tag->pivot->coverage_ratio) * 100, 2)
                            : null,
                    ],
                ];
            });
        @endphp

        <div id="evaluationTagListContainer">
            <div class="mt-4 max-h-72 overflow-y-auto border border-gray-200 dark:border-gray-700 rounded-lg divide-y divide-gray-100 dark:divide-gray-800">
                @forelse ($tags as $tag)
                    @php
                        $tagSetting = $assignedTagSettings[(string) $tag->id] ?? null;
                    @endphp
                    <label
                        class="evaluation-tag-option flex items-center gap-3 px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer"
                        data-tag-id="{{ $tag->id }}"
                        data-tag-name="{{ $tag->name }}"
                        data-tag-category="{{ $tag->category }}"
                        data-tag-relevance="{{ $tagSetting['relevance'] ?? config('reviewers.default_tag_relevance', 'medium') }}"
                        data-tag-coverage="{{ $tagSetting['coverage'] ?? '' }}"
                    >
                        <input type="checkbox"
                            class="evaluation-tag-checkbox h-4 w-4 rounded border-gray-300"
                            value="{{ $tag->id }}"
                            {{ in_array($tag->id, $assignedTagIds, true) ? 'checked' : '' }}>
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
            <p id="evaluationTagListEmpty" class="mt-3 text-sm text-gray-500 dark:text-gray-300 hidden">
                No tags match your filters.
            </p>

            <div id="evaluationTagPagination" class="mt-4 flex items-center justify-between">
                <button
                    type="button"
                    id="evaluationTagPrevPage"
                    class="px-3 py-1.5 border border-[#ABB7C2] rounded-lg text-sm text-[#5E5F60] dark:text-white hover:bg-gray-100 dark:hover:bg-gray-800 disabled:opacity-50 disabled:cursor-not-allowed">
                    Prev
                </button>
                <p id="evaluationTagPageInfo" class="text-sm text-gray-500 dark:text-gray-300">Page 1 of 1</p>
                <button
                    type="button"
                    id="evaluationTagNextPage"
                    class="px-3 py-1.5 border border-[#ABB7C2] rounded-lg text-sm text-[#5E5F60] dark:text-white hover:bg-gray-100 dark:hover:bg-gray-800 disabled:opacity-50 disabled:cursor-not-allowed">
                    Next
                </button>
            </div>
        </div>

        <div id="evaluationTagCategoryPicker" class="hidden mt-4 border border-gray-200 dark:border-gray-700 rounded-md divide-y divide-gray-100 dark:divide-gray-800">
            <button type="button"
                class="evaluation-tag-category-option w-full text-left px-4 py-2.5 border-l-4 border-transparent hover:bg-gray-50 dark:hover:bg-gray-800 text-sm font-medium text-[#5E5F60] dark:text-white transition-colors"
                data-tag-category="">
                All categories
            </button>
            @forelse ($tagCategories as $category)
                <button type="button"
                    class="evaluation-tag-category-option w-full text-left px-4 py-2.5 border-l-4 border-transparent hover:bg-gray-50 dark:hover:bg-gray-800 text-sm font-medium text-[#5E5F60] dark:text-white transition-colors"
                    data-tag-category="{{ $category }}">
                    {{ $category }}
                </button>
            @empty
                <div class="px-4 py-6 text-sm text-gray-500 dark:text-gray-300">
                    No categories available.
                </div>
            @endforelse
        </div>
        <p id="evaluationCategoryListEmpty" class="mt-3 text-sm text-gray-500 dark:text-gray-300 hidden">
            Category not found.
        </p>
        <div id="evaluationCategoryPagination" class="hidden mt-4 flex items-center justify-between">
            <button
                type="button"
                id="evaluationCategoryPrevPage"
                class="px-3 py-1.5 border border-[#ABB7C2] rounded-lg text-sm text-[#5E5F60] dark:text-white hover:bg-gray-100 dark:hover:bg-gray-800 disabled:opacity-50 disabled:cursor-not-allowed">
                Prev
            </button>
            <p id="evaluationCategoryPageInfo" class="text-sm text-gray-500 dark:text-gray-300">Page 1 of 1</p>
            <button
                type="button"
                id="evaluationCategoryNextPage"
                class="px-3 py-1.5 border border-[#ABB7C2] rounded-lg text-sm text-[#5E5F60] dark:text-white hover:bg-gray-100 dark:hover:bg-gray-800 disabled:opacity-50 disabled:cursor-not-allowed">
                Next
            </button>
        </div>

        <div id="evaluationTagRelevancyContainer" class="hidden mt-4">
            <div class="rounded-lg border border-[#E2E8F0] dark:border-[#334155] p-3 bg-[#F8FAFC] dark:bg-[#0B1220]">
                <div id="evaluationTagMetricsEditor" class="space-y-2"></div>
                <div id="evaluationRelevancyPagination" class="hidden mt-3 flex items-center justify-between">
                    <button
                        type="button"
                        id="evaluationRelevancyPrevPage"
                        class="px-3 py-1.5 border border-[#ABB7C2] rounded-lg text-sm text-[#5E5F60] dark:text-white hover:bg-gray-100 dark:hover:bg-gray-800 disabled:opacity-50 disabled:cursor-not-allowed">
                        Prev
                    </button>
                    <p id="evaluationRelevancyPageInfo" class="text-sm text-gray-500 dark:text-gray-300">Page 1 of 1</p>
                    <button
                        type="button"
                        id="evaluationRelevancyNextPage"
                        class="px-3 py-1.5 border border-[#ABB7C2] rounded-lg text-sm text-[#5E5F60] dark:text-white hover:bg-gray-100 dark:hover:bg-gray-800 disabled:opacity-50 disabled:cursor-not-allowed">
                        Next
                    </button>
                </div>
            </div>
        </div>

        <div class="mt-6 flex justify-end">
            <button
                type="button"
                id="evaluationConfirmTagsModal"
                data-loading-text="Saving..."
                class="px-4 py-2 bg-primaryColor text-white rounded hover:opacity-90">
                Confirm
            </button>
        </div>
    </div>
</div>

<style>
    .tag-pill-tone-very-high {
        border-color: #16a34a;
        background-color: #dcfce7;
        color: #14532d;
    }

    .tag-pill-tone-very-high:hover {
        background-color: #bbf7d0;
    }

    .tag-pill-tone-high {
        border-color: #14b8a6;
        background-color: #ccfbf1;
        color: #134e4a;
    }

    .tag-pill-tone-high:hover {
        background-color: #99f6e4;
    }

    .tag-pill-tone-medium {
        border-color: #84cc16;
        background-color: #f7fee7;
        color: #4d7c0f;
    }

    .tag-pill-tone-medium:hover {
        background-color: #ecfccb;
    }

    .tag-pill-tone-low {
        border-color: #0ea5e9;
        background-color: #f0f9ff;
        color: #0c4a6e;
    }

    .tag-pill-tone-low:hover {
        background-color: #bae6fd;
    }

    .tag-pill-tone-very-low {
        border-color: #1d4ed8;
        background-color: #dbeafe;
        color: #172554;
    }

    .tag-pill-tone-very-low:hover {
        background-color: #93c5fd;
    }

    .dark .tag-pill-tone-very-high {
        border-color: #4ade80;
        background-color: rgba(22, 163, 74, 0.35);
        color: #dcfce7;
    }

    .dark .tag-pill-tone-very-high:hover {
        background-color: rgba(22, 163, 74, 0.5);
    }

    .dark .tag-pill-tone-high {
        border-color: #2dd4bf;
        background-color: rgba(15, 118, 110, 0.4);
        color: #ccfbf1;
    }

    .dark .tag-pill-tone-high:hover {
        background-color: rgba(20, 184, 166, 0.5);
    }

    .dark .tag-pill-tone-medium {
        border-color: #a3e635;
        background-color: rgba(132, 204, 22, 0.12);
        color: #d9f99d;
    }

    .dark .tag-pill-tone-medium:hover {
        background-color: rgba(132, 204, 22, 0.2);
    }

    .dark .tag-pill-tone-low {
        border-color: #38bdf8;
        background-color: rgba(3, 105, 161, 0.38);
        color: #bae6fd;
    }

    .dark .tag-pill-tone-low:hover {
        background-color: rgba(14, 165, 233, 0.48);
    }

    .dark .tag-pill-tone-very-low {
        border-color: #3b82f6;
        background-color: rgba(29, 78, 216, 0.58);
        color: #dbeafe;
    }

    .dark .tag-pill-tone-very-low:hover {
        background-color: rgba(29, 78, 216, 0.7);
    }

    .tag-gradient-slider {
        -webkit-appearance: none;
        appearance: none;
        width: 100%;
        height: 10px;
        border-radius: 9999px;
        background: linear-gradient(90deg, #3b82f6 0%, #22c55e 100%);
        outline: none;
    }

    .tag-gradient-slider::-webkit-slider-runnable-track {
        height: 10px;
        border-radius: 9999px;
        background: linear-gradient(90deg, #3b82f6 0%, #22c55e 100%);
    }

    .tag-gradient-slider::-webkit-slider-thumb {
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

    .tag-gradient-slider::-moz-range-track {
        height: 10px;
        border-radius: 9999px;
        background: linear-gradient(90deg, #3b82f6 0%, #22c55e 100%);
    }

    .tag-gradient-slider::-moz-range-thumb {
        height: 16px;
        width: 16px;
        border-radius: 9999px;
        background: #ffffff;
        border: 2px solid #5f2ded;
        cursor: pointer;
    }
</style>
<script>
document.addEventListener("DOMContentLoaded", function () {
    /** Delete Modal */
    const openDeleteModalBtn = document.getElementById('openDeleteModal');
    const deleteModal = document.getElementById('deleteModal');
    const cancelDeleteBtn = document.getElementById('cancelDelete');

    openDeleteModalBtn?.addEventListener('click', () => {
        deleteModal?.classList.remove('hidden');
    });

    cancelDeleteBtn?.addEventListener('click', () => {
        deleteModal?.classList.add('hidden');
    });

    /** Save Modal */
    const openConfirmSaveModalBtn = document.getElementById('openConfirmSaveModal');
    const confirmSaveModal = document.getElementById('confirmSaveModal');
    const cancelSaveBtn = document.getElementById('cancelSaveBtn');
    const confirmSaveBtn = document.getElementById('confirmSaveBtn');
    const form = document.getElementById('evaluationSettingsForm');

    openConfirmSaveModalBtn?.addEventListener('click', () => {
        confirmSaveModal?.classList.remove('hidden');
        confirmSaveModal?.classList.add('flex');
    });

    cancelSaveBtn?.addEventListener('click', () => {
        confirmSaveModal?.classList.add('hidden');
        confirmSaveModal?.classList.remove('flex');
    });

    confirmSaveBtn?.addEventListener('click', () => {
        if (window.AdminModalLoading && typeof window.AdminModalLoading.setLoading === 'function') {
            window.AdminModalLoading.setLoading(confirmSaveBtn, {
                text: confirmSaveBtn?.dataset?.loadingText || 'Saving...',
            });
        } else {
            confirmSaveBtn.disabled = true;
            confirmSaveBtn.classList.add('opacity-75', 'cursor-wait');
        }
        cancelSaveBtn && (cancelSaveBtn.disabled = true);
        form?.submit();
    });    const tagsHeader = document.getElementById('evaluationTagsHeader');
    const tagsContent = document.getElementById('evaluationTagsContent');
    const tagsArrow = document.getElementById('evaluationTagsArrow');
    let tagsOpen = true;

    const setSectionState = (content, arrow, isOpen) => {
        if (!content || !arrow) return;
        content.style.maxHeight = isOpen ? content.scrollHeight + 'px' : '0px';
        content.style.overflow = isOpen ? 'visible' : 'hidden';
        arrow.classList.toggle('rotate-180', !isOpen);
    };

    const resizeTagsSection = () => {
        if (!tagsContent || !tagsOpen) return;
        tagsContent.style.maxHeight = tagsContent.scrollHeight + 'px';
        tagsContent.style.overflow = 'visible';
    };

    if (tagsHeader && tagsContent && tagsArrow) {
        setSectionState(tagsContent, tagsArrow, tagsOpen);

        tagsHeader.addEventListener('click', () => {
            tagsOpen = !tagsOpen;
            setSectionState(tagsContent, tagsArrow, tagsOpen);
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

const tagInputsContainer = document.getElementById('evaluationTagInputs');
        const tagPills = document.getElementById('evaluationTagPills');
        const evaluationNoTagsMessage = document.getElementById('evaluationNoTagsMessage');
        const openTagsModalBtn = document.getElementById('evaluationOpenTagsModal');
        const clearTagsBtn = document.getElementById('evaluationClearTagsBtn');
        const tagsModal = document.getElementById('evaluationTagsModal');
        const closeTagsModalBtn = document.getElementById('evaluationCloseTagsModal');
        const confirmTagsModalBtn = document.getElementById('evaluationConfirmTagsModal');
        const tagSearchInput = document.getElementById('evaluationTagSearchInput');
        const tagListContainer = document.getElementById('evaluationTagListContainer');
        const tagListEmpty = document.getElementById('evaluationTagListEmpty');
        const tagPagination = document.getElementById('evaluationTagPagination');
        const tagPrevPageBtn = document.getElementById('evaluationTagPrevPage');
        const tagNextPageBtn = document.getElementById('evaluationTagNextPage');
        const tagPageInfo = document.getElementById('evaluationTagPageInfo');
        const openCategoryPickerBtn = document.getElementById('evaluationOpenCategoryPicker');
        const currentCategoryLabel = document.getElementById('evaluationCurrentCategoryLabel');
        const backToTagsBtn = document.getElementById('evaluationBackToTags');
        const tagCategoryPicker = document.getElementById('evaluationTagCategoryPicker');
        const categoryPagination = document.getElementById('evaluationCategoryPagination');
        const categoryPrevPageBtn = document.getElementById('evaluationCategoryPrevPage');
        const categoryNextPageBtn = document.getElementById('evaluationCategoryNextPage');
        const categoryPageInfo = document.getElementById('evaluationCategoryPageInfo');
        const categoryListEmpty = document.getElementById('evaluationCategoryListEmpty');
        const metricModeRelevancyBtn = document.getElementById('evaluationModeRelevancyBtn');
        const metricModeCoverageBtn = document.getElementById('evaluationModeCoverageBtn');
        const metricModeSlider = document.getElementById('evaluationMetricModeSlider');
        const tagRelevancyContainer = document.getElementById('evaluationTagRelevancyContainer');
        const tagMetricsEditor = document.getElementById('evaluationTagMetricsEditor');
        const relevancyPagination = document.getElementById('evaluationRelevancyPagination');
        const relevancyPrevPageBtn = document.getElementById('evaluationRelevancyPrevPage');
        const relevancyNextPageBtn = document.getElementById('evaluationRelevancyNextPage');
        const relevancyPageInfo = document.getElementById('evaluationRelevancyPageInfo');
        const derivedTagNote = document.getElementById('evaluationTagDerivedNote');

        const tagOptions = Array.from(document.querySelectorAll('.evaluation-tag-option'));
        const tagCheckboxes = Array.from(document.querySelectorAll('.evaluation-tag-checkbox'));
        const categoryOptions = Array.from(document.querySelectorAll('.evaluation-tag-category-option'));

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
            very_high: 'tag-pill-tone-very-high',
            high: 'tag-pill-tone-high',
            medium: 'tag-pill-tone-medium',
            low: 'tag-pill-tone-low',
            very_low: 'tag-pill-tone-very-low',
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
                evaluationNoTagsMessage?.classList.remove('hidden');
                updateDerivedTagNote();
            resizeTagsSection();
                return;
            }

            evaluationNoTagsMessage?.classList.add('hidden');
            tagEntries.forEach((tag) => {
                const pill = document.createElement('button');
                pill.type = 'button';
                const toneClass = relevancePillToneClasses[tag.relevance] || relevancePillToneClasses.medium;
                pill.className = `tag-pill inline-flex items-center gap-1.5 px-3 py-1 rounded-full border transition ${toneClass}`;
                pill.dataset.tagId = String(tag.id);
                pill.title = 'Edit relevance';
                pill.innerHTML = `
                    <span class="text-sm">${escapeHtml(tag.name)}</span>
                    <span class="ml-1 text-[10px] leading-none opacity-80">${tag.coverage}%</span>
                    <span class="tag-pill-remove text-xs leading-none opacity-70 hover:opacity-100 hover:text-red-500">x</span>
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
                                        class="tag-gradient-slider w-full">
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
                                        class="tag-gradient-slider w-full">
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
                '#evaluationTagInputs input[name="tag_ids[]"]'
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
            const pill = target.closest('.tag-pill');
            if (!pill) return;
            const tagId = String(pill.dataset.tagId || '');
            if (!tagId) return;

            const removeBtn = target.closest('.tag-pill-remove');
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

        
    window.addEventListener('resize', resizeTagsSection);

});
</script>

