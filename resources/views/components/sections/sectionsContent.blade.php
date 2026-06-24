@props([
    'columns',
    'sections',
    'grade_level',
    'schoolYears' => collect(),
    'selectedSchoolYear' => null,
    'activeSchoolYear' => null,
])

@php
    $sectionQuery = request()->only(['grade_level', 'school_year', 'search']);
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

<div x-data="sectionManagementFilters({
        schoolYears: @js($schoolYearFilterPayload),
        selectedSchoolYearId: @js(optional($selectedSchoolYear)->id),
    })"
    class="flex-1 flex flex-col p-6 sm:p-10 h-auto bg-whiteColor shadow-lg dark:bg-darkblack rounded-2xl transition-all
        duration-300 dark:ring-2 dark:ring-blue-500/20 dark:ring-offset-0">
    
    <!-- Page header -->
    <div id="page-header" class="mb-4">
        <h1 id="page-title" class="text-2xl sm:text-3xl font-bold text-[#5E5F60] dark:text-white">Class Sections Management</h1>
        <p id="page-subtitle" class="text-[#ABB7C2] dark:text-white">Control Sections Information</p>
    </div>

    <!-- Filters & Search -->
    <form method="GET" action="{{ route('admin.sections') }}" x-ref="filtersForm" @submit.prevent="submitFilters()"
        class="flex flex-col gap-4 pb-4 lg:flex-row lg:items-end lg:justify-between">
        <input type="hidden" name="school_year" x-model="selectedSchoolYearId">

        <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-end">
            <div class="flex flex-col gap-1">
                <p class="text-[#5E5F60] dark:text-white font-medium text-sm">Grade Level</p>
                <select id="grade_level" name="grade_level" @change="submitFilters()"
                    class="border border-gray-300 dark:border-gray-50 rounded-lg cursor-pointer text-sm px-3 pr-8 py-2 dark:bg-bodyBg-dark dark:text-white">
                    @for ($i = 1; $i <= 12; $i++)
                        <option value="{{ $i }}" {{ $grade_level == $i ? 'selected' : '' }}>Grade {{ $i }}</option>
                    @endfor
                </select>
            </div>

            <div class="flex flex-col gap-1">
                <p class="text-[#5E5F60] dark:text-white font-medium text-sm">School Year</p>
                <button type="button"
                    @click="openSchoolYearModal()"
                    class="inline-flex min-h-11 items-center justify-between gap-3 rounded-2xl border border-[#D7E1EA] bg-[#F9FBFD] px-4 py-2 text-left text-sm font-semibold text-[#394554] transition hover:border-primaryColor dark:border-white/10 dark:bg-bodyBg-dark dark:text-white disabled:cursor-not-allowed disabled:opacity-60"
                    :disabled="!hasSchoolYears()"
                    aria-haspopup="dialog"
                    :aria-expanded="schoolYearModalOpen ? 'true' : 'false'">
                    <span class="flex items-center gap-2">
                        <span x-text="selectedYearButtonLabel()"></span>
                        <span x-show="selectedYearMetaLabel()" x-cloak class="rounded-full bg-[#EAF4EA] px-2 py-0.5 text-[10px] font-semibold uppercase tracking-[0.18em] text-[#1F8A46] dark:bg-[#12331E] dark:text-[#82D7A0]" x-text="selectedYearMetaLabel()"></span>
                    </span>
                    <i class="icofont-simple-down text-xs text-[#8B97A7] dark:text-[#7F92A8]"></i>
                </button>
            </div>
        </div>

        <div class="w-full lg:w-[30%]">
            <div class="relative w-full">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                    <i class="icofont-search-1"></i>
                </span>
                <input
                    type="text"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Search by section name..."
                    class="w-full pl-10 pr-3 py-2 rounded-xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-[#7F6994] text-sm
                    dark:bg-bodyBg-dark dark:text-white dark:focus:ring-blue"
                />
            </div>
        </div>
    </form>

    <!-- Grade title -->
    <div class="mt-2">
        <p class="text-lg text-[#5E5F60] dark:text-white font-semibold">Grade {{ $grade_level }}</p>
    </div>

    <!-- Section Cards -->
    <div class="py-2 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
        @forelse ($sections as $section)
            <a href="{{ route('admin.editsection', array_merge(['id' => $section->id], $sectionQuery)) }}"
               class="w-full h-40 hover:scale-105 transition-transform duration-200 cursor-pointer rounded-md border-[1.5px] border-[#578ABF] overflow-hidden flex flex-col sm:flex-row">
                
                <!-- Left strip -->
                <div class="bg-[#578ABF] w-full sm:w-2 h-2 sm:h-full"></div>

                <!-- Section name -->
                <div class="flex-1 flex flex-col items-center justify-center px-3">
                    <p class="text-sm dark:text-white">Section {{ $section->section_rank }}</p>
                    <p class="text-lg font-bold dark:text-white text-center truncate">{{ $section->section_name }}</p>
                </div>
            </a>
        @empty
            <div class="w-full h-32 rounded-md border-2 border-[#ABB7C2] overflow-hidden flex items-center justify-center">
                <p class="text-sm font-semibold text-[#818181] dark:text-white text-center">
                    {{ $selectedSchoolYear ? 'No sections found' : 'Select a school year to view sections' }}
                </p>
            </div>
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

    <!-- Add Section & Pagination -->
    <div class="flex flex-col sm:flex-row justify-between items-center mt-6 gap-4">
        <!-- Add Section -->
        @if ($selectedSchoolYear && $activeSchoolYear && (int) $selectedSchoolYear->id === (int) $activeSchoolYear->id)
            <a href="{{ route('admin.addsection', ['grade_level' => $grade_level]) }}"
               class="p-3 text-sm gap-2 px-10 flex flex-row justify-center items-center text-[#5F2DED] font-[450] border border-[#5F2DED] rounded-xl
               dark:text-[#477eec] dark:border-[#477eec] hover:bg-primaryColor dark:hover:bg-[#477eec] hover:text-white hover:dark:bg-[#244890] hover:dark:text-white">
                <i class="icofont-ui-add text-sm"></i>
                <p>Add Section</p>
            </a>
        @endif

        <!-- Pagination -->
        <div class="flex-1 flex justify-end">
            <nav class="flex justify-center">
                {{ $sections->links() }}
            </nav>
        </div>
    </div>

    <!-- Modal -->
    <x-lmsmodal/>
    <div id="confirmDeleteStudent" class="hidden">
        <div class="flex justify-center items-center flex-col w-full">
            <p class="dark:text-white">Are you sure you want to delete this student?</p>
            <div class="flex flex-col sm:flex-row gap-4 mt-4 w-full">
                <button data-close="lmsmodal" class="px-4 flex-1 py-2 border dark:text-white dark:hover:text-black border-[#ABB7C2] hover:bg-gray-100 rounded">Cancel</button>
                <button id="confirmDeleteStudent" class="px-4 py-2 flex-1 bg-red-500 hover:bg-red-300 text-white rounded">Confirm</button>
            </div>
        </div>
    </div>
</div>

<script>
    function sectionManagementFilters(config) {
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
