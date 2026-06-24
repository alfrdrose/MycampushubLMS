@extends('pages.teacher.layout')

@section('content')
<div class="bg-[#FFFFFF] dark:bg-[#1E1E2D] dark:border-[#5F2DED] shadow-lg border p-8 rounded-lg">

    <div class="flex items-center gap-4 border-b border-[#D4D4D8] dark:border-[#5F2DED] pb-4">
        <button onclick="window.location='{{ route('teacher.reviewer.index') }}'"
                class="flex items-center gap-2">
            <i class="icofont-arrow-left text-primaryColor text-4xl"></i>
        </button>
        <div>
            <h2 class="text-2xl font-semibold text-[#373737] dark:text-white">
                {{ $reviewer->title }}
            </h2>
            <p class="text-sm text-gray-500 dark:text-gray-300">
                H5P Reviewer Preview
            </p>
        </div>
    </div>

    @php
        $resizerUrl = $reviewer->resizer_url ?? '';
        $embedUrl = $reviewer->embed_url ?? '';
    @endphp
    

    @if($embedUrl)
        <div class="mt-6">
            <iframe
                src="{{ $embedUrl }}"
                width="100%"
                height="600"
                style="border:none;"
                allow="fullscreen"
                allowfullscreen="allowfullscreen"
                frameborder="0"
            ></iframe>
        </div>
    @else
        <div class="mt-6 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
            <p><strong>Error:</strong> No embed URL found for this H5P reviewer.</p>
            <p class="text-sm mt-2">Embed URL: <code>{{ $embedUrl ?? 'NULL' }}</code></p>
            <p class="text-sm">Raw DB Value: <code>{{ $rawEmbedUrl }}</code></p>
            <p class="text-sm">Reviewer ID: {{ $reviewer->id ?? 'NULL' }}</p>
        </div>
    @endif

    @if($resizerUrl)
        <script src="{{ $resizerUrl }}" charset="UTF-8"></script>
    @else
        <script src="https://h5p.org/sites/all/modules/h5p/library/js/h5p-resizer.js" charset="UTF-8"></script>
    @endif
</div>
@endsection
