@extends('layouts.updatesection-layout')

@section('content')
<script src="//unpkg.com/alpinejs" defer></script>

<div 
    x-data="{ 
        selected: null, 
        search: '' 
    }"
    class="flex flex-col gap-4"
>
    <div class="flex flex-col md:flex-row mb-4 gap-4">
        <div class="flex-1">
            <p class="font-medium text-2xl dark:text-white">Add Adviser</p>
            <p class="text-[#ABB7C2] dark:text-gray-400 mt-[-1%] text-sm">
                Choose an adviser for section: 
                <span class="font-semibold">{{ $section->section_name }}</span>
            </p>
        </div>

        {{-- Search bar --}}
        <div class="flex-1 flex justify-end items-center">
            <input 
                type="text" 
                placeholder="Search teacher..." 
                x-model="search"
                class="w-full max-w-sm border border-gray-300 dark:border-gray-600 rounded-lg px-4 py-2 focus:ring-2 focus:ring-[#5F2DED] dark:bg-darkblack dark:text-white dark:placeholder-gray-400"
            >
        </div>
    </div>

    {{-- Form --}}
    <form id="assignAdviserForm" method="POST" action="{{ route('admin.section.assignadviser', $section->id) }}">
        @csrf
        <input type="hidden" name="teacher_id" :value="selected">

        <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-600">
            <table class="w-full text-left border-collapse table-fixed">
                <thead class="bg-gray-100 dark:bg-gray-800">
    <tr class="border-b border-gray-300 dark:border-gray-700">
        <th class="py-2 px-3 text-sm font-medium text-gray-700 dark:text-gray-200 w-1/5">
            Teacher ID
        </th>
        <th class="py-2 px-3 text-sm font-medium text-gray-700 dark:text-gray-200 w-2/5">
            Name
        </th>
        <th class="py-2 px-3 text-sm font-medium text-gray-700 dark:text-gray-200 w-2/5">
            Faculty
        </th>
    </tr>
</thead>

<tbody>
    @foreach ($teachers as $teacher)
        <tr 
            x-show="('{{ strtolower($teacher->first_name . ' ' . $teacher->last_name) }}'.includes(search.toLowerCase())) ||
                    ('{{ strtolower($teacher->faculty) }}'.includes(search.toLowerCase())) ||
                    ('{{ $teacher->id }}'.includes(search.toLowerCase()))"
            
            class="transition hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer border-l-4"
            :class="selected === {{ $teacher->id }} 
                ? 'bg-[#5F2DED]/20 border-[#5F2DED]' 
                : 'border-transparent'"
            
            @click="selected = {{ $teacher->id }}"
        >
            <td class="py-2 px-3 text-sm text-gray-800 dark:text-white truncate w-1/5">
                {{ $teacher->id }}
            </td>

            <td class="py-2 px-3 text-sm text-gray-800 dark:text-white truncate w-2/5">
                {{ $teacher->first_name }} {{ $teacher->last_name }}
            </td>

            <td class="py-2 px-3 text-sm text-gray-800 dark:text-white truncate w-2/5">
                {{ $teacher->faculty }}
            </td>
        </tr>
    @endforeach
</tbody>

            </table>
        </div>

        <div class="flex justify-end mt-6">
            <button 
                type="button"
                onclick="openConfirmAssignAdviserModal()"
                class="bg-[#5F2DED] text-white px-6 py-2 rounded disabled:opacity-50 disabled:cursor-not-allowed hover:bg-[#4C23C7] transition-all"
                :disabled="!selected"
            >
                Assign Adviser
            </button>
        </div>
    </form>
</div>

{{-- Include the global modal --}}
<x-lmsmodal />

@endsection

@section('scripts')
<script>
    function openConfirmAssignAdviserModal() {
        const selectedTeacher = document.querySelector('input[name="teacher_id"]').value;
        if (!selectedTeacher) return;

        const modal = document.getElementById("lmsmodal");
        const title = document.getElementById("lmsmodaltitle");
        const content = document.getElementById("lmsmodalcontent");

        title.textContent = "Confirm Adviser Assignment";
        content.innerHTML = `
            <p class="text-center dark:text-white mb-4">
                Are you sure you want to assign this teacher
                as the adviser for this section?
            </p>
            <div class="flex flex-col md:flex-row gap-4 w-full">
                <button data-close="lmsmodal"
                    class="px-4 flex-1 py-2 border dark:text-white dark:hover:text-white  border-[#ABB7C2] hover:bg-gray-100 dark:hover:bg-gray-700 rounded">
                    Cancel
                </button>
                <button id="confirmAssignAdviserBtn"
                    class="px-4 py-2 flex-1 bg-[#5F2DED] hover:bg-[#4C23C7] dark:hover:bg-[#5A23D4] text-white rounded">
                    Confirm
                </button>
            </div>
        `;

        modal.classList.remove("hidden");

        setTimeout(() => {
            const confirmBtn = document.getElementById("confirmAssignAdviserBtn");
            if (confirmBtn) {
                confirmBtn.addEventListener("click", () => {
                    window.AdminModalLoading?.setLoading(confirmBtn, { text: "Assigning..." });
                    document.getElementById("assignAdviserForm").submit();
                });
            }
        }, 50);
    }
</script>
@endsection
