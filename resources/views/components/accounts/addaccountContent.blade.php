@extends('layouts.dashboard')

@section('content')
<div class="flex-1 flex flex-col p-6 sm:p-10 h-auto bg-whiteColor shadow-lg dark:bg-darkblack rounded-2xl transition-all duration-300 dark:ring-2 dark:ring-blue-500/20 dark:ring-offset-0">

    {{-- Header --}}
    <div id="page-header" class="mb-6 sm:mb-8">
        <h1 id="page-title" class="text-2xl sm:text-3xl font-bold text-[#5E5F60] dark:text-white">Add Account</h1>
        <p id="page-subtitle" class="text-sm sm:text-base text-[#ABB7C2]">Kindly fill out the form to add a new account.</p>
    </div>

    {{-- Form --}}
    <form id="formaddaccount" action="{{ route('admin.store') }}" method="POST" class="w-full">
        @csrf

        <div class="flex flex-col gap-6 sm:gap-10">

            {{-- Username & Email --}}
            <div class="flex flex-col sm:flex-row gap-4 sm:gap-6">
                <div class="flex-1 flex flex-col gap-1">
                    <label for="txtusername" class="text-[#373737] font-medium dark:text-white">Username</label>
                    <input type="text" id="txtusername" name="name" placeholder="Enter username"
                        class="p-3 dark:placeholder-gray-300 border border-[#ABB7C2] rounded-md text-base dark:bg-whiteColor-dark dark:text-white w-full">
                    <p id="errorusername" class="text-red-400 text-sm hidden">Username is required.</p>
                </div>
                <div class="flex-1 flex flex-col gap-1">
                    <label for="txtemail" class="text-[#373737] font-medium dark:text-white">
                        Email
                    </label>

                    <input type="email"
                        id="txtemail"
                        name="email"
                        value="{{ old('email') }}"
                        placeholder="Enter email"
                        class="p-3 border rounded-md text-base w-full
                            dark:bg-whiteColor-dark dark:text-white
                            @error('email') border-red-500 @else border-[#ABB7C2] @enderror">

                    @error('email')
                        <p class="text-red-500 text-sm">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

            </div>

            {{-- Password & Confirm Password --}}
            <div class="flex-1 flex flex-col gap-1">
                <label for="txtpassword" class="text-[#373737] font-medium dark:text-white">Password</label>
                <div class="flex items-center border border-[#ABB7C2] rounded-md p-1 dark:bg-whiteColor-dark dark:text-white">
                    <input type="password" id="txtpassword" name="password" placeholder="Enter password"
                        class="flex-1 dark:placeholder-gray-300 bg-transparent outline-none border-none text-base dark:text-white">
                    <i id="togglePassword" class="icofont-eye-blocked text-[#5E5F60] dark:text-gray-300 cursor-pointer mr-2"></i>
                </div>
                <p id="errorpassword" class="text-red-400 text-sm hidden">Password error.</p>
            </div>

            {{-- Confirm Password --}}
            <div class="flex-1 flex flex-col gap-1">
                <label for="txtconfirmpass" class="text-[#373737] font-medium dark:text-white">Confirm Password</label>
                <div class="flex items-center border border-[#ABB7C2] rounded-md p-1 dark:bg-whiteColor-dark dark:text-white">
                    <input type="password" id="txtconfirmpass" name="password_confirmation" placeholder="Confirm password"
                        class="flex-1 dark:placeholder-gray-300 bg-transparent border-none outline-none text-base dark:text-white">
                    <i id="togglePassword2" class="icofont-eye-blocked text-[#5E5F60] dark:text-gray-300 cursor-pointer mr-2"></i>
                </div>
                <p id="errorconfirmpass" class="text-red-400 text-sm hidden">Confirm password error.</p>
            </div>



            {{-- Account Type --}}
            <div class="flex flex-col gap-1">
                <label for="cmbtype" class="text-[#373737] font-medium dark:text-white">Account Type</label>
                <select id="cmbtype" name="role" class="p-3 cursor-pointer border border-[#ABB7C2] rounded-md text-base dark:bg-whiteColor-dark dark:text-white w-full">
                    <option value=""  disabled selected>Select role</option>
                    <option value="admin">LMS Supervisor</option>
                    <option value="teacher">Teacher</option>
                    <option value="student">Student</option>
                </select>
                <p id="errortype" class="text-red-400 text-sm hidden">Role is required.</p>
            </div>
           <div id="accountMainButtons" class="flex flex-col sm:flex-row w-full gap-4">

                {{-- Back button --}}
                <div class="w-full sm:w-1/2 flex justify-start">
                    <a href="{{ route('admin.accounts') }}"
                    class="w-full sm:w-auto inline-flex justify-center items-center
                            px-6 sm:px-10 py-2
                            border border-primaryColor
                            text-primaryColor font-medium
                            rounded-lg
                            hover:bg-[#4d1db8] hover:text-white
                            transition">
                        Back
                    </a>
                </div>

                {{-- Submit buttons --}}
                <div class="w-full sm:w-1/2 flex flex-col sm:flex-row justify-end gap-2">
                    <button
                        type="button"
                        id="btnAddAccountClear"
                        class="w-full sm:flex-1 px-4 py-2
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
                        id="openAddAccountModal"
                        data-title="Add Account"
                        data-open="lmsmodal"
                        data-content="confirmAddModal"
                        class="w-full sm:flex-1 px-4 py-2
                            bg-[#5F2DED] text-white font-medium text-base
                            rounded-md
                            hover:bg-[#4e26c6]
                            transition-colors duration-200">
                        Add account
                    </button>
                </div>

            </div>

        </div>
    </form>

    <form id="formaddteacherfromaccount" action="{{ route('admin.storeteacherwithaccount') }}" method="POST" class="w-full hidden role-section" data-role="teacher">
        @csrf
        <input type="hidden" id="accountNameTeacher" name="account_name">
        <input type="hidden" id="accountEmailTeacher" name="account_email">
        <input type="hidden" id="accountPasswordTeacher" name="account_password">
        <input type="hidden" id="accountPasswordConfirmTeacher" name="account_password_confirmation">
        <input type="hidden" id="accountRoleTeacher" name="account_role" value="teacher">
        <div class="flex flex-col gap-6 sm:gap-10">
            {{-- Teacher Name --}}
            <div class="flex flex-col sm:flex-row gap-4 sm:gap-6 mt-4">
                <div class="flex-1 flex flex-col gap-1">
                    <label for="teacherFirstName" class="text-[#373737] font-medium dark:text-white">First Name</label>
                    <input type="text" id="teacherFirstName" name="first_name" placeholder="Enter first name"
                        class="p-3 dark:placeholder-gray-300 border border-[#ABB7C2] rounded-md text-base dark:bg-whiteColor-dark dark:text-white w-full">
                    <p id="errorTeacherFirstName" class="text-red-400 text-sm hidden">First name is required.</p>
                </div>
                <div class="flex-1 flex flex-col gap-1">
                    <label for="teacherLastName" class="text-[#373737] font-medium dark:text-white">Last Name</label>
                    <input type="text" id="teacherLastName" name="last_name" placeholder="Enter last name"
                        class="p-3 dark:placeholder-gray-300 border border-[#ABB7C2] rounded-md text-base dark:bg-whiteColor-dark dark:text-white w-full">
                    <p id="errorTeacherLastName" class="text-red-400 text-sm hidden">Last name is required.</p>
                </div>
            </div>


            {{-- Faculty --}}
            <div class="flex flex-col gap-1">
                <label for="teacherFaculty" class="text-[#373737] font-medium dark:text-white">Faculty</label>
                <select id="teacherFaculty" name="faculty" class="p-3 cursor-pointer border border-[#ABB7C2] rounded-md text-base dark:bg-whiteColor-dark dark:text-white w-full">
                    <option value="" disabled selected>Select Faculty</option>
                    <option value="english">English</option>
                    <option value="mathematics">Mathematics</option>
                    <option value="science">Science</option>
                    <option value="filipino">Filipino</option>
                    <option value="ap">Araling Panlipunan</option>
                    <option value="mapeh">MAPEH</option>
                    <option value="esp">ESP (Values Education)</option>
                    <option value="tle">TLE / ICT</option>
                    <option value="shs_core">Senior High Core</option>
                    <option value="shs_specialized">Senior High Specialized</option>
                </select>
                <p id="errorTeacherFaculty" class="text-red-400 text-sm hidden">Please select a faculty type.</p>
            </div>

            <div class="flex w-full justify-end">



                {{-- Submit buttons --}}
                <div class="w-full sm:w-1/2 flex flex-col sm:flex-row justify-end gap-2">
                    <button
                        type="button"
                        id="btnClearTeacherFromAccount"
                        class="w-full sm:flex-1 px-4 py-2
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
                        id="openAddTeacherFromAccountModal"
                        data-title="Add Teacher"
                        data-open="lmsmodal"
                        data-content="confirmAddTeacherFromAccountModal"
                        class="w-full sm:flex-1 px-4 py-2
                            bg-[#5F2DED] text-white font-medium text-base
                            rounded-md
                            hover:bg-[#4e26c6]
                            transition-colors duration-200">
                        Add Teacher
                    </button>
                </div>

            </div>

        </div>
    </form>

    <form id="formaddstudentfromaccount" action="{{ route('admin.storestudentwithaccount') }}" method="POST" class="w-full hidden role-section" data-role="student">
        @csrf
        <input type="hidden" id="accountNameStudent" name="account_name">
        <input type="hidden" id="accountEmailStudent" name="account_email">
        <input type="hidden" id="accountPasswordStudent" name="account_password">
        <input type="hidden" id="accountPasswordConfirmStudent" name="account_password_confirmation">
        <input type="hidden" id="accountRoleStudent" name="account_role" value="student">
        <div class="flex flex-col gap-6 sm:gap-10">
            {{-- Student Name --}}
            <div class="flex flex-col sm:flex-row gap-4 sm:gap-6 mt-4">
                <div class="flex-1 flex flex-col gap-1">
                    <label for="studentFirstName" class="text-[#373737] font-medium dark:text-white">First Name</label>
                    <input type="text" id="studentFirstName" name="first_name" placeholder="Enter first name"
                        class="p-3 dark:placeholder-gray-300 border border-[#ABB7C2] rounded-md text-base dark:bg-whiteColor-dark dark:text-white w-full">
                    <p id="errorStudentFirstName" class="text-red-400 text-sm hidden">First name is required.</p>
                </div>
                <div class="flex-1 flex flex-col gap-1">
                    <label for="studentLastName" class="text-[#373737] font-medium dark:text-white">Last Name</label>
                    <input type="text" id="studentLastName" name="last_name" placeholder="Enter last name"
                        class="p-3 dark:placeholder-gray-300 border border-[#ABB7C2] rounded-md text-base dark:bg-whiteColor-dark dark:text-white w-full">
                    <p id="errorStudentLastName" class="text-red-400 text-sm hidden">Last name is required.</p>
                </div>
            </div>


            {{-- Guardian Email --}}
            <div class="flex flex-col gap-1">
                <label for="studentGuardianEmail" class="text-[#373737] font-medium dark:text-white">Guardian Email</label>
                <input type="text" id="studentGuardianEmail" name="guardian_email" placeholder="Enter guardian email"
                    class="p-3 dark:placeholder-gray-300 border border-[#ABB7C2] rounded-md text-base dark:bg-whiteColor-dark dark:text-white w-full">
                <p id="errorStudentGuardianEmail" class="text-red-400 text-sm hidden">Please enter a valid guardian email.</p>
            </div>

            {{-- Grade --}}
            <div class="flex flex-col gap-1">
                <label for="studentGrade" class="text-[#373737] font-medium dark:text-white">Grade Level</label>
                <select id="studentGrade" name="grade" class="p-3 cursor-pointer border border-[#ABB7C2] rounded-md text-base dark:bg-whiteColor-dark dark:text-white w-full">
                    <option value="" disabled selected>Select Grade Level</option>
                    @for ($i = 1; $i <= 12; $i++)
                        <option value="{{ $i }}">Grade {{ $i }}</option>
                    @endfor
                </select>
                <p id="errorStudentGrade" class="text-red-400 text-sm hidden">Please select a grade level.</p>
            </div>

            <div class="flex w-full justify-end">



            {{-- Submit buttons --}}
            <div class="w-full sm:w-1/2 flex flex-col sm:flex-row justify-end gap-2">
                <button
                    type="button"
                    id="btnClearStudentFromAccount"
                    class="w-full sm:flex-1 px-4 py-2
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
                    id="openAddStudentFromAccountModal"
                    data-title="Add Student"
                    data-open="lmsmodal"
                    data-content="confirmAddStudentFromAccountModal"
                    class="w-full sm:flex-1 px-4 py-2
                        bg-[#5F2DED] text-white font-medium text-base
                        rounded-md
                        hover:bg-[#4e26c6]
                        transition-colors duration-200">
                    Add Student
                </button>
            </div>

        </div>

        </div>
    </form>
</div>


@endsection

<x-lmsmodal />

<div id="confirmAddModal" class="hidden">
    <div class="flex justify-center items-center flex-col w-full max-w-md mx-auto p-2">
        <p class="dark:text-white text-center">Are you sure you want to add this account?</p>
        <div class="flex flex-col sm:flex-row gap-4 mt-5 w-full">
            <button data-close="lmsmodal" class="px-4 py-2 flex-1 border dark:text-white dark:hover:text-black border-[#ABB7C2] hover:bg-gray-100 rounded">
                Cancel
            </button>
            <button type="submit" id="confirmAdd" form="formaddaccount" class="px-4 py-2 flex-1 bg-[#5F2DED] hover:bg-[#4f23c6] text-white rounded">
                Confirm
            </button>
        </div>
    </div>
</div>

<div id="confirmAddTeacherFromAccountModal" class="hidden">
    <div class="flex justify-center items-center flex-col w-full max-w-md mx-auto p-2">
        <p class="dark:text-white text-center">Are you sure you want to add this teacher?</p>
        <div class="flex flex-col sm:flex-row gap-4 mt-5 w-full">
            <button data-close="lmsmodal" class="px-4 py-2 flex-1 border dark:text-white dark:hover:text-black border-[#ABB7C2] hover:bg-gray-100 rounded">
                Cancel
            </button>
            <button type="submit" id="confirmAddTeacherFromAccount" form="formaddteacherfromaccount" class="px-4 py-2 flex-1 bg-[#5F2DED] hover:bg-[#4f23c6] text-white rounded">
                Confirm
            </button>
        </div>
    </div>
</div>

<div id="confirmAddStudentFromAccountModal" class="hidden">
    <div class="flex justify-center items-center flex-col w-full max-w-md mx-auto p-2">
        <p class="dark:text-white text-center">Are you sure you want to add this student?</p>
        <div class="flex flex-col sm:flex-row gap-4 mt-5 w-full">
            <button data-close="lmsmodal" class="px-4 py-2 flex-1 border dark:text-white dark:hover:text-black border-[#ABB7C2] hover:bg-gray-100 rounded">
                Cancel
            </button>
            <button type="submit" id="confirmAddStudentFromAccount" form="formaddstudentfromaccount" class="px-4 py-2 flex-1 bg-[#5F2DED] hover:bg-[#4f23c6] text-white rounded">
                Confirm
            </button>
        </div>
    </div>
</div>
