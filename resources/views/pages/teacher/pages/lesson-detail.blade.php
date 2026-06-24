@extends('pages.teacher.layout')

@section('content')
@php
    $isCreateMode = (bool) ($isCreate ?? false);
    $lessonModel = $lesson ?? null;
    $allTags = $tags ?? collect();
    $allTags = $allTags instanceof \Illuminate\Support\Collection ? $allTags : collect($allTags);

    $fallbackPeriod = $selectedGradingPeriod ?? ($lessonModel->grading_period ?? '1st');
    $currentPeriod = old('grading_period', $fallbackPeriod);
    $backUrl = route('teacher.lesson', [$section->id, $subject->id]) . '?grading_period=' . urlencode((string) $currentPeriod);

    $titleValue = old('lesson_title', $lessonModel->lesson_title ?? '');
    $descriptionValue = old('description', $lessonModel->description ?? '');
    $isVisibleChecked = (string) old('visible', $lessonModel ? (int) $lessonModel->visible : 1) === '1';

    $seedTagIds = old('tag_ids', $assignedTagIds ?? []);
    $selectedTagIds = collect($seedTagIds)
        ->map(fn($id) => (int) $id)
        ->filter(fn($id) => $id > 0)
        ->unique()
        ->values();

    $selectedTagMap = $allTags
        ->whereIn('id', $selectedTagIds->all())
        ->keyBy(fn($tag) => (int) $tag->id);

    $assignedTagsForView = $selectedTagIds
        ->map(fn($id) => $selectedTagMap->get($id))
        ->filter();

    $defaultTagRelevance = strtolower((string) config('reviewers.default_tag_relevance', 'medium'));
    $oldTagRelevance = old('tag_relevance', []);
    $oldTagCoverage = old('tag_coverage', []);
    $oldTagRelevance = is_array($oldTagRelevance) ? $oldTagRelevance : [];
    $oldTagCoverage = is_array($oldTagCoverage) ? $oldTagCoverage : [];

    $persistedTagSettings = collect();
    if (!$isCreateMode && $lessonModel) {
        $persistedTagSettings = $lessonModel->tags
            ->mapWithKeys(function ($tag) use ($defaultTagRelevance) {
                return [
                    (string) $tag->id => [
                        'relevance' => strtolower((string) ($tag->pivot->relevance_level ?? $defaultTagRelevance)),
                        'coverage' => $tag->pivot->coverage_ratio !== null
                            ? round(((float) $tag->pivot->coverage_ratio) * 100, 2)
                            : null,
                    ],
                ];
            });
    }

    $assignedTagSettings = $selectedTagIds
        ->mapWithKeys(function ($id) use ($oldTagRelevance, $oldTagCoverage, $persistedTagSettings, $defaultTagRelevance) {
            $tagKey = (string) $id;
            $persisted = $persistedTagSettings->get($tagKey, []);

            $relevance = $oldTagRelevance[$id]
                ?? $oldTagRelevance[$tagKey]
                ?? ($persisted['relevance'] ?? $defaultTagRelevance);

            $coverage = $oldTagCoverage[$id]
                ?? $oldTagCoverage[$tagKey]
                ?? ($persisted['coverage'] ?? '');

            return [
                $tagKey => [
                    'relevance' => strtolower((string) $relevance),
                    'coverage' => $coverage === null ? '' : $coverage,
                ],
            ];
        });

    $tagCategories = $allTags
        ->pluck('category')
        ->filter(fn($category) => filled($category))
        ->map(fn($category) => trim((string) $category))
        ->filter()
        ->unique()
        ->sort()
        ->values();

    $saveAction = $isCreateMode
        ? route('teacher.lesson.store', [$section->id, $subject->id])
        : route('teacher.lesson.update', [$section->id, $subject->id, $lessonModel->id]);

    $deleteAction = (!$isCreateMode && $lessonModel)
        ? route('teacher.lesson.delete', ['lesson_id' => $lessonModel->id]) . '?grading_period=' . urlencode((string) $currentPeriod)
        : null;

    $existingAttachments = (!$isCreateMode && $lessonModel)
        ? $lessonModel->getMedia('attachments')
        : collect();
@endphp

<div class="bg-[#FFFFFF] dark:bg-[#1E1E2D] dark:border-[#5F2DED] shadow-lg border p-8 rounded-lg">
    <form id="lessonForm" action="{{ $saveAction }}" method="POST" enctype="multipart/form-data">
        @csrf
        @unless($isCreateMode)
            @method('PUT')
        @endunless

        <div class="border-b border-[#D4D4D8] dark:border-[#5F2DED] pb-4">
            <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
                <div class="flex items-start gap-3 min-w-0 flex-1">
                    <button type="button" onclick="window.location='{{ $backUrl }}'" class="mt-1 shrink-0" aria-label="Return">
                        <i class="icofont-arrow-left text-primaryColor text-4xl"></i>
                    </button>

                    <input
                        type="text"
                        id="lesson_title"
                        name="lesson_title"
                        value="{{ $titleValue }}"
                        placeholder="Lesson title"
                        class="w-full border-none text-[#373737] font-bold text-3xl dark:text-white bg-transparent focus:outline-none focus:ring-transparent p-0"
                        required
                    />
                </div>

                <div class="flex flex-col items-start lg:items-end gap-3 shrink-0">
                    @unless($isCreateMode)
                        <button
                            type="button"
                            id="openDeleteModal"
                            class="px-5 py-2 bg-red-600 text-white text-sm rounded-lg"
                        >
                            Delete Lesson
                        </button>
                    @endunless
                </div>
            </div>

            @unless($isCreateMode)
                <div class="flex flex-row gap-2 items-center text-sm mt-2">
                    <i class="icofont-clock-time text-[#008236] text-lg"></i>
                    <p class="text-[#373737] dark:text-white">
                        <span class="font-bold">Opened:</span>
                        {{ \Carbon\Carbon::parse($lessonModel->created_at)->format('h:i A F d, Y') }}
                    </p>
                </div>
            @endunless
        </div>

        @if($errors->any())
            <div class="mt-4 rounded-lg border border-red-200 bg-red-50 text-red-700 px-4 py-3 text-sm">
                <ul class="list-disc pl-5 space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="my-6 text-[#373737]">
            <input
                id="lesson_description"
                name="description"
                type="hidden"
                value="{{ $descriptionValue }}"
            >

            <trix-editor
                input="lesson_description"
                placeholder="Write your activity description here..."
                class="p-3 w-full overflow-y-auto max-h-[600px] bg-white dark:bg-[#1E1E2D] text-[#373737] dark:text-white border-none rounded-lg"
            ></trix-editor>
        </div>

        <div class="mt-6 pt-4 border-t border-[#D4D4D8] dark:border-[#5F2DED]">
            <label class="block mb-2 text-xl font-semibold text-[#5E5F60] dark:text-white">
                Upload Files
            </label>

            <div
                id="dropZone"
                class="w-full border-2 border-dashed border-[#ABB7C2] dark:border-[#5F2DED] rounded-lg p-6 text-center cursor-pointer hover:border-[#5F2DED] dark:hover:border-[#A88BFF] transition"
            >
                <p class="text-[#5E5F60] dark:text-white">
                    Drag & Drop files here or click to upload
                </p>
                <p class="text-xs text-[#ABB7C2] mt-1">
                    You can upload multiple files
                </p>

                <input
                    type="file"
                    id="fileInput"
                    name="attachments[]"
                    multiple
                    class="hidden"
                />
            </div>

            <div id="filePreviewList" class="mt-3 space-y-2"></div>
        </div>

        @if(!$isCreateMode && $existingAttachments->isNotEmpty())
            <div class="mt-4 pt-4 border-t border-[#D4D4D8] dark:border-[#5F2DED]">
                <div class="mb-2 flex items-center justify-between">
                    <h2 class="text-xl font-semibold text-[#5E5F60] dark:text-white">Attached Files</h2>
                    <button
                        type="button"
                        data-download-all-target="#teacherLessonAttachments"
                        class="inline-flex h-8 w-8 items-center justify-center rounded border border-[#D4D4D8] text-primaryColor transition hover:bg-gray-100 dark:border-[#5F2DED] dark:text-white dark:hover:bg-[#2A2A3C]"
                        title="Download all"
                        aria-label="Download all attached files"
                    >
                        <i class="icofont-download text-base"></i>
                    </button>
                </div>

                <ul id="teacherLessonAttachments" class="space-y-2">
                    @foreach($existingAttachments as $media)
                        <li class="flex justify-between items-center gap-3 bg-gray-100 dark:bg-[#2A2A3C] p-2 rounded">
                            <div class="flex items-center gap-2 min-w-0">
                                <a
                                    href="{{ $media->getUrl() }}"
                                    target="_blank"
                                    class="text-[#373737] dark:text-white truncate max-w-xs md:max-w-sm"
                                >
                                    {{ $media->file_name }}
                                </a>
                                <a
                                    href="{{ $media->getUrl() }}"
                                    download
                                    data-download-link="true"
                                    class="shrink-0 text-primaryColor hover:opacity-80"
                                    title="Download {{ $media->file_name }}"
                                    aria-label="Download {{ $media->file_name }}"
                                >
                                    <i class="icofont-download text-sm"></i>
                                </a>
                            </div>

                            <button
                                type="button"
                                class="text-red-600 dark:text-red-400 hover:underline open-remove-modal"
                                data-action="{{ route('teacher.lesson.file.remove', ['lesson_id' => $lessonModel->id, 'media_id' => $media->id]) }}"
                            >
                                Remove
                            </button>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="mt-4">
            <div id="visibilityHeader"
                class="w-full mb-2 flex flex-row justify-between hover:bg-gray-100 dark:hover:bg-gray-800 text-[#5E5F60] dark:text-white rounded cursor-pointer py-2 px-1 transition-all">
                <h1 class="text-lg font-bold">Visibility Options</h1>
                <i id="visibilityArrow" class="icofont-rounded-down text-xl transition-all duration-300"></i>
            </div>

            <div id="visibilityContent"
                class="overflow-hidden transition-all duration-300 border-b"
                data-expanded-padding="16"
                style="max-height: 0px;">
                <div class="flex flex-col gap-3 pb-4">
                    <div class="w-full max-w-sm">
                        <label for="grading_period" class="block mb-1 text-sm font-medium text-[#5E5F60] dark:text-white">
                            Grading Period
                        </label>
                        <select
                            id="grading_period"
                            name="grading_period"
                            class="w-full border border-[#ABB7C2] rounded-lg px-3 py-2 text-[#5E5F60] dark:text-white dark:bg-[#1E1E2D] bg-transparent focus:outline-none focus:ring-2 focus:ring-[#5F2DED]"
                        >
                            @foreach($gradingPeriods as $periodKey => $periodLabel)
                                <option value="{{ $periodKey }}" {{ (string) $currentPeriod === (string) $periodKey ? 'selected' : '' }}>
                                    {{ $periodLabel }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mt-1 flex items-center gap-2">
                        <label class="text-sm font-medium text-[#5E5F60] dark:text-white">
                            Lesson is visible to students
                        </label>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="hidden" name="visible" value="0">
                            <input
                                type="checkbox"
                                id="is_visible"
                                name="visible"
                                value="1"
                                {{ $isVisibleChecked ? 'checked' : '' }}
                                class="sr-only peer"
                            >
                            <div class="w-11 h-6 bg-gray-300 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:bg-[#5F2DED] dark:peer-checked:bg-blue-500 transition-all duration-300"></div>
                            <span class="absolute left-1 top-[3px] w-4 h-4 bg-white rounded-full transition-all peer-checked:translate-x-5 dark:bg-gray-200"></span>
                        </label>
                    </div>

                    <div class="mt-1 flex items-center gap-2 mb-2">
                        <label class="text-sm font-medium text-[#5E5F60] dark:text-white">
                            Lesson Status:
                        </label>
                        <span id="visibilityStatusText" class="text-sm font-semibold"></span>
                    </div>
                </div>
            </div>

            <div id="activityTagsHeader"
                class="w-full mb-2 mt-4 flex flex-row justify-between hover:bg-gray-100 dark:hover:bg-gray-800 text-[#5E5F60] dark:text-white rounded cursor-pointer py-2 px-1 transition-all">
                <h1 class="text-lg font-bold">Tags</h1>
                <i id="activityTagsArrow" class="icofont-rounded-down text-xl transition-all duration-300"></i>
            </div>

            <div id="activityTagsContent"
                class="overflow-hidden transition-all duration-300 border-b"
                data-expanded-padding="48"
                style="max-height: 0px;">
                <div class="pt-2">
                    <div id="activityTagPills" class="flex flex-wrap items-center gap-2">
                        @foreach($assignedTagsForView as $tag)
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

                <div class="mt-2 flex items-center pb-4 justify-between mb-4">
                    <div class="flex items-center gap-2">
                        <p id="activityNoTagsMessage"
                           class="text-sm text-gray-500 dark:text-gray-300 {{ $assignedTagsForView->isEmpty() ? '' : 'hidden' }}">
                            No tags assigned yet.
                        </p>
                        <button type="button" id="activityOpenTagsModal"
                            class="inline-flex items-center gap-2 px-3 py-1 rounded-full border border-dashed border-[#5F2DED] text-[#5F2DED] hover:bg-[#5F2DED]/10 transition">
                            <i class="icofont-ui-add text-sm"></i>
                            <span class="text-sm font-medium">Add Tags</span>
                        </button>
                    </div>
                    <button type="button" id="activityClearTagsBtn"
                        data-loading-text="Clearing..."
                        data-loading-text-class="text-xs"
                        class="inline-flex items-center gap-2 px-3 py-1 rounded-full border border-red-300 text-red-500 hover:bg-red-50 transition">
                        <i class="icofont-close text-sm"></i>
                        <span class="text-sm font-medium">Clear</span>
                    </button>
                </div>

                <div id="activityTagInputs">
                    @foreach($selectedTagIds as $tagId)
                        @php
                            $tagSetting = $assignedTagSettings[(string) $tagId] ?? null;
                        @endphp
                        <input type="hidden" name="tag_ids[]" value="{{ $tagId }}" data-hidden-tag-id="{{ $tagId }}">
                        <input type="hidden" name="tag_relevance[{{ $tagId }}]" value="{{ $tagSetting['relevance'] ?? $defaultTagRelevance }}">
                        <input type="hidden" name="tag_coverage[{{ $tagId }}]" value="{{ $tagSetting['coverage'] ?? '' }}">
                    @endforeach
                </div>
            </div>

            <div class="flex justify-end mt-6">
                <button
                    id="openSaveModal"
                    type="button"
                    class="px-5 py-2 rounded-lg bg-primaryColor text-white hover:opacity-90 transition"
                >
                    Save
                </button>
            </div>
        </div>
    </form>
</div>
@endsection

<x-lmsmodal />

<div id="removeFileModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50">
    <div class="bg-white dark:bg-[#1F1F2E] rounded-lg w-full max-w-md p-6">
        <h3 class="text-lg font-semibold text-[#373737] dark:text-white mb-2">Remove file?</h3>
        <p class="text-sm text-gray-600 dark:text-gray-300 mb-4">
            This action cannot be undone. Are you sure you want to remove this file?
        </p>

        <form id="removeFileForm" method="POST">
            @csrf
            @method('DELETE')

            <div class="flex justify-end gap-3">
                <button
                    type="button"
                    id="cancelRemoveFile"
                    class="px-4 py-2 rounded border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    id="confirmRemoveFile"
                    class="px-4 py-2 rounded bg-red-600 text-white flex items-center gap-2 disabled:opacity-60"
                >
                    <svg id="removeLoadingSpinner" class="w-4 h-4 animate-spin hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                    </svg>
                    <span id="removeBtnText">Remove</span>
                </button>
            </div>
        </form>
    </div>
</div>

<div id="activityTagsModal" class="fixed inset-0 items-center justify-center bg-black bg-opacity-50 hidden z-50">
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
                        id="activityTagSearchInput"
                        placeholder="Search tags..."
                        class="w-full pl-9 pr-3 py-1.5 text-sm rounded-full border border-[#ABB7C2] text-[#5E5F60] dark:text-white bg-transparent focus:outline-none focus:ring-2 focus:ring-[#5F2DED]"
                    />
                </div>
                <button type="button" id="activityCloseTagsModal" class="text-gray-500 hover:text-gray-700 dark:text-gray-300">
                    <i class="icofont-close text-xl"></i>
                </button>
            </div>
        </div>

        <div class="mt-4 flex items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <span class="text-sm text-gray-500 dark:text-gray-300">Category:</span>
                <button type="button" id="activityOpenCategoryPicker"
                    class="inline-flex items-center gap-2 px-3 py-1.5 rounded-md border border-[#ABB7C2] bg-gray-50 dark:bg-[#2A2A3C] text-sm font-medium text-[#5E5F60] dark:text-white hover:border-[#5F2DED] hover:bg-[#F6F2FF] dark:hover:bg-[#34344A] transition-colors">
                    <span id="activityCurrentCategoryLabel">All categories</span>
                    <i class="icofont-rounded-down text-sm"></i>
                </button>
                <div id="activityMetricModeToggle"
                    class="relative inline-grid grid-cols-2 h-9 w-56 rounded-full border border-[#ABB7C2] bg-gray-50 dark:bg-[#2A2A3C] overflow-hidden">
                    <span id="activityMetricModeSlider"
                        class="pointer-events-none absolute inset-y-0 left-0 w-1/2 bg-[#5F2DED] rounded-full transition-transform duration-200 opacity-0"></span>
                    <button type="button" id="activityModeRelevancyBtn"
                        class="relative z-10 text-xs font-semibold transition-colors">
                        Relevance
                    </button>
                    <button type="button" id="activityModeCoverageBtn"
                        class="relative z-10 text-xs font-semibold transition-colors">
                        Coverage
                    </button>
                </div>
            </div>
            <button type="button" id="activityBackToTags"
                class="hidden inline-flex h-8 w-8 items-center justify-center rounded-full border border-[#ABB7C2] text-[#5F2DED] hover:bg-[#F6F2FF] dark:border-[#5F2DED] dark:hover:bg-[#2A2A3C]"
                title="Back to tags"
                aria-label="Back to tags">
                <i class="icofont-simple-left text-sm"></i>
            </button>
        </div>

        <div id="activityTagListContainer">
            <div class="mt-4 max-h-72 overflow-y-auto border border-gray-200 dark:border-gray-700 rounded-lg divide-y divide-gray-100 dark:divide-gray-800">
                @forelse ($allTags as $tag)
                    @php
                        $tagSetting = $assignedTagSettings[(string) $tag->id] ?? null;
                    @endphp
                    <label
                        class="activity-tag-option flex items-center gap-3 px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer"
                        data-tag-id="{{ $tag->id }}"
                        data-tag-name="{{ $tag->name }}"
                        data-tag-category="{{ $tag->category }}"
                        data-tag-relevance="{{ $tagSetting['relevance'] ?? $defaultTagRelevance }}"
                        data-tag-coverage="{{ $tagSetting['coverage'] ?? '' }}"
                    >
                        <input
                            type="checkbox"
                            class="activity-tag-checkbox h-4 w-4 rounded border-gray-300"
                            value="{{ $tag->id }}"
                            {{ $selectedTagIds->contains((int) $tag->id) ? 'checked' : '' }}
                        >
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
            <p id="activityTagListEmpty" class="mt-3 text-sm text-gray-500 dark:text-gray-300 hidden">
                No tags match your filters.
            </p>

            <div id="activityTagPagination" class="mt-4 flex items-center justify-between">
                <button
                    type="button"
                    id="activityTagPrevPage"
                    class="px-3 py-1.5 border border-[#ABB7C2] rounded-lg text-sm text-[#5E5F60] dark:text-white hover:bg-gray-100 dark:hover:bg-gray-800 disabled:opacity-50 disabled:cursor-not-allowed">
                    Prev
                </button>
                <p id="activityTagPageInfo" class="text-sm text-gray-500 dark:text-gray-300">Page 1 of 1</p>
                <button
                    type="button"
                    id="activityTagNextPage"
                    class="px-3 py-1.5 border border-[#ABB7C2] rounded-lg text-sm text-[#5E5F60] dark:text-white hover:bg-gray-100 dark:hover:bg-gray-800 disabled:opacity-50 disabled:cursor-not-allowed">
                    Next
                </button>
            </div>
        </div>

        <div id="activityTagCategoryPicker" class="hidden mt-4 border border-gray-200 dark:border-gray-700 rounded-md divide-y divide-gray-100 dark:divide-gray-800">
            <button
                type="button"
                class="activity-tag-category-option w-full text-left px-4 py-2.5 border-l-4 border-transparent hover:bg-gray-50 dark:hover:bg-gray-800 text-sm font-medium text-[#5E5F60] dark:text-white transition-colors"
                data-tag-category="">
                All categories
            </button>
            @forelse ($tagCategories as $category)
                <button
                    type="button"
                    class="activity-tag-category-option w-full text-left px-4 py-2.5 border-l-4 border-transparent hover:bg-gray-50 dark:hover:bg-gray-800 text-sm font-medium text-[#5E5F60] dark:text-white transition-colors"
                    data-tag-category="{{ $category }}">
                    {{ $category }}
                </button>
            @empty
                <div class="px-4 py-6 text-sm text-gray-500 dark:text-gray-300">
                    No categories available.
                </div>
            @endforelse
        </div>

        <p id="activityCategoryListEmpty" class="mt-3 text-sm text-gray-500 dark:text-gray-300 hidden">
            Category not found.
        </p>
        <div id="activityCategoryPagination" class="hidden mt-4 flex items-center justify-between">
            <button
                type="button"
                id="activityCategoryPrevPage"
                class="px-3 py-1.5 border border-[#ABB7C2] rounded-lg text-sm text-[#5E5F60] dark:text-white hover:bg-gray-100 dark:hover:bg-gray-800 disabled:opacity-50 disabled:cursor-not-allowed">
                Prev
            </button>
            <p id="activityCategoryPageInfo" class="text-sm text-gray-500 dark:text-gray-300">Page 1 of 1</p>
            <button
                type="button"
                id="activityCategoryNextPage"
                class="px-3 py-1.5 border border-[#ABB7C2] rounded-lg text-sm text-[#5E5F60] dark:text-white hover:bg-gray-100 dark:hover:bg-gray-800 disabled:opacity-50 disabled:cursor-not-allowed">
                Next
            </button>
        </div>

        <div id="activityTagRelevancyContainer" class="hidden mt-4">
            <div class="rounded-lg border border-[#E2E8F0] dark:border-[#334155] p-3 bg-[#F8FAFC] dark:bg-[#0B1220]">
                <div id="activityTagMetricsEditor" class="space-y-2"></div>
                <div id="activityRelevancyPagination" class="hidden mt-3 flex items-center justify-between">
                    <button
                        type="button"
                        id="activityRelevancyPrevPage"
                        class="px-3 py-1.5 border border-[#ABB7C2] rounded-lg text-sm text-[#5E5F60] dark:text-white hover:bg-gray-100 dark:hover:bg-gray-800 disabled:opacity-50 disabled:cursor-not-allowed">
                        Prev
                    </button>
                    <p id="activityRelevancyPageInfo" class="text-sm text-gray-500 dark:text-gray-300">Page 1 of 1</p>
                    <button
                        type="button"
                        id="activityRelevancyNextPage"
                        class="px-3 py-1.5 border border-[#ABB7C2] rounded-lg text-sm text-[#5E5F60] dark:text-white hover:bg-gray-100 dark:hover:bg-gray-800 disabled:opacity-50 disabled:cursor-not-allowed">
                        Next
                    </button>
                </div>
            </div>
        </div>

        <div class="mt-6 flex justify-end">
            <button
                type="button"
                id="activityConfirmTagsModal"
                class="px-4 py-2 bg-primaryColor text-white rounded hover:opacity-90"
            >
                Confirm
            </button>
        </div>
    </div>
</div>

@unless($isCreateMode)
<div id="deleteModal" class="fixed inset-0 items-center justify-center bg-black bg-opacity-50 hidden z-50">
    <div class="bg-white dark:bg-[#1E1E2D] p-6 rounded-lg w-96 mx-4">
        <h2 class="text-xl font-bold mb-4 text-red-600">Delete Lesson?</h2>
        <p class="text-[#373737] dark:text-white mb-6">
            This action cannot be undone. Students will lose access to this lesson.
        </p>

        <div class="flex justify-end gap-3">
            <button
                id="cancelDelete"
                type="button"
                class="px-4 py-2 bg-gray-300 rounded dark:bg-gray-600 dark:text-white"
            >
                Cancel
            </button>

            <form id="deleteLessonForm" action="{{ $deleteAction }}" method="POST">
                @csrf
                @method('DELETE')
                <button
                    id="confirmDeleteBtn"
                    type="button"
                    class="px-4 py-2 bg-red-600 text-white rounded flex items-center gap-2"
                >
                    <svg id="deleteLoadingSpinner" class="w-4 h-4 animate-spin hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                    </svg>
                    <span id="deleteBtnText">Delete</span>
                </button>
            </form>
        </div>
    </div>
</div>
@endunless

<div id="confirmSaveModal" class="fixed inset-0 bg-black bg-opacity-50 hidden justify-center items-center z-50">
    <div class="bg-white dark:bg-[#1E1E2D] p-6 rounded-lg shadow-lg w-96 mx-4">
        <h2 class="text-xl font-bold mb-3 text-primaryColor">{{ $isCreateMode ? 'Create Lesson?' : 'Save Lesson?' }}</h2>
        <p class="text-[#373737] dark:text-white mb-6">
            {{ $isCreateMode ? 'Are you sure you want to create this lesson?' : 'Are you sure you want to save all changes to this lesson?' }}
        </p>

        <div class="flex justify-end gap-3">
            <button id="cancelSaveBtn" type="button" class="px-4 py-2 bg-gray-300 dark:bg-gray-600 dark:text-white rounded">
                Cancel
            </button>
            <button id="confirmSaveBtn" type="button" class="px-4 py-2 min-w-[96px] bg-primaryColor text-white rounded flex items-center justify-center gap-2 transition disabled:opacity-75 disabled:cursor-wait">
                <svg id="saveLoadingSpinner" class="w-4 h-4 animate-spin hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                </svg>
                <span id="saveBtnText">{{ $isCreateMode ? 'Create' : 'Save' }}</span>
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
document.addEventListener("DOMContentLoaded", () => {
    const lessonForm = document.getElementById("lessonForm");
    const dropZone = document.getElementById("dropZone");
    const fileInput = document.getElementById("fileInput");
    const previewList = document.getElementById("filePreviewList");

    const visibilityHeader = document.getElementById("visibilityHeader");
    const visibilityContent = document.getElementById("visibilityContent");
    const visibilityArrow = document.getElementById("visibilityArrow");

    const tagsHeader = document.getElementById("activityTagsHeader");
    const tagsContent = document.getElementById("activityTagsContent");
    const tagsArrow = document.getElementById("activityTagsArrow");

    const visibilityStatusText = document.getElementById("visibilityStatusText");
    const visibleToggle = document.getElementById("is_visible");

    const openSaveModalBtn = document.getElementById("openSaveModal");
    const confirmSaveModal = document.getElementById("confirmSaveModal");
    const cancelSaveBtn = document.getElementById("cancelSaveBtn");
    const confirmSaveBtn = document.getElementById("confirmSaveBtn");
    const saveLoadingSpinner = document.getElementById("saveLoadingSpinner");
    const saveBtnText = document.getElementById("saveBtnText");
    const defaultSaveBtnText = saveBtnText ? saveBtnText.textContent : "Save";
    const defaultOpenSaveBtnText = openSaveModalBtn ? openSaveModalBtn.textContent.trim() : "Save";

    const openDeleteModalBtn = document.getElementById("openDeleteModal");
    const deleteModal = document.getElementById("deleteModal");
    const cancelDeleteBtn = document.getElementById("cancelDelete");
    const confirmDeleteBtn = document.getElementById("confirmDeleteBtn");
    const deleteLessonForm = document.getElementById("deleteLessonForm");
    const deleteLoadingSpinner = document.getElementById("deleteLoadingSpinner");
    const deleteBtnText = document.getElementById("deleteBtnText");

    const removeFileModal = document.getElementById("removeFileModal");
    const removeFileForm = document.getElementById("removeFileForm");
    const cancelRemoveFileBtn = document.getElementById("cancelRemoveFile");
    const confirmRemoveFileBtn = document.getElementById("confirmRemoveFile");
    const removeLoadingSpinner = document.getElementById("removeLoadingSpinner");
    const removeBtnText = document.getElementById("removeBtnText");

    const tagsModal = document.getElementById("activityTagsModal");
    const openTagsModalBtn = document.getElementById("activityOpenTagsModal");
    const closeTagsModalBtn = document.getElementById("activityCloseTagsModal");
    const confirmTagsModalBtn = document.getElementById("activityConfirmTagsModal");
    const clearTagsBtn = document.getElementById("activityClearTagsBtn");
    const noTagsMessage = document.getElementById("activityNoTagsMessage");
    const tagPillsContainer = document.getElementById("activityTagPills");
    const tagInputsContainer = document.getElementById("activityTagInputs");

    const tagSearchInput = document.getElementById("activityTagSearchInput");
    const tagOptions = Array.from(document.querySelectorAll(".activity-tag-option"));
    const tagCheckboxes = Array.from(document.querySelectorAll(".activity-tag-checkbox"));
    const tagListContainer = document.getElementById("activityTagListContainer");
    const tagListEmpty = document.getElementById("activityTagListEmpty");
    const tagPagination = document.getElementById("activityTagPagination");
    const tagPrevPageBtn = document.getElementById("activityTagPrevPage");
    const tagNextPageBtn = document.getElementById("activityTagNextPage");
    const tagPageInfo = document.getElementById("activityTagPageInfo");

    const openCategoryPickerBtn = document.getElementById("activityOpenCategoryPicker");
    const backToTagsBtn = document.getElementById("activityBackToTags");
    const currentCategoryLabel = document.getElementById("activityCurrentCategoryLabel");
    const tagCategoryPicker = document.getElementById("activityTagCategoryPicker");
    const tagCategoryButtons = Array.from(document.querySelectorAll(".activity-tag-category-option"));
    const categoryPagination = document.getElementById("activityCategoryPagination");
    const categoryPrevPageBtn = document.getElementById("activityCategoryPrevPage");
    const categoryNextPageBtn = document.getElementById("activityCategoryNextPage");
    const categoryPageInfo = document.getElementById("activityCategoryPageInfo");
    const categoryListEmpty = document.getElementById("activityCategoryListEmpty");
    const metricModeRelevancyBtn = document.getElementById("activityModeRelevancyBtn");
    const metricModeCoverageBtn = document.getElementById("activityModeCoverageBtn");
    const metricModeSlider = document.getElementById("activityMetricModeSlider");
    const tagRelevancyContainer = document.getElementById("activityTagRelevancyContainer");
    const tagMetricsEditor = document.getElementById("activityTagMetricsEditor");
    const relevancyPagination = document.getElementById("activityRelevancyPagination");
    const relevancyPrevPageBtn = document.getElementById("activityRelevancyPrevPage");
    const relevancyNextPageBtn = document.getElementById("activityRelevancyNextPage");
    const relevancyPageInfo = document.getElementById("activityRelevancyPageInfo");
    const downloadAllButtons = Array.from(document.querySelectorAll("[data-download-all-target]"));

    const triggerAttachmentDownloads = (targetSelector) => {
        if (!targetSelector) return;

        const container = document.querySelector(targetSelector);
        if (!container) return;

        const links = Array.from(container.querySelectorAll('a[data-download-link="true"]'));
        links.forEach((link, index) => {
            window.setTimeout(() => {
                const tempLink = document.createElement("a");
                tempLink.href = link.href;
                tempLink.setAttribute("download", "");
                tempLink.style.display = "none";
                document.body.appendChild(tempLink);
                tempLink.click();
                tempLink.remove();
            }, index * 150);
        });
    };

    const sections = [
        { header: visibilityHeader, content: visibilityContent, arrow: visibilityArrow, expandedPadding: Number(visibilityContent?.dataset.expandedPadding || 0), open: false },
        { header: tagsHeader, content: tagsContent, arrow: tagsArrow, expandedPadding: Number(tagsContent?.dataset.expandedPadding || 0), open: false },
    ];

    downloadAllButtons.forEach((button) => {
        button.addEventListener("click", () => {
            triggerAttachmentDownloads(button.dataset.downloadAllTarget || "");
        });
    });

    const updateSectionState = (section) => {
        if (!section.content || !section.arrow) return;

        if (section.open) {
            section.content.style.paddingBottom = `${section.expandedPadding}px`;
            section.content.style.borderBottomWidth = "1px";
            section.content.style.maxHeight = section.content.scrollHeight + "px";
            section.content.style.overflow = "visible";
            section.arrow.classList.add("rotate-180");
        } else {
            section.content.style.maxHeight = "0px";
            section.content.style.overflow = "hidden";
            section.content.style.paddingBottom = "0px";
            section.content.style.borderBottomWidth = "0px";
            section.arrow.classList.remove("rotate-180");
        }
    };

    const resizeOpenSections = () => {
        sections.forEach((section) => {
            if (!section.open || !section.content) return;
            section.content.style.maxHeight = section.content.scrollHeight + "px";
        });
    };

    sections.forEach((section) => {
        updateSectionState(section);
        if (!section.header) return;

        section.header.addEventListener("click", () => {
            section.open = !section.open;
            updateSectionState(section);
        });
    });

    if (dropZone && fileInput && previewList) {
        let uploadedFiles = [];

        const syncInput = () => {
            const dt = new DataTransfer();
            uploadedFiles.forEach((file) => dt.items.add(file));
            fileInput.files = dt.files;
        };

        const renderPreview = () => {
            previewList.innerHTML = "";

            uploadedFiles.forEach((file, index) => {
                const row = document.createElement("div");
                row.className = "flex items-center justify-between border border-[#D4D4D8] dark:border-[#5F2DED] bg-white dark:bg-[#2A2A3C] rounded-lg px-4 py-2";
                row.innerHTML = `
                    <div class="flex items-center gap-3 truncate">
                        <div class="w-8 h-8 flex items-center justify-center rounded bg-[#E1D6FF] text-[#5F2DED]">
                            <i class="icofont-file-alt"></i>
                        </div>
                        <span class="truncate text-sm text-[#373737] dark:text-white">${file.name}</span>
                    </div>
                    <button type="button" class="text-red-500 hover:text-red-700 text-lg font-bold" data-file-index="${index}">
                        x
                    </button>
                `;
                previewList.appendChild(row);
            });

            previewList.querySelectorAll("button[data-file-index]").forEach((button) => {
                button.addEventListener("click", () => {
                    const index = Number(button.dataset.fileIndex);
                    if (!Number.isInteger(index)) return;
                    uploadedFiles.splice(index, 1);
                    syncInput();
                    renderPreview();
                    resizeOpenSections();
                });
            });
        };

        const addFiles = (fileList) => {
            Array.from(fileList).forEach((file) => {
                const exists = uploadedFiles.some((item) => item.name === file.name && item.size === file.size);
                if (!exists) {
                    uploadedFiles.push(file);
                }
            });
            syncInput();
            renderPreview();
            resizeOpenSections();
        };

        dropZone.addEventListener("click", () => {
            // Clear only before opening picker so same-file reselection works
            // without wiping the synced FileList after selection.
            fileInput.value = "";
            fileInput.click();
        });
        ["dragenter", "dragover"].forEach((eventName) => {
            dropZone.addEventListener(eventName, (event) => {
                event.preventDefault();
                event.stopPropagation();
                dropZone.classList.add("border-[#5F2DED]");
            });
        });

        ["dragleave", "drop"].forEach((eventName) => {
            dropZone.addEventListener(eventName, (event) => {
                event.preventDefault();
                event.stopPropagation();
                dropZone.classList.remove("border-[#5F2DED]");
            });
        });

        dropZone.addEventListener("drop", (event) => {
            addFiles(event.dataTransfer.files);
        });

        fileInput.addEventListener("change", () => {
            addFiles(fileInput.files);
        });
    }

    const setStatusState = (className, text) => {
        if (!visibilityStatusText) return;
        visibilityStatusText.className = `text-sm font-semibold ${className}`;
        visibilityStatusText.textContent = text;
    };

    const updateVisibilityStatus = () => {
        if (!visibleToggle) return;

        const isVisible = Boolean(visibleToggle.checked);

        if (!isVisible) {
            setStatusState("text-red-500", "Lesson is not visible and closed");
            return;
        }

        setStatusState("text-green-500", "Lesson is visible and open");
    };

    [visibleToggle].forEach((element) => {
        element?.addEventListener("change", updateVisibilityStatus);
        element?.addEventListener("input", updateVisibilityStatus);
    });
    updateVisibilityStatus();

    document.querySelectorAll(".open-remove-modal").forEach((button) => {
        button.addEventListener("click", () => {
            if (!removeFileModal || !removeFileForm) return;
            removeFileForm.action = button.dataset.action;
            removeFileModal.classList.remove("hidden");
            removeFileModal.classList.add("flex");
        });
    });

    cancelRemoveFileBtn?.addEventListener("click", () => {
        if (!removeFileModal) return;
        removeFileModal.classList.add("hidden");
        removeFileModal.classList.remove("flex");
        if (confirmRemoveFileBtn && removeLoadingSpinner && removeBtnText) {
            confirmRemoveFileBtn.disabled = false;
            removeLoadingSpinner.classList.add("hidden");
            removeBtnText.textContent = "Remove";
        }
    });

    removeFileForm?.addEventListener("submit", () => {
        if (!confirmRemoveFileBtn || !removeLoadingSpinner || !removeBtnText) return;
        confirmRemoveFileBtn.disabled = true;
        removeLoadingSpinner.classList.remove("hidden");
        removeBtnText.textContent = "Removing...";
    });

    openSaveModalBtn?.addEventListener("click", () => {
        confirmSaveModal?.classList.remove("hidden");
        confirmSaveModal?.classList.add("flex");
        openSaveModalBtn.disabled = false;
        openSaveModalBtn.classList.remove("opacity-75", "cursor-wait");
        openSaveModalBtn.textContent = defaultOpenSaveBtnText;
        if (confirmSaveBtn && saveLoadingSpinner && saveBtnText && cancelSaveBtn) {
            confirmSaveBtn.disabled = false;
            confirmSaveBtn.classList.remove("opacity-75", "cursor-wait");
            cancelSaveBtn.disabled = false;
            cancelSaveBtn.classList.remove("opacity-60", "cursor-not-allowed");
            saveLoadingSpinner.classList.add("hidden");
            saveBtnText.textContent = defaultSaveBtnText;
        }
    });

    cancelSaveBtn?.addEventListener("click", () => {
        confirmSaveModal?.classList.add("hidden");
        confirmSaveModal?.classList.remove("flex");
    });

    confirmSaveBtn?.addEventListener("click", () => {
        if (!lessonForm || !saveLoadingSpinner || !saveBtnText || !confirmSaveBtn || !cancelSaveBtn) return;
        confirmSaveBtn.disabled = true;
        confirmSaveBtn.classList.add("opacity-75", "cursor-wait");
        cancelSaveBtn.disabled = true;
        cancelSaveBtn.classList.add("opacity-60", "cursor-not-allowed");
        if (openSaveModalBtn) {
            openSaveModalBtn.disabled = false;
            openSaveModalBtn.classList.remove("opacity-75", "cursor-wait");
            openSaveModalBtn.textContent = defaultOpenSaveBtnText;
        }
        saveLoadingSpinner.classList.remove("hidden");
        saveBtnText.textContent = "Saving...";

        window.setTimeout(() => {
            lessonForm.submit();
        }, 250);
    });

    openDeleteModalBtn?.addEventListener("click", () => {
        deleteModal?.classList.remove("hidden");
        deleteModal?.classList.add("flex");
    });

    cancelDeleteBtn?.addEventListener("click", () => {
        deleteModal?.classList.add("hidden");
        deleteModal?.classList.remove("flex");
    });

    confirmDeleteBtn?.addEventListener("click", () => {
        if (!deleteLessonForm || !confirmDeleteBtn || !deleteLoadingSpinner || !deleteBtnText) return;
        confirmDeleteBtn.disabled = true;
        deleteLoadingSpinner.classList.remove("hidden");
        deleteBtnText.textContent = "Deleting...";
        window.setTimeout(() => {
            deleteLessonForm.submit();
        }, 250);
    });

    const escapeHtml = (value) => {
        return String(value ?? "")
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#39;");
    };

    const tagsPerPage = 6;
    const categoriesPerPage = 6;
    const relevancyCardsPerPage = 3;
    let currentTagPage = 1;
    let currentCategoryPage = 1;
    let currentRelevancyPage = 1;
    let currentCategory = "";
    let isCategoryMode = false;
    let isRelevancyMode = false;
    let currentMetricMode = "relevance";
    let focusedMetricTagId = null;
    let filteredTagOptions = [...tagOptions];
    let filteredCategoryButtons = [...tagCategoryButtons];

    const categoryActiveClasses = ["bg-[#F3EEFF]", "dark:bg-[#34255F]", "border-[#5F2DED]", "text-[#5F2DED]", "dark:text-white"];
    const categoryInactiveClasses = ["bg-transparent", "border-transparent", "text-[#5E5F60]", "dark:text-white"];
    const relevanceLevels = ["very_high", "high", "medium", "low", "very_low"];
    const relevanceScale = ["very_low", "low", "medium", "high", "very_high"];
    const relevanceLabels = {
        very_high: "Very High",
        high: "High",
        medium: "Medium",
        low: "Low",
        very_low: "Very Low",
    };
    const relevanceRank = {
        very_high: 0,
        high: 1,
        medium: 2,
        low: 3,
        very_low: 4,
    };
    const relevancePillToneClasses = {
        very_high: "tag-pill-tone-very-high",
        high: "tag-pill-tone-high",
        medium: "tag-pill-tone-medium",
        low: "tag-pill-tone-low",
        very_low: "tag-pill-tone-very-low",
    };
    const defaultRelevance = @json(config('reviewers.default_tag_relevance', 'medium'));
    const tagMetrics = {};

    const toggleTagsModal = (show) => {
        if (!tagsModal) return;
        tagsModal.classList.toggle("hidden", !show);
        tagsModal.classList.toggle("flex", show);
        document.body.classList.toggle("modal-open", show);
    };

    const normalizeRelevance = (value) => {
        const normalized = String(value || "").trim().toLowerCase();
        if (relevanceLevels.includes(normalized)) {
            return normalized;
        }
        return relevanceLevels.includes(defaultRelevance) ? defaultRelevance : "medium";
    };

    const normalizeCoverage = (value) => {
        if (value === null || value === undefined || value === "") {
            return "5";
        }
        const parsed = Number(value);
        if (!Number.isFinite(parsed)) {
            return "5";
        }
        const bounded = Math.min(100, Math.max(5, parsed));
        const snapped = Math.round(bounded / 5) * 5;
        return String(snapped);
    };

    tagOptions.forEach((option) => {
        const tagId = String(option.dataset.tagId || "").trim();
        if (!tagId) return;
        tagMetrics[tagId] = {
            relevance: normalizeRelevance(option.dataset.tagRelevance),
            coverage: normalizeCoverage(option.dataset.tagCoverage),
        };
    });

    const getSelectedTagIds = () => {
        return tagCheckboxes
            .filter((checkbox) => checkbox.checked)
            .map((checkbox) => String(checkbox.value));
    };

    const getMetricTargetTagIds = () => {
        const selectedIds = getSelectedTagIds();
        if (focusedMetricTagId && selectedIds.includes(focusedMetricTagId)) {
            return [focusedMetricTagId];
        }
        return selectedIds;
    };

    const syncHiddenTagInputs = (tagIds) => {
        if (!tagInputsContainer) return;
        tagInputsContainer.innerHTML = "";
        tagIds.forEach((tagId) => {
            const tagKey = String(tagId);
            if (!tagMetrics[tagKey]) {
                tagMetrics[tagKey] = {
                    relevance: normalizeRelevance(defaultRelevance),
                    coverage: "5",
                };
            }

            const input = document.createElement("input");
            input.type = "hidden";
            input.name = "tag_ids[]";
            input.value = tagKey;
            input.dataset.hiddenTagId = tagKey;
            tagInputsContainer.appendChild(input);

            const relevanceInput = document.createElement("input");
            relevanceInput.type = "hidden";
            relevanceInput.name = `tag_relevance[${tagKey}]`;
            relevanceInput.value = tagMetrics[tagKey].relevance;
            tagInputsContainer.appendChild(relevanceInput);

            const coverageInput = document.createElement("input");
            coverageInput.type = "hidden";
            coverageInput.name = `tag_coverage[${tagKey}]`;
            coverageInput.value = tagMetrics[tagKey].coverage;
            tagInputsContainer.appendChild(coverageInput);
        });
    };

    const renderTagPills = (tagIds = getSelectedTagIds()) => {
        if (!tagPillsContainer) return;

        tagPillsContainer.querySelectorAll('[data-tag-pill="true"]').forEach((pill) => pill.remove());

        const entries = tagIds
            .map((tagId) => {
                const option = tagOptions.find((item) => item.dataset.tagId === String(tagId));
                const metric = tagMetrics[String(tagId)] || {
                    relevance: normalizeRelevance(defaultRelevance),
                    coverage: "5",
                };
                const relevance = normalizeRelevance(metric.relevance);
                const coverage = Number.parseInt(normalizeCoverage(metric.coverage), 10);
                return {
                    id: String(tagId),
                    name: option?.dataset.tagName || "Tag",
                    relevance,
                    coverage: Number.isFinite(coverage) ? coverage : 5,
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

        entries.forEach((tag) => {
            const pill = document.createElement("button");
            pill.type = "button";
            pill.dataset.tagPill = "true";
            pill.dataset.tagId = tag.id;
            pill.title = "Edit relevance";
            const toneClass = relevancePillToneClasses[tag.relevance] || relevancePillToneClasses.medium;
            pill.className = `tag-pill inline-flex items-center gap-1.5 px-3 py-1 rounded-full border transition ${toneClass}`;
            pill.innerHTML = `
                <span class="text-sm">${escapeHtml(tag.name)}</span>
                <span class="ml-1 text-[10px] leading-none opacity-80">${tag.coverage}%</span>
                <span class="tag-pill-remove text-xs text-gray-400 hover:text-red-500">x</span>
            `;
            tagPillsContainer.appendChild(pill);
        });

        noTagsMessage?.classList.toggle("hidden", entries.length > 0);
        syncHiddenTagInputs(tagIds);
        resizeOpenSections();
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
        const selectedCategory = String(currentCategory || "").trim().toLowerCase();
        tagCategoryButtons.forEach((button) => {
            const buttonCategory = String(button.dataset.tagCategory || "").trim().toLowerCase();
            setCategoryButtonState(button, buttonCategory === selectedCategory);
        });
    };

    const getTagTotalPages = () => {
        if (filteredTagOptions.length === 0) return 0;
        return Math.ceil(filteredTagOptions.length / tagsPerPage);
    };

    const getCategoryTotalPages = () => {
        if (filteredCategoryButtons.length === 0) return 0;
        return Math.ceil(filteredCategoryButtons.length / categoriesPerPage);
    };

    const applyTagPagination = () => {
        if (tagOptions.length === 0) {
            tagPagination?.classList.add("hidden");
            tagListEmpty?.classList.add("hidden");
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
            option.classList.toggle("hidden", !visibleOptions.has(option));
        });

        if (tagPageInfo) {
            tagPageInfo.textContent = totalPages > 0
                ? `Page ${currentTagPage} of ${totalPages}`
                : "Page 0 of 0";
        }
        tagPagination?.classList.toggle("hidden", totalPages <= 1);
        if (tagPrevPageBtn) {
            tagPrevPageBtn.disabled = currentTagPage <= 1;
        }
        if (tagNextPageBtn) {
            tagNextPageBtn.disabled = currentTagPage >= totalPages;
        }
        tagListEmpty?.classList.toggle("hidden", filteredTagOptions.length > 0);
    };

    const applyCategoryPagination = () => {
        if (tagCategoryButtons.length === 0) {
            categoryPagination?.classList.add("hidden");
            return;
        }

        const totalPages = getCategoryTotalPages();
        if (currentCategoryPage > totalPages) {
            currentCategoryPage = totalPages || 1;
        }

        const start = (currentCategoryPage - 1) * categoriesPerPage;
        const end = start + categoriesPerPage;
        const visibleButtons = new Set(filteredCategoryButtons.slice(start, end));

        tagCategoryButtons.forEach((button) => {
            button.classList.toggle("hidden", !visibleButtons.has(button));
        });

        if (categoryPageInfo) {
            categoryPageInfo.textContent = totalPages > 0
                ? `Page ${currentCategoryPage} of ${totalPages}`
                : "Page 0 of 0";
        }
        categoryPagination?.classList.toggle("hidden", totalPages <= 1);
        if (categoryPrevPageBtn) {
            categoryPrevPageBtn.disabled = currentCategoryPage <= 1;
        }
        if (categoryNextPageBtn) {
            categoryNextPageBtn.disabled = currentCategoryPage >= totalPages;
        }

        refreshCategoryButtonStates();
    };

    const updateSearchPlaceholder = () => {
        if (!tagSearchInput) return;
        if (isRelevancyMode) {
            tagSearchInput.placeholder = "Search selected tags...";
            return;
        }
        tagSearchInput.placeholder = isCategoryMode ? "Search categories..." : "Search tags...";
    };

    const setMetricModeSwitchState = () => {
        const isCoverageMode = currentMetricMode === "coverage";
        if (metricModeSlider) {
            metricModeSlider.style.opacity = isRelevancyMode ? "1" : "0";
            metricModeSlider.style.transform = isCoverageMode ? "translateX(100%)" : "translateX(0)";
        }

        const setButtonState = (button, isActive) => {
            if (!button) return;
            button.classList.toggle("text-white", isRelevancyMode && isActive);
            button.classList.toggle("text-[#5E5F60]", !(isRelevancyMode && isActive));
            button.classList.toggle("dark:text-gray-300", !(isRelevancyMode && isActive));
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
        const term = (tagSearchInput?.value || "").trim().toLowerCase();
        if (!term) {
            return selectedIds;
        }

        return selectedIds.filter((id) => {
            const tagId = String(id);
            const option = tagOptions.find((item) => item.dataset.tagId === tagId);
            const name = String(option?.dataset.tagName || "").toLowerCase();
            const category = String(option?.dataset.tagCategory || "").toLowerCase();
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
        relevancyPagination.classList.toggle("hidden", !isRelevancyMode || totalPages <= 1);
    };

    const toggleCategoryPicker = (show) => {
        if (isRelevancyMode) {
            return;
        }
        tagListContainer?.classList.toggle("hidden", show);
        tagCategoryPicker?.classList.toggle("hidden", !show);
        tagRelevancyContainer?.classList.add("hidden");
        backToTagsBtn?.classList.toggle("hidden", !(show || isRelevancyMode));
        categoryPagination?.classList.toggle("hidden", !show);
        categoryListEmpty?.classList.add("hidden");
        isCategoryMode = show;
        updateSearchPlaceholder();
        if (show) refreshCategoryButtonStates();
    };

    const toggleRelevancyMode = (show, mode = currentMetricMode) => {
        currentMetricMode = mode === "coverage" ? "coverage" : "relevance";
        isRelevancyMode = show;

        if (show) {
            currentRelevancyPage = 1;
            isCategoryMode = false;
            tagListContainer?.classList.add("hidden");
            tagCategoryPicker?.classList.add("hidden");
            tagRelevancyContainer?.classList.remove("hidden");
            backToTagsBtn?.classList.remove("hidden");
            categoryPagination?.classList.add("hidden");
            categoryListEmpty?.classList.add("hidden");
            tagPagination?.classList.add("hidden");
            tagListEmpty?.classList.add("hidden");
            renderTagMetricsEditor(getMetricTargetTagIds());
        } else {
            tagRelevancyContainer?.classList.add("hidden");
            syncRelevancyPaginationState(0);
            toggleCategoryPicker(false);
            filterTags();
        }

        updateSearchPlaceholder();
        setMetricModeSwitchState();
    };

    const filterTags = () => {
        const term = (tagSearchInput?.value || "").trim().toLowerCase();
        const categoryValue = currentCategory.trim().toLowerCase();

        filteredTagOptions = tagOptions.filter((option) => {
            const name = (option.dataset.tagName || "").toLowerCase();
            const category = (option.dataset.tagCategory || "").toLowerCase();
            const matchesTerm = !term || name.includes(term) || category.includes(term);
            const matchesCategory = !categoryValue || category === categoryValue;
            return matchesTerm && matchesCategory;
        });

        currentTagPage = 1;
        applyTagPagination();
    };

    const filterCategories = () => {
        const term = (tagSearchInput?.value || "").trim().toLowerCase();

        filteredCategoryButtons = tagCategoryButtons.filter((button) => {
            const label = (button.textContent || "").trim().toLowerCase();
            return !term || label.includes(term);
        });

        currentCategoryPage = 1;
        applyCategoryPagination();
        categoryListEmpty?.classList.toggle("hidden", filteredCategoryButtons.length > 0);
    };

    const renderTagMetricsEditor = (selectedIds) => {
        if (!tagMetricsEditor) return;

        if (!selectedIds.length) {
            tagMetricsEditor.innerHTML = '<p class="text-xs text-gray-500 dark:text-gray-300">Select at least one tag to configure relevance and coverage.</p>';
            syncRelevancyPaginationState(0);
            resizeOpenSections();
            return;
        }

        const filteredSelectedIds = getFilteredRelevancyTagIds(selectedIds);
        if (!filteredSelectedIds.length) {
            tagMetricsEditor.innerHTML = '<p class="text-xs text-gray-500 dark:text-gray-300">No selected tags match your search.</p>';
            syncRelevancyPaginationState(0);
            resizeOpenSections();
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
            const tagName = option?.dataset.tagName || "Tag";
            const metric = tagMetrics[tagId] || {
                relevance: normalizeRelevance(defaultRelevance),
                coverage: "5",
            };
            const currentRelevance = normalizeRelevance(metric.relevance);
            const relevanceIndex = Math.max(0, relevanceScale.indexOf(currentRelevance));
            const currentCoverage = normalizeCoverage(metric.coverage);
            const isCoverageMode = currentMetricMode === "coverage";

            return `
                <div class="border border-[#94A3B8] dark:border-[#475569] rounded-none p-3 transition-colors hover:bg-[#EFF6FF] dark:hover:bg-[#12243D] focus-within:bg-[#DBEAFE] dark:focus-within:bg-[#1E3A5F]">
                    <p class="text-sm text-[#373737] dark:text-white font-medium">${escapeHtml(tagName)}</p>
                    <div class="mt-2 ${isCoverageMode ? "hidden" : ""}" data-metric-relevance-panel="${tagId}">
                        <div class="flex items-center gap-3">
                            <div class="flex-1" style="padding-right: 0.5in;">
                                <input type="range"
                                    min="0" max="4" step="1"
                                    value="${relevanceIndex}"
                                    data-metric-relevance-slider="${tagId}"
                                    class="tag-gradient-slider w-full">
                            </div>
                            <span data-metric-relevance-current="${tagId}" style="width: 100px; flex: 0 0 100px;" class="shrink-0 whitespace-nowrap text-right text-sm font-semibold font-poppins text-[#334155] dark:text-white">
                                ${escapeHtml(relevanceLabels[currentRelevance] || "Medium")}
                            </span>
                        </div>
                    </div>
                    <div class="mt-2 ${isCoverageMode ? "" : "hidden"}" data-metric-coverage-panel="${tagId}">
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
        }).join("");

        tagMetricsEditor.innerHTML = rows;

        tagMetricsEditor.querySelectorAll("[data-metric-relevance-slider]").forEach((element) => {
            element.addEventListener("input", (event) => {
                const target = event.target;
                if (!(target instanceof HTMLInputElement)) return;
                const tagId = String(target.getAttribute("data-metric-relevance-slider") || "");
                if (!tagId) return;
                const rawIndex = Number.parseInt(target.value, 10);
                const clampedIndex = Number.isFinite(rawIndex)
                    ? Math.min(4, Math.max(0, rawIndex))
                    : 0;
                target.value = String(clampedIndex);
                const relevanceValue = normalizeRelevance(relevanceScale[clampedIndex] || defaultRelevance);

                if (!tagMetrics[tagId]) {
                    tagMetrics[tagId] = { relevance: normalizeRelevance(defaultRelevance), coverage: "5" };
                }
                tagMetrics[tagId].relevance = relevanceValue;
                const relevanceLabel = tagMetricsEditor.querySelector(`[data-metric-relevance-current="${tagId}"]`);
                if (relevanceLabel) {
                    relevanceLabel.textContent = relevanceLabels[relevanceValue] || "Medium";
                }
                const selectedTagIds = getSelectedTagIds();
                renderTagPills(selectedTagIds);
            });
        });

        tagMetricsEditor.querySelectorAll("[data-metric-coverage-slider]").forEach((element) => {
            element.addEventListener("input", (event) => {
                const target = event.target;
                if (!(target instanceof HTMLInputElement)) return;
                const tagId = String(target.getAttribute("data-metric-coverage-slider") || "");
                if (!tagId) return;
                if (!tagMetrics[tagId]) {
                    tagMetrics[tagId] = { relevance: normalizeRelevance(defaultRelevance), coverage: "5" };
                }
                const normalizedCoverage = normalizeCoverage(target.value);
                target.value = normalizedCoverage;
                tagMetrics[tagId].coverage = normalizedCoverage;
                const coverageLabel = tagMetricsEditor.querySelector(`[data-metric-coverage-current="${tagId}"]`);
                if (coverageLabel) {
                    coverageLabel.textContent = `${normalizedCoverage}%`;
                }
                const selectedTagIds = getSelectedTagIds();
                renderTagPills(selectedTagIds);
            });
        });

        syncRelevancyPaginationState(totalPages);
        resizeOpenSections();
    };

    const saveTagSelection = () => {
        const selectedIds = getSelectedTagIds();
        focusedMetricTagId = null;
        renderTagPills(selectedIds);
        toggleTagsModal(false);
        toggleRelevancyMode(false);
    };

    openTagsModalBtn?.addEventListener("click", () => {
        focusedMetricTagId = null;
        if (tagSearchInput) {
            tagSearchInput.value = "";
        }
        toggleRelevancyMode(false);
        toggleCategoryPicker(false);
        filterTags();
        toggleTagsModal(true);
    });

    closeTagsModalBtn?.addEventListener("click", () => {
        focusedMetricTagId = null;
        toggleRelevancyMode(false);
        toggleCategoryPicker(false);
        toggleTagsModal(false);
    });

    confirmTagsModalBtn?.addEventListener("click", () => {
        saveTagSelection();
    });

    clearTagsBtn?.addEventListener("click", () => {
        tagCheckboxes.forEach((checkbox) => {
            checkbox.checked = false;
        });
        saveTagSelection();
    });

    tagSearchInput?.addEventListener("input", () => {
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

    tagPrevPageBtn?.addEventListener("click", () => {
        if (currentTagPage <= 1) return;
        currentTagPage -= 1;
        applyTagPagination();
    });

    tagNextPageBtn?.addEventListener("click", () => {
        const totalPages = getTagTotalPages();
        if (currentTagPage >= totalPages) return;
        currentTagPage += 1;
        applyTagPagination();
    });

    openCategoryPickerBtn?.addEventListener("click", () => {
        if (isRelevancyMode) {
            toggleRelevancyMode(false);
        }
        currentCategoryPage = 1;
        toggleCategoryPicker(true);
        filterCategories();
    });

    backToTagsBtn?.addEventListener("click", () => {
        if (isRelevancyMode) {
            focusedMetricTagId = null;
            toggleRelevancyMode(false);
            return;
        }
        toggleCategoryPicker(false);
        filterTags();
    });

    tagCategoryButtons.forEach((button) => {
        button.addEventListener("click", () => {
            currentCategory = button.dataset.tagCategory || "";
            if (currentCategoryLabel) {
                currentCategoryLabel.textContent = currentCategory || "All categories";
            }
            refreshCategoryButtonStates();
            toggleCategoryPicker(false);
            filterTags();
        });
    });

    categoryPrevPageBtn?.addEventListener("click", () => {
        if (currentCategoryPage <= 1) return;
        currentCategoryPage -= 1;
        applyCategoryPagination();
    });

    categoryNextPageBtn?.addEventListener("click", () => {
        const totalPages = getCategoryTotalPages();
        if (currentCategoryPage >= totalPages) return;
        currentCategoryPage += 1;
        applyCategoryPagination();
    });

    metricModeRelevancyBtn?.addEventListener("click", () => {
        toggleRelevancyMode(true, "relevance");
    });

    metricModeCoverageBtn?.addEventListener("click", () => {
        toggleRelevancyMode(true, "coverage");
    });

    relevancyPrevPageBtn?.addEventListener("click", () => {
        if (currentRelevancyPage <= 1) return;
        currentRelevancyPage -= 1;
        renderTagMetricsEditor(getMetricTargetTagIds());
    });

    relevancyNextPageBtn?.addEventListener("click", () => {
        const totalPages = getRelevancyTotalPages(getFilteredRelevancyTagIds(getMetricTargetTagIds()));
        if (currentRelevancyPage >= totalPages) return;
        currentRelevancyPage += 1;
        renderTagMetricsEditor(getMetricTargetTagIds());
    });

    tagPillsContainer?.addEventListener("click", (event) => {
        const target = event.target;
        if (!(target instanceof Element)) return;

        const pill = target.closest('[data-tag-pill="true"]');
        if (!pill) return;

        const tagId = String(pill.dataset.tagId || "");
        if (!tagId) return;

        const removeButton = target.closest(".tag-pill-remove");
        if (removeButton) {
            const checkbox = document.querySelector(`.activity-tag-checkbox[value="${tagId}"]`);
            if (checkbox) {
                checkbox.checked = false;
            }
            if (focusedMetricTagId === tagId) {
                focusedMetricTagId = null;
            }
            saveTagSelection();
            return;
        }

        const checkbox = document.querySelector(`.activity-tag-checkbox[value="${tagId}"]`);
        if (!checkbox || !checkbox.checked) {
            return;
        }

        focusedMetricTagId = tagId;
        currentMetricMode = "relevance";
        currentRelevancyPage = 1;
        if (tagSearchInput) {
            tagSearchInput.value = "";
        }
        toggleCategoryPicker(false);
        filterTags();
        toggleTagsModal(true);
        toggleRelevancyMode(true, "relevance");
    });

    tagCheckboxes.forEach((checkbox) => {
        checkbox.addEventListener("change", () => {
            if (!isRelevancyMode) return;
            currentRelevancyPage = 1;
            renderTagMetricsEditor(getMetricTargetTagIds());
        });
    });

    tagsModal?.addEventListener("click", (event) => {
        if (event.target === tagsModal) {
            focusedMetricTagId = null;
            toggleRelevancyMode(false);
            toggleCategoryPicker(false);
            toggleTagsModal(false);
        }
    });

    renderTagPills(getSelectedTagIds());
    setMetricModeSwitchState();
    filterTags();
    resizeOpenSections();
});
</script>
