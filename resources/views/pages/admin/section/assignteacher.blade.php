@extends('layouts.updatesection-layout')

@section('content')
<div class="flex flex-col gap-6">

    <!-- Header + Search -->
    <div class="flex flex-col lg:flex-row gap-4 items-start lg:items-center">
        <div class="flex-1">
            <p class="font-medium text-2xl dark:text-white">Assign Teacher</p>
            <p class="text-[#ABB7C2] dark:text-white">
                Select a teacher to assign for <strong>{{ $subject->subject_name }}</strong>
            </p>
        </div>

        <div class="w-full lg:w-1/2">
            <input 
                type="text" 
                id="teacherSearch" 
                placeholder="Search teacher by name or email..."
                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg 
                       focus:outline-none focus:ring-2 focus:ring-primaryColor 
                       dark:bg-gray-800 dark:text-white text-sm"
            >
        </div>
    </div>

    <!-- ================= DESKTOP TABLE ================= -->
    <div class="hidden md:block">
        <table class="min-w-full border-t border-gray-300">
            <thead>
                <tr>
                    <th class="p-2 text-left text-[#5E5F60] dark:text-white">Teacher Name</th>
                    <th class="p-2 text-left text-[#5E5F60] dark:text-white">Email</th>
                    <th class="p-2 text-left text-[#5E5F60] dark:text-white">Action</th>
                </tr>
            </thead>
            <tbody id="teacherTableBody">
                @foreach ($teachers as $teacher)
                    <tr class="border-b">
                        <td class="p-2 dark:text-white">
                            {{ $teacher->first_name }} {{ $teacher->last_name }}
                        </td>
                        <td class="p-2 dark:text-white">{{ $teacher->email }}</td>
                        <td class="p-2">
                            <button 
                                onclick="openAssignConfirmModal({{ $teacher->id }}, '{{ $teacher->first_name }} {{ $teacher->last_name }}')" 
                                class="text-white bg-primaryColor py-2 px-6 rounded hover:bg-[#5226cb] text-sm transition">
                                Assign
                            </button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="mt-4 flex justify-end" id="paginationWrapper">
            {{ $teachers->links() }}
        </div>
    </div>

    <!-- ================= MOBILE CARDS ================= -->
    <div class="md:hidden flex flex-col gap-4" id="teacherCardBody">
        @foreach ($teachers as $teacher)
            <div class="border border-gray-300 dark:border-gray-700 rounded-xl p-4 bg-white dark:bg-gray-800">
                <p class="font-semibold dark:text-white">
                    {{ $teacher->first_name }} {{ $teacher->last_name }}
                </p>
                <p class="text-sm text-gray-600 dark:text-gray-300 break-all">
                    {{ $teacher->email }}
                </p>

                <button
                    onclick="openAssignConfirmModal({{ $teacher->id }}, '{{ $teacher->first_name }} {{ $teacher->last_name }}')"
                    class="mt-3 w-full text-white bg-primaryColor py-2 rounded hover:bg-[#5226cb] text-sm transition">
                    Assign
                </button>
            </div>
        @endforeach
    </div>

</div>

<!-- ================= CONFIRM MODAL ================= -->
<div id="assignConfirmModal" class="fixed inset-0 bg-black bg-opacity-40 hidden items-center justify-center z-50">
    <div class="bg-white dark:bg-gray-800 p-6 sm:p-8 rounded-2xl shadow-lg w-full max-w-md mx-4 text-left">
        <h2 class="text-xl font-bold mb-2 text-[#5E5F60] dark:text-white">
            Confirm Assignment
        </h2>

        <p class="text-gray-600 dark:text-gray-300 mb-6 text-center break-words" id="assignConfirmMessage"></p>

        <div class="flex flex-col sm:flex-row gap-4">
            <button 
                onclick="closeAssignConfirmModal()" 
                class="w-full sm:w-1/2 border py-2 border-gray-400 rounded 
                       hover:bg-gray-100 dark:hover:bg-gray-700 dark:text-white">
                Cancel
            </button>

            <form id="assignForm" method="POST" action="" class="w-full sm:w-1/2">
                @csrf
                <button type="submit" 
                        class="w-full py-2 bg-primaryColor text-white rounded hover:bg-[#5226cb]">
                    Confirm
                </button>
            </form>
        </div>
    </div>
</div>

<script>
let selectedTeacherId = null;

function openAssignConfirmModal(teacherId, teacherName) {
    document.getElementById('assignConfirmMessage').innerText = 
        `Are you sure you want to assign ${teacherName} to this subject?`;

    const modal = document.getElementById('assignConfirmModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');

    const form = document.getElementById('assignForm');
    form.action = `/admin/sections/{{ $section->id }}/subjects/{{ $subject->id }}/teachers/${teacherId}/assign`;
}

function closeAssignConfirmModal() {
    const modal = document.getElementById('assignConfirmModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

// Live Search (desktop + mobile)
document.getElementById('teacherSearch').addEventListener('keyup', async function() {
    const search = this.value.trim();
    const tbody = document.getElementById('teacherTableBody');
    const cards = document.getElementById('teacherCardBody');

    if (tbody) {
        tbody.innerHTML = `<tr><td colspan="3" class="p-3 text-center text-gray-500">Searching...</td></tr>`;
    }
    if (cards) {
        cards.innerHTML = `<p class="text-center text-gray-500">Searching...</p>`;
    }

    try {
        const res = await fetch(`/admin/sections/{{ $section->id }}/subjects/{{ $subject->id }}/teachers/search?search=${encodeURIComponent(search)}`);
        const data = await res.json();

        if (data.length === 0) {
            if (tbody) tbody.innerHTML = `<tr><td colspan="3" class="p-3 text-center text-gray-500">No teachers found.</td></tr>`;
            if (cards) cards.innerHTML = `<p class="text-center text-gray-500">No teachers found.</p>`;
            return;
        }

        if (tbody) {
            tbody.innerHTML = data.map(t => `
                <tr>
                    <td class="p-2 dark:text-white">${t.first_name} ${t.last_name}</td>
                    <td class="p-2 dark:text-white">${t.email}</td>
                    <td class="p-2">
                        <button onclick="openAssignConfirmModal(${t.id}, '${t.first_name} ${t.last_name}')"
                            class="text-white bg-primaryColor py-2 px-6 rounded hover:bg-[#5226cb] text-sm">
                            Assign
                        </button>
                    </td>
                </tr>
            `).join('');
        }

        if (cards) {
            cards.innerHTML = data.map(t => `
                <div class="border border-gray-300 dark:border-gray-700 rounded-xl p-4 bg-white dark:bg-gray-800">
                    <p class="font-semibold dark:text-white">${t.first_name} ${t.last_name}</p>
                    <p class="text-sm text-gray-600 dark:text-gray-300">${t.email}</p>
                    <button onclick="openAssignConfirmModal(${t.id}, '${t.first_name} ${t.last_name}')"
                        class="mt-3 w-full text-white bg-primaryColor py-2 rounded hover:bg-[#5226cb] text-sm">
                        Assign
                    </button>
                </div>
            `).join('');
        }

    } catch {
        if (tbody) tbody.innerHTML = `<tr><td colspan="3" class="p-3 text-center text-red-500">Error loading results</td></tr>`;
        if (cards) cards.innerHTML = `<p class="text-center text-red-500">Error loading results</p>`;
    }
});
</script>
@endsection
