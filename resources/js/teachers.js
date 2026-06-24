console.log("✅ Teacher Script Loaded");

import "trix";
import "trix/dist/trix.css";

document.addEventListener("DOMContentLoaded", function () {

    attachUpdateClearListener();

    // =================================================
    // ADD TEACHER
    // =================================================
    const openAddTeacherBtn = document.getElementById("openAddTeacherModal");

    if (openAddTeacherBtn) {
        openAddTeacherBtn.addEventListener("click", function () {

            const fn = document.getElementById("txtfn").value.trim();
            const ln = document.getElementById("txtln").value.trim();
            const email = document.getElementById("txtemail").value.trim();
            const faculty = document.getElementById("cmbfaculty").value;

            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            let hasError = false;

            if (!fn) { errorfn.classList.remove("hidden"); hasError = true; }
            else errorfn.classList.add("hidden");

            if (!ln) { errorln.classList.remove("hidden"); hasError = true; }
            else errorln.classList.add("hidden");

            if (!email) {
                erroremail.textContent = "Email is required.";
                erroremail.classList.remove("hidden");
                hasError = true;
            } else if (!emailPattern.test(email)) {
                erroremail.textContent = "Please enter a valid email.";
                erroremail.classList.remove("hidden");
                hasError = true;
            } else erroremail.classList.add("hidden");

            if (!faculty) { errorfaculty.classList.remove("hidden"); hasError = true; }
            else errorfaculty.classList.add("hidden");

            if (!hasError) {
                const modal = document.getElementById("lmsmodal");
                document.getElementById("lmsmodaltitle").textContent = "Add Teacher";
                document.getElementById("lmsmodalcontent").innerHTML =
                    document.getElementById("confirmTeacherContent").innerHTML;
                modal.classList.remove("hidden");
            }
        });
    }

    // CLEAR ADD FORM
    document.getElementById("btnclearaddteacher")?.addEventListener("click", function (e) {
        e.preventDefault();

        ["txtfn", "txtln", "txtemail"].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.value = "";
        });

        document.getElementById("cmbfaculty").selectedIndex = 0;
        [errorfn, errorln, erroremail, errorfaculty].forEach(el => el.classList.add("hidden"));
    });

    // HIDE ADD ERRORS ON INPUT
    document.getElementById("txtfn")?.addEventListener("input", () => errorfn.classList.add("hidden"));
    document.getElementById("txtln")?.addEventListener("input", () => errorln.classList.add("hidden"));
    document.getElementById("txtemail")?.addEventListener("input", () => erroremail.classList.add("hidden"));
    document.getElementById("cmbfaculty")?.addEventListener("change", () => errorfaculty.classList.add("hidden"));


    // =================================================
    // UPDATE FORM MODAL VALIDATION
    // =================================================
    const openUpdateBtn = document.getElementById("openUpdateteacherModal");

    if (openUpdateBtn) {
        openUpdateBtn.addEventListener("click", function () {

            const fn = document.getElementById("txtupdatefn").value.trim();
            const ln = document.getElementById("txtupdateln").value.trim();
            const faculty = document.getElementById("cmbUpdatefaculty").value;

            let hasError = false;

            if (!fn) { errorUpdatefn.classList.remove("hidden"); hasError = true; }
            else errorUpdatefn.classList.add("hidden");

            if (!ln) { errorUpdateln.classList.remove("hidden"); hasError = true; }
            else errorUpdateln.classList.add("hidden");

            if (!faculty) { errorUpdatefaculty.classList.remove("hidden"); hasError = true; }
            else errorUpdatefaculty.classList.add("hidden");

            if (!hasError) {
                const modal = document.getElementById("lmsmodal");
                document.getElementById("lmsmodaltitle").textContent = "Update Teacher";
                document.getElementById("lmsmodalcontent").innerHTML =
                    document.getElementById("confirmUpdateteacherModal").innerHTML;

                modal.classList.remove("hidden");

                document.getElementById("confirmUpdateTeacher")?.addEventListener("click", () => {
                    document.getElementById("formupdateteacher").submit();
                });
            }
        });
    }

    // HIDE UPDATE ERRORS ON INPUT
    document.getElementById("txtupdatefn")?.addEventListener("input", () => errorUpdatefn.classList.add("hidden"));
    document.getElementById("txtupdateln")?.addEventListener("input", () => errorUpdateln.classList.add("hidden"));
    document.getElementById("cmbUpdatefaculty")?.addEventListener("change", () => errorUpdatefaculty.classList.add("hidden"));
});


// =================================================
// UPDATE + DELETE (DESKTOP + MOBILE SAFE)
// =================================================
document.addEventListener("click", function (e) {

    // UPDATE TEACHER
    if (e.target.closest(".btnupdateTeacher")) {
        e.preventDefault();

        const container = e.target.closest("[data-teacher-id]");
        if (!container) return;

        const teacherId = container.dataset.teacherId;
        window.location.href = `/admin/teachers/${teacherId}/edit`;
    }

    // DELETE TEACHER
    if (e.target.closest(".btndeleteTeacher")) {
        e.preventDefault();

        const container = e.target.closest("[data-teacher-id]");
        if (!container) return;

        const teacherId = container.dataset.teacherId;

        const modal = document.getElementById("lmsmodal");
        document.getElementById("lmsmodaltitle").textContent = "Delete Teacher";
        document.getElementById("lmsmodalcontent").innerHTML =
            document.getElementById("confirmDeleteTeacherModal").innerHTML;

        modal.classList.remove("hidden");

        // Reset confirm button listeners safely
        const oldBtn = document.getElementById("confirmDeleteTeacher");
        const newBtn = oldBtn.cloneNode(true);
        oldBtn.parentNode.replaceChild(newBtn, oldBtn);

        newBtn.addEventListener("click", function () {
            window.AdminModalLoading?.setLoading(newBtn, { text: "Deleting..." });
            fetch(`/admin/teachers/${teacherId}`, {
                method: "DELETE",
                headers: {
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
                    "Accept": "application/json",
                },
            })
            .then(res => res.json())
            .then(data => {
                if (!data?.success) {
                    window.AdminModalLoading?.clearLoading(newBtn);
                    return;
                }
                if (data.success) {
                    document.querySelector(`[data-teacher-id="${teacherId}"]`)?.remove();
                    modal.classList.add("hidden");
                }
            })
            .catch((err) => {
                window.AdminModalLoading?.clearLoading(newBtn);
                console.error(err);
            });
        });
    }

    if (e.target.closest(".teacherStatusToggle")) {
        e.preventDefault();

        const button = e.target.closest(".teacherStatusToggle");
        const teacherId = button?.dataset.teacherStatusId;

        if (!button || !teacherId || button.disabled) return;

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        if (!csrfToken) return;

        button.disabled = true;

        fetch(`/admin/teachers/${teacherId}/status`, {
            method: "POST",
            headers: {
                "X-CSRF-TOKEN": csrfToken,
                "Accept": "application/json",
            },
        })
            .then(async (response) => {
                const payload = await response.json().catch(() => ({}));
                if (!response.ok || !payload?.success) {
                    throw new Error(payload?.message || "Unable to update teacher status.");
                }

                document
                    .querySelectorAll(`.teacherStatusToggle[data-teacher-status-id="${teacherId}"]`)
                    .forEach((toggle) => applyTeacherStatusState(toggle, payload.status, payload.label));
            })
            .catch((err) => {
                console.error(err);
            })
            .finally(() => {
                button.disabled = false;
            });
    }
});


// =================================================
// UPDATE CLEAR BUTTON
// =================================================
function attachUpdateClearListener() {
    const clearBtn =
        document.getElementById("btnUpdateTeacherClear") ||
        document.getElementById("btnUpdateteacherClear");

    if (!clearBtn) return;

    clearBtn.addEventListener("click", function (e) {
        e.preventDefault();

        ["txtupdatefn", "txtupdateln"].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.value = "";
        });

        const faculty = document.getElementById("cmbUpdatefaculty");
        if (faculty) faculty.selectedIndex = 0;

        ["errorUpdatefn", "errorUpdateln", "errorUpdatefaculty"]
            .forEach(id => document.getElementById(id)?.classList.add("hidden"));
    });
}

function applyTeacherStatusState(button, status, label) {
    if (!button) return;

    const statusClasses = {
        active: [
            "border-green-200",
            "bg-green-100",
            "text-green-700",
            "dark:border-green-500/30",
            "dark:bg-green-900/30",
            "dark:text-green-300",
        ],
        inactive: [
            "border-red-200",
            "bg-red-100",
            "text-red-700",
            "dark:border-red-500/30",
            "dark:bg-red-900/30",
            "dark:text-red-300",
        ],
        default: [
            "border-slate-200",
            "bg-slate-100",
            "text-slate-600",
            "dark:border-slate-500/30",
            "dark:bg-slate-800",
            "dark:text-slate-300",
        ],
    };

    Object.values(statusClasses).flat().forEach((className) => button.classList.remove(className));

    const nextClasses = statusClasses[status] || statusClasses.default;
    nextClasses.forEach((className) => button.classList.add(className));

    button.dataset.status = status;
    button.textContent = label;
}
