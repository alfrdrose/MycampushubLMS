console.log("TeacherPage.js loaded");

import "trix";
import "trix/dist/trix.css";
import flatpickr from "flatpickr";
import "flatpickr/dist/flatpickr.min.css";
import "flatpickr/dist/themes/dark.css";

/* =========================
   LMS MODAL OPENER
========================= */
document.addEventListener('DOMContentLoaded', function() {
    const addButton = document.querySelector('[data-open="lmsmodal"]');
    if (!addButton) return;

    addButton.addEventListener('click', function() {
        const modal = document.getElementById('lmsmodal');
        const title = this.getAttribute('data-title');
        const contentId = this.getAttribute('data-content');

        if (modal && contentId) {
            document.getElementById('lmsmodaltitle').textContent = title;
            document.getElementById('lmsmodalcontent').innerHTML =
                document.getElementById(contentId).innerHTML;
            modal.classList.remove('hidden');
        }
    });
});

/* =========================
   SIDEBAR DESKTOP (LG+ ONLY)
========================= */
document.addEventListener("DOMContentLoaded", () => {
    const sidebar = document.getElementById("teacherSidebar");
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

    // Fix state when resizing screen
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

    mobileMenuToggle.addEventListener("click", () => {
        mobileSidebar.classList.remove("-translate-x-full");
        mobileOverlay.classList.remove("hidden");
    });

    mobileSidebarClose.addEventListener("click", () => {
        mobileSidebar.classList.add("-translate-x-full");
        mobileOverlay.classList.add("hidden");
    });

    mobileOverlay.addEventListener("click", () => {
        mobileSidebar.classList.add("-translate-x-full");
        mobileOverlay.classList.add("hidden");
    });
});

/* =========================
   DATE / TIME PICKERS
========================= */
/* =========================
   DATE / TIME PICKERS
========================= */
document.addEventListener('DOMContentLoaded', function () {

    // ===== Due Date =====
    flatpickr("#due_date", {
        altInput: true,
        altFormat: "F j, Y",
        dateFormat: "Y-m-d",
        theme: "dark",
    });

    // ===== Due Time =====
    flatpickr("#due_time", {
        enableTime: true,
        noCalendar: true,
        dateFormat: "H:i",      // backend value
        altInput: true,
        altFormat: "h:i K",     // visible format
        time_24hr: false,
        defaultHour: 8,
        defaultMinute: 0,
        minuteIncrement: 1,
    });

    // ===== Publish Date =====
    flatpickr("#open_date", {
        altInput: true,
        altFormat: "F j, Y",
        dateFormat: "Y-m-d",
        theme: "dark",
    });

    // ===== Publish Time =====
    flatpickr("#open_time", {
        enableTime: true,
        noCalendar: true,
        dateFormat: "H:i",      // backend value
        altInput: true,
        altFormat: "h:i K",     // visible format
        time_24hr: false,
        defaultHour: 8,
        defaultMinute: 0,
        theme: "dark",
        minuteIncrement: 1, // or 60 if you want full hours only
    });
});

const activityFormEl = document.getElementById("activityForm");
const openDeleteModal = document.getElementById("openDeleteModal");
const cancelDelete = document.getElementById("cancelDelete");
const deleteModal = document.getElementById("deleteModal");
const deleteQuizModal = document.getElementById("deleteQuizModal");
const activeDeleteModal = deleteModal || deleteQuizModal;

if (openDeleteModal && cancelDelete && activeDeleteModal) {
    openDeleteModal.addEventListener("click", () => {
        activeDeleteModal.classList.remove("hidden");
    });

    cancelDelete.addEventListener("click", () => {
        activeDeleteModal.classList.add("hidden");
    });
}

document.addEventListener('DOMContentLoaded', () => {
    // File upload: mobile tap support
    const fileUpload = document.getElementById('file-upload');
    const fileInput = document.getElementById('file-input');

    if (fileUpload && fileInput) {
        fileUpload.addEventListener('touchstart', () => fileInput.click());
    }

    // Save modal: mobile tap support
    const openSaveModal = document.getElementById("openSaveModal");
    if (openSaveModal) {
        openSaveModal.addEventListener("touchstart", () => openSaveModal.click());
    }

    // Delete modal: mobile tap support
    if (openDeleteModal && activeDeleteModal) {
        openDeleteModal.addEventListener("touchstart", () => openDeleteModal.click());
    }

    if (cancelDelete && activeDeleteModal) {
        cancelDelete.addEventListener("touchstart", () => cancelDelete.click());
    }

    // Publishing mode toggle: mobile tap support
    const publishingMode = document.getElementById('publishingMode');
    if (publishingMode) {
        publishingMode.addEventListener('touchstart', () => publishingMode.dispatchEvent(new Event('change')));
    }
});


// ----------------------
// MOBILE SCRIPT
// ----------------------

// Delete Modal (mobile, activity page only)
if (activityFormEl) {
    const openDeleteModalMobile = document.getElementById('openDeleteModalMobile');
    const deleteModalMobile = document.getElementById('deleteModal');
    const cancelDeleteMobile = document.getElementById('cancelDelete');

    if (openDeleteModalMobile && deleteModalMobile && cancelDeleteMobile) {
        openDeleteModalMobile.addEventListener('click', () => {
            deleteModalMobile.classList.remove('hidden');
        });

        cancelDeleteMobile.addEventListener('click', () => {
            deleteModalMobile.classList.add('hidden');
        });
    }
}

// Save Modal
const openSaveModalMobile = document.getElementById('openSaveModalMobile');
const formErrorsMobile = document.getElementById('formErrorsMobile');
const activityFormMobile = document.getElementById('activityForm');

if (openSaveModalMobile) {
    openSaveModalMobile.addEventListener('click', () => {

        // Sync input values to hidden form
        document.getElementById('activity_title_input').value = document.getElementById('activity_title_mobile').value;
        document.getElementById('activity_description_input').value = document.getElementById('activity_description_mobile').value;

        document.getElementById('time_posted_date').value = document.getElementById('publish_dateMobile')?.value || '';
        document.getElementById('time_posted_time').value = document.getElementById('publish_timeMobile')?.value || '';

        document.getElementById('due_date_input').value = document.getElementById('due_dateMobile')?.value || '';
        document.getElementById('due_time_input').value = document.getElementById('due_timeMobile')?.value || '';

        document.getElementById('accepting_input').value = document.getElementById('acceptingMobile').value;
        document.getElementById('max_files_input').value = document.getElementById('max_filesMobile').value;
        document.getElementById('total_score_input').value = document.getElementById('total_scoreMobile').value;
        document.getElementById('status_input').value = document.getElementById('status_selectMobile').value;

        // Trigger form submission
        activityFormMobile.submit();
    });
}

// File Upload
const fileUploadMobile = document.getElementById('file-upload-mobile');
const fileInputMobile = document.getElementById('file-input-mobile');
const fileListMobile = document.getElementById('file-list-mobile');
const hiddenFilesInput = document.getElementById('hidden_files_input');

if (fileUploadMobile && fileInputMobile && fileListMobile && hiddenFilesInput) {
    fileUploadMobile.addEventListener('click', () => fileInputMobile.click());

    fileInputMobile.addEventListener('change', () => {
        fileListMobile.innerHTML = '';
        const files = Array.from(fileInputMobile.files);
        const dataTransfer = new DataTransfer();

        files.forEach(file => {
            dataTransfer.items.add(file);
            const li = document.createElement('div');
            li.textContent = file.name;
            li.classList.add('text-sm', 'text-[#5E5F60]', 'dark:text-white');
            fileListMobile.appendChild(li);
        });

        hiddenFilesInput.files = dataTransfer.files;
    });
}

// Trix Editor Sync
document.addEventListener('trix-change', (event) => {
    if (event.target.id === 'activity_description_mobile') {
        document.getElementById('activity_description_input').value = event.target.value;
    }
});











