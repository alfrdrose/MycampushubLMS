@extends('layouts.updatesection-layout')

@section('content')
    <p class="font-medium text-2xl dark:text-white">Adviser</p>
    <p class="text-[#ABB7C2] dark:text-gray-400  text-sm">Class Section Assigned Adviser</p>

    {{-- Adviser details --}}
    <div class="pt-4 flex flex-col gap-4">
        @if ($adviser)
            {{-- First name --}}
            <div class="flex flex-col gap-2">
                <p class="text-[#373737] font-medium dark:text-white">First Name</p>
                <input value="{{ $adviser->first_name }}" disabled
                    class="border border-[#ABB7C2] rounded p-4 dark:bg-whiteColor-dark dark:text-white dark:border-gray-600">
            </div>

            {{-- Last name --}}
            <div class="flex flex-col gap-2">
                <p class="text-[#373737] font-medium dark:text-white">Last Name</p>
                <input value="{{ $adviser->last_name }}" disabled
                    class="border border-[#ABB7C2] rounded p-4 dark:bg-whiteColor-dark dark:text-white dark:border-gray-600">
            </div>

            {{-- Faculty & Status --}}
            <div class="flex flex-col md:flex-row gap-4 mt-4">
                <div class="flex flex-col flex-1 gap-2">
                    <p class="text-[#373737] font-medium dark:text-white">Faculty</p>
                    <input value="{{ $adviser->faculty }}" disabled
                        class="border border-[#ABB7C2] rounded p-4 dark:bg-whiteColor-dark dark:text-white dark:border-gray-600">
                </div>
            </div>
        @else
            <p class="text-gray-500 dark:text-gray-400 mt-4">No assigned adviser yet.</p>
        @endif

        {{-- Buttons --}}
        <div class="flex flex-col md:flex-row justify-end mt-10 gap-3">
            @if ($adviser)
                <button 
                    onclick="openRemoveAdviserModal()"
                    class="bg-red-400 text-white font-medium py-2 px-8 rounded hover:bg-red-700 dark:hover:bg-red-600 transition-all">
                    Remove Adviser
                </button>
            @endif
        
            {{-- Add/Change Adviser --}}
            <a class="border-[#5F2DED] text-center border text-[#5F2DED] font-medium py-2 px-8 rounded
                hover:text-white hover:bg-[#5F2DED] dark:hover:bg-[#5F2DED] dark:text-[#5F2DED] transition-all"
                href="{{ route('admin.section.addadviser', ['id' => $section->id]) }}">
                {{ $adviser ? 'Change Adviser' : 'Add Adviser' }}
            </a>
        </div>
    </div>

<script>
    const removeAdviserRoute = "{{ route('admin.section.removeadviser', ['id' => $section->id]) }}";

    function openRemoveAdviserModal() {
        const modal = document.getElementById("lmsmodal");
        if (!modal) return console.error("Modal not found!");

        document.getElementById("lmsmodaltitle").textContent = "Remove Adviser";

        document.getElementById("lmsmodalcontent").innerHTML = `
            <p class='dark:text-white text-center mb-4'>
                Are you sure you want to remove this adviser?
            </p>
            <div class='flex flex-col md:flex-row gap-4 w-full'>
                <button data-close='lmsmodal' 
                    class='px-4 flex-1 py-2 border dark:text-white dark:hover:text-black border-[#ABB7C2] hover:bg-gray-100 dark:hover:bg-gray-700 rounded'>
                    Cancel
                </button>

                <form method='POST' action='${removeAdviserRoute}' class='flex-1'>
                    <input type='hidden' name='_token' value='${document.querySelector('meta[name=csrf-token]').content}'>
                    <input type='hidden' name='_method' value='DELETE'>
                    <button type='submit' class='px-4 py-2 w-full bg-red-500 hover:bg-red-400 dark:hover:bg-red-600 text-white rounded'>
                        Confirm
                    </button>
                </form>
            </div>
        `;

        modal.classList.remove("hidden");
    }
</script>
@endsection
