@extends('layouts.dashboard')

@section('content')
<div class=" flex-1 flex flex-col p-10 h-auto bg-whiteColor shadow-lg dark:bg-darkblack rounded-2xl transition-all
        duration-300 dark:ring-2 dark:ring-blue-500/20 dark:ring-offset-0">
    <div id="page-header" class="mb-4  ">
            <h1 id="page-title" class="text-3xl font-bold text-[#5E5F60] dark:text-white">Add Subject</h1>
            <p id="page-subtitle" class="text-[#ABB7C2]">Kindly fill out the form to add a new subject.</p>
        </div>
    <form id="formaddsubject" action="{{route('admin.storesubject')}}" method="POST">
        @csrf
            <div class="flex flex-col gap-10 mt-4">
                {{-- Username & Email --}}
                <div class="flex flex-row gap-6">
                    {{-- username --}}
                    <div class="flex flex-1 flex-col gap-1"> 
                        <p class="text-[#373737] font-medium dark:text-white">Subject Name</p>
                        <input type="text" id="txtsubjectname" name="subject_name" class="p-3 dark:placeholder:text-gray-50 border border-[#ABB7C2] rounded-md text-base dark:bg-whiteColor-dark dark:text-white" placeholder="Enter subject name">
                        <p id="errorname" class="text-red-400 text-sm hidden">Subject name is required.</p>
                    
                        
                        
                    </div>
                </div>
                
                
                
                {{-- Subject Grade Level --}}
                <div class="flex flex-col gap-1">
                    <p class="text-[#373737] font-medium dark:text-white">Grade Level</p>
                    <select id="cmblevel" name="grade_level" class="p-3 cursor-pointer border border-[#ABB7C2] rounded-md text-base dark:bg-whiteColor-dark dark:text-white">
                        <option value="" disabled selected>Select Grade Level</option>
                        <option value="1">Grade 1</option>
                        <option value="2">Grade 2</option>
                        <option value="3">Grade 3</option>
                        <option value="4">Grade 4</option>
                        <option value="5">Grade 5</option>
                        <option value="6">Grade 6</option>
                        <option value="7">Grade 7</option>
                        <option value="8">Grade 8</option>
                        <option value="9">Grade 9</option>
                        <option value="10">Grade 10</option>
                        <option value="11">Grade 11</option>
                        <option value="12">Grade 12</option>
                    </select>
                    <p id="errorlevel" class="text-red-400 text-sm hidden">Please select a level.</p>
                            
                        
                </div>

                {{-- buttons --}}
                <div class="flex flex-col sm:flex-row w-full gap-4 sm:gap-0">

    {{-- Back button --}}
    <div class="w-full sm:w-1/2 flex justify-start">
        <a href="{{ route('admin.subjects') }}"
           class="inline-flex justify-center items-center
                  w-full sm:w-auto
                  px-6 sm:px-10 py-2
                  border border-primaryColor text-primaryColor font-medium
                  rounded-lg
                  hover:bg-[#4d1db8] hover:text-white
                  transition">
            Back
        </a>
    </div>

    {{-- Action buttons --}}
    <div class="w-full sm:w-1/2 flex flex-col sm:flex-row justify-end gap-3">
        <button
            type="button"
            id="btnclearaddsubject"
            class="w-full sm:flex-1
                   py-3
                   border border-[#ABB7C2]
                   text-[#5E5F60] font-medium
                   dark:text-[#ABB7C2] dark:hover:text-white
                   rounded-md
                   hover:bg-red-400 hover:text-white
                   transition-colors duration-200">
            Clear
        </button>

        <button
            type="button"
            id="openAddSubjectModal"
            data-title="Add Subject"
            data-open="lmsmodal"
            data-content="confirmSubjectContent"
            class="w-full sm:flex-1
                   py-3
                   bg-[#5F2DED] text-white
                   font-medium text-base
                   rounded-md
                   hover:bg-[#4e26c6]
                   transition-colors duration-200">
            Add Subject
        </button>
    </div>

</div>


    </div>

    </form>
</div>


<x-lmsmodal/>


<div id="confirmSubjectContent" class="hidden">
    <div class="flex justify-center items-center flex-col w-full"> 
        <p class="dark:text-white">Are you sure you want to add this subject?</p>
        <div class="flex gap-4 mt-4 w-full">
            <button data-close="lmsmodal" class="px-4 flex-1 py-2 border dark:text-white dark:hover:text-black border-[#ABB7C2] hover:bg-gray-100 rounded">Cancel</button>
            <button id="confirmAddSubject" form="formaddsubject" class="px-4 py-2 flex-1 bg-[#5F2DED] hover:bg-[#4f23c6] text-white rounded">Confirm</button>
        </div>
    </div>
    
</div>
@endsection