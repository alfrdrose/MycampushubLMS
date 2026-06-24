console.log("StudentPage.js loaded");

import "trix";
import "trix/dist/trix.css";

/* =========================
   SIDEBAR DESKTOP (LG+ ONLY)
========================= */
document.addEventListener("DOMContentLoaded", () => {
    const sidebar = document.getElementById("studentSidebar");
    const dashboardContent = document.getElementById("dashboardContent");
    const toggleButton = document.getElementById("sidebarToggle");
    const floatingButton = document.getElementById("sidebarFloatingButton");

    if (!sidebar || !dashboardContent) return;

    function isDesktop() {
        return window.innerWidth >= 1024; // Tailwind lg breakpoint
    }

    function collapseSidebar() {
        if (!isDesktop()) return;

        sidebar.classList.remove("lg:block");
        sidebar.classList.add("lg:hidden");

        floatingButton?.classList.remove("hidden");

        dashboardContent.classList.remove("lg:col-start-4", "lg:col-span-9");
        dashboardContent.classList.add("lg:col-start-1", "lg:col-span-12");
    }

    function expandSidebar() {
        if (!isDesktop()) return;

        sidebar.classList.remove("lg:hidden");
        sidebar.classList.add("lg:block");

        floatingButton?.classList.add("hidden");

        dashboardContent.classList.remove("lg:col-start-1", "lg:col-span-12");
        dashboardContent.classList.add("lg:col-start-4", "lg:col-span-9");
    }

    toggleButton?.addEventListener("click", collapseSidebar);
    floatingButton?.addEventListener("click", expandSidebar);

    // Fix sidebar state when resizing screen
    window.addEventListener("resize", () => {
        if (isDesktop()) {
            expandSidebar();
        }
    });
});


/* =========================
   SIDEBAR MOBILE
========================= */
document.addEventListener("DOMContentLoaded", () => {
    const mobileMenuToggle = document.getElementById("mobileMenuToggle");
    const mobileSidebar = document.getElementById("mobileSidebar");
    const mobileSidebarClose = document.getElementById("mobileSidebarClose");
    const mobileOverlay = document.getElementById("mobileOverlay");

    if (!mobileMenuToggle || !mobileSidebar || !mobileSidebarClose) return;

    // Open Sidebar
    mobileMenuToggle.addEventListener("click", () => {
        mobileSidebar.classList.remove("-translate-x-full");
        mobileOverlay.classList.remove("hidden");
    });

    // Close Sidebar button
    mobileSidebarClose.addEventListener("click", () => {
        mobileSidebar.classList.add("-translate-x-full");
        mobileOverlay.classList.add("hidden");
    });

    // Close Sidebar by clicking overlay
    mobileOverlay.addEventListener("click", () => {
        mobileSidebar.classList.add("-translate-x-full");
        mobileOverlay.classList.add("hidden");
    });
});


/* =========================
   CALENDAR
========================= */
document.addEventListener('DOMContentLoaded', function () {
    const calendarEl = document.getElementById('studentCalendar');
    if (!calendarEl) return;

    const rawEvents = Array.isArray(window.studentCalendarEvents) ? window.studentCalendarEvents : [];
    const statusColors = {
        submitted: '#22c55e',
        upcoming: '#3b82f6',
        missed: '#ef4444'
    };

    const events = rawEvents.map((event) => {
        const status = event.status || (event.extendedProps && event.extendedProps.status);
        const color = event.color || (status ? statusColors[status] : undefined);
        return color ? { ...event, color } : event;
    });

    const popover = document.createElement('div');
    popover.id = 'studentCalendarDayPopover';
    popover.className = 'student-day-popover hidden';
    document.body.appendChild(popover);

    let popoverDate = null;
    let suppressOutsideClick = false;

    const getDateKey = (dateStr) => (dateStr || '').split('T')[0];
    const formatDateLabel = (date) => {
        if (!date) return '';
        return date.toLocaleDateString('en-US', {
            month: 'long',
            day: 'numeric',
            year: 'numeric'
        });
    };

    const parseTime = (timeStr) => {
        if (!timeStr) return null;
        const parts = timeStr.trim().split(' ');
        if (parts.length < 2) return null;
        const [time, meridian] = parts;
        const [hourRaw, minuteRaw] = time.split(':');
        let hour = Number(hourRaw);
        const minute = Number(minuteRaw);
        if (Number.isNaN(hour) || Number.isNaN(minute)) return null;
        if (meridian.toUpperCase() === 'PM' && hour < 12) hour += 12;
        if (meridian.toUpperCase() === 'AM' && hour === 12) hour = 0;
        return hour * 60 + minute;
    };

    const closePopover = () => {
        popover.classList.add('hidden');
        popoverDate = null;
    };

    const positionPopover = (anchorEl) => {
        const rect = anchorEl.getBoundingClientRect();
        popover.classList.remove('hidden');
        popover.style.top = '0px';
        popover.style.left = '0px';

        const popRect = popover.getBoundingClientRect();
        const padding = 8;
        const viewportLeft = window.scrollX;
        const viewportTop = window.scrollY;
        const viewportRight = viewportLeft + window.innerWidth;
        const viewportBottom = viewportTop + window.innerHeight;

        let top = rect.bottom + window.scrollY + padding;
        let left = rect.left + window.scrollX;

        if (left + popRect.width > viewportRight - padding) {
            left = viewportRight - popRect.width - padding;
        }
        if (left < viewportLeft + padding) {
            left = viewportLeft + padding;
        }

        if (top + popRect.height > viewportBottom - padding) {
            top = rect.top + window.scrollY - popRect.height - padding;
        }
        if (top < viewportTop + padding) {
            top = viewportTop + padding;
        }

        popover.style.top = `${top}px`;
        popover.style.left = `${left}px`;
    };

    const renderDayPopover = (info) => {
        const dateKey = getDateKey(info.dateStr || info.event?.startStr);
        if (!dateKey) return;

        if (popoverDate === dateKey && !popover.classList.contains('hidden')) {
            closePopover();
            return;
        }

        const targetDate = info.date || info.event?.start || null;
        const dayEvents = calendar.getEvents()
            .filter((event) => getDateKey(event.startStr) === dateKey)
            .sort((a, b) => {
                const aTime = parseTime(a.extendedProps?.due_time);
                const bTime = parseTime(b.extendedProps?.due_time);
                if (aTime === null && bTime === null) {
                    return (a.title || '').localeCompare(b.title || '');
                }
                if (aTime === null) return 1;
                if (bTime === null) return -1;
                return aTime - bTime;
            });

        const header = `
            <div class="student-day-popover-header">
                <span>${formatDateLabel(targetDate)}</span>
                <button type="button" class="student-day-popover-close" data-popover-close>&times;</button>
            </div>
        `;

        const body = dayEvents.length
            ? dayEvents.map((event) => {
                const status = event.extendedProps?.status || 'upcoming';
                const time = event.extendedProps?.due_time ?? '';
                const subject = event.extendedProps?.subject ?? 'No Subject';
                const url = event.extendedProps?.url || event.url || '';
                return `
                    <button type="button" class="student-day-popover-item student-day-popover-${status}" data-url="${url}">
                        <span class="student-day-popover-dot"></span>
                        <div>
                            <div class="student-day-popover-time">${time}</div>
                            <div class="student-day-popover-title">${event.title || 'Untitled'}</div>
                            <div class="student-day-popover-subject">${subject}</div>
                        </div>
                    </button>
                `;
            }).join('')
            : `<div class="student-day-popover-empty">No classwork for this day.</div>`;

        popover.innerHTML = header + `<div class="student-day-popover-body">${body}</div>`;

        popover.querySelectorAll('[data-url]').forEach((btn) => {
            btn.addEventListener('click', () => {
                const url = btn.getAttribute('data-url');
                if (url) {
                    window.location.href = url;
                }
            });
        });

        const closeBtn = popover.querySelector('[data-popover-close]');
        closeBtn?.addEventListener('click', closePopover);

        popoverDate = dateKey;
        suppressOutsideClick = true;
        setTimeout(() => {
            suppressOutsideClick = false;
        }, 0);
        positionPopover(info.dayEl || info.el);
    };

    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        dayMaxEvents: 1,
        moreLinkText: function () {
            return '';
        },
        moreLinkClick: function (info) {
            if (info?.jsEvent) {
                info.jsEvent.preventDefault();
                info.jsEvent.stopPropagation();
            }
            renderDayPopover({
                date: info?.date || null,
                dateStr: info?.dateStr || '',
                dayEl: info?.dayEl || null
            });
            return 'none';
        },
        contentHeight: 'auto',
        aspectRatio: 1.8,
        themeSystem: 'standard',
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
        eventClassNames: function (arg) {
            const status = arg.event.extendedProps?.status;
            if (status === 'missed') return ['fc-event-missed'];
            if (status === 'submitted') return ['fc-event-submitted'];
            return ['fc-event-upcoming'];
        },
        eventContent: function (arg) {
            const dueTime = arg.event.extendedProps?.due_time ?? '';
            const subject = arg.event.extendedProps?.subject ?? 'No Subject';

            return {
                html: `
                    <div class="fc-event-custom">
                        <div class="fc-event-line fc-event-time">${dueTime}</div>
                        <div class="fc-event-line fc-event-title">${arg.event.title}</div>
                        <div class="fc-event-line fc-event-subject">${subject}</div>
                    </div>
                `
            };
        },
        dateClick: function (info) {
            renderDayPopover(info);
        },
        eventClick: function (info) {
            info.jsEvent.preventDefault();
            info.jsEvent.stopPropagation();
            renderDayPopover(info);
        }
    });

    calendar.render();

    // Re-render when resizing
    window.addEventListener('resize', () => {
        calendar.updateSize();
        if (!popover.classList.contains('hidden')) {
            closePopover();
        }
    });

    // Re-render if sidebar toggles
    const toggleBtn = document.getElementById('sidebarToggle');
    if (toggleBtn) {
        toggleBtn.addEventListener('click', () => {
            setTimeout(() => calendar.updateSize(), 300);
        });
    }

    document.addEventListener('click', (event) => {
        if (suppressOutsideClick) return;
        if (popover.classList.contains('hidden')) return;
        if (popover.contains(event.target)) return;
        if (event.target.closest('.fc-daygrid-day')) return;
        closePopover();
    });

    window.addEventListener('scroll', () => {
        if (!popover.classList.contains('hidden')) {
            closePopover();
        }
    });
});


/* =========================
   REMOVE FILE LOGIC
========================= */
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.remove-file-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const id = btn.dataset.id;
            const input = document.getElementById('remove-input-' + id);

            input.value = id;

            btn.parentElement.style.opacity = "0.4";
            btn.parentElement.style.textDecoration = "line-through";
        });
    });
});
