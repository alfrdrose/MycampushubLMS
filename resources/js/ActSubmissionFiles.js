console.log("Unified Submission JS Loaded");

document.addEventListener("DOMContentLoaded", () => {

    const hasSubmission = document.getElementById("has-submission")?.value === "1";

    // If submission exists, DO NOT RUN this JS
    if (hasSubmission) {
        console.warn("First-submission JS disabled because submission already exists");
        return;
    }

    const submitBtn    = document.getElementById("submitWorkButton");
    const modal        = document.getElementById("submitModal");
    const cancelModal  = document.getElementById("cancelSubmit");
    const confirmModal = document.getElementById("confirmSubmit");

    const form = document.getElementById("submitUnifiedForm");
    const essayInput = document.getElementById("essay_text_input");

    if (!form) {
        console.warn("Unified submission: Missing unified form");
        return;
    }

    // ======================================================
    // FILE ELEMENTS
    // ======================================================
    let selectedFiles = [];

    const fileInput  = document.getElementById("file-input");
    const fileUpload = document.getElementById("file-upload");
    const fileList   = document.getElementById("file-list");

    const maxFiles = parseInt(fileUpload?.dataset.maxFiles || 5);
    const minWords = parseInt(form?.dataset.minWords || 0);
    const maxWords = parseInt(form?.dataset.maxWords || 0);

    // TOTAL SIZE UI
    const totalSizeDisplay = document.getElementById("first-total-size");
    const totalSizeError   = document.getElementById("first-size-error");

    // MAX TOTAL MB FROM BLADE (PASSED AS DATA ATTRIBUTE)
    const maxTotalMB = parseInt(fileUpload?.dataset.maxTotal || 50);


    // ======================================================
    // FILE HANDLING
    // ======================================================
    if (fileUpload && fileInput) {
        // Initial validation state on load
validateFilesPresent();
updateTotalSize();

        fileUpload.addEventListener("click", e => {
            e.preventDefault();
            fileInput.click();
        });

        fileInput.addEventListener("change", e => {
            addFiles(Array.from(e.target.files));
        });

        fileUpload.addEventListener("dragover", e => e.preventDefault());

        fileUpload.addEventListener("drop", e => {
            e.preventDefault();
            addFiles(Array.from(e.dataTransfer.files));
        });
    }
    function syncFileInput() {
    if (!fileInput) return;

    const dt = new DataTransfer();
    selectedFiles.forEach(file => dt.items.add(file));
    fileInput.files = dt.files;
}



    function addFiles(files) {
        selectedFiles = [...selectedFiles, ...files];

        if (selectedFiles.length > maxFiles) {

            LMSModal.open({
                title: "File Limit Reached",
                content: `
                    <p class="text-[#5E5F60] dark:text-white mb-4">
                        You can only upload up to ${maxFiles} files.
                    </p>
                    <div class="flex justify-end">
                        <button data-close="lmsmodal"
                                class="px-4 py-2 bg-[#5E5F60] text-white rounded">
                            OK
                        </button>
                    </div>`
            });

            selectedFiles = selectedFiles.slice(0, maxFiles);
        }
        syncFileInput();
        renderFiles();
        updateTotalSize();
    }
    function validateFilesPresent() {
    const required = fileUpload?.dataset.required === "1";
    const valid = required ? selectedFiles.length > 0 : true;

    window.dispatchEvent(new CustomEvent("files-valid", {
        detail: valid
    }));
}



    function renderFiles() {
    if (!fileList) return;

    fileList.innerHTML = "";

    selectedFiles.forEach((file, index) => {
        const div = document.createElement("div");
        div.className =
            "flex justify-between items-center bg-gray-100 dark:bg-[#2A2A3C] p-2 rounded-lg";

        div.innerHTML = `
            <span class="text-[#373737] dark:text-white">${file.name}</span>
            <button data-index="${index}" class="text-red-500">Remove</button>
        `;

        fileList.appendChild(div);
    });

    fileList.querySelectorAll("button").forEach(btn => {
        btn.addEventListener("click", e => {
            syncFileInput(); 
            selectedFiles.splice(e.target.dataset.index, 1);
            renderFiles();
            
            updateTotalSize();
            validateFilesPresent();
        });
    });

    validateFilesPresent();
}




    // ======================================================
    // TOTAL SIZE VALIDATION
    // ======================================================
    function updateTotalSize() {
    if (!totalSizeDisplay) return;

    let totalBytes = selectedFiles.reduce((s,f)=> s + f.size, 0);
    let totalMB = (totalBytes / (1024*1024)).toFixed(2);

    totalSizeDisplay.textContent = `${totalMB} MB`;

    const ok = totalMB <= maxTotalMB;

    window.dispatchEvent(new CustomEvent("files-size-valid", {
        detail: ok
    }));

    if (!ok) {
        totalSizeDisplay.classList.remove("text-green-500");
        totalSizeDisplay.classList.add("text-red-500");
        totalSizeError?.classList.remove("hidden");
    } else {
        totalSizeDisplay.classList.remove("text-red-500");
        totalSizeDisplay.classList.add("text-green-500");
        totalSizeError?.classList.add("hidden");
    }
}





    // ======================================================
    // SUBMIT HANDLING
    // ======================================================
    submitBtn?.addEventListener("click", validateBeforeSubmit);

    function validateBeforeSubmit() {

        // Essay validation
        if (essayInput) {
            const text = essayInput.value.trim();
            const wordCount = text.split(/\s+/).filter(w => w.length > 0).length;

            if (minWords && wordCount < minWords)
                return showError(`Your essay must have at least ${minWords} words.`);

            if (maxWords && wordCount > maxWords)
                return showError(`Your essay cannot exceed ${maxWords} words.`);
        }

        // File required
        if (fileUpload &&
            selectedFiles.length === 0 &&
            fileUpload.dataset.required === "1"
        ) {
            return showError("Please upload at least one file.");
        }

        // Total size check
        let totalBytes = selectedFiles.reduce((s,f)=> s + f.size, 0);
        let totalMB = (totalBytes / (1024*1024)).toFixed(2);

        if (totalMB > maxTotalMB)
            return showError(`Total files exceed ${maxTotalMB} MB.`);
syncFileInput();

        if (!modal) return form.submit();

        modal.classList.remove("hidden");
    }



    cancelModal?.addEventListener("click", () => {
        modal?.classList.add("hidden");
    });

    confirmModal?.addEventListener("click", () => submitUnified());



    // ======================================================
    // ACTUAL SUBMIT
    // ======================================================
    function submitUnified() {

        const route = form.action;
        const csrf = document.querySelector('input[name="_token"]')?.value;

        if (!csrf)
            return showError("Missing CSRF token");

        let formData = new FormData();

        if (essayInput)
            formData.append("essay_text", essayInput.value);

        if (selectedFiles.length > 0)
            selectedFiles.forEach(f => formData.append("files[]", f));

        fetch(route, {
            method: "POST",
            headers: { "X-CSRF-TOKEN": csrf },
            body: formData
        })
        .then(async res => {
            console.log("RAW RESPONSE:", res);

            if (res.redirected)
                return window.location = res.url;

            try {
                const data = await res.json();
                if (data?.error)
                    return showError(data.error);

                location.reload();
            } catch {
                location.reload();
            }

        })
        .catch(err => {
            console.error("FETCH ERROR:", err);
            showError("Submission failed — check console");
        });
    }



    function showError(msg) {
        LMSModal.open({
            title: "Submission Error",
            content: `
                <p class="text-[#5E5F60] dark:text-white mb-4">
                    ${msg}
                </p>
                <div class="flex justify-end">
                    <button data-close="lmsmodal"
                            class="px-4 py-2 bg-red-600 text-white rounded">
                        Close
                    </button>
                </div>
            `
        });
    }

});
