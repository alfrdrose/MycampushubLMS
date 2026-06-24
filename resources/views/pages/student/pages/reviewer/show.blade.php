@extends('pages.student.layout')

@section('content')
<div class="bg-[#FFFFFF] dark:bg-[#1E1E2D] dark:border-[#5F2DED] shadow-lg border p-8 rounded-lg">
    <div class="flex items-center gap-4 border-b border-[#D4D4D8] dark:border-[#5F2DED] pb-4">
        <button onclick="window.location='{{ route('student.reviewer.recommendation', ['tab' => 'reviewer-bank']) }}'"
                class="flex items-center gap-2">
            <i class="icofont-arrow-left text-primaryColor text-4xl"></i>
        </button>
        <div>
            <h1 class="text-2xl font-bold text-[#5E5F60] dark:text-white">Reviewer</h1>
            <p class="text-sm text-gray-500 dark:text-gray-300">{{ $reviewer->title }}</p>
        </div>
    </div>

    <div class="mt-6 border border-[#5F2DED] dark:border-[#A88BFF] rounded-lg p-5">
        <div class="flex flex-col gap-2">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2">
                <div>
                    <p class="text-xl font-semibold text-[#373737] dark:text-white">
                        {{ $reviewer->title }}
                    </p>
                    <div class="mt-2 flex flex-wrap gap-2">
                        @forelse($reviewer->tags as $tag)
                            <span class="text-xs px-2 py-1 rounded-full border border-[#ABB7C2] text-[#5E5F60] dark:text-gray-300">
                                {{ $tag->name }}
                            </span>
                        @empty
                            <span class="text-xs text-gray-400">No tags</span>
                        @endforelse
                    </div>
                </div>
            </div>

            <p class="text-sm text-[#5E5F60] dark:text-gray-300">
                <span class="font-semibold">Objective:</span> {{ $reviewer->learning_objective }}
            </p>

            <div class="text-sm text-[#373737] dark:text-gray-300">
                {!! nl2br(e($reviewer->mini_lesson)) !!}
            </div>

            @if(!empty($reviewer->mini_quiz))
                <div class="mt-4">
                    <p class="text-sm font-semibold text-[#373737] dark:text-white">Guide Questions</p>
                    <div class="mt-2 space-y-2">
                        @foreach($reviewer->mini_quiz as $index => $item)
                            <details class="border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-2">
                                <summary class="cursor-pointer text-sm text-[#5E5F60] dark:text-gray-300">
                                    Q{{ $index + 1 }}: {{ $item['question'] ?? '' }}
                                </summary>
                                <div class="mt-2 text-sm text-[#373737] dark:text-white">
                                    Answer: {{ $item['answer'] ?? '' }}
                                </div>
                            </details>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="mt-4 flex justify-end">
                @if($completedReviewer)
                    <span class="text-sm font-semibold text-green-600">Completed</span>
                @else
                    <form method="POST"
                          action="{{ route('student.reviewer.complete', $reviewer->id) }}"
                          onsubmit="handleMarkDone(this)">
                        @csrf
                        <button type="submit"
                                class="flex items-center gap-2 px-3 py-1.5 bg-[#E1D6FF] dark:bg-[#5F2DED] text-[#5F2DED] dark:text-white rounded-lg text-sm hover:opacity-90">
                            <svg class="hidden w-4 h-4 animate-spin text-current"
                                 xmlns="http://www.w3.org/2000/svg"
                                 fill="none"
                                 viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10"
                                        stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor"
                                      d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                            </svg>
                            <span>Mark as done</span>
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
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
