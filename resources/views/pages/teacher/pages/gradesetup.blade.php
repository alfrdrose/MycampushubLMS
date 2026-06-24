@extends('pages.teacher.layout')

@section('content')
<div class="bg-white dark:bg-[#0F1724] dark:border-[#5F2DED] shadow-lg border p-4 sm:p-6 lg:p-8 rounded-lg"> 

    {{-- HEADER --}}
    <div class="border-b border-[#D4D4D8] dark:border-[#334155] pb-4">
        <h1 class="text-2xl sm:text-3xl font-bold mb-4 text-[#5E5F60] dark:text-white">
            {{ $section->section_name }}
        </h1>

        <div class="overflow-x-auto">
            <x-teacher-nav 
                active="grade"
                :sectionId="$section->id"
                :subjectId="$subject->id"
            />
        </div>

        {{-- GRADING PERIOD --}}
        <form method="GET" class="my-4 flex flex-col sm:flex-row gap-2 sm:items-center">
            <label class="text-sm font-medium text-[#373737] dark:text-white">
                Grading Period:
            </label>

            <select name="grading_period"
                onchange="this.form.submit()"
                class="border rounded-lg px-8 py-2 text-sm dark:bg-[#0F1724] dark:text-white w-full sm:w-auto">
                @foreach (['1st','2nd','3rd','4th'] as $period)
                    <option value="{{ $period }}" @selected($gradingPeriod == $period)>
                        {{ $period }} Grading
                    </option>
                @endforeach
            </select>
        </form>

        <div class="overflow-x-auto">
            <x-grading-nav 
                :section="$section"
                :subject="$subject"
            />
        </div>
    </div>

    {{-- GROUPED CLASSWORK DISPLAY --}}
    <div class="mt-6 space-y-10">

        @foreach ($classworkTypes as $type)

            @php
                $activityItems = $activitiesByType[$type->id] ?? collect();
                $quizItems = $quizzesByType[$type->id] ?? collect();
                $evaluationItems = $evaluationsByType[$type->id] ?? collect();

                $hasAttendance = $attendanceGroupId == $type->id;

                $hasContent =
                    $hasAttendance ||
                    $activityItems->isNotEmpty() ||
                    $quizItems->isNotEmpty() ||
                    $evaluationItems->isNotEmpty();
            @endphp

            <div class="group-wrapper"
                 data-group-id="{{ $type->id }}"
                 data-has-content="{{ $hasContent ? '1' : '0' }}">

                <p class="text-lg sm:text-xl font-bold text-[#5E5F60] dark:text-white">
                    {{ $type->classwork_type }} ({{ (int)$type->percentage }}%)
                </p>

                <div class="overflow-x-auto mt-2">
                    <table class="min-w-[520px] w-full rounded-lg">
                        <thead>
                            <tr class="text-center">
                                <th class="p-3 sm:p-4 bg-[#F0F9FF] dark:bg-[#0B1220] border dark:border-[#334155] dark:text-white">
                                    Classwork Name
                                </th>
                            </tr>
                        </thead>

                        <tbody>
                            {{-- ATTENDANCE --}}
                            @if ($hasAttendance)
                                <tr>
                                    <td class="text-center border p-2 dark:border-[#334155] dark:text-white">
                                        Attendance
                                    </td>
                                </tr>
                            @endif

                            {{-- ACTIVITIES --}}
                            @foreach ($activityItems as $activity)
                                <tr>
                                    <td class="text-center border p-2 dark:border-[#334155] dark:text-white">
                                        {{ $activity->title }}
                                    </td>
                                </tr>
                            @endforeach

                            {{-- QUIZZES --}}
                            @foreach ($quizItems as $quiz)
                                <tr>
                                    <td class="text-center border p-2 dark:border-[#334155] dark:text-white">
                                        Quiz: {{ $quiz->title }}
                                    </td>
                                </tr>
                            @endforeach

                            {{-- EVALUATIONS --}}
                            @foreach ($evaluationItems as $evaluation)
                                <tr>
                                    <td class="text-center border p-2 dark:border-[#334155] dark:text-white">
                                        Evaluation: {{ $evaluation->title }}
                                    </td>
                                </tr>
                            @endforeach

                            {{-- EMPTY --}}
                            @if (!$hasContent)
                                <tr>
                                    <td class="italic text-center text-red-500 p-4">
                                        No classwork in this group
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>

        @endforeach
    </div>

    {{-- COMPUTE BUTTON --}}
    <div class="mt-8 flex justify-end">
        <button id="computeBtn"
            class="px-4 py-2 bg-primaryColor text-white rounded-lg text-sm opacity-50 cursor-not-allowed"
            disabled>
            Compute Grades
        </button>
    </div>
</div>





<script>
const computeBtn = document.getElementById('computeBtn');
const computeModal = document.getElementById('computeModal');
const computeCancel = document.getElementById('computeCancel');
const computeConfirm = document.getElementById('computeConfirm');

function validateGroups() {
    let valid = true;

    const groups = document.querySelectorAll('.group-wrapper');

    groups.forEach(group => {
        const hasContent = group.dataset.hasContent === "1";

        if (!hasContent) {
            valid = false;
        }
    });

    computeBtn.disabled = !valid;
    computeBtn.classList.toggle('opacity-50', !valid);
    computeBtn.classList.toggle('cursor-not-allowed', !valid);
}

computeBtn.onclick = () => {
    if (computeBtn.disabled) return;
    computeModal.classList.remove('hidden');
};

computeCancel.onclick = () => computeModal.classList.add('hidden');

computeConfirm.onclick = async () => {

    const computeText = document.querySelector('.compute-text');
    const computeLoading = document.querySelector('.compute-loading');

    computeText.classList.add('hidden');
    computeLoading.classList.remove('hidden');
    computeConfirm.disabled = true;

    await fetch("{{ route('teacher.grade.compute', [$section->id, $subject->id]) }}", {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            grading_period: "{{ $gradingPeriod }}"
        })
    });

    location.reload();
};

// Validate on load
validateGroups();
</script>
@endsection

{{-- COMPUTE MODAL --}}
<div id="computeModal" class="fixed inset-0 bg-black/50 hidden z-50 flex items-center justify-center px-4">
    <div class="bg-white dark:bg-[#0F1724] rounded-lg p-6 w-full max-w-[420px]">
        <h2 class="text-lg font-bold mb-4 dark:text-white">Compute Grades</h2>
        <p class="text-sm mb-6 dark:text-gray-300">
            This will calculate and save final grades for all students in this class.
        </p>
        <div class="flex justify-end gap-3">
            <button id="computeCancel" class="px-4 py-2 bg-gray-300 dark:bg-transparent dark:border-gray-300 dark:border dark:text-gray-50 rounded">
                Cancel
            </button>
            <button id="computeConfirm" class="px-4 py-2 bg-primaryColor text-white rounded flex items-center gap-2">
                <span class="compute-text">Compute</span>
                <span class="compute-loading hidden animate-spin h-4 w-4 border-2 border-white border-t-transparent rounded-full"></span>
            </button>
        </div>
    </div>
</div>