@extends('pages.student.layout')

@section('content')
<div class="bg-[#FFFFFF] dark:bg-whiteColor-dark dark:border shadow-lg dark:border-[#5F2DED] p-6 rounded-lg">
    <div class="border-b border-[#D4D4D8] dark:border-white mb-4 flex items-center justify-between">
        <h1 class="text-2xl font-bold text-[#5E5F60] dark:text-white mb-2">Calendar</h1>

        {{-- Legend --}}
        <div class="flex gap-3 text-xs">
            <div class="flex items-center gap-1">
                <span class="w-3 h-3 rounded-sm inline-block bg-[#22c55e]"></span>
                <span class="text-[#5E5F60] dark:text-white">Submitted</span>
            </div>
            <div class="flex items-center gap-1">
                <span class="w-3 h-3 rounded-sm inline-block bg-[#3b82f6]"></span>
                <span class="text-[#5E5F60] dark:text-white">Upcoming</span>
            </div>
            <div class="flex items-center gap-1">
                <span class="w-3 h-3 rounded-sm inline-block bg-[#ef4444]"></span>
                <span class="text-[#5E5F60] dark:text-white">Missed</span>
            </div>
        </div>
    </div>

    <div id="studentCalendarFull" class="w-full" style="height: 600px;"></div>
</div>
@endsection


@section('scripts')
<style>
    /* ✅ Make calendar responsive */
    #studentCalendarFull {
        width: 100%;
        max-width: 100%;
        overflow: visible;
    }

    /* ✅ Allow "+ more" popovers to extend outside the calendar box */
    #studentCalendarFull .fc,
    #studentCalendarFull .fc-view-harness,
    #studentCalendarFull .fc-scroller-harness,
    #studentCalendarFull .fc-scroller,
    #studentCalendarFull .fc-scrollgrid,
    #studentCalendarFull .fc-scrollgrid-section,
    #studentCalendarFull .fc-daygrid-body,
    #studentCalendarFull .fc-daygrid-body .fc-daygrid-day-frame {
        overflow: visible !important;
    }

    #studentCalendarFull .fc-popover {
        z-index: 60;
        max-width: 260px;
    }

    #studentCalendarFull .fc-popover-body {
        max-height: 220px;
        overflow: auto;
    }

    /* ✅ Only highlight the day number for TODAY */
    .fc .fc-daygrid-day.fc-day-today {
        background: transparent !important;
    }

    .fc .fc-daygrid-day.fc-day-today .fc-daygrid-day-number {
        background: #FF4081;
        color: white;
        padding: 4px 8px;
        border-radius: 9999px;
        font-weight: 600;
    }

    /* ✅ Make event blocks cleaner */
    .fc .fc-daygrid-event {
        border-radius: 8px !important;
        padding: 2px 4px !important;
        border: none !important;
    }

    /* ✅ Custom event text truncation */
    .fc-event-custom {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        justify-content: flex-start;
        gap: 2px;
        width: 100%;
        overflow: hidden;
    }

    .fc-event-line {
        width: 100%;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        text-align: left;
        line-height: 1.1;
    }

    .fc-event-time {
        font-size: 10px;
        font-weight: 600;
        opacity: 0.95;
        color: white !important;
    }

    .fc-event-title {
        font-size: 11px;
        font-weight: 700;
        color: white !important;
    }

    .fc-event-subject {
        font-size: 10px;
        opacity: 0.9;
        color: white !important;
    }

    /* ✅ Background colors by status */
    .fc-event-missed {
        background: #ef4444 !important; /* red */
        color: white !important;
    }

    .fc-event-submitted {
        background: #22c55e !important; /* green */
        color: white !important;
    }

    .fc-event-upcoming {
        background: #3b82f6 !important; /* blue */
        color: white !important;
    }

    /* ✅ Make "+ more" link nicer */
    .fc .fc-daygrid-more-link {
        font-size: 11px;
        font-weight: 600;
        color: #5F2DED;
        text-decoration: none;
    }

    .fc .fc-daygrid-more-link:hover {
        text-decoration: underline;
    }

    /* ✅ Mobile adjustments */
    @media (max-width: 640px) {
        #studentCalendarFull {
            height: 500px !important;
        }

        .fc .fc-toolbar-title {
            font-size: 16px !important;
        }

        .fc-event-title {
            font-size: 10px;
        }
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const calendarEl = document.getElementById('studentCalendarFull');
    if (!calendarEl) return;

    const events = @json($calendarEvents);

    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        height: 'auto',
        expandRows: true,
        fixedWeekCount: false,

        // ✅ ONLY show 1 event per cell, then show "+X more"
        dayMaxEvents: 1,
        moreLinkClick: 'popover',
        moreLinkText: function(num) {
            return `+${num} more`;
        },

        headerToolbar: {
            left: 'prev',
            center: 'title',
            right: 'next'
        },
        buttonText: {
            prev: '<',
            next: '>'
        },

        events: events,

        // ✅ Apply background based on status
        eventClassNames: function(arg) {
            const status = arg.event.extendedProps.status;

            if (status === 'missed') return ['fc-event-missed'];
            if (status === 'submitted') return ['fc-event-submitted'];
            return ['fc-event-upcoming'];
        },

        // ✅ Display:
        // Due time
        // Activity title
        // Subject
        eventContent: function(arg) {
            const dueTime = arg.event.extendedProps.due_time ?? '';
            const subject = arg.event.extendedProps.subject ?? 'No Subject';

            return {
                html: `
                    <div class="fc-event-custom">
                        <div class="fc-event-line fc-event-time">${dueTime}</div>
                        <div class="fc-event-line fc-event-title">${arg.event.title}</div>
                        <div class="fc-event-line fc-event-subject">${subject}</div>
                    </div>
                `
            };
        }
        ,
        eventClick: function(info) {
            const url = info.event.extendedProps?.url || info.event.url;
            if (!url) return;
            info.jsEvent.preventDefault();
            window.location.href = url;
        }
    });

    calendar.render();

    // ✅ Resize handling (sidebar toggle / window resize)
    window.addEventListener('resize', () => calendar.updateSize());

    const toggleBtn = document.getElementById('sidebarToggle');
    if (toggleBtn) {
        toggleBtn.addEventListener('click', () => {
            setTimeout(() => calendar.updateSize(), 300);
        });
    }
});
</script>
@endsection
