@extends('pages.teacher.layout')

@section('content')
<form
    method="POST"
    action="{{ route('teacher.profile.update') }}"
    enctype="multipart/form-data"
    id="profileForm"
>
    @csrf
    <input type="hidden" name="remove_profile_image" id="removeProfileImage" value="0">

<div class="bg-white dark:bg-[#1E1E2D] dark:border shadow-lg dark:border-[#5F2DED] p-6 sm:p-8 rounded-lg max-w-4xl mx-auto">

    {{-- Header --}}
    <div class="border-b border-[#D4D4D8] dark:border-gray-600 mb-6">
        <h1 class="text-xl sm:text-2xl font-bold mb-4 text-[#5E5F60] dark:text-white">
            Edit Profile
        </h1>
    </div>

    <div class="flex flex-col gap-10">

        {{-- ===================== --}}
        {{-- General --}}
        {{-- ===================== --}}
        <div>
            <h2 class="text-lg font-semibold mb-4 text-[#5E5F60] dark:text-white">
                General
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm mb-2 text-gray-600 dark:text-gray-300">
                        First Name
                    </label>
                    <input disabled value="{{ $teacher->first_name }}"
                        class="w-full px-4 py-2 rounded-md bg-gray-100 dark:bg-gray-700 border dark:border-gray-600">
                </div>

                <div>
                    <label class="block text-sm mb-2 text-gray-600 dark:text-gray-300">
                        Last Name
                    </label>
                    <input disabled value="{{ $teacher->last_name }}"
                        class="w-full px-4 py-2 rounded-md bg-gray-100 dark:bg-gray-700 border dark:border-gray-600">
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-sm mb-2 text-gray-600 dark:text-gray-300">
                        Email Address
                    </label>
                    <input id="emailInput" name="email" type="email"
                        value="{{ $teacher->user->email }}"
                        class="w-full px-4 py-2 rounded-md border dark:border-gray-600 dark:bg-gray-800 focus:ring-2 focus:ring-[#5F2DED]">
                    <p id="emailError" class="text-sm text-red-500 mt-1 hidden"></p>
                </div>
            </div>
        </div>

        {{-- ===================== --}}
        {{-- Profile Picture --}}
        {{-- ===================== --}}
        <div>
            <h2 class="text-lg font-semibold mb-4 text-[#5E5F60] dark:text-white">
                Profile Picture
            </h2>

            <div class="flex flex-col sm:flex-row items-center gap-6">
                <img
                    id="profilePreview"
                    src="{{ $teacher->profileImageUrl() }}"
                    data-original="{{ $teacher->profileImageUrl() }}"
                    class="w-24 h-24 rounded-full border-2 border-[#5F2DED] object-cover"
                >

                <div class="flex flex-col gap-2">
                    <input
                        id="profileImageInput"
                        name="profile_image"
                        type="file"
                        accept="image/*"
                        class="block text-sm file:bg-[#5F2DED] file:text-white
                               file:rounded-md file:px-4 file:py-2 file:border-0"
                    >

                    <p class="text-xs text-gray-400">
                        JPG or PNG. Max size 2MB
                    </p>
                </div>
            </div>

            <button
                type="button"
                id="removeImageBtn"
                class="mt-2 text-sm text-red-500 hover:underline hidden"
            >
                Remove photo
            </button>
        </div>

        {{-- ===================== --}}
        {{-- Security --}}
        {{-- ===================== --}}
        <div>
            <h2 class="text-lg font-semibold mb-4 text-[#5E5F60] dark:text-white">
                Security
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm mb-2 text-gray-600 dark:text-gray-300">
                        New Password
                    </label>
                    <div class="relative">
                        <input id="newPassword" type="password" name="password"
                            class="w-full px-4 py-2 pr-12 rounded-md border dark:border-gray-600 dark:bg-gray-800">
                        <button type="button"
                            class="toggle-password absolute right-3 top-1/2 -translate-y-1/2"
                            data-target="newPassword">
                            <i class="icofont-eye-blocked text-gray-500"></i>
                        </button>
                    </div>
                    <p id="passwordLengthError" class="text-sm text-red-500 mt-1 hidden"></p>
                </div>

                <div>
                    <label class="block text-sm mb-2 text-gray-600 dark:text-gray-300">
                        Confirm New Password
                    </label>
                    <div class="relative">
                        <input id="confirmPassword" type="password" name="password_confirmation"
                            class="w-full px-4 py-2 pr-12 rounded-md border dark:border-gray-600 dark:bg-gray-800">
                        <button type="button"
                            class="toggle-password absolute right-3 top-1/2 -translate-y-1/2"
                            data-target="confirmPassword">
                            <i class="icofont-eye-blocked text-gray-500"></i>
                        </button>
                    </div>
                    <p id="passwordMatchError" class="text-sm text-red-500 mt-1 hidden"></p>
                </div>

                <p class="sm:col-span-2 text-xs text-gray-400">
                    Password must be at least 8 characters. Leave blank to keep current password.
                </p>
            </div>
        </div>

        {{-- ===================== --}}
        {{-- Actions --}}
        {{-- ===================== --}}
        <div class="flex justify-end gap-3 border-t pt-4">
            <button
                type="button"
                id="openConfirmModal"
                disabled
                class="px-6 py-2 rounded-md bg-[#5F2DED] text-white opacity-50 cursor-not-allowed"
            >
                Save Changes
            </button>
        </div>

    </div>
</div>
</form>

{{-- ===================== --}}
{{-- Confirm Modal --}}
{{-- ===================== --}}
<div id="confirmModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
    <div class="bg-white dark:bg-[#1E1E2D] p-6 rounded-lg max-w-md w-full">
        <h3 class="text-lg font-semibold mb-4 dark:text-white">Confirm Changes</h3>
        <div class="flex justify-end gap-3">
            <button id="cancelModal" type="button" class="px-4 py-2 border rounded">
                Cancel
            </button>
            <button id="confirmSave" type="button" class="px-5 py-2 bg-[#5F2DED] text-white rounded">
                Save
            </button>
        </div>
    </div>
</div>

{{-- ===================== --}}
{{-- File Error Modal --}}
{{-- ===================== --}}
<div id="fileErrorModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
    <div class="bg-white dark:bg-[#1E1E2D] p-6 rounded-lg max-w-sm w-full">
        <h3 class="text-lg font-semibold mb-2 text-red-500">Invalid File</h3>
        <p id="fileErrorMessage" class="text-sm text-gray-600 dark:text-gray-300 mb-4"></p>
        <div class="flex justify-end">
            <button
                id="closeFileErrorModal"
                class="px-4 py-2 bg-[#5F2DED] text-white rounded"
            >
                OK
            </button>
        </div>
    </div>
</div>

{{-- ===================== --}}
{{-- Scripts --}}
{{-- ===================== --}}
<script>
document.addEventListener('DOMContentLoaded', () => {
    const emailInput = document.getElementById('emailInput');
    const emailError = document.getElementById('emailError');

    const newPassword = document.getElementById('newPassword');
    const confirmPassword = document.getElementById('confirmPassword');
    const lengthError = document.getElementById('passwordLengthError');
    const matchError = document.getElementById('passwordMatchError');

    const imageInput = document.getElementById('profileImageInput');
    const preview = document.getElementById('profilePreview');
    const removeBtn = document.getElementById('removeImageBtn');
    const removeFlag = document.getElementById('removeProfileImage');
    const originalImage = preview.dataset.original;

    const saveBtn = document.getElementById('openConfirmModal');
    const originalEmail = emailInput.value;

    const fileErrorModal = document.getElementById('fileErrorModal');
    const fileErrorMessage = document.getElementById('fileErrorMessage');
    const closeFileErrorModal = document.getElementById('closeFileErrorModal');

    function showFileError(message) {
        fileErrorMessage.textContent = message;
        fileErrorModal.classList.replace('hidden', 'flex');
    }

    closeFileErrorModal.onclick = () =>
        fileErrorModal.classList.replace('flex', 'hidden');

    function validate() {
        let valid = true;
        let changed = false;

        if (!emailInput.value.trim()) {
            emailError.textContent = 'Email is required.';
            emailError.classList.remove('hidden');
            valid = false;
        } else {
            emailError.classList.add('hidden');
        }

        if (emailInput.value !== originalEmail) {
            changed = true;
        }

        lengthError.classList.add('hidden');
        matchError.classList.add('hidden');

        if (newPassword.value || confirmPassword.value) {
            if (newPassword.value.length < 8) {
                lengthError.textContent = 'Password must be at least 8 characters.';
                lengthError.classList.remove('hidden');
                valid = false;
            } else if (newPassword.value !== confirmPassword.value) {
                matchError.textContent = 'Passwords do not match.';
                matchError.classList.remove('hidden');
                valid = false;
            } else {
                changed = true;
            }
        }

        if (imageInput.files.length > 0 || removeFlag.value === '1') {
            changed = true;
        }

        saveBtn.disabled = !(valid && changed);
        saveBtn.classList.toggle('opacity-50', !(valid && changed));
        saveBtn.classList.toggle('cursor-not-allowed', !(valid && changed));
    }

    imageInput.addEventListener('change', () => {
        const file = imageInput.files[0];
        if (!file) return;

        if (!file.type.startsWith('image/')) {
            showFileError('Only image files are allowed.');
            imageInput.value = '';
            return;
        }

        if (file.size > 2 * 1024 * 1024) {
            showFileError('Image size must not exceed 2MB.');
            imageInput.value = '';
            return;
        }

        const reader = new FileReader();
        reader.onload = e => {
            preview.src = e.target.result;
            removeBtn.classList.remove('hidden');
            removeFlag.value = '0';
            validate();
        };
        reader.readAsDataURL(file);
    });

    removeBtn.onclick = () => {
        preview.src = originalImage;
        imageInput.value = '';
        removeBtn.classList.add('hidden');
        removeFlag.value = '1';
        validate();
    };

    document.querySelectorAll('input').forEach(i => i.addEventListener('input', validate));

    openConfirmModal.onclick = () => confirmModal.classList.replace('hidden', 'flex');
    cancelModal.onclick = () => confirmModal.classList.replace('flex', 'hidden');
    confirmSave.onclick = () => profileForm.submit();

    validate();
});
</script>
@endsection
