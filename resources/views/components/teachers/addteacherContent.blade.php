<div class="flex-1 flex flex-col p-6 sm:p-10 h-auto bg-whiteColor shadow-lg dark:bg-darkblack rounded-2xl transition-all duration-300 dark:ring-2 dark:ring-blue-500/20 dark:ring-offset-0">
    <div id="page-header" class="mb-4">
        <h1 id="page-title" class="text-2xl sm:text-3xl font-bold text-[#5E5F60] dark:text-white">Add Teacher</h1>
        <p id="page-subtitle" class="text-sm sm:text-base text-[#ABB7C2]">Kindly fill out the form to add a new teacher.</p>
    </div>

    <form id="formaddteacher" action="{{route('admin.storeteacher')}}" method="POST">
        @csrf
        <div class="flex flex-col gap-6 mt-4">
            <!-- Teacher Name -->
            <div class="flex flex-col sm:flex-row gap-6">
                <!-- First Name -->
                <div class="flex-1 flex flex-col gap-1">
                    <label class="text-[#373737] font-medium dark:text-white" for="txtfn">First Name</label>
                    <input type="text" id="txtfn" name="first_name" placeholder="Enter first name"
                        class="p-3 border dark:placeholder:text-gray-50 border-[#ABB7C2] rounded-md text-base dark:bg-whiteColor-dark dark:text-white w-full">
                    <p id="errorfn" class="text-red-400 text-sm hidden">First name is required.</p>
                </div>

                <!-- Last Name -->
                <div class="flex-1 flex flex-col gap-1">
                    <label class="text-[#373737] font-medium dark:text-white" for="txtln">Last Name</label>
                    <input type="text" id="txtln" name="last_name" placeholder="Enter last name"
                        class="p-3 border dark:placeholder:text-gray-50 border-[#ABB7C2] rounded-md text-base dark:bg-whiteColor-dark dark:text-white w-full">
                    <p id="errorln" class="text-red-400 text-sm hidden">Last name is required.</p>
                </div>
            </div>

            <!-- Email -->
            <div class="flex flex-col gap-1">
                <label class="text-[#373737] font-medium dark:text-white" for="txtemail">Email</label>
                <input type="text" id="txtemail" name="email" placeholder="Enter email"
                    class="p-3 border dark:placeholder:text-gray-50 border-[#ABB7C2] rounded-md text-base dark:bg-whiteColor-dark dark:text-white w-full">
                <p id="erroremail" class="text-red-400 text-sm hidden">Email is required.</p>
            </div>

            <!-- Faculty -->
            <div class="flex flex-col gap-1">
                <label class="text-[#373737] font-medium dark:text-white" for="cmbfaculty">Faculty</label>
                <select id="cmbfaculty" name="faculty" class="p-3 cursor-pointer border border-[#ABB7C2] rounded-md text-base dark:bg-whiteColor-dark dark:text-white w-full">
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
                <p id="errorfaculty" class="text-red-400 text-sm hidden">Please select a faculty type.</p>
            </div>


            <div class="flex flex-row w-full"> 
                {{-- back button --}}
                <div class="w-1/2 flex justify-start"> 
                    <a href="{{ route('admin.teachers') }}"
                    class="inline-flex items-center px-10 py-2 border border-primaryColor text-primaryColor font-medium

                            rounded-lg hover:bg-[#4d1db8] hover:text-white transition ">
                        Back
                    </a>
                </div>
                {{-- submit button --}}
                <div class="w-1/2 flex justify-end gap-2">
                    <button type="button" id="btnclearaddteacher"
                        class="flex-1 border border-[#ABB7C2] text-[#5E5F60] font-medium dark:text-[#ABB7C2] dark:hover:text-white rounded-md hover:bg-red-400 hover:text-white transition-colors duration-200">
                        Clear
                    </button>
                    <button type="button" id="openAddTeacherModal" data-title="Add Account" data-open="lmsmodal" data-content="confirmAddModal"
                        class="flex-1 p-3 bg-[#5F2DED] text-white font-medium text-base rounded-md hover:bg-[#4e26c6] transition-colors duration-200">
                        Add Teacher
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<x-lmsmodal/>

<div id="confirmTeacherContent" class="hidden">
    <div class="flex flex-col justify-center items-center w-full gap-4">
        <p class="dark:text-white">Are you sure you want to add this teacher?</p>
        <div class="flex flex-col sm:flex-row gap-4 w-full">
            <button data-close="lmsmodal" class="px-4 flex-1 py-2 border dark:text-white dark:hover:text-black border-[#ABB7C2] rounded">Cancel</button>
            <button id="confirmAddTeacher" form="formaddteacher" class="px-4 py-2 flex-1 bg-[#5F2DED] hover:bg-[#4f23c6] text-white rounded">Confirm</button>
        </div>
    </div>
</div>
