@extends('layouts.dashboard')

@section('content')
<div class="flex-1 flex flex-col p-6 sm:p-10 h-auto bg-whiteColor shadow-lg dark:bg-darkblack rounded-2xl transition-all
        duration-300 dark:ring-2 dark:ring-blue-500/20 dark:ring-offset-0">
    
    <div id="page-header" class="mb-4">
        <h1 id="page-title" class="text-2xl sm:text-3xl font-bold text-[#5E5F60] dark:text-white">Update Subject</h1>
        <p id="page-subtitle" class="text-[#ABB7C2] dark:text-white">Kindly fill out the form to update the subject.</p>
    </div>

    <form id="formupdatesubject" 
          class="space-y-6"
          action="{{ route('admin.updatesubject', $subject->id) }}" 
          method="POST">
        @csrf

        {{-- Subject Name --}}
        <div class="flex flex-col sm:flex-row gap-4 sm:gap-6">
            <div class="flex-1 flex flex-col gap-1">
                <label for="txtupdateSubjectName" class="text-[#373737] font-medium dark:text-white">Subject Name</label>
                <input 
                    value="{{ $subject->subject_name ?? '' }}"
                    type="text" 
                    id="txtupdateSubjectName" 
                    name="name" 
                    placeholder="Enter subject name"
                    class="p-3 border dark:placeholder:text-gray-50 border-[#ABB7C2] rounded-md text-base dark:bg-whiteColor-dark dark:text-white"
                >
                <p id="errorUpdateSubjectName" class="text-red-400 text-sm hidden">Subject name is required.</p>
            </div>
        </div>

        {{-- Grade Level --}}
        <div class="flex flex-col gap-1">
            <label for="cmblevel" class="text-[#373737] font-medium dark:text-white">Grade Level</label>
            <select 
                id="cmblevel" 
                name="grade_level" 
                class="p-3 border cursor-pointer border-[#ABB7C2] rounded-md text-base dark:bg-whiteColor-dark dark:text-white"
            >
                <option value="" disabled selected>Select Grade Level</option>
                @for ($i = 1; $i <= 12; $i++)
                    <option value="{{ $i }}" {{ $subject->grade_level == $i ? 'selected' : '' }}>Grade {{ $i }}</option>
                @endfor
            </select>
            <p id="errorUpdatelevel" class="text-red-400 text-sm hidden">Grade level is required.</p>
        </div>
        {{-- buttons --}}
               <div class="flex flex-col sm:flex-row w-full gap-4 sm:gap-0">
    {{-- back button --}}
    <div class="w-full sm:w-1/2 flex justify-start">
        <a href="{{ route('admin.subjects') }}"
            class="inline-flex justify-center items-center w-full sm:w-auto px-10 py-2
                   border border-primaryColor text-primaryColor font-medium
                   rounded-lg hover:bg-[#4d1db8] hover:text-white transition">
            Back
        </a>
    </div>

    {{-- submit button --}}
    <div class="w-full sm:w-1/2 flex flex-col sm:flex-row justify-end gap-3">
        <button
            type="button"
            id="btnUpdateSubjectClear"
            class="w-full sm:flex-1 px-4 py-3
                   border border-[#ABB7C2] text-[#5E5F60] font-medium
                   dark:text-[#ABB7C2] dark:hover:text-white
                   rounded-md hover:bg-red-400 hover:text-white
                   transition-colors duration-200">
            Clear
        </button>

        <button
            type="button"
            id="openUpdateSubjectModal"
            data-title="Update Subject"
            data-open="lmsmodal"
            data-content="confirmUpdateSubjectModal"
            class="w-full sm:flex-1 px-4 py-3
                   bg-[#5F2DED] text-white font-medium text-base
                   rounded-md hover:bg-[#4e26c6]
                   transition-colors duration-200">
            Update Subject
        </button>
    </div>
</div>

    </form>
</div>

<x-lmsmodal/>

{{-- Confirm Update Modal --}}
<div id="confirmUpdateSubjectModal" class="hidden">
    <div class="flex justify-center items-center flex-col w-full">
        <p class="dark:text-white">Are you sure you want to update this subject?</p>
        <div class="flex flex-col sm:flex-row gap-4 mt-4 w-full">
            <button data-close="lmsmodal" 
                    class="px-4 flex-1 py-2 border dark:text-white dark:hover:text-black border-[#ABB7C2] hover:bg-gray-100 rounded">
                Cancel
            </button>
            <button id="confirmUpdateSubject" form="formupdatesubject" 
                    class="px-4 py-2 flex-1 bg-[#5F2DED] hover:bg-[#4f23c6] text-white rounded">
                Confirm
            </button>
        </div>
    </div>
</div>
@endsection
