@extends('layouts.updatesection-layout')

@section('content')
<p class="font-medium text-2xl dark:text-white">Students</p>
<p class="text-[#ABB7C2] dark:text-gray-400  text-sm">List of Enrolled Students</p>

{{-- Mobile Cards --}}
<div class="mt-4 grid grid-cols-1 gap-4 block md:hidden">
    @if($students->count() > 0)
        @foreach($students as $student)
            <div class="bg-white dark:bg-whiteColor-dark border border-gray-200 dark:border-gray-600 rounded-lg shadow p-4 flex flex-col justify-between">
                <div class="mb-4">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Student ID</p>
                    <p class="font-medium text-gray-800 dark:text-white">{{ $loop->iteration }}</p>

                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">First Name</p>
                    <p class="font-medium text-gray-800 dark:text-white">{{ $student->first_name }}</p>

                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">Last Name</p>
                    <p class="font-medium text-gray-800 dark:text-white">{{ $student->last_name }}</p>

                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">Grade Level</p>
                    <p class="font-medium text-gray-800 dark:text-white">{{ $student->grade }}</p>
                </div>

                <div class="flex flex-col gap-2">
                    <button 
                        onclick="openRemoveStudentModal({{ $student->id }}, '{{ $student->first_name }} {{ $student->last_name }}')"
                        class="bg-white dark:bg-whiteColor-dark border border-red-500 text-red-500 py-1 px-4 rounded hover:bg-red-500 dark:hover:bg-red-500 hover:text-white transition-all w-full">
                        Remove
                    </button>
                </div>
            </div>
        @endforeach
    @else
        <p class="dark:text-white col-span-full">No students found for this section.</p>
    @endif

    {{-- Add Student button inside mobile cards section --}}
    <a href="{{ route('admin.section.addstudent', ['id' => $section->id]) }}"
       class="border-[#5F2DED] text-center border text-[#5F2DED] font-medium py-2 px-6 rounded
              hover:text-white hover:bg-[#5F2DED] transition-all text-sm mt-2">
        Add Student
    </a>
</div>

{{-- Desktop / Tablet Table --}}
<div class="relative min-h-full pt-4 hidden md:block">
    @if($students->count() > 0)
        <table class="min-w-full border-t border-gray-300">
            <thead>
                <tr>
                    <th class="p-2 text-left dark:text-white text-[#5E5F60] font-semibold">Student ID</th>
                    <th class="p-2 text-left dark:text-white text-[#5E5F60] font-semibold">First Name</th>
                    <th class="p-2 text-left dark:text-white text-[#5E5F60] font-semibold">Last Name</th>
                    <th class="p-2 text-left dark:text-white text-[#5E5F60] font-semibold">Grade Level</th>
                    <th class="p-2 text-left dark:text-white text-[#5E5F60] font-semibold">Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($students as $student)
                    <tr>
                        <td class="p-2 dark:text-white">{{ $loop->iteration }}</td>
                        <td class="p-2 dark:text-white">{{ $student->first_name }}</td>
                        <td class="p-2 dark:text-white">{{ $student->last_name }}</td>
                        <td class="p-2 dark:text-white">{{ $student->grade }}</td>
                        <td class="p-2 text-left">
                            <button 
                                onclick="openRemoveStudentModal({{ $student->id }}, '{{ $student->first_name }} {{ $student->last_name }}')"
                                class="bg-white border dark:bg-whiteColor-dark border-red-500 text-red-500 py-1 px-4 rounded hover:bg-red-500 dark:hover:bg-red-500 hover:text-white transition-all">
                                Remove
                            </button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="mt-4">
            {{ $students->links() }}
        </div>

        
    @else
        <p class="dark:text-white">No students found for this section.</p>
    @endif
    {{-- Add Student button for desktop --}}
        <div class="mt-6 flex justify-end w-full ">
            <a href="{{ route('admin.section.addstudent', ['id' => $section->id]) }}"
               class="border-[#5F2DED] w-[20%] text-center border text-[#5F2DED] font-medium py-2 px-6 rounded 
                      hover:text-white hover:bg-[#5F2DED] transition-all text-sm md:text-base">
                Add Student
            </a>
        </div>
</div>

<script>
function openRemoveStudentModal(studentId, studentName) {
    const modal = document.getElementById("lmsmodal");
    if (!modal) return console.error("Modal not found!");

    document.getElementById("lmsmodaltitle").textContent = "Remove Student";

    document.getElementById("lmsmodalcontent").innerHTML = `
        <p class='dark:text-white text-center mb-4'>
            Are you sure you want to remove <strong>${studentName}</strong> from this section?
        </p>
        <div class='flex flex-col md:flex-row gap-4 w-full'>
            <button data-close='lmsmodal' 
                    class='px-4 flex-1 py-2 border dark:text-white dark:hover:text-black border-[#ABB7C2] hover:bg-gray-100 dark:hover:bg-gray-700 rounded'>
                Cancel
            </button>

            <form method='POST' action='/admin/sections/{{ $section->id }}/students/${studentId}/remove' class='flex-1'>
                <input type='hidden' name='_token' value='{{ csrf_token() }}'>
                <input type='hidden' name='_method' value='DELETE'>
                <button type='submit' class='px-4 py-2 w-full bg-red-500 hover:bg-red-400 text-white rounded'>
                    Confirm
                </button>
            </form>
        </div>
    `;

    modal.classList.remove("hidden");
}
</script>
@endsection
