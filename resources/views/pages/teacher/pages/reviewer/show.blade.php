@extends('pages.teacher.layout')

@section('content')
<div class="bg-[#FFFFFF] dark:bg-[#1E1E2D] dark:border-[#5F2DED] shadow-lg border p-8 rounded-lg">
    <div class="flex items-center gap-4 border-b border-[#D4D4D8] dark:border-[#5F2DED] pb-4">
        <button onclick="window.location='{{ route('teacher.reviewer.index') }}'"
                class="flex items-center gap-2">
            <i class="icofont-arrow-left text-primaryColor text-4xl"></i>
        </button>
        <div>
            <h1 class="text-2xl font-bold text-[#5E5F60] dark:text-white">Reviewer Preview</h1>
            @php
                $creatorFirst = trim((string) optional($reviewer->creator)->first_name);
                $creatorLast = trim((string) optional($reviewer->creator)->last_name);
                $creatorName = trim($creatorFirst . ' ' . $creatorLast);
                $creatorLabel = $creatorName !== '' ? $creatorName : 'unknown';
            @endphp
            <p class="text-sm text-gray-500 dark:text-gray-300">{{ $creatorLabel }}</p>
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
        </div>
    </div>
</div>
@endsection
