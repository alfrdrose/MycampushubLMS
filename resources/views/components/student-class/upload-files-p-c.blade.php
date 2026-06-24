<div>

    <div id="file-upload"
         data-max-files="{{ $maxFiles }}"
         class="w-full sm:w-full border-2 border-dashed border-gray-300 p-6 sm:p-8 rounded-lg text-center cursor-pointer hover:border-primaryColor transition">

        <p class="text-[#373737] dark:text-white text-base">
            Drag & Drop files here or click to select
        </p>
        <p class="text-[#5E5F60] dark:text-white text-sm">
            Maximum number of files: {{ $maxFiles }}
        </p>

        <input type="file" id="file-input" name="files[]" multiple class="hidden">
    </div>

    <div id="file-list" class="mt-4 space-y-1 text-left"></div>

    <div class="flex flex-row items-center">
        <div class="w-1/2"> 
            @if($attemptsRemaining > 0)
                <p class="text-xs md:text-sm text-gray-600 dark:text-gray-300">
                    Attempts remaining: <span class="font-semibold">{{ $attemptsRemaining }}</span>
                </p>
            @else
                <p class="text-xs md:text-sm text-red-500 font-semibold">
                    Maximum attempts reached.
                </p>
            @endif
        </div>
        <div class="flex justify-end mt-4 w-1/2">
            <button id="submitWorkButton" type="button" class="px-5 py-2 bg-primaryColor text-white rounded-lg">
                Submit Work
            </button>
        </div>
    </div>
    
</div>
