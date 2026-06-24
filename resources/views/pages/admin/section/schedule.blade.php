@extends('layouts.updatesection-layout')

@section('content')
<p class="font-medium text-xl sm:text-2xl dark:text-white">
    Class Schedule
</p>

<p class="text-[#ABB7C2] dark:text-gray-300 text-xs sm:text-sm mb-6">
    Grade {{ $section->grade_level }} – Section {{ $section->section_rank }}
</p>

@php
    $weekDays = ['Mon','Tue','Wed','Thu','Fri'];

    $scheduledSubjects = $subjects
        ->filter(fn ($s) => $s->pivot->start_time && $s->pivot->end_time)
        ->sortBy(fn ($s) => $s->pivot->start_time)
        ->values();

    $existingSchedules = $scheduledSubjects->map(function ($s) {
        return [
            'subject_id' => $s->id,
            'days'  => json_decode($s->pivot->days ?? '[]'),
            'start' => $s->pivot->start_time,
            'end'   => $s->pivot->end_time,
        ];
    })->values();
@endphp

{{-- ================= TIMETABLE ================= --}}
<div class="overflow-x-auto">
    <table class="min-w-full border border-gray-200 dark:border-gray-700 text-sm">
        <thead class="bg-[#F0F9FF] dark:bg-[#0B1220] dark:text-white sticky top-0 z-10">
            <tr>
                <th class="p-2 sm:p-3 border dark:border-gray-700 whitespace-nowrap">Time</th>
                <th class="p-2 sm:p-3 border dark:border-gray-700 whitespace-nowrap">Days</th>
                <th class="p-2 sm:p-3 border dark:border-gray-700 whitespace-nowrap">Subject</th>
                <th class="p-2 sm:p-3 border dark:border-gray-700 whitespace-nowrap">Teacher</th>
                <th class="p-2 sm:p-3 border dark:border-gray-700 text-center whitespace-nowrap">Actions</th>
            </tr>
        </thead>

        <tbody>
            @forelse($scheduledSubjects as $subject)
                @php
                    $daysText = implode(', ', json_decode($subject->pivot->days ?? '[]'));
                @endphp

                <tr class="odd:bg-white even:bg-[#F9FAFB]
                           dark:odd:bg-bodyBg-dark dark:even:bg-[#1e214f]">

                    <td class="p-2 sm:p-3 border dark:border-gray-700 dark:text-white font-medium whitespace-nowrap">
                        {{ \Carbon\Carbon::parse($subject->pivot->start_time)->format('h:i A') }}
                        –
                        {{ \Carbon\Carbon::parse($subject->pivot->end_time)->format('h:i A') }}
                    </td>

                    <td class="p-2 sm:p-3 border dark:border-gray-700 text-gray-600 dark:text-gray-300">
                        {{ $daysText }}
                    </td>

                    <td class="p-2 sm:p-3 border font-semibold dark:border-gray-700 text-[#5F2DED] dark:text-[#8B7CFF]">
                        {{ $subject->subject_name }}
                    </td>

                    <td class="p-2 sm:p-3 border dark:border-gray-700 dark:text-white">
                        @if($subject->teachers->isNotEmpty())
                            {{ $subject->teachers
                                ->unique('id')
                                ->map(fn ($t) => $t->first_name . ' ' . $t->last_name)
                                ->join(', ')
                            }}

                        @else
                            <span class="italic text-gray-400">No teacher</span>
                        @endif
                    </td>

                    <td class="p-2 sm:p-3 border dark:border-gray-700">
                        <div class="flex justify-center gap-2 flex-col sm:flex-row">
                            @if($subject->teachers->isNotEmpty())
                            <button
                                onclick="openAssignModal(
                                    {{ $subject->id }},
                                    '{{ $subject->pivot->start_time }}',
                                    '{{ $subject->pivot->end_time }}',
                                    {{ json_encode(json_decode($subject->pivot->days ?? '[]')) }}
                                )"
                                class="text-primaryColor hover:underline text-sm">
                                Edit
                            </button>
                            @else
                                <button
                                    disabled
                                    class="text-gray-400 cursor-not-allowed text-sm"
                                    title="Assign a teacher first">
                                    Edit
                                </button>
                            @endif


                            <button
                                onclick="openRemoveModal({{ $subject->id }})"
                                class="text-red-500 dark:text-red-400 hover:underline text-sm">
                                Remove
                            </button>

                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5"
                        class="p-6 text-center text-gray-400 italic">
                        No schedule yet
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>



{{-- ================= UNSCHEDULED SUBJECTS ================= --}}
@php
    $unscheduledSubjects = $subjects->filter(fn ($s) =>
        !$s->pivot->start_time && !$s->pivot->end_time
    );
@endphp

<div class="mt-10">
    <h3 class="text-lg font-semibold dark:text-white mb-3">
        Unscheduled Subjects
    </h3>

    @if($unscheduledSubjects->isEmpty())
        <p class="text-gray-400 italic">
            All subjects are scheduled.
        </p>
    @else
        <div class="space-y-3">
            @foreach($unscheduledSubjects as $subject)
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between
                            gap-3
                            p-4 border rounded
                            bg-white dark:bg-bodyBg-dark
                            dark:border-gray-700">

                    <div>
                        <p class="font-medium dark:text-white">
                            {{ $subject->subject_name }}
                        </p>

                        @if($subject->teachers->isNotEmpty())
                            <p class="text-xs text-gray-500">
                                {{ $subject->teachers
                                    ->unique('id')
                                    ->map(fn ($t) => $t->first_name . ' ' . $t->last_name)
                                    ->join(', ')
                                }}


                            </p>
                        @endif
                    </div>

                    @if($subject->teachers->isNotEmpty())
                    <button
                        onclick="openAssignModal({{ $subject->id }})"
                        class="w-full sm:w-auto px-4 py-2 text-sm
                            bg-[#5F2DED] text-white rounded
                            hover:bg-opacity-90">
                        Assign Schedule
                    </button>
                @else
                    <button
                        disabled
                        class="w-full sm:w-auto px-4 py-2 text-sm
                            bg-gray-300 text-gray-600 rounded
                            cursor-not-allowed"
                        title="Assign a teacher first">
                        Assign Schedule
                    </button>
                @endif

                </div>
            @endforeach
        </div>
    @endif
</div>
<div class="mt-2"> 
    <p class="text-xs text-[#373737] dark:text-white">Note: You cannot assign a schedule for subjects that doesn't have a teacher assigned yet.</p>
</div>
{{-- ================= REMOVE CONFIRM MODAL ================= --}}
<div id="removeModal"
     class="fixed inset-0 bg-black bg-opacity-40 hidden items-center justify-center z-50">
    <div class="bg-white dark:bg-bodyBg-dark p-6 rounded-2xl shadow-lg
                w-full max-w-md mx-4">

        <h2 class="text-lg font-semibold dark:text-white mb-2">
            Remove Schedule
        </h2>

        <p class="text-sm text-gray-600 dark:text-gray-300 mb-6">
            Are you sure you want to remove this subject’s schedule?
            This action cannot be undone.
        </p>

        <div class="flex gap-3">
            <button type="button"
                    onclick="closeRemoveModal()"
                    class="flex-1 border py-2 rounded
                           dark:border-gray-600
                           dark:text-gray-200
                           hover:bg-gray-100 dark:hover:bg-gray-700
                           transition-colors">
                Cancel
            </button>

            <button type="button"
                    onclick="confirmRemoveSchedule(this)"
                    class="flex-1 bg-red-600 text-white py-2 rounded
                           hover:bg-red-700 transition-colors">
                Remove
            </button>
        </div>
    </div>
</div>

    {{-- ================= ASSIGN / EDIT MODAL ================= --}}
    <div id="assignModal"
        class="fixed inset-0 bg-black bg-opacity-40 hidden items-center justify-center z-50">
        <div class="bg-white dark:bg-bodyBg-dark p-6 rounded-2xl shadow-lg
                w-full max-w-lg mx-4">


            <h2 class="text-xl font-semibold mb-4 dark:text-white">
                Assign Schedule
            </h2>

            <form id="assignForm" method="POST">
                @csrf

                <p id="scheduleError"
                class="hidden text-red-500 dark:text-red-400 text-sm mb-3 text-center"></p>

                <div class="mb-4">
                    <label class="block mb-1 font-medium dark:text-white">Days</label>
                    <div class="grid grid-cols-5 gap-2">
                        @foreach($weekDays as $day)
                            <label class="flex items-center gap-1 text-sm dark:text-gray-200">
                                <input type="checkbox"
                                    name="days[]"
                                    value="{{ $day }}"
                                    class="day-checkbox accent-[#5F2DED] bg-transparent ">
                                {{ $day }}
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="mb-4">
                    <label class="block mb-1 dark:text-white">Start Time</label>
                    <input type="time" name="start_time" id="startTime"
                        class="w-full border rounded p-2
                                bg-transparent 
                                text-black dark:text-white
                                border-gray-300 dark:border-gray-600"
                        required>
                </div>

                <div class="mb-4">
                    <label class="block mb-1 dark:text-white">End Time</label>
                    <input type="time" name="end_time" id="endTime"
                        class="w-full border rounded p-2
                                bg-transparent 
                                text-black dark:text-white
                                border-gray-300 dark:border-gray-600"
                        required>
                </div>

                <div class="flex gap-3">
                    <button type="button"
                        onclick="closeAssignModal()"
                        class="flex-1 border py-2 rounded
                            dark:border-gray-600
                            dark:text-gray-200
                            hover:bg-gray-100
                            dark:hover:bg-gray-700
                            transition-colors">

                        Cancel
                    </button>

                    <button type="submit"
            id="saveBtn"
            class="flex-1 bg-[#5F2DED] text-white py-2 rounded
                hover:bg-opacity-90
                disabled:opacity-50
                disabled:cursor-not-allowed
                disabled:hover:bg-opacity-100">
        Save
    </button>



            </div>
        </form>
    </div>
</div>

{{-- ================= FAIL MODAL ================= --}}
<div id="failModal"
     class="fixed inset-0 bg-black bg-opacity-40 hidden items-center justify-center z-[60]">
    <div class="bg-white dark:bg-bodyBg-dark p-6 rounded-2xl shadow-lg
                w-full max-w-md mx-4 text-center">

        <h2 class="text-lg font-semibold text-red-600 dark:text-red-400 mb-3">
            Schedule Conflict
        </h2>

        <p id="failMessage"
           class="text-sm text-gray-700 dark:text-gray-300 mb-6"></p>

        <button
            onclick="closeFailModal()"
            class="w-full bg-[#5F2DED] text-white py-2 rounded">
            OK
        </button>
    </div>
</div>




<script>
window.existingSchedules = @json($existingSchedules);
</script>

<script>
const modal = document.getElementById('assignModal');
const form = document.getElementById('assignForm');
const startInput = document.getElementById('startTime');
const endInput = document.getElementById('endTime');
const errorBox = document.getElementById('scheduleError');
const saveBtn = document.getElementById('saveBtn');
const dayCheckboxes = document.querySelectorAll('.day-checkbox');

let currentSubjectId = null;

function openAssignModal(subjectId = null, start = '', end = '', days = []) {
    currentSubjectId = subjectId;
    form.action = `/admin/sections/{{ $section->id }}/subjects/${subjectId}/schedule`;

    startInput.value = start;
    endInput.value = end;

    dayCheckboxes.forEach(cb => cb.checked = days.includes(cb.value));

    errorBox.classList.add('hidden');
    saveBtn.disabled = true; // 👈 IMPORTANT
    modal.classList.replace('hidden','flex');

    validateSchedule(); // 👈 run immediately
}


function closeAssignModal() {
    modal.classList.replace('flex','hidden');
    form.reset();
}

function toMinutes(time) {
    const [h, m] = time.split(':').map(Number);
    return h * 60 + m;
}

function hasConflict(start, end, selectedDays) {
    const newStart = toMinutes(start);
    const newEnd = toMinutes(end);

    return window.existingSchedules.some(s => {
        if (s.subject_id == currentSubjectId) return false;

        const existingStart = toMinutes(s.start);
        const existingEnd = toMinutes(s.end);

        // STRICT overlap check (touching edges allowed)
        const overlapTime =
            newStart < existingEnd &&
            newEnd > existingStart;

        if (!overlapTime) return false;

        return s.days.some(d => selectedDays.includes(d));
    });
}


function validateSchedule() {
    const start = startInput.value;
    const end = endInput.value;
    const selectedDays = Array.from(dayCheckboxes)
        .filter(cb => cb.checked)
        .map(cb => cb.value);

    // Incomplete input → disable save
    if (!start || !end || !selectedDays.length) {
        errorBox.classList.add('hidden');
        saveBtn.disabled = true;
        return;
    }

    const startMin = toMinutes(start);
    const endMin = toMinutes(end);

    // ❌ End must be AFTER start (at least 1 minute)
    if (endMin <= startMin) {
        errorBox.textContent = 'End time must be at least 1 minute after start time.';
        errorBox.classList.remove('hidden');
        saveBtn.disabled = true;
        return;
    }

    // ❌ Conflict detected
    if (hasConflict(start, end, selectedDays)) {
        errorBox.textContent = 'Schedule conflict detected on selected days.';
        errorBox.classList.remove('hidden');
        saveBtn.disabled = true;
        return;
    }

    // ✅ Valid schedule
    errorBox.classList.add('hidden');
    saveBtn.disabled = false;
}



[startInput, endInput].forEach(i => i.addEventListener('input', validateSchedule));
dayCheckboxes.forEach(cb => cb.addEventListener('change', validateSchedule));

const removeModal = document.getElementById('removeModal');
let removeSubjectId = null;

function openRemoveModal(subjectId) {
    removeSubjectId = subjectId;
    removeModal.classList.replace('hidden', 'flex');
}

function closeRemoveModal() {
    removeSubjectId = null;
    removeModal.classList.replace('flex', 'hidden');
}

function confirmRemoveSchedule(btn) {
    if (!removeSubjectId) return;
    window.AdminModalLoading?.setLoading(btn, { text: "Removing..." });

    const f = document.createElement('form');
    f.method = 'POST';
    f.action =
        `/admin/sections/{{ $section->id }}/subjects/${removeSubjectId}/remove-schedule`;

    f.innerHTML = `
        <input type="hidden" name="_token" value="{{ csrf_token() }}">
        <input type="hidden" name="_method" value="DELETE">
    `;

    document.body.appendChild(f);
    f.submit();
}

const failModal = document.getElementById('failModal');
const failMessage = document.getElementById('failMessage');

function openFailModal(message) {
    failMessage.textContent = message;
    failModal.classList.replace('hidden', 'flex');
}

function closeFailModal() {
    failModal.classList.replace('flex', 'hidden');
}


</script>

@if($errors->has('schedule'))
<script>
document.addEventListener('DOMContentLoaded', () => {
    openFailModal("{{ $errors->first('schedule') }}");
});
</script>
@endif

@endsection
