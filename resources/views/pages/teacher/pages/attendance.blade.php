@extends('pages.teacher.layout')

@section('content')
@php
    $isAuditOnly = (bool) ($isAuditOnly ?? false);
@endphp
<div class="bg-[#FFFFFF] dark:bg-[#1E1E2D] dark:border-[#5F2DED] shadow-lg border p-8 rounded-lg"> 

    {{-- Header --}}
    <div class="border-b border-[#D4D4D8] dark:border-[#5F2DED]">
        <h1 class="text-3xl font-bold text-[#5E5F60] dark:text-white">
            {{ $subject->subject_name }}
        </h1>
        <p class="mb-4 text-[#5E5F60] dark:text-white">
            Section {{ $section->section_rank }} - {{ $section->section_name }}
        </p>

        <x-teacher-nav 
            active="attendance" 
            :sectionId="$section->id" 
            :subjectId="$subject->id"
        />

        <x-attendance-nav 
            :section="$section"
            :subject="$subject"
        />
    </div>

    @if($isAuditOnly)
        <div class="mt-4 rounded-lg border border-[#F3C27A] bg-[#FFF7ED] px-4 py-3 text-sm font-medium text-[#9A3412] dark:border-[#7C4A03] dark:bg-[#31210D] dark:text-[#F6C98A]">
            Teacher audit mode is enabled. Attendance records are read only.
        </div>
    @endif

    {{-- Grading Period Dropdown + Attendance --}}
    <div x-data="{ selectedPeriod: '1st' }" class="mt-6">

        {{-- Dropdown --}}
        <div class="flex justify-end mb-4">
    <select
        x-model="selectedPeriod"
        class="
            w-full
            sm:w-56
            md:w-40
            px-4 sm:px-6
            py-2
            border
            rounded-lg
            bg-white
            dark:bg-[#2A2A3C]
            dark:border-[#5F2DED]
            dark:text-white
        "
    >
        <option value="1st">1st Quarter</option>
        <option value="2nd">2nd Quarter</option>
        <option value="3rd">3rd Quarter</option>
        <option value="4th">4th Quarter</option>
    </select>
</div>


        {{-- Attendance Cards --}}
        @foreach($gradingPeriods as $periodKey => $period)
            <div x-show="selectedPeriod === '{{ $periodKey }}'" x-transition>

                <p class="font-semibold text-[#373737] text-2xl mb-4 dark:text-white">
                    {{ $period }}
                </p>

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 w-full">
                    @forelse($groupedAttendance[$periodKey] ?? [] as $attendance)
                        <a href="{{ route('teacher.attendance.record', $attendance->id) }}">
                            <div class="border border-primaryColor rounded-xl hover:shadow-2xl hover:scale-105 transition-all cursor-pointer">
                                <div class="py-4 flex items-center">

                                    <div class="border-l-8 border-primaryColor ml-4 py-12"></div>

                                    <div class="flex flex-col w-[80%] pl-4">
                                        <div class="flex items-center gap-2">
                                            <i class="icofont-calendar text-3xl text-[#f2277e]"></i>
                                            <p class="text-xl font-semibold dark:text-white">
                                                {{ $attendance->day }}
                                            </p>
                                        </div>
                                        <p class="text-sm text-[#808080] dark:text-white">
                                            {{ \Carbon\Carbon::parse($attendance->date)->format('F d, Y') }}
                                        </p>
                                    </div>

                                    <div class="flex w-[20%] justify-end pr-4 mb-14">
                                        <div class="w-10 h-10 bg-[#e1d6ff] rounded-full flex items-center justify-center">
                                            <i class="icofont-list text-lg text-primaryColor"></i>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </a>
                    @empty
                        <p class="text-gray-400 dark:text-gray-300 col-span-full">
                            No attendance added yet.
                        </p>
                    @endforelse
                </div>

            </div>
        @endforeach
    </div>

    @unless($isAuditOnly)
        <div
            class="w-full mt-6 flex justify-end"
            x-data="attendanceForm({
                existingDates: @js($existingAttendanceDates),
                hasErrors: @js($errors->any()),
                oldDate: @js(old('date')),
                oldPeriod: @js(old('grading_period'))
            })"
        >

            <button
                type="button"
                @click="openModal"
                data-action="add-attendance"
                class="bg-[#E1D6FF] dark:bg-[#5F2DED] text-[#5F2DED] dark:text-white px-3 py-2 rounded-lg flex items-center hover:scale-105 transition-all"
            >
                <i class="icofont-plus mr-2"></i> Add Attendance
            </button>

        {{-- Modal --}}
        <div
            x-show="open"
            class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50"
            x-transition
        >
            <div class="bg-white dark:bg-[#1E1E2D] rounded-lg shadow-lg w-96 p-6 relative">
                <h2 class="text-xl font-semibold dark:text-white mb-4">
                    Add Attendance
                </h2>

                <form
                    action="{{ route('teacher.attendance.store', [$section->id, $subject->id]) }}"
                    method="POST"
                    @submit="handleSubmit"
                >
                    @csrf

                    {{-- Date --}}
                    <div class="mb-4">
                        <label class="block mb-1 dark:text-white">Date</label>
                        <input
                            type="date"
                            name="date"
                            x-model="date"
                            @input="validateDate"
                            class="w-full p-2 border rounded-lg dark:bg-[#2A2A3C] dark:border-[#5F2DED] dark:text-white"
                        >
                        <p x-show="errors.date" class="text-sm text-red-500 mt-1" x-text="errors.date"></p>
                        @error('date')
                            <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Grading Period --}}
                    <div class="mb-4">
                        <label class="block mb-1 dark:text-white">Grading Period</label>
                        <select
                            name="grading_period"
                            x-model="period"
                            @change="validatePeriod"
                            class="w-full p-2 border rounded-lg dark:bg-[#2A2A3C] dark:border-[#5F2DED] dark:text-white"
                        >
                            <option value="">Select period</option>
                            <option value="1st">1st Quarter</option>
                            <option value="2nd">2nd Quarter</option>
                            <option value="3rd">3rd Quarter</option>
                            <option value="4th">4th Quarter</option>
                        </select>
                        <p x-show="errors.period" class="text-sm text-red-500 mt-1" x-text="errors.period"></p>
                        @error('grading_period')
                            <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Actions --}}
                    <div class="flex justify-end gap-2">
                        <button
                            type="button"
                            @click="closeModal"
                            :disabled="loading"
                            class="px-4 py-2 border rounded-lg dark:text-white"
                        >
                            Cancel
                        </button>

                        <button
                            type="submit"
                            :disabled="!isValid || loading"
                            class="px-4 py-2 rounded-lg bg-[#5F2DED] text-white transition-all
                                   disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <span x-show="!loading">Save</span>
                            <span x-show="loading" class="flex items-center gap-2">
                                <i class="icofont-spinner-alt-2 animate-spin"></i>
                                Saving…
                            </span>
                        </button>
                    </div>
                </form>

                <button
                    @click="closeModal"
                    class="absolute top-2 right-2 text-gray-500 dark:text-gray-300"
                >
                    &times;
                </button>
            </div>
        </div>
        </div>
    @endunless
</div>
@endsection

{{-- Alpine logic --}}
<script>
function attendanceForm({ existingDates, hasErrors, oldDate, oldPeriod }) {
    return {
        open: false,
        loading: false,

        date: oldDate ?? '',
        period: oldPeriod ?? '',

        errors: {
            date: '',
            period: ''
        },

        init() {
            if (hasErrors) {
                this.open = true;
            }
        },

        openModal() {
            this.open = true;
        },

        closeModal() {
            this.open = false;
            this.loading = false;
        },

        validateDate() {
            if (!this.date) {
                this.errors.date = 'Date is required.';
            } else if (existingDates.includes(this.date)) {
                this.errors.date = 'Attendance for this date already exists.';
            } else {
                this.errors.date = '';
            }
        },

        validatePeriod() {
            if (!this.period) {
                this.errors.period = 'Grading period is required.';
            } else {
                this.errors.period = '';
            }
        },

        get isValid() {
            return !this.errors.date && !this.errors.period && this.date && this.period;
        },

        handleSubmit() {
            this.validateDate();
            this.validatePeriod();
            if (!this.isValid) return;
            this.loading = true;
        }
    }
}
</script>
