@props(['activity', 'attemptsRemaining'])

<div 
    x-data="mobileEssayUploader({ 
        submitUrl: '{{ route('student.submission.essay', $activity->id) }}',
        attemptsRemaining: {{ $attemptsRemaining ?? 0 }}
    })"
    class="w-full"
>
    <input type="hidden" id="essay_text_input">

    <trix-editor 
        input="essay_text_input"
        class="p-3 w-full max-h-[600px] overflow-y-auto bg-white dark:bg-[#1E1E2D] 
               text-[#373737] dark:text-white rounded-lg border"
        @trix-change="essayText = $event.target.value"
    ></trix-editor>

    <div class="flex flex-row items-center">
        <div class="w-1/2"> 
            @if($attemptsRemaining > 0)
            <p class="text-xs md:text-sm text-gray-600 dark:text-gray-300">
                Attempts remaining: <span class="font-semibold">{{ $attemptsRemaining }}</span>
            </p>
            @else
            <p class="text-xs md:text-sm text-red-500 font-semibold">
                You have reached the maximum number of attempts.
            </p>
            @endif
        </div>

        <div class="w-1/2 flex items-center justify-end">
            <button 
                type="button"
                class="mt-4 bg-primaryColor text-white py-2 px-3 text-sm rounded-lg
                    md:py-2 md:px-6 md:text-base"
                @click="checkBeforeSubmit()"
            >
                Submit Essay
            </button>

        </div>
    </div>

    {{-- Confirmation Modal --}}
    <div 
        class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
        x-show="confirmSubmit"
        x-transition
    >
        <div class="bg-white dark:bg-[#1F1F22] p-6 rounded-lg w-80 shadow-lg">
            <h2 class="text-lg font-bold mb-3 text-gray-800 dark:text-gray-100">
                Submit Essay?
            </h2>
            <p class="text-gray-600 dark:text-gray-300 mb-6">
                Are you sure you want to submit your essay?
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
                    @click="submitEssay()"
                >
                    Yes, Submit
                </button>
            </div>
        </div>
    </div>

    {{-- Empty Modal --}}
    <div 
        class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
        x-show="showEmptyModal"
        x-transition
    >
        <div class="bg-white dark:bg-[#1F1F22] p-6 rounded-lg w-80 shadow-lg">
            <h2 class="text-lg font-bold mb-3 text-gray-800 dark:text-gray-100">
                Warning
            </h2>
            <p class="text-gray-600 dark:text-gray-300 mb-6">
                Please write something before submitting.
            </p>
            <div class="flex justify-end">
                <button 
                    class="px-4 py-2 bg-primaryColor text-white rounded dark:bg-primaryColor/90"
                    @click="showEmptyModal = false"
                >
                    OK
                </button>
            </div>
        </div>
    </div>

    {{-- Max Attempts Modal --}}
    <div 
        class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
        x-show="showMaxAttemptsModal"
        x-transition
    >
        <div class="bg-white dark:bg-[#1F1F22] p-6 rounded-lg w-80 shadow-lg">
            <h2 class="text-lg font-bold mb-3 text-gray-800 dark:text-gray-100">
                Max Attempts Reached
            </h2>
            <p class="text-gray-600 dark:text-gray-300 mb-6">
                You have reached the maximum number of attempts for this activity.
            </p>
            <div class="flex justify-end">
                <button 
                    class="px-4 py-2 bg-primaryColor text-white rounded dark:bg-primaryColor/90"
                    @click="showMaxAttemptsModal = false"
                >
                    OK
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('mobileEssayUploader', ({ submitUrl, attemptsRemaining }) => ({
        essayText: '',
        confirmSubmit: false,
        showEmptyModal: false,
        showMaxAttemptsModal: false,
        attemptsRemaining,

        checkBeforeSubmit() {
            if (this.attemptsRemaining <= 0) {
                this.showMaxAttemptsModal = true;
                return;
            }
            if (!this.essayText.trim()) {
                this.showEmptyModal = true;
                return;
            }
            this.confirmSubmit = true;
        },

        async submitEssay() {
            this.confirmSubmit = false;

            const formData = new FormData();
            formData.append('essay_text', this.essayText);

            try {
                let response = await fetch(submitUrl, {
                    method: 'POST',
                    headers: {
                        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: formData
                });

                let data = await response.json();

                if (data.success) {
                    window.location.reload();
                }
            } catch (err) {
                console.error(err);
                window.location.reload();
            }
        }
    }));
});
</script>
