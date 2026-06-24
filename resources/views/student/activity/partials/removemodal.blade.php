{{-- ================= REMOVE SUBMISSION MODAL ================= --}}
<div id="removeModal" class="fixed inset-0 hidden bg-black bg-opacity-50 flex justify-center items-center z-50">
    <div class="bg-white dark:bg-[#1E1E2D] p-6 rounded-lg shadow-lg w-[90%] sm:w-[400px]">

        <h2 class="text-xl font-bold text-[#373737] dark:text-white mb-3">
            Remove Submission?
        </h2>

        <p class="text-sm text-[#5E5F60] dark:text-gray-300 mb-4">
            This will completly remove your submission for this activity. Are you sure you want to proceed?
        </p>

        <div class="flex justify-end gap-2 ">
             <div> 
                <button onclick="document.getElementById('removeModal').classList.add('hidden')"
                        class="px-4 py-2  bg-gray-500 text-white rounded">
                    Cancel
                </button>
            
             </div>
                
            

            <form method="POST" class=" flex items-center" action="{{ route('student.activity.unified.remove', $activity->id) }}">
                @csrf
                <button type="submit"
                        class="px-4 py-2 bg-red-600 text-white rounded">
                    Remove
                </button>
            </form>
        </div>
    </div>
</div>