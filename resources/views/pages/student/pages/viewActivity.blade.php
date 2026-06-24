@extends('pages.student.layout')

@section('content')

<div class="bg-white dark:bg-[#1E1E2D] border dark:border-[#5F2DED] shadow-lg p-6 sm:p-8 rounded-lg">

    {{-- HEADER --}}
    <div class="pb-3 flex gap-3 items-center">
        <button onclick="window.location='{{ route('student.classwork', [$section->id, $subject->id]) }}'">
            <i class="icofont-arrow-left text-primaryColor text-3xl"></i>
        </button>

        <p class="font-semibold text-2xl sm:text-3xl text-[#373737] dark:text-white">
            {{ $activity->title }}
        </p>
    </div>

    {{-- OPEN + DUE --}}
    <div class="border-b border-gray-300 dark:border-[#5F2DED] pb-3">
        <div class="flex gap-2 text-sm">
            <i class="icofont-clock-time text-green-600"></i>
            <p class="dark:text-white">
                <b>Opened:</b>
                {{ \Carbon\Carbon::parse($activity->open_date . ' ' . $activity->open_time)->format('h:i A F d, Y') }}
            </p>
        </div>

        <div class="flex gap-2 text-sm">
            <i class="icofont-clock-time text-pink-600"></i>
            <p class="dark:text-white">
                <b>Due:</b>
                {{ $activity->due_date ? \Carbon\Carbon::parse($activity->due_date)->format('h:i A F d, Y') : 'No due date' }}
            </p>
        </div>
    </div>


    {{-- ========================= --}}
    {{--  DETERMINE IF CAN SUBMIT --}}
    {{-- ========================= --}}
    @php
        $now = now();
        $due = $activity->due_date;
        $canSubmit = true;

        if ($activity->accepting == 0) {
            $canSubmit = false;
        }
    @endphp


    {{-- ========================= --}}
    {{--  UNIFIED SUBMISSION FORM  --}}
    {{-- ========================= --}}
    @if(!$canSubmit)
        <div class="mt-6 p-4 bg-red-100 dark:bg-red-900 text-red-700 dark:text-white rounded-lg">
            This activity is no longer accepting submissions.
        </div>
    @else

    <form id="submitUnifiedForm"
          action="{{ $submission 
                ? route('student.activity.unified.update', $activity->id) 
                : route('student.activity.unified.submit', $activity->id) }}"
          method="POST"
          enctype="multipart/form-data"
          data-min-words="{{ $activity->min_words }}"
          data-max-words="{{ $activity->max_words }}">

        @csrf
        <input type="hidden" id="has-submission" value="{{ $submission ? '1' : '0' }}">
        


                <div 
                x-data="{
                        editing:false,

                        hasSubmission: {{ $submission ? 'true' : 'false' }},

                        requiresFiles: {{ $activity->accept_files ? 'true' : 'false' }},
                        requiresEssay: {{ $activity->accept_text ? 'true' : 'false' }},

                        filesValid: {{ $activity->accept_files ? 'false' : 'true' }},
                        filesSizeOk: true,
                        essayOk: {{ $activity->accept_text ? 'false' : 'true' }},

                        get canSubmit() {
                            let filePass = this.requiresFiles ? (this.filesValid && this.filesSizeOk) : true;
                            let essayPass = this.requiresEssay ? this.essayOk : true;
                            return filePass && essayPass;
                        }
                    }"

                    @files-valid.window="filesValid = $event.detail"
                    @files-size-valid.window="filesSizeOk = $event.detail"
                    @essay-valid.window="essayOk = $event.detail"
                    x-cloak>
                    <input
    type="file"
    name="files[]"
    multiple
    class="hidden"
    x-ref="fileInput"
    @change="$dispatch('edit-file-picked', $event)"
/>







                    {{-- DESCRIPTION + ATTACHMENTS --}}
                    <x-student-class.act-description-files
                        :description="$activity->description"
                        :attachments="$activity->getMedia('attachments')" 
                    />

                    {{-- FILE SECTION --}}
                    @if($activity->accept_files)
                        @include('student.activity.partials.files-section', [
                            'activity' => $activity,
                            'submission' => $submission ?? null,
                            'attemptsRemaining' => $attemptsRemaining
                        ])
                    @endif

                    {{-- ESSAY SECTION --}}
                    @if($activity->accept_text)
                        @include('student.activity.partials.essay-section', [
                            'activity' => $activity,
                            'submission' => $submission ?? null
                        ])
                    @endif

                    
                    

                    {{-- ================= UNIFIED CONTROLS ================= --}}
                    <div class="mt-6 flex justify-end gap-2">

                        {{-- FIRST SUBMISSION --}}
                        @if(!$submission)
                        <button type="submit"
                                x-show="!editing"
                                :disabled="!canSubmit || {{ $attemptsRemaining }} <= 0"
                                class="px-6 py-3 bg-primaryColor text-white rounded-lg
                                    disabled:bg-gray-400 disabled:cursor-not-allowed">
                            Submit Work
                        </button>
                        @endif

                        @php
                            $hasScore = $submission && !is_null($submission->score);
                        @endphp


                        @if($submission && !$hasScore)

            <button type="button"
                    x-show="!editing"
                    @if($attemptsRemaining <= 0) disabled @endif
                    @if($attemptsRemaining > 0)
                        onclick="document.getElementById('removeModal').classList.remove('hidden')"
                    @endif
                    class="px-6 py-3 rounded-lg text-white
                        @if($attemptsRemaining > 0)
                                bg-red-600 hover:bg-red-700
                        @else
                                bg-gray-400 cursor-not-allowed opacity-70
                        @endif">
                Remove Submission
            </button>

            <button type="button"
                x-show="!editing"
                @if($attemptsRemaining <= 0) disabled @endif
                @if($attemptsRemaining > 0)
                    @click="editing = true"
                @endif
                class="px-6 py-3 rounded-lg text-white transition-all
                    @if($attemptsRemaining > 0)
                        bg-primaryColor hover:opacity-90
                    @else
                        bg-gray-400 cursor-not-allowed opacity-70
                    @endif">
            Edit Submission
        </button>


        @endif




                {{-- CANCEL --}}
                <button type="button"
                        x-show="editing"
                        @click="editing = false"
                        class="px-6 py-3 bg-red-700 dark:bg-red-900 text-white rounded-lg">
                    Cancel
                </button>

                {{-- SAVE UPDATED WORK --}}
                <button type="submit"
                        x-show="editing"
                        :disabled="!canSubmit"

                        class="px-6 py-3 bg-green-600 text-white rounded-lg
                            disabled:bg-gray-400 disabled:cursor-not-allowed">
                    Save Changes
                </button>
                





            </div>
            {{-- ================= DISABLE REASON TOOLTIP ================= --}}
                <div class="mt-2 text-sm font-semibold flex w-full flex-col items-end"
                    x-show="(!canSubmit) && (editing || !hasSubmission)"
                    x-cloak>
                    
                    @if($activity->accept_files)
                        <p class="text-red-500" x-show="!filesValid">
                            You must upload at least one valid file.
                        </p>

                        <p class="text-red-500" x-show="!filesSizeOk">
                            Total file size exceeds the allowed maximum.
                        </p>
                    @endif

                    @if($activity->accept_text)
                        <p class="text-red-500" x-show="!essayOk">
                            Essay word requirement not satisfied yet.
                        </p>
                    @endif
                </div>

                @if(!$hasScore)
                    {{-- Attempts remaining --}}
                    <div class="mt-2 text-right">
                        @if($attemptsRemaining > 0)
                            <span class="text-sm font-semibold text-green-600 dark:text-green-400">
                                You have {{ $attemptsRemaining }} attempt{{ $attemptsRemaining > 1 ? 's' : '' }} remaining.
                            </span>
                        @else
                            <span class="text-sm font-semibold text-red-600 dark:text-red-400">
                                You have no attempts remaining.
                            </span>
                        @endif
                    </div>
                @endif

        </div>

        @include('student.activity.partials.submitmodal')

    </form>
    @include('student.activity.partials.removemodal')
    @endif


    {{-- ================= STATUS BELOW ================= --}}
    @if(isset($submission) && $submission)
        <x-student-class.submission-status 
            :submission="$submission" 
            :activity="$activity" 
        />
    @endif


</div>

@endsection

@section('scripts')
@vite('resources/js/ActSubmissionFiles.js')
@endsection

<x-lmsmodal />
<script>
function fileEditor(config) {
    return {
        existing: (config.existing || []).map(f => ({ ...f, removed: false })),
        removed: [],
        newFiles: [],
        maxFiles: config.maxFiles || 5,
        maxTotalMB: config.maxTotalMB || 50,

        get removedJson() {
            return JSON.stringify(this.removed);
        },

        get keptExistingFiles() {
            return this.existing.filter(f => !f.removed);
        },

        get totalSizeBytes() {
            let existingSize = this.keptExistingFiles.reduce(
                (s, f) => s + (f.size ?? 0),
                0
            );
            let newSize = this.newFiles.reduce((s, f) => s + f.size, 0);
            return existingSize + newSize;
        },

        get totalSizeMB() {
            return (this.totalSizeBytes / (1024 * 1024)).toFixed(2);
        },

        get sizeOk() {
            return this.totalSizeBytes <= this.maxTotalMB * 1024 * 1024;
        },

        get hasFiles() {
            return this.keptExistingFiles.length + this.newFiles.length > 0;
        },

        notify() {
            this.$nextTick(() => {
                window.dispatchEvent(new CustomEvent('files-valid', {
                    detail: this.hasFiles
                }));

                window.dispatchEvent(new CustomEvent('files-size-valid', {
                    detail: this.sizeOk
                }));
            });
        },

        toggleRemove(index) {
            let file = this.existing[index];
            file.removed = !file.removed;

            if (file.removed)
                this.removed.push(file.id);
            else
                this.removed = this.removed.filter(id => id !== file.id);

            this.notify();
        },

        addNewFiles(event) {
            const incoming = Array.from(
                event.dataTransfer?.files || event.target.files
            );

            this.newFiles = [...this.newFiles, ...incoming];

            const kept = this.keptExistingFiles.length;

            if (this.newFiles.length + kept > this.maxFiles) {
                LMSModal.open({
                    title: "File Limit Reached",
                    content: `
                        <p class="text-[#5E5F60] dark:text-white mb-4">
                            You can only upload up to <b>${this.maxFiles}</b> files.
                        </p>
                        <div class="flex justify-end">
                            <button
                                data-close="lmsmodal"
                                class="px-4 py-2 bg-primaryColor text-white rounded">
                                OK
                            </button>
                        </div>
                    `
                });

                this.newFiles = this.newFiles.slice(0, this.maxFiles - kept);
            }

            // sync real form input
            const dt = new DataTransfer();
            this.newFiles.forEach(file => dt.items.add(file));

            const input = document
                .getElementById('submitUnifiedForm')
                .querySelector('[x-ref=fileInput]');

            input.files = dt.files;

            this.$nextTick(() => {
                this.notify();
            });
        },

        removeNew(i) {
            this.newFiles.splice(i, 1);

            const dt = new DataTransfer();
            this.newFiles.forEach(file => dt.items.add(file));

            const input = document
                .getElementById('submitUnifiedForm')
                .querySelector('[x-ref=fileInput]');

            input.files = dt.files;

            this.$nextTick(() => {
                this.notify();
            });
        },

        init() {
            this.notify();
        }
    };
}
</script>



