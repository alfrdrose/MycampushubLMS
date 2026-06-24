<!-- Post Announcement Modal -->
<div
    id="announcementModal"
    class="fixed inset-0 bg-black bg-opacity-50 hidden justify-center items-center z-50"
>
    <div class="bg-white dark:bg-[#1E1E2D] rounded-lg shadow-lg w-full max-w-2xl p-6">
        <h2 class="text-xl font-bold text-[#5F2DED] mb-4">
            Post Announcement
        </h2>

        <form
            id="announcementForm"
            action="{{ route('teacher.announcements.store') }}"
            method="POST"
            onsubmit="handleAnnouncementSubmit(this)"
        >
            @csrf
            <input type="hidden" name="section_subject_id" value="{{ $schedule->id }}">

            {{-- Title --}}
            <div class="mb-4">
                <label class="block mb-1 text-sm font-medium dark:text-gray-50">
                    Title
                </label>

                <input
                    type="text"
                    id="announcementTitle"
                    maxlength="30"
                    name="title"
                    class="w-full bg-transparent dark:text-white border rounded px-3 py-2"
                >
                <div class="flex justify-between w-full"> 
                    <p id="titleCounter" class="text-xs text-gray-500 mt-1">
                    0 / 30
                    </p>

                    <p id="titleError" class="hidden text-sm text-red-500 mt-1">
                        Title is required
                    </p>
                    
                </div>
                
            </div>

            {{-- Body --}}
            <div class="mb-6">
                <label class="block mb-1 text-sm font-medium dark:text-gray-50">
                    Body
                </label>

                <input id="announcementBody" type="hidden" name="body">

                <trix-editor
                    input="announcementBody"
                    class="trix-content bg-transparent dark:text-white h-[300px] overflow-y-auto"
                ></trix-editor>

                <p id="bodyError" class="hidden text-sm text-red-500 mt-1">
                    Body is required
                </p>
            </div>

            {{-- Actions --}}
            <div class="flex justify-end gap-2 items-center">
                <button
                    type="button"
                    id="cancelPostBtn"
                    onclick="toggleModal()"
                    class="px-4 py-2 border rounded dark:text-white"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    id="postBtn"
                    disabled
                    class="px-4 py-2 bg-[#5F2DED] text-white rounded opacity-50 cursor-not-allowed flex items-center gap-2"
                >
                    <span>Post</span>
                </button>
            </div>

            {{-- Loading --}}
            <div id="postLoading" class="hidden mt-4 flex items-center gap-2 text-sm text-gray-500 dark:text-gray-300">
                <svg class="animate-spin h-4 w-4 text-[#5F2DED]" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor"
                          d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z">
                    </path>
                </svg>
                <span>Posting…</span>
            </div>
        </form>
    </div>
</div>




<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 hidden justify-center items-center z-50">
    <div class="bg-white dark:bg-[#1E1E2D] rounded-lg shadow-lg w-full max-w-md p-6">
        <h2 class="text-xl font-bold text-red-600 mb-4">Confirm Delete</h2>

        <p class="mb-4 dark:text-white">
            Are you sure you want to delete this announcement? This action cannot be undone.
        </p>

        <form
            id="deleteForm"
            method="POST"
            action=""
            onsubmit="handleDeleteSubmit(this)"
        >
            @csrf
            @method('DELETE')

            <div class="flex justify-end gap-2 items-center">
                <button
                    type="button"
                    id="cancelDeleteBtn"
                    onclick="closeDeleteModal()"
                    class="px-4 py-2 dark:text-white border rounded"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    id="confirmDeleteBtn"
                    class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 flex items-center gap-2"
                >
                    <span class="delete-text">Delete</span>
                </button>
            </div>

            {{-- Loading Indicator --}}
            <div class="delete-loading hidden mt-4 flex items-center gap-2 text-sm text-gray-500 dark:text-gray-300">
                <svg class="animate-spin h-4 w-4 text-red-600" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor"
                          d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z">
                    </path>
                </svg>
                <span>Deleting…</span>
            </div>
        </form>
    </div>
</div>



<!-- Invalid Input Modal -->
<div
    id="invalidInputModal"
    class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50"
>
    <div class="bg-white dark:bg-[#1E1E2D] rounded-lg shadow-lg max-w-sm w-full p-6 text-center">
        <h2 class="text-lg font-semibold text-red-600 mb-2">
            Invalid Input
        </h2>

        <p class="text-sm text-gray-600 dark:text-gray-300 mb-4">
            You cannot enter long unbroken text. Please add spaces.
        </p>

        <button
            onclick="closeInvalidInputModal()"
            class="px-4 py-2 bg-[#5F2DED] text-white rounded hover:bg-purple-700 w-full"
        >
            OK
        </button>
    </div>
</div>
