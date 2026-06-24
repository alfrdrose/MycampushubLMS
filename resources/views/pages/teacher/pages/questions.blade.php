@extends('pages.teacher.layout')

@section('content')
@php
    $questionBankRoutes = $subject ? [
        'index' => route('teacher.question-bank.subject.index', ['subject_id' => $subject->id]),
        'store' => route('teacher.question-bank.subject.store', ['subject_id' => $subject->id]),
        'quizzes' => route('teacher.question-bank.subject.quizzes', ['subject_id' => $subject->id]),
        'importToQuiz' => route('teacher.question-bank.subject.import-to-quiz', ['subject_id' => $subject->id]),
        'show' => route('teacher.question-bank.subject.show', ['subject_id' => $subject->id, 'item' => '__ITEM__']),
        'update' => route('teacher.question-bank.subject.update', ['subject_id' => $subject->id, 'item' => '__ITEM__']),
        'archive' => route('teacher.question-bank.subject.archive', ['subject_id' => $subject->id, 'item' => '__ITEM__']),
        'restore' => route('teacher.question-bank.subject.restore', ['subject_id' => $subject->id, 'item' => '__ITEM__']),
    ] : [];
    $questionBankDefaultTagRelevance = config('reviewers.default_tag_relevance', 'medium');
    $questionBankRelevanceLevels = config('reviewers.tag_relevance_levels', ['very_high', 'high', 'medium', 'low', 'very_low']);
    $questionBankSubjectOptions = collect($subjectOptions ?? [])->values();
    $questionBankSubjectAssignments = collect($subjectAssignments ?? [])->values();
@endphp

<style>
    [x-cloak] { display: none !important; }

    .qb-shell {
        position: relative;
        overflow: hidden;
        border-radius: 28px;
        border: 1px solid rgba(226, 232, 240, 0.9);
        background:
            radial-gradient(circle at top right, rgba(95, 45, 237, 0.08), transparent 28%),
            linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(248, 250, 252, 0.98));
        box-shadow: 0 30px 80px -42px rgba(15, 23, 42, 0.35);
    }

    .dark .qb-shell {
        border-color: rgba(95, 45, 237, 0.35);
        background:
            radial-gradient(circle at top right, rgba(95, 45, 237, 0.15), transparent 30%),
            linear-gradient(180deg, rgba(30, 30, 45, 0.98), rgba(20, 20, 32, 0.98));
        box-shadow: 0 36px 90px -48px rgba(0, 0, 0, 0.75);
    }

    .qb-pane {
        border-radius: 24px;
        border: 1px solid rgba(226, 232, 240, 0.9);
        background: rgba(255, 255, 255, 0.84);
        backdrop-filter: blur(14px);
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.85);
    }

    .dark .qb-pane {
        border-color: rgba(51, 65, 85, 0.9);
        background: rgba(17, 24, 39, 0.58);
        box-shadow: inset 0 1px 0 rgba(148, 163, 184, 0.06);
    }

    .qb-soft {
        border-radius: 20px;
        border: 1px solid rgba(226, 232, 240, 0.9);
        background: rgba(248, 250, 252, 0.78);
    }

    .dark .qb-soft {
        border-color: rgba(51, 65, 85, 0.9);
        background: rgba(15, 23, 42, 0.52);
    }

    .qb-scroll {
        max-height: calc(100vh - 16rem);
        overflow-y: auto;
        padding-right: 0.25rem;
    }

    .qb-scroll::-webkit-scrollbar {
        width: 8px;
    }

    .qb-scroll::-webkit-scrollbar-thumb {
        border-radius: 999px;
        background: rgba(148, 163, 184, 0.45);
    }

    .qb-step {
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.18em;
        text-transform: uppercase;
        color: rgb(100 116 139);
    }

    .dark .qb-step {
        color: rgb(148 163 184);
    }

    .qb-stat {
        border-radius: 18px;
        border: 1px solid rgba(226, 232, 240, 0.95);
        background: rgba(255, 255, 255, 0.78);
        padding: 0.9rem 1rem;
    }

    .dark .qb-stat {
        border-color: rgba(51, 65, 85, 0.95);
        background: rgba(15, 23, 42, 0.6);
    }

    .qb-editor-card {
        border-radius: 26px;
        border: 1px solid rgba(226, 232, 240, 0.95);
        background:
            linear-gradient(180deg, rgba(255, 255, 255, 0.96), rgba(248, 250, 252, 0.94));
        box-shadow: 0 18px 48px -34px rgba(15, 23, 42, 0.28);
    }

    .dark .qb-editor-card {
        border-color: rgba(51, 65, 85, 0.95);
        background:
            linear-gradient(180deg, rgba(30, 41, 59, 0.85), rgba(15, 23, 42, 0.78));
        box-shadow: 0 24px 60px -40px rgba(0, 0, 0, 0.6);
    }

    .qb-section-separator {
        border-top: 1px solid rgba(226, 232, 240, 0.95);
    }

    .dark .qb-section-separator {
        border-top-color: rgba(51, 65, 85, 0.95);
    }

    .qb-icon-button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 2.25rem;
        height: 2.25rem;
        border-radius: 9999px;
        border: 1px solid rgba(148, 163, 184, 0.45);
        background: rgba(255, 255, 255, 0.9);
        color: rgb(95 45 237);
        box-shadow: 0 8px 24px -18px rgba(15, 23, 42, 0.45);
        transition: 160ms ease;
    }

    .qb-icon-button:hover {
        background: rgba(245, 243, 255, 0.98);
        border-color: rgba(95, 45, 237, 0.4);
    }

    .dark .qb-icon-button {
        border-color: rgba(95, 45, 237, 0.3);
        background: rgba(95, 45, 237, 0.18);
        color: rgb(233 213 255);
    }

    .dark .qb-icon-button:hover {
        background: rgba(95, 45, 237, 0.28);
    }

    .qb-icon-button-danger {
        color: rgb(225 29 72);
    }

    .dark .qb-icon-button-danger {
        color: rgb(253 164 175);
    }

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

    .quiz-gradient-slider {
        -webkit-appearance: none;
        appearance: none;
        width: 100%;
        height: 10px;
        border-radius: 9999px;
        background: linear-gradient(90deg, #3b82f6 0%, #22c55e 100%);
        outline: none;
    }

    .quiz-gradient-slider::-webkit-slider-runnable-track {
        height: 10px;
        border-radius: 9999px;
        background: linear-gradient(90deg, #3b82f6 0%, #22c55e 100%);
    }

    .quiz-gradient-slider::-webkit-slider-thumb {
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

    .quiz-gradient-slider::-moz-range-track {
        height: 10px;
        border-radius: 9999px;
        background: linear-gradient(90deg, #3b82f6 0%, #22c55e 100%);
    }

    .quiz-gradient-slider::-moz-range-thumb {
        height: 16px;
        width: 16px;
        border-radius: 9999px;
        background: #ffffff;
        border: 2px solid #5f2ded;
        cursor: pointer;
    }

    .quiz-scrollbar-visible {
        overflow-y: scroll !important;
        overflow-x: hidden !important;
        overscroll-behavior: contain;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: thin !important;
        scrollbar-color: #64748b #e2e8f0 !important;
        scrollbar-gutter: stable both-edges;
        background-image: linear-gradient(to bottom, #cbd5e1, #cbd5e1);
        background-repeat: no-repeat;
        background-size: 6px calc(100% - 8px);
        background-position: right 2px top 4px;
    }

    .quiz-scrollbar-visible::-webkit-scrollbar {
        width: 12px !important;
        height: 12px !important;
    }

    .quiz-scrollbar-visible::-webkit-scrollbar-track {
        background: #e2e8f0 !important;
        border-radius: 9999px;
    }

    .quiz-scrollbar-visible::-webkit-scrollbar-thumb {
        background: #64748b !important;
        border-radius: 9999px;
        border: 2px solid #e2e8f0;
    }

    .dark .quiz-scrollbar-visible {
        scrollbar-color: #64748b #0f172a !important;
        background-image: linear-gradient(to bottom, #334155, #334155);
    }

    .dark .quiz-scrollbar-visible::-webkit-scrollbar-track {
        background: #0f172a !important;
    }

    .dark .quiz-scrollbar-visible::-webkit-scrollbar-thumb {
        background: #64748b !important;
        border-color: #0f172a;
    }
</style>

<script>
    window.__questionBankConfig = {
        routes: @json($questionBankRoutes),
        hasSubject: @json((bool) $subject),
        pageUrl: @json(route('teacher.questions')),
        subjects: @json($questionBankSubjectOptions),
        subjectAssignments: @json($questionBankSubjectAssignments),
        currentSubjectId: @json($subject?->id),
        csrfToken: @json(csrf_token()),
        tags: @json($tags ?? []),
        tagCategories: @json($tagCategories ?? []),
        defaultTagRelevance: @json($questionBankDefaultTagRelevance),
        relevanceLevels: @json($questionBankRelevanceLevels),
        flashSuccess: @json(session('success')),
    };
</script>

<div x-data="questionBankPage(window.__questionBankConfig)" x-init="init()" class="rounded-2xl border bg-white p-6 shadow-lg dark:border-[#5F2DED] dark:bg-[#1E1E2D] sm:p-8">
    <div class="border-b border-[#D4D4D8] dark:border-[#5F2DED] pb-4">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-[#8B97A7] dark:text-[#7F92A8]">Question Bank</p>
            @if ($subject)
                <button type="button" @click="openSubjectSelector()" class="mt-2 inline-flex items-center gap-2 text-left text-2xl font-bold text-[#5E5F60] transition hover:text-[#5F2DED] dark:text-white dark:hover:text-[#B8A2FF] sm:text-3xl">
                    <span>{{ $subject->subject_name }}</span>
                    <i class="icofont-simple-down text-sm opacity-60"></i>
                </button>
            @else
                <h1 class="mt-2 text-2xl sm:text-3xl font-bold text-[#5E5F60] dark:text-white">
                    Questions
                </h1>
            @endif
        </div>
    </div>

    <div class="mt-4 space-y-3">
        <div x-show="flash.success" x-cloak x-transition class="rounded-lg border border-emerald-200/70 bg-emerald-50/85 px-3 py-2 text-xs text-emerald-700 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-200" x-text="flash.success"></div>
        <div x-show="flash.error" x-cloak x-transition class="rounded-lg border border-rose-200/70 bg-rose-50/85 px-3 py-2 text-xs text-rose-700 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-200" x-text="flash.error"></div>
    </div>

    <div x-show="!hasSubject" x-cloak class="mt-6 rounded-2xl border border-dashed border-slate-300 bg-slate-50/80 px-6 py-10 text-center dark:border-slate-700 dark:bg-[#121A29]">
        <h2 class="text-lg font-semibold text-slate-900 dark:text-white">No assigned subjects available</h2>
        <p class="mt-2 text-sm text-slate-500 dark:text-slate-300">
            Assign at least one current or audit-enabled class subject to access the shared question bank.
        </p>
    </div>

    <div x-show="hasSubject" x-cloak class="mt-6 space-y-6">
        <div x-show="currentView === 'library'" x-cloak class="flex justify-end">
            <label class="relative w-full max-w-[210px]">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400 dark:text-slate-500"><i class="icofont-search-1 text-sm"></i></span>
                <input type="text" x-model.trim="filters.search" @keydown.enter.prevent="applyLibraryFilters()" placeholder="Search" class="h-9 w-full rounded-full border border-slate-200 bg-white pl-9 pr-3 text-sm text-slate-700 outline-none transition focus:border-[#5F2DED] dark:border-slate-700 dark:bg-[#141B2D] dark:text-white dark:focus:border-[#8B7CFF]">
            </label>
        </div>

        <section x-show="currentView === 'library'" x-cloak class="qb-pane relative overflow-hidden p-0">
            <div class="border-b border-slate-200/80 px-4 py-4 dark:border-slate-700/80 sm:px-5">
                <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">
                    <select x-model="filters.type" @change="applyLibraryFilters()" class="h-10 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-700 outline-none transition focus:border-[#5F2DED] dark:border-slate-700 dark:bg-[#141B2D] dark:text-white dark:focus:border-[#8B7CFF] sm:w-[170px]">
                        <option value="">All types</option>
                        <template x-for="option in typeOptions" :key="`table-filter-${option.value}`">
                            <option :value="option.value" x-text="option.label"></option>
                        </template>
                    </select>

                    <select x-model="filters.state" @change="applyLibraryFilters()" class="h-10 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-700 outline-none transition focus:border-[#5F2DED] dark:border-slate-700 dark:bg-[#141B2D] dark:text-white dark:focus:border-[#8B7CFF] sm:w-[155px]">
                        <option value="active">Active</option>
                        <option value="archived">Archived</option>
                        <option value="all">All states</option>
                    </select>

                    <button type="button" @click="openSavedQuestionTagModal()" class="inline-flex h-10 w-full items-center justify-center rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 transition hover:border-[#5F2DED] hover:text-[#5F2DED] dark:border-slate-700 dark:bg-[#141B2D] dark:text-slate-100 dark:hover:border-[#B8A2FF] dark:hover:text-[#B8A2FF] sm:w-auto">
                        <span x-text="filters.tag_ids.length ? `Tags (${filters.tag_ids.length})` : 'Tags'"></span>
                    </button>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
                    <thead class="bg-slate-50/90 dark:bg-[#111827]">
                        <tr>
                            <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400 sm:px-5">Question</th>
                            <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Type</th>
                            <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Tags</th>
                            <th class="px-4 py-3 text-center text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Points</th>
                            <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Updated</th>
                            <th class="px-4 py-3 text-right text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400 sm:px-5">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200/80 bg-white dark:divide-slate-800 dark:bg-[#0F172A]">
                        <template x-if="loadingList">
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-sm text-slate-500 dark:text-slate-400 sm:px-5">Loading saved questions...</td>
                            </tr>
                        </template>
                        <template x-if="!loadingList && items.length === 0">
                            <tr>
                                <td colspan="6" class="px-4 py-10 text-center text-sm text-slate-500 dark:text-slate-400 sm:px-5">No saved questions found for the current filters.</td>
                            </tr>
                        </template>
                        <template x-for="item in items" :key="`question-row-${item.id}`">
                            <tr class="transition" :class="selectedId === item.id ? 'bg-[#F8FBFF] dark:bg-[#13203A]' : 'hover:bg-slate-50 dark:hover:bg-[#111827]'">
                                <td class="px-4 py-4 align-top sm:px-5">
                                    <div class="min-w-0">
                                        <div class="flex items-start gap-1">
                                            <p class="max-w-[340px] truncate text-sm font-semibold text-slate-900 dark:text-white" x-text="item.text || 'Untitled question'"></p>
                                            <span x-show="item.required" x-cloak class="shrink-0 text-sm font-semibold leading-none text-rose-500">*</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-4 align-top text-sm text-slate-600 dark:text-slate-300">
                                    <div class="flex flex-col gap-2">
                                        <span class="font-medium text-slate-800 dark:text-slate-100" x-text="typeLabel(item.type)"></span>
                                        <span x-show="item.is_archived" x-cloak class="inline-flex w-fit rounded-full bg-rose-50 px-2.5 py-1 text-[11px] font-semibold text-rose-600 dark:bg-rose-500/10 dark:text-rose-200">Archived</span>
                                    </div>
                                </td>
                                <td class="px-4 py-4 align-top">
                                    <div class="flex max-w-[240px] min-w-0 items-center gap-2">
                                        <template x-if="!item.tags || item.tags.length === 0">
                                            <span class="text-xs text-slate-400 dark:text-slate-500">No tags</span>
                                        </template>
                                        <template x-if="item.tags && item.tags.length > 0">
                                            <div class="flex min-w-0 items-center gap-2 overflow-hidden">
                                                <template x-for="tag in visibleTableTags(item)" :key="`table-tag-${item.id}-${tag.id}`">
                                                    <span :class="tagToneClass(tag.relevance)" class="tag-pill inline-flex min-w-0 max-w-[100px] items-center gap-1.5 rounded-full border px-3 py-1 text-[11px] font-medium transition">
                                                        <span class="truncate" x-text="tag.name"></span>
                                                    </span>
                                                </template>
                                                <span x-show="tableHiddenTagCount(item) > 0" x-cloak class="inline-flex shrink-0 rounded-full border border-slate-200 px-2.5 py-1 text-[11px] font-semibold text-slate-500 dark:border-slate-700 dark:text-slate-300" x-text="`+${tableHiddenTagCount(item)}`"></span>
                                            </div>
                                        </template>
                                    </div>
                                </td>
                                <td class="px-4 py-4 text-center align-top text-sm font-semibold text-slate-700 dark:text-slate-200" x-text="Number(item.default_points ?? 0)"></td>
                                <td class="px-4 py-4 align-top text-sm text-slate-500 dark:text-slate-400" x-text="formatListDate(item.updated_at || item.created_at)"></td>
                                <td class="px-4 py-4 align-top sm:px-5">
                                    <div class="flex justify-end gap-2" @click.stop>
                                        <button type="button" @click="selectItem(item.id)" class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-slate-200 text-slate-600 transition hover:border-[#5F2DED] hover:text-[#5F2DED] dark:border-slate-700 dark:text-slate-300 dark:hover:border-[#B8A2FF] dark:hover:text-[#B8A2FF]" title="Edit saved question" aria-label="Edit saved question">
                                            <i class="icofont-pencil-alt-2 text-sm"></i>
                                        </button>
                                        <button type="button" x-show="!item.is_archived" x-cloak @click="openArchiveModal(item.id, item.text)" class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-rose-200 text-rose-500 transition hover:bg-rose-50 dark:border-rose-500/30 dark:text-rose-200 dark:hover:bg-rose-500/10" title="Archive saved question" aria-label="Archive saved question">
                                            <i class="icofont-archive text-sm"></i>
                                        </button>
                                        <button type="button" x-show="item.is_archived" x-cloak @click="restoreItemFromModal(item.id)" class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-emerald-200 text-emerald-600 transition hover:bg-emerald-50 dark:border-emerald-500/30 dark:text-emerald-200 dark:hover:bg-emerald-500/10" title="Restore saved question" aria-label="Restore saved question">
                                            <i class="icofont-refresh text-sm"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
            <div class="flex flex-col gap-4 border-t border-slate-200/80 px-4 py-4 dark:border-slate-700/80 sm:flex-row sm:items-center sm:justify-between sm:px-5">
                <div class="flex items-center gap-2">
                    <button type="button" @click="changePage(pagination.currentPage - 1)" :disabled="pagination.currentPage <= 1 || loadingList" class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-slate-200 text-slate-500 transition hover:border-[#5F2DED] hover:text-[#5F2DED] disabled:cursor-not-allowed disabled:opacity-35 dark:border-slate-700 dark:text-slate-300 dark:hover:border-[#B8A2FF] dark:hover:text-[#B8A2FF]" title="Previous page" aria-label="Previous page">
                        <i class="icofont-simple-left text-xs"></i>
                    </button>
                    <span class="min-w-[72px] text-center text-xs font-semibold uppercase tracking-[0.16em] text-slate-400 dark:text-slate-500" x-text="`${pagination.currentPage}/${pagination.lastPage}`"></span>
                    <button type="button" @click="changePage(pagination.currentPage + 1)" :disabled="pagination.currentPage >= pagination.lastPage || loadingList" class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-slate-200 text-slate-500 transition hover:border-[#5F2DED] hover:text-[#5F2DED] disabled:cursor-not-allowed disabled:opacity-35 dark:border-slate-700 dark:text-slate-300 dark:hover:border-[#B8A2FF] dark:hover:text-[#B8A2FF]" title="Next page" aria-label="Next page">
                        <i class="icofont-simple-right text-xs"></i>
                    </button>
                </div>

                <div class="flex justify-end">
                    <button type="button" @click="createNew()" class="inline-flex items-center gap-2 rounded-xl bg-[#5F2DED] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#4E25C7]">
                        <i class="icofont-plus text-sm"></i>
                        <span>New Question</span>
                    </button>
                </div>
            </div>
        </section>

        <section x-show="currentView !== 'library'" x-cloak class="qb-pane p-4 sm:p-5">
                <div class="flex flex-col gap-4 border-b border-slate-200/80 pb-4 dark:border-slate-700/80">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div class="flex items-center gap-3">
                            <button type="button" @click="returnToLibrary()" class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-slate-200 text-slate-500 transition hover:border-[#5F2DED] hover:text-[#5F2DED] dark:border-slate-700 dark:text-slate-300 dark:hover:border-[#B8A2FF] dark:hover:text-[#B8A2FF]" title="Back to question list" aria-label="Back to question list">
                                <i class="icofont-simple-left text-xs"></i>
                            </button>
                            <h2 class="text-lg font-semibold text-slate-900 dark:text-white" x-text="selectedId ? 'Edit Saved Question' : 'Create Saved Question'"></h2>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <button type="button" x-show="selectedId && form.is_archived" x-cloak @click="restoreCurrent()" class="inline-flex items-center gap-2 rounded-xl border border-emerald-200 bg-white px-3 py-2 text-sm font-semibold text-emerald-600 transition hover:bg-emerald-50 dark:border-emerald-500/30 dark:bg-[#141B2D] dark:text-emerald-200 dark:hover:bg-emerald-500/10" title="Restore saved question" aria-label="Restore saved question">
                                <i class="icofont-refresh text-sm"></i>
                                <span>Restore</span>
                            </button>
                        </div>
                    </div>
                </div>

            <div class="mt-4">
                <div class="qb-editor-card overflow-hidden">
                    <div class="space-y-5 p-4 sm:p-5">
                        <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                            <div>
                                <h3 class="text-base font-semibold text-slate-900 dark:text-white">Question Details</h3>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-4 md:max-w-[180px]">
                            <label class="block">
                                <span class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-300">Type</span>
                                <select x-model="form.type" @change="handleTypeChange()" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-700 dark:border-slate-700 dark:bg-[#2A2A3C] dark:text-white">
                                    <template x-for="option in typeOptions" :key="`editor-${option.value}`">
                                        <option :value="option.value" x-text="option.label"></option>
                                    </template>
                                </select>
                                <p x-show="errors.type" x-cloak class="mt-2 text-xs font-medium text-rose-600" x-text="errors.type"></p>
                            </label>
                        </div>

                        <div class="qb-section-separator pt-5">
                            <label class="block">
                                <span class="mb-2 block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-300">Question Text</span>
                                <textarea x-model="form.text" rows="4" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700 dark:border-slate-700 dark:bg-[#2A2A3C] dark:text-white" placeholder="Enter the reusable question prompt"></textarea>
                                <p x-show="errors.text" x-cloak class="mt-2 text-xs font-medium text-rose-600" x-text="errors.text"></p>
                            </label>

                            <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
                                <label class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-300">
                                    <span>Points</span>
                                    <input x-model.number="form.default_points" type="number" min="0" class="h-9 w-20 rounded-lg border border-slate-200 bg-slate-50/80 px-3 text-sm text-slate-700 dark:border-slate-700 dark:bg-[#202031] dark:text-white">
                                </label>
                                <input x-model="form.required" type="checkbox" class="sr-only">
                                <button type="button"
                                    @click="form.required = !form.required"
                                    :class="form.required ? 'bg-purple-600 text-white border border-purple-600 dark:bg-purple-500 dark:border-purple-500' : 'bg-gray-100 text-black border border-gray-300 dark:bg-gray-700 dark:text-white dark:border-gray-600'"
                                    class="px-3 py-2 rounded-full text-xs sm:text-sm font-medium transition-colors duration-200"
                                    :aria-pressed="form.required ? 'true' : 'false'">
                                    Required
                                </button>
                            </div>
                            <p x-show="errors.default_points" x-cloak class="mt-2 text-xs font-medium text-rose-600" x-text="errors.default_points"></p>
                        </div>

                <div x-show="form.type === 'multiple_choice'" x-cloak class="qb-section-separator pt-5">
                    <div class="flex items-center justify-between gap-3">
                        <h3 class="text-sm font-semibold text-slate-800 dark:text-white">Options</h3>
                    </div>
                    <div class="mt-4 space-y-3">
                        <template x-for="(option, index) in form.option" :key="`mc-option-${index}`">
                            <div class="grid grid-cols-[auto_minmax(0,1fr)_auto] items-center gap-3">
                                <input type="checkbox" :checked="form.answer_key.includes(String(index))" @change="toggleMultipleChoiceAnswer(index)" class="h-4 w-4 rounded border-slate-300 text-violet-600">
                                <input x-model="form.option[index]" type="text" :class="form.answer_key.includes(String(index)) ? 'border-green-300 bg-emerald-50/80 text-emerald-800 dark:border-emerald-500/40 dark:bg-emerald-500/10 dark:text-emerald-100' : 'border-slate-200 bg-slate-50/70 text-slate-700 dark:border-slate-700 dark:bg-[#202031] dark:text-white'" class="w-full max-w-[620px] rounded-lg border px-3 py-2 text-sm" :placeholder="`Option ${index + 1}`">
                                <button type="button" @click="removeMultipleChoiceOption(index)" class="text-rose-500 transition hover:text-rose-600 dark:text-rose-300 dark:hover:text-rose-200" title="Remove option" aria-label="Remove option">&times;</button>
                            </div>
                        </template>
                    </div>
                    <div class="mt-4 flex justify-end">
                        <button type="button" @click="addMultipleChoiceOption()" class="qb-icon-button" title="Add option" aria-label="Add option">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                            </svg>
                        </button>
                    </div>
                    <p x-show="errors.option" x-cloak class="mt-3 text-xs font-medium text-rose-600" x-text="errors.option"></p>
                    <p x-show="errors.answer_key" x-cloak class="mt-2 text-xs font-medium text-rose-600" x-text="errors.answer_key"></p>
                </div>

                <div x-show="form.type === 'true_false'" x-cloak class="qb-section-separator pt-5">
                    <h3 class="text-sm font-semibold text-slate-800 dark:text-white">Correct Answer</h3>
                    <div class="mt-3 space-y-2">
                        <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-200">
                            <input type="radio" value="true" x-model="form.answer_key_bool" class="text-violet-600">
                            <span>True</span>
                        </label>
                        <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-200">
                            <input type="radio" value="false" x-model="form.answer_key_bool" class="text-violet-600">
                            <span>False</span>
                        </label>
                    </div>
                    <p x-show="errors.answer_key" x-cloak class="mt-3 text-xs font-medium text-rose-600" x-text="errors.answer_key"></p>
                </div>

                <div x-show="form.type === 'short_answer'" x-cloak class="qb-section-separator pt-5">
                    <h3 class="text-sm font-semibold text-slate-800 dark:text-white">Answer Key</h3>
                    <input x-model="form.answer_key_text" type="text" class="mt-3 h-10 w-full max-w-sm rounded-lg border border-slate-200 bg-slate-50/70 px-3 text-sm text-slate-700 dark:border-slate-700 dark:bg-[#202031] dark:text-white">
                </div>

                <div x-show="form.type === 'numerical'" x-cloak class="qb-section-separator pt-5">
                    <h3 class="text-sm font-semibold text-slate-800 dark:text-white">Numerical Answer</h3>
                    <input x-model="form.answer_key_num" type="number" step="any" class="mt-3 h-10 w-full max-w-sm rounded-lg border border-slate-200 bg-slate-50/70 px-3 text-sm text-slate-700 dark:border-slate-700 dark:bg-[#202031] dark:text-white">
                    <p x-show="errors.answer_key" x-cloak class="mt-3 text-xs font-medium text-rose-600" x-text="errors.answer_key"></p>
                </div>

                <div x-show="form.type === 'matching'" x-cloak class="qb-section-separator space-y-4 pt-5">
                    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                        <div>
                            <div class="flex items-center justify-between gap-3">
                                <h3 class="text-sm font-semibold text-slate-800 dark:text-white">Left Items</h3>
                            </div>
                            <div class="mt-3 space-y-2">
                                <template x-for="(value, index) in form.match_left" :key="`left-${index}`">
                                    <div class="grid grid-cols-[minmax(0,1fr)_auto] gap-2">
                                        <input x-model="form.match_left[index]" type="text" class="w-full rounded-lg border border-slate-200 bg-slate-50/70 px-3 py-2 text-sm text-slate-700 dark:border-slate-700 dark:bg-[#202031] dark:text-white" :placeholder="`Left item ${index + 1}`">
                                        <button type="button" @click="removeMatchLeft(index)" class="text-rose-500 transition hover:text-rose-600 dark:text-rose-300 dark:hover:text-rose-200" title="Remove left item" aria-label="Remove left item">&times;</button>
                                    </div>
                                </template>
                            </div>
                            <div class="mt-4 flex justify-end">
                                <button type="button" @click="addMatchLeft()" class="qb-icon-button" title="Add left item" aria-label="Add left item">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                    </svg>
                                </button>
                            </div>
                            <p x-show="errors.match_left" x-cloak class="mt-3 text-xs font-medium text-rose-600" x-text="errors.match_left"></p>
                        </div>

                        <div>
                            <div class="flex items-center justify-between gap-3">
                                <h3 class="text-sm font-semibold text-slate-800 dark:text-white">Right Items</h3>
                            </div>
                            <div class="mt-3 space-y-2">
                                <template x-for="(value, index) in form.match_right" :key="`right-${index}`">
                                    <div class="grid grid-cols-[minmax(0,1fr)_auto] gap-2">
                                        <input x-model="form.match_right[index]" type="text" class="w-full rounded-lg border border-slate-200 bg-slate-50/70 px-3 py-2 text-sm text-slate-700 dark:border-slate-700 dark:bg-[#202031] dark:text-white" :placeholder="`Right item ${index + 1}`">
                                        <button type="button" @click="removeMatchRight(index)" class="text-rose-500 transition hover:text-rose-600 dark:text-rose-300 dark:hover:text-rose-200" title="Remove right item" aria-label="Remove right item">&times;</button>
                                    </div>
                                </template>
                            </div>
                            <div class="mt-4 flex justify-end">
                                <button type="button" @click="addMatchRight()" class="qb-icon-button" title="Add right item" aria-label="Add right item">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                    </svg>
                                </button>
                            </div>
                            <p x-show="errors.match_right" x-cloak class="mt-3 text-xs font-medium text-rose-600" x-text="errors.match_right"></p>
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button type="button"
                            @click="form.show_matching_answer_key = !form.show_matching_answer_key"
                            :class="form.show_matching_answer_key ? 'bg-purple-600 text-white border border-purple-600 dark:bg-purple-500 dark:border-purple-500' : 'bg-gray-100 text-black border border-gray-300 dark:bg-gray-700 dark:text-white dark:border-gray-600'"
                            class="px-3 py-2 rounded-full text-xs sm:text-sm font-medium transition-colors duration-200">
                            Answer Key
                        </button>
                    </div>

                    <div x-show="form.show_matching_answer_key" x-cloak class="overflow-x-auto rounded-2xl border border-slate-200 dark:border-slate-700">
                        <table class="min-w-full text-sm border-collapse">
                            <thead>
                                <tr>
                                    <th class="border-b border-slate-200 p-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:border-slate-700 dark:text-slate-300"></th>
                                    <template x-for="(colText, colIndex) in cleanedMatchRight()" :key="`match-hdr-${colIndex}`">
                                        <th class="border-b border-slate-200 p-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:border-slate-700 dark:text-slate-300">
                                            <span x-text="colText"></span>
                                        </th>
                                    </template>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(rowText, rowIndex) in cleanedMatchLeft()" :key="`match-row-${rowIndex}`">
                                    <tr>
                                        <td class="border-b border-slate-200 p-3 text-sm font-medium text-slate-700 dark:border-slate-700 dark:text-white">
                                            <span x-text="rowText"></span>
                                        </td>
                                        <template x-for="(colText, colIndex) in cleanedMatchRight()" :key="`match-cell-${rowIndex}-${colIndex}`">
                                            <td class="border-b border-slate-200 p-3 dark:border-slate-700">
                                                <button
                                                    type="button"
                                                    @click="toggleFormMatchSelection(rowIndex, colIndex)"
                                                    :class="isFormMatchSelected(rowIndex, colIndex) ? 'bg-green-300 text-green-900 font-semibold shadow-sm ring-1 ring-green-500 dark:bg-green-900/50 dark:text-green-100 dark:ring-green-500' : ''"
                                                    class="flex h-7 w-7 items-center justify-center rounded-full focus:outline-none focus:ring-2 focus:ring-purple-400">
                                                    <input
                                                        type="radio"
                                                        tabindex="-1"
                                                        :checked="isFormMatchSelected(rowIndex, colIndex)"
                                                        class="h-4 w-4 rounded-full border-2 border-green-500 text-green-700 bg-white focus:ring-2 focus:ring-green-500 pointer-events-none"
                                                    >
                                                </button>
                                            </td>
                                        </template>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                    <p x-show="errors.answer_key" x-cloak class="text-xs font-medium text-rose-600" x-text="errors.answer_key"></p>
                </div>

                        <div class="qb-section-separator pt-5">
                            <div class="flex items-center justify-between gap-3">
                                <button type="button" @click="openTagModal()" class="rounded-full border border-slate-200 px-3 py-1.5 text-sm font-semibold text-slate-800 transition hover:border-[#5F2DED] hover:text-[#5F2DED] dark:border-slate-700 dark:text-white dark:hover:border-[#B8A2FF] dark:hover:text-[#B8A2FF]">
                                    <span x-text="form.tags.length ? `Tags (${form.tags.length})` : 'Tags'"></span>
                                </button>
                            </div>

                            <div class="mt-4 flex flex-wrap gap-2">
                                <template x-if="form.tags.length === 0">
                                    <p class="text-xs text-slate-500 dark:text-slate-400">No tags selected yet.</p>
                                </template>
                                <template x-for="tag in form.tags" :key="`selected-chip-${tag.id}`">
                                    <button type="button" @click="openTagModal()" :class="tagToneClass(tag.relevance)" class="tag-pill inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-[11px] font-medium transition">
                                        <span class="truncate max-w-[180px]" x-text="tag.name"></span>
                                        <span @click.stop="removeTag(tag.id)" class="tag-pill-remove text-xs leading-none opacity-70 hover:opacity-100 hover:text-red-500" :title="`Remove ${tag.name}`" :aria-label="`Remove ${tag.name}`">&times;</span>
                                    </button>
                                </template>
                            </div>
                            <p x-show="errors.tag_ids || errors.tag_relevance" x-cloak class="mt-3 text-xs font-medium text-rose-600" x-text="errors.tag_ids || errors.tag_relevance"></p>
                        </div>
                    </div>

                    <div class="flex flex-col gap-3 border-t border-slate-200/80 px-4 py-4 dark:border-slate-700/80 sm:flex-row sm:items-center sm:justify-between sm:px-5">
                        <div class="text-xs text-slate-500 dark:text-slate-400">
                            <span x-show="selectedId" x-cloak x-text="form.is_archived ? 'This item is archived.' : 'Editing the selected bank item.'"></span>
                        </div>
                        <button type="button" @click="saveCurrent()" :disabled="saving" class="rounded-xl bg-[#5F2DED] px-5 py-2.5 text-sm font-semibold text-white disabled:opacity-60">
                            <span x-show="!saving" x-cloak x-text="selectedId ? 'Save Changes' : 'Save Question'"></span>
                            <span x-show="saving" x-cloak>Saving...</span>
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <div x-cloak x-show="subjectSelectorOpen" class="fixed inset-0 z-[120000] flex items-center justify-center p-4" aria-hidden="false">
            <div x-show="subjectSelectorOpen" x-transition.opacity class="absolute inset-0 bg-[#0F172A]/45 backdrop-blur-[2px]" @click="closeSubjectSelector()"></div>
            <aside x-show="subjectSelectorOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-y-3 opacity-0" x-transition:enter-end="translate-y-0 opacity-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-y-0 opacity-100" x-transition:leave-end="translate-y-3 opacity-0" class="relative z-10 flex h-[min(74vh,620px)] min-h-0 w-full max-w-[760px] flex-col rounded-[22px] border border-[#E6EDF4] bg-white p-5 shadow-2xl dark:border-white/10 dark:bg-[#0F172A]">
                <div class="flex items-start justify-between gap-4 border-b border-[#E6EDF4] pb-4 dark:border-white/10">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-[#8B97A7] dark:text-[#7F92A8]">Question Bank</p>
                        <h3 class="mt-2 text-xl font-bold text-[#394554] dark:text-white">Select Subject</h3>
                    </div>
                    <button type="button" @click="closeSubjectSelector()" class="px-2 text-xl leading-none text-gray-500 transition hover:text-gray-700 dark:text-gray-300 dark:hover:text-white" aria-label="Close subject selector">&times;</button>
                </div>

                <div class="mt-4 flex items-end gap-2 overflow-x-auto [scrollbar-width:none] [-ms-overflow-style:none] [&::-webkit-scrollbar]:hidden">
                    <template x-for="grade in availableAssignmentGrades()" :key="`subject-grade-${grade.value}`">
                        <button type="button" @click="selectSubjectGrade(grade.value)" class="relative -mb-px inline-flex min-w-[110px] items-center justify-between gap-2 border px-3 py-2 text-sm font-semibold transition" :class="String(subjectSelectorGrade) === String(grade.value) ? 'z-10 rounded-t-2xl border-[#D7E1EA] border-b-[#F9FBFD] bg-white text-primaryColor dark:border-white/10 dark:border-b-[#121A29] dark:bg-[#0F172A]' : 'rounded-t-2xl border-transparent bg-[#EEF2F6] text-[#708096] hover:bg-white hover:text-primaryColor dark:bg-[#172234] dark:text-[#94A3B8]'">
                            <span x-text="grade.label"></span>
                            <span class="text-[11px] opacity-70" x-text="grade.sections"></span>
                        </button>
                    </template>
                </div>

                <div class="min-h-0 flex-1 overflow-hidden rounded-[20px] border border-[#E6EDF4] bg-[#F9FBFD] p-3 dark:border-white/10 dark:bg-[#121A29]" :class="availableAssignmentGrades().length ? '-mt-px rounded-tl-none' : ''">
                    <div class="flex flex-col gap-3 border-b border-[#E6EDF4] pb-3 dark:border-white/10 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex flex-wrap gap-2">
                            <button type="button" @click="selectSubjectSection('')" class="rounded-full border px-3 py-1.5 text-sm font-medium transition" :class="subjectSelectorSectionId === '' ? 'border-primaryColor bg-[#EEF4FF] text-primaryColor dark:border-[#4B7BFF] dark:bg-[#15253D] dark:text-[#8EB6FF]' : 'border-[#D7E1EA] bg-white text-[#394554] hover:border-primaryColor hover:text-primaryColor dark:border-white/10 dark:bg-[#0F172A] dark:text-white'">
                                All Sections
                            </button>

                            <template x-for="sectionOption in availableAssignmentSections()" :key="`subject-section-${sectionOption.id}`">
                                <button type="button" @click="selectSubjectSection(sectionOption.id)" class="rounded-full border px-3 py-1.5 text-sm font-medium transition" :class="String(subjectSelectorSectionId) === String(sectionOption.id) ? 'border-primaryColor bg-[#EEF4FF] text-primaryColor dark:border-[#4B7BFF] dark:bg-[#15253D] dark:text-[#8EB6FF]' : 'border-[#D7E1EA] bg-white text-[#394554] hover:border-primaryColor hover:text-primaryColor dark:border-white/10 dark:bg-[#0F172A] dark:text-white'">
                                    <span class="truncate" x-text="sectionOption.name"></span>
                                </button>
                            </template>
                        </div>

                        <div class="relative w-full sm:max-w-[220px]">
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-[#8B97A7] dark:text-[#7F92A8]"><i class="icofont-search-1 text-xs"></i></span>
                            <input type="text" x-model.trim="subjectSelectorSearch" placeholder="Search subject" class="h-10 w-full rounded-lg border border-[#D7E1EA] bg-white pl-8 pr-3 text-sm text-[#394554] outline-none transition focus:border-primaryColor dark:border-white/10 dark:bg-[#0F172A] dark:text-white">
                        </div>
                    </div>

                    <div class="quiz-scrollbar-visible mt-3 h-full max-h-[360px] min-h-0 space-y-2 overflow-y-auto pr-1">
                        <template x-if="filteredAssignmentSubjects().length === 0">
                            <div class="rounded-xl border border-dashed border-[#D7E1EA] px-4 py-6 text-center text-sm text-[#8B97A7] dark:border-white/10 dark:text-[#7F92A8]">No subjects found for the selected grade and section.</div>
                        </template>

                        <template x-for="subjectOption in filteredAssignmentSubjects()" :key="`subject-result-${subjectOption.id}`">
                            <button type="button" @click="applySubjectSelection(subjectOption.id)" class="flex w-full items-center justify-between gap-3 rounded-xl border px-4 py-3 text-left transition" :class="Number(selectedSubjectId) === Number(subjectOption.id) ? 'border-primaryColor bg-[#F8FBFF] dark:border-[#4B7BFF] dark:bg-[#13203A]' : 'border-transparent bg-white hover:border-primaryColor hover:bg-[#F8FBFF] dark:bg-[#0F172A] dark:hover:bg-[#13203A]'">
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-semibold text-[#394554] dark:text-white" x-text="subjectOption.label"></p>
                                    <p class="mt-1 truncate text-xs text-[#617184] dark:text-[#96A6BB]" x-text="subjectOption.meta"></p>
                                </div>
                                <span x-show="Number(selectedSubjectId) === Number(subjectOption.id)" x-cloak class="rounded-full bg-[#EAF4EA] px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-[#1F8A46] dark:bg-[#12331E] dark:text-[#82D7A0]">Current</span>
                            </button>
                        </template>
                    </div>
                </div>
            </aside>
        </div>

        <div x-cloak x-show="archiveModalOpen" class="fixed inset-0 z-[120000] flex items-center justify-center p-4" aria-hidden="false">
            <div x-show="archiveModalOpen" x-transition.opacity class="absolute inset-0 bg-[#0F172A]/45 backdrop-blur-[2px]" @click="closeArchiveModal()"></div>
            <aside x-show="archiveModalOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-y-3 opacity-0" x-transition:enter-end="translate-y-0 opacity-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-y-0 opacity-100" x-transition:leave-end="translate-y-3 opacity-0" class="relative z-10 w-full max-w-md rounded-[22px] border border-[#E6EDF4] bg-white p-6 shadow-2xl dark:border-white/10 dark:bg-[#0F172A]">
                <h3 class="text-xl font-bold text-[#394554] dark:text-white">Archive Saved Question?</h3>
                <p class="mt-3 text-sm text-[#617184] dark:text-[#96A6BB]">
                    Are you sure you want to archive this saved question?
                </p>
                <p x-show="pendingArchiveLabel" x-cloak class="mt-3 max-h-40 overflow-y-auto rounded-2xl border border-[#E6EDF4] bg-[#F9FBFD] px-4 py-3 text-sm font-medium text-[#394554] break-words whitespace-pre-wrap dark:border-white/10 dark:bg-[#121A29] dark:text-white" x-text="pendingArchiveLabel"></p>

                <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:justify-end">
                    <button type="button" @click="closeArchiveModal()" class="rounded-xl border border-[#D7E1EA] px-4 py-2.5 text-sm font-semibold text-[#394554] transition hover:bg-[#EEF4FF] dark:border-white/10 dark:text-white dark:hover:bg-[#1B2540]">
                        Cancel
                    </button>
                    <button type="button" @click="confirmArchive()" class="rounded-xl bg-rose-500 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-rose-600">
                        Archive
                    </button>
                </div>
            </aside>
        </div>

        <div x-cloak x-show="savedQuestionTagModalOpen" class="fixed inset-0 z-[120001] flex items-center justify-center p-4" aria-hidden="false">
            <div x-show="savedQuestionTagModalOpen" x-transition.opacity class="absolute inset-0 bg-[#0F172A]/45 backdrop-blur-[2px]" @click="closeSavedQuestionTagModal()"></div>
            <aside x-show="savedQuestionTagModalOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-y-3 opacity-0" x-transition:enter-end="translate-y-0 opacity-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-y-0 opacity-100" x-transition:leave-end="translate-y-3 opacity-0" class="relative z-10 flex h-[min(68vh,520px)] min-h-0 w-full max-w-[460px] flex-col rounded-[22px] border border-[#E6EDF4] bg-white p-5 shadow-2xl dark:border-white/10 dark:bg-[#0F172A]">
                <div class="flex items-start justify-between gap-4 border-b border-[#E6EDF4] pb-4 dark:border-white/10">
                    <div>
                        <h3 class="text-xl font-bold text-[#394554] dark:text-white">Tags</h3>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="relative w-full sm:w-40">
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-[#8B97A7] dark:text-[#7F92A8]"><i class="icofont-search-1 text-[11px]"></i></span>
                            <input type="text" x-model.trim="savedQuestionTagFilterSearch" placeholder="Search" class="h-9 w-full rounded-full border border-[#D7E1EA] bg-transparent pl-8 pr-3 text-sm text-[#394554] outline-none transition focus:border-primaryColor dark:border-white/10 dark:text-white">
                        </div>
                        <button type="button" @click="closeSavedQuestionTagModal()" class="px-2 text-xl leading-none text-gray-500 transition hover:text-gray-700 dark:text-gray-300 dark:hover:text-white" aria-label="Close saved question tag filters">&times;</button>
                    </div>
                </div>

                <div class="mt-4 min-h-0 flex-1 overflow-hidden rounded-2xl border border-[#E6EDF4] bg-[#F9FBFD] p-2 dark:border-white/10 dark:bg-[#121A29]">
                    <div class="quiz-scrollbar-visible h-full max-h-[340px] min-h-0 space-y-1 overflow-y-auto pr-1">
                        <template x-if="filteredSavedQuestionFilterTags().length === 0">
                            <div class="rounded-xl border border-dashed border-[#D7E1EA] px-4 py-6 text-center text-sm text-[#8B97A7] dark:border-white/10 dark:text-[#7F92A8]">No tags match the current search.</div>
                        </template>

                        <template x-for="tag in filteredSavedQuestionFilterTags()" :key="`saved-filter-tag-${tag.id}`">
                            <label class="flex cursor-pointer items-start gap-3 rounded-xl bg-white px-4 py-3 transition hover:bg-[#FDFEFF] dark:bg-[#0F172A] dark:hover:bg-[#13203A]">
                                <input type="checkbox" class="mt-0.5 h-4 w-4 rounded border-[#D7E1EA] text-primaryColor focus:ring-primaryColor dark:border-white/10" :checked="hasSavedQuestionDraftTag(tag.id)" @change="toggleSavedQuestionFilterTag(tag.id)">
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-medium text-[#394554] dark:text-white" x-text="tag.name"></p>
                                    <p x-show="tag.category" x-cloak class="mt-1 truncate text-[11px] text-[#8B97A7] dark:text-[#7F92A8]" x-text="tag.category"></p>
                                </div>
                            </label>
                        </template>
                    </div>
                </div>

                <div class="mt-4 flex items-center justify-between gap-3 border-t border-[#E6EDF4] pt-4 dark:border-white/10">
                    <p class="text-xs text-[#8B97A7] dark:text-[#7F92A8]" x-text="savedQuestionTagDraftIds.length ? `${savedQuestionTagDraftIds.length} tag filter(s) selected` : 'No tag filters selected'"></p>
                    <div class="flex items-center gap-2">
                        <button type="button" @click="clearSavedQuestionTagFilters()" :disabled="!savedQuestionTagDraftIds.length" class="rounded-xl border border-[#D7E1EA] px-3 py-2 text-sm font-semibold text-[#394554] transition hover:bg-[#EEF4FF] disabled:cursor-not-allowed disabled:opacity-50 dark:border-white/10 dark:text-white dark:hover:bg-[#1B2540]">Clear</button>
                        <button type="button" @click="applySavedQuestionTagFilters()" class="rounded-xl bg-[#5F2DED] px-4 py-2 text-sm font-semibold text-white">Done</button>
                    </div>
                </div>
            </aside>
        </div>

        <div x-show="tagModalOpen" @click.self="closeTagModal()" x-cloak style="display: none; z-index: 120000;" class="fixed inset-0 flex items-center justify-center bg-black/50 p-4">
            <div class="w-full max-w-2xl rounded-lg bg-white p-6 shadow-xl dark:bg-[#1E1E2D]">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <h3 class="text-xl font-bold text-gray-800 dark:text-white">Select Tags</h3>
                    <div class="flex w-full items-center gap-2 sm:w-auto sm:justify-end">
                        <div class="relative w-full sm:w-52">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                                <i class="icofont-search-1 text-sm"></i>
                            </span>
                            <input
                                type="text"
                                x-model="tagFilters.search"
                                @input="resetTagModalPages()"
                                :placeholder="tagModalRelevanceMode ? 'Search selected tags...' : (tagModalCategoryMode ? 'Search categories...' : 'Search tags...')"
                                class="w-full rounded-full border border-[#ABB7C2] bg-transparent py-2 pl-10 pr-3 text-sm text-[#5E5F60] focus:outline-none focus:ring-2 focus:ring-[#5F2DED] dark:text-white"
                            />
                        </div>
                        <button type="button" @click="closeTagModal()" class="text-gray-500 hover:text-gray-700 dark:text-gray-300">
                            <i class="icofont-close text-xl"></i>
                        </button>
                    </div>
                </div>

                <div class="mt-4 flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <span class="text-sm text-gray-500 dark:text-gray-300">Category:</span>
                        <button type="button"
                            @click="toggleTagCategoryMode()"
                            class="inline-flex items-center gap-2 rounded-md border border-[#ABB7C2] bg-gray-50 px-3 py-1.5 text-sm font-medium text-[#5E5F60] transition-colors hover:border-[#5F2DED] hover:bg-[#F6F2FF] dark:bg-[#2A2A3C] dark:text-white dark:hover:bg-[#34344A]">
                            <span x-text="selectedTagCategoryLabel()"></span>
                            <i class="icofont-rounded-down text-sm"></i>
                        </button>

                        <button type="button"
                            @click="toggleTagRelevanceMode()"
                            class="inline-flex items-center gap-2 rounded-md border border-[#ABB7C2] bg-gray-50 px-3 py-1.5 text-sm font-medium text-[#5E5F60] transition-colors hover:border-[#5F2DED] hover:bg-[#F6F2FF] dark:bg-[#2A2A3C] dark:text-white dark:hover:bg-[#34344A]">
                            <span>Relevance</span>
                            <i class="icofont-rounded-down text-sm"></i>
                        </button>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button"
                            @click="clearAllTags()"
                            :disabled="!form.tags.length"
                            class="inline-flex items-center gap-2 rounded-full border border-red-300 px-3 py-1.5 text-sm font-medium text-red-500 transition hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-50">
                            <i class="icofont-close text-sm"></i>
                            <span>Clear all</span>
                        </button>
                        <button type="button"
                            x-show="tagModalCategoryMode || tagModalRelevanceMode"
                            @click="handleTagModalBack()"
                            class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-[#ABB7C2] text-[#5F2DED] hover:bg-[#F6F2FF] dark:border-[#5F2DED] dark:hover:bg-[#2A2A3C]"
                            title="Back to tags"
                            aria-label="Back to tags">
                            <i class="icofont-simple-left text-sm"></i>
                        </button>
                    </div>
                </div>

                <div x-show="!tagModalCategoryMode && !tagModalRelevanceMode">
                    <div class="quiz-scrollbar-visible mt-4 max-h-72 rounded-lg border border-gray-200 divide-y divide-gray-100 dark:border-gray-700 dark:divide-gray-800">
                        <template x-if="!visibleTagOptions().length">
                            <div class="px-4 py-6 text-sm text-gray-500 dark:text-gray-300">No tags match your filters.</div>
                        </template>

                        <template x-for="tag in visibleTagOptions()" :key="`tag-option-${tag.id}`">
                            <label class="flex cursor-pointer items-center gap-3 px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-800">
                                <input type="checkbox"
                                    class="h-4 w-4 rounded border-gray-300"
                                    :checked="hasTag(tag.id)"
                                    @change="toggleTag(tag)">
                                <div class="flex min-w-0 flex-1 items-center justify-between gap-3">
                                    <div class="flex min-w-0 flex-col">
                                        <span class="text-sm font-medium text-[#5E5F60] dark:text-white" x-text="tag.name"></span>
                                        <span class="text-xs text-gray-500 dark:text-gray-300" x-text="tag.category || 'Uncategorized'"></span>
                                    </div>
                                </div>
                            </label>
                        </template>
                    </div>

                    <div class="mt-4 flex items-center justify-between" x-show="tagOptionTotalPages() > 1">
                        <button type="button"
                            @click="prevTagOptionPage()"
                            :disabled="tagFilters.page <= 1"
                            class="rounded-lg border border-[#ABB7C2] px-3 py-1.5 text-sm text-[#5E5F60] hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-50 dark:text-white dark:hover:bg-gray-800">
                            Prev
                        </button>
                        <p class="text-sm text-gray-500 dark:text-gray-300" x-text="`Page ${tagFilters.page} of ${tagOptionTotalPages()}`"></p>
                        <button type="button"
                            @click="nextTagOptionPage()"
                            :disabled="tagFilters.page >= tagOptionTotalPages()"
                            class="rounded-lg border border-[#ABB7C2] px-3 py-1.5 text-sm text-[#5E5F60] hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-50 dark:text-white dark:hover:bg-gray-800">
                            Next
                        </button>
                    </div>
                </div>

                <div x-show="tagModalRelevanceMode">
                    <div class="mt-4 rounded-lg border border-[#E2E8F0] bg-[#F8FAFC] p-3 dark:border-[#334155] dark:bg-[#0B1220]">
                        <div class="space-y-2">
                            <template x-if="!visibleTagRelevanceEntries().length">
                                <p class="text-xs text-gray-500 dark:text-gray-300">Select at least one tag to configure relevance.</p>
                            </template>

                            <template x-for="tag in visibleTagRelevanceEntries()" :key="`tag-rel-${tag.id}`">
                                <div class="border border-[#94A3B8] p-3 transition-colors hover:bg-[#EFF6FF] focus-within:bg-[#DBEAFE] dark:border-[#475569] dark:hover:bg-[#12243D] dark:focus-within:bg-[#1E3A5F]">
                                    <p class="text-sm font-medium text-[#373737] dark:text-white" x-text="tag.name"></p>
                                    <div class="mt-2 flex items-center gap-2">
                                        <div class="min-w-0 flex-1">
                                            <input type="range"
                                                min="0"
                                               :max="relevanceLevels.length - 1"
                                               step="1"
                                               class="quiz-gradient-slider w-full"
                                               :value="tagRelevanceIndex(tag.id)"
                                               @input="setTagRelevanceByIndex(tag.id, $event.target.value)">
                                        </div>
                                        <span class="w-24 shrink-0 whitespace-nowrap text-right text-sm font-semibold text-[#334155] dark:text-white" x-text="relevanceLabel(tag.relevance)"></span>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <div class="mt-3 flex items-center justify-between" x-show="tagRelevanceTotalPages() > 1">
                            <button type="button"
                                @click="prevTagRelevancePage()"
                                :disabled="tagFilters.relevancePage <= 1"
                                class="rounded-lg border border-[#ABB7C2] px-3 py-1.5 text-sm text-[#5E5F60] hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-50 dark:text-white dark:hover:bg-gray-800">
                                Prev
                            </button>
                            <p class="text-sm text-gray-500 dark:text-gray-300" x-text="`Page ${tagFilters.relevancePage} of ${tagRelevanceTotalPages()}`"></p>
                            <button type="button"
                                @click="nextTagRelevancePage()"
                                :disabled="tagFilters.relevancePage >= tagRelevanceTotalPages()"
                                class="rounded-lg border border-[#ABB7C2] px-3 py-1.5 text-sm text-[#5E5F60] hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-50 dark:text-white dark:hover:bg-gray-800">
                                Next
                            </button>
                        </div>
                    </div>
                </div>

                <div x-show="tagModalCategoryMode">
                    <div class="mt-4 rounded-md border border-gray-200 divide-y divide-gray-100 dark:border-gray-700 dark:divide-gray-800">
                        <button type="button"
                            @click="selectTagCategory('')"
                            :class="tagFilters.category === '' ? 'border-l-[#5F2DED] bg-[#F6F2FF] dark:bg-[#34344A]' : 'border-l-transparent'"
                            class="w-full border-l-4 px-4 py-2.5 text-left text-sm font-medium text-[#5E5F60] transition-colors hover:bg-gray-50 dark:text-white dark:hover:bg-gray-800">
                            All categories
                        </button>

                        <template x-for="category in visibleTagCategories()" :key="`tag-category-${category}`">
                            <button type="button"
                                @click="selectTagCategory(category)"
                                :class="tagFilters.category === category ? 'border-l-[#5F2DED] bg-[#F6F2FF] dark:bg-[#34344A]' : 'border-l-transparent'"
                                class="w-full border-l-4 px-4 py-2.5 text-left text-sm font-medium text-[#5E5F60] transition-colors hover:bg-gray-50 dark:text-white dark:hover:bg-gray-800"
                                x-text="category">
                            </button>
                        </template>
                    </div>
                </div>

                <div class="mt-4 flex justify-end">
                    <button type="button" @click="closeTagModal()" class="rounded-xl bg-[#5F2DED] px-5 py-2.5 text-sm font-semibold text-white">Confirm</button>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
    function questionBankPage(config) {
        const deepClone = (value) => JSON.parse(JSON.stringify(value));
        const emptyPair = () => ({ left: '', right: '' });
        const defaultTagRelevance = String(config.defaultTagRelevance || 'medium').trim().toLowerCase();
        const sliderRelevanceLevels = ['very_low', 'low', 'medium', 'high', 'very_high'];
        const typeOptions = [
            { value: 'multiple_choice', label: 'Multiple Choice' },
            { value: 'true_false', label: 'True / False' },
            { value: 'short_answer', label: 'Short Answer' },
            { value: 'essay', label: 'Essay' },
            { value: 'numerical', label: 'Numerical' },
            { value: 'matching', label: 'Matching' },
        ];
        const emptyForm = () => ({
            id: null,
            type: 'multiple_choice',
            text: '',
            default_points: 1,
            required: false,
            is_archived: false,
            show_matching_answer_key: false,
            option: ['', ''],
            answer_key: [],
            answer_key_bool: 'true',
            answer_key_text: '',
            answer_key_num: '',
            match_left: [''],
            match_right: [''],
            answer_pairs: [emptyPair()],
            tags: [],
        });

        return {
            typeOptions,
            hasSubject: !!config.hasSubject,
            pageUrl: String(config.pageUrl || ''),
            subjects: Array.isArray(config.subjects) ? config.subjects : [],
            subjectAssignments: Array.isArray(config.subjectAssignments) ? config.subjectAssignments : [],
            selectedSubjectId: Number(config.currentSubjectId || 0) || null,
            relevanceLevels: Array.isArray(config.relevanceLevels) && config.relevanceLevels.length ? config.relevanceLevels : ['very_high', 'high', 'medium', 'low', 'very_low'],
            tagCategories: Array.isArray(config.tagCategories) ? config.tagCategories : [],
            availableTags: Array.isArray(config.tags) ? config.tags : [],
            items: [],
            quizzes: [],
            currentView: 'library',
            selectedId: null,
            selectedQuizId: null,
            selectedQuizSnapshot: null,
            pagination: { currentPage: 1, lastPage: 1, perPage: 12, total: 0 },
            selectedImportIds: [],
            selectedImportDetails: {},
            loadingList: false,
            loadingDetail: false,
            loadingQuizzes: false,
            saving: false,
            quizImporting: false,
            filters: { search: '', type: '', state: 'active', tag_ids: [] },
            quizFilters: { search: '' },
            tagFilters: { search: '', category: '', page: 1, relevancePage: 1 },
            savedQuestionModalOpen: false,
            savedQuestionModalSource: 'editor',
            savedQuestionTagModalOpen: false,
            savedQuestionTagFilterSearch: '',
            savedQuestionTagDraftIds: [],
            expandedSavedQuestionIds: [],
            quizModalOpen: false,
            subjectSelectorOpen: false,
            subjectSelectorGrade: '',
            subjectSelectorSectionId: '',
            subjectSelectorSearch: '',
            tagModalOpen: false,
            tagModalCategoryMode: false,
            tagModalRelevanceMode: false,
            archiveModalOpen: false,
            pendingArchiveId: null,
            pendingArchiveLabel: '',
            form: emptyForm(),
            errors: {},
            flash: { success: config.flashSuccess || '', error: '' },
            flashTimer: null,
            errorTimer: null,
            popstateBound: false,

            async init() {
                if (!this.hasSubject) {
                    return;
                }

                this.bindPopState();
                await Promise.all([this.fetchItems(), this.fetchQuizzes()]);
                await this.applyViewFromLocation();
                if (this.flash.success) {
                    this.scheduleFlashClear();
                }
            },

            resetFilters() {
                this.filters = { search: '', type: '', state: 'active', tag_ids: [] };
                this.pagination.currentPage = 1;
                this.fetchItems();
            },

            applyLibraryFilters() {
                this.pagination.currentPage = 1;
                this.fetchItems();
            },

            changePage(page) {
                const nextPage = Number(page);
                if (!Number.isInteger(nextPage) || nextPage < 1 || nextPage === this.pagination.currentPage || nextPage > this.pagination.lastPage) {
                    return;
                }

                this.pagination.currentPage = nextPage;
                this.fetchItems();
                this.scrollToTop();
            },

            bindPopState() {
                if (this.popstateBound) {
                    return;
                }

                window.addEventListener('popstate', async () => {
                    await this.applyViewFromLocation();
                });
                this.popstateBound = true;
            },

            currentPageUrl() {
                return new URL(window.location.href);
            },

            viewStateFromLocation() {
                const currentUrl = this.currentPageUrl();
                const view = String(currentUrl.searchParams.get('question_view') || '').trim().toLowerCase();
                const itemId = Number(currentUrl.searchParams.get('question_id') || 0) || null;

                if (view === 'edit' && itemId) {
                    return { view: 'edit', itemId };
                }

                if (view === 'create') {
                    return { view: 'create', itemId: null };
                }

                return { view: 'library', itemId: null };
            },

            syncCurrentView(replaceHistory = false) {
                if (!this.pageUrl) {
                    return;
                }

                const nextUrl = new URL(this.pageUrl, window.location.origin);
                if (this.selectedSubjectId) {
                    nextUrl.searchParams.set('subject_id', String(this.selectedSubjectId));
                }

                if (this.currentView === 'create') {
                    nextUrl.searchParams.set('question_view', 'create');
                } else if (this.currentView === 'edit' && this.selectedId) {
                    nextUrl.searchParams.set('question_view', 'edit');
                    nextUrl.searchParams.set('question_id', String(this.selectedId));
                }

                const nextHref = nextUrl.toString();
                if (nextHref === window.location.href) {
                    return;
                }

                window.history[replaceHistory ? 'replaceState' : 'pushState']({}, '', nextHref);
            },

            scrollToTop() {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            },

            openLibraryView(options = {}) {
                this.currentView = 'library';

                if (this.selectedId && !this.items.some((item) => Number(item.id) === Number(this.selectedId))) {
                    this.selectedId = null;
                }

                if (options.syncHistory !== false) {
                    this.syncCurrentView(!!options.replaceHistory);
                }

                if (options.scroll !== false) {
                    this.scrollToTop();
                }
            },

            async applyViewFromLocation() {
                const nextView = this.viewStateFromLocation();

                if (nextView.view === 'create') {
                    this.createNew({ syncHistory: false, scroll: false });
                    return;
                }

                if (nextView.view === 'edit' && nextView.itemId) {
                    await this.selectItem(nextView.itemId, { syncHistory: false, scroll: false });
                    return;
                }

                this.openLibraryView({ syncHistory: false, scroll: false });
            },

            selectedSubject() {
                return this.subjects.find((subject) => Number(subject.id) === Number(this.selectedSubjectId)) || null;
            },

            selectedSubjectLabel() {
                return this.selectedSubject()?.label || 'Select subject';
            },

            openSubjectSelector() {
                if (!this.hasSubject) {
                    return;
                }

                const currentAssignments = this.subjectAssignments.filter((assignment) => Number(assignment.subject_id) === Number(this.selectedSubjectId));
                const fallbackAssignment = currentAssignments[0] || this.subjectAssignments[0] || null;
                const fallbackGrade = fallbackAssignment?.grade_level
                    ? String(fallbackAssignment.grade_level)
                    : String(this.availableAssignmentGrades()[0]?.value || '');

                this.subjectSelectorGrade = fallbackGrade;
                this.subjectSelectorSectionId = '';
                this.subjectSelectorSearch = '';
                this.subjectSelectorOpen = true;
            },

            closeSubjectSelector() {
                this.subjectSelectorOpen = false;
                this.subjectSelectorSearch = '';
            },

            availableAssignmentGrades() {
                const grades = new Map();

                this.subjectAssignments.forEach((assignment) => {
                    const value = String(assignment.grade_level || '');
                    const key = value === '' ? 'none' : value;

                    if (!grades.has(key)) {
                        grades.set(key, {
                            value,
                            label: assignment.grade_label || (value === '' ? 'No grade level' : `Grade ${value}`),
                            sections: new Set(),
                        });
                    }

                    grades.get(key).sections.add(Number(assignment.section_id));
                });

                return [...grades.values()]
                    .map((grade) => ({
                        value: grade.value,
                        label: grade.label,
                        sections: grade.sections.size,
                    }))
                    .sort((left, right) => {
                        const leftValue = Number.parseInt(left.value, 10);
                        const rightValue = Number.parseInt(right.value, 10);

                        if (!Number.isNaN(leftValue) && !Number.isNaN(rightValue)) {
                            return leftValue - rightValue;
                        }

                        return String(left.label).localeCompare(String(right.label));
                    });
            },

            selectSubjectGrade(gradeLevel) {
                this.subjectSelectorGrade = String(gradeLevel || '');
                this.subjectSelectorSectionId = '';
            },

            filteredAssignmentsByGrade() {
                const selectedGrade = String(this.subjectSelectorGrade || '');

                return this.subjectAssignments.filter((assignment) => {
                    if (selectedGrade === '') {
                        return true;
                    }

                    return String(assignment.grade_level || '') === selectedGrade;
                });
            },

            availableAssignmentSections() {
                const sections = new Map();

                this.filteredAssignmentsByGrade().forEach((assignment) => {
                    const sectionId = Number(assignment.section_id);
                    if (!Number.isInteger(sectionId) || sectionId < 1) {
                        return;
                    }

                    if (!sections.has(sectionId)) {
                        sections.set(sectionId, {
                            id: sectionId,
                            name: assignment.section_name || 'Unnamed section',
                            subjects: new Set(),
                        });
                    }

                    sections.get(sectionId).subjects.add(Number(assignment.subject_id));
                });

                return [...sections.values()]
                    .map((section) => ({
                        id: section.id,
                        name: section.name,
                        subjects: section.subjects.size,
                    }))
                    .sort((left, right) => String(left.name).localeCompare(String(right.name)));
            },

            selectSubjectSection(sectionId) {
                this.subjectSelectorSectionId = sectionId === '' ? '' : String(sectionId);
            },

            filteredAssignmentSubjects() {
                const selectedSectionId = String(this.subjectSelectorSectionId || '');
                const search = String(this.subjectSelectorSearch || '').trim().toLowerCase();
                const subjects = new Map();

                this.filteredAssignmentsByGrade()
                    .filter((assignment) => selectedSectionId === '' || String(assignment.section_id) === selectedSectionId)
                    .forEach((assignment) => {
                        const subjectId = Number(assignment.subject_id);
                        if (!Number.isInteger(subjectId) || subjectId < 1) {
                            return;
                        }

                        if (!subjects.has(subjectId)) {
                            subjects.set(subjectId, {
                                id: subjectId,
                                label: assignment.subject_label || assignment.subject_name || 'Unnamed subject',
                                sections: new Set(),
                            });
                        }

                        subjects.get(subjectId).sections.add(String(assignment.section_name || 'Unnamed section'));
                    });

                return [...subjects.values()]
                    .map((subject) => {
                        const sectionNames = [...subject.sections].sort((left, right) => left.localeCompare(right));
                        const meta = selectedSectionId !== ''
                            ? (sectionNames[0] || 'Selected section')
                            : (sectionNames.length === 1 ? sectionNames[0] : `${sectionNames.length} sections`);

                        return {
                            id: subject.id,
                            label: subject.label,
                            meta,
                        };
                    })
                    .filter((subject) => {
                        if (search === '') {
                            return true;
                        }

                        return String(subject.label).toLowerCase().includes(search)
                            || String(subject.meta).toLowerCase().includes(search);
                    })
                    .sort((left, right) => String(left.label).localeCompare(String(right.label)));
            },

            changeSubject(subjectId) {
                const normalizedSubjectId = Number(subjectId);
                if (!Number.isInteger(normalizedSubjectId) || normalizedSubjectId < 1 || !this.pageUrl) {
                    return;
                }

                if (normalizedSubjectId === Number(this.selectedSubjectId)) {
                    this.selectedSubjectId = normalizedSubjectId;
                    return;
                }

                const nextUrl = new URL(this.pageUrl, window.location.origin);
                nextUrl.searchParams.set('subject_id', String(normalizedSubjectId));
                window.location.href = nextUrl.toString();
            },

            applySubjectSelection(subjectId) {
                this.closeSubjectSelector();
                this.changeSubject(subjectId);
            },

            createNew(options = {}) {
                this.selectedId = null;
                this.form = emptyForm();
                this.errors = {};
                this.clearFlash();
                this.currentView = 'create';

                if (options.syncHistory !== false) {
                    this.syncCurrentView(!!options.replaceHistory);
                }

                if (options.scroll !== false) {
                    this.scrollToTop();
                }
            },

            returnToLibrary() {
                this.openLibraryView({ replaceHistory: true });
            },

            openArchiveModal(itemId, label = '') {
                const normalizedItemId = Number(itemId);
                if (!normalizedItemId) {
                    return;
                }

                this.pendingArchiveId = normalizedItemId;
                this.pendingArchiveLabel = String(label || '').trim();
                this.archiveModalOpen = true;
            },

            closeArchiveModal() {
                this.archiveModalOpen = false;
                this.pendingArchiveId = null;
                this.pendingArchiveLabel = '';
            },

            async confirmArchive() {
                if (!this.pendingArchiveId) {
                    return;
                }

                const targetItemId = this.pendingArchiveId;
                this.closeArchiveModal();
                await this.postItemAction(this.itemRoute(config.routes.archive, targetItemId), 'DELETE', 'Question archived successfully.');
            },

            async fetchItems() {
                this.loadingList = true;
                this.flash.error = '';

                try {
                    const params = new URLSearchParams({
                        search: this.filters.search || '',
                        type: this.filters.type || '',
                        state: this.filters.state || 'active',
                        page: String(this.pagination.currentPage || 1),
                        per_page: String(this.pagination.perPage || 12),
                    });

                    (Array.isArray(this.filters.tag_ids) ? this.filters.tag_ids : []).forEach((tagId) => {
                        const normalizedTagId = Number(tagId);
                        if (Number.isInteger(normalizedTagId) && normalizedTagId > 0) {
                            params.append('tag_ids[]', String(normalizedTagId));
                        }
                    });

                    const response = await fetch(`${config.routes.index}?${params.toString()}`, { headers: this.jsonHeaders() });
                    if (!response.ok) {
                        throw new Error('Unable to load question bank items.');
                    }

                    const payload = await response.json();
                    this.items = Array.isArray(payload.data) ? payload.data : [];
                    this.pagination.currentPage = Number(payload.meta?.current_page || 1) || 1;
                    this.pagination.lastPage = Math.max(1, Number(payload.meta?.last_page || 1) || 1);
                    this.pagination.perPage = Number(payload.meta?.per_page || this.pagination.perPage || 12) || 12;
                    this.pagination.total = Number(payload.meta?.total || 0) || 0;

                    this.items.forEach((item) => {
                        const normalizedId = Number(item.id);
                        if (this.isImportSelected(normalizedId)) {
                            this.selectedImportDetails[normalizedId] = {
                                id: normalizedId,
                                text: item.text || '',
                                type: item.type || '',
                            };
                        }
                    });

                    if (this.pagination.currentPage > this.pagination.lastPage) {
                        this.pagination.currentPage = this.pagination.lastPage;
                        await this.fetchItems();
                        return;
                    }

                    if (this.selectedId && !this.items.some((item) => Number(item.id) === Number(this.selectedId)) && this.currentView === 'library') {
                        this.selectedId = null;
                    }
                } catch (error) {
                    this.setFlash('error', error.message || 'Unable to load question bank items.');
                } finally {
                    this.loadingList = false;
                }
            },

            async fetchQuizzes() {
                if (!config.routes.quizzes) {
                    return;
                }

                this.loadingQuizzes = true;
                this.flash.error = '';

                try {
                    const params = new URLSearchParams({
                        all: '1',
                        search: this.quizFilters.search || '',
                    });

                    const response = await fetch(`${config.routes.quizzes}?${params.toString()}`, { headers: this.jsonHeaders() });
                    if (!response.ok) {
                        throw new Error('Unable to load quizzes for the selected subject.');
                    }

                    const payload = await response.json();
                    this.quizzes = Array.isArray(payload.data) ? payload.data : [];

                    if (this.selectedQuizId) {
                        const selected = this.quizzes.find((quiz) => Number(quiz.id) === Number(this.selectedQuizId));
                        if (selected) {
                            this.selectedQuizSnapshot = deepClone(selected);
                        }
                    }
                } catch (error) {
                    this.setFlash('error', error.message || 'Unable to load quizzes for the selected subject.');
                } finally {
                    this.loadingQuizzes = false;
                }
            },

            openSavedQuestionModal(source = 'editor') {
                this.savedQuestionModalSource = source;
                this.savedQuestionModalOpen = true;
            },

            closeSavedQuestionModal() {
                this.savedQuestionModalOpen = false;
                this.savedQuestionTagModalOpen = false;
                this.savedQuestionTagFilterSearch = '';
                this.savedQuestionTagDraftIds = [];
            },

            openSavedQuestionTagModal() {
                this.savedQuestionTagDraftIds = Array.isArray(this.filters.tag_ids) ? [...this.filters.tag_ids] : [];
                this.savedQuestionTagModalOpen = true;
            },

            closeSavedQuestionTagModal() {
                this.savedQuestionTagModalOpen = false;
                this.savedQuestionTagFilterSearch = '';
                this.savedQuestionTagDraftIds = [];
            },

            async selectItem(itemId, options = {}) {
                if (!itemId) {
                    return;
                }

                this.loadingDetail = true;
                this.errors = {};
                this.flash.error = '';

                try {
                    const response = await fetch(this.itemRoute(config.routes.show, itemId), { headers: this.jsonHeaders() });
                    if (!response.ok) {
                        throw new Error('Unable to load the selected question.');
                    }

                    const payload = await response.json();
                    this.selectedId = Number(itemId);
                    this.form = this.mapDetailToForm(payload.data || {});
                    this.currentView = 'edit';

                    if (options.syncHistory !== false) {
                        this.syncCurrentView(!!options.replaceHistory);
                    }

                    if (options.scroll !== false) {
                        this.scrollToTop();
                    }
                } catch (error) {
                    this.setFlash('error', error.message || 'Unable to load the selected question.');
                    if (options.syncHistory === false) {
                        this.openLibraryView({ replaceHistory: true, scroll: false });
                    }
                } finally {
                    this.loadingDetail = false;
                }
            },

            async selectItemFromModal(itemId) {
                await this.selectItem(itemId);
                this.closeSavedQuestionModal();
            },

            selectQuiz(quiz) {
                this.selectedQuizId = Number(quiz?.id || 0) || null;
                this.selectedQuizSnapshot = quiz ? deepClone(quiz) : null;
            },

            openQuizModal() {
                this.quizModalOpen = true;
            },

            closeQuizModal() {
                this.quizModalOpen = false;
            },

            selectQuizFromModal(quiz) {
                this.selectQuiz(quiz);
                this.closeQuizModal();
            },

            openSelectedQuiz() {
                const targetUrl = String(this.selectedQuizSnapshot?.edit_url || '').trim();
                if (!targetUrl) {
                    return;
                }

                window.location.href = targetUrl;
            },

            isImportSelected(itemId) {
                return this.selectedImportIds.includes(Number(itemId));
            },

            toggleImportSelection(itemId, item = null) {
                const normalizedId = Number(itemId);
                if (!normalizedId) {
                    return;
                }

                if (this.isImportSelected(normalizedId)) {
                    this.selectedImportIds = this.selectedImportIds.filter((value) => Number(value) !== normalizedId);
                    delete this.selectedImportDetails[normalizedId];
                    return;
                }

                this.selectedImportIds.push(normalizedId);
                if (item && typeof item === 'object') {
                    this.selectedImportDetails[normalizedId] = {
                        id: normalizedId,
                        text: item.text || '',
                        type: item.type || '',
                    };
                }
            },

            clearImportSelection() {
                this.selectedImportIds = [];
                this.selectedImportDetails = {};
            },

            openTagModal() {
                this.resetTagFilters();
                this.tagModalCategoryMode = false;
                this.tagModalRelevanceMode = false;
                this.tagModalOpen = true;
            },

            closeTagModal() {
                this.tagModalOpen = false;
                this.tagModalCategoryMode = false;
                this.tagModalRelevanceMode = false;
            },

            resetTagFilters() {
                this.tagFilters = { search: '', category: '', page: 1, relevancePage: 1 };
            },

            resetTagModalPages() {
                this.tagFilters.page = 1;
                this.tagFilters.relevancePage = 1;
            },

            toggleTagCategoryMode() {
                this.tagModalCategoryMode = !this.tagModalCategoryMode;
                this.tagModalRelevanceMode = false;
                this.tagFilters.relevancePage = 1;
            },

            toggleTagRelevanceMode() {
                this.tagModalRelevanceMode = !this.tagModalRelevanceMode;
                this.tagModalCategoryMode = false;
                this.tagFilters.page = 1;
            },

            handleTagModalBack() {
                this.tagModalCategoryMode = false;
                this.tagModalRelevanceMode = false;
            },

            clearAllTags() {
                this.form.tags = [];
                this.tagFilters.relevancePage = 1;
            },

            selectedTagCategoryLabel() {
                return this.tagFilters.category || 'All categories';
            },

            selectedImportPreview() {
                return this.selectedImportIds
                    .slice(0, 4)
                    .map((itemId) => this.selectedImportDetails[itemId] || null)
                    .filter(Boolean);
            },

            visibleTableTags(item) {
                return Array.isArray(item?.tags) ? item.tags.slice(0, 2) : [];
            },

            tableHiddenTagCount(item) {
                const count = Array.isArray(item?.tags) ? item.tags.length : 0;
                return count > 2 ? count - 2 : 0;
            },

            formatListDate(value) {
                const normalized = String(value || '').trim();
                if (normalized === '') {
                    return 'Unavailable';
                }

                const parsed = new Date(normalized);
                if (Number.isNaN(parsed.getTime())) {
                    return normalized;
                }

                return parsed.toLocaleDateString(undefined, {
                    month: 'short',
                    day: 'numeric',
                    year: 'numeric',
                });
            },

            mapDetailToForm(item) {
                const form = emptyForm();
                form.id = Number(item.id || 0) || null;
                form.type = item.type || 'multiple_choice';
                form.text = item.text || '';
                form.default_points = Number(item.default_points ?? 1) || 0;
                form.required = !!item.required;
                form.is_archived = !!item.is_archived;
                form.show_matching_answer_key = false;
                form.tags = Array.isArray(item.tags) ? item.tags.map((tag) => ({
                    id: Number(tag.id),
                    name: tag.name,
                    category: tag.category || '',
                    relevance: this.normalizeRelevance(tag.relevance),
                })) : [];

                if (form.type === 'multiple_choice') {
                    form.option = Array.isArray(item.option) && item.option.length ? deepClone(item.option) : ['', ''];
                    const answerValues = Array.isArray(item.answer_key) ? item.answer_key : (item.answer_key == null ? [] : [item.answer_key]);
                    form.answer_key = answerValues
                        .map((value) => form.option.findIndex((option) => option === value))
                        .filter((index) => index >= 0)
                        .map((index) => String(index));
                } else if (form.type === 'true_false') {
                    const value = Array.isArray(item.answer_key) ? item.answer_key[0] : item.answer_key;
                    form.answer_key_bool = (value === false || value === 'false' || value === 0 || value === '0') ? 'false' : 'true';
                } else if (form.type === 'short_answer' || form.type === 'essay') {
                    const value = Array.isArray(item.answer_key) ? item.answer_key[0] : item.answer_key;
                    form.answer_key_text = value == null ? '' : String(value);
                } else if (form.type === 'numerical') {
                    const value = Array.isArray(item.answer_key) ? item.answer_key[0] : item.answer_key;
                    form.answer_key_num = value == null ? '' : String(value);
                } else if (form.type === 'matching') {
                    const option = item.option && typeof item.option === 'object' && !Array.isArray(item.option) ? item.option : {};
                    form.match_left = Array.isArray(option.left) && option.left.length ? deepClone(option.left) : [''];
                    form.match_right = Array.isArray(option.right) && option.right.length ? deepClone(option.right) : [''];
                    form.answer_pairs = Array.isArray(item.answer_key) && item.answer_key.length ? item.answer_key.map((pair) => ({
                        left: pair?.left ? String(pair.left) : '',
                        right: pair?.right ? String(pair.right) : '',
                    })) : [emptyPair()];
                    form.show_matching_answer_key = Array.isArray(item.answer_key) && item.answer_key.length > 0;
                }

                return form;
            },

            handleTypeChange() {
                const keep = {
                    id: this.form.id,
                    text: this.form.text,
                    default_points: this.form.default_points,
                    required: this.form.required,
                    is_archived: this.form.is_archived,
                    tags: deepClone(this.form.tags),
                };
                this.form = { ...emptyForm(), ...keep, type: this.form.type };
                this.errors = {};
            },

            addMultipleChoiceOption() {
                this.form.option.push('');
            },

            removeMultipleChoiceOption(index) {
                if (this.form.option.length <= 2) {
                    return;
                }
                this.form.option.splice(index, 1);
                this.form.answer_key = this.form.answer_key
                    .map((value) => Number(value))
                    .filter((value) => value !== index)
                    .map((value) => value > index ? String(value - 1) : String(value));
            },

            toggleMultipleChoiceAnswer(index) {
                const key = String(index);
                if (this.form.answer_key.includes(key)) {
                    this.form.answer_key = this.form.answer_key.filter((value) => value !== key);
                } else {
                    this.form.answer_key.push(key);
                }
            },

            addMatchLeft() {
                this.form.match_left.push('');
            },

            removeMatchLeft(index) {
                if (this.form.match_left.length <= 1) {
                    return;
                }
                const removed = this.trimmedValue(this.form.match_left[index]);
                this.form.match_left.splice(index, 1);
                this.form.answer_pairs = this.form.answer_pairs.filter((pair) => pair.left !== removed);
                if (this.form.answer_pairs.length === 0) {
                    this.form.answer_pairs = [emptyPair()];
                }
            },

            addMatchRight() {
                this.form.match_right.push('');
            },

            removeMatchRight(index) {
                if (this.form.match_right.length <= 1) {
                    return;
                }
                const removed = this.trimmedValue(this.form.match_right[index]);
                this.form.match_right.splice(index, 1);
                this.form.answer_pairs = this.form.answer_pairs.filter((pair) => pair.right !== removed);
                if (this.form.answer_pairs.length === 0) {
                    this.form.answer_pairs = [emptyPair()];
                }
            },

            addAnswerPair() {
                this.form.answer_pairs.push(emptyPair());
            },

            removeAnswerPair(index) {
                if (this.form.answer_pairs.length <= 1) {
                    this.form.answer_pairs = [emptyPair()];
                } else {
                    this.form.answer_pairs.splice(index, 1);
                }
            },

            cleanedMatchLeft() {
                return this.form.match_left.map((value) => this.trimmedValue(value)).filter(Boolean);
            },

            cleanedMatchRight() {
                return this.form.match_right.map((value) => this.trimmedValue(value)).filter(Boolean);
            },

            isFormMatchSelected(rowIndex, colIndex) {
                const left = this.cleanedMatchLeft()[rowIndex] || '';
                const right = this.cleanedMatchRight()[colIndex] || '';
                return this.form.answer_pairs.some((pair) => this.trimmedValue(pair.left) === left && this.trimmedValue(pair.right) === right);
            },

            toggleFormMatchSelection(rowIndex, colIndex) {
                const left = this.cleanedMatchLeft()[rowIndex] || '';
                const right = this.cleanedMatchRight()[colIndex] || '';
                if (!left || !right) {
                    return;
                }

                const currentIndex = this.form.answer_pairs.findIndex((pair) => this.trimmedValue(pair.left) === left);
                if (currentIndex >= 0 && this.trimmedValue(this.form.answer_pairs[currentIndex].right) === right) {
                    this.form.answer_pairs.splice(currentIndex, 1);
                } else if (currentIndex >= 0) {
                    this.form.answer_pairs[currentIndex] = { left, right };
                } else {
                    this.form.answer_pairs.push({ left, right });
                }

                if (this.form.answer_pairs.length === 0) {
                    this.form.answer_pairs = [emptyPair()];
                } else {
                    this.form.answer_pairs = this.form.answer_pairs
                        .filter((pair) => this.trimmedValue(pair.left) !== '' && this.trimmedValue(pair.right) !== '');
                }
            },

            filteredAvailableTags() {
                const search = String(this.tagFilters.search || '').trim().toLowerCase();
                const category = String(this.tagFilters.category || '').trim();
                return this.availableTags.filter((tag) => {
                    const matchesSearch = search === '' || String(tag.name || '').toLowerCase().includes(search) || String(tag.category || '').toLowerCase().includes(search);
                    const matchesCategory = category === '' || String(tag.category || '') === category;
                    return matchesSearch && matchesCategory;
                });
            },

            visibleTagCategories() {
                const search = String(this.tagFilters.search || '').trim().toLowerCase();
                return [...new Set(this.availableTags
                    .map((tag) => String(tag.category || '').trim())
                    .filter(Boolean)
                    .filter((category) => search === '' || category.toLowerCase().includes(search)))]
                    .sort((left, right) => left.localeCompare(right));
            },

            selectTagCategory(category) {
                this.tagFilters.category = String(category || '');
                this.tagModalCategoryMode = false;
                this.tagFilters.page = 1;
            },

            tagOptionTotalPages() {
                return Math.max(1, Math.ceil(this.filteredAvailableTags().length / 8));
            },

            visibleTagOptions() {
                const currentPage = Math.min(this.tagFilters.page, this.tagOptionTotalPages());
                const start = (currentPage - 1) * 8;
                return this.filteredAvailableTags().slice(start, start + 8);
            },

            prevTagOptionPage() {
                if (this.tagFilters.page > 1) {
                    this.tagFilters.page -= 1;
                }
            },

            nextTagOptionPage() {
                if (this.tagFilters.page < this.tagOptionTotalPages()) {
                    this.tagFilters.page += 1;
                }
            },

            visibleTagRelevanceEntries() {
                const search = String(this.tagFilters.search || '').trim().toLowerCase();
                const selected = this.form.tags.filter((tag) => {
                    return search === '' || String(tag.name || '').toLowerCase().includes(search) || String(tag.category || '').toLowerCase().includes(search);
                });
                const currentPage = Math.min(this.tagFilters.relevancePage, this.tagRelevanceTotalPages());
                const start = (currentPage - 1) * 6;
                return selected.slice(start, start + 6);
            },

            tagRelevanceTotalPages() {
                const search = String(this.tagFilters.search || '').trim().toLowerCase();
                const count = this.form.tags.filter((tag) => {
                    return search === '' || String(tag.name || '').toLowerCase().includes(search) || String(tag.category || '').toLowerCase().includes(search);
                }).length;
                return Math.max(1, Math.ceil(count / 6));
            },

            prevTagRelevancePage() {
                if (this.tagFilters.relevancePage > 1) {
                    this.tagFilters.relevancePage -= 1;
                }
            },

            nextTagRelevancePage() {
                if (this.tagFilters.relevancePage < this.tagRelevanceTotalPages()) {
                    this.tagFilters.relevancePage += 1;
                }
            },

            hasTag(tagId) {
                return this.form.tags.some((tag) => Number(tag.id) === Number(tagId));
            },

            toggleTag(tag) {
                const index = this.form.tags.findIndex((item) => Number(item.id) === Number(tag.id));
                if (index >= 0) {
                    this.form.tags.splice(index, 1);
                } else {
                    this.form.tags.push({
                        id: Number(tag.id),
                        name: tag.name,
                        category: tag.category || '',
                        relevance: defaultTagRelevance,
                    });
                }
                this.tagFilters.relevancePage = 1;
            },

            removeTag(tagId) {
                this.form.tags = this.form.tags.filter((tag) => Number(tag.id) !== Number(tagId));
                this.tagFilters.relevancePage = 1;
            },

            tagRelevanceIndex(tagId) {
                const tag = this.form.tags.find((item) => Number(item.id) === Number(tagId));
                const relevance = this.normalizeRelevance(tag?.relevance);
                const foundIndex = sliderRelevanceLevels.indexOf(relevance);
                return foundIndex >= 0 ? foundIndex : 0;
            },

            setTagRelevanceByIndex(tagId, index) {
                const normalizedIndex = Number(index);
                const tag = this.form.tags.find((item) => Number(item.id) === Number(tagId));
                if (!tag) {
                    return;
                }

                tag.relevance = sliderRelevanceLevels[normalizedIndex] || defaultTagRelevance;
            },

            tagToneClass(relevance) {
                const normalized = this.normalizeRelevance(relevance);
                return {
                    very_high: 'tag-pill-tone-very-high',
                    high: 'tag-pill-tone-high',
                    medium: 'tag-pill-tone-medium',
                    low: 'tag-pill-tone-low',
                    very_low: 'tag-pill-tone-very-low',
                }[normalized] || 'tag-pill-tone-medium';
            },

            savedQuestionMetaLabel(item) {
                const parts = [this.typeLabel(item?.type || '')];

                if (item?.is_archived) {
                    parts.push('Archived');
                }

                return parts.filter(Boolean).join(' • ');
            },

            savedQuestionFilterTags() {
                return [...this.availableTags]
                    .filter((tag) => Number.isInteger(Number(tag?.id)))
                    .sort((left, right) => String(left?.name || '').localeCompare(String(right?.name || '')));
            },

            filteredSavedQuestionFilterTags() {
                const search = String(this.savedQuestionTagFilterSearch || '').trim().toLowerCase();
                return this.savedQuestionFilterTags().filter((tag) => {
                    return search === ''
                        || String(tag?.name || '').toLowerCase().includes(search)
                        || String(tag?.category || '').toLowerCase().includes(search);
                });
            },

            hasSavedQuestionFilterTag(tagId) {
                return (Array.isArray(this.filters.tag_ids) ? this.filters.tag_ids : []).includes(Number(tagId));
            },

            hasSavedQuestionDraftTag(tagId) {
                return (Array.isArray(this.savedQuestionTagDraftIds) ? this.savedQuestionTagDraftIds : []).includes(Number(tagId));
            },

            toggleSavedQuestionFilterTag(tagId) {
                const normalizedTagId = Number(tagId);
                if (!Number.isInteger(normalizedTagId) || normalizedTagId < 1) {
                    return;
                }

                const selected = Array.isArray(this.savedQuestionTagDraftIds) ? [...this.savedQuestionTagDraftIds] : [];
                this.savedQuestionTagDraftIds = selected.includes(normalizedTagId)
                    ? selected.filter((value) => value !== normalizedTagId)
                    : [...selected, normalizedTagId];
            },

            applySavedQuestionTagFilters() {
                const nextIds = Array.isArray(this.savedQuestionTagDraftIds)
                    ? [...new Set(this.savedQuestionTagDraftIds.map((value) => Number(value)).filter((value) => Number.isInteger(value) && value > 0))]
                    : [];
                const currentIds = Array.isArray(this.filters.tag_ids)
                    ? [...new Set(this.filters.tag_ids.map((value) => Number(value)).filter((value) => Number.isInteger(value) && value > 0))]
                    : [];

                const changed = nextIds.length !== currentIds.length
                    || nextIds.some((value, index) => value !== currentIds[index]);

                this.filters.tag_ids = nextIds;
                this.closeSavedQuestionTagModal();

                if (changed) {
                    this.pagination.currentPage = 1;
                    this.fetchItems();
                }
            },

            clearSavedQuestionTagFilters() {
                if (this.savedQuestionTagModalOpen) {
                    if (!Array.isArray(this.savedQuestionTagDraftIds) || this.savedQuestionTagDraftIds.length === 0) {
                        return;
                    }

                    this.savedQuestionTagDraftIds = [];
                    return;
                }

                if (!Array.isArray(this.filters.tag_ids) || this.filters.tag_ids.length === 0) {
                    return;
                }

                this.filters.tag_ids = [];
                this.pagination.currentPage = 1;
                this.fetchItems();
            },

            isSavedQuestionExpanded(itemId) {
                return this.expandedSavedQuestionIds.includes(Number(itemId));
            },

            toggleSavedQuestionExpanded(itemId) {
                const normalizedItemId = Number(itemId);
                if (!Number.isInteger(normalizedItemId) || normalizedItemId < 1) {
                    return;
                }

                this.expandedSavedQuestionIds = this.isSavedQuestionExpanded(normalizedItemId)
                    ? this.expandedSavedQuestionIds.filter((value) => value !== normalizedItemId)
                    : [...this.expandedSavedQuestionIds, normalizedItemId];
            },

            async saveCurrent() {
                this.saving = true;
                this.errors = {};
                this.clearFlash();
                const wasEditing = !!this.selectedId;

                try {
                    const route = this.selectedId ? this.itemRoute(config.routes.update, this.selectedId) : config.routes.store;
                    const method = this.selectedId ? 'PUT' : 'POST';
                    const response = await fetch(route, {
                        method,
                        headers: this.jsonHeaders(),
                        body: JSON.stringify(this.buildPayload()),
                    });

                    if (response.status === 422) {
                        const failed = await response.json();
                        this.setErrors(this.normalizeErrors(failed.errors || {}));
                        return;
                    }

                    if (!response.ok) {
                        throw new Error('Unable to save the question bank item.');
                    }

                    const payload = await response.json();
                    this.setFlash('success', payload.message || 'Saved successfully.');
                    this.form = this.mapDetailToForm(payload.data || {});
                    this.selectedId = this.form.id;
                    if (this.selectedId && this.isImportSelected(this.selectedId)) {
                        this.selectedImportDetails[this.selectedId] = {
                            id: this.selectedId,
                            text: this.form.text || '',
                            type: this.form.type || '',
                        };
                    }
                    await this.fetchItems();

                    if (wasEditing && this.selectedId) {
                        this.currentView = 'edit';
                        this.syncCurrentView(true);
                    } else {
                        this.openLibraryView({ replaceHistory: true });
                    }
                } catch (error) {
                    this.setFlash('error', error.message || 'Unable to save the question bank item.');
                } finally {
                    this.saving = false;
                }
            },

            async archiveCurrent() {
                if (!this.selectedId) {
                    return;
                }

                this.openArchiveModal(this.selectedId, this.form.text || '');
            },

            async restoreCurrent() {
                if (!this.selectedId) {
                    return;
                }

                await this.postItemAction(this.itemRoute(config.routes.restore, this.selectedId), 'POST', 'Question restored successfully.', {
                    reloadSelected: true,
                });
            },

            async restoreItemFromModal(itemId) {
                if (!itemId) {
                    return;
                }

                await this.postItemAction(this.itemRoute(config.routes.restore, itemId), 'POST', 'Question restored successfully.', {
                    reloadSelected: this.currentView !== 'library' && Number(this.selectedId) === Number(itemId),
                });
            },

            async postItemAction(route, method, successMessage, options = {}) {
                this.clearFlash();

                try {
                    const response = await fetch(route, { method, headers: this.jsonHeaders() });
                    if (!response.ok) {
                        throw new Error('Unable to update this saved question.');
                    }

                    this.setFlash('success', successMessage);
                    if (options.reloadSelected && this.selectedId) {
                        await this.selectItem(this.selectedId, { syncHistory: false, scroll: false });
                    }
                    await this.fetchItems();
                    if (options.reloadSelected && this.currentView !== 'library') {
                        this.syncCurrentView(true);
                    }
                } catch (error) {
                    this.setFlash('error', error.message || 'Unable to update this saved question.');
                }
            },

            async importIntoQuiz() {
                if (!config.routes.importToQuiz || !this.selectedQuizId || this.selectedImportIds.length === 0) {
                    return;
                }

                this.quizImporting = true;
                this.clearFlash();

                try {
                    const response = await fetch(config.routes.importToQuiz, {
                        method: 'POST',
                        headers: this.jsonHeaders(),
                        body: JSON.stringify({
                            quiz_id: this.selectedQuizId,
                            item_ids: this.selectedImportIds,
                        }),
                    });

                    if (response.status === 422) {
                        const failed = await response.json();
                        this.setFlash('error', this.firstErrorMessage(failed.errors) || failed.message || 'Unable to import into the selected quiz.');
                        return;
                    }

                    if (!response.ok) {
                        throw new Error('Unable to import into the selected quiz.');
                    }

                    const payload = await response.json();
                    this.setFlash('success', payload.message || 'Questions imported successfully.');
                    if (payload.data?.quiz) {
                        this.selectedQuizSnapshot = payload.data.quiz;
                        this.selectedQuizId = Number(payload.data.quiz.id || this.selectedQuizId);
                    }

                    this.clearImportSelection();
                    await this.fetchQuizzes();
                } catch (error) {
                    this.setFlash('error', error.message || 'Unable to import into the selected quiz.');
                } finally {
                    this.quizImporting = false;
                }
            },

            buildPayload() {
                const payload = {
                    type: this.form.type,
                    text: this.form.text,
                    default_points: Number(this.form.default_points || 0),
                    required: !!this.form.required,
                    tag_ids: this.form.tags.map((tag) => Number(tag.id)),
                    tag_relevance: this.form.tags.reduce((carry, tag) => {
                        carry[tag.id] = this.normalizeRelevance(tag.relevance);
                        return carry;
                    }, {}),
                };

                if (this.form.type === 'multiple_choice') {
                    payload.option = deepClone(this.form.option);
                    payload.answer_key = deepClone(this.form.answer_key);
                } else if (this.form.type === 'true_false') {
                    payload.answer_key = this.form.answer_key_bool || 'true';
                } else if (this.form.type === 'short_answer' || this.form.type === 'essay') {
                    payload.answer_key = this.form.answer_key_text || '';
                } else if (this.form.type === 'numerical') {
                    payload.answer_key = this.form.answer_key_num === '' ? '' : this.form.answer_key_num;
                } else if (this.form.type === 'matching') {
                    payload.match_left = deepClone(this.form.match_left);
                    payload.match_right = deepClone(this.form.match_right);
                    payload.answer_key = this.form.answer_pairs
                        .map((pair) => ({
                            left: this.trimmedValue(pair.left),
                            right: this.trimmedValue(pair.right),
                        }))
                        .filter((pair) => pair.left !== '' && pair.right !== '');
                }

                return payload;
            },

            normalizeErrors(rawErrors) {
                const mapped = {};
                Object.entries(rawErrors).forEach(([key, messages]) => {
                    const value = Array.isArray(messages) ? messages[0] : messages;
                    if (!value) {
                        return;
                    }
                    const normalizedKey = String(key)
                        .replace(/^questions\.\d+\./, '')
                        .replace(/^answer_key\.\d+\.(left|right)$/, 'answer_key')
                        .replace(/^tag_ids\.\d+$/, 'tag_ids')
                        .replace(/^tag_relevance\.[^.]+$/, 'tag_relevance');
                    mapped[normalizedKey] = String(value);
                });
                return mapped;
            },

            firstErrorMessage(rawErrors) {
                const first = Object.values(rawErrors || {})[0];
                if (Array.isArray(first)) {
                    return first[0] || '';
                }

                return typeof first === 'string' ? first : '';
            },

            setErrors(nextErrors = {}) {
                this.errors = nextErrors;
                this.scheduleErrorClear();
            },

            scheduleErrorClear() {
                if (this.errorTimer) {
                    clearTimeout(this.errorTimer);
                }

                this.errorTimer = setTimeout(() => {
                    this.errors = {};
                    this.errorTimer = null;
                }, 5000);
            },

            clearFlash() {
                this.flash.success = '';
                this.flash.error = '';
                if (this.flashTimer) {
                    clearTimeout(this.flashTimer);
                    this.flashTimer = null;
                }
            },

            setFlash(type, message) {
                this.clearFlash();
                if (type === 'success') {
                    this.flash.success = String(message || '');
                } else {
                    this.flash.error = String(message || '');
                }
                this.scheduleFlashClear();
            },

            scheduleFlashClear() {
                if (this.flashTimer) {
                    clearTimeout(this.flashTimer);
                }

                this.flashTimer = setTimeout(() => {
                    this.flash.success = '';
                    this.flash.error = '';
                    this.flashTimer = null;
                }, 5000);
            },

            normalizeRelevance(value) {
                const normalized = String(value || '').trim().toLowerCase();
                return this.relevanceLevels.includes(normalized) ? normalized : defaultTagRelevance;
            },

            typeLabel(value) {
                const found = this.typeOptions.find((option) => option.value === value);
                return found ? found.label : value;
            },

            relevanceLabel(value) {
                return String(value || '').split('_').filter(Boolean).map((part) => part.charAt(0).toUpperCase() + part.slice(1)).join(' ');
            },

            quizStatusLabel(value) {
                return this.relevanceLabel(value);
            },

            quizModalMetaLabel(quiz) {
                const parts = [
                    quiz?.section?.name || '',
                    this.quizStatusLabel(quiz?.status || ''),
                    quiz?.visible ? 'Visible' : 'Hidden',
                ];

                return parts.filter(Boolean).join(' • ');
            },

            itemRoute(template, itemId) {
                return String(template).replace('__ITEM__', String(itemId));
            },

            trimmedValue(value) {
                return String(value || '').trim();
            },

            jsonHeaders() {
                return {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': config.csrfToken,
                };
            },
        };
    }
</script>
@endsection
