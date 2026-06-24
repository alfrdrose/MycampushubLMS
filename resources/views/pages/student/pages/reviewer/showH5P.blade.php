@extends('pages.student.layout')

@section('content')
<div class="bg-[#FFFFFF] dark:bg-[#1E1E2D] dark:border-[#5F2DED] shadow-lg border p-8 rounded-lg">
    <div class="flex items-center gap-4 border-b border-[#D4D4D8] dark:border-[#5F2DED] pb-4">
        <button onclick="window.location='{{ route('student.reviewer.recommendation', ['tab' => 'reviewer-bank']) }}'"
                class="flex items-center gap-2">
            <i class="icofont-arrow-left text-primaryColor text-4xl"></i>
        </button>
        <div>
            <h1 class="text-2xl font-bold text-[#5E5F60] dark:text-white">H5P Reviewer</h1>
            <p class="text-sm text-gray-500 dark:text-gray-300">{{ $reviewer->title }}</p>
        </div>
    </div>

    @php
        $resizerUrl = $reviewer->resizer_url ?? '';
    @endphp

    <div class="mt-6 border border-[#5F2DED] dark:border-[#A88BFF] rounded-lg p-5">
        <div class="flex flex-col gap-3">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2">
                <p class="text-xl font-semibold text-[#373737] dark:text-white">
                    {{ $reviewer->title }}
                </p>
                <span class="text-xs px-2 py-1 rounded-full border border-[#ABB7C2] text-[#5E5F60] dark:text-gray-300">
                    H5P File
                </span>
            </div>

            <div class="flex flex-wrap gap-2">
                @forelse($reviewer->tags as $tag)
                    <span class="text-xs px-2 py-1 rounded-full border border-[#ABB7C2] text-[#5E5F60] dark:text-gray-300">
                        {{ $tag->name }}
                    </span>
                @empty
                    <span class="text-xs text-gray-400">No tags</span>
                @endforelse
            </div>

            <div class="mt-4">
                <iframe
                    src="{{ $reviewer->embed_url }}"
                    width="100%"
                    height="600"
                    style="border:none;"
                    allowfullscreen
                ></iframe>
            </div>
        </div>
    </div>

    @if($resizerUrl)
        <script src="{{ $resizerUrl }}" charset="UTF-8"></script>
    @endif
</div>
@endsection
