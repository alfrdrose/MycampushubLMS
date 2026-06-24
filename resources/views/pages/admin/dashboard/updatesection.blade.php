@extends('layouts.updatesection-layout')

@section('content')

<div class="flex justify-between items-center mb-4">
    <div class="flex items-center gap-3">
        <p class="font-medium text-[#373737] dark:text-white text-xl">Summary</p>
        <span class="inline-flex items-center rounded-full border border-[#D7E1EA] px-2.5 py-1 text-xs font-medium text-[#667085] dark:border-white/10 dark:text-[#C5CED8]">
            SY {{ $section->schoolYear?->label ?? ($section->start_year . ' - ' . $section->end_year) }}
        </span>
    </div>

    <!-- DELETE BUTTON -->
    <button
        onclick="openDeleteModal()"
        class="bg-red-500 hover:opacity-90 transition-all text-white px-4 py-2 rounded-lg text-sm">
        Delete Section
    </button>
</div>


{{-- ================= DELETE MODAL ================= --}}
<div id="deleteModal"
     class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">

    <div class="bg-white dark:bg-[#1f2937] rounded-lg w-[90%] max-w-md p-6 shadow-lg">

        <h2 class="text-lg font-semibold text-[#373737] dark:text-white mb-2">
            Delete Section
        </h2>

        <p class="text-sm text-gray-600 dark:text-gray-300 mb-6">
            Are you sure you want to delete this section?  
            Students, adviser, subjects, and schedules will only be unassigned.
        </p>

        <div class="flex justify-end gap-3">

            <button
                onclick="closeDeleteModal()"
                class="px-4 py-2 rounded-lg border border-gray-300 text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 dark:text-white">
                Cancel
            </button>

            <!-- DELETE FORM -->
            <form id="deleteForm"
                  action="{{ route('admin.section.delete', $section->id) }}"
                  method="POST">
                @csrf
                @method('DELETE')

                <button type="submit"
                        id="deleteSubmitBtn"
                        class="bg-red-500 hover:opacity-90 text-white px-4 py-2 rounded-lg flex items-center gap-2">

                    <svg id="loadingSpinner"
                         class="animate-spin h-4 w-4 hidden"
                         xmlns="http://www.w3.org/2000/svg"
                         fill="none"
                         viewBox="0 0 24 24">
                        <circle class="opacity-25"
                                cx="12" cy="12" r="10"
                                stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75"
                              fill="currentColor"
                              d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>

                    <span id="deleteText">Delete</span>
                </button>
            </form>

        </div>
    </div>
</div>


{{-- Summary cards --}}
<div class="border-t border-[#D4D4D8] dark:border-white py-1">
    <div class="w-full flex flex-col md:flex-row gap-4 mt-5">

        <!-- Card 1 - Total Students -->
        <div class="flex-1 rounded-lg border-2 border-[#E1D6FF] px-4 py-5 flex flex-row items-center">
            <div class="w-[4px] h-[55px] bg-[#F2277E] rounded-full"></div>
            <div class="flex flex-col ml-4">
                <p class="text-3xl font-bold text-[#373737] dark:text-white">{{ $totalStudents }}</p>
                <p class="text-sm text-[#5E5F60] dark:text-white mt-3">Total Students</p>
            </div>
        </div>

        <!-- Card 2 - Total Subjects -->
        <div class="flex-1 rounded-lg border-2 border-[#E1D6FF] px-4 py-5 flex flex-row items-center">
            <div class="w-[4px] h-[55px] bg-[#F2277E] rounded-full"></div>
            <div class="flex flex-col ml-4">
                <p class="text-3xl font-bold text-[#373737] dark:text-white">{{ $totalSubjects }}</p>
                <p class="text-sm text-[#5E5F60] dark:text-white mt-3">Total Subjects</p>
            </div>
        </div>

        <!-- Card 3 - Class Adviser -->
        <div class="flex-1 rounded-lg border-2 border-[#E1D6FF] px-4 py-5 flex flex-row items-center">
            <div class="w-[4px] h-[55px] bg-[#F2277E] rounded-full"></div>
            <div class="flex flex-col ml-4">
                <p class="text-lg font-bold text-[#373737] dark:text-white">
                    @if ($adviser)
                        {{ $adviser->first_name . ' ' . $adviser->last_name }}
                    @else
                        <span class="text-sm italic text-gray-400 mt-2">Not assigned yet</span>
                    @endif
                </p>
                <p class="text-sm text-[#5E5F60] dark:text-white mt-3">Class Adviser</p>
            </div>
        </div>
    </div>

    <!-- Class Schedule -->
    <div class="rounded-lg border-2 border-[#E1D6FF] mt-6 px-4 py-4 overflow-x-auto">
        <p class="text-base text-[#373737] dark:text-white font-semibold pb-3">Class Schedule</p>

        <div class="min-w-[600px]">

            <div class="flex flex-row w-full pb-3 border-b border-[#E2E8F0]">
                <div class="flex items-center flex-[1.5] mr-2">
                    <p class="text-[#5E5F60] dark:text-white text-sm">Days</p>
                </div>
                <div class="flex items-center flex-[1.5]">
                    <p class="text-[#5E5F60] dark:text-white text-sm">Time</p>
                </div>
                <div class="flex items-center flex-[2]">
                    <p class="text-[#5E5F60] dark:text-white text-sm">Subject Name</p>
                </div>
                <div class="flex items-center flex-[1.5]">
                    <p class="text-[#5E5F60] dark:text-white text-sm">Teacher</p>
                </div>
            </div>

            <div class="flex flex-col">
                @forelse($subjects as $subject)
                    @if($subject->pivot->days && $subject->pivot->start_time && $subject->pivot->end_time)
                        <div class="flex flex-row w-full py-3 border-b border-gray-100">
                            <div class="flex items-center flex-[1.5] mr-2">
                                <p class="text-[#373737] dark:text-white text-sm">
                                    {{ implode(', ', json_decode($subject->pivot->days)) }}
                                </p>
                            </div>
                            <div class="flex items-center flex-[1.5]">
                                <p class="text-[#373737] dark:text-white text-sm">
                                    {{ \Carbon\Carbon::parse($subject->pivot->start_time)->format('h:i A') }}
                                    -
                                    {{ \Carbon\Carbon::parse($subject->pivot->end_time)->format('h:i A') }}
                                </p>
                            </div>
                            <div class="flex items-center flex-[2]">
                                <p class="text-[#373737] dark:text-white text-sm">{{ $subject->subject_name }}</p>
                            </div>
                            <div class="flex items-center flex-[1.5] space-x-2">
                                @if($subject->teachers->isNotEmpty())
                                    @foreach($subject->teachers as $teacher)
                                        <p class="text-[#373737] dark:text-white text-sm">
                                            {{ $teacher->last_name }}, {{ $teacher->first_name }}
                                        </p>
                                    @endforeach
                                @else
                                    <p class="text-sm text-gray-400 italic">No teacher assigned</p>
                                @endif
                            </div>
                        </div>
                    @endif
                @empty
                    <p class="text-gray-400 dark:text-white text-sm mt-3">No schedule assigned yet.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>


{{-- ================= JS ================= --}}
<script>
    function openDeleteModal() {
        document.getElementById('deleteModal').classList.remove('hidden');
        document.getElementById('deleteModal').classList.add('flex');
    }

    function closeDeleteModal() {
        document.getElementById('deleteModal').classList.add('hidden');
        document.getElementById('deleteModal').classList.remove('flex');
    }

    document.getElementById('deleteForm').addEventListener('submit', function () {

        document.getElementById('deleteSubmitBtn').disabled = true;
        document.getElementById('loadingSpinner').classList.remove('hidden');
        document.getElementById('deleteText').innerText = 'Deleting...';
    });
</script>

@endsection
