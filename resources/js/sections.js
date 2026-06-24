console.log("Section script loaded");

document.addEventListener("DOMContentLoaded", function () {
    const sectionName = document.getElementById("txtfn");
    const schoolYearInput = document.getElementById("txtSchoolYearLabel");
    const hasActiveSchoolYear = schoolYearInput?.dataset.activeSchoolYear === "1";

    // ================= OPEN ADD SECTION MODAL =================
    const openAddSectionBtn = document.getElementById("openAddSectionModal");

    if (openAddSectionBtn) {
        openAddSectionBtn.addEventListener("click", function () {
            const sectionNameVal = sectionName?.value.trim() ?? "";

            let hasError = false;

            hideError("errorsectionname");

            if (hasActiveSchoolYear) {
                hideError("errorSchoolYear");
            }

            if (!sectionNameVal) {
                showError("errorsectionname");
                hasError = true;
            }

            if (!hasActiveSchoolYear) {
                showError("errorSchoolYear");
                hasError = true;
            }

            if (!hasError) {
                const modal = document.getElementById("lmsmodal");
                document.getElementById("lmsmodaltitle").textContent = "Add Section";
                document.getElementById("lmsmodalcontent").innerHTML =
                    document.getElementById("confirmSectionContent").innerHTML;
                modal.classList.remove("hidden");
            }
        });
    }

    // ================= CLEAR FORM =================
    const clearBtn = document.getElementById("btnclearaddsection");
    if (clearBtn) {
        clearBtn.addEventListener("click", function (event) {
            event.preventDefault();

            if (sectionName) {
                sectionName.value = "";
            }

            hideError("errorsectionname");
        });
    }

    // ================= AUTO HIDE ERRORS WHILE TYPING =================
    sectionName?.addEventListener("input", () => hideError("errorsectionname"));

    // ================= HELPERS =================
    function showError(id) {
        const el = document.getElementById(id);
        if (el) {
            el.classList.remove("hidden");
        }
    }

    function hideError(id) {
        const el = document.getElementById(id);
        if (el) {
            el.classList.add("hidden");
        }
    }
});

// ================= VALIDATE SCHEDULE =================
const scheduleForm = document.getElementById("scheduleForm");

if (scheduleForm) {
    scheduleForm.addEventListener("submit", function (event) {
        const daysSelect = scheduleForm.querySelector('select[name="days[]"]');
        const dayOptions = Array.from(daysSelect?.selectedOptions || []);

        const daysError = document.getElementById("scheduleDaysError");
        daysError.classList.add("hidden");
        daysError.textContent = "";

        if (dayOptions.length === 0) {
            event.preventDefault();
            daysError.textContent = "Please select at least one day.";
            daysError.classList.remove("hidden");
            return;
        }

        const start = scheduleForm.querySelector('input[name="start_time"]').value;
        const end = scheduleForm.querySelector('input[name="end_time"]').value;

        const error = document.getElementById("scheduleError");
        error.classList.add("hidden");
        error.textContent = "";

        if (!start || !end) {
            return;
        }

        if (start === end) {
            event.preventDefault();
            error.textContent = "Start time and End time cannot be the same.";
            error.classList.remove("hidden");
            return;
        }

        if (end < start) {
            event.preventDefault();
            error.textContent = "End time must be later than Start time.";
            error.classList.remove("hidden");
        }
    });
}
