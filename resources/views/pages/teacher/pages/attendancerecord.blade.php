@extends('pages.teacher.layout')

@section('content')
@php
    $isAuditOnly = (bool) ($isAuditOnly ?? false);
@endphp
<div class="bg-[#FFFFFF] dark:bg-[#0F1724] dark:border-[#5F2DED] shadow-lg border p-4 sm:p-8 rounded-lg">

    {{-- Header --}}
    <div class="border-b border-[#D4D4D8] py-3 dark:border-[#5F2DED] mb-4">
        <h1 class="text-2xl sm:text-3xl font-bold text-[#5E5F60] dark:text-white">
            {{ $attendance->day }}
        </h1>
        <p class="text-sm sm:text-base text-[#5E5F60] dark:text-white">
            Date: {{ \Carbon\Carbon::parse($attendance->date)->format('F d, Y') }}
        </p>

        <div class="mt-2">
            <x-teacher-nav
                active="attendance"
                :sectionId="$section->id"
                :subjectId="$subject->id"
            />
        </div>
    </div>

    @if($isAuditOnly)
        <div class="mb-4 rounded-lg border border-[#F3C27A] bg-[#FFF7ED] px-4 py-3 text-sm font-medium text-[#9A3412] dark:border-[#7C4A03] dark:bg-[#31210D] dark:text-[#F6C98A]">
            Teacher audit mode is enabled. Attendance statuses are read only.
        </div>
    @endif

    {{-- Students --}}
    <form id="attendanceForm"
          action="{{ route('teacher.attendance.saveStatus', $attendance->id) }}"
          method="POST">
        @csrf

        <div class="flex flex-col divide-y dark:divide-gray-700">
            @foreach($students as $student)
                @php
                    $record = $attendance->records->firstWhere('student_id', $student->id);
                    $status = $record ? $record->status : 'not marked';
                    $colors = [
                        'present' => '#4ed43c',
                        'excused' => '#b298f6',
                        'late' => '#fca127',
                        'absent' => '#b83330',
                        'not marked' => '#9ca3af',
                    ];
                @endphp

                <div class="flex flex-col sm:flex-row sm:items-center gap-3 px-3 py-3
                    {{ $loop->odd ? 'bg-[#F0F9FF] dark:bg-[#0B1220]' : 'bg-white dark:bg-[#0F1724]' }}">

                    {{-- Student --}}
                    <div class="flex items-center gap-3 sm:w-1/2">
                        <div class="w-9 h-9 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center">
                            {{ substr($student->first_name, 0, 1) }}
                        </div>
                        <p class="text-sm sm:text-base dark:text-white">
                            {{ $student->last_name }}, {{ $student->first_name }}
                        </p>
                    </div>

                    {{-- Dropdown Trigger --}}
                    <div class="sm:w-1/2 flex justify-end">
                        <input type="hidden"
                               name="statuses[{{ $student->id }}]"
                               value="{{ $status }}"
                               class="status-input">

                        @if($isAuditOnly)
                            <div class="status-btn flex items-center gap-2 px-3 py-2 text-sm">
                                <span class="w-2.5 h-2.5 rounded-full"
                                      style="background: {{ $colors[$status] }}"></span>
                                <span class="status-label capitalize"
                                      style="color: {{ $colors[$status] }}">
                                    {{ $status }}
                                </span>
                            </div>
                        @else
                            <button type="button"
                                    class="status-btn flex items-center gap-2 px-3 py-2 text-sm"
                                    onclick="openDropdown(this)">
                                <span class="w-2.5 h-2.5 rounded-full"
                                      style="background: {{ $colors[$status] }}"></span>
                                <span class="status-label capitalize"
                                      style="color: {{ $colors[$status] }}">
                                    {{ $status }}
                                </span>
                            </button>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        @unless($isAuditOnly)
            <div class="mt-6 flex justify-end">
                <button id="saveBtn"
                        type="button"
                        disabled
                        onclick="openConfirmModal()"
                        class="bg-[#5F2DED] text-white px-6 py-3 rounded-lg disabled:opacity-50">
                    Save Attendance
                </button>
            </div>
        @endunless
    </form>
</div>
@endsection

{{-- ===== CONFIRM SAVE MODAL ===== --}}
@unless($isAuditOnly)
<div id="confirmModal"
     class="fixed inset-0 hidden z-[100000] flex items-center justify-center">

    {{-- Backdrop --}}
    <div class="absolute inset-0 bg-black/50"
         onclick="closeConfirmModal()"></div>

    {{-- Modal --}}
    <div class="relative bg-white dark:bg-[#0F1724] rounded-xl shadow-xl w-full max-w-md p-6">
        <h2 class="text-lg font-semibold dark:text-white">
            Save Attendance
        </h2>

        <p class="text-sm text-gray-600 dark:text-gray-300 mt-2">
            Are you sure you want to save the attendance for this class?
        </p>

        <div class="mt-6 flex justify-end gap-3">
            <button id="cancelBtn"
                    type="button"
                    onclick="closeConfirmModal()"
                    class="px-4 py-2 rounded-lg border dark:border-gray-600 text-gray-700 dark:text-gray-200">
                Cancel
            </button>

            <button id="confirmSaveBtn"
                    type="button"
                    onclick="submitAttendance()"
                    class="bg-[#5F2DED] text-white px-5 py-2 rounded-lg flex items-center gap-2">
                <span id="confirmText">Confirm</span>

                {{-- Spinner --}}
                <svg id="loadingSpinner"
                     class="hidden w-4 h-4 animate-spin text-white"
                     viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10"
                            stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor"
                          d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                </svg>
            </button>
        </div>
    </div>
</div>
@endunless

{{-- ===== FLOATING DROPDOWN PORTAL ===== --}}
@unless($isAuditOnly)
<div id="statusDropdown"
     class="fixed hidden bg-white dark:bg-gray-800 rounded-md shadow-lg z-[99999] w-44 border dark:border-gray-700">

    @php
        $dropdownStatuses = [
            'not marked' => '#9ca3af',
            'present' => '#4ed43c',
            'absent' => '#b83330',
            'late' => '#fca127',
            'excused' => '#b298f6',
        ];
    @endphp

    @foreach($dropdownStatuses as $label => $color)
        <div class="flex items-center gap-2 px-3 py-2 cursor-pointer
                    hover:bg-gray-100 dark:hover:bg-gray-700 text-sm capitalize"
             onclick="selectStatus('{{ $label }}')">
            <span class="w-2.5 h-2.5 rounded-full"
                  style="background: {{ $color }}"></span>
            <span style="color: {{ $color }}">
                {{ $label }}
            </span>
        </div>
    @endforeach
</div>
@endunless

@section('scripts')
<script>
let activeButton = null;

function openDropdown(btn) {
    const dropdown = document.getElementById('statusDropdown');
    activeButton = btn;

    dropdown.classList.remove('hidden');
    dropdown.style.visibility = 'hidden';

    const rect = btn.getBoundingClientRect();
    const dropdownWidth = dropdown.offsetWidth;

    dropdown.style.top = rect.bottom + 'px';
    dropdown.style.left = rect.right - dropdownWidth + 'px';

    dropdown.style.visibility = 'visible';
}

function selectStatus(value) {
    const input = activeButton.closest('div').querySelector('.status-input');
    const label = activeButton.querySelector('.status-label');

    const colors = {
        present: '#4ed43c',
        excused: '#b298f6',
        late: '#fca127',
        absent: '#b83330',
        'not marked': '#9ca3af'
    };

    input.value = value;
    label.textContent = value;
    label.style.color = colors[value];
    activeButton.querySelector('span').style.background = colors[value];

    document.getElementById('statusDropdown').classList.add('hidden');
    checkIfAllMarked();
}

document.addEventListener('click', e => {
    const dropdown = document.getElementById('statusDropdown');
    if (!dropdown.contains(e.target) && !e.target.closest('.status-btn')) {
        dropdown.classList.add('hidden');
    }
});

function checkIfAllMarked() {
    const btn = document.getElementById('saveBtn');
    btn.disabled = [...document.querySelectorAll('.status-input')]
        .some(i => i.value === 'not marked');
}

/* ===== MODAL LOGIC ===== */
function openConfirmModal() {
    document.getElementById('confirmModal').classList.remove('hidden');
}

function closeConfirmModal() {
    document.getElementById('confirmModal').classList.add('hidden');
}

function submitAttendance() {
    document.getElementById('confirmText').textContent = 'Saving...';
    document.getElementById('loadingSpinner').classList.remove('hidden');
    document.getElementById('confirmSaveBtn').disabled = true;
    document.getElementById('cancelBtn').disabled = true;

    document.getElementById('attendanceForm').submit();
}

/* ESC key closes modal */
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        closeConfirmModal();
    }
});
</script>
@endsection
