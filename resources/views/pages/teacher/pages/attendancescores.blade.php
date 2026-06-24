@extends('pages.teacher.layout')

@section('content')
@php
    $isAuditOnly = (bool) ($isAuditOnly ?? false);
@endphp
<div class="bg-[#FFFFFF] dark:bg-[#1E1E2D] dark:border-[#5F2DED] shadow-lg border p-4 sm:p-8 rounded-lg"> 

    {{-- HEADER --}}
    <div class="border-b border-[#D4D4D8] dark:border-[#5F2DED] mb-4">
        <h1 class="text-2xl sm:text-3xl font-bold text-[#5E5F60] dark:text-white">
            {{ $subject->subject_name }}
        </h1>

        <p class="mb-4 text-sm sm:text-base text-[#5E5F60] dark:text-white">
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
        <div class="mb-6 rounded-lg border border-[#F3C27A] bg-[#FFF7ED] px-4 py-3 text-sm font-medium text-[#9A3412] dark:border-[#7C4A03] dark:bg-[#31210D] dark:text-[#F6C98A]">
            Teacher audit mode is enabled. Attendance grouping is read only.
        </div>
    @endif

    {{-- FILTERS --}}
    <div class="flex flex-col lg:flex-row lg:justify-between lg:items-center gap-4 mt-4 mb-6">

        {{-- GRADING PERIOD --}}
        <div class="flex flex-col sm:flex-row sm:items-center gap-2">
            <label class="font-medium text-sm dark:text-white whitespace-nowrap flex items-center h-[40px]">
                Grading Period:
            </label>

            <form method="GET" class="w-full sm:w-auto">
                <select
                    name="grading_period"
                    onchange="this.form.submit()"
                    class="w-full sm:w-auto h-[40px] border dark:bg-[#1E1E2D] dark:text-white rounded-lg px-6 text-sm"
                >
                    @foreach (['1st','2nd','3rd','4th'] as $period)
                        <option value="{{ $period }}" @selected($gradingPeriod == $period)>
                            {{ $period }} Grading
                        </option>
                    @endforeach
                </select>
            </form>
        </div>

        {{-- ATTENDANCE GROUP --}}
        <div class="flex flex-col gap-1 w-full sm:w-auto">
            <div class="flex flex-col sm:flex-row sm:items-center gap-2">
                <p class="font-medium text-sm dark:text-white whitespace-nowrap">
                    Attendance Group:
                </p>

                <select
                    id="attendanceGroup"
                    name="subject_grading_id"
                    form="saveAttendanceForm"
                    @disabled($isAuditOnly)
                    class="w-full sm:w-auto border dark:bg-[#1E1E2D] dark:text-white rounded-lg px-4 py-2 text-sm"
                    required
                    onchange="clearAttendanceGroupError()"
                >
                    <option value="">Select Group</option>

                    @foreach ($classworkTypes as $type)
                        <option
                            value="{{ $type->id }}"
                            @selected(
                                $selectedAttendanceType &&
                                $selectedAttendanceType->subject_grading_id == $type->id
                            )
                        >
                            {{ $type->classwork_type }} ({{ $type->percentage }}%)
                        </option>
                    @endforeach
                </select>
            </div>

            <p id="attendanceGroupError"
               class="hidden text-sm text-red-500">
                Please select an attendance group before saving.
            </p>
        </div>
    </div>

    {{-- TABLE --}}
    <div class="overflow-x-auto">
        <table class="min-w-full border dark:border-[#5F2DED] rounded-lg text-sm sm:text-base">
            <thead class="bg-[#F0F9FF] dark:bg-[#0B1220]">
                <tr>
                    <th class="p-3 text-left dark:text-white">Student Name</th>
                    <th class="p-3 text-center text-green-600">Present</th>
                    <th class="p-3 text-center text-[#fca127]">Late</th>
                    <th class="p-3 text-center text-purple-500">Excused</th>
                    <th class="p-3 text-center text-red-500">Absent</th>
                    <th class="p-3 text-center text-[#5F2DED]">Attendance %</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($studentStats as $s)
                @php
                    $totalDays =
                        $s['present'] +
                        $s['late'] +
                        $s['excused'] +
                        $s['absent'];

                    $earnedDays =
                        ($s['present'] * 1) +
                        ($s['late'] * 0.5) +
                        ($s['excused'] * 1);
                @endphp

                <tr class="border-t dark:border-[#5F2DED] dark:text-white">
                    <td class="p-3">{{ $s['name'] }}</td>
                    <td class="p-3 text-center">{{ $s['present'] }}</td>
                    <td class="p-3 text-center">{{ $s['late'] }}</td>
                    <td class="p-3 text-center">{{ $s['excused'] }}</td>
                    <td class="p-3 text-center">{{ $s['absent'] }}</td>
                    <td class="p-3 text-center font-bold text-[#5F2DED]">
                        {{ round($earnedDays, 2) }} / {{ $totalDays }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="p-6 text-center text-gray-500">
                        No attendance records found.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- SAVE --}}
    @unless($isAuditOnly)
        <div class="mt-4 flex justify-end">
            <button
                @if (empty($studentStats)) disabled @endif
                onclick="handleSaveClick()"
                class="w-full sm:w-auto px-6 py-2 rounded-lg text-white
                       {{ empty($studentStats)
                            ? 'bg-gray-400 cursor-not-allowed opacity-60'
                            : 'bg-[#5F2DED] hover:opacity-90' }}">
                Save Attendance Group
            </button>
        </div>
    @endunless

</div>
@endsection


{{-- SAVE MODAL --}}
@unless($isAuditOnly)
<div id="saveModal"
     class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50 px-4">

    <div class="bg-white dark:bg-[#1E1E2D] p-6 rounded-lg w-full max-w-md">
        <h2 class="text-lg font-bold mb-4 dark:text-white">
            Confirm Save
        </h2>

        <form id="saveAttendanceForm"
              method="POST"
              action="{{ route('teacher.attendance.scores.store', [$section->id, $subject->id]) }}">
            @csrf

            <input type="hidden" name="grading_period" value="{{ $gradingPeriod }}">

            <p class="text-sm mb-4 dark:text-gray-300">
                This will assign attendance to the selected grading group.
            </p>

            <div class="flex flex-col sm:flex-row justify-end gap-2">
                <button
                    type="button"
                    onclick="closeSaveModal()"
                    class="px-4 py-2 border rounded dark:text-white w-full sm:w-auto">
                    Cancel
                </button>

                <button
                    type="submit"
                    class="px-4 py-2 bg-[#5F2DED] text-white rounded w-full sm:w-auto">
                    Confirm Save
                </button>
            </div>
        </form>
    </div>
</div>
@endunless


{{-- JS --}}
<script>
function handleSaveClick() {
    const select = document.getElementById('attendanceGroup');
    const error = document.getElementById('attendanceGroupError');

    if (!select.value) {
        error.classList.remove('hidden');
        select.classList.add('border-red-500');
        return;
    }

    document.getElementById('saveModal').classList.remove('hidden');
}

function clearAttendanceGroupError() {
    document.getElementById('attendanceGroupError').classList.add('hidden');
    document.getElementById('attendanceGroup').classList.remove('border-red-500');
}

function closeSaveModal() {
    document.getElementById('saveModal').classList.add('hidden');
}
</script>
