<div 
    x-data="mobileUploader({ 
        maxFiles: {{ $maxFiles ?? 5 }}, 
        submitUrl: '{{ route('student.submission.files', $activity->id) }}' 
    })"
    class="w-full"
>

    {{-- Upload Area --}}
    <div 
        class="border-2 border-dashed border-gray-300  rounded-lg p-6 text-center "
        @click="$refs.fileInput.click()"
    >
        <p class="text-gray-700 mb-1 dark:text-white">Tap to select files</p>
        <p class="text-sm text-gray-500">Max files: {{ $maxFiles ?? 5 }}</p>

        <input 
            type="file" 
            x-ref="fileInput" 
            multiple 
            class="hidden"
            @change="addFiles($event)"
        >
    </div>

    {{-- Selected Files List --}}
    <div class="mt-4 space-y-2" x-show="files.length > 0">
        <template x-for="(file, index) in files" :key="index">
            <div class="flex items-center justify-between bg-gray-100 dark:bg-[#2a2a3c] p-2 rounded-lg">
                <span class="truncate w-40 text-gray-800 dark:text-white" x-text="file.name"></span>

                <button 
                    class="text-red-600 text-sm"
                    @click="removeFile(index)"
                >
                    Remove
                </button>
            </div>
        </template>
    </div>

    @if($attemptsRemaining > 0)
        <p class="text-xs md:text-sm text-gray-600 dark:text-gray-300 mt-4">
            Attempts remaining: <span class="font-semibold">{{ $attemptsRemaining }}</span>
        </p>
    @else
        <p class="text-xs md:text-sm text-red-500 font-semibold mt-4">
            Maximum attempts reached.
        </p>
    @endif
    {{-- Submit Button --}}
    <button 
        class="mt-4 w-full bg-primaryColor text-white py-2 rounded-lg"
        @click="validateBeforeSubmit"
    >
        Submit
    </button>


    {{-- Max Files Modal --}}
    <div 
        class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
        x-show="showLimitModal"
        x-transition
    >
        <div class="bg-white dark:bg-[#1F1F22] p-6 rounded-lg w-80 shadow-lg">
            <h2 class="text-lg font-bold mb-2 text-gray-800 dark:text-gray-100">
                Maximum Files Reached
            </h2>

            <p class="text-gray-600 dark:text-gray-300 mb-4">
                You can upload up to {{ $maxFiles ?? 5 }} files only.
            </p>

            <div class="text-right">
                <button 
                    class="px-4 py-2 bg-primaryColor text-white rounded dark:bg-primaryColor/90"
                    @click="showLimitModal = false"
                >
                    OK
                </button>
            </div>
        </div>
    </div>


    {{-- Confirmation Modal --}}
    <div 
        class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
        x-show="confirmSubmit"
        x-transition
    >
        <div class="bg-white dark:bg-[#1F1F22] p-6 rounded-lg w-80 shadow-lg">
            <h2 class="text-lg font-bold mb-3 text-gray-800 dark:text-gray-100">Submit Files?</h2>

            <p class="text-gray-600 dark:text-gray-300 mb-6">
                Are you sure you want to submit your files?
            </p>

            <div class="flex justify-end gap-3">
                <button 
                    class="px-4 py-2 bg-gray-300 dark:bg-gray-600 dark:text-gray-200 rounded"
                    @click="confirmSubmit = false"
                >
                    No
                </button>

                <button 
                    class="px-4 py-2 bg-primaryColor text-white rounded dark:bg-primaryColor/90"
                    @click="submitFiles"
                >
                    Yes, Submit
                </button>
            </div>
        </div>
    </div>


    {{-- NEW: No Files Selected Modal --}}
    <div 
        class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
        x-show="showNoFilesModal"
        x-transition
    >
        <div class="bg-white dark:bg-[#1F1F22] p-6 rounded-lg w-80 shadow-lg">
            <h2 class="text-lg font-bold mb-2 text-gray-800 dark:text-gray-100">
                No Files Selected
            </h2>

            <p class="text-gray-600 dark:text-gray-300 mb-4">
                Please upload at least one file before submitting.
            </p>

            <div class="text-right">
                <button 
                    class="px-4 py-2 bg-primaryColor text-white rounded dark:bg-primaryColor/90"
                    @click="showNoFilesModal = false"
                >
                    OK
                </button>
            </div>
        </div>
    </div>

</div>


{{-- Alpine Script --}}
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('mobileUploader', ({ maxFiles, submitUrl }) => ({
        maxFiles,
        submitUrl,

        files: [],
        showLimitModal: false,
        showNoFilesModal: false,   // <-- NEW
        confirmSubmit: false,

        addFiles(event) {
            let selected = [...event.target.files];

            if (this.files.length + selected.length > this.maxFiles) {
                this.showLimitModal = true;
                return;
            }

            this.files.push(...selected);
        },

        removeFile(index) {
            this.files.splice(index, 1);
        },

        // NEW: check before showing confirmation modal
        validateBeforeSubmit() {
            if (this.files.length === 0) {
                this.showNoFilesModal = true;
                return;
            }
            this.confirmSubmit = true;
        },

        async submitFiles() {
            this.confirmSubmit = false;

            let form = new FormData();
            this.files.forEach(file => form.append('files[]', file));

            try {
                let response = await fetch(this.submitUrl, {
                    method: "POST",
                    headers: {
                        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: form
                });

                let data = await response.json();
                if (data.success) window.location.reload();

            } catch (err) {
                console.error(err);
                window.location.reload();
            }
        }
    }));
});
</script>
