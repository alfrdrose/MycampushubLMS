
@extends('pages.teacher.layout')

@section('content')
<div class="bg-[#FFFFFF] dark:bg-[#1E1E2D] dark:border-[#5F2DED] shadow-lg border p-8 rounded-lg"> 
    <div class="pb-2 flex flex-row gap-4 items-center"> 
        <button onclick="window.location='{{ route('teacher.classwork.view', [$section->id, $subject->id]) }}'"class="flex items-center gap-2">
            <i class="icofont-arrow-left text-primaryColor text-4xl mb-2"></i>
        </button>

        <x-classwork-nav 
            active="instructions" 
            :section-id="$section->id" 
            :subject-id="$subject->id" 
            :activity-id="$activity->id"
        />
        <div class="ml-auto mt-2 flex gap-2">
            <button 
                id="openDeleteModal"
                class="px-5 py-2 bg-red-600 text-white text-sm rounded-lg">
                Delete Activity
            </button>
        </div>

    </div>

    <div class="border-b border-[#D4D4D8] dark:border-[#5F2DED]">
        <input 
            type="text" 
            id="activity_title"
            value="{{ $activity->title }}"
            class="w-full border-none
                   text-[#373737] font-bold text-3xl pl-[-5%] dark:text-white bg-transparent
                   focus:outline-none focus:ring-transparent "
        />

        <div class="flex flex-row gap-2 items-center text-sm"> 
            <i class="icofont-clock-time text-[#008236] text-lg"></i>
            <p class="text-[#373737] dark:text-white"><span class="font-bold">Opened:</span>  
                {{ \Carbon\Carbon::parse($activity->created_at)->format('h:i A F d, Y') }}
            </p>
        </div>
        <div class="flex flex-row gap-2 items-center text-sm mb-4"> 
            <i class="icofont-clock-time text-[#F2277E] text-lg"></i>
            <p class="text-[#373737] dark:text-white"><span class="font-bold">Due:</span>
                {{ $activity->due_date ? \Carbon\Carbon::parse($activity->due_date)->format('h:i A F d, Y') : 'No due date' }}
            </p>
        </div>
    </div>

    {{-- Description --}}
        <div class="my-6 text-[#373737] ">
            <input
                id="activity_description"
                name="description"
                type="hidden"
                value="{{ $activity->description }}"
            >

            <trix-editor 
                input="activity_description"
                placeholder="Write your activity description here..."
                class="
                    p-3
                    w-full
                    overflow-y-auto
                    max-h-[600px]
                    bg-white dark:bg-[#1E1E2D] 
                    text-[#373737] dark:text-white
                    border-none
                    rounded-lg
                "
            ></trix-editor>
        </div>

    {{-- Attach Files --}}
    {{-- Attach Files --}}
    <div class="mt-6 pt-4 border-t border-[#D4D4D8] dark:border-[#5F2DED]">
        <label class="block mb-2 text-xl font-semibold text-[#5E5F60] dark:text-white">
            Upload Files
        </label>

        <!-- Drop Zone -->
        <div
            id="dropZone"
            class="w-full border-2 border-dashed border-[#ABB7C2] dark:border-[#5F2DED]
                rounded-lg p-6 text-center cursor-pointer
                hover:border-[#5F2DED] dark:hover:border-[#A88BFF]
                transition"
        >
            <p class="text-[#5E5F60] dark:text-white">
                Drag & Drop files here or click to upload
            </p>
            <p class="text-xs text-[#ABB7C2] mt-1">
                You can upload multiple files
            </p>

            <input
                type="file"
                id="fileInput"
                multiple
                class="hidden"
            />

        </div>

        <!-- Preview List -->
        <div id="filePreviewList" class="mt-3 space-y-2"></div>
    </div>

    {{-- Existing Uploaded Files --}}
    @if($activity->getMedia('attachments')->isNotEmpty())
        <div class="mt-4 pt-4 border-t border-[#D4D4D8] dark:border-[#5F2DED]">
            <h2 class="text-xl font-semibold text-[#5E5F60] dark:text-white mb-2">Attached Files</h2>

            <ul class="space-y-2">
                @foreach($activity->getMedia('attachments') as $media)
                    <li class="flex justify-between items-center bg-gray-100 dark:bg-[#2A2A3C] p-2 rounded">
                        <a
                            href="{{ $media->getUrl() }}"
                            target="_blank"
                            class="text-[#373737] dark:text-white truncate max-w-xs"
                        >
                            {{ $media->file_name }}
                        </a>

                        <button
                            type="button"
                            class="text-red-600 dark:text-red-400 hover:underline open-remove-modal"
                            data-action="{{ route('teacher.activity.file.remove', $media->id) }}"
                        >
                            Remove
                        </button>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif


    {{-- Activity Options --}}
    <div class="mt-4">

        {{-- Visibility Dropdown --}}
        <div id="visibilityHeader"
            class="w-full mb-2 flex flex-row justify-between hover:bg-gray-100 dark:hover:bg-gray-800 
                text-[#5E5F60] dark:text-white rounded cursor-pointer py-2 px-1 transition-all">

            <h1 class="text-lg font-bold">Visibility Options</h1>

            <i id="visibilityArrow" class="icofont-rounded-down text-xl transition-all duration-300"></i>
        </div>

        {{-- Visibility Options Content --}}
        <div id="visibilityContent" class="flex flex-col overflow-hidden transition-all duration-300 border-b">

            <div class="flex flex-row items-center gap-4">

                <div class="flex flex-col w-1/2 gap-2">
                    {{-- Publish Date --}}
                    <div>
                        <label class="block mb-1 text-sm font-medium text-[#5E5F60] dark:text-white">
                            Open Date
                        </label>
                        <input type="text" id="open_date"
                            value="{{ $activity->open_date ? \Carbon\Carbon::parse($activity->open_date)->format('Y-m-d') : '' }}"
                            placeholder="Select date"
                            class="w-full border border-[#ABB7C2] rounded-lg px-3 py-2 text-[#5E5F60] dark:text-white 
                                bg-transparent focus:outline-none focus:ring-2 focus:ring-[#5F2DED]">
                    </div>

                    {{-- Publish Time --}}
                    <div>
                        <label class="block mb-1 text-sm font-medium text-[#5E5F60] dark:text-white">
                            Open Time
                        </label>
                        <input type="text" id="open_time" required
                            value="{{ $activity->open_time ? \Carbon\Carbon::parse($activity->open_time)->format('H:i') : '' }}"
                            placeholder="Select time"
                            class="w-full border dark:bg-[#1E1E2D] border-[#ABB7C2] rounded-lg px-3 py-2 text-[#5E5F60] 
                                dark:text-white bg-transparent focus:outline-none focus:ring-2 focus:ring-[#5F2DED]">
                    </div>
                </div>

                <div class="flex flex-col w-1/2 gap-2">
                    {{-- Due Date --}}
                    <div>
                        <label class="block mb-1 text-sm font-medium text-[#5E5F60] dark:text-white">
                            Due Date
                        </label>
                        <input type="text" id="due_date"
                            value="{{ $activity->due_date ? \Carbon\Carbon::parse($activity->due_date)->format('Y-m-d') : '' }}"
                            placeholder="Select date"
                            class="w-full border border-[#ABB7C2] rounded-lg px-3 py-2 text-[#5E5F60] dark:text-white 
                                bg-transparent focus:outline-none focus:ring-2 focus:ring-[#5F2DED]">
                    </div>

                    {{-- Due Time --}}
                    <div>
                        <label class="block mb-1 text-sm font-medium text-[#5E5F60] dark:text-white">
                            Due Time
                        </label>
                        <input type="text" id="due_time"
                            value="{{ $activity->due_date ? \Carbon\Carbon::parse($activity->due_date)->format('H:i') : '' }}"
                            placeholder="Select time"
                            class="w-full border border-[#ABB7C2] rounded-lg px-3 py-2 text-[#5E5F60] dark:text-white 
                                bg-transparent focus:outline-none focus:ring-2 focus:ring-[#5F2DED]">
                    </div>
                </div>
            </div>

            {{-- Visible Toggle --}}
            <div class="mt-2 flex items-center gap-2 ">
                <label class="text-sm font-medium text-[#5E5F60] dark:text-white">
                    Classwork is visible to students
                </label>

                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" id="is_visible"
                        @checked($activity->visible ?? false)
                        class="sr-only peer">

                    <div class="w-11 h-6 bg-gray-300 peer-focus:outline-none rounded-full peer dark:bg-gray-700
                                peer-checked:bg-[#5F2DED] dark:peer-checked:bg-blue-500
                                transition-all duration-300"></div>

                    <span class="absolute left-1 top-[3px] w-4 h-4 bg-white rounded-full transition-all
                                peer-checked:translate-x-5 dark:bg-gray-200"></span>
                </label>
            </div>
            {{-- Assignment Status --}}
            @php
                $now = \Carbon\Carbon::now('Asia/Manila');
                $deadlinePassed = $activity->due_date &&
                    \Carbon\Carbon::parse($activity->due_date)->lt($now);
            @endphp


            <div class="mt-2 flex items-center gap-2 mb-4">
                <label class="text-sm font-medium text-[#5E5F60] dark:text-white">
                    Assignment Status:
                </label>

                {{-- STATUS LOGIC --}}
                @if ($activity->is_open == 0 && $activity->visible == 0)
                    <span class="text-red-500 font-semibold text-sm">
                        Assignment is not visible and closed
                    </span>

                @elseif ($activity->is_open == 1 && $activity->visible == 0)
                    <span class="text-yellow font-semibold text-sm">
                        Assignment is open but not visible
                    </span>

                @elseif ($activity->is_open == 0 && $activity->visible == 1)
                    <span class="text-yellow font-semibold text-sm">
                        Assignment is visible but not open
                    </span>

                @elseif ($activity->is_open == 1 && $activity->visible == 1)

                    {{-- Check deadline conditions --}}
                    @if ($deadlinePassed && $activity->accepting == 1)
                        <span class="text-yellow font-semibold text-sm">
                            Accepting late submissions
                        </span>

                    @elseif ($deadlinePassed && $activity->accepting == 0)
                        <span class="text-red-500 font-semibold text-sm">
                            Assignment is visible but not accepting anymore
                        </span>

                    @else
                        <span class="text-green-500 font-semibold text-sm">
                            Assignment is visible and open
                        </span>
                    @endif

                @endif
            </div>

        </div>
        <input type="hidden" id="is_open_status" value="{{ $activity->is_open }}">

        {{-- Submission Options --}}
        <div id="submissionHeader"
            class="w-full mb-2 mt-4 flex flex-row justify-between hover:bg-gray-100 dark:hover:bg-gray-800 
                text-[#5E5F60] dark:text-white rounded cursor-pointer py-2 px-1 transition-all">

            <h1 class="text-lg font-bold">Submission Options & Score</h1>

            <i id="submissionArrow" class="icofont-rounded-down text-xl transition-all duration-300"></i>
        </div>

        <div id="submissionContent" class="flex flex-col overflow-hidden transition-all duration-300 border-b">
            {{-- File Submission Toggle --}}
            <div class="mt-2 flex items-center gap-2 mb-4">
                <label class="text-sm font-medium text-[#5E5F60] dark:text-white">
                    Allow File Submissions
                </label>

                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" id="allow_files"
                        @checked($activity->accept_files ?? false)
                        class="sr-only peer">


                    <div class="w-11 h-6 bg-gray-300 peer-focus:outline-none rounded-full peer dark:bg-gray-700
                                peer-checked:bg-[#5F2DED] dark:peer-checked:bg-blue-500
                                transition-all duration-300"></div>

                    <span class="absolute left-1 top-[3px] w-4 h-4 bg-white rounded-full transition-all
                                peer-checked:translate-x-5 dark:bg-gray-200"></span>
                </label>
                
            </div>
            <div id="fileSettings" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                {{-- Maximum Number of Files --}}
                <div>
                    <label class="block mb-1 text-sm font-medium text-[#5E5F60] dark:text-white">Maximum Number of Files</label>
                    <input type="number" min="1" id="max_files" required value="{{ $activity->max_number_files }}" placeholder="e.g. 5" class="w-full border border-[#ABB7C2] rounded-lg px-3 py-2 text-[#5E5F60] dark:text-white bg-transparent focus:outline-none focus:ring-2 focus:ring-[#5F2DED]">
                </div>
                {{-- Maximum Submission Size --}}
                <div>
                    <label class="block mb-1 text-sm font-medium text-[#5E5F60] dark:text-white">Maximum Submission Size (50 MB MAX)</label>
                    <input type="number" min="1" max="50" id="max_size" required value="{{ $activity->max_file_size }}" placeholder="e.g. 5" class="w-full border border-[#ABB7C2] rounded-lg px-3 py-2 text-[#5E5F60] dark:text-white bg-transparent focus:outline-none focus:ring-2 focus:ring-[#5F2DED]">
                </div>
            </div>
            <div class="mt-2 flex items-center gap-2 mb-4">
                <label class="text-sm font-medium text-[#5E5F60] dark:text-white">
                    Allow Text Submissions
                </label>

                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" id="allow_text"
                        @checked($activity->accept_text ?? false)
                        class="sr-only peer">


                    <div class="w-11 h-6 bg-gray-300 peer-focus:outline-none rounded-full peer dark:bg-gray-700
                                peer-checked:bg-[#5F2DED] dark:peer-checked:bg-blue-500
                                transition-all duration-300"></div>

                    <span class="absolute left-1 top-[3px] w-4 h-4 bg-white rounded-full transition-all
                                peer-checked:translate-x-5 dark:bg-gray-200"></span>
                </label>
                
            </div>
            <div id="textSettings" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                {{-- Maximum Number of Files --}}
                <div>
                    <label class="block mb-1 text-sm font-medium text-[#5E5F60] dark:text-white">Minimum Words</label>
                    <input type="number" min="0" id="min_words" required value="{{ $activity->min_words }}" placeholder="e.g. 5" class="w-full border border-[#ABB7C2] rounded-lg px-3 py-2 text-[#5E5F60] dark:text-white bg-transparent focus:outline-none focus:ring-2 focus:ring-[#5F2DED]">
                </div>
                {{-- Maximum Submission Size --}}
                <div>
                    <label class="block mb-1 text-sm font-medium text-[#5E5F60] dark:text-white">Maximum Words</label>
                    <input type="number" min="0" id="max_words" required value="{{ $activity->max_words }}" placeholder="e.g. 5" class="w-full border border-[#ABB7C2] rounded-lg px-3 py-2 text-[#5E5F60] dark:text-white bg-transparent focus:outline-none focus:ring-2 focus:ring-[#5F2DED]">
                </div>
            </div>
            {{-- Total Score --}}
            <div class="w-1/2 mb-4 mt-2">
                    <label class="block mb-1 text-sm font-medium text-[#5E5F60] dark:text-white">Total Score</label>
                    <input type="number" min="1" id="total_score" required value="{{ $activity->total_score }}" placeholder="e.g. 5" class="w-full border border-[#ABB7C2] rounded-lg px-3 py-2 text-[#5E5F60] dark:text-white bg-transparent focus:outline-none focus:ring-2 focus:ring-[#5F2DED]">
            </div>
            
        </div>

        {{-- Restrictions Options --}}
        <div id="restrictionHeader"
            class="w-full mb-2 mt-4 flex flex-row justify-between hover:bg-gray-100 dark:hover:bg-gray-800 
                text-[#5E5F60] dark:text-white rounded cursor-pointer py-2 px-1 transition-all">

            <h1 class="text-lg font-bold">Restrictions</h1>

            <i id="restrictionArrow" class="icofont-rounded-down text-xl transition-all duration-300"></i>
        </div>
        <div id="restrictionContent" class="flex flex-row overflow-hidden transition-all duration-300 border-b gap-4">
            {{-- Allow Late Submissions --}}
            <div class="w-1/2">
                <label class="block mb-1 text-sm font-medium text-[#5E5F60] dark:text-white">Allow Late Submissions</label>
                <select id="accepting" class="w-full border dark:bg-[#1E1E2D] border-[#ABB7C2] rounded-lg px-3 py-2 text-[#5E5F60] dark:text-white bg-transparent dark:bg-  focus:outline-none focus:ring-2 focus:ring-[#5F2DED]">
                    <option value="1" {{ $activity->accepting ? 'selected' : '' }}>Yes</option>
                    <option value="0" {{ !$activity->accepting ? 'selected' : '' }}>No</option>
                </select>
            </div>

            

            <div class="w-1/2 pb-4">
                <label class="block mb-1 text-sm font-medium text-[#5E5F60] dark:text-white">Maximum number of Attempts</label>
                <input type="number" min="1" id="max_attempt" required value="{{ $activity->max_attempt }}" placeholder="e.g. 5" class="w-full mb-4 border border-[#ABB7C2] rounded-lg px-3 py-2 text-[#5E5F60] dark:text-white bg-transparent focus:outline-none focus:ring-2 focus:ring-[#5F2DED]">
            </div>
        </div>

        {{-- Tags --}}
        <div id="activityTagsHeader"
            class="w-full mb-2 mt-4 flex flex-row justify-between hover:bg-gray-100 dark:hover:bg-gray-800 
                text-[#5E5F60] dark:text-white rounded cursor-pointer py-2 px-1 transition-all">
            <h1 class="text-lg font-bold">Tags</h1>
            <i id="activityTagsArrow" class="icofont-rounded-up text-xl transition-all duration-300"></i>
        </div>

            <div id="activityTagsContent" class="flex flex-col overflow-hidden transition-all duration-300 pb-6" style="max-height: 0px;">
            <div class="flex flex-wrap items-center gap-2 pt-2">
                <div id="activityTagPills" class="flex flex-wrap items-center gap-2">
                    @foreach ($assignedTags as $tag)
                        <button type="button"
                            class="tag-pill inline-flex items-center gap-2 px-3 py-1 rounded-full border border-[#ABB7C2] text-[#5E5F60] dark:text-white hover:bg-gray-100 dark:hover:bg-gray-800 transition"
                            data-tag-pill="true"
                            data-tag-id="{{ $tag->id }}">
                            <span class="text-sm">{{ $tag->name }}</span>
                            <span class="tag-pill-remove text-xs text-gray-400 hover:text-red-500">x</span>
                        </button>
                    @endforeach
                </div>
            </div>
            <div class="mt-2 flex items-center justify-between mb-4">
                <div class="flex items-center gap-2">
                    <p id="activityNoTagsMessage" class="text-sm text-gray-500 dark:text-gray-300 {{ $assignedTags->isEmpty() ? '' : 'hidden' }}">
                        No tags assigned yet.
                    </p>
                    <button type="button" id="activityOpenTagsModal"
                        class="inline-flex items-center gap-2 px-3 py-1 rounded-full border border-dashed border-[#5F2DED] text-[#5F2DED] hover:bg-[#5F2DED]/10 transition">
                        <i class="icofont-ui-add text-sm"></i>
                        <span class="text-sm font-medium">Add Tags</span>
                    </button>
                </div>
                <button type="button" id="activityClearTagsBtn"
                    data-loading-text="Clearing..."
                    data-loading-text-class="text-xs"
                    class="inline-flex items-center gap-2 px-3 py-1 rounded-full border border-red-300 text-red-500 hover:bg-red-50 transition">
                    <i class="icofont-close text-sm"></i>
                    <span class="text-sm font-medium">Clear</span>
                </button>
            </div>
            <div id="activityTagInputs" class="hidden">
                @foreach ($assignedTagIds as $tagId)
                    <input type="hidden" name="tag_ids[]" value="{{ $tagId }}">
                @endforeach
            </div>
        </div>

        <div class="mt-6 pt-6 border-t border-[#E2E8F0] dark:border-[#334155]">
            {{-- Save Button --}}
            <div class="flex justify-end flex-col">
                <button
                    id="openSaveModal"
                    type="button"
                    disabled
                    class="px-5 py-2 rounded-lg ml-auto block bg-gray-400 text-white cursor-not-allowed opacity-60 transition"
                >
                    Save
                </button>

                <div id="formErrors" class="mt-3 text-red-600 dark:text-red-400 hidden"></div>
            </div>
        </div>
        

    </div>
</div>



@endsection

<x-lmsmodal/>
{{-- Confirm Remove Modal --}}
<div
    id="removeFileModal"
    class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50"
>
    <div class="bg-white dark:bg-[#1F1F2E] rounded-lg w-full max-w-md p-6">
        <h3 class="text-lg font-semibold text-[#373737] dark:text-white mb-2">
            Remove file?
        </h3>

        <p class="text-sm text-gray-600 dark:text-gray-300 mb-4">
            This action cannot be undone. Are you sure you want to remove this file?
        </p>

        <form id="removeFileForm" method="POST">
            @csrf
            @method('DELETE')

            <div class="flex justify-end gap-3">
                <button
                    type="button"
                    id="cancelRemoveFile"
                    class="px-4 py-2 rounded border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    id="confirmRemoveFile"
                    class="px-4 py-2 rounded bg-red-600 text-white flex items-center gap-2 disabled:opacity-60"
                >
                    <svg
                        id="removeLoadingSpinner"
                        class="w-4 h-4 animate-spin hidden"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                    >
                        <circle
                            class="opacity-25"
                            cx="12"
                            cy="12"
                            r="10"
                            stroke="currentColor"
                            stroke-width="4"
                        ></circle>
                        <path
                            class="opacity-75"
                            fill="currentColor"
                            d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"
                        ></path>
                    </svg>

                    <span id="removeBtnText">Remove</span>
                </button>
            </div>
        </form>
    </div>
</div>

<div id="activityTagsModal"
     class="fixed inset-0 items-center justify-center bg-black bg-opacity-50 hidden z-50">
    <div class="bg-white dark:bg-[#1E1E2D] p-6 rounded-lg w-full max-w-2xl mx-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <h2 class="text-xl font-bold text-gray-800 dark:text-white">Select Tags</h2>
            <div class="flex items-center gap-2 sm:justify-end w-full sm:w-auto">
                <div class="relative w-full sm:w-52">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                        <i class="icofont-search-1 text-sm"></i>
                    </span>
                    <input
                        type="text"
                        id="activityTagSearchInput"
                        placeholder="Search tags..."
                        class="w-full pl-9 pr-3 py-1.5 text-sm rounded-full border border-[#ABB7C2] text-[#5E5F60] dark:text-white bg-transparent focus:outline-none focus:ring-2 focus:ring-[#5F2DED]"
                    />
                </div>
                <button type="button" id="activityCloseTagsModal"
                    class="text-gray-500 hover:text-gray-700 dark:text-gray-300">
                    <i class="icofont-close text-xl"></i>
                </button>
            </div>
        </div>

        <div class="mt-4 flex items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <span class="text-sm text-gray-500 dark:text-gray-300">Category:</span>
                <button type="button" id="activityOpenCategoryPicker"
                    class="inline-flex items-center gap-2 px-3 py-1.5 rounded-md border border-[#ABB7C2] bg-gray-50 dark:bg-[#2A2A3C] text-sm font-medium text-[#5E5F60] dark:text-white hover:border-[#5F2DED] hover:bg-[#F6F2FF] dark:hover:bg-[#34344A] transition-colors">
                    <span id="activityCurrentCategoryLabel">All categories</span>
                    <i class="icofont-rounded-down text-sm"></i>
                </button>
                <div id="activityMetricModeToggle"
                    class="relative inline-grid grid-cols-2 h-9 w-56 rounded-full border border-[#ABB7C2] bg-gray-50 dark:bg-[#2A2A3C] overflow-hidden">
                    <span id="activityMetricModeSlider"
                        class="pointer-events-none absolute inset-y-0 left-0 w-1/2 bg-[#5F2DED] rounded-full transition-transform duration-200 opacity-0"></span>
                    <button type="button" id="activityModeRelevancyBtn"
                        class="relative z-10 text-xs font-semibold transition-colors">
                        Relevance
                    </button>
                    <button type="button" id="activityModeCoverageBtn"
                        class="relative z-10 text-xs font-semibold transition-colors">
                        Coverage
                    </button>
                </div>
            </div>
            <button type="button" id="activityBackToTags"
                class="hidden inline-flex h-8 w-8 items-center justify-center rounded-full border border-[#ABB7C2] text-[#5F2DED] hover:bg-[#F6F2FF] dark:border-[#5F2DED] dark:hover:bg-[#2A2A3C]"
                title="Back to tags"
                aria-label="Back to tags">
                <i class="icofont-simple-left text-sm"></i>
            </button>
        </div>

        @php
            $assignedTagSettings = collect($assignedTags ?? [])->mapWithKeys(function ($tag) {
                return [
                    (string) $tag->id => [
                        'relevance' => strtolower((string) ($tag->pivot->relevance_level ?? config('reviewers.default_tag_relevance', 'medium'))),
                        'coverage' => $tag->pivot->coverage_ratio !== null
                            ? round(((float) $tag->pivot->coverage_ratio) * 100, 2)
                            : null,
                    ],
                ];
            });
        @endphp

        <div id="activityTagListContainer">
            <div class="mt-4 max-h-72 overflow-y-auto border border-gray-200 dark:border-gray-700 rounded-lg divide-y divide-gray-100 dark:divide-gray-800">
                @forelse ($tags as $tag)
                    @php
                        $tagSetting = $assignedTagSettings[(string) $tag->id] ?? null;
                    @endphp
                    <label
                        class="activity-tag-option flex items-center gap-3 px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer"
                        data-tag-id="{{ $tag->id }}"
                        data-tag-name="{{ $tag->name }}"
                        data-tag-category="{{ $tag->category }}"
                        data-tag-relevance="{{ $tagSetting['relevance'] ?? config('reviewers.default_tag_relevance', 'medium') }}"
                        data-tag-coverage="{{ $tagSetting['coverage'] ?? '' }}"
                    >
                        <input type="checkbox"
                            class="activity-tag-checkbox h-4 w-4 rounded border-gray-300"
                            value="{{ $tag->id }}"
                            {{ in_array($tag->id, $assignedTagIds, true) ? 'checked' : '' }}>
                        <div class="flex flex-col">
                            <span class="text-sm font-medium text-[#5E5F60] dark:text-white">{{ $tag->name }}</span>
                            <span class="text-xs text-gray-500 dark:text-gray-300">{{ $tag->category }}</span>
                        </div>
                    </label>
                @empty
                    <div class="px-4 py-6 text-sm text-gray-500 dark:text-gray-300">
                        No tags available.
                    </div>
                @endforelse
            </div>
            <p id="activityTagListEmpty" class="mt-3 text-sm text-gray-500 dark:text-gray-300 hidden">
                No tags match your filters.
            </p>

            <div id="activityTagPagination" class="mt-4 flex items-center justify-between">
                <button
                    type="button"
                    id="activityTagPrevPage"
                    class="px-3 py-1.5 border border-[#ABB7C2] rounded-lg text-sm text-[#5E5F60] dark:text-white hover:bg-gray-100 dark:hover:bg-gray-800 disabled:opacity-50 disabled:cursor-not-allowed">
                    Prev
                </button>
                <p id="activityTagPageInfo" class="text-sm text-gray-500 dark:text-gray-300">Page 1 of 1</p>
                <button
                    type="button"
                    id="activityTagNextPage"
                    class="px-3 py-1.5 border border-[#ABB7C2] rounded-lg text-sm text-[#5E5F60] dark:text-white hover:bg-gray-100 dark:hover:bg-gray-800 disabled:opacity-50 disabled:cursor-not-allowed">
                    Next
                </button>
            </div>
        </div>

        <div id="activityTagCategoryPicker" class="hidden mt-4 border border-gray-200 dark:border-gray-700 rounded-md divide-y divide-gray-100 dark:divide-gray-800">
            <button type="button"
                class="activity-tag-category-option w-full text-left px-4 py-2.5 border-l-4 border-transparent hover:bg-gray-50 dark:hover:bg-gray-800 text-sm font-medium text-[#5E5F60] dark:text-white transition-colors"
                data-tag-category="">
                All categories
            </button>
            @forelse ($tagCategories as $category)
                <button type="button"
                    class="activity-tag-category-option w-full text-left px-4 py-2.5 border-l-4 border-transparent hover:bg-gray-50 dark:hover:bg-gray-800 text-sm font-medium text-[#5E5F60] dark:text-white transition-colors"
                    data-tag-category="{{ $category }}">
                    {{ $category }}
                </button>
            @empty
                <div class="px-4 py-6 text-sm text-gray-500 dark:text-gray-300">
                    No categories available.
                </div>
            @endforelse
        </div>
        <p id="activityCategoryListEmpty" class="mt-3 text-sm text-gray-500 dark:text-gray-300 hidden">
            Category not found.
        </p>
        <div id="activityCategoryPagination" class="hidden mt-4 flex items-center justify-between">
            <button
                type="button"
                id="activityCategoryPrevPage"
                class="px-3 py-1.5 border border-[#ABB7C2] rounded-lg text-sm text-[#5E5F60] dark:text-white hover:bg-gray-100 dark:hover:bg-gray-800 disabled:opacity-50 disabled:cursor-not-allowed">
                Prev
            </button>
            <p id="activityCategoryPageInfo" class="text-sm text-gray-500 dark:text-gray-300">Page 1 of 1</p>
            <button
                type="button"
                id="activityCategoryNextPage"
                class="px-3 py-1.5 border border-[#ABB7C2] rounded-lg text-sm text-[#5E5F60] dark:text-white hover:bg-gray-100 dark:hover:bg-gray-800 disabled:opacity-50 disabled:cursor-not-allowed">
                Next
            </button>
        </div>

        <div id="activityTagRelevancyContainer" class="hidden mt-4">
            <div class="rounded-lg border border-[#E2E8F0] dark:border-[#334155] p-3 bg-[#F8FAFC] dark:bg-[#0B1220]">
                <div id="activityTagMetricsEditor" class="space-y-2"></div>
                <div id="activityRelevancyPagination" class="hidden mt-3 flex items-center justify-between">
                    <button
                        type="button"
                        id="activityRelevancyPrevPage"
                        class="px-3 py-1.5 border border-[#ABB7C2] rounded-lg text-sm text-[#5E5F60] dark:text-white hover:bg-gray-100 dark:hover:bg-gray-800 disabled:opacity-50 disabled:cursor-not-allowed">
                        Prev
                    </button>
                    <p id="activityRelevancyPageInfo" class="text-sm text-gray-500 dark:text-gray-300">Page 1 of 1</p>
                    <button
                        type="button"
                        id="activityRelevancyNextPage"
                        class="px-3 py-1.5 border border-[#ABB7C2] rounded-lg text-sm text-[#5E5F60] dark:text-white hover:bg-gray-100 dark:hover:bg-gray-800 disabled:opacity-50 disabled:cursor-not-allowed">
                        Next
                    </button>
                </div>
            </div>
        </div>

        <div class="mt-6 flex justify-end">
            <button
                type="button"
                id="activityConfirmTagsModal"
                data-loading-text="Saving..."
                class="px-4 py-2 bg-primaryColor text-white rounded hover:opacity-90">
                Confirm
            </button>
        </div>
    </div>
</div>

<div id="deleteModal" 
     class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden z-50">

    <div class="bg-white dark:bg-[#1E1E2D] p-6 rounded-lg w-96">
        <h2 class="text-xl font-bold mb-4 text-red-600">Delete Activity?</h2>
        <p class="text-[#373737] dark:text-white mb-6">
            This action cannot be undone. Students will lose access and all submissions will be deleted.
        </p>

        <div class="flex justify-end gap-3">

            <div> 
                <button 
                id="cancelDelete"
                type="button"
                class="px-4 py-2 bg-gray-300 rounded dark:bg-gray-600 dark:text-white">
                Cancel
            </button>
            </div>
            

            <form id="deleteActivityForm"
                  action="{{ route('teacher.activity.delete', $activity->id) }}"
                  method="POST">
                @csrf
                @method('DELETE')

                <button 
                    id="confirmDeleteBtn"
                    type="button"
                    class="px-4 py-2 bg-red-600 text-white rounded">
                    Delete
                </button>
            </form>

        </div>
    </div>
</div>


<input type="hidden" id="updateUrl"
 value="{{ route('teacher.activity.update', $activity->id) }}">

 <div id="confirmSaveModal" 
    class="fixed inset-0 bg-black bg-opacity-50 hidden justify-center items-center z-50">

    <div class="bg-white dark:bg-[#1E1E2D] p-6 rounded-lg shadow-lg w-96">
        <h2 class="text-xl font-bold mb-3 text-primaryColor">Save Changes?</h2>

        <p class="text-[#373737] dark:text-white mb-6">
            Are you sure you want to save all changes to this activity?
        </p>

        <div class="flex justify-end gap-3">
            <button id="cancelSaveBtn"
                class="px-4 py-2 bg-gray-300 dark:bg-gray-600 dark:text-white rounded">
                Cancel
            </button>

            <button id="confirmSaveBtn"
    class="px-4 py-2 bg-primaryColor text-white rounded flex items-center gap-2">
    <svg
        id="saveLoadingSpinner"
        class="w-4 h-4 animate-spin hidden"
        xmlns="http://www.w3.org/2000/svg"
        fill="none"
        viewBox="0 0 24 24">
        <circle
            class="opacity-25"
            cx="12"
            cy="12"
            r="10"
            stroke="currentColor"
            stroke-width="4"></circle>
        <path
            class="opacity-75"
            fill="currentColor"
            d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
    </svg>
    <span id="saveBtnText">Save</span>
</button>


        </div>
    </div>
</div>

<style>
    .tag-pill-tone-very-high {
        border-color: #16a34a;
        background-color: #dcfce7;
        color: #14532d;
    }

    .tag-pill-tone-very-high:hover {
        background-color: #bbf7d0;
    }

    .tag-pill-tone-high {
        border-color: #14b8a6;
        background-color: #ccfbf1;
        color: #134e4a;
    }

    .tag-pill-tone-high:hover {
        background-color: #99f6e4;
    }

    .tag-pill-tone-medium {
        border-color: #84cc16;
        background-color: #f7fee7;
        color: #4d7c0f;
    }

    .tag-pill-tone-medium:hover {
        background-color: #ecfccb;
    }

    .tag-pill-tone-low {
        border-color: #0ea5e9;
        background-color: #f0f9ff;
        color: #0c4a6e;
    }

    .tag-pill-tone-low:hover {
        background-color: #bae6fd;
    }

    .tag-pill-tone-very-low {
        border-color: #1d4ed8;
        background-color: #dbeafe;
        color: #172554;
    }

    .tag-pill-tone-very-low:hover {
        background-color: #93c5fd;
    }

    .dark .tag-pill-tone-very-high {
        border-color: #4ade80;
        background-color: rgba(22, 163, 74, 0.35);
        color: #dcfce7;
    }

    .dark .tag-pill-tone-very-high:hover {
        background-color: rgba(22, 163, 74, 0.5);
    }

    .dark .tag-pill-tone-high {
        border-color: #2dd4bf;
        background-color: rgba(15, 118, 110, 0.4);
        color: #ccfbf1;
    }

    .dark .tag-pill-tone-high:hover {
        background-color: rgba(20, 184, 166, 0.5);
    }

    .dark .tag-pill-tone-medium {
        border-color: #a3e635;
        background-color: rgba(132, 204, 22, 0.12);
        color: #d9f99d;
    }

    .dark .tag-pill-tone-medium:hover {
        background-color: rgba(132, 204, 22, 0.2);
    }

    .dark .tag-pill-tone-low {
        border-color: #38bdf8;
        background-color: rgba(3, 105, 161, 0.38);
        color: #bae6fd;
    }

    .dark .tag-pill-tone-low:hover {
        background-color: rgba(14, 165, 233, 0.48);
    }

    .dark .tag-pill-tone-very-low {
        border-color: #3b82f6;
        background-color: rgba(29, 78, 216, 0.58);
        color: #dbeafe;
    }

    .dark .tag-pill-tone-very-low:hover {
        background-color: rgba(29, 78, 216, 0.7);
    }

    .tag-gradient-slider {
        -webkit-appearance: none;
        appearance: none;
        width: 100%;
        height: 10px;
        border-radius: 9999px;
        background: linear-gradient(90deg, #3b82f6 0%, #22c55e 100%);
        outline: none;
    }

    .tag-gradient-slider::-webkit-slider-runnable-track {
        height: 10px;
        border-radius: 9999px;
        background: linear-gradient(90deg, #3b82f6 0%, #22c55e 100%);
    }

    .tag-gradient-slider::-webkit-slider-thumb {
        -webkit-appearance: none;
        appearance: none;
        height: 16px;
        width: 16px;
        border-radius: 9999px;
        background: #ffffff;
        border: 2px solid #5f2ded;
        margin-top: -3px;
        cursor: pointer;
    }

    .tag-gradient-slider::-moz-range-track {
        height: 10px;
        border-radius: 9999px;
        background: linear-gradient(90deg, #3b82f6 0%, #22c55e 100%);
    }

    .tag-gradient-slider::-moz-range-thumb {
        height: 16px;
        width: 16px;
        border-radius: 9999px;
        background: #ffffff;
        border: 2px solid #5f2ded;
        cursor: pointer;
    }
</style>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const allowFiles = document.getElementById("allow_files");
    const allowText  = document.getElementById("allow_text");
    const saveBtn    = document.getElementById("openSaveModal");

    function updateSaveButtonState() {
        const canSave = (allowFiles && allowFiles.checked) || (allowText && allowText.checked);

        if (canSave) {
            saveBtn.disabled = false;
            saveBtn.classList.remove("bg-gray-400", "cursor-not-allowed", "opacity-60");
            saveBtn.classList.add("bg-primaryColor");
        } else {
            saveBtn.disabled = true;
            saveBtn.classList.remove("bg-primaryColor");
            saveBtn.classList.add("bg-gray-400", "cursor-not-allowed", "opacity-60");
        }
    }

    // run once on page load (important because activity might already have values)
    updateSaveButtonState();

    // update whenever toggles change
    allowFiles?.addEventListener("change", updateSaveButtonState);
    allowText?.addEventListener("change", updateSaveButtonState);
});
</script>
<script>
document.addEventListener("DOMContentLoaded", () => {
    const dropZone = document.getElementById("dropZone");
    const fileInput = document.getElementById("fileInput");
    const previewList = document.getElementById("filePreviewList");

    if (!dropZone || !fileInput || !previewList) return;

    // let uploadedFiles = [];
    const uploadedFiles = [];
    window.uploadedFiles = uploadedFiles;


    // Open file picker
    dropZone.addEventListener("click", () => {
        // Clear only before opening picker so same-file reselection works
        // without wiping the synced FileList after selection.
        fileInput.value = "";
        fileInput.click();
    });

    // Drag highlight
    ["dragenter", "dragover"].forEach(evt => {
        dropZone.addEventListener(evt, e => {
            e.preventDefault();
            e.stopPropagation();
            dropZone.classList.add("border-[#5F2DED]");
        });
    });

    ["dragleave", "drop"].forEach(evt => {
        dropZone.addEventListener(evt, e => {
            e.preventDefault();
            e.stopPropagation();
            dropZone.classList.remove("border-[#5F2DED]");
        });
    });

    // Drop files
    dropZone.addEventListener("drop", e => {
        addFiles(e.dataTransfer.files);
    });

    // Select files
    fileInput.addEventListener("change", () => {
        addFiles(fileInput.files);
    });

    function addFiles(files) {
        Array.from(files).forEach(file => {
            // Prevent duplicates (same name + size)
            const exists = uploadedFiles.some(
                f => f.name === file.name && f.size === file.size
            );
            if (!exists) {
                uploadedFiles.push(file);
            }
        });

        syncInput();
        renderPreview();
    }

    function syncInput() {
        const dt = new DataTransfer();
        uploadedFiles.forEach(file => dt.items.add(file));
        fileInput.files = dt.files;
    }

    function renderPreview() {
        previewList.innerHTML = "";

        uploadedFiles.forEach((file, index) => {
            const row = document.createElement("div");
            row.className = `
                flex items-center justify-between
                border border-[#D4D4D8] dark:border-[#5F2DED]
                bg-white dark:bg-[#2A2A3C]
                rounded-lg px-4 py-2
            `;

            row.innerHTML = `
                <div class="flex items-center gap-3 truncate">
                    <div class="w-8 h-8 flex items-center justify-center
                                rounded bg-[#E1D6FF] text-[#5F2DED]">
                        <i class="icofont-file-alt"></i>
                    </div>
                    <span class="truncate text-sm text-[#373737] dark:text-white">
                        ${file.name}
                    </span>
                </div>

                <button type="button"
                        class="text-red-500 hover:text-red-700 text-lg font-bold"
                        data-index="${index}">
                    ×
                </button>
            `;

            previewList.appendChild(row);
        });

        previewList.querySelectorAll("button").forEach(btn => {
            btn.addEventListener("click", () => {
                removeFile(btn.dataset.index);
            });
        });
    }

    function removeFile(index) {
        uploadedFiles.splice(index, 1);
        syncInput();
        renderPreview();
    }
});
</script>
