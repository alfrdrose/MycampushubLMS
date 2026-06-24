console.log("✅ Student Script Loaded");

document.addEventListener('DOMContentLoaded', function () { 
    setupInputValidationListeners();
    attachUpdateClearListener();

    // OPEN ADD STUDENT MODAL
    const openAddStudentBtn = document.getElementById("openAddStudentModal");
    if (openAddStudentBtn) {
        openAddStudentBtn.addEventListener("click", function () { 
            const fn = document.getElementById("txtfn").value.trim();
            const ln = document.getElementById("txtln").value.trim();
            const email = document.getElementById("txtemail").value.trim();
            const guardianEmail = document.getElementById("txtguardianemail").value.trim();
            const grade = document.getElementById("cmbgrade").value;
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            let hasError = false;

            // First name
            if (!fn) {
                document.getElementById("errorfn").classList.remove("hidden");
                hasError = true;
            } else document.getElementById("errorfn").classList.add("hidden");

            // Last name
            if (!ln) {
                document.getElementById("errorln").classList.remove("hidden");
                hasError = true;
            } else document.getElementById("errorln").classList.add("hidden");

            // Student email
            if (!email || !emailPattern.test(email)) {
                document.getElementById("erroremail").classList.remove("hidden");
                hasError = true;
            } else document.getElementById("erroremail").classList.add("hidden");

            // Guardian email (optional)
            if (guardianEmail && !emailPattern.test(guardianEmail)) {
                document.getElementById("errorguardianemail").classList.remove("hidden");
                hasError = true;
            } else document.getElementById("errorguardianemail")?.classList.add("hidden");

            // guardian email
            if (!guardianEmail) {
                document.getElementById("errorguardianemail").textContent = "Guardian email is required.";
                document.getElementById("errorguardianemail").classList.remove("hidden");
                hasError = true;
            } else if (!emailPattern.test(guardianEmail)) {
                document.getElementById("errorguardianemail").textContent = "Please enter a valid guardian email address.";
                document.getElementById("errorguardianemail").classList.remove("hidden");
                hasError = true;
            } else {
                document.getElementById("errorguardianemail").classList.add("hidden");
            }

            // grade validation
            if (!grade) {
                document.getElementById("errorgrade").classList.remove("hidden");
                hasError = true;
            } else document.getElementById("errorgrade").classList.add("hidden");

            if (!hasError) {
                const modal = document.getElementById("lmsmodal");
                document.getElementById("lmsmodaltitle").textContent = "Add Student";
                document.getElementById("lmsmodalcontent").innerHTML =
                    document.getElementById("confirmStudentContent").innerHTML;
                modal.classList.remove("hidden");
            }
        });
    }

        //Clear Add Student form
        const clearBtn = document.getElementById("btnclearaddstudent");

        if (clearBtn) {
            clearBtn.addEventListener("click", function (event) {
                event.preventDefault(); // Prevent unwanted form submission

                // Clear Subject Name
                const fnInput = document.getElementById("txtfn");
                if (fnInput) {
                    fnInput.value = "";
                }

                const lnInput = document.getElementById("txtln");
                if (lnInput) {
                    lnInput.value = "";
                }

                const emailInput = document.getElementById("txtemail");
                if (emailInput) {
                    emailInput.value = "";
                }

                const guardianEmailInput = document.getElementById("txtguardianemail");
                if (guardianEmailInput) {
                    guardianEmailInput.value = "";
                }

                // Reset Grade Level dropdown
                const gradeSelect = document.getElementById("cmbgrade");
                if (gradeSelect) {
                    gradeSelect.selectedIndex = 0;
                }

                // Hide validation messages if any
                const fnerror = document.getElementById("errorfn");
                const lnerror = document.getElementById("errorln");
                const emailerror = document.getElementById("erroremail");
                const guardianEmailError = document.getElementById("errorguardianemail");
                const gradeError = document.getElementById("errorgrade");

                if (fnerror) fnerror.classList.add("hidden");
                if (lnerror) lnerror.classList.add("hidden");
                if (emailerror) emailerror.classList.add("hidden");
                if (guardianEmailError) guardianEmailError.classList.add("hidden");
                if (gradeError) gradeError.classList.add("hidden");
            });

            const grade = document.getElementById("cmbUpdategrade");
            if (grade) grade.selectedIndex = 0;

            const guardianEmailInput = document.getElementById("txtupdateguardianemail");
            if (guardianEmailInput) {
                guardianEmailInput.value = "";
            }

            // Reset Grade Level dropdown
            const gradeSelect = document.getElementById("cmbUpdategrade");
            if (gradeSelect) {
                gradeSelect.selectedIndex = 0;
            }

            // Hide validation messages if any
            const fnerror = document.getElementById("errorUpdatefn");
            const lnerror = document.getElementById("errorUpdateln");
            const guardianEmailError = document.getElementById("errorUpdateguardianemail");
            const gradeError = document.getElementById("errorUpdategrade");

            if (fnerror) fnerror.classList.add("hidden");
            if (lnerror) lnerror.classList.add("hidden");
            if (guardianEmailError) guardianEmailError.classList.add("hidden");
            if (gradeError) gradeError.classList.add("hidden");
    }

    // OPEN UPDATE CONFIRM MODAL
    const openConfirm = document.getElementById("openUpdateStudentModal");
    if (openConfirm) {
        openConfirm.addEventListener("click", function () { 
            const fn = document.getElementById("txtupdatefn").value.trim();
            const ln = document.getElementById("txtupdateln").value.trim();
            const guardianEmail = document.getElementById("txtupdateguardianemail").value.trim();
            const grade = document.getElementById("cmbUpdategrade").value;
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            let hasError = false;

            if (!fn) {
                document.getElementById("errorUpdatefn").classList.remove("hidden");
                hasError = true;
            }

            if (!ln) {
                document.getElementById("errorUpdateln").classList.remove("hidden");
                hasError = true;
            }

            // Guardian email validation
            if (!guardianEmail) {
                document.getElementById("errorUpdateguardianemail").textContent = "Guardian email is required.";
                document.getElementById("errorUpdateguardianemail").classList.remove("hidden");
                hasError = true;
            } else if (!emailPattern.test(guardianEmail)) {
                document.getElementById("errorUpdateguardianemail").textContent = "Please enter a valid guardian email address.";
                document.getElementById("errorUpdateguardianemail").classList.remove("hidden");
                hasError = true;
            } else {
                document.getElementById("errorUpdateguardianemail").classList.add("hidden");
            }
            // Level validation
            if (!grade) {
                document.getElementById("errorUpdategrade").classList.remove("hidden");
                hasError = true;
            }

            if (!hasError) {
                const modal = document.getElementById("lmsmodal");
                document.getElementById("lmsmodaltitle").textContent = "Update Student";
                document.getElementById("lmsmodalcontent").innerHTML =
                    document.getElementById("confirmUpdateStudentModal").innerHTML;
                modal.classList.remove("hidden");
            }
        });
    }
});

// INPUT LISTENERS
function setupInputValidationListeners() {
    // ---- Add Student Form ----
    const fnInput = document.getElementById("txtfn");
    if (fnInput) fnInput.addEventListener("input", () => {
        const fnError = document.getElementById("errorfn");
        if (fnError) fnError.classList.add("hidden");
    });
    // ADD Student Grade - hide error on change
    const addGradeSelect = document.getElementById("cmbgrade");
    if (addGradeSelect) addGradeSelect.addEventListener("change", () => {
        const gradeError = document.getElementById("errorgrade");
        if (gradeError) gradeError.classList.add("hidden");
    });

    // UPDATE Student Grade - hide error on change
    const updateGradeSelect = document.getElementById("cmbUpdategrade");
    if (updateGradeSelect) updateGradeSelect.addEventListener("change", () => {
        const gradeError = document.getElementById("errorUpdategrade");
        if (gradeError) gradeError.classList.add("hidden");
    });

    const lnInput = document.getElementById("txtln");
    if (lnInput) lnInput.addEventListener("input", () => {
        const lnError = document.getElementById("errorln");
        if (lnError) lnError.classList.add("hidden");
    });

    const emailInput = document.getElementById("txtemail");
    if (emailInput) emailInput.addEventListener("input", () => {
        const emailError = document.getElementById("erroremail");
        if (emailError) emailError.classList.add("hidden");
    });

    const guardianEmailInput = document.getElementById("txtguardianemail");
    if (guardianEmailInput) guardianEmailInput.addEventListener("input", () => {
        const guardianEmailError = document.getElementById("errorguardianemail");
        if (guardianEmailError) guardianEmailError.classList.add("hidden");
    });

    const gradeSelect = document.getElementById("cmbUpdategrade");
    if (gradeSelect) gradeSelect.addEventListener("change", () => {
        const gradeError = document.getElementById("errorUpdategrade");
        if (gradeError) gradeError.classList.add("hidden");
    });

    // ---- Update Student Form ----
    const updateFnInput = document.getElementById("txtupdatefn");
    if (updateFnInput) updateFnInput.addEventListener("input", () => {
        const fnError = document.getElementById("errorUpdatefn");
        if (fnError) fnError.classList.add("hidden");
    });

    const updateLnInput = document.getElementById("txtupdateln");
    if (updateLnInput) updateLnInput.addEventListener("input", () => {
        const lnError = document.getElementById("errorUpdateln");
        if (lnError) lnError.classList.add("hidden");
    });

    const updateGuardianEmailInput = document.getElementById("txtupdateguardianemail");
    if (updateGuardianEmailInput) updateGuardianEmailInput.addEventListener("input", () => {
        const guardianEmailError = document.getElementById("errorUpdateguardianemail");
        if (guardianEmailError) guardianEmailError.classList.add("hidden");
    });

    const updateFacultySelect = document.getElementById("cmbUpdatefaculty");
    if (updateFacultySelect) updateFacultySelect.addEventListener("change", () => {
        const facultyError = document.getElementById("errorUpdatefaculty");
        if (facultyError) facultyError.classList.add("hidden");
    });
}

function attachUpdateClearListener() {
    const clearBtn = document.getElementById("btnUpdateStudentClear");
    if (!clearBtn) return; // exit if button not found

    clearBtn.addEventListener("click", function (e) {
        e.preventDefault();

        // Clear all update inputs
        const fnInput = document.getElementById("txtupdatefn");
        if (fnInput) fnInput.value = "";

        const lnInput = document.getElementById("txtupdateln");
        if (lnInput) lnInput.value = "";

        const guardianEmailInput = document.getElementById("txtupdateguardianemail");
        if (guardianEmailInput) guardianEmailInput.value = "";

        const gradeSelect = document.getElementById("cmbUpdategrade");
        if (gradeSelect) gradeSelect.selectedIndex = 0;

        // Hide all update validation messages
        ["errorUpdatefn", "errorUpdateln", "errorUpdateguardianemail", "errorUpdatefaculty"].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.classList.add("hidden");
        });
    });
}

// DELETE STUDENT (unchanged)
document.addEventListener("click", function (e) {
    if (e.target.closest(".btndeleteStudent")) {
        e.preventDefault();
        const row = e.target.closest("tr");
        if (!row) return;

        const studentID = row.querySelector("td:first-child").textContent.trim();

        const modal = document.getElementById("lmsmodal");
        document.getElementById("lmsmodaltitle").textContent = "Delete Student";
        document.getElementById("lmsmodalcontent").innerHTML =
            document.getElementById("confirmDeleteStudent").innerHTML;
        modal.classList.remove("hidden");

        document.getElementById("confirmDeleteStudent").onclick = function () {
            window.AdminModalLoading?.setLoading(this, { text: "Deleting..." });
            fetch(`/admin/students/${studentID}`, {
                method: "DELETE",
                headers: {
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
                    "Accept": "application/json",
                },
            })
            .then(res => res.json())
            .then(() => location.reload())
            .catch((err) => {
                window.AdminModalLoading?.clearLoading(this);
                console.error(err);
            });
        };
    }
});
