@extends('pages.teacher.layout')

@section('content')
<div class="bg-[#FFFFFF] dark:bg-[#1E1E2D] dark:border-[#5F2DED] shadow-lg border p-8 rounded-lg">
    <div class="border-b border-[#D4D4D8] dark:border-[#5F2DED] pb-4">
        <h2 class="text-2xl font-semibold text-[#373737] dark:text-white">Reviewers</h2>
        <p class="text-sm text-gray-500 dark:text-gray-300">
            Browse reviewers, filter by tags, and manage your own reviewers.
        </p>
    </div>

    @php
        $reviewers = $reviewers ?? collect();
        $reviewerTags = $reviewerTags ?? collect();
        $currentTeacherId = $currentTeacherId ?? null;
        $createOptions = $createOptions ?? collect();
        $h5pReviewers = $h5pReviewers ?? collect();
    @endphp

    <!-- Navigation Tabs -->
    <div
    class="bg-[#F1F5F9] dark:bg-[#2A2A3B]
           rounded-xl mb-4 p-2 mt-4
           w-full md:w-fit
           overflow-x-auto no-scrollbar"
>
    <div class="flex gap-4 w-max">
        <a href="#reviewer-bank"
           data-tab="reviewer-bank"
           class="reviewer-tab font-semibold rounded-xl px-4 md:px-5 lg:px-6
                  py-1.5 md:py-2 whitespace-nowrap transition
                  text-sm md:text-base bg-[#5F2DED] text-white">
            Reviewer Bank
        </a>

        <a href="#your-reviewers"
           data-tab="your-reviewers"
           class="reviewer-tab font-semibold rounded-xl px-4 md:px-5 lg:px-6
                  py-1.5 md:py-2 whitespace-nowrap transition
                  text-sm md:text-base text-[#5E5F60] dark:text-gray-300">
            Your Reviewers
        </a>
    </div>
</div>


    <!-- Reviewer Bank Tab -->
    <div class="mt-6" id="reviewer-bank" data-tab-content="reviewer-bank">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <div class="lg:col-span-3">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-[#373737] dark:text-white">Sort by tag</h3>
                </div>
                <div class="mt-3">
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                            <i class="icofont-search-1 text-sm"></i>
                        </span>
                        <input
                            type="text"
                            id="reviewerBankTagSearch"
                            placeholder="Search tags..."
                            class="w-full pl-10 pr-3 py-2 text-xs rounded-full border border-[#ABB7C2] text-[#5E5F60] dark:text-white bg-transparent focus:outline-none focus:ring-2 focus:ring-[#5F2DED]"
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

            <div class="lg:col-span-9">
                <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
                    <div class="relative w-full sm:w-72 ml-auto">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                            <i class="icofont-search-1 text-sm"></i>
                        </span>
                        <input
                            type="text"
                            id="reviewerBankTitleSearch"
                            placeholder="Search by reviewer title"
                            class="w-full pl-10 pr-3 py-2 text-sm rounded-full border border-[#ABB7C2] text-[#5E5F60] dark:text-white bg-transparent focus:outline-none focus:ring-2 focus:ring-[#5F2DED]"
                        />
                    </div>
                    {{-- Create button moved to bottom-right per request --}}
                </div>

                <div class="border border-[#E2E8F0] dark:border-[#334155] rounded-lg p-6 bg-[#F8FAFC] dark:bg-[#0B1220]">
                    @if($reviewers->isEmpty() && $h5pReviewers->isEmpty())
                        <p class="text-[#5E5F60] dark:text-gray-300 text-center">No reviewers available yet.</p>
                    @else
                        <div class="grid grid-cols-1 gap-4" id="reviewerBankCards">
                            {{-- H5P Reviewers --}}
                            @foreach($h5pReviewers as $h5p)
                                @php
                                    $h5pIsOwned = !empty($currentTeacherId) && (int) $h5p->teacher_id === (int) $currentTeacherId;
                                    $h5pCreatorFirst = trim((string) optional($h5p->creator)->first_name);
                                    $h5pCreatorLast = trim((string) optional($h5p->creator)->last_name);
                                    $h5pCreatorName = trim($h5pCreatorFirst . ' ' . $h5pCreatorLast);
                                    if ($h5pCreatorName === '') {
                                        $h5pCreatorName = trim((string) (optional($h5p->teacher)->name ?? ''));
                                    }
                                    $h5pCreatorName = $h5pCreatorName !== '' ? $h5pCreatorName : null;
                                    $h5pCreatorLabel = $h5pIsOwned ? 'You' : ($h5pCreatorName ?: 'unknown');
                                @endphp
                                <div class="reviewer-bank-card relative border border-[#5F2DED] dark:border-[#A88BFF] rounded-lg p-4 cursor-pointer hover:bg-gray-50 dark:hover:bg-[#2A2A3C] transition"
                                    data-owned="{{ $h5pIsOwned ? '1' : '0' }}"
                                    data-title="{{ strtolower($h5p->title) }}"
                                    data-tag-ids="{{ $h5p->tags->pluck('id')->implode(',') }}"
                                    data-tag-names="{{ $h5p->tags->pluck('name')->map(fn ($name) => strtolower($name))->implode('|') }}"
                                    onclick="window.location='{{ route('teacher.reviewers.h5p.show', $h5p->id) }}'">


                                    <div class="border-l-4 border-[#F2277E]  p-4">
                                        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2">
                                            <p class="text-lg font-semibold text-[#373737] dark:text-white">
                                                {{ $h5p->title }}
                                            </p>

                                            <p class="text-xs text-gray-500 dark:text-gray-300">
                                                H5P
                                            </p>
                                        </div>

                                        <p class="text-xs text-gray-500 dark:text-gray-300">
                                            Created by: {{ $h5pCreatorLabel }}
                                        </p>

                                        <div class="mt-2 flex flex-wrap gap-2 reviewer-card-tag-group">
                                            <span class="reviewer-card-tag-chip text-xs px-2 py-0.5 rounded-full border border-[#ABB7C2] text-[#5E5F60] dark:text-gray-300" data-static-tag="1">
                                                H5P
                                            </span>
                                            @forelse($h5p->tags as $tag)
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

                            {{-- Basic Reviewers --}}
                            @foreach($reviewers as $reviewer)
                                @php
                                    $isOwned = !empty($currentTeacherId) && (int) $reviewer->created_by_teacher_id === (int) $currentTeacherId;
                                    $creatorFirst = trim((string) optional($reviewer->creator)->first_name);
                                    $creatorLast = trim((string) optional($reviewer->creator)->last_name);
                                    $creatorName = trim($creatorFirst . ' ' . $creatorLast);
                                    $creatorName = $creatorName !== '' ? $creatorName : null;
                                    $creatorLabel = $isOwned ? 'You' : ($creatorName ?: 'unknown');
                                    $reviewerEditUrl = route('teacher.reviewer.edit', $reviewer->id);
                                    $reviewerPreviewUrl = route('teacher.reviewer.show', $reviewer->id);
                                    $reviewerCardUrl = $isOwned ? $reviewerEditUrl : $reviewerPreviewUrl;
                                @endphp
                                <div class="reviewer-bank-card relative border border-[#5F2DED] dark:border-[#A88BFF] rounded-lg p-4 cursor-pointer hover:bg-gray-50 dark:hover:bg-[#2A2A3C] transition"
                                     data-owned="{{ $isOwned ? '1' : '0' }}"
                                     data-title="{{ strtolower($reviewer->title) }}"
                                     data-tag-ids="{{ $reviewer->tags->pluck('id')->implode(',') }}"
                                     data-tag-names="{{ $reviewer->tags->pluck('name')->map(fn ($name) => strtolower($name))->implode('|') }}"
                                     onclick="window.location='{{ $reviewerCardUrl }}'">
                                    @if($isOwned)
                                        <form method="POST"
                                              action="{{ route('teacher.reviewer.destroy', $reviewer->id) }}"
                                              class="reviewer-delete-form hidden absolute top-3 right-3">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button"
                                                data-reviewer-delete="true"
                                                data-reviewer-title="{{ $reviewer->title }}"
                                                class="reviewer-delete-btn w-6 h-6 rounded-full bg-red-500 text-white text-xs font-semibold flex items-center justify-center hover:bg-red-600 transition">
                                                x
                                            </button>
                                        </form>
                                    @endif
                                    <div class="border-l-4 border-[#F2277E]  p-4">
                                        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2">
                                            <p class="text-lg font-semibold text-[#373737] dark:text-white">
                                                {{ $reviewer->title }}
                                            </p>
                                            <p class="text-xs text-gray-500 dark:text-gray-300">
                                                Reviewer
                                            </p>
                                        </div>
                                        <p class="text-xs text-gray-500 dark:text-gray-300">
                                            Created by: {{ $creatorLabel }}
                                        </p>
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

    <!-- Your Reviewers Tab -->
    <div class="mt-6 hidden" id="your-reviewers" data-tab-content="your-reviewers">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <div class="lg:col-span-3 ">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-[#373737] dark:text-white">Sort by tag</h3>
                </div>
                <div class="mt-3">
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                            <i class="icofont-search-1 text-sm"></i>
                        </span>
                        <input
                            type="text"
                            id="yourReviewersTagSearch"
                            placeholder="Search tags..."
                            class="w-full pl-10 pr-3  py-2 text-xs rounded-full border border-[#ABB7C2] text-[#5E5F60] dark:text-white bg-transparent focus:outline-none focus:ring-2 focus:ring-[#5F2DED]"
                        />
                    </div>
                </div>

                <div id="yourReviewersTagList" class="mt-4 space-y-2">
                    @forelse ($reviewerTags as $tag)
                        <label
                            class="your-reviewers-tag-option flex items-center gap-3 px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer"
                            data-tag-id="{{ $tag->id }}"
                            data-tag-name="{{ $tag->name }}"
                        >
                            <input type="checkbox"
                                class="your-reviewers-tag-checkbox h-4 w-4 rounded border-gray-300"
                                value="{{ $tag->id }}">
                            <span class="text-sm text-[#5E5F60] dark:text-gray-300">{{ $tag->name }}</span>
                        </label>
                    @empty
                        <p class="text-sm text-gray-500 dark:text-gray-300">No tags available.</p>
                    @endforelse
                </div>

                <p id="yourReviewersTagEmpty" class="mt-3 text-sm text-gray-500 dark:text-gray-300 hidden">
                    No tags match your search.
                </p>

                <div id="yourReviewersTagPagination" class="mt-4 flex items-center justify-between">
                    <button
                        type="button"
                        id="yourReviewersTagPrevPage"
                        class="px-3 py-1.5 border border-[#ABB7C2] rounded-lg text-sm text-[#5E5F60] dark:text-white hover:bg-gray-100 dark:hover:bg-gray-800 disabled:opacity-50 disabled:cursor-not-allowed">
                        Prev
                    </button>
                    <p id="yourReviewersTagPageInfo" class="text-sm text-gray-500 dark:text-gray-300">Page 1 of 1</p>
                    <button
                        type="button"
                        id="yourReviewersTagNextPage"
                        class="px-3 py-1.5 border border-[#ABB7C2] rounded-lg text-sm text-[#5E5F60] dark:text-white hover:bg-gray-100 dark:hover:bg-gray-800 disabled:opacity-50 disabled:cursor-not-allowed">
                        Next
                    </button>
                </div>
            </div>

            <div class="lg:col-span-9">
                <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
                    <div class="relative w-full sm:w-72 ml-auto">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                            <i class="icofont-search-1 text-sm"></i>
                        </span>
                        <input
                            type="text"
                            id="yourReviewersTitleSearch"
                            placeholder="Search by reviewer title"
                            class="w-full pl-10 pr-3 py-2 text-sm rounded-full border border-[#ABB7C2] text-[#5E5F60] dark:text-white bg-transparent focus:outline-none focus:ring-2 focus:ring-[#5F2DED]"
                        />
                    </div>
                </div>

                <div class="border border-[#E2E8F0] dark:border-[#334155] rounded-lg p-6 bg-[#F8FAFC] dark:bg-[#0B1220]">
                    <div id="yourReviewersCards" class="grid grid-cols-1 gap-4">
                        {{-- Your H5P Reviewers --}}
                        @foreach($h5pReviewers as $h5p)
                            @php
                                $h5pIsOwned = !empty($currentTeacherId) && (int) $h5p->teacher_id === (int) $currentTeacherId;
                                $h5pCreatorFirst = trim((string) optional($h5p->creator)->first_name);
                                $h5pCreatorLast = trim((string) optional($h5p->creator)->last_name);
                                $h5pCreatorName = trim($h5pCreatorFirst . ' ' . $h5pCreatorLast);
                                if ($h5pCreatorName === '') {
                                    $h5pCreatorName = trim((string) (optional($h5p->teacher)->name ?? ''));
                                }
                                $h5pCreatorName = $h5pCreatorName !== '' ? $h5pCreatorName : null;
                                $h5pCreatorLabel = $h5pIsOwned ? 'You' : ($h5pCreatorName ?: 'unknown');
                            @endphp
                            <div class="your-reviewers-card relative border border-[#5F2DED] dark:border-[#A88BFF] rounded-lg p-4 cursor-pointer hover:bg-gray-50 dark:hover:bg-[#2A2A3C] transition"
                                data-title="{{ strtolower($h5p->title) }}"
                                data-tag-ids="{{ $h5p->tags->pluck('id')->implode(',') }}"
                                data-tag-names="{{ $h5p->tags->pluck('name')->map(fn ($name) => strtolower($name))->implode('|') }}"
                                onclick="window.location='{{ route('teacher.reviewers.h5p.show', $h5p->id) }}'">

                                                                <form method="POST"
                                                                            action="{{ route('teacher.reviewer.h5p.destroy', $h5p->id) }}"
                                                                            class="your-reviewers-delete-form absolute bottom-1 right-5">
                                                                        @csrf
                                                                        @method('DELETE')
                                                                        <input type="hidden" name="tab" value="your-reviewers">
                                    <button type="button"
                                        data-reviewer-delete="true"
                                        data-reviewer-title="{{ $h5p->title }}"
                                        class="reviewer-delete-btn w-10 h-10 rounded-full bg-red-100 text-red-600 text-xs font-semibold flex items-center justify-center hover:opacity-90 transition">
                                        <i class="icofont-ui-delete text-lg"></i>
                                    </button>
                                </form>

                                <div class="border-l-4 border-[#F2277E]  p-4">
                                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2">
                                        <p class="text-lg font-semibold text-[#373737] dark:text-white">
                                            {{ $h5p->title }}
                                        </p>

                                        <p class="text-xs text-gray-500 dark:text-gray-300">
                                            H5P
                                        </p>
                                    </div>

                                    <p class="text-xs text-gray-500 dark:text-gray-300">
                                        Created by: {{ $h5pCreatorLabel }}
                                    </p>

                                    <div class="mt-2 flex flex-wrap gap-2 reviewer-card-tag-group">
                                        <span class="reviewer-card-tag-chip text-xs px-2 py-0.5 rounded-full border border-[#ABB7C2] text-[#5E5F60] dark:text-gray-300" data-static-tag="1">
                                            H5P
                                        </span>
                                        @forelse($h5p->tags as $tag)
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

                        {{-- Your Regular Reviewers --}}
                        @foreach($reviewers as $reviewer)
                            @php
                                $isOwned = !empty($currentTeacherId) && (int) $reviewer->created_by_teacher_id === (int) $currentTeacherId;
                                if (!$isOwned) continue;
                                $reviewerEditUrl = route('teacher.reviewer.edit', $reviewer->id);
                            @endphp
                            <div class="your-reviewers-card relative border border-[#5F2DED] dark:border-[#A88BFF] rounded-lg p-4 cursor-pointer hover:bg-gray-50 dark:hover:bg-[#2A2A3C] transition"
                                 data-title="{{ strtolower($reviewer->title) }}"
                                 data-tag-ids="{{ $reviewer->tags->pluck('id')->implode(',') }}"
                                 data-tag-names="{{ $reviewer->tags->pluck('name')->map(fn ($name) => strtolower($name))->implode('|') }}"
                                 onclick="window.location='{{ $reviewerEditUrl }}'">
                                                                <form method="POST"
                                                                            action="{{ route('teacher.reviewer.destroy', $reviewer->id) }}"
                                                                            class="your-reviewers-delete-form absolute bottom-1 right-5">
                                                                        @csrf
                                                                        @method('DELETE')
                                                                        <input type="hidden" name="tab" value="your-reviewers">
                                    <button type="button"
                                        data-reviewer-delete="true"
                                        data-reviewer-title="{{ $reviewer->title }}"
                                        class="reviewer-delete-btn w-10 h-10 rounded-full bg-red-100 text-red-600 text-xs font-semibold flex items-center justify-center hover:opacity-90 transition">
                                        <i class="icofont-ui-delete text-lg"></i>
                                    </button>
                                </form>
                                <div class="border-l-4 border-[#F2277E] ] p-4">
                                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2">
                                        <p class="text-lg font-semibold text-[#373737] dark:text-white">
                                            {{ $reviewer->title }}
                                        </p>
                                        <p class="text-xs text-gray-500 dark:text-gray-300">
                                            Reviewer
                                        </p>
                                    </div>
                                    <p class="text-xs text-gray-500 dark:text-gray-300">
                                        Created by: You
                                    </p>
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
                    <p id="yourReviewersEmpty" class="mt-4 text-sm text-gray-500 dark:text-gray-300 hidden">
                        You haven't created any reviewers yet.
                    </p>
                    <div id="yourReviewersPagination" class="mt-4 flex items-center justify-between">
                        <button
                            type="button"
                            id="yourReviewersPrevPage"
                            class="px-3 py-1.5 border border-[#ABB7C2] rounded-lg text-sm text-[#5E5F60] dark:text-white hover:bg-gray-100 dark:hover:bg-gray-800 disabled:opacity-50 disabled:cursor-not-allowed">
                            Prev
                        </button>
                        <p id="yourReviewersPageInfo" class="text-sm text-gray-500 dark:text-gray-300">Page 1 of 1</p>
                        <button
                            type="button"
                            id="yourReviewersNextPage"
                            class="px-3 py-1.5 border border-[#ABB7C2] rounded-lg text-sm text-[#5E5F60] dark:text-white hover:bg-gray-100 dark:hover:bg-gray-800 disabled:opacity-50 disabled:cursor-not-allowed">
                            Next
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div id="reviewerCreateWrapper" class="mt-6 flex justify-end">
            <button
                type="button"
                id="reviewerCreateBtn"
                class="px-4 py-2 rounded-lg bg-[#5F2DED] text-white text-sm font-semibold {{ $createOptions->isEmpty() ? 'opacity-50 cursor-not-allowed' : 'hover:opacity-90' }}"
                {{ $createOptions->isEmpty() ? 'disabled' : '' }}>
                Create Reviewer
            </button>
        </div>
    </div>
</div>
@endsection

<x-lmsmodal />

@section('scripts')
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
        const ownToggleBtn = document.getElementById('reviewerBankOwnToggle');
        const createWrapper = document.getElementById('reviewerCreateWrapper');
        const createBtn = document.getElementById('reviewerCreateBtn');
        const createOptions = @json($createOptions->values());
        const modalSizeClasses = ['max-w-lg', 'max-w-xl', 'max-w-2xl', 'max-w-3xl'];
        const getModalPanel = () => document.querySelector('#lmsmodal > div');

        const applyModalSize = (sizeClass) => {
            const panel = getModalPanel();
            if (!panel) return;
            if (!panel.dataset.defaultSize) {
                const currentSize = modalSizeClasses.find((cls) => panel.classList.contains(cls));
                panel.dataset.defaultSize = currentSize || 'max-w-lg';
            }
            modalSizeClasses.forEach((cls) => panel.classList.remove(cls));
            panel.classList.add(sizeClass);
        };

        const resetModalSize = () => {
            const panel = getModalPanel();
            if (!panel) return;
            const defaultSize = panel.dataset.defaultSize || 'max-w-lg';
            modalSizeClasses.forEach((cls) => panel.classList.remove(cls));
            panel.classList.add(defaultSize);
            delete panel.dataset.defaultSize;
        };

        const reviewerDeleteModal = {
            pendingForm: null,
            open(title) {
                if (!window.LMSModal) return;
                const safeTitle = title
                    ? title.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
                    : 'this reviewer';
                window.LMSModal.open({
                    title: 'Delete Reviewer',
                    content: `
                        <div class="flex flex-col gap-4">
                            <p class="text-sm text-[#373737] dark:text-gray-300 mt-2">
                                Are you sure you want to delete <strong>${safeTitle}</strong>? This action cannot be undone.
                            </p>
                            <div class="mt-6 flex justify-end gap-3">
                                <button type="button" data-close="lmsmodal"
                                    class="px-4 py-2 bg-gray-300 rounded dark:bg-gray-600 dark:text-white">
                                    Cancel
                                </button>
                                <button type="button" data-reviewer-delete-confirm="true"
                                    class="px-4 py-2 bg-red-600 text-white rounded">
                                    Delete
                                </button>
                            </div>
                        </div>
                    `
                });
            },
            confirm() {
                if (this.pendingForm) {
                    this.pendingForm.submit();
                    this.pendingForm = null;
                }
                window.LMSModal?.close();
            }
        };
        const reviewerDeleteButtons = Array.from(document.querySelectorAll('[data-reviewer-delete]'));

        const initializeReviewerTagOverflow = (maxVisibleDynamicTags = 5) => {
            const tagGroups = Array.from(document.querySelectorAll('.reviewer-card-tag-group'));

            tagGroups.forEach((group) => {
                const dynamicChips = Array.from(
                    group.querySelectorAll('.reviewer-card-tag-chip:not([data-static-tag="1"])')
                );

                if (dynamicChips.length <= maxVisibleDynamicTags) {
                    return;
                }

                const hiddenChipCount = dynamicChips.length - maxVisibleDynamicTags;
                const toggleBtn = document.createElement('button');
                toggleBtn.type = 'button';
                toggleBtn.className = 'reviewer-tag-toggle text-xs font-semibold text-[#5F2DED] hover:underline focus:outline-none';
                toggleBtn.setAttribute('aria-expanded', 'false');

                const setCollapsedState = (collapsed) => {
                    dynamicChips.forEach((chip, index) => {
                        const shouldHide = collapsed && index >= maxVisibleDynamicTags;
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
        let showOwnOnly = false;

        reviewerDeleteButtons.forEach((button) => {
            button.addEventListener('click', (event) => {
                event.preventDefault();
                event.stopPropagation();
                reviewerDeleteModal.pendingForm = button.closest('form');
                reviewerDeleteModal.open(button.dataset.reviewerTitle || 'this reviewer');
            });
        });

        const setOwnToggleState = () => {
            if (!ownToggleBtn) return;
            ownToggleBtn.classList.toggle('bg-[#5F2DED]', showOwnOnly);
            ownToggleBtn.classList.toggle('text-white', showOwnOnly);
            ownToggleBtn.classList.toggle('border-[#5F2DED]', showOwnOnly);
            ownToggleBtn.classList.toggle('text-[#5E5F60]', !showOwnOnly);
            ownToggleBtn.classList.toggle('hover:bg-gray-100', !showOwnOnly);
            ownToggleBtn.classList.toggle('dark:hover:bg-gray-800', !showOwnOnly);
        };

        const updateReviewerActions = () => {
            reviewerCards.forEach((card) => {
                const isOwned = card.dataset.owned === '1';
                const deleteForm = card.querySelector('.reviewer-delete-form');
                if (deleteForm) {
                    deleteForm.classList.toggle('hidden', !(showOwnOnly && isOwned));
                }
            });
        };

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
                const isOwned = card.dataset.owned === '1';

                const matchesTitle = !titleTerm || title.includes(titleTerm);
                const matchesTagSearch = !tagSearchTerm || tagNames.includes(tagSearchTerm);
                const matchesTags = selectedTags.size === 0
                    ? true
                    : tagIds.some((id) => selectedTags.has(id));
                const matchesOwnership = showOwnOnly ? isOwned : true;

                return matchesTitle && matchesTags && matchesTagSearch && matchesOwnership;
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

        ownToggleBtn?.addEventListener('click', () => {
            showOwnOnly = !showOwnOnly;
            setOwnToggleState();
            updateReviewerActions();
            applyReviewerFilters(true);
        });

        createBtn?.addEventListener('click', () => {
            if (!Array.isArray(createOptions) || createOptions.length === 0) {
                return;
            }

            // For now: use the FIRST option as the Basic reviewer link
            const basicUrl = createOptions[0]?.url || '#';
            const h5pUrl = "{{ route('teacher.reviewer.h5p') }}";

            if (window.LMSModal) {
                window.LMSModal.open({
                    title: 'Choose Reviewer Type',
                    content: `
                        <div class="space-y-10">

    <!-- ===================== -->
    <!-- Standard Section -->
    <!-- ===================== -->
    <div>
        <h3 class="text-lg font-semibold mb-4 text-black dark:text-white">
            Standard
        </h3>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">

            <!-- Basic (ENABLED) -->
            <a href="${basicUrl}"
               class="cursor-pointer p-4 rounded-lg border border-dashed border-[#5F2DED]
                      flex flex-col items-center justify-center text-center
                      hover:scale-105 transition-all duration-200">

                <div class="rounded-full mb-3 w-10 h-10 bg-[#5F2DED] flex items-center justify-center">
                    <i class="icofont-paper text-white text-lg"></i>
                </div>

                <p class="font-medium text-sm text-black dark:text-white">
                    Basic
                </p>
            </a>

            <!-- Quiz (Disabled) -->
            <div class="p-4 rounded-lg border border-dashed border-gray-300
                        flex flex-col items-center justify-center text-center
                        opacity-50 cursor-not-allowed">

                <div class="rounded-full mb-3 w-10 h-10 bg-gray-400 flex items-center justify-center">
                    <i class="icofont-question-circle text-white text-lg"></i>
                </div>

                <p class="font-medium text-sm text-gray-500 dark:text-gray-400">
                    Quiz
                </p>
                <span class="text-xs mt-1 text-gray-400">Coming Soon</span>
            </div>

            <!-- Timed Exam (Disabled) -->
            <div class="p-4 rounded-lg border border-dashed border-gray-300
                        flex flex-col items-center justify-center text-center
                        opacity-50 cursor-not-allowed">

                <div class="rounded-full mb-3 w-10 h-10 bg-gray-400 flex items-center justify-center">
                    <i class="icofont-clock-time text-white text-lg"></i>
                </div>

                <p class="font-medium text-sm text-gray-500 dark:text-gray-400">
                    Timed Exam
                </p>
                <span class="text-xs mt-1 text-gray-400">Coming Soon</span>
            </div>

        </div>
    </div>

    <!-- ===================== -->
    <!-- H5P Section -->
    <!-- ===================== -->
    <div>
        <h3 class="text-lg font-semibold mb-4 text-black dark:text-white">
            H5P
        </h3>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">

            <!-- Flash Cards -->
            <div class="p-4 rounded-lg border border-dashed border-gray-300
                        flex flex-col items-center justify-center text-center
                        opacity-50 cursor-not-allowed">

                <div class="rounded-full mb-3 w-10 h-10 bg-gray-400 flex items-center justify-center">
                    <i class="icofont-refresh text-white text-lg"></i>
                </div>

                <p class="font-medium text-sm text-gray-500 dark:text-gray-400">
                    Flash Cards
                </p>
                <span class="text-xs mt-1 text-gray-400">Coming Soon</span>
            </div>

            <!-- Interactive Video -->
            <div class="p-4 rounded-lg border border-dashed border-gray-300
                        flex flex-col items-center justify-center text-center
                        opacity-50 cursor-not-allowed">

                <div class="rounded-full mb-3 w-10 h-10 bg-gray-400 flex items-center justify-center">
                    <i class="icofont-ui-play text-white text-lg"></i>
                </div>

                <p class="font-medium text-sm text-gray-500 dark:text-gray-400">
                    Interactive Video
                </p>
                <span class="text-xs mt-1 text-gray-400">Coming Soon</span>
            </div>

            <!-- Matching -->
            <div class="p-4 rounded-lg border border-dashed border-gray-300
                        flex flex-col items-center justify-center text-center
                        opacity-50 cursor-not-allowed">

                <div class="rounded-full mb-3 w-10 h-10 bg-gray-400 flex items-center justify-center">
                    <i class="icofont-random text-white text-lg"></i>
                </div>

                <p class="font-medium text-sm text-gray-500 dark:text-gray-400">
                    Matching
                </p>
                <span class="text-xs mt-1 text-gray-400">Coming Soon</span>
            </div>

            <!-- Course Presentation -->
            <div class="p-4 rounded-lg border border-dashed border-gray-300
                        flex flex-col items-center justify-center text-center
                        opacity-50 cursor-not-allowed">

                <div class="rounded-full mb-3 w-10 h-10 bg-gray-400 flex items-center justify-center">
                    <i class="icofont-presentation text-white text-lg"></i>
                </div>

                <p class="font-medium text-sm text-gray-500 dark:text-gray-400">
                    Course Presentation
                </p>
                <span class="text-xs mt-1 text-gray-400">Coming Soon</span>
            </div>

        </div>
    </div>

</div>
                    `
                });

                applyModalSize('max-w-xl');
            }
        });

        document.addEventListener('click', (event) => {
            const optionBtn = event.target.closest('[data-reviewer-create-option]');
            if (optionBtn) {
                const url = optionBtn.getAttribute('data-url');
                if (url) {
                    window.location = url;
                }
                return;
            }

            const deleteBtn = event.target.closest('[data-reviewer-delete]');
            if (deleteBtn) {
                event.preventDefault();
                event.stopPropagation();
                reviewerDeleteModal.pendingForm = deleteBtn.closest('form');
                reviewerDeleteModal.open(deleteBtn.dataset.reviewerTitle || 'this reviewer');
                return;
            }

            const closeBtn = event.target.closest('[data-close="lmsmodal"]');
            if (closeBtn) {
                resetModalSize();
                return;
            }

            const confirmBtn = event.target.closest('[data-reviewer-delete-confirm]');
            if (confirmBtn) {
                event.preventDefault();
                reviewerDeleteModal.confirm();
            }
        });

        // Tabs: Reviewer Bank / Your Reviewers
        const tabs = Array.from(document.querySelectorAll('[data-tab]'));
        const tabContents = Array.from(document.querySelectorAll('[data-tab-content]'));

        const showTab = (name) => {
            tabs.forEach((tab) => {
                const isActive = tab.dataset.tab === name;
                tab.dataset.active = isActive ? 'true' : 'false';
                tab.classList.toggle('bg-[#5F2DED]', isActive);
                tab.classList.toggle('text-white', isActive);
                tab.classList.toggle('text-[#5E5F60]', !isActive);
            });
            tabContents.forEach((c) => {
                const shouldShow = c.dataset.tabContent === name;
                c.classList.toggle('hidden', !shouldShow);
                c.hidden = !shouldShow;
            });

            // when switching tabs, re-run filters/pagination and action visibility
            applyReviewerFilters(true);
            updateReviewerActions();
        };

        tabs.forEach((tab) => {
            tab.addEventListener('click', (ev) => {
                ev.preventDefault();
                const name = tab.dataset.tab;
                if (name) showTab(name);
            });
        });

        initializeReviewerTagOverflow();

        // initialize active tab
        const initiallyActive = tabs.find((t) => t.dataset.active === 'true')?.dataset.tab || 'reviewer-bank';
        showTab(initiallyActive);

        // Initialize Reviewer Bank
        filterTags();
        applyReviewerFilters(true);
        setOwnToggleState();
        updateReviewerActions();

        // Your Reviewers: same filtering/pagination behaviour as Reviewer Bank
        const yourTagSearchInput = document.getElementById('yourReviewersTagSearch');
        const yourTitleSearchInput = document.getElementById('yourReviewersTitleSearch') || document.getElementById('yourReviewersTitleSearch');
        const yourTagOptions = Array.from(document.querySelectorAll('.your-reviewers-tag-option'));
        const yourTagCheckboxes = Array.from(document.querySelectorAll('.your-reviewers-tag-checkbox'));
        const yourTagEmptyMessage = document.getElementById('yourReviewersTagEmpty');
        const yourTagPagination = document.getElementById('yourReviewersTagPagination');
        const yourTagPrevPageBtn = document.getElementById('yourReviewersTagPrevPage');
        const yourTagNextPageBtn = document.getElementById('yourReviewersTagNextPage');
        const yourTagPageInfo = document.getElementById('yourReviewersTagPageInfo');
        const yourReviewerCards = Array.from(document.querySelectorAll('.your-reviewers-card'));
        const yourReviewerEmptyMessage = document.getElementById('yourReviewersEmpty');
        const yourReviewerPagination = document.getElementById('yourReviewersPagination');
        const yourReviewerPrevPageBtn = document.getElementById('yourReviewersPrevPage');
        const yourReviewerNextPageBtn = document.getElementById('yourReviewersNextPage');
        const yourReviewerPageInfo = document.getElementById('yourReviewersPageInfo');

        const yourTagsPerPage = 6;
        let yourCurrentTagPage = 1;
        let yourFilteredTagOptions = [...yourTagOptions];
        const yourReviewersPerPage = 4;
        let yourCurrentReviewerPage = 1;
        let yourFilteredReviewerCards = [...yourReviewerCards];

        const getYourTagTotalPages = () => {
            if (yourFilteredTagOptions.length === 0) return 0;
            return Math.ceil(yourFilteredTagOptions.length / yourTagsPerPage);
        };

        const applyYourTagPagination = () => {
            if (yourTagOptions.length === 0) {
                if (yourTagEmptyMessage) {
                    yourTagEmptyMessage.classList.add('hidden');
                    yourTagEmptyMessage.hidden = true;
                }
                if (yourTagPagination) {
                    yourTagPagination.classList.add('hidden');
                    yourTagPagination.hidden = true;
                }
                return;
            }

            const totalPages = getYourTagTotalPages();
            if (yourCurrentTagPage > totalPages) {
                yourCurrentTagPage = totalPages || 1;
            }

            const start = (yourCurrentTagPage - 1) * yourTagsPerPage;
            const end = start + yourTagsPerPage;
            const visibleOptions = new Set(yourFilteredTagOptions.slice(start, end));

            yourTagOptions.forEach((option) => {
                const shouldHide = !visibleOptions.has(option);
                option.classList.toggle('hidden', shouldHide);
                option.hidden = shouldHide;
            });

            if (yourTagPageInfo) {
                yourTagPageInfo.textContent = totalPages > 0
                    ? `Page ${yourCurrentTagPage} of ${totalPages}`
                    : 'Page 0 of 0';
            }
            if (yourTagPagination) {
                const shouldHidePagination = totalPages <= 1;
                yourTagPagination.classList.toggle('hidden', shouldHidePagination);
                yourTagPagination.hidden = shouldHidePagination;
            }
            if (yourTagPrevPageBtn) {
                yourTagPrevPageBtn.disabled = yourCurrentTagPage <= 1;
            }
            if (yourTagNextPageBtn) {
                yourTagNextPageBtn.disabled = yourCurrentTagPage >= totalPages;
            }
            if (yourTagEmptyMessage) {
                const hasMatches = yourFilteredTagOptions.length > 0;
                yourTagEmptyMessage.classList.toggle('hidden', hasMatches);
                yourTagEmptyMessage.hidden = hasMatches;
            }
        };

        const filterYourTags = () => {
            const term = (yourTagSearchInput?.value || '').trim().toLowerCase();
            yourFilteredTagOptions = yourTagOptions.filter((option) => {
                const name = (option.dataset.tagName || '').toLowerCase();
                return !term || name.includes(term);
            });
            yourCurrentTagPage = 1;
            applyYourTagPagination();
        };

        const getSelectedYourTagIds = () => {
            return yourTagCheckboxes
                .filter((checkbox) => checkbox.checked)
                .map((checkbox) => checkbox.value);
        };

        const getYourReviewerTotalPages = () => {
            if (yourFilteredReviewerCards.length === 0) return 0;
            return Math.ceil(yourFilteredReviewerCards.length / yourReviewersPerPage);
        };

        const applyYourReviewerPagination = () => {
            if (yourReviewerCards.length === 0) {
                if (yourReviewerPagination) {
                    yourReviewerPagination.classList.add('hidden');
                    yourReviewerPagination.hidden = true;
                }
                return;
            }

            const totalPages = getYourReviewerTotalPages();
            if (yourCurrentReviewerPage > totalPages) {
                yourCurrentReviewerPage = totalPages || 1;
            }

            const start = (yourCurrentReviewerPage - 1) * yourReviewersPerPage;
            const end = start + yourReviewersPerPage;
            const visibleCards = new Set(yourFilteredReviewerCards.slice(start, end));

            yourReviewerCards.forEach((card) => {
                const shouldShow = visibleCards.has(card);
                card.classList.toggle('hidden', !shouldShow);
                card.hidden = !shouldShow;
            });

            if (yourReviewerPageInfo) {
                yourReviewerPageInfo.textContent = totalPages > 0
                    ? `Page ${yourCurrentReviewerPage} of ${totalPages}`
                    : 'Page 0 of 0';
            }
            if (yourReviewerPagination) {
                const shouldHidePagination = totalPages <= 1;
                yourReviewerPagination.classList.toggle('hidden', shouldHidePagination);
                yourReviewerPagination.hidden = shouldHidePagination;
            }
            if (yourReviewerPrevPageBtn) {
                yourReviewerPrevPageBtn.disabled = yourCurrentReviewerPage <= 1;
            }
            if (yourReviewerNextPageBtn) {
                yourReviewerNextPageBtn.disabled = yourCurrentReviewerPage >= totalPages;
            }
            if (yourReviewerEmptyMessage) {
                const hasVisible = yourFilteredReviewerCards.length > 0;
                yourReviewerEmptyMessage.classList.toggle('hidden', hasVisible);
                yourReviewerEmptyMessage.hidden = hasVisible;
            }
        };

        const applyYourReviewerFilters = (resetPage = false) => {
            const titleTerm = (yourTitleSearchInput?.value || '').trim().toLowerCase();
            const tagSearchTerm = (yourTagSearchInput?.value || '').trim().toLowerCase();
            const selectedTags = new Set(getSelectedYourTagIds());

            yourFilteredReviewerCards = yourReviewerCards.filter((card) => {
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
                yourCurrentReviewerPage = 1;
            }
            applyYourReviewerPagination();
        };

        yourTagSearchInput?.addEventListener('input', () => {
            filterYourTags();
            applyYourReviewerFilters(true);
        });

        yourTagPrevPageBtn?.addEventListener('click', () => {
            if (yourCurrentTagPage <= 1) return;
            yourCurrentTagPage -= 1;
            applyYourTagPagination();
        });

        yourTagNextPageBtn?.addEventListener('click', () => {
            const totalPages = getYourTagTotalPages();
            if (yourCurrentTagPage >= totalPages) return;
            yourCurrentTagPage += 1;
            applyYourTagPagination();
        });

        yourTagCheckboxes.forEach((checkbox) => {
            checkbox.addEventListener('change', () => applyYourReviewerFilters(true));
        });

        yourTitleSearchInput?.addEventListener('input', () => applyYourReviewerFilters(true));

        yourReviewerPrevPageBtn?.addEventListener('click', () => {
            if (yourCurrentReviewerPage <= 1) return;
            yourCurrentReviewerPage -= 1;
            applyYourReviewerPagination();
        });

        yourReviewerNextPageBtn?.addEventListener('click', () => {
            const totalPages = getYourReviewerTotalPages();
            if (yourCurrentReviewerPage >= totalPages) return;
            yourCurrentReviewerPage += 1;
            applyYourReviewerPagination();
        });

        // initialize your reviewers
        filterYourTags();
        applyYourReviewerFilters(true);
    })();
</script>
@endsection
