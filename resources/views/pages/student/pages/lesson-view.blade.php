@extends('pages.student.layout')

@section('content')
@php
    $activePeriod = request('grading_period', $lesson->grading_period ?? '1st');
    $backUrl = route('student.lesson', [$section->id, $subject->id]) . '?grading_period=' . urlencode($activePeriod);
    $attachments = $lesson->getMedia('attachments');

    $openedAt = null;
    if ($lesson->open_date) {
        $datePart = \Carbon\Carbon::parse($lesson->open_date)->format('Y-m-d');
        $timePart = $lesson->open_time
            ? \Carbon\Carbon::parse($lesson->open_time)->format('H:i:s')
            : '00:00:00';
        $openedAt = \Carbon\Carbon::parse($datePart . ' ' . $timePart);
    }
@endphp

<div class="bg-white dark:bg-[#1E1E2D] dark:border-[#5F2DED] shadow-lg border p-8 rounded-lg">
    <div class="pb-3 flex flex-wrap gap-3 items-center border-b border-[#D4D4D8] dark:border-[#5F2DED]">
        <a href="{{ $backUrl }}" class="flex items-center gap-2 shrink-0">
            <i class="icofont-arrow-left text-primaryColor text-4xl mb-1"></i>
        </a>

        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-[#5E5F60] dark:text-white">
                {{ $lesson->lesson_title }}
            </h1>
            <div class="mt-1 flex items-center gap-2 text-sm text-[#5E5F60] dark:text-gray-300">
                <i class="icofont-clock-time text-[#008236]"></i>
                <span>
                    <span class="font-semibold">Opened:</span>
                    {{ $openedAt ? $openedAt->format('M d, Y h:i A') : 'Immediately' }}
                </span>
            </div>
        </div>
    </div>

    <div class="mt-4">
        <div class="prose dark:prose-invert max-w-none text-[#373737] dark:text-white">
            {!! $lesson->description ?: '<p>No description.</p>' !!}
        </div>

        <div class="mt-8 pt-4 border-t border-[#D4D4D8] dark:border-[#5F2DED]">
            <div class="mb-3 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-[#5E5F60] dark:text-white">Attached Files</h2>

                @if($attachments->isNotEmpty())
                    <button
                        type="button"
                        data-download-all-target="#studentLessonAttachments"
                        class="inline-flex h-8 w-8 items-center justify-center rounded border border-[#D4D4D8] text-primaryColor transition hover:bg-gray-100 dark:border-[#5F2DED] dark:text-white dark:hover:bg-[#2A2A3C]"
                        title="Download all"
                        aria-label="Download all attached files"
                    >
                        <i class="icofont-download text-base"></i>
                    </button>
                @endif
            </div>

            @if($attachments->isNotEmpty())
                <ul id="studentLessonAttachments" class="space-y-2">
                    @foreach($attachments as $media)
                        <li class="bg-gray-100 dark:bg-[#2A2A3C] rounded px-3 py-2">
                            <div class="flex items-center gap-2 min-w-0">
                                <a
                                    href="{{ $media->getUrl() }}"
                                    target="_blank"
                                    class="text-[#373737] dark:text-white hover:underline break-all"
                                >
                                    {{ $media->file_name }}
                                </a>
                                <a
                                    href="{{ $media->getUrl() }}"
                                    download
                                    data-download-link="true"
                                    class="shrink-0 text-primaryColor hover:opacity-80"
                                    title="Download {{ $media->file_name }}"
                                    aria-label="Download {{ $media->file_name }}"
                                >
                                    <i class="icofont-download text-sm"></i>
                                </a>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="text-sm text-gray-500 dark:text-gray-300">No attachments.</p>
            @endif
        </div>

        <div class="mt-5 rounded-lg bg-[#F8F9FC] dark:bg-[#242438] p-4">
            <h2 class="text-sm font-semibold text-[#5E5F60] dark:text-white mb-2">Tags</h2>
            <div class="flex flex-wrap gap-2">
                @forelse($lesson->tags as $tag)
                    <span class="text-xs px-2 py-1 rounded-full border border-[#C8D0D9] dark:border-[#4D4D6A] text-[#5E5F60] dark:text-gray-300 bg-white/70 dark:bg-[#2C2C44]">
                        {{ $tag->name }}
                    </span>
                @empty
                    <p class="text-sm text-gray-500 dark:text-gray-300">No tags assigned.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener("DOMContentLoaded", () => {
    const triggerAttachmentDownloads = (targetSelector) => {
        if (!targetSelector) return;

        const container = document.querySelector(targetSelector);
        if (!container) return;

        const links = Array.from(container.querySelectorAll('a[data-download-link="true"]'));
        links.forEach((link, index) => {
            window.setTimeout(() => {
                const tempLink = document.createElement("a");
                tempLink.href = link.href;
                tempLink.setAttribute("download", "");
                tempLink.style.display = "none";
                document.body.appendChild(tempLink);
                tempLink.click();
                tempLink.remove();
            }, index * 150);
        });
    };

    document.querySelectorAll("[data-download-all-target]").forEach((button) => {
        button.addEventListener("click", () => {
            triggerAttachmentDownloads(button.dataset.downloadAllTarget || "");
        });
    });
});
</script>
@endsection
