<div class="flex-1 flex flex-col p-4 sm:p-10 h-auto bg-whiteColor shadow-lg dark:bg-darkblack rounded-2xl transition-all duration-300 dark:ring-2 dark:ring-blue-500/20 dark:ring-offset-0">
    
    <!-- Page Header -->
    <div id="page-header" class="mb-4">
        <h1 class="text-2xl sm:text-3xl font-bold text-[#5E5F60] dark:text-white">Add Student</h1>
        <p class="text-sm sm:text-base text-[#ABB7C2]">Kindly fill out the form to add a new student.</p>
    </div>

    <!-- Add Student Form -->
    <form id="formaddstudent" action="{{ route('admin.storestudent') }}" method="POST" class="space-y-6">
        @csrf

        <!-- Name Fields -->
        <div class="flex flex-col sm:flex-row gap-4 sm:gap-6">
            <div class="flex-1 flex flex-col gap-1">
                <label class="text-[#373737] font-medium dark:text-white" for="txtfn">First Name</label>
                <input type="text" id="txtfn" name="first_name" placeholder="Enter first name"
                    class="p-3 border border-[#ABB7C2] rounded-md text-base dark:placeholder:text-gray-50 dark:bg-whiteColor-dark dark:text-white">
                <p id="errorfn" class="text-red-400 text-sm hidden">First name is required.</p>
            </div>
            <div class="flex-1 flex flex-col gap-1">
                <label class="text-[#373737] font-medium dark:text-white" for="txtln">Last Name</label>
                <input type="text" id="txtln" name="last_name" placeholder="Enter last name"
                    class="p-3 border dark:placeholder:text-gray-50 border-[#ABB7C2] rounded-md text-base dark:bg-whiteColor-dark dark:text-white">
                <p id="errorln" class="text-red-400 text-sm hidden">Last name is required.</p>
            </div>
        </div>

        <!-- Email -->
        <div class="flex flex-col gap-1">
            <label class="text-[#373737] font-medium dark:text-white" for="txtemail">Email</label>
            <input type="text" id="txtemail" name="email" placeholder="Enter Email"
                class="p-3 border dark:placeholder:text-gray-50 border-[#ABB7C2] rounded-md text-base dark:bg-whiteColor-dark dark:text-white">
            <p id="erroremail" class="text-red-400 text-sm hidden">Email is required.</p>
        </div>

        <!-- Guardian Email -->
        <div class="flex flex-col gap-1">
            <label class="text-[#373737] font-medium dark:text-white" for="txtguardianemail">Guardian Email</label>
            <input type="text" id="txtguardianemail" name="guardian_email" placeholder="Enter Guardian Email"
                class="p-3 border dark:placeholder:text-gray-50 border-[#ABB7C2] rounded-md text-base dark:bg-whiteColor-dark dark:text-white">
            <p id="errorguardianemail" class="text-red-400 text-sm hidden">Guardian email is required.</p>
        </div>

        <!-- Grade -->
        <div class="flex flex-col gap-1">
            <label class="text-[#373737] font-medium dark:text-white" for="cmbgrade">Grade Level</label>
            <select id="cmbgrade" name="grade"
                class="p-3 border cursor-pointer border-[#ABB7C2] rounded-md text-base dark:bg-whiteColor-dark dark:text-white">
                <option value="" disabled selected>Select Grade Level</option>
                @for ($i = 1; $i <= 12; $i++)
                    <option value="{{ $i }}">Grade {{ $i }}</option>
                @endfor
            </select>
            <p id="errorgrade" class="text-red-400 text-sm hidden">Please select a grade level.</p>
        </div>
        <div class="flex flex-row w-full"> 
                {{-- back button --}}
                <div class="w-1/2 flex justify-start"> 
                    <a href="{{ route('admin.students') }}"
                    class="inline-flex items-center px-10 py-2 border border-primaryColor text-primaryColor font-medium

                            rounded-lg hover:bg-[#4d1db8] hover:text-white transition ">
                        Back
                    </a>
                </div>
                {{-- submit button --}}
                <div class="w-1/2 flex justify-end gap-2">
                    <button type="button" id="btnclearaddstudent"
                        class="flex-1 border border-[#ABB7C2] text-[#5E5F60] font-medium dark:text-[#ABB7C2] dark:hover:text-white rounded-md hover:bg-red-400 hover:text-white transition-colors duration-200">
                        Clear
                    </button>
                    <button type="button" id="openAddStudentModal" data-title="Add Account" data-open="lmsmodal" data-content="confirmAddModal"
                        class="flex-1 p-3 bg-[#5F2DED] text-white font-medium text-base rounded-md hover:bg-[#4e26c6] transition-colors duration-200">
                        Add Student
                    </button>
                </div>
            </div>
    </form>
</div>

<!-- Modal -->
<x-lmsmodal/>

<div id="confirmStudentContent" class="hidden">
    <div class="flex justify-center items-center flex-col w-full gap-4">
        <p class="dark:text-white">Are you sure you want to add this student?</p>
        <div class="flex flex-col sm:flex-row gap-4 w-full">
            <button data-close="lmsmodal"
                class="px-4 flex-1 py-2 border dark:text-white dark:hover:text-black border-[#ABB7C2] rounded">Cancel</button>
            <button id="confirmAddStudent" form="formaddstudent"
                class="px-4 py-2 flex-1 bg-[#5F2DED] hover:bg-[#4f23c6] text-white rounded">Confirm</button>
        </div>
    </div>
</div>
