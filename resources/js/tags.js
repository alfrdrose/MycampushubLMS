document.addEventListener("DOMContentLoaded", function () {
    setupTagInputValidationListeners();

    const openAddTagBtn = document.getElementById("openAddTagModal");
    if (openAddTagBtn) {
        openAddTagBtn.addEventListener("click", function () {
            const name = document.getElementById("txttagname").value.trim();
            const category = document.getElementById("txttagcategory").value.trim();

            let hasError = false;

            if (!name) {
                document.getElementById("errorTagName").classList.remove("hidden");
                hasError = true;
            } else {
                document.getElementById("errorTagName").classList.add("hidden");
            }

            if (!category) {
                document.getElementById("errorTagCategory").classList.remove("hidden");
                hasError = true;
            } else {
                document.getElementById("errorTagCategory").classList.add("hidden");
            }

            if (!hasError) {
                const modal = document.getElementById("lmsmodal");
                document.getElementById("lmsmodaltitle").textContent = "Add Tag";
                document.getElementById("lmsmodalcontent").innerHTML =
                    document.getElementById("confirmTagContent").innerHTML;
                modal.classList.remove("hidden");
            }
        });
    }

    const openUpdateTagBtn = document.getElementById("openUpdateTagModal");
    if (openUpdateTagBtn) {
        openUpdateTagBtn.addEventListener("click", function () {
            const name = document.getElementById("txtupdateTagName").value.trim();
            const category = document.getElementById("txtupdateTagCategory").value.trim();

            let hasError = false;

            if (!name) {
                document.getElementById("errorUpdateTagName").classList.remove("hidden");
                hasError = true;
            } else {
                document.getElementById("errorUpdateTagName").classList.add("hidden");
            }

            if (!category) {
                document.getElementById("errorUpdateTagCategory").classList.remove("hidden");
                hasError = true;
            } else {
                document.getElementById("errorUpdateTagCategory").classList.add("hidden");
            }

            if (!hasError) {
                const modal = document.getElementById("lmsmodal");
                document.getElementById("lmsmodaltitle").textContent = "Update Tag";
                document.getElementById("lmsmodalcontent").innerHTML =
                    document.getElementById("confirmUpdateTagModal").innerHTML;
                modal.classList.remove("hidden");
            }
        });
    }

    const clearBtn = document.getElementById("btnclearaddtag");
    if (clearBtn) {
        clearBtn.addEventListener("click", function (event) {
            event.preventDefault();

            const nameInput = document.getElementById("txttagname");
            const categoryInput = document.getElementById("txttagcategory");
            if (nameInput) nameInput.value = "";
            if (categoryInput) categoryInput.value = "";

            const nameError = document.getElementById("errorTagName");
            const categoryError = document.getElementById("errorTagCategory");

            if (nameError) nameError.classList.add("hidden");
            if (categoryError) categoryError.classList.add("hidden");
        });
    }

    const clearBtn2 = document.getElementById("btnUpdateTagClear");
    if (clearBtn2) {
        clearBtn2.addEventListener("click", function () {
            const nameInput = document.getElementById("txtupdateTagName");
            const categoryInput = document.getElementById("txtupdateTagCategory");
            if (nameInput) nameInput.value = "";
            if (categoryInput) categoryInput.value = "";

            const nameError = document.getElementById("errorUpdateTagName");
            const categoryError = document.getElementById("errorUpdateTagCategory");

            if (nameError) nameError.classList.add("hidden");
            if (categoryError) categoryError.classList.add("hidden");
        });
    }
});

function setupTagInputValidationListeners() {
    const nameInput = document.getElementById("txttagname");
    const categoryInput = document.getElementById("txttagcategory");

    if (nameInput) {
        nameInput.addEventListener("input", function () {
            const error = document.getElementById("errorTagName");
            if (error) error.classList.add("hidden");
        });
    }

    if (categoryInput) {
        categoryInput.addEventListener("input", function () {
            const error = document.getElementById("errorTagCategory");
            if (error) error.classList.add("hidden");
        });
    }

    const updateNameInput = document.getElementById("txtupdateTagName");
    if (updateNameInput) {
        updateNameInput.addEventListener("input", function () {
            const error = document.getElementById("errorUpdateTagName");
            if (error) error.classList.add("hidden");
        });
    }

    const updateCategoryInput = document.getElementById("txtupdateTagCategory");
    if (updateCategoryInput) {
        updateCategoryInput.addEventListener("input", function () {
            const error = document.getElementById("errorUpdateTagCategory");
            if (error) error.classList.add("hidden");
        });
    }
}

document.addEventListener("click", function (e) {
    if (e.target.classList.contains("btnupdatetag") || e.target.closest(".btnupdatetag")) {
        e.preventDefault();
        e.stopPropagation();

        const container = e.target.closest("[data-tag-id]");
        if (!container) return;

        const id = container.dataset.tagId;
        if (id) {
            window.location.href = `/admin/tags/${id}/edit`;
        }
    }
});

document.addEventListener("click", function (e) {
    if (e.target.classList.contains("btndeleteTag") || e.target.closest(".btndeleteTag")) {
        e.preventDefault();
        e.stopPropagation();

        const container = e.target.closest("[data-tag-id]");
        if (!container) return;

        const tagId = container.dataset.tagId;

        const modal = document.getElementById("lmsmodal");
        document.getElementById("lmsmodaltitle").textContent = "Delete Tag";
        document.getElementById("lmsmodalcontent").innerHTML =
            document.getElementById("confirmDeleteTagModal").innerHTML;
        modal.classList.remove("hidden");

        const confirmBtn = document.getElementById("confirmDeleteTag");
        confirmBtn.dataset.tagId = tagId;

        const newConfirmBtn = confirmBtn.cloneNode(true);
        confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);

        newConfirmBtn.addEventListener("click", function () {
            window.AdminModalLoading?.setLoading(newConfirmBtn, { text: "Deleting..." });
            const currentTagId = this.dataset.tagId;

            fetch(`/admin/tags/${currentTagId}`, {
                method: "DELETE",
                headers: {
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute("content"),
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
                        document.querySelectorAll(`[data-tag-id="${currentTagId}"]`).forEach(el => el.remove());

                        const toast = document.getElementById("toast-success");
                        const toastMessage = document.getElementById("toast-message");
                        if (toast && toastMessage) {
                            toastMessage.textContent = data.message || "Tag deleted successfully!";
                            toast.classList.remove("hidden");
                            setTimeout(() => toast.classList.add("hidden"), 3000);
                        }

                        document.getElementById("lmsmodal").classList.add("hidden");
                    }
                })
                .catch(err => {
                    window.AdminModalLoading?.clearLoading(newConfirmBtn);
                    console.error(err);
                });
        });
    }
});
