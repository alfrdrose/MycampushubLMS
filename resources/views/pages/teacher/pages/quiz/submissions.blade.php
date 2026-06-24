{{-- resources/views/pages/teacher/quiz/submissions/index.blade.php --}}
@extends('pages.teacher.layout')

@section('content')
<div class="bg-white dark:bg-[#1E1E2D] dark:border-[#5F2DED] shadow-lg border p-6 rounded-lg">

    {{-- Header + nav --}}
    <div class="flex flex-col lg:flex-row lg:items-center gap-4 mb-4">
        <div class="flex items-center gap-3 w-full lg:w-auto">
            <button onclick="window.location='{{ route('teacher.classwork.view', [$section->id, $subject->id]) }}'"
                    class="flex items-center">
                <i class="icofont-arrow-left text-primaryColor text-3xl"></i>
            </button>

            <x-quiz-nav
                mode="view"
                active="submissions"
                :section-id="$section->id"
                :subject-id="$subject->id"
                :quiz-id="$quiz->id"
            />
        </div>

        <div class="ml-auto w-full lg:w-auto flex gap-2 items-center">
            <input id="liveSearch"
                   name="q"
                   value="{{ $search }}"
                   autocomplete="off"
                   placeholder="Search student by name"
                   class="flex-1 px-3 py-2 border rounded-lg dark:bg-[#111118] dark:border-[#2A2A3B] focus:outline-none" />
        </div>
    </div>

    {{-- Quiz header --}}
    <div class="mb-4">
        <h2 class="text-xl font-semibold text-[#373737] dark:text-white">
            {{ $quiz->title }} - Submissions
        </h2>
        <p class="text-sm text-[#5E5F60] dark:text-gray-300">
            {{ $subject->subject_name }} - {{ $section->section_name }}
            <span class="font-medium">{{ $students->count() }}</span> student(s)
        </p>
    </div>

    {{-- Submissions --}}
    <div class="w-full max-w-5xl mx-auto flex flex-col gap-4 mt-4 p-2">
        @forelse($students as $student)
            @php
                $response = $responsesByUser->get($student->user_id) ?? null;
                $hasSubmission = (bool) $response;
                $detailUrl = route('teacher.quiz.submissions.show', [
                    $section->id,
                    $subject->id,
                    $quiz->id,
                    $student->id
                ]);

                $isLate = false;
                if ($hasSubmission && $quiz->due_date) {
                    $isLate = \Carbon\Carbon::parse($response->created_at)->greaterThan(\Carbon\Carbon::parse($quiz->due_date));
                }
            @endphp

            @php
                $searchName = trim(strtolower(($student->last_name ?? '') . ', ' . ($student->first_name ?? '')));
            @endphp

            @if($hasSubmission)
                <a href="{{ $detailUrl }}"
                   data-name="{{ $searchName }}"
                   class="submission-card block border border-l-12 px-8 py-6 border-l-[#bd9ad1]
                       shadow-md rounded cursor-pointer
                       hover:scale-105 hover:bg-gray-50 dark:hover:bg-[#111118]
                       transition-transform duration-200">
            @else
                <div
                   data-name="{{ $searchName }}"
                   aria-disabled="true"
                   class="submission-card block border border-l-12 px-8 py-6 border-l-gray-300 dark:border-l-gray-600
                       shadow-md rounded cursor-not-allowed opacity-90">
            @endif
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">

                    {{-- Student --}}
                    <div class="flex flex-col">
                        <p class="font-semibold text-[#373737] dark:text-white text-xl">
                            {{ $student->last_name ?? 'Student' }}, {{ $student->first_name ?? '' }}
                        </p>

                        <div class="flex items-center gap-2 mt-1">
                            @if($hasSubmission)
                                @if($isLate)
                                    <div class="w-3 h-3 rounded-full bg-red-800 dark:bg-red-500"></div>
                                    <p class="text-red-800 dark:text-red-500 text-sm">Late submission</p>
                                @else
                                    <div class="w-3 h-3 rounded-full bg-green-800 dark:bg-green-500"></div>
                                    <p class="text-green-800 dark:text-green-500 text-sm">Submitted on time</p>
                                @endif
                            @else
                                <div class="w-3 h-3 rounded-full bg-gray-300 dark:bg-gray-600"></div>
                                <p class="text-gray-500 dark:text-gray-400 text-sm">No submission yet</p>
                            @endif
                        </div>
                    </div>

                    {{-- Submission date --}}
                    <div class="flex flex-col md:items-center">
                        <div class="flex flex-row gap-2 items-center">
                            <i class="icofont-calendar {{ $hasSubmission ? 'text-primaryColor' : 'text-gray-300 dark:text-gray-600' }} text-lg"></i>
                            <p class="text-sm text-[#5E5F60] dark:text-gray-300">
                                {{ $hasSubmission ? $response->created_at->format('M d, Y') : '-' }}
                            </p>
                        </div>

                        <div class="flex flex-row gap-2 items-center">
                            <i class="icofont-clock-time {{ $hasSubmission ? 'text-primaryColor' : 'text-gray-300 dark:text-gray-600' }} text-lg"></i>
                            <p class="text-sm text-[#5E5F60] dark:text-gray-300">
                                {{ $hasSubmission ? $response->created_at->format('h:i A') : '-' }}
                            </p>
                        </div>
                    </div>

                    {{-- Score --}}
                    <div class="flex justify-start md:justify-end">
                        <div class="flex flex-col items-start md:items-end">
                            <p class="text-3xl font-bold {{ $hasSubmission ? 'text-[#373737] dark:text-white' : 'text-gray-400 dark:text-gray-500' }}">
                                {{ $hasSubmission ? ($response->score ?? '0') : '-' }} /
                                <span class="text-[#f2277e]">{{ $quiz->total_score }}</span>
                            </p>
                            <p class="text-sm text-[#5E5F60] dark:text-gray-300">{{ $hasSubmission ? 'Quiz score' : 'No submission' }}</p>
                        </div>
                    </div>

                </div>
            @if($hasSubmission)
                </a>
            @else
                </div>
            @endif
        @empty
            <div class="text-gray-600 dark:text-gray-300 p-4">No students found.</div>
        @endforelse
        <div id="searchEmptyState"
             class="text-gray-600 dark:text-gray-300 p-4 hidden">
            No students match your search.
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('liveSearch');
    const cards = Array.from(document.querySelectorAll('.submission-card[data-name]'));
    const emptyState = document.getElementById('searchEmptyState');

    if (!searchInput || cards.length === 0) return;

    const applySearch = () => {
        const term = String(searchInput.value || '').trim().toLowerCase();
        let visibleCount = 0;

        cards.forEach((card) => {
            const name = String(card.dataset.name || '').toLowerCase();
            const show = !term || name.includes(term);
            card.classList.toggle('hidden', !show);
            if (show) visibleCount += 1;
        });

        if (emptyState) {
            emptyState.classList.toggle('hidden', visibleCount > 0);
        }
    };

    searchInput.addEventListener('input', applySearch);

    applySearch();
});
</script>
@endsection
