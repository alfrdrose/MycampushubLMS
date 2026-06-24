@extends('pages.student.layout')

@section('content')
<div class="bg-[#FFFFFF] dark:bg-[#1E1E2D] dark:border-[#5F2DED] shadow-lg border p-6 sm:p-8 rounded-lg">
    <div class="border-b border-[#D4D4D8] dark:border-[#5F2DED] pb-4">
        <div class="flex items-center gap-3">
            <a href="{{ route('student.classwork', [$section->id, $subject->id]) }}"
               class="flex items-center gap-2"
               aria-label="Back"
               title="Back">
                <i class="icofont-arrow-left text-primaryColor text-4xl mb-2"></i>
            </a>

            <h1 class="text-2xl sm:text-3xl font-bold text-[#5E5F60] dark:text-white">
                {{ $quiz->title }}
            </h1>
        </div>

        <div class="flex flex-col gap-1 text-sm mt-2">
            <div class="flex items-center gap-2">
                <i class="icofont-clock-time text-green-700 text-lg"></i>
                <p class="text-gray-600 dark:text-gray-300">
                    <span class="font-semibold">Opened:</span>
                    {{ $openedAt ? $openedAt->format('h:i A F d, Y') : 'Not assigned yet' }}
                </p>
            </div>

            <div class="flex items-center gap-2">
                <i class="icofont-clock-time text-red-600 text-lg"></i>
                <p class="text-gray-600 dark:text-gray-300">
                    <span class="font-semibold">Due:</span>
                    {{ $dueAt ? $dueAt->format('h:i A F d, Y') : 'No due date' }}
                </p>
            </div>

            @php
                $descriptionHtml = (string) ($quiz->description ?? '');
                $descriptionHtml = preg_replace('/<(div|p)>\s*(?:<br\s*\/?>|&nbsp;|\s)*<\/\1>/i', '', $descriptionHtml) ?? '';
                $descriptionText = trim(strip_tags($descriptionHtml));
            @endphp
            <div class="text-gray-600 dark:text-gray-300 mt-1 [&>*]:text-inherit">
                @if($descriptionText !== '')
                    {!! $descriptionHtml !!}
                @else
                    <span>No description provided.</span>
                @endif
            </div>
        </div>
    </div>

    <div class="mt-8 flex justify-end">
        <a href="{{ $attemptUrl }}"
           class="px-5 py-2 rounded-lg bg-primaryColor text-white font-medium hover:opacity-90">
            Attempt Quiz Now
        </a>
    </div>
</div>
@endsection
