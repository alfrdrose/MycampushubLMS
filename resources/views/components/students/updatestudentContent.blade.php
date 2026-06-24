@extends('layouts.dashboard')

@section('content')
<div class="flex-1 flex flex-col p-4 sm:p-10 h-auto bg-whiteColor shadow-lg dark:bg-darkblack rounded-2xl transition-all duration-300 dark:ring-2 dark:ring-blue-500/20 dark:ring-offset-0">
    
    <!-- Page Header -->
    <div id="page-header" class="mb-4">
        <h1 class="text-2xl sm:text-3xl font-bold text-[#5E5F60] dark:text-white">Update Student</h1>
        <p class="text-sm sm:text-base text-[#ABB7C2]">Kindly fill out the form to update the student.</p>
    </div>

    <!-- Update Student Form -->
    <form id="formupdatestudent" class="space-y-6" action="{{ route('admin.updatestudent', $student->id) }}" method="POST">
        @csrf

        <!-- Name Fields -->
        <div class="flex flex-col sm:flex-row gap-4 sm:gap-6">
            <div class="flex-1 flex flex-col gap-1">
                <label class="text-[#373737] font-medium dark:text-white" for="txtupdatefn">First Name</label>
                <input type="text" id="txtupdatefn" name="first_name" placeholder="Enter First Name" 
                    value="{{ $student->first_name ?? '' }}"
                    class="p-3 border border-[#ABB7C2] rounded-md text-base dark:bg-whiteColor-dark dark:text-white">
                <p id="errorUpdatefn" class="text-red-400 text-sm hidden">First name is required.</p>
            </div>

            <div class="flex-1 flex flex-col gap-1">
                <label class="text-[#373737] font-medium dark:text-white" for="txtupdateln">Last Name</label>
                <input type="text" id="txtupdateln" name="last_name" placeholder="Enter Last Name" 
                    value="{{ $student->last_name ?? '' }}"
                    class="p-3 border border-[#ABB7C2] rounded-md text-base dark:bg-whiteColor-dark dark:text-white">
                <p id="errorUpdateln" class="text-red-400 text-sm hidden">Last name is required.</p>
            </div>
        </div>

        <!-- Guardian Email -->
        <div class="flex flex-col gap-1">
            <label class="text-[#373737] font-medium dark:text-white" for="txtupdateguardianemail">Guardian Email</label>
            <input type="text" id="txtupdateguardianemail" name="guardian_email" placeholder="Enter Guardian Email" 
                value="{{ $student->guardian_email ?? '' }}"
                class="p-3 border border-[#ABB7C2] rounded-md text-base dark:bg-whiteColor-dark dark:text-white">
            <p id="errorUpdateguardianemail" class="text-red-400 text-sm hidden">Guardian email is required.</p>
        </div>

        <!-- Grade Level -->
        <div class="flex flex-col gap-1">
            <label class="text-[#373737] font-medium dark:text-white" for="cmbUpdategrade">Grade Level</label>
            <select id="cmbUpdategrade" name="grade" 
                class="p-3 border border-[#ABB7C2] rounded-md text-base dark:bg-whiteColor-dark dark:text-white">
                <option value="" disabled>Select Grade Level</option>
                @for ($i = 1; $i <= 12; $i++)
                    <option value="{{ $i }}" {{ $student->grade == $i ? 'selected' : '' }}>
                        Grade {{ $i }}
                    </option>
                @endfor
            </select>
            <p id="errorUpdategrade" class="text-red-400 text-sm hidden">Grade level is required.</p>
        </div>

        <!-- Buttons -->
        <div class="flex flex-col sm:flex-row w-full gap-4 sm:gap-0">
            <!-- Back Button -->
            <div class="w-full sm:w-1/2 flex justify-start">
                <a href="{{ route('admin.students') }}"
                    class="inline-flex justify-center items-center w-full sm:w-auto px-10 py-2
                        border border-primaryColor text-primaryColor font-medium
                        rounded-lg hover:bg-[#4d1db8] hover:text-white transition">
                    Back
                </a>
            </div>

            <!-- Action Buttons -->
            <div class="w-full sm:w-1/2 flex flex-col sm:flex-row justify-end gap-3">
                <button
                    type="button"
                    id="btnUpdateStudentClear"
                    class="w-full sm:flex-1 px-4 py-3
                        border border-[#ABB7C2] text-[#5E5F60] font-medium
                        dark:text-[#ABB7C2] dark:hover:text-white
                        rounded-md hover:bg-red-400 hover:text-white
                        transition-colors duration-200">
                    Clear
                </button>

                <button
                    type="button"
                    id="openUpdateStudentModal"
                    data-title="Update Student"
                    data-open="lmsmodal"
                    data-content="confirmUpdateStudentModal"
                    class="w-full sm:flex-1 px-4 py-3
                        bg-[#5F2DED] text-white font-medium text-base
                        rounded-md hover:bg-[#4e26c6]
                        transition-colors duration-200">
                    Update Student
                </button>
            </div>
        </div>

    </form>
</div>

<!-- Modal -->
<x-lmsmodal/>

<div id="confirmUpdateStudentModal" class="hidden">
    <div class="flex flex-col gap-4 justify-center items-center w-full">
        <p class="dark:text-white">Are you sure you want to update this student?</p>
        <div class="flex flex-col sm:flex-row gap-4 w-full">
            <button data-close="lmsmodal" 
                class="px-4 py-2 flex-1 border dark:text-white dark:hover:text-black border-[#ABB7C2] rounded">
                Cancel
            </button>
            <button type="submit" form="formupdatestudent" 
                class="px-4 py-2 flex-1 bg-[#5F2DED] hover:bg-[#4f23c6] text-white rounded">
                Confirm
            </button>
        </div>
    </div>
</div>
@endsection
