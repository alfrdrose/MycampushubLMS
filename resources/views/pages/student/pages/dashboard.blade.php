@extends('pages.student.layout')

@section('content')

{{-- Responsive layout --}}
<div class="flex flex-col md:flex-row gap-6">

    {{-- Main panel --}}
    <div class="w-full md:w-[60%] bg-[#FFFFFF] dark:bg-whiteColor-dark dark:border shadow-lg dark:border-[#5F2DED] p-4 md:p-6 rounded-lg">
        <div class="border-b border-[#D4D4D8] dark:border-white mb-4">
            <h1 class="text-2xl md:text-3xl font-bold text-[#5E5F60] dark:text-white mb-4">
                Announcements
            </h1>
        </div>

        {{-- Announcement Container --}}
        <div class="space-y-4">

            @forelse($announcements as $announcement)
                <div class="border border-[#5F2DED] rounded-lg p-4 bg-white dark:bg-whiteColor-dark">
                    <div class="border-l-4 pl-4 border-[#5F2DED]">

                        {{-- Title --}}
                        <p class="font-semibold text-base md:text-lg text-[#373737] dark:text-white">
                            {{ $announcement->title }}
                        </p>

                        {{-- Body with See More --}}
                        <div class="mt-2 text-sm text-[#373737] dark:text-white">
                            <div class="announcement-content trix-content line-clamp-4">
                                {!! $announcement->description !!}
                            </div>

                            <button
                                type="button"
                                class="see-more-btn hidden mt-1 text-xs font-semibold text-[#7e7e7e] hover:underline hover:text-primaryColor transition-all"
                            >
                                ...See more
                            </button>
                        </div>

                        {{-- Image --}}
                        @if($announcement->image)
                            <div class="my-4">
                                <img 
                                    src="{{ asset('storage/' . $announcement->image) }}" 
                                    alt="Announcement Image"
                                    class="w-full h-auto rounded-lg border border-[#D4D4D8] dark:border-[#5F2DED]"
                                >
                            </div>
                        @endif

                        {{-- Timestamp --}}
                        <p class="text-[10px] text-[#757575] dark:text-white mt-2">
                            Posted {{ \Carbon\Carbon::parse($announcement->date_posted)->diffForHumans() }}
                        </p>
                    </div>
                </div>
            @empty
                <p class="text-sm text-[#757575] dark:text-white">
                    No announcements posted yet.
                </p>
            @endforelse

            {{-- Pagination --}}
            <div class="flex justify-center mt-4">
                {{ $announcements->links() }}
            </div>

        </div>
    </div>

    {{-- Side panel --}}
    <div class="w-full md:w-[40%] flex flex-col gap-6">

        {{-- To-do-List Panel --}}
        <div class="bg-[#FFFFFF] dark:bg-whiteColor-dark dark:border shadow-lg dark:border-[#5F2DED] p-4 md:p-6 rounded-lg">
            <div class="border-b border-[#D4D4D8] dark:border-white mb-4">
                <h1 class="text-lg md:text-xl font-bold text-[#5E5F60] dark:text-white mb-2">
                    To-do-List
                </h1>
            </div>

            <div class="flex flex-col mt-4 gap-4">
                @if($todoItems->count())
                    @foreach($todoItems as $item)
                        <a href="{{ $item['url'] }}"
                           class="flex items-start p-2 rounded-lg hover:bg-[#F5F5F5] dark:hover:bg-[#2b2b2b] transition">

                            <div>
                                <i class="{{ $item['icon'] }} text-2xl md:text-3xl {{ $item['icon_color'] }}"></i>
                            </div>

                            <div class="ml-3 md:ml-4">
                                <p class="text-[#2864FF] font-semibold text-sm md:text-base">
                                    {{ $item['title'] }}
                                    <span class="ml-2 text-[10px] uppercase tracking-wide text-[#7A7A7A] dark:text-white">
                                        {{ $item['type_label'] }}
                                    </span>
                                </p>

                                <p class="text-sm text-[#757575] dark:text-white mt-1">
                                    {{ $item['subject'] }}
                                </p>

                                <div class="flex gap-2 text-[#7A7A7A] dark:text-white mt-1">
                                    <i class="icofont-calendar text-xs"></i>
                                    <p class="text-xs">{{ $item['due_date'] }}</p>
                                </div>

                                <div class="flex gap-2 text-[#7A7A7A] dark:text-white">
                                    <i class="icofont-clock-time text-xs"></i>
                                    <p class="text-xs">{{ $item['due_time'] }}</p>
                                </div>
                            </div>
                        </a>
                    @endforeach

                    <div class="flex justify-center">
                        {{ $todoItems->links() }}
                    </div>
                @else
                    <p class="text-sm text-[#757575] dark:text-white">
                        No pending tasks!
                    </p>
                @endif
            </div>
        </div>

        {{-- Calendar Panel --}}
        <div class="bg-[#FFFFFF] dark:bg-whiteColor-dark dark:border shadow-lg dark:border-[#5F2DED] p-4 md:p-6 rounded-lg">
            <div class="border-b border-[#D4D4D8] dark:border-white mb-4">
                <h1 class="text-lg md:text-xl font-bold text-[#5E5F60] dark:text-white mb-2">
                    Calendar
                </h1>
            </div>

            <div
                id="studentCalendar"
                class="w-full max-w-full px-1 sm:px-2 md:px-4"
                style="height: 300px;"
            ></div>
        </div>
    </div>
</div>

@endsection

@section('scripts')

<style>
.line-clamp-4 {
    display: -webkit-box;
    -webkit-line-clamp: 4;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.announcement-content').forEach(content => {
        const button = content.nextElementSibling;

        if (content.scrollHeight > content.clientHeight) {
            button.classList.remove('hidden');
        }

        button.addEventListener('click', () => {
            content.classList.toggle('line-clamp-4');
            button.textContent = content.classList.contains('line-clamp-4')
                ? '...See more'
                : 'See less';
        });
    });
});
</script>

<script>
    window.studentCalendarEvents = @json($calendarEvents ?? []);
</script>

<style>
    /* Mini calendar popover + event styling */
    #studentCalendar .fc-event-custom {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        justify-content: flex-start;
        gap: 2px;
        width: 100%;
        overflow: hidden;
    }

    #studentCalendar .fc-event-line {
        width: 100%;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        text-align: left;
        line-height: 1.1;
    }

    #studentCalendar .fc-event-time {
        font-size: 10px;
        font-weight: 600;
        opacity: 0.95;
        color: white !important;
    }

    #studentCalendar .fc-event-title {
        font-size: 11px;
        font-weight: 700;
        color: white !important;
    }

    #studentCalendar .fc-event-subject {
        font-size: 10px;
        opacity: 0.9;
        color: white !important;
    }

    #studentCalendar .fc-event-missed {
        background: #ef4444 !important;
        color: white !important;
    }

    #studentCalendar .fc-event-submitted {
        background: #22c55e !important;
        color: white !important;
    }

    #studentCalendar .fc-event-upcoming {
        background: #3b82f6 !important;
        color: white !important;
    }

    #studentCalendar .fc-daygrid-more-link {
        display: none !important;
        font-size: 16px;
        font-weight: 700;
        color: #5F2DED;
        text-decoration: none;
        line-height: 1;
    }

    #studentCalendar .fc-daygrid-more-link:hover {
        text-decoration: underline;
    }

    .student-day-popover {
        position: absolute;
        z-index: 60;
        min-width: 220px;
        max-width: 280px;
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        box-shadow: 0 18px 40px rgba(15, 23, 42, 0.18);
        padding: 12px;
    }

    .dark .student-day-popover {
        background: #1E1E2D;
        border-color: #5F2DED;
        color: #ffffff;
    }

    .student-day-popover.hidden {
        display: none;
    }

    .student-day-popover-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-weight: 600;
        font-size: 13px;
        color: #374151;
    }

    .dark .student-day-popover-header {
        color: #ffffff;
    }

    .student-day-popover-close {
        border: none;
        background: transparent;
        font-size: 16px;
        line-height: 1;
        cursor: pointer;
        color: #9ca3af;
    }

    .student-day-popover-body {
        margin-top: 10px;
        max-height: 240px;
        overflow: auto;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .student-day-popover-item {
        display: flex;
        gap: 10px;
        align-items: flex-start;
        width: 100%;
        text-align: left;
        padding: 8px;
        border-radius: 10px;
        border: 1px solid transparent;
        background: #f9fafb;
        cursor: pointer;
    }

    .student-day-popover-item:hover {
        background: #f3f4f6;
    }

    .dark .student-day-popover-item {
        background: #111827;
    }

    .dark .student-day-popover-item:hover {
        background: #0f172a;
    }

    .student-day-popover-dot {
        width: 10px;
        height: 10px;
        border-radius: 9999px;
        margin-top: 4px;
        flex-shrink: 0;
    }

    .student-day-popover-missed .student-day-popover-dot {
        background: #ef4444;
    }

    .student-day-popover-submitted .student-day-popover-dot {
        background: #22c55e;
    }

    .student-day-popover-upcoming .student-day-popover-dot {
        background: #3b82f6;
    }

    .student-day-popover-time {
        font-size: 11px;
        font-weight: 600;
        color: #2563eb;
    }

    .student-day-popover-title {
        font-size: 12px;
        font-weight: 700;
        color: #111827;
    }

    .dark .student-day-popover-title {
        color: #ffffff;
    }

    .student-day-popover-subject {
        font-size: 11px;
        color: #6b7280;
    }

    .dark .student-day-popover-subject {
        color: #9ca3af;
    }

    .student-day-popover-empty {
        font-size: 12px;
        color: #6b7280;
        text-align: center;
        padding: 12px 0;
    }
</style>
<script>
    window.studentCalendarEvents = @json($calendarEvents ?? []);
</script>
@endsection
