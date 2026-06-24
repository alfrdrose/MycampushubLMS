@extends('pages.student.layout')

@section('content')
<div class="bg-white dark:bg-[#1E1E2D] dark:border-[#5F2DED] shadow-lg border
            p-4 sm:p-6 md:p-8 rounded-lg">

    <!-- Header -->
    <div class="flex items-start sm:items-center gap-3 sm:gap-4
                border-b border-[#D4D4D8] dark:border-[#5F2DED]
                pb-4">

        <button onclick="window.location='{{ route('student.reviewer.generated.index') }}'"
                class="flex items-center justify-center
                       w-9 h-9 sm:w-auto sm:h-auto
                       rounded-md sm:rounded-none
                       hover:bg-gray-100 dark:hover:bg-gray-800
                       transition">
            <i class="icofont-arrow-left text-primaryColor text-2xl sm:text-3xl md:text-4xl"></i>
        </button>

        <div class="min-w-0">
            <h1 class="text-lg sm:text-xl md:text-2xl font-bold
                       text-[#5E5F60] dark:text-white truncate">
                Reviewer #{{ $reviewer->reviewer_number }}
            </h1>
            <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-300">
                Generated based on your incorrect answers.
            </p>
        </div>
    </div>

    <!-- Content -->
    <div class="mt-5 sm:mt-6 border border-[#5F2DED]
                dark:border-[#A88BFF]
                rounded-lg p-4 sm:p-5">

        @if(empty($reviewer->data))

            <p class="text-sm text-[#5E5F60] dark:text-gray-300">
                No content available.
            </p>

        @else

            <div class="flex flex-col gap-8">

                @foreach($reviewer->data as $subject => $tags)

                    <div>

                        <!-- Subject -->
                        <p class="text-base sm:text-lg md:text-xl font-semibold
                                  text-[#373737] dark:text-white mb-4">
                            {{ $subject }}
                        </p>

                        @foreach($tags as $tagName => $questions)

                            <div class="mb-8">

                                <!-- Tag -->
                                <div class="mb-4">
                                    <span class="text-[11px] sm:text-xs
                                                 px-3 py-1 rounded-full
                                                 border border-[#ABB7C2]
                                                 text-[#5E5F60]
                                                 dark:text-gray-300">
                                        {{ $tagName }}
                                    </span>
                                </div>

                                <!-- Flashcards -->
                                <div class="space-y-4">

                                    @foreach($questions as $index => $item)

                                        <div class="flashcard
                                                    border border-gray-200 dark:border-gray-700
                                                    rounded-lg
                                                    p-4 sm:p-5
                                                    transition
                                                    cursor-pointer
                                                    hover:shadow-md
                                                    active:scale-[0.99]"
                                             onclick="toggleCard(this)">

                                            <!-- Question -->
                                            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start gap-2">

                                                <p class="text-sm sm:text-base font-medium
                                                          text-[#373737] dark:text-white break-words">
                                                    Q{{ $index + 1 }}:
                                                    {{ $item['question'] }}
                                                </p>

                                                <span class="text-[11px] sm:text-xs
                                                             text-[#5F2DED] font-semibold
                                                             shrink-0">
                                                    Tap to reveal
                                                </span>
                                            </div>

                                            <!-- Answer -->
                                            <div class="flashcard-answer
                                                        mt-4 hidden
                                                        transition-all duration-300 ease-in-out">

                                                <div class="h-px bg-gray-200 dark:bg-gray-700 mb-3"></div>

                                                <p class="text-sm sm:text-base
                                                          text-[#5E5F60] dark:text-gray-300
                                                          break-words">
                                                    <span class="font-semibold">
                                                        Answer:
                                                    </span>
                                                    {{ $item['correct_answer'] }}
                                                </p>
                                            </div>

                                        </div>

                                    @endforeach

                                </div>

                            </div>

                        @endforeach

                    </div>

                @endforeach

                <!-- Completion Section -->
                <div class="mt-4 flex justify-center sm:justify-end">

                    @if($reviewer->completed_at)

                        <span class="text-sm font-semibold text-green-600">
                            Completed
                        </span>

                    @else

                        <form method="POST"
                              action="{{ route('student.reviewer.generated.complete', $reviewer->id) }}"
                              onsubmit="handleMarkDone(this)">

                            @csrf

                            <button type="submit"
                                    class="flex items-center justify-center gap-2
                                           w-full sm:w-auto
                                           px-4 py-2
                                           bg-[#E1D6FF] dark:bg-[#5F2DED]
                                           text-[#5F2DED] dark:text-white
                                           rounded-lg text-sm
                                           hover:opacity-90 transition">

                                <svg class="hidden w-4 h-4 animate-spin text-current"
                                     xmlns="http://www.w3.org/2000/svg"
                                     fill="none"
                                     viewBox="0 0 24 24">
                                    <circle class="opacity-25"
                                            cx="12"
                                            cy="12"
                                            r="10"
                                            stroke="currentColor"
                                            stroke-width="4"></circle>
                                    <path class="opacity-75"
                                          fill="currentColor"
                                          d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                                </svg>

                                <span>Mark as done</span>

                            </button>

                        </form>

                    @endif

                </div>

            </div>

        @endif

    </div>

</div>

<script>
function toggleCard(card) {
    const answer = card.querySelector('.flashcard-answer');

    if (answer.classList.contains('hidden')) {
        answer.classList.remove('hidden');
        card.classList.add('bg-[#F8FAFC]', 'dark:bg-[#0B1220]');
    } else {
        answer.classList.add('hidden');
        card.classList.remove('bg-[#F8FAFC]', 'dark:bg-[#0B1220]');
    }
}

function handleMarkDone(form) {
    const button = form.querySelector('button');
    const spinner = button.querySelector('svg');
    const text = button.querySelector('span');

    button.disabled = true;
    spinner.classList.remove('hidden');
    text.textContent = 'Marking...';
}
</script>
@endsection