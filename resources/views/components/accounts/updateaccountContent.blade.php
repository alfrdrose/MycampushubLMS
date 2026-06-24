<div class="flex-1 flex flex-col p-6 sm:p-10 h-auto bg-whiteColor shadow-lg dark:bg-darkblack rounded-2xl transition-all
        duration-300 dark:ring-2 dark:ring-blue-500/20 dark:ring-offset-0">

    <div id="page-header" class="mb-4">
        <h1 id="page-title" class="text-2xl sm:text-3xl font-bold text-[#5E5F60] dark:text-white">Update Account</h1>
        <p id="page-subtitle" class="text-[#ABB7C2] text-sm sm:text-base">Kindly fill out the form to update the account.</p>
    </div>

    <form id="formupdateaccount" class="space-y-6" action="{{ route('admin.updateaccount', $user->id) }}" method="POST">
        @csrf

        {{-- Username & Email --}}
        <div class="flex flex-col sm:flex-row gap-6">
            <div class="flex-1 flex flex-col gap-1">
                <label class="text-[#373737] font-medium dark:text-white">Username</label>
                <input
                    value="{{ old('name', $user->name ?? '') }}"
                    type="text"
                    id="txtupdateUsername"
                    name="name"
                    class="p-3 border rounded-md text-base w-full
                        dark:bg-whiteColor-dark dark:text-white
                        @error('name') border-red-500 @else border-[#ABB7C2] @enderror"
                    placeholder="Enter username">

                @error('name')
                    <p class="text-red-500 text-sm">
                        {{ $message }}
                    </p>
                @enderror

                <p id="errorUpdateUsername" class="text-red-400 text-sm hidden">Username is required.</p>
            </div>

            <div class="flex-1 flex flex-col gap-1">
                <label class="text-[#373737] font-medium dark:text-white">
                    Email
                </label>

                <input
                    value="{{ old('email', $user->email ?? '') }}"
                    type="text"
                    id="txtupdateEmail"
                    name="email"
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

        <input type="hidden" name="user_id" id="user_id" value="{{ $user->id ?? '' }}">

        {{-- Password & Confirm Password --}}
        <div class="flex flex-col sm:flex-row gap-6">
            <div class="flex-1 flex flex-col gap-1 relative">
                <label class="text-[#373737] font-medium dark:text-white">Password</label>
                <div class="relative flex items-center">
                    <input type="password" id="txtupdatePassword" name="password"
                        class="p-3 pr-10 dark:placeholder-gray-300 border border-[#ABB7C2] rounded-md text-base dark:bg-whiteColor-dark dark:text-white w-full"
                        placeholder="Enter password">
                    <i id="toggleUpdatePassword"
                        class="icofont-eye-blocked  absolute right-3 text-[#5E5F60] cursor-pointer"></i>
                </div>
                <p id="errorUpdatePassword" class="text-red-400 text-sm hidden">Password error</p>
            </div>


            <div class="flex-1 flex flex-col gap-1 relative">
                <label class="text-[#373737] font-medium dark:text-white">Confirm Password</label>
                <div class="relative flex items-center">
                    <input type="password" id="txtupdateConfirmPass" name="password_confirmation"
                        class="p-3 border dark:placeholder-gray-300 border-[#ABB7C2] rounded-md text-base dark:bg-whiteColor-dark dark:text-white w-full pr-10"
                        placeholder="Confirm password">
                    <i id="toggleUpdatePassword2"
                        class="icofont-eye-blocked absolute right-3 text-[#5E5F60] cursor-pointer"></i>
                    
                </div>
                <p id="errorUpdateConfirmPass" class="text-red-400 text-sm hidden">Confirm password error</p>
            </div>
        </div>

        {{-- Account Type
        <div class="flex flex-col gap-1">
            <label for="account_type" class="text-[#373737] font-medium dark:text-white">Account Type</label>
            <select id="updateType" name="role"
                class="p-3 border cursor-pointer border-[#ABB7C2] rounded-md text-base dark:bg-whiteColor-dark dark:text-white w-full">
                <option value="" disabled {{ !isset($user) ? 'selected' : '' }}>Select role</option>
                <option value="admin" {{ (isset($user) && $user->role === 'admin') ? 'selected' : '' }}>Admin</option>
                <option value="staff" {{ (isset($user) && $user->role === 'staff') ? 'selected' : '' }}>Staff</option>
                <option value="teacher" {{ (isset($user) && $user->role === 'teacher') ? 'selected' : '' }}>Teacher</option>
                <option value="student" {{ (isset($user) && $user->role === 'student') ? 'selected' : '' }}>Student</option>
            </select>
            <p id="errorUpdateType" class="text-red-400 text-sm hidden">Account type is required.</p>
        </div> --}}

        {{-- Status --}}
        <div class="flex flex-col gap-1">
            <label for="status" class="text-[#373737] font-medium dark:text-white">Status</label>
            <select id="status" name="status"
                class="p-3 border cursor-pointer border-[#ABB7C2] rounded-md text-base dark:bg-whiteColor-dark dark:text-white w-full">
                <option value="" disabled {{ !isset($user) ? 'selected' : '' }}>Select status</option>
                <option value="active" {{ (isset($user) && $user->status === 'active') ? 'selected' : '' }}>Active</option>
                <option value="inactive" {{ (isset($user) && $user->status === 'inactive') ? 'selected' : '' }}>Inactive</option>
            </select>
            <p id="errorstatus" class="text-red-400 text-sm hidden">Status is required.</p>
        </div>
        <div class="flex flex-row w-full">
            {{-- Back link --}}
            <div class="w-1/2 flex justify-start"> 
                <a href="{{ route('admin.accounts') }}"
                    class="inline-flex items-center px-10 py-2 border border-primaryColor text-primaryColor font-medium
                    rounded-lg hover:bg-[#4d1db8] hover:text-white transition ">
                    Back
                </a>
            </div>
            {{-- Buttons --}}
            <div class="w-1/2 flex justify-end gap-2">
                <button type="button" id="btnUpdateAccountClear"
                    class="flex-1 border border-[#ABB7C2] text-[#5E5F60] font-medium dark:text-[#ABB7C2] rounded-md 
                        transition-colors duration-200 ease-in-out hover:bg-red-400 hover:border-0 dark:hover:text-white hover:text-white">
                    Clear
                </button>

                <button type="button" id="openUpdateAccountModal" data-title="Update Account" data-open="lmsmodal"
                    data-content="confirmUpdateModal"
                    class="flex-1 p-3 bg-[#5F2DED] text-white font-medium text-base rounded-md transition-colors duration-200 ease-in-out hover:bg-[#4e26c6]">
                    Update account
                </button>
            </div>

            
        </div>
        
    </form>
</div>

<x-lmsmodal />

<div id="confirmUpdateModal" class="hidden">
    <div class="flex justify-center items-center flex-col w-full">
        <p class="dark:text-white">Are you sure you want to update this account?</p>
        <div class="flex flex-col sm:flex-row gap-4 mt-4 w-full">
            <button data-close="lmsmodal"
                class="px-4 py-2 flex-1 border dark:text-white  border-[#ABB7C2] hover:bg-gray-100 dark:hover:bg-gray-500 dark:hover:text-white rounded">Cancel</button>
            <button id="confirmUpdate" form="formupdateaccount"
                class="px-4 py-2 flex-1 bg-[#5F2DED] hover:bg-[#4f23c6] text-white rounded">Confirm</button>
        </div>
    </div>
</div>
