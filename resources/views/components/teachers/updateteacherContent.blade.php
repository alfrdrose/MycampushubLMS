@extends('layouts.dashboard')

@section('content')
<div class="flex-1 flex flex-col p-6 sm:p-10 h-auto bg-whiteColor shadow-lg dark:bg-darkblack rounded-2xl transition-all duration-300 dark:ring-2 dark:ring-blue-500/20 dark:ring-offset-0">

    <!-- Page Header -->
    <div id="page-header" class="mb-4">
        <h1 id="page-title" class="text-2xl sm:text-3xl font-bold text-[#5E5F60] dark:text-white">Update Teacher</h1>
        <p id="page-subtitle" class="text-sm sm:text-base text-[#ABB7C2] dark:text-gray-400">Kindly fill out the form to update the teacher.</p>
    </div>

    <!-- Update Teacher Form -->
    <form id="formupdateteacher" class="space-y-6" action="{{ route('admin.updateteacher', $teacher->id) }}" method="POST">
        @csrf

        <!-- Name Fields -->
        <div class="flex flex-col sm:flex-row gap-6">
            <div class="flex-1 flex flex-col gap-1">
                <label class="text-[#373737] font-medium dark:text-white" for="txtupdatefn">First Name</label>
                <input 
                    value="{{ $teacher->first_name ?? '' }}"
                    type="text" id="txtupdatefn" name="first_name" 
                    class="p-3 border dark:placeholder:text-gray-50 border-[#ABB7C2] rounded-md text-base dark:bg-whiteColor-dark dark:text-white w-full"
                    placeholder="Enter First Name">
                <p id="errorUpdatefn" class="text-red-400 text-sm hidden">First name is required.</p>
            </div>

            <div class="flex-1 flex flex-col gap-1">
                <label class="text-[#373737] font-medium dark:text-white" for="txtupdateln">Last Name</label>
                <input 
                    value="{{ $teacher->last_name ?? '' }}"
                    type="text" id="txtupdateln" name="last_name" 
                    class="p-3 border dark:placeholder:text-gray-50 border-[#ABB7C2] rounded-md text-base dark:bg-whiteColor-dark dark:text-white w-full"
                    placeholder="Enter Last Name">
                <p id="errorUpdateln" class="text-red-400 text-sm hidden">Last name is required.</p>
            </div>
        </div>

        <!-- Faculty -->
        <div class="flex flex-col gap-1">
            <label class="text-[#373737] font-medium dark:text-white" for="cmbUpdatefaculty">Subject / Faculty</label>
            <select 
                id="cmbUpdatefaculty" 
                name="faculty" 
                class="p-3 border cursor-pointer border-[#ABB7C2] rounded-md text-base dark:bg-whiteColor-dark dark:text-white w-full">
                <option value="" disabled>Select Faculty</option>
                <option value="english" {{ $teacher->faculty == 'english' ? 'selected' : '' }}>English</option>
                <option value="mathematics" {{ $teacher->faculty == 'mathematics' ? 'selected' : '' }}>Mathematics</option>
                <option value="science" {{ $teacher->faculty == 'science' ? 'selected' : '' }}>Science</option>
                <option value="filipino" {{ $teacher->faculty == 'filipino' ? 'selected' : '' }}>Filipino</option>
                <option value="ap" {{ $teacher->faculty == 'ap' ? 'selected' : '' }}>Araling Panlipunan</option>
                <option value="mapeh" {{ $teacher->faculty == 'mapeh' ? 'selected' : '' }}>MAPEH</option>
                <option value="esp" {{ $teacher->faculty == 'esp' ? 'selected' : '' }}>ESP (Values Education)</option>
                <option value="tle" {{ $teacher->faculty == 'tle' ? 'selected' : '' }}>TLE / ICT</option>
                <option value="shs_core" {{ $teacher->faculty == 'shs_core' ? 'selected' : '' }}>Senior High Core</option>
                <option value="shs_specialized" {{ $teacher->faculty == 'shs_specialized' ? 'selected' : '' }}>Senior High Specialized</option>
            </select>
            <p id="errorUpdatefaculty" class="text-red-400 text-sm hidden">Faculty is required.</p>
        </div>

        @if ($teacher->user)
            <div class="flex flex-col gap-1">
                <label class="text-[#373737] font-medium dark:text-white" for="status">Login Status</label>
                <select id="status" name="status" class="p-3 border cursor-pointer border-[#ABB7C2] rounded-md text-base dark:bg-whiteColor-dark dark:text-white w-full">
                    <option value="active" {{ $teacher->user->statusValue() === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ $teacher->user->statusValue() === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
        @else
            <div class="rounded-xl border border-dashed border-[#D7E1EA] px-4 py-3 text-sm text-[#617184] dark:border-white/10 dark:text-[#96A6BB]">
                This teacher has no linked login account yet.
            </div>
        @endif
        <div class="flex flex-col sm:flex-row w-full gap-4 sm:gap-0">
    {{-- back button --}}
    <div class="w-full sm:w-1/2 flex justify-start">
        <a href="{{ route('admin.teachers') }}"
            class="inline-flex justify-center items-center w-full sm:w-auto px-10 py-2 border border-primaryColor text-primaryColor font-medium
                   rounded-lg hover:bg-[#4d1db8] hover:text-white transition">
            Back
        </a>
    </div>

    {{-- submit buttons --}}
    <div class="w-full sm:w-1/2 flex flex-col sm:flex-row justify-end gap-3">
        <button
            type="button"
            id="btnUpdateTeacherClear"
            class="w-full sm:flex-1 px-4 py-3 border border-[#ABB7C2] text-[#5E5F60] font-medium
                   dark:text-[#ABB7C2] dark:hover:text-white rounded-md
                   hover:bg-red-400 hover:text-white transition-colors duration-200">
            Clear
        </button>

        <button
            type="button"
            id="openUpdateteacherModal"
            data-title="Add Account"
            data-open="lmsmodal"
            data-content="confirmAddModal"
            class="w-full sm:flex-1 px-4 py-3 bg-[#5F2DED] text-white font-medium text-base
                   rounded-md hover:bg-[#4e26c6] transition-colors duration-200">
            Update Teacher
        </button>
    </div>
</div>

    </form>
</div>

<!-- Modal Component -->
<x-lmsmodal/>

<div id="confirmUpdateteacherModal" class="hidden">
    <div class="flex flex-col justify-center items-center w-full gap-4">
        <p class="dark:text-white">Are you sure you want to update this teacher?</p>
        <div class="flex flex-col sm:flex-row gap-4 w-full">
            <button data-close="lmsmodal" class="px-4 flex-1 py-2 border dark:text-white dark:hover:text-black border-[#ABB7C2] rounded">Cancel</button>
            <button id="confirmUpdateTeacher" form="formupdateteacher" class="px-4 py-2 flex-1 bg-[#5F2DED] hover:bg-[#4f23c6] text-white rounded">Confirm</button>
        </div>
    </div>
</div>
@endsection
