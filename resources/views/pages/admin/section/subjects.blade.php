@extends('layouts.updatesection-layout')

@section('content')

<!-- Page Header -->
<div class="mb-4">
    <p class="font-medium text-xl sm:text-2xl dark:text-white">Subjects</p>
    <p class="text-[#ABB7C2] dark:text-white text-sm">
        List of Assigned Subjects
    </p>
</div>

{{-- ================= DESKTOP TABLE ================= --}}
<div class="hidden md:block">
    @if($subjects->count() > 0)
        <table class="min-w-full border-t border-gray-300 dark:border-gray-700">
            <thead>
                <tr>
                    <th class="p-3 text-left dark:text-white font-semibold">Subject</th>
                    <th class="p-3 text-left dark:text-white font-semibold">Teacher</th>
                    <th class="p-3 text-left dark:text-white font-semibold">Actions</th>
                </tr>
            </thead>

            <tbody>
                @foreach ($subjects as $subject)
                    @php
                        $hasTeachers = $subject->teachers->isNotEmpty();
                        $disableRemove = $hasTeachers;
                    @endphp

                    <tr class="odd:bg-[#F0F9FF] even:bg-white dark:odd:bg-[#161a40] dark:even:bg-bodyBg-dark">
                        <td class="p-3 dark:text-white">{{ $subject->subject_name }}</td>

                        <td class="p-3 dark:text-white">
                            @if($hasTeachers)
                                @foreach($subject->teachers as $teacher)
                                    <p>{{ $teacher->first_name }} {{ $teacher->last_name }}</p>
                                @endforeach
                            @else
                                <span class="italic text-gray-400">No teacher</span>
                            @endif
                        </td>

                        <td class="p-3">
                            <div x-data="{ open:false }" class="relative">
                                <i @click="open = !open"
                                   class="icofont-options cursor-pointer px-3 py-1 rounded
                                          text-[#5E5F60] dark:text-white
                                          hover:bg-gray-200 dark:hover:bg-gray-700"></i>

                                <div x-show="open" @click.outside="open = false"
                                     class="absolute mt-2 w-48 right-0 bg-white dark:bg-gray-800
                                            shadow-lg rounded border dark:border-gray-600 z-50">

                                    <button
                                        onclick="openRemoveSubjectModal({{ $subject->id }}, '{{ $subject->subject_name }}')"
                                        class="flex w-full px-3 py-2 text-left text-sm
                                        {{ $disableRemove
                                            ? 'opacity-50 text-gray-500 cursor-not-allowed'
                                            : 'hover:bg-red-500 hover:text-white text-red-500' }}"
                                        {{ $disableRemove ? 'disabled' : '' }}>
                                        Remove Subject
                                    </button>

                                    @if($hasTeachers)
                                        @foreach ($subject->teachers as $teacher)
                                            <button
                                                onclick="openRemoveTeacherModal(
                                                    {{ $subject->id }},
                                                    {{ $teacher->id }},
                                                    '{{ $teacher->first_name }} {{ $teacher->last_name }}'
                                                )"
                                                class="flex w-full px-3 py-2 text-left text-sm
                                                       hover:bg-red-500 hover:text-white text-red-500">
                                                Remove Teacher
                                            </button>
                                        @endforeach
                                    @else
                                        <a
                                            href="{{ route('admin.section.assignteacher', [
                                                'section_id' => $section->id,
                                                'subject_id' => $subject->id
                                            ]) }}"
                                            class="flex w-full px-3 py-2 text-left text-sm
                                                   hover:bg-green-500 hover:text-white text-green-600">
                                            Assign Teacher
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>

{{-- ================= MOBILE CARDS ================= --}}
<div class="md:hidden flex flex-col gap-4">
    @forelse ($subjects as $subject)
        @php
            $hasTeachers = $subject->teachers->isNotEmpty();
            $disableRemove = $hasTeachers;
        @endphp

        <div class="border border-gray-300 dark:border-gray-700
                    rounded-xl p-4 bg-white dark:bg-bodyBg-dark shadow-sm">

            <div class="flex justify-between items-start">
                <p class="font-semibold dark:text-white">
                    {{ $subject->subject_name }}
                </p>

                <div x-data="{ open:false }" class="relative">
                    <i @click="open = !open"
                       class="icofont-options cursor-pointer px-2 py-1 rounded
                              text-[#5E5F60] dark:text-white
                              hover:bg-gray-200 dark:hover:bg-gray-700"></i>

                    <div x-show="open" @click.outside="open = false"
                         class="absolute mt-2 w-44 right-0 bg-white dark:bg-gray-800
                                shadow-lg rounded border dark:border-gray-600 z-50">

                        <button
                            onclick="openRemoveSubjectModal({{ $subject->id }}, '{{ $subject->subject_name }}')"
                            class="flex w-full px-3 py-2 text-left text-sm
                            {{ $disableRemove
                                ? 'opacity-50 text-gray-500 cursor-not-allowed'
                                : 'hover:bg-red-500 hover:text-white text-red-500' }}"
                            {{ $disableRemove ? 'disabled' : '' }}>
                            Remove Subject
                        </button>

                        @if($hasTeachers)
                            @foreach ($subject->teachers as $teacher)
                                <button
                                    onclick="openRemoveTeacherModal(
                                        {{ $subject->id }},
                                        {{ $teacher->id }},
                                        '{{ $teacher->first_name }} {{ $teacher->last_name }}'
                                    )"
                                    class="flex w-full px-3 py-2 text-left text-sm
                                           hover:bg-red-500 hover:text-white text-red-500">
                                    Remove Teacher
                                </button>
                            @endforeach
                        @else
                            <a
                                href="{{ route('admin.section.assignteacher', [
                                    'section_id' => $section->id,
                                    'subject_id' => $subject->id
                                ]) }}"
                                class="flex w-full px-3 py-2 text-left text-sm
                                       hover:bg-green-500 hover:text-white text-green-600">
                                Assign Teacher
                            </a>
                        @endif
                    </div>
                </div>
            </div>

            <div class="mt-2 text-sm">
                <p class="font-medium dark:text-white">Teacher(s):</p>
                @if($hasTeachers)
                    @foreach($subject->teachers as $teacher)
                        <p class="text-gray-600 dark:text-gray-300">
                            {{ $teacher->first_name }} {{ $teacher->last_name }}
                        </p>
                    @endforeach
                @else
                    <p class="italic text-gray-400">No teacher assigned</p>
                @endif
            </div>
        </div>
    @empty
        <p class="text-center text-gray-500 dark:text-gray-400">
            No subjects assigned.
        </p>
    @endforelse
</div>

{{-- ================= ADD BUTTON ================= --}}
<div class="flex justify-end mt-6">
    <a href="{{ route('admin.section.addsubject', $section->id) }}"
       class="w-full sm:w-auto text-center
              bg-[#5F2DED] text-white px-6 py-3 rounded-lg
              hover:bg-[#4C23C7] transition shadow">
        + Add Subject
    </a>
</div>

{{-- ================= GLOBAL MODAL ================= --}}
<div id="lmsmodal"
     class="fixed inset-0 bg-black bg-opacity-40 hidden items-center justify-center z-50">
    <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-lg w-full max-w-md">
        <h2 id="lmsmodaltitle"
            class="text-xl font-semibold mb-4 dark:text-white">
        </h2>
        <div id="lmsmodalcontent"></div>
    </div>
</div>

{{-- ================= SCRIPTS ================= --}}
<script>
function openRemoveSubjectModal(subjectId, subjectName) {
    const modal = document.getElementById("lmsmodal");

    document.getElementById("lmsmodaltitle").textContent = "Remove Subject";
    document.getElementById("lmsmodalcontent").innerHTML = `
        <p class="dark:text-white text-center mb-4">
            Remove <strong>${subjectName}</strong> from this section?
        </p>

        <div class="flex gap-4">
            <button onclick="closeModal()"
                class="flex-1 border px-4 py-2 rounded dark:text-white">
                Cancel
            </button>

            <form method="POST"
                  action="/admin/sections/{{ $section->id }}/subjects/${subjectId}/remove"
                  class="flex-1">
                @csrf
                @method('DELETE')
                <button type="submit"
                        class="w-full bg-red-500 hover:bg-red-400 text-white px-4 py-2 rounded">
                    Confirm
                </button>
            </form>
        </div>
    `;

    modal.classList.remove("hidden");
    modal.classList.add("flex");
}

function openRemoveTeacherModal(subjectId, teacherId, teacherName) {
    const modal = document.getElementById("lmsmodal");

    document.getElementById("lmsmodaltitle").textContent = "Remove Teacher";
    document.getElementById("lmsmodalcontent").innerHTML = `
        <p class="dark:text-white text-center mb-4">
            Unassign <strong>${teacherName}</strong>?
        </p>

        <div class="flex gap-4">
            <button onclick="closeModal()"
                class="flex-1 border px-4 py-2 rounded dark:text-white">
                Cancel
            </button>

            <form method="POST"
                  action="/admin/sections/{{ $section->id }}/subjects/${subjectId}/teachers/${teacherId}/remove"
                  class="flex-1">
                @csrf
                @method('DELETE')
                <button type="submit"
                        class="w-full bg-red-500 hover:bg-red-400 text-white px-4 py-2 rounded">
                    Confirm
                </button>
            </form>
        </div>
    `;

    modal.classList.remove("hidden");
    modal.classList.add("flex");
}

function closeModal() {
    const modal = document.getElementById("lmsmodal");
    modal.classList.add("hidden");
    modal.classList.remove("flex");
}
</script>

@endsection
