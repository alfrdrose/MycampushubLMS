@extends('layouts.dashboard')

@section('content')
@php
    $announcementQuery = request()->only(['school_year']);
    $announcementsReadOnly = $selectedSchoolYear && ! $selectedSchoolYear->is_active;
    $schoolYearFilterPayload = $schoolYears->map(fn ($schoolYear) => [
        'id' => (int) $schoolYear->id,
        'label' => $schoolYear->label,
        'start' => (int) $schoolYear->start_year,
        'end' => (int) $schoolYear->end_year,
        'is_active' => (bool) $schoolYear->is_active,
        'is_archived' => (bool) $schoolYear->is_archived,
    ])->values();
@endphp

<style>
    [x-cloak] { display: none !important; }
</style>

<div
    x-data="announcementManagementFilters({
        schoolYears: @js($schoolYearFilterPayload),
        selectedSchoolYearId: @js(optional($selectedSchoolYear)->id),
    })"
    class="w-full p-6 md:p-10 bg-whiteColor dark:bg-whiteColor-dark shadow-accordion dark:shadow-accordion-dark rounded-lg"
>
    <div id="page-header" class="mb-4 flex items-center justify-between flex-wrap">
        <div>
            <h1 id="page-title" class="text-3xl font-bold text-[#5E5F60] dark:text-white">
                Announcements
            </h1>
        </div>

        <div id="spinner-template" class="hidden">
            <div class="flex items-center justify-center h-full w-full">
                <x-spinner />
            </div>
        </div>
    </div>

    <div id="main-panel">
        <div class="border-t border-[#D4D4D8] dark:border-white py-4">
            <form
                method="GET"
                action="{{ route('admin.announcements') }}"
                x-ref="filtersForm"
                @submit.prevent="submitFilters()"
                class="flex flex-col gap-4 pb-4 sm:flex-row sm:items-end sm:justify-between"
            >
                <input type="hidden" name="school_year" x-model="selectedSchoolYearId">

                <div class="flex flex-col gap-1">
                    <p class="text-[#5E5F60] dark:text-white font-medium text-sm">School Year</p>
                    <button
                        type="button"
                        @click="openSchoolYearModal()"
                        class="inline-flex min-h-11 items-center justify-between gap-3 rounded-2xl border border-[#D7E1EA] bg-[#F9FBFD] px-4 py-2 text-left text-sm font-semibold text-[#394554] transition hover:border-primaryColor dark:border-white/10 dark:bg-bodyBg-dark dark:text-white disabled:cursor-not-allowed disabled:opacity-60"
                        :disabled="!hasSchoolYears()"
                        aria-haspopup="dialog"
                        :aria-expanded="schoolYearModalOpen ? 'true' : 'false'"
                    >
                        <span class="flex items-center gap-2">
                            <span x-text="selectedYearButtonLabel()"></span>
                            <span
                                x-show="selectedYearMetaLabel()"
                                x-cloak
                                class="rounded-full bg-[#EAF4EA] px-2 py-0.5 text-[10px] font-semibold uppercase tracking-[0.18em] text-[#1F8A46] dark:bg-[#12331E] dark:text-[#82D7A0]"
                                x-text="selectedYearMetaLabel()"
                            ></span>
                        </span>
                        <i class="icofont-simple-down text-xs text-[#8B97A7] dark:text-[#7F92A8]"></i>
                    </button>
                </div>
            </form>

            @if ($selectedSchoolYear && ! $selectedSchoolYear->is_active)
                <div class="mb-4 rounded-xl border border-[#F3D7A3] bg-[#FFF7E8] px-4 py-3 text-sm text-[#8A5A00] dark:border-[#5B4721] dark:bg-[#2B2112] dark:text-[#F2C889]">
                    Viewing announcements for SY {{ $selectedSchoolYear->label }}. All announcement actions are disabled while this school year is inactive.
                </div>
            @endif

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 w-full">
                @forelse($announcements as $announcement)
                    <div
                        class="relative h-full"
                        x-data="{ deleteOpen: false }"
                    >
                        <a
                            href="{{ route('admin.editAnnouncement', array_merge(['announcement' => $announcement->id], $announcementQuery)) }}"
                            class="block h-full overflow-hidden rounded-xl border border-primaryColor transition-all {{ $announcementsReadOnly ? '' : 'cursor-pointer hover:scale-105 hover:shadow-2xl' }}"
                        >
                            <div class="flex h-full py-4">
                                <div class="ml-4 border-l-8 border-primaryColor"></div>

                                <div class="flex w-full flex-col pl-4 pr-4">
                                    <div class="mb-1 flex items-center gap-2">
                                        <i class="icofont-megaphone text-3xl text-[#f2277e]"></i>
                                        <p class="line-clamp-1 text-xl font-semibold dark:text-white">
                                            {{ $announcement->title }}
                                        </p>
                                    </div>

                                    <p class="mb-2 text-sm text-[#808080] dark:text-gray-200">
                                        {{ \Carbon\Carbon::parse($announcement->date_posted)->format('F d, Y h:i A') }}
                                    </p>

                                    <p class="mb-2 text-sm text-[#808080] dark:text-gray-200">
                                        {{ $announcement->schoolYear ? 'SY ' . $announcement->schoolYear->label : 'No school year assigned' }}
                                    </p>

                                    <p class="line-clamp-3 text-sm text-[#5E5F60] dark:text-white">
                                        {{ strip_tags($announcement->description) }}
                                    </p>

                                    <div class="flex-grow"></div>
                                </div>
                            </div>
                        </a>

                        @if ($announcementsReadOnly)
                            <button
                                type="button"
                                disabled
                                aria-disabled="true"
                                class="absolute top-3 right-3 flex h-7 w-6 cursor-not-allowed items-center justify-center rounded bg-gray-300 opacity-70 shadow dark:bg-gray-600"
                                title="Announcement actions are disabled for inactive school years"
                            >
                                <i class="icofont-trash text-md text-white"></i>
                            </button>
                        @else
                            <button
                                type="button"
                                @click.stop.prevent="deleteOpen = true"
                                class="absolute top-3 right-3 flex h-7 w-6 items-center justify-center rounded bg-red-500 shadow transition hover:scale-110"
                                title="Delete Announcement"
                            >
                                <i class="icofont-trash text-md text-white"></i>
                            </button>

                            <div
                                x-show="deleteOpen"
                                x-cloak
                                class="fixed inset-0 z-50 flex items-center justify-center"
                            >
                                <div
                                    class="absolute inset-0 bg-black/50"
                                    @click="deleteOpen = false"
                                ></div>

                                <div
                                    class="relative w-[90%] max-w-md rounded-2xl bg-white p-6 shadow-lg dark:bg-[#1E1E1E]"
                                    x-transition
                                    @keydown.escape.window="deleteOpen = false"
                                >
                                    <div class="mb-4 flex items-start justify-between">
                                        <h2 class="text-xl font-bold text-[#5E5F60] dark:text-white">
                                            Delete Announcement
                                        </h2>

                                        <button
                                            type="button"
                                            @click="deleteOpen = false"
                                            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"
                                        >
                                            &times;
                                        </button>
                                    </div>

                                    <p class="text-sm text-[#808080] dark:text-gray-300">
                                        Are you sure you want to delete this announcement?
                                        <span class="font-semibold text-[#5E5F60] dark:text-white">
                                            "{{ $announcement->title }}"
                                        </span>
                                    </p>

                                    <div class="mt-6 flex items-center justify-end gap-3">
                                        <button
                                            type="button"
                                            @click="deleteOpen = false"
                                            class="rounded-lg border border-[#D4D4D8] px-4 py-2 text-[#5E5F60] transition hover:opacity-80 dark:border-gray-700 dark:text-white"
                                        >
                                            Cancel
                                        </button>

                                        <form
                                            action="{{ route('admin.deleteAnnouncement', array_merge(['announcement' => $announcement->id], $announcementQuery)) }}"
                                            method="POST"
                                        >
                                            @csrf
                                            @method('DELETE')

                                            <button
                                                type="submit"
                                                class="rounded-lg bg-red-600 px-4 py-2 text-white transition-all hover:scale-105"
                                            >
                                                Yes, Delete
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                @empty
                    <p class="col-span-full text-center text-gray-400 dark:text-gray-300">
                        {{ $selectedSchoolYear ? 'No announcements posted yet.' : 'Select a school year to view announcements.' }}
                    </p>
                @endforelse
            </div>

            <div x-cloak x-show="schoolYearModalOpen" class="fixed inset-0 z-[1000] flex items-center justify-center p-4" aria-hidden="false">
                <div x-show="schoolYearModalOpen" x-transition.opacity class="absolute inset-0 bg-[#0F172A]/45 backdrop-blur-[2px]" @click="closeSchoolYearModal()"></div>
                <aside x-show="schoolYearModalOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-y-3 opacity-0" x-transition:enter-end="translate-y-0 opacity-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-y-0 opacity-100" x-transition:leave-end="translate-y-3 opacity-0" class="relative z-10 flex h-[min(72vh,560px)] min-h-0 w-full max-w-[500px] flex-col rounded-[22px] border border-[#E6EDF4] bg-white p-5 shadow-2xl dark:border-white/10 dark:bg-[#0F172A]">
                    <div class="flex items-start justify-between gap-4 border-b border-[#E6EDF4] pb-4 dark:border-white/10">
                        <div>
                            <div class="flex items-center gap-2">
                                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-[#8B97A7] dark:text-[#7F92A8]">List of School Years</p>
                                <span class="inline-flex h-6 w-6 items-center justify-center text-[#2864FF] dark:text-[#8EB6FF]"><i class="icofont-ui-folder text-sm"></i></span>
                            </div>
                            <h3 class="mt-2 text-xl font-bold text-[#394554] dark:text-white">Select School Year</h3>
                        </div>
                        <div class="flex items-center gap-2">
                            <div x-show="hasSchoolYears()" x-cloak class="relative hidden sm:block">
                                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-[#8B97A7] dark:text-[#7F92A8]"><i class="icofont-search-1 text-xs"></i></span>
                                <input type="text" x-model="searchTerm" placeholder="Search year" class="h-9 w-[150px] rounded-lg border border-[#D7E1EA] bg-[#F9FBFD] pl-8 pr-3 text-xs text-[#394554] outline-none transition focus:border-primaryColor dark:border-white/10 dark:bg-[#121A29] dark:text-white">
                            </div>
                            <button type="button" @click="closeSchoolYearModal()" class="px-2 text-xl leading-none text-gray-500 transition hover:text-gray-700 dark:text-gray-300 dark:hover:text-white" aria-label="Close school year filter">&times;</button>
                        </div>
                    </div>

                    <div x-show="hasSchoolYears()" x-cloak class="mt-4 sm:hidden">
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-[#8B97A7] dark:text-[#7F92A8]"><i class="icofont-search-1 text-xs"></i></span>
                            <input type="text" x-model="searchTerm" placeholder="Search year" class="h-9 w-full rounded-lg border border-[#D7E1EA] bg-[#F9FBFD] pl-8 pr-3 text-xs text-[#394554] outline-none transition focus:border-primaryColor dark:border-white/10 dark:bg-[#121A29] dark:text-white">
                        </div>
                    </div>

                    <div class="mt-4 min-h-0 flex-1 overflow-hidden rounded-2xl border border-[#E6EDF4] bg-[#F9FBFD] p-2 dark:border-white/10 dark:bg-[#121A29]">
                        <div class="flex h-full min-h-0 flex-col">
                            <div x-show="hasSchoolYears()" x-cloak class="flex items-end justify-between gap-2 px-1 pb-0 pt-1">
                                <div class="flex items-end gap-2 overflow-x-auto [scrollbar-width:none] [-ms-overflow-style:none] [&::-webkit-scrollbar]:hidden">
                                    <button type="button" @click="selectedDecade = 'all'" class="decade-folder-tab relative -mb-px inline-flex min-w-[88px] items-center justify-center border px-3 py-2 text-xs font-semibold transition" :class="selectedDecade === 'all' ? 'z-10 border-b-[#F9FBFD] border-[#D7E1EA] bg-white text-primaryColor dark:border-white/10 dark:border-b-[#121A29] dark:bg-[#0F172A]' : 'border-transparent bg-[#EEF2F6] text-[#708096] hover:bg-white hover:text-primaryColor dark:bg-[#172234] dark:text-[#94A3B8]'">
                                        <span>All Years</span>
                                    </button>
                                    <template x-for="decade in availableDecades()" :key="decade">
                                        <button type="button" @click="selectedDecade = decade" class="decade-folder-tab relative -mb-px inline-flex min-w-[88px] items-center justify-center border px-3 py-2 text-xs font-semibold transition" :class="selectedDecade === decade ? 'z-10 border-b-[#F9FBFD] border-[#D7E1EA] bg-white text-primaryColor dark:border-white/10 dark:border-b-[#121A29] dark:bg-[#0F172A]' : 'border-transparent bg-[#EEF2F6] text-[#708096] hover:bg-white hover:text-primaryColor dark:bg-[#172234] dark:text-[#94A3B8]'">
                                            <span x-text="decadeLabel(decade)"></span>
                                        </button>
                                    </template>
                                </div>
                            </div>

                            <div class="min-h-0 flex-1 overflow-hidden rounded-2xl border border-[#E6EDF4] bg-[#F9FBFD] p-2 dark:border-white/10 dark:bg-[#121A29]" :class="hasSchoolYears() ? '-mt-px rounded-tl-none' : ''">
                                <div x-show="hasSchoolYears()" x-cloak class="mx-auto h-full max-h-[320px] min-h-0 w-full space-y-2 overflow-y-auto pr-1">
                                    <template x-for="year in filteredSchoolYears()" :key="year.id">
                                        <button type="button" @click="applySchoolYear(year.id)" class="flex min-h-[56px] w-full items-center justify-between rounded-xl border bg-white px-4 py-2.5 text-left transition dark:bg-[#0F172A]" :class="String(selectedSchoolYearId) === String(year.id) ? 'border-primaryColor bg-[#F8FBFF] dark:border-[#4B7BFF] dark:bg-[#13203A]' : (year.is_active ? 'border-[#1F8A46] bg-[#F3FBF5] dark:border-[#2E8B57] dark:bg-[#10291A]' : 'border-transparent hover:border-primaryColor hover:bg-[#F8FBFF] dark:hover:bg-[#13203A]')">
                                            <div class="min-w-0 flex-1">
                                                <p class="text-sm font-semibold text-[#394554] dark:text-white" x-text="year.label"></p>
                                            </div>
                                            <div class="ml-4 flex items-center gap-2">
                                                <span x-show="year.is_active" x-cloak class="rounded-full bg-[#EAF4EA] px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-[#1F8A46] dark:bg-[#12331E] dark:text-[#82D7A0]">Active</span>
                                                <span x-show="!year.is_active && !year.is_archived" x-cloak class="rounded-full bg-[#EEF2F6] px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-[#617184] dark:bg-[#172234] dark:text-[#94A3B8]">Inactive</span>
                                                <span x-show="year.is_archived" x-cloak class="rounded-full bg-[#EEF2F6] px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-[#617184] dark:bg-[#172234] dark:text-[#94A3B8]">Archived</span>
                                            </div>
                                        </button>
                                    </template>

                                    <div x-show="filteredSchoolYears().length === 0" x-cloak class="rounded-xl border border-dashed border-[#D7E1EA] px-4 py-6 text-center text-sm text-[#8B97A7] dark:border-white/10 dark:text-[#7F92A8]">No school years found for this decade.</div>
                                </div>

                                <div x-show="!hasSchoolYears()" x-cloak class="flex h-full items-center justify-center">
                                    <div class="rounded-xl border border-dashed border-[#D7E1EA] px-4 py-6 text-center text-sm text-[#8B97A7] dark:border-white/10 dark:text-[#7F92A8]">No school years available.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </aside>
            </div>

            <div class="mt-6 flex flex-col items-center justify-between gap-4 sm:flex-row">
                <div>
                    {{ $announcements->links() }}
                </div>

                <div class="flex justify-end">
                    @if ($selectedSchoolYear && $activeSchoolYear && (int) $selectedSchoolYear->id === (int) $activeSchoolYear->id)
                        <a
                            href="{{ route('admin.addAnnouncement', $announcementQuery) }}"
                            class="flex items-center rounded-lg bg-[#E1D6FF] px-3 py-2 text-[#5F2DED] transition-all hover:scale-105 dark:bg-[#5F2DED] dark:text-white"
                        >
                            <i class="icofont-plus mr-2"></i> Add Announcement
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function announcementManagementFilters(config) {
        return {
            schoolYearModalOpen: false,
            searchTerm: '',
            schoolYears: Array.isArray(config.schoolYears) ? config.schoolYears : [],
            selectedSchoolYearId: config.selectedSchoolYearId ? String(config.selectedSchoolYearId) : '',
            selectedDecade: 'all',

            hasSchoolYears() {
                return this.schoolYears.length > 0;
            },

            availableDecades() {
                return [...new Set(this.schoolYears.map((year) => Math.floor(Number(year.start) / 10) * 10))]
                    .sort((left, right) => left - right);
            },

            decadeLabel(decade) {
                return `${decade}s`;
            },

            openSchoolYearModal() {
                if (!this.hasSchoolYears()) {
                    return;
                }

                this.selectedDecade = 'all';
                this.searchTerm = '';
                this.schoolYearModalOpen = true;
            },

            closeSchoolYearModal() {
                this.schoolYearModalOpen = false;
            },

            filteredSchoolYears() {
                const search = String(this.searchTerm || '').trim().toLowerCase();

                return this.schoolYears.filter((year) => {
                    const yearDecade = Math.floor(Number(year.start) / 10) * 10;
                    const matchesDecade = this.selectedDecade === 'all' || yearDecade === this.selectedDecade;
                    const matchesSearch = search === '' || String(year.label || '').toLowerCase().includes(search);

                    return matchesDecade && matchesSearch;
                });
            },

            selectedYear() {
                return this.schoolYears.find((year) => String(year.id) === String(this.selectedSchoolYearId)) || null;
            },

            selectedYearButtonLabel() {
                const selectedYear = this.selectedYear();

                if (selectedYear) {
                    return selectedYear.label;
                }

                return this.hasSchoolYears() ? 'Select school year' : 'No school years available';
            },

            selectedYearMetaLabel() {
                const selectedYear = this.selectedYear();
                if (!selectedYear) {
                    return '';
                }

                if (selectedYear.is_active) {
                    return 'Active';
                }

                if (!selectedYear.is_archived) {
                    return 'Inactive';
                }

                if (selectedYear.is_archived) {
                    return 'Archived';
                }

                return '';
            },

            applySchoolYear(yearId) {
                this.selectedSchoolYearId = String(yearId);
                this.closeSchoolYearModal();
                this.submitFilters({ school_year: this.selectedSchoolYearId });
            },

            submitFilters(overrides = {}) {
                if (!this.$refs.filtersForm) {
                    return;
                }

                const form = this.$refs.filtersForm;
                const params = new URLSearchParams(new FormData(form));

                Object.entries(overrides).forEach(([key, value]) => {
                    if (value === null || value === undefined || String(value).trim() === '') {
                        params.delete(key);
                        return;
                    }

                    params.set(key, String(value));
                });

                [...params.entries()].forEach(([key, value]) => {
                    if (String(value).trim() === '') {
                        params.delete(key);
                    }
                });

                const query = params.toString();
                window.location.assign(query ? `${form.action}?${query}` : form.action);
            },
        };
    }
</script>
@endsection
