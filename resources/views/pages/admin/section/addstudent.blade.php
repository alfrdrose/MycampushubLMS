@extends('layouts.updatesection-layout')

@section('content')
<script src="//unpkg.com/alpinejs" defer></script>

<div 
    x-data="{ 
        selected: [], 
        search: '' 
    }"
>
    <div class="flex flex-row mb-4"> 
        <div class="flex-1"> 
            <p class="font-medium text-2xl dark:text-white">Add Students</p>
            <p class="text-[#ABB7C2] dark:text-white">
                Choose students to add for section: 
                <span class="font-semibold">{{ $section->section_name }}</span>
            </p>
        </div>

        {{-- Search bar --}}
        <div class="flex-1 flex justify-end items-center">
            <input 
                type="text" 
                placeholder="Search student..." 
                x-model="search"
                class="w-[60%] max-w-sm border border-gray-300 dark:border-gray-600 rounded-lg px-4 py-2 focus:ring-2 focus:ring-[#5F2DED] dark:bg-darkblack dark:text-white"
            >
        </div>
    </div>

    {{-- Form --}}
    <form id="addStudentsForm" method="POST" action="{{ route('admin.section.addstudents', $section->id) }}">
        @csrf
        <input type="hidden" name="student_ids" :value="JSON.stringify(selected)">

        <div class="mt-6 overflow-hidden">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-gray-300 dark:border-gray-700">
                        <th class="py-2 px-3 w-12 text-center">
                            <input 
    type="checkbox"
    class="w-4 h-4 text-[#5F2DED] rounded focus:ring-[#5F2DED]"

    {{-- stays synced with selected[] --}}
    :checked="selected.length === {{ count($students) }}"

    {{-- toggles all --}}
    @click="selected.length === {{ count($students) }} 
        ? selected = [] 
        : selected = {{ json_encode($students->pluck('id')) }}"
>

                        </th>
                        <th class="py-2 px-3 dark:text-white">Student ID</th>
                        <th class="py-2 px-3 dark:text-white">Name</th>
                        <th class="py-2 px-3 dark:text-white">Email</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($students as $student)
                        <tr 
                            x-show="('{{ strtolower($student->first_name . ' ' . $student->last_name) }}'.includes(search.toLowerCase())) ||
                                    ('{{ strtolower($student->email) }}'.includes(search.toLowerCase())) ||
                                    ('{{ $student->id }}'.includes(search.toLowerCase()))"
                            :class="selected.includes({{ $student->id }}) 
                                ? 'bg-[#5F2DED]/20 border-l-4 border-[#5F2DED]' 
                                : 'hover:bg-gray-100 dark:hover:bg-gray-800 cursor-pointer'"
                            class="transition"
                            @click="selected.includes({{ $student->id }}) 
                                ? selected = selected.filter(id => id !== {{ $student->id }}) 
                                : selected.push({{ $student->id }})"
                        >
                            <td class="py-2 px-3 text-center">
                                <input type="checkbox" :checked="selected.includes({{ $student->id }})"
                                    class="w-4 h-4 text-[#5F2DED] rounded focus:ring-[#5F2DED]">
                            </td>
                            <td class="py-2 px-3 dark:text-white">{{ $student->id }}</td>
                            <td class="py-2 px-3 dark:text-white">{{ $student->first_name }} {{ $student->last_name }}</td>
                            <td class="py-2 px-3 dark:text-white">{{ $student->email }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="flex justify-end mt-6">
            <button 
                type="button"
                onclick="openConfirmAddStudentsModal()"
                class="bg-[#5F2DED] text-white px-6 py-2 rounded disabled:opacity-50"
                :disabled="selected.length === 0"
            >
                Add Selected Students
            </button>
        </div>
    </form>
</div>

{{-- Include the global modal --}}
<x-lmsmodal />

@endsection


@section('scripts')
<script>
    function openConfirmAddStudentsModal() {
        const selectedStudents = document.querySelector('input[name="student_ids"]').value;
        const parsed = JSON.parse(selectedStudents);

        if (parsed.length === 0) return;

        const modal = document.getElementById("lmsmodal");
        const title = document.getElementById("lmsmodaltitle");
        const content = document.getElementById("lmsmodalcontent");

        title.textContent = "Confirm Student Addition";
        content.innerHTML = `
            <p class="text-center dark:text-white mb-4">
                Are you sure you want to add <b>${parsed.length}</b> student(s) to this section?
            </p>
            <div class="flex gap-4 w-full">
                <button data-close="lmsmodal"
                    class="px-4 flex-1 py-2 border dark:text-white dark:hover:text-black border-[#ABB7C2] hover:bg-gray-100 rounded">
                    Cancel
                </button>
                <button id="confirmAddStudentsBtn"
                    class="px-4 py-2 flex-1 bg-[#5F2DED] hover:bg-[#4C23C7] text-white rounded">
                    Confirm
                </button>
            </div>
        `;

        modal.classList.remove("hidden");

        // Bind confirm button
        setTimeout(() => {
            const confirmBtn = document.getElementById("confirmAddStudentsBtn");
            if (confirmBtn) {
                confirmBtn.addEventListener("click", () => {
                    window.AdminModalLoading?.setLoading(confirmBtn, { text: "Adding..." });
                    document.getElementById("addStudentsForm").submit();
                });
            }
        }, 50);
    }
</script>
@endsection
