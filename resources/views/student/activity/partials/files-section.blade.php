{{-- ================= FILE SECTION ================= --}}
<div class="mt-6 border-t border-gray-300 dark:border-[#5F2DED] pt-4">

    <h2 class="text-xl font-semibold text-[#5E5F60] dark:text-white mb-2">
        File Submission
    </h2>

@if($submission && $submission->files->count())

    {{-- VIEW MODE --}}
    <ul x-show="!editing" class="space-y-2">
        @foreach($submission->files as $fileRecord)
            @foreach($fileRecord->getMedia('student_files') as $media)
                <li class="flex justify-between bg-gray-100 dark:bg-[#2A2A3C] p-2 rounded">
                    <span class="dark:text-white">{{ $media->file_name }}</span>
                </li>
            @endforeach
        @endforeach
    </ul>


    {{-- EDIT MODE --}}
    <div x-show="editing"
     x-data="fileEditor({
        existing: [
            @foreach($submission->files as $fileRecord)
                @foreach($fileRecord->getMedia('student_files') as $media)
                    { 
                        id: '{{ $media->id }}',
                        name: '{{ $media->file_name }}',
                        size: {{ $media->size }} 
                    },
                @endforeach
            @endforeach
        ],
        maxFiles: {{ $activity->max_number_files ?? 5 }},
        maxTotalMB: {{ $activity->max_total_file_size ?? 50 }},
    })"
     x-init="init()"
      @edit-file-picked.window="addNewFiles($event.detail)">


        {{-- EXISTING FILES --}}
        <p class="font-semibold dark:text-white mb-2">Current Files</p>

        <ul class="space-y-2 mb-3">
            <template x-for="(file, index) in existing" :key="file.id">
                <li class="flex justify-between items-center bg-gray-100 dark:bg-[#2A2A3C] p-2 rounded">

                    <span
                        class="dark:text-white transition-all"
                        :class="file.removed ? 'line-through opacity-50' : ''"
                        x-text="file.name">
                    </span>

                    <button type="button"
                            class="text-red-500"
                            @click="toggleRemove(index)">
                        <span x-show="!file.removed">Remove</span>
                        <span x-show="file.removed">Undo</span>
                    </button>

                </li>
            </template>
        </ul>

        {{-- removed file ids are sent to backend --}}
        <input type="hidden" name="remove_file_ids" x-model="removedJson">


        {{-- ADD NEW FILES --}}
<p class="font-semibold dark:text-white mb-2">Add New Files</p>

<div class="border-dashed border-2 dark:border-[#5F2DED] rounded-lg p-5 cursor-pointer text-center text-gray-500 dark:text-gray-300"
     @click="document.querySelector('#submitUnifiedForm [x-ref=fileInput]').click()"


     @dragover.prevent
     @drop.prevent="addNewFiles($event)">
    Click or Drag Files Here
</div>



        <div class="mt-2 space-y-2">
            <template x-for="(file, i) in newFiles" :key="i">
                <div class="flex justify-between items-center bg-gray-100 dark:bg-[#2A2A3C] p-2 rounded-lg">
                    <span class="dark:text-white" x-text="file.name"></span>
                    <button class="text-red-500" type="button" @click="removeNew(i)">Remove</button>
                </div>
            </template>
        </div>
        <p class="mt-3 text-sm font-semibold dark:text-white">
            Total Size:
            <span :class="sizeOk ? 'text-green-500' : 'text-red-500'">
                <span x-text="totalSizeMB"></span> MB
            </span>
            / {{ $activity->max_total_file_size ?? 50 }} MB
        </p>

        {{-- ERROR --}}
        <p class="text-red-500 text-sm mt-2" x-show="!$root.__x.$data.filesValid">
            Must at least pass 1 file
            </p>


    </div>


@else
    {{-- FIRST SUBMISSION --}}
    <p class="text-sm font-semibold dark:text-white mb-2">
        Upload Files
    </p>

    <div id="file-upload"
     class="border-dashed border-2 dark:border-[#5F2DED] rounded-lg p-8 cursor-pointer text-center text-gray-500 dark:text-gray-300"
     data-max-files="{{ $activity->max_number_files }}"
     data-required="{{ $activity->accept_files }}"
     data-max-total="{{ $activity->max_file_size  ?? 50 }}">

        Click or Drag Files Here
        <span class="block text-xs mt-2">
            (Maximum of {{ $activity->max_number_files ?? 'X' }} files)
            (Maximum file size: {{ $activity->max_file_size ?? 'X' }} MB)
        </span>
    </div>

    <input type="file" id="file-input" name="files[]" multiple hidden>
    <div id="file-list" class="mt-2 space-y-2"></div>
    <div class="mt-2">
        <p class="text-sm font-semibold dark:text-white">
            Total Size:
            <span id="first-total-size" class="text-green-500">0 MB</span>
            / {{ $activity->max_file_size  ?? 50 }} MB
        </p>

        <p id="first-size-error"
        class="text-red-500 text-sm mt-1 hidden">
            Total file size exceeded. Please remove some files.
        </p>
    </div>



@endif
</div>
