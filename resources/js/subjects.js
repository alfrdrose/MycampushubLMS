console.log("✅ Subjects Script Loaded");

document.addEventListener("DOMContentLoaded", function () {

    setupInputValidationListeners();

    /* ===============================
       ADD SUBJECT
    =============================== */
    const openAddSubjectBtn = document.getElementById("openAddSubjectModal");
    if (openAddSubjectBtn) {
        openAddSubjectBtn.addEventListener("click", function () {
            const name = document.getElementById("txtsubjectname")?.value.trim();
            const level = document.getElementById("cmblevel")?.value;

            let hasError = false;

            if (!name) {
                document.getElementById("errorname")?.classList.remove("hidden");
                hasError = true;
            } else {
                document.getElementById("errorname")?.classList.add("hidden");
            }

            if (!level) {
                document.getElementById("errorlevel")?.classList.remove("hidden");
                hasError = true;
            } else {
                document.getElementById("errorlevel")?.classList.add("hidden");
            }

            if (hasError) return;

            const modal = document.getElementById("lmsmodal");
            document.getElementById("lmsmodaltitle").textContent = "Add Subject";
            document.getElementById("lmsmodalcontent").innerHTML =
                document.getElementById("confirmSubjectContent").innerHTML;

            modal.classList.remove("hidden");
        });
    }

    /* ===============================
       UPDATE SUBJECT (FORM PAGE)
    =============================== */
    const openUpdateSubjectBtn = document.getElementById("openUpdateSubjectModal");
    if (openUpdateSubjectBtn) {
        openUpdateSubjectBtn.addEventListener("click", function () {
            const name = document.getElementById("txtupdateSubjectName")?.value.trim();
            const level = document.getElementById("cmblevel")?.value;

            let hasError = false;

            if (!name) {
                document.getElementById("errorUpdateSubjectName")?.classList.remove("hidden");
                hasError = true;
            } else {
                document.getElementById("errorUpdateSubjectName")?.classList.add("hidden");
            }

            if (!level) {
                document.getElementById("errorUpdatelevel")?.classList.remove("hidden");
                hasError = true;
            } else {
                document.getElementById("errorUpdatelevel")?.classList.add("hidden");
            }

            if (hasError) return;

            const modal = document.getElementById("lmsmodal");
            document.getElementById("lmsmodaltitle").textContent = "Update Subject";
            document.getElementById("lmsmodalcontent").innerHTML =
                document.getElementById("confirmUpdateSubjectModal").innerHTML;

            modal.classList.remove("hidden");

            // Attach submit AFTER modal content is injected
            setTimeout(() => {
                const confirmBtn = document.getElementById("confirmUpdateSubject");
                if (confirmBtn) {
                    confirmBtn.addEventListener("click", () => {
                        document.getElementById("formupdatesubject").submit();
                    });
                }
            }, 50);
        });
    }

    /* ===============================
       CLEAR ADD FORM
    =============================== */
    document.getElementById("btnclearaddsubject")?.addEventListener("click", function (e) {
        e.preventDefault();
        document.getElementById("txtsubjectname").value = "";
        document.getElementById("cmblevel").selectedIndex = 0;
        document.getElementById("errorname")?.classList.add("hidden");
        document.getElementById("errorlevel")?.classList.add("hidden");
    });

    /* ===============================
       CLEAR UPDATE FORM
    =============================== */
    document.getElementById("btnUpdateSubjectClear")?.addEventListener("click", function (e) {
        e.preventDefault();
        document.getElementById("txtupdateSubjectName").value = "";
        document.getElementById("cmblevel").selectedIndex = 0;
        document.getElementById("errorUpdateSubjectName")?.classList.add("hidden");
        document.getElementById("errorUpdatelevel")?.classList.add("hidden");
    });

});

/* ===============================
   INPUT VALIDATION LISTENERS
=============================== */
function setupInputValidationListeners() {
    document.getElementById("txtsubjectname")?.addEventListener("input", () => {
        document.getElementById("errorname")?.classList.add("hidden");
    });

    document.getElementById("txtupdateSubjectName")?.addEventListener("input", () => {
        document.getElementById("errorUpdateSubjectName")?.classList.add("hidden");
    });

    document.getElementById("cmblevel")?.addEventListener("change", () => {
        document.getElementById("errorlevel")?.classList.add("hidden");
        document.getElementById("errorUpdatelevel")?.classList.add("hidden");
    });
}

/* ===============================
   UPDATE SUBJECT (DESKTOP + MOBILE LIST)
=============================== */
document.addEventListener("click", function (e) {
    const btn = e.target.closest(".btnupdatesubject");
    if (!btn) return;

    e.preventDefault();
    e.stopPropagation();

    const subjectID = btn.dataset.id;
    if (!subjectID) return;

    window.location.href = `/admin/subjects/${subjectID}/edit`;
});

/* ===============================
   DELETE SUBJECT (DESKTOP + MOBILE)
=============================== */
document.addEventListener("click", function (e) {
    const btn = e.target.closest(".btndeleteSubject");
    if (!btn) return;

    e.preventDefault();
    e.stopPropagation();

    const subjectID = btn.dataset.id;
    if (!subjectID) return;

    const modal = document.getElementById("lmsmodal");
    document.getElementById("lmsmodaltitle").textContent = "Delete Subject";
    document.getElementById("lmsmodalcontent").innerHTML =
        document.getElementById("confirmDeleteSubjectModal").innerHTML;

    modal.classList.remove("hidden");

    let confirmBtn = document.getElementById("confirmDeleteSubject");

    // Clean previous listeners
    const cleanBtn = confirmBtn.cloneNode(true);
    confirmBtn.parentNode.replaceChild(cleanBtn, confirmBtn);

    cleanBtn.addEventListener("click", function () {
        window.AdminModalLoading?.setLoading(cleanBtn, { text: "Deleting..." });
        fetch(`/admin/subjects/${subjectID}`, {
            method: "DELETE",
            headers: {
                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
                "Accept": "application/json"
            }
        })
        .then(res => res.json())
        .then(data => {
            if (!data?.success) {
                window.AdminModalLoading?.clearLoading(cleanBtn);
                return;
            }
            if (data.success) {
                document
                    .querySelectorAll(`[data-subject-id="${subjectID}"]`)
                    .forEach(el => el.remove());

                modal.classList.add("hidden");
            }
        })
        .catch(err => {
            window.AdminModalLoading?.clearLoading(cleanBtn);
            console.error(err);
        });
    });
});
