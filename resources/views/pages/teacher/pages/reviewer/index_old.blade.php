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
    <div class="bg-[#F1F5F9] dark:bg-[#2A2A3B] rounded-xl mb-4 p-2 w-full overflow-x-auto no-scrollbar mt-4">
        <div class="flex gap-6 min-w-max">
            <a href="#reviewer-bank"
               data-tab="reviewer-bank"
               class="reviewer-tab font-semibold rounded-xl px-4 md:px-5 lg:px-6 py-1.5 md:py-2 whitespace-nowrap flex-shrink-0 transition text-sm md:text-base
                      bg-[#5F2DED] text-white"
               data-active="true">
                Reviewer Bank
            </a>
            <a href="#your-reviewers"
               data-tab="your-reviewers"
               class="reviewer-tab font-semibold rounded-xl px-4 md:px-5 lg:px-6 py-1.5 md:py-2 whitespace-nowrap flex-shrink-0 transition text-sm md:text-base
                      text-[#5E5F60] dark:text-gray-300 hover:text-[#5F2DED] dark:hover:text-[#A88BFF]">
                Your Reviewers
            </a>
        </div>
    </div>

    <div class="mt-6" id="reviewer-bank" data-tab-content="reviewer-bank">
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
                <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
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
                    <button
                        type="button"
                        id="reviewerCreateBtn"
                        class="px-4 py-2 rounded-lg bg-[#5F2DED] text-white text-sm font-semibold {{ $createOptions->isEmpty() ? 'opacity-50 cursor-not-allowed' : 'hover:opacity-90' }}"
                        {{ $createOptions->isEmpty() ? 'disabled' : '' }}>
                        Create Reviewer
                    </button>
                </div>

                <div class="border border-[#E2E8F0] dark:border-[#334155] rounded-lg p-6 bg-[#F8FAFC] dark:bg-[#0B1220]">
                    @if($reviewers->isEmpty() && $h5pReviewers->isEmpty())
                        <p class="text-[#5E5F60] dark:text-gray-300">No reviewers available yet.</p>
                    @else
                        <div class="grid grid-cols-1 gap-4" id="reviewerBankCards">
                            {{-- ✅ H5P Reviewers --}}
                            @foreach($h5pReviewers as $h5p)
                                <div class="reviewer-bank-card relative border border-[#5F2DED] dark:border-[#A88BFF] rounded-lg p-4 cursor-pointer hover:bg-gray-50 dark:hover:bg-[#2A2A3C] transition"
                                    data-owned="1"
                                    data-title="{{ strtolower($h5p->title) }}"
                                    data-tag-ids="{{ $h5p->tags->pluck('id')->implode(',') }}"
                                    data-tag-names="{{ $h5p->tags->pluck('name')->map(fn ($name) => strtolower($name))->implode('|') }}"
                                    onclick="window.location='{{ route('teacher.reviewers.h5p.show', $h5p->id) }}'">

                                    <form method="POST"
                                          action="{{ route('teacher.reviewer.destroy', $h5p->id) }}"
                                          class="h5p-delete-form hidden absolute top-3 right-3">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button"
                                            data-reviewer-delete="true"
                                            data-reviewer-title="{{ $h5p->title }}"
                                            class="reviewer-delete-btn w-6 h-6 rounded-full bg-red-500 text-white text-xs font-semibold flex items-center justify-center hover:bg-red-600 transition">
                                            x
                                        </button>
                                    </form>

                                    <div class="border-l-4 border-[#5F2DED] dark:border-[#A88BFF] p-4">
                                        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2">
                                            <p class="text-lg font-semibold text-[#373737] dark:text-white">
                                                {{ $h5p->title }}
                                            </p>

                                            <p class="text-xs text-gray-500 dark:text-gray-300">
                                                H5P
                                            </p>
                                        </div>

                                        <p class="text-xs text-gray-500 dark:text-gray-300">
                                            Created by: You
                                        </p>

                                        <div class="mt-2 flex flex-wrap gap-2">
                                            <span class="text-xs px-2 py-0.5 rounded-full border border-[#ABB7C2] text-[#5E5F60] dark:text-gray-300">
                                                H5P
                                            </span>
                                            @forelse($h5p->tags as $tag)
                                                <span class="text-xs px-2 py-0.5 rounded-full border border-[#ABB7C2] text-[#5E5F60] dark:text-gray-300">
                                                    {{ $tag->name }}
                                                </span>
                                            @empty
                                                <span class="text-xs text-gray-400">No tags</span>
                                            @endforelse
                                        </div>
                                    </div>
                                </div>
                            @endforeach

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
                                    <div class="border-l-4 border-[#5F2DED] dark:border-[#A88BFF] p-4">
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
                                        <div class="mt-2 flex flex-wrap gap-2">
                                            @forelse($reviewer->tags as $tag)
                                                <span class="text-xs px-2 py-0.5 rounded-full border border-[#ABB7C2] text-[#5E5F60] dark:text-gray-300">
                                                    {{ $tag->name }}
                                                </span>
                                            @empty
                                                <span class="text-xs text-gray-400">No tags</span>
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
    <div id="reviewerCreateWrapper" class="mt-2 flex justify-end">
        <button
            type="button"
            id="reviewerCreateBtn"
            class="px-4 py-2 rounded-lg bg-[#5F2DED] text-white text-sm font-semibold {{ $createOptions->isEmpty() ? 'opacity-50 cursor-not-allowed' : 'hover:opacity-90' }}"
            {{ $createOptions->isEmpty() ? 'disabled' : '' }}>
            Create Reviewer
        </button>
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
                        <div class="grid grid-cols-2 gap-4">

                            <!-- Basic (current one) -->
                            <a href="${basicUrl}"
                            class="cursor-pointer px-8 py-6 rounded-lg border border-dashed border-[#5F2DED]
                                    flex flex-col items-center justify-center hover:scale-105 transition-all">
                                <div class="rounded-full mb-4 w-12 h-12 bg-[#5F2DED] flex items-center justify-center">
                                    <i class="icofont-paper text-white text-2xl"></i>
                                </div>
                                <p class="font-bold text-xl text-black dark:text-white">Basic</p>
                            </a>

                            <!-- New slot (you will do later) -->
                            <a href="${h5pUrl}"
                            class="cursor-pointer px-8 py-6 rounded-lg border border-dashed border-[#5F2DED]
                                    flex flex-col items-center justify-center hover:scale-105 transition-all ">
                                <div class="rounded-full mb-4 w-12 h-12 bg-[#5F2DED] flex items-center justify-center">
                                    <i class="icofont-ui-play text-white text-2xl"></i>
                                </div>
                                <p class="font-bold text-xl text-black dark:text-white">H5P File</p>
                            </a>

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

        filterTags();
        applyReviewerFilters(true);
        setOwnToggleState();
        updateReviewerActions();
    })();
</script>
@endsection
