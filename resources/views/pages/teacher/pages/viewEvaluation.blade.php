@extends('pages.teacher.layout')

@section('content')
@php
    $totalScore = (int) ($evaluation->total_score ?? 0);
    $disabledAll = $totalScore <= 0;
@endphp

<div class="bg-[#FFFFFF] dark:bg-[#1E1E2D] dark:border-[#5F2DED] shadow-lg border p-8 rounded-lg"> 
    <div class="pb-2 flex flex-row gap-4 items-center"> 
        <button onclick="window.location='{{ route('teacher.classwork.view', [$section->id, $subject->id]) }}'"
                class="flex items-center gap-2">
            <i class="icofont-arrow-left text-primaryColor text-4xl mb-2"></i>
        </button>

        <x-evaluation-nav 
            active="Scores" 
            :section-id="$section->id" 
            :subject-id="$subject->id" 
            :evaluation-id="$evaluation->id"
        />
    </div>

    <div class="border-b border-[#D4D4D8] dark:border-[#5F2DED] pb-4">
        <p class="text-3xl font-bold text-[#373737] dark:text-white">
            {{ $evaluation->title }} 
        </p>

        <div class="flex flex-row gap-2 items-center text-sm mt-2"> 
            <i class="icofont-clock-time text-[#008236] text-lg"></i>
            <p class="text-[#373737] dark:text-white">
                <span class="font-bold">Opened:</span>  
                {{ \Carbon\Carbon::parse($evaluation->created_at)->format('h:i A F d, Y') }}
            </p>
        </div>
    </div>

    {{-- FORM --}}
    <form id="evaluationScoresForm"
          method="POST"
          action="{{ route('teacher.evaluation.scores.save', [
              'section_id' => $section->id,
              'subject_id' => $subject->id,
              'evaluation_id' => $evaluation->id
          ]) }}">
        @csrf

        {{-- Students + scores --}}
        <div class="flex flex-col gap-2 mt-4">

            <div class="flex flex-row justify-between pb-2">
                <p class="text-xl text-[#373737] dark:text-white font-medium">Student Name</p>
                <p class="text-xl text-[#373737] dark:text-white font-medium">Score</p>
            </div>

            @foreach($students as $student)
                @php
                    $studentScore = $scores[$student->id]->score ?? 0;
                    $rowBg = $loop->odd
                        ? 'bg-gray-50 dark:bg-[#242436]'
                        : 'bg-white dark:bg-[#1E1E2D]';
                @endphp

                <div class="student-row {{ $rowBg }} flex flex-row justify-between items-center rounded-lg px-4 py-3 border border-gray-200 dark:border-[#34344A]">
                    <p class="text-lg text-[#373737] dark:text-white">
                        {{ $student->last_name }}, {{ $student->first_name }}
                    </p>

                    <div class="flex flex-col items-end">
                        <div class="flex flex-row items-center gap-2">
                            <input type="number"
                                   name="scores[{{ $student->id }}]"
                                   value="{{ $studentScore }}"
                                   min="0"
                                   max="{{ $totalScore }}"
                                   inputmode="numeric"
                                   class="score-input w-20 text-center font-semibold text-lg
                                          px-2 py-1 rounded-lg
                                          border border-gray-300 dark:border-[#5F2DED]
                                          bg-white dark:bg-[#2A2A3C]
                                          text-[#373737] dark:text-white
                                          focus:outline-none focus:ring-2 focus:ring-[#5F2DED]
                                          {{ $disabledAll ? 'opacity-50 cursor-not-allowed' : '' }}"
                                   {{ $disabledAll ? 'disabled' : '' }}
                            />

                            <span class="font-bold text-xl text-[#F2277E]">
                                /{{ $totalScore }}
                            </span>
                        </div>

                        <p class="score-error text-sm text-red-600 dark:text-red-400 mt-1 hidden">
                            Score cannot exceed total score ({{ $totalScore }}).
                        </p>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Save section --}}
        <div class="w-full flex flex-col items-end mt-6">
            <button type="button"
                    id="openConfirmSaveModal"
                    class="px-6 py-3 bg-primaryColor text-white rounded-lg hover:opacity-90
                           disabled:opacity-50 disabled:cursor-not-allowed"
                    {{ $disabledAll ? 'disabled' : '' }}>
                Save Changes
            </button>

            @if(!$disabledAll && $evaluation->updated_at)
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                    Last saved on:
                    {{ \Carbon\Carbon::parse($evaluation->updated_at)->format('h:i A F d, Y') }}
                </p>
            @endif

            <p id="globalError"
               class="text-sm text-red-600 dark:text-red-400 mt-2 {{ $disabledAll ? '' : 'hidden' }}">
                You must set a Total Score first before grading students.
            </p>
        </div>
    </form>
</div>

{{-- CONFIRM SAVE MODAL --}}
<div id="confirmSaveModal" 
     class="fixed inset-0 bg-black bg-opacity-50 hidden justify-center items-center z-50">
    <div class="bg-white dark:bg-[#1E1E2D] p-6 rounded-lg shadow-lg w-96">
        <h2 class="text-xl font-bold mb-3 text-primaryColor">Save Changes?</h2>

        <p class="text-[#373737] dark:text-white mb-6">
            Are you sure you want to save all changes to this evaluation?
        </p>

        <div class="flex justify-end gap-3">
            <button id="cancelSaveBtn"
                    type="button"
                    class="px-4 py-2 bg-gray-300 dark:bg-gray-600 dark:text-white rounded">
                Cancel
            </button>

            <button id="confirmSaveBtn"
                    type="button"
                    class="px-4 py-2 bg-primaryColor text-white rounded flex items-center gap-2">
                <span id="saveBtnText">Save</span>
                <svg id="saveSpinner"
                     class="w-4 h-4 animate-spin hidden"
                     xmlns="http://www.w3.org/2000/svg"
                     fill="none"
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

{{-- SCRIPT --}}
<script>
document.addEventListener("DOMContentLoaded", function () {

    const totalScore = Number(@json($totalScore));
    const disabledAll = totalScore <= 0;

    const openSaveModalBtn = document.getElementById("openConfirmSaveModal");
    const modal = document.getElementById("confirmSaveModal");
    const cancelBtn = document.getElementById("cancelSaveBtn");
    const confirmBtn = document.getElementById("confirmSaveBtn");
    const form = document.getElementById("evaluationScoresForm");
    const globalError = document.getElementById("globalError");

    const saveText = document.getElementById("saveBtnText");
    const saveSpinner = document.getElementById("saveSpinner");

    const scoreInputs = document.querySelectorAll(".score-input");

    let isDirty = false;

    function setSaveDisabled(isDisabled) {
        openSaveModalBtn.disabled = isDisabled;

        if (disabledAll) {
            globalError.classList.remove("hidden");
            globalError.textContent = "You must set a Total Score first before grading students.";
        } else if (isDisabled) {
            globalError.classList.remove("hidden");
            globalError.textContent = "Fix invalid scores before saving.";
        } else {
            globalError.classList.add("hidden");
        }
    }

    function validateScores() {
        if (disabledAll) {
            setSaveDisabled(true);
            return;
        }

        let hasError = false;

        scoreInputs.forEach((input) => {
            const value = Number(input.value || 0);
            const row = input.closest(".student-row");
            const errorEl = row?.querySelector(".score-error");

            if (value > totalScore) {
                hasError = true;
                input.classList.add("border-red-500");
                errorEl?.classList.remove("hidden");
            } else {
                input.classList.remove("border-red-500");
                errorEl?.classList.add("hidden");
            }
        });

        setSaveDisabled(hasError);
    }

    scoreInputs.forEach((input) => {
        input.addEventListener("input", () => {
            isDirty = true;
            validateScores();
        });
    });

    window.addEventListener("beforeunload", function (e) {
        if (!isDirty) return;
        e.preventDefault();
        e.returnValue = "";
    });

    openSaveModalBtn.addEventListener("click", () => {
        validateScores();
        if (!openSaveModalBtn.disabled) {
            modal.classList.remove("hidden");
            modal.classList.add("flex");
        }
    });

    cancelBtn.addEventListener("click", () => {
        modal.classList.add("hidden");
        modal.classList.remove("flex");
    });

    confirmBtn.addEventListener("click", () => {
        validateScores();
        if (openSaveModalBtn.disabled) return;

        isDirty = false;

        confirmBtn.disabled = true;
        cancelBtn.disabled = true;
        saveText.textContent = "Saving...";
        saveSpinner.classList.remove("hidden");

        form.submit();
    });

    validateScores();
});
</script>
@endsection
