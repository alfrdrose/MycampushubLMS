console.log("Accounts JS Loaded");
import flatpickr from "flatpickr";
import "flatpickr/dist/flatpickr.css";
window.flatpickr = flatpickr;
import "trix";
import "trix/dist/trix.css";
// Wait for DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    
    // Set up input validation listeners ONCE (outside click handlers)
    setupInputValidationListeners();
    
    // Add Account + Teacher/Student Step Handler
    const addAccountForm = document.getElementById("formaddaccount");
    const openAddAccountBtn = document.getElementById("openAddAccountModal");
    const roleSelect = document.getElementById("cmbtype");
    const teacherStepForm = document.getElementById("formaddteacherfromaccount");
    const studentStepForm = document.getElementById("formaddstudentfromaccount");
    const accountNameTeacher = document.getElementById("accountNameTeacher");
    const accountEmailTeacher = document.getElementById("accountEmailTeacher");
    const accountPasswordTeacher = document.getElementById("accountPasswordTeacher");
    const accountPasswordConfirmTeacher = document.getElementById("accountPasswordConfirmTeacher");
    const accountRoleTeacher = document.getElementById("accountRoleTeacher");
    const accountNameStudent = document.getElementById("accountNameStudent");
    const accountEmailStudent = document.getElementById("accountEmailStudent");
    const accountPasswordStudent = document.getElementById("accountPasswordStudent");
    const accountPasswordConfirmStudent = document.getElementById("accountPasswordConfirmStudent");
    const accountRoleStudent = document.getElementById("accountRoleStudent");
    const pageTitle = document.getElementById("page-title");
    const pageSubtitle = document.getElementById("page-subtitle");
    const defaultTitle = pageTitle?.textContent || "";
    const defaultSubtitle = pageSubtitle?.textContent || "";
    const openStudentsCsvImportPopoverBtn = document.getElementById("openStudentsCsvImportPopover");
    const studentsCsvImportPopover = document.getElementById("studentsCsvImportPopover");
    const studentsCsvImportState = document.getElementById("studentsCsvImportState");

    const attachStudentsCsvImportHandlers = () => {
        const studentsCsvInput = document.getElementById("studentsCsvInput");
        const studentsCsvFilename = document.getElementById("studentsCsvFilename");
        const studentsCsvDropzone = document.getElementById("studentsCsvDropzone");
        const studentsCsvUploadForm = document.getElementById("studentsCsvUploadForm");
        const studentsCsvUploadOverlay = document.getElementById("studentsCsvUploadOverlay");

        if (!studentsCsvInput || !studentsCsvFilename || !studentsCsvDropzone || !studentsCsvUploadForm || !studentsCsvUploadOverlay) {
            return;
        }

        if (studentsCsvInput.dataset.bound === "1") {
            return;
        }

        studentsCsvInput.dataset.bound = "1";

        let isSubmitting = false;
        const dropzoneActiveClasses = ["ring-2", "ring-[#5F2DED]", "bg-[#EEF2FF]"];

        const updateStudentsCsvIndicator = () => {
            const file = studentsCsvInput.files && studentsCsvInput.files[0];
            const emptyText = studentsCsvFilename.dataset.empty || "No file selected";
            studentsCsvFilename.textContent = file ? file.name : emptyText;
        };

        const highlightDropzone = (isActive) => {
            dropzoneActiveClasses.forEach((className) => {
                if (isActive) {
                    studentsCsvDropzone.classList.add(className);
                } else {
                    studentsCsvDropzone.classList.remove(className);
                }
            });
        };

        const setUploadingState = () => {
            if (isSubmitting) return;
            isSubmitting = true;
            studentsCsvUploadOverlay.classList.remove("opacity-0");
            studentsCsvUploadOverlay.classList.add("opacity-100");
            studentsCsvDropzone.classList.add("pointer-events-none", "opacity-80");
        };

        const submitSelectedCsv = () => {
            const file = studentsCsvInput.files && studentsCsvInput.files[0];
            if (!file || isSubmitting) return;

            setUploadingState();

            if (typeof studentsCsvUploadForm.requestSubmit === "function") {
                studentsCsvUploadForm.requestSubmit();
                return;
            }

            studentsCsvUploadForm.submit();
        };

        updateStudentsCsvIndicator();

        studentsCsvInput.addEventListener("change", () => {
            updateStudentsCsvIndicator();
            submitSelectedCsv();
        });

        studentsCsvUploadForm.addEventListener("submit", () => {
            setUploadingState();
        });

        ["dragenter", "dragover"].forEach((eventName) => {
            studentsCsvDropzone.addEventListener(eventName, (event) => {
                event.preventDefault();
                event.stopPropagation();
                highlightDropzone(true);
            });
        });

        ["dragleave", "dragend"].forEach((eventName) => {
            studentsCsvDropzone.addEventListener(eventName, (event) => {
                event.preventDefault();
                event.stopPropagation();
                highlightDropzone(false);
            });
        });

        studentsCsvDropzone.addEventListener("drop", (event) => {
            event.preventDefault();
            event.stopPropagation();
            highlightDropzone(false);

            const files = event.dataTransfer?.files;
            if (!files || files.length === 0) {
                return;
            }

            studentsCsvInput.files = files;
            updateStudentsCsvIndicator();
            submitSelectedCsv();
        });
    };

    const setStudentsCsvImportPopoverState = (isOpen) => {
        if (!studentsCsvImportPopover || !openStudentsCsvImportPopoverBtn) {
            return;
        }

        studentsCsvImportPopover.classList.toggle("hidden", !isOpen);
        openStudentsCsvImportPopoverBtn.setAttribute("aria-expanded", isOpen ? "true" : "false");
    };

    const openStudentsCsvImportPopover = () => {
        if (!studentsCsvImportPopover) {
            return;
        }

        setStudentsCsvImportPopoverState(true);
        attachStudentsCsvImportHandlers();
    };

    const closeStudentsCsvImportPopover = () => {
        setStudentsCsvImportPopoverState(false);
    };

    if (openStudentsCsvImportPopoverBtn) {
        openStudentsCsvImportPopoverBtn.addEventListener("click", (event) => {
            event.preventDefault();
            event.stopPropagation();

            const shouldOpen = studentsCsvImportPopover?.classList.contains("hidden");
            if (shouldOpen) {
                openStudentsCsvImportPopover();
                return;
            }

            closeStudentsCsvImportPopover();
        });
    }

    if (studentsCsvImportPopover) {
        studentsCsvImportPopover.addEventListener("click", (event) => {
            const dismissValidationBtn = event.target.closest("[data-dismiss-student-csv-validation]");
            if (dismissValidationBtn) {
                const validationPanel = dismissValidationBtn.closest("#studentsCsvValidationPanel");
                if (validationPanel) {
                    validationPanel.classList.add("hidden");
                }
            }

            event.stopPropagation();
        });
    }

    document.addEventListener("click", (event) => {
        if (!studentsCsvImportPopover || !openStudentsCsvImportPopoverBtn) {
            return;
        }

        const clickedInsidePopover = studentsCsvImportPopover.contains(event.target);
        const clickedTrigger = openStudentsCsvImportPopoverBtn.contains(event.target);

        if (!clickedInsidePopover && !clickedTrigger) {
            closeStudentsCsvImportPopover();
        }
    });

    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape") {
            closeStudentsCsvImportPopover();
        }
    });

    if (studentsCsvImportState?.dataset.autoOpen === "1") {
        openStudentsCsvImportPopover();
    }

    const updateAddAccountButtonLabel = (value) => {
        if (!openAddAccountBtn) return;
        const isNextRole = value === "teacher" || value === "student";
        openAddAccountBtn.textContent = isNextRole ? "Next" : "Add account";
    };

    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    const updateAddAccountButtonState = () => {
        if (!openAddAccountBtn) return;
        openAddAccountBtn.disabled = false;
    };

    const setAddAccountButtonLabel = () => {
        if (!roleSelect) return;
        updateAddAccountButtonLabel(roleSelect.value);
    };

    const showAddAccountModal = () => {
        const modal = document.getElementById("lmsmodal");
        document.getElementById("lmsmodaltitle").textContent = "Add Account";
        document.getElementById("lmsmodalcontent").innerHTML =
            document.getElementById("confirmAddModal").innerHTML;
        modal.classList.remove("hidden");
    };

    const showAccountValidationErrors = (errors = {}) => {
        if (errors.name) {
            document.getElementById("errorusername").textContent = errors.name[0];
            document.getElementById("errorusername").classList.remove("hidden");
        }
        if (errors.email) {
            document.getElementById("erroremail").textContent = errors.email[0];
            document.getElementById("erroremail").classList.remove("hidden");
        }
        if (errors.password) {
            document.getElementById("errorpassword").textContent = errors.password[0];
            document.getElementById("errorpassword").classList.remove("hidden");
        }
        if (errors.password_confirmation) {
            document.getElementById("errorconfirmpass").textContent = errors.password_confirmation[0];
            document.getElementById("errorconfirmpass").classList.remove("hidden");
        }
        if (errors.role) {
            document.getElementById("errortype").textContent = errors.role[0];
            document.getElementById("errortype").classList.remove("hidden");
        }
    };

    const getAccountPayload = () => ({
        name: document.getElementById("txtusername")?.value.trim() || "",
        email: document.getElementById("txtemail")?.value.trim() || "",
        password: document.getElementById("txtpassword")?.value || "",
        password_confirmation: document.getElementById("txtconfirmpass")?.value || "",
    });

    const updatePrefilledEmail = (input, emailValue) => {
        if (!input) return;
        const previousDefault = input.dataset.defaultValue || "";
        if (!input.value || input.value === previousDefault) {
            input.value = emailValue;
        }
        input.dataset.defaultValue = emailValue;
    };

    const syncAccountToTeacherForm = () => {
        const payload = getAccountPayload();
        if (accountNameTeacher) accountNameTeacher.value = payload.name;
        if (accountEmailTeacher) accountEmailTeacher.value = payload.email;
        if (accountPasswordTeacher) accountPasswordTeacher.value = payload.password;
        if (accountPasswordConfirmTeacher) accountPasswordConfirmTeacher.value = payload.password_confirmation;
        if (accountRoleTeacher) accountRoleTeacher.value = "teacher";
    };

    const syncAccountToStudentForm = () => {
        const payload = getAccountPayload();
        if (accountNameStudent) accountNameStudent.value = payload.name;
        if (accountEmailStudent) accountEmailStudent.value = payload.email;
        if (accountPasswordStudent) accountPasswordStudent.value = payload.password;
        if (accountPasswordConfirmStudent) accountPasswordConfirmStudent.value = payload.password_confirmation;
        if (accountRoleStudent) accountRoleStudent.value = "student";
    };

    const syncAccountToRoleForms = () => {
        syncAccountToTeacherForm();
        syncAccountToStudentForm();
    };

   const validateAddAccountFields = () => {

    const nameInput = document.getElementById("txtusername");
    const emailInput = document.getElementById("txtemail");
    const passwordInput = document.getElementById("txtpassword");
    const confirmInput = document.getElementById("txtconfirmpass");

    const errorUsername = document.getElementById("errorusername");
    const errorEmail = document.getElementById("erroremail");
    const errorPassword = document.getElementById("errorpassword");
    const errorConfirm = document.getElementById("errorconfirmpass");
    const errorRole = document.getElementById("errortype");

    const name = nameInput?.value.trim() || "";
    const email = emailInput?.value.trim() || "";
    const password = passwordInput?.value.trim() || "";
    const confirm = confirmInput?.value.trim() || "";
    const role = roleSelect?.value || "";

    let hasError = false;

    /* ---------- USERNAME ---------- */
    if (!name) {
        if (errorUsername) errorUsername.classList.remove("hidden");
        hasError = true;
    } else {
        if (errorUsername) errorUsername.classList.add("hidden");
    }

    /* ---------- EMAIL ---------- */
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    if (!email || !emailRegex.test(email)) {
        if (errorEmail) {
            errorEmail.textContent = "Please enter a valid email.";
            errorEmail.classList.remove("hidden");
        }
        hasError = true;
    } else {
        if (errorEmail) errorEmail.classList.add("hidden");
    }

    /* ---------- PASSWORD ---------- */
    if (!password) {
        if (errorPassword) {
            errorPassword.textContent = "Password is required.";
            errorPassword.classList.remove("hidden");
        }
        hasError = true;
    } else {
        if (errorPassword) errorPassword.classList.add("hidden");
    }

    /* ---------- CONFIRM PASSWORD ---------- */
    if (!confirm || password !== confirm) {
        if (errorConfirm) {
            errorConfirm.textContent = "Passwords must match.";
            errorConfirm.classList.remove("hidden");
        }
        hasError = true;
    } else {
        if (errorConfirm) errorConfirm.classList.add("hidden");
    }

    /* ---------- ROLE ---------- */
    if (!role) {
        if (errorRole) errorRole.classList.remove("hidden");
        hasError = true;
    } else {
        if (errorRole) errorRole.classList.add("hidden");
    }

    return { hasError, role };
};

    const clearAccountFields = () => {
    const username = document.getElementById("txtusername");
    const email = document.getElementById("txtemail");
    const password = document.getElementById("txtpassword");
    const confirmPass = document.getElementById("txtconfirmpass");
    const roleSelect = document.getElementById("cmbtype");

    if (username) username.value = "";
    if (email) email.value = "";
    if (password) password.value = "";
    if (confirmPass) confirmPass.value = "";
        if (roleSelect) roleSelect.selectedIndex = 0;

    [
        "errorusername",
        "erroremail",
        "errorpassword",
        "errorconfirmpass",
        "errortype"
    ].forEach(id => {
        document.getElementById(id)?.classList.add("hidden");
    });

    syncAccountToRoleForms();

    toggleRoleSections(""); // hide teacher/student form
};


    const toggleRoleSections = (role) => {

    // hide role sections first
    document.querySelectorAll(".role-section").forEach(section => {
        section.classList.add("hidden");
    });

    const accountButtons = document.getElementById("accountMainButtons");

    // LMS Supervisor (admin)
    if (role === "admin" || !role) {
        accountButtons?.classList.remove("hidden");
        return;
    }

    // Teacher
    if (role === "teacher") {
        accountButtons?.classList.add("hidden");
        syncAccountToTeacherForm();
        teacherStepForm?.classList.remove("hidden");
        return;
    }

    // Student
    if (role === "student") {
        accountButtons?.classList.add("hidden");
        syncAccountToStudentForm();
        studentStepForm?.classList.remove("hidden");
    }
};



    if (roleSelect && openAddAccountBtn) {
        setAddAccountButtonLabel();
        updateAddAccountButtonState(roleSelect.value);
        roleSelect.addEventListener("change", function () {
            setAddAccountButtonLabel();
            updateAddAccountButtonState(roleSelect.value);
            toggleRoleSections(roleSelect.value);
        });

    }

    document.addEventListener("change", (event) => {
        if (event.target && event.target.id === "cmbtype") {
            updateAddAccountButtonLabel(event.target.value);
            updateAddAccountButtonState(event.target.value);
        }
    });

    if (openAddAccountBtn) {
        openAddAccountBtn.addEventListener("click", function () {
            const { hasError, role } = validateAddAccountFields();
            if (hasError) return;

            if (role === "teacher") {
                const hasTeacherError = validateTeacherFields();
                if (hasTeacherError) return;

                const modal = document.getElementById("lmsmodal");
                document.getElementById("lmsmodaltitle").textContent = "Add Teacher";
                document.getElementById("lmsmodalcontent").innerHTML =
                    document.getElementById("confirmAddTeacherFromAccountModal").innerHTML;
                modal.classList.remove("hidden");
                return;
            }

            if (role === "student") {
                const hasStudentError = validateStudentFields();
                if (hasStudentError) return;

                const modal = document.getElementById("lmsmodal");
                document.getElementById("lmsmodaltitle").textContent = "Add Student";
                document.getElementById("lmsmodalcontent").innerHTML =
                    document.getElementById("confirmAddStudentFromAccountModal").innerHTML;
                modal.classList.remove("hidden");
                return;
            }


            showAddAccountModal();
        });
    }

    if (addAccountForm && roleSelect && openAddAccountBtn) {
        addAccountForm.addEventListener("submit", function (event) {
            if (roleSelect.value === "teacher" || roleSelect.value === "student") {
                event.preventDefault();
                openAddAccountBtn.click();
            }
        });
    }

    ["txtusername", "txtemail", "txtpassword", "txtconfirmpass"].forEach((id) => {
        const field = document.getElementById(id);
        if (field) {
            field.addEventListener("input", () => {
                updateAddAccountButtonState();
                syncAccountToRoleForms();
            });
        }
    });

    // Teacher Details Step Handler (account data staged)
    const teacherFirstNameInput = document.getElementById("teacherFirstName");
    const teacherLastNameInput = document.getElementById("teacherLastName");
    const teacherFacultyInput = document.getElementById("teacherFaculty");
    const errorTeacherFirstName = document.getElementById("errorTeacherFirstName");
    const errorTeacherLastName = document.getElementById("errorTeacherLastName");
    const errorTeacherFaculty = document.getElementById("errorTeacherFaculty");
    const openAddTeacherFromAccountBtn = document.getElementById("openAddTeacherFromAccountModal");
    const clearTeacherFromAccountBtn = document.getElementById("btnClearTeacherFromAccount");

    const validateTeacherFields = () => {
        const firstName = teacherFirstNameInput?.value.trim() || "";
        const lastName = teacherLastNameInput?.value.trim() || "";
        const faculty = teacherFacultyInput?.value || "";

        let hasError = false;

        if (!firstName) {
            errorTeacherFirstName?.classList.remove("hidden");
            hasError = true;
        } else {
            errorTeacherFirstName?.classList.add("hidden");
        }

        if (!lastName) {
            errorTeacherLastName?.classList.remove("hidden");
            hasError = true;
        } else {
            errorTeacherLastName?.classList.add("hidden");
        }




        if (!faculty) {
            errorTeacherFaculty?.classList.remove("hidden");
            hasError = true;
        } else {
            errorTeacherFaculty?.classList.add("hidden");
        }

        return hasError;
    };

        if (openAddTeacherFromAccountBtn) {
        openAddTeacherFromAccountBtn.addEventListener("click", function () {
            syncAccountToTeacherForm();

            // ✅ Validate account fields first
            const { hasError: accountError } = validateAddAccountFields();
            if (accountError) return;

            // ✅ Then validate teacher fields
            const hasError = validateTeacherFields();
            if (hasError) return;


            const modal = document.getElementById("lmsmodal");
            document.getElementById("lmsmodaltitle").textContent = "Add Teacher";
            document.getElementById("lmsmodalcontent").innerHTML =
                document.getElementById("confirmAddTeacherFromAccountModal").innerHTML;
            modal.classList.remove("hidden");
        });
    }


    if (clearTeacherFromAccountBtn) {
    clearTeacherFromAccountBtn.addEventListener("click", function (event) {
        event.preventDefault();

        // clear teacher fields
        if (teacherFirstNameInput) teacherFirstNameInput.value = "";
        if (teacherLastNameInput) teacherLastNameInput.value = "";
        if (teacherFacultyInput) teacherFacultyInput.selectedIndex = 0;

        errorTeacherFirstName?.classList.add("hidden");
        errorTeacherLastName?.classList.add("hidden");
        errorTeacherFaculty?.classList.add("hidden");

        // clear account fields too
        clearAccountFields();
    });
}


    teacherFirstNameInput?.addEventListener("input", () => errorTeacherFirstName?.classList.add("hidden"));
    teacherLastNameInput?.addEventListener("input", () => errorTeacherLastName?.classList.add("hidden"));
    teacherFacultyInput?.addEventListener("change", () => errorTeacherFaculty?.classList.add("hidden"));
    teacherStepForm?.addEventListener("submit", syncAccountToTeacherForm);

    // Student Details Step Handler (account data staged)
    const studentFirstNameInput = document.getElementById("studentFirstName");
    const studentLastNameInput = document.getElementById("studentLastName");
    const studentGuardianEmailInput = document.getElementById("studentGuardianEmail");
    const studentGradeInput = document.getElementById("studentGrade");
    const errorStudentFirstName = document.getElementById("errorStudentFirstName");
    const errorStudentLastName = document.getElementById("errorStudentLastName");
    const errorStudentGuardianEmail = document.getElementById("errorStudentGuardianEmail");
    const errorStudentGrade = document.getElementById("errorStudentGrade");
    const openAddStudentFromAccountBtn = document.getElementById("openAddStudentFromAccountModal");
    const clearStudentFromAccountBtn = document.getElementById("btnClearStudentFromAccount");

const validateStudentFields = () => {
    const firstName = studentFirstNameInput?.value.trim() || "";
    const lastName = studentLastNameInput?.value.trim() || "";
    const guardianEmail = studentGuardianEmailInput?.value.trim() || "";
    const grade = studentGradeInput?.value || "";

    let hasError = false;

    if (!firstName) {
        errorStudentFirstName?.classList.remove("hidden");
        hasError = true;
    } else {
        errorStudentFirstName?.classList.add("hidden");
    }

    if (!lastName) {
        errorStudentLastName?.classList.remove("hidden");
        hasError = true;
    } else {
        errorStudentLastName?.classList.add("hidden");
    }

    if (guardianEmail && !emailRegex.test(guardianEmail)) {
        if (errorStudentGuardianEmail) {
            errorStudentGuardianEmail.textContent = "Please enter a valid guardian email.";
            errorStudentGuardianEmail.classList.remove("hidden");
        }
        hasError = true;
    } else {
        errorStudentGuardianEmail?.classList.add("hidden");
    }

    if (!grade) {
        errorStudentGrade?.classList.remove("hidden");
        hasError = true;
    } else {
        errorStudentGrade?.classList.add("hidden");
    }

    return hasError;
};

    if (openAddStudentFromAccountBtn) {
    openAddStudentFromAccountBtn.addEventListener("click", function () {
        syncAccountToStudentForm();

        // ✅ Validate account fields first
        const { hasError: accountError } = validateAddAccountFields();
        if (accountError) return;

        // ✅ Then validate student fields
        const hasError = validateStudentFields();
        if (hasError) return;


            const modal = document.getElementById("lmsmodal");
            document.getElementById("lmsmodaltitle").textContent = "Add Student";
            document.getElementById("lmsmodalcontent").innerHTML =
                document.getElementById("confirmAddStudentFromAccountModal").innerHTML;
            modal.classList.remove("hidden");
        });
    }


    if (clearStudentFromAccountBtn) {
    clearStudentFromAccountBtn.addEventListener("click", function (event) {
        event.preventDefault();

        // clear student fields
        if (studentFirstNameInput) studentFirstNameInput.value = "";
        if (studentLastNameInput) studentLastNameInput.value = "";
        if (studentGuardianEmailInput) studentGuardianEmailInput.value = "";
        if (studentGradeInput) studentGradeInput.selectedIndex = 0;

        errorStudentFirstName?.classList.add("hidden");
        errorStudentLastName?.classList.add("hidden");
        errorStudentGuardianEmail?.classList.add("hidden");
        errorStudentGrade?.classList.add("hidden");

        // clear account fields too
        clearAccountFields();
    });
}


    studentFirstNameInput?.addEventListener("input", () => errorStudentFirstName?.classList.add("hidden"));
    studentLastNameInput?.addEventListener("input", () => errorStudentLastName?.classList.add("hidden"));
    studentGuardianEmailInput?.addEventListener("input", () => errorStudentGuardianEmail?.classList.add("hidden"));
    studentGradeInput?.addEventListener("change", () => errorStudentGrade?.classList.add("hidden"));
    studentStepForm?.addEventListener("submit", syncAccountToStudentForm);

    // Update Account Modal Handler + Validations
const openUpdateAccountBtn = document.getElementById("openUpdateAccountModal");

if (openUpdateAccountBtn) {

    openUpdateAccountBtn.addEventListener("click", function () {

        const nameInput = document.getElementById("txtupdateUsername");
        const emailInput = document.getElementById("txtupdateEmail");
        const passwordInput = document.getElementById("txtupdatePassword");
        const confirmInput = document.getElementById("txtupdateConfirmPass");

        const errorUsername = document.getElementById("errorUpdateUsername");
        const errorEmail = document.getElementById("errorUpdateEmail");
        const errorPassword = document.getElementById("errorUpdatePassword");
        const errorConfirm = document.getElementById("errorUpdateConfirmPass");

        const name = nameInput?.value.trim() || "";
        const email = emailInput?.value.trim() || "";
        const password = passwordInput?.value.trim() || "";
        const confirm = confirmInput?.value.trim() || "";

        let hasError = false;

        /* ---------- USERNAME ---------- */
        if (!name) {
            if (errorUsername) errorUsername.classList.remove("hidden");
            hasError = true;
        } else {
            if (errorUsername) errorUsername.classList.add("hidden");
        }

        /* ---------- EMAIL ---------- */
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

        if (!email || !emailRegex.test(email)) {
            if (errorEmail) {
                errorEmail.textContent = "Please enter a valid email.";
                errorEmail.classList.remove("hidden");
            }
            hasError = true;
        } else {
            if (errorEmail) errorEmail.classList.add("hidden");
        }

        /* ---------- PASSWORD (OPTIONAL) ---------- */
        if (password) {

            if (password.length < 6) {
                if (errorPassword) {
                    errorPassword.textContent = "Password must be at least 6 characters.";
                    errorPassword.classList.remove("hidden");
                }
                hasError = true;
            } else {
                if (errorPassword) errorPassword.classList.add("hidden");
            }

            if (password !== confirm) {
                if (errorConfirm) {
                    errorConfirm.textContent = "Passwords must match.";
                    errorConfirm.classList.remove("hidden");
                }
                hasError = true;
            } else {
                if (errorConfirm) errorConfirm.classList.add("hidden");
            }

        } else {
            // Hide errors safely if password empty
            if (errorPassword) errorPassword.classList.add("hidden");
            if (errorConfirm) errorConfirm.classList.add("hidden");
        }

        /* ---------- OPEN MODAL ---------- */
        if (!hasError) {
            const modal = document.getElementById("lmsmodal");
            const modalTitle = document.getElementById("lmsmodaltitle");
            const modalContent = document.getElementById("lmsmodalcontent");
            const confirmContent = document.getElementById("confirmUpdateModal");

            if (modal && modalTitle && modalContent && confirmContent) {
                modalTitle.textContent = "Update Account";
                modalContent.innerHTML = confirmContent.innerHTML;
                modal.classList.remove("hidden");
            }
        }

    });

}



    //toggle passwords for add
    const togglePass1 = document.getElementById('togglePassword');
    const passInput1 = document.getElementById('txtpassword');

    if (togglePass1 && passInput1) {
        togglePass1.addEventListener('click', function () {
            const isHidden = passInput1.getAttribute('type') === 'password';
            passInput1.setAttribute('type', isHidden ? 'text' : 'password');
            this.classList.toggle('icofont-eye-blocked');
            this.classList.toggle('icofont-eye');
        });
    }

    // ✅ Toggle Password Visibility (Confirm Password Field)
    const togglePass2 = document.getElementById('togglePassword2');
    const passInput2 = document.getElementById('txtconfirmpass');

    if (togglePass2 && passInput2) {
        togglePass2.addEventListener('click', function () {
            const isHidden = passInput2.getAttribute('type') === 'password';
            passInput2.setAttribute('type', isHidden ? 'text' : 'password');
            this.classList.toggle('icofont-eye');
            this.classList.toggle('icofont-eye-blocked');
        });
    }


    
    
    // Disable Confirm Password until Password has value (UPDATE)
    const updatePassInput = document.getElementById("txtupdatePassword");
    const updateConfirmInput = document.getElementById("txtupdateConfirmPass");

    if (updatePassInput && updateConfirmInput) {
        // disable on load
        updateConfirmInput.disabled = !updatePassInput.value.trim();

        updatePassInput.addEventListener("input", () => {
            if (updatePassInput.value.trim() !== "") {
                updateConfirmInput.disabled = false;
            } else {
                updateConfirmInput.disabled = true;
                updateConfirmInput.value = "";
            }
        });
    }

    //Toggle passwords for update
    const togglePass1A = document.getElementById('toggleUpdatePassword');
    const passInput1A = document.getElementById('txtupdatePassword');

    if (togglePass1A && passInput1A) {
        togglePass1A.addEventListener('click', function () {
            const isHidden = passInput1A.getAttribute('type') === 'password';
            passInput1A.setAttribute('type', isHidden ? 'text' : 'password');
            this.classList.toggle('icofont-eye');
            this.classList.toggle('icofont-eye-blocked');
        });
    }

    // Toggle for Confirm Password
    const togglePass2B = document.getElementById('toggleUpdatePassword2');
    const passInput2B = document.getElementById('txtupdateConfirmPass');

    if (togglePass2B && passInput2B) {
        togglePass2B.addEventListener('click', function () {
            const isHidden = passInput2B.getAttribute('type') === 'password';
            passInput2B.setAttribute('type', isHidden ? 'text' : 'password');
            this.classList.toggle('icofont-eye');
            this.classList.toggle('icofont-eye-blocked');
        });
    }

    //Clear add account form
    const clearBtn = document.getElementById("btnAddAccountClear");

    if (clearBtn) {
        clearBtn.addEventListener("click", function (event) {
            event.preventDefault(); // Prevent any default behavior

            // ✅ Clear text fields
            const username = document.getElementById("txtusername");
            const email = document.getElementById("txtemail");
            const password = document.getElementById("txtpassword");
            const confirmPass = document.getElementById("txtconfirmpass");

            if (username) username.value = "";
            if (email) email.value = "";
            if (password) password.value = "";
            if (confirmPass) confirmPass.value = "";

            // ✅ Reset dropdown
            const roleSelect = document.getElementById("cmbtype");
            if (roleSelect) roleSelect.selectedIndex = 0;

            // ✅ Hide all possible errors
            const errors = [
                "errorusername",
                "erroremail",
                "errorpassword",
                "errorconfirmpass",
                "errortype"
            ];

            errors.forEach(id => {
                const el = document.getElementById(id);
                if (el) el.classList.add("hidden");
            });

            setAddAccountButtonLabel();
            updateAddAccountButtonState();
        });
    }

    //clear update form
    const clearUpdateBtn = document.getElementById('btnUpdateAccountClear');
    const updateForm = document.getElementById('formupdateaccount');

    if (clearUpdateBtn && updateForm) {
        clearUpdateBtn.addEventListener('click', function () {
            // Reset the form
            updateForm.reset();

            // Manually clear fields (because value="" from Blade keeps old values)
            document.getElementById('txtupdateUsername').value = '';
            document.getElementById('txtupdateEmail').value = '';
            document.getElementById('txtupdatePassword').value = '';
            document.getElementById('txtupdateConfirmPass').value = '';

            // Reset dropdowns
            document.getElementById('updateType').selectedIndex = 0;
            document.getElementById('status').selectedIndex = 0;

            // Hide any validation errors
            document.querySelectorAll('[id^="errorUpdate"]').forEach(el => el.classList.add('hidden'));
            const errorStatus = document.getElementById('errorstatus');
            if (errorStatus) errorStatus.classList.add('hidden');
        });
    }
});

// Function to set up input validation listeners (called once)
function setupInputValidationListeners() {
    const usernameInput = document.getElementById("txtusername");
    if (usernameInput) {
        usernameInput.addEventListener("input", function () {
            document.getElementById("errorusername").classList.add("hidden");
        });
    }

    const emailInput = document.getElementById("txtemail");
    if (emailInput) {
        emailInput.addEventListener("input", function () {
            document.getElementById("erroremail").classList.add("hidden");
        });
    }

    const passwordInput = document.getElementById("txtpassword");
    if (passwordInput) {
        passwordInput.addEventListener("input", function () {
            document.getElementById("errorpassword").classList.add("hidden");
            document.getElementById("errorconfirmpass").classList.add("hidden");
        });
    }

    const confirmPassInput = document.getElementById("txtconfirmpass");
    if (confirmPassInput) {
        confirmPassInput.addEventListener("input", function () {
            document.getElementById("errorconfirmpass").classList.add("hidden");
        });
    }

    const roleSelect = document.getElementById("cmbtype");
    if (roleSelect) {
        roleSelect.addEventListener("change", function () {
            document.getElementById("errortype").classList.add("hidden");
        });
    }
}

//(Account) Handle update button clicks
document.addEventListener("click", function(e) {
    if (e.target.classList.contains("btnupdate") || e.target.closest(".btnupdate")) {
        e.preventDefault();
        e.stopPropagation();
        
        
        // Get user data from the table row
        const row = e.target.closest("tr");
        if (row) {
            const userId = row.querySelector("td:first-child").textContent.trim();
            const userName = row.querySelector("td:nth-child(2)").textContent.trim();
            const userEmail = row.querySelector("td:nth-child(3)").textContent.trim();
            const userStatus = row.querySelector("td:nth-child(5) p")?.textContent.trim() || "";

            
            console.log("User Data:", {
                id: userId,
                name: userName,
                email: userEmail,
                status: userStatus
            });
            
            if (userId) {
                window.location.href = `/admin/accounts/${userId}/edit`;
            }
            
        }
    }
});

// Handle Delete button clicks
document.addEventListener("click", function(e) {
    if (e.target.classList.contains("btndelete") || e.target.closest(".btndelete")) {
        e.preventDefault();
        e.stopPropagation();

        // Get user data from the table row
        const row = e.target.closest("tr");
        if (!row) return;

        const userId = row.querySelector("td:first-child").textContent.trim();
        const userName = row.querySelector("td:nth-child(2)").textContent.trim();
        const userEmail = row.querySelector("td:nth-child(3)").textContent.trim();
        const statusEl = row.querySelector("td:nth-child(4) span") || row.querySelector("td:nth-child(4) p");
        const userStatus = statusEl ? statusEl.textContent.trim() : "";

        console.log("User Data:", { id: userId, name: userName, email: userEmail, status: userStatus });

        // Show the modal
        const modal = document.getElementById("lmsmodal");
        document.getElementById("lmsmodaltitle").textContent = "Delete Account";
        document.getElementById("lmsmodalcontent").innerHTML =
            document.getElementById("confirmDeleteModal").innerHTML;
        modal.classList.remove("hidden");

        // Grab the Confirm button from the inserted modal content
        const confirmBtn = document.getElementById("confirmDelete");

        // Set the userId on the button
        confirmBtn.dataset.userId = userId;

        // Remove previous click listeners to avoid duplicates
        const newConfirmBtn = confirmBtn.cloneNode(true);
        confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);

        newConfirmBtn.addEventListener("click", function() {
            window.AdminModalLoading?.setLoading(newConfirmBtn, { text: "Deleting..." });
            const userId = this.dataset.userId;

            fetch(`/admin/accounts/${userId}`, {
                method: "DELETE",
                headers: {
                    "X-CSRF-TOKEN": document
                        .querySelector('meta[name="csrf-token"]')
                        .getAttribute('content'),
                    "Accept": "application/json",
                },
            })
            .then(response => response.json())
            .then(data => {
                if (!data?.success) {
                    window.AdminModalLoading?.clearLoading(newConfirmBtn);
                    return;
                }

                if (data.success) {
                    // Close modal
                    document.getElementById("lmsmodal").classList.add("hidden");

                    // Optional toast (will flash briefly before reload)
                    const toast = document.getElementById("toast-success");
                    const toastMessage = document.getElementById("toast-message");
                    if (toast && toastMessage) {
                        toastMessage.textContent = data.message || "Account deleted successfully!";
                        toast.classList.remove("hidden");
                    }

                    // ✅ REFRESH PAGE
                    setTimeout(() => {
                        window.location.reload();
                    }, 300);
                }
            })
            .catch(err => {
                window.AdminModalLoading?.clearLoading(newConfirmBtn);
                console.error(err);
            });
        });
    }
});


// Handle dropdown menu logic
document.addEventListener("click", (e) => {
    const btn = e.target.closest(".actionMenuBtn");

    // If click is not on a button -> close all menus
    if (!btn) {
        document.querySelectorAll(".actionMenu").forEach(menu => menu.classList.add("hidden"));
        return;
    }

    const menu = btn.closest(".relative").querySelector(".actionMenu");
    const isOpen = !menu.classList.contains("hidden");

    // Close all menus first
    document.querySelectorAll(".actionMenu").forEach(menu => menu.classList.add("hidden"));

    // If it *was* open, stop here (so it stays closed)
    if (isOpen) return;

    // Otherwise open it
    menu.classList.remove("hidden");
});
