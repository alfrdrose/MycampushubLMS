@extends('layouts.dashboard')

@section('content')
@php
    $studentManagementQuery = request()->only(['search', 'grade', 'page']);
@endphp
<div class="flex-1 flex flex-col p-4 sm:p-10 h-auto bg-whiteColor shadow-lg dark:bg-darkblack rounded-2xl transition-all duration-300 dark:ring-2 dark:ring-blue-500/20 dark:ring-offset-0">

    <!-- Page Header -->
    <div id="page-header" class="mb-4">
        <h1 class="text-2xl sm:text-3xl font-bold text-[#5E5F60] dark:text-white">Students Management</h1>
        <p class="text-sm sm:text-base text-[#ABB7C2]">Control Student Information</p>
    </div>

    <!-- Filters & Search -->
    <div class="flex flex-col sm:flex-row w-full gap-4 sm:gap-0 sm:justify-between items-start sm:items-center pb-4">
        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4 sm:gap-6 w-full sm:w-auto">
            <p class="text-[#5E5F60] dark:text-white">Filter by:</p>
            <form method="GET" action="{{ route('admin.students') }}" class="flex items-center gap-2 w-full sm:w-auto">
                <label class="font-medium text-[#5E5F60] dark:text-white text-sm">Grade:</label>
                <select name="grade" onchange="this.form.submit()" class="w-full sm:w-auto border border-gray-300 dark:border-gray-50 cursor-pointer text-sm px-4 pr-8 py-2 dark:bg-bodyBg-dark dark:text-white rounded">
                    <option value="">All Grade Levels</option>
                    @for ($i = 1; $i <= 12; $i++)
                        <option value="{{ $i }}" {{ request('grade') == (string)$i ? 'selected' : '' }}>Grade {{ $i }}</option>
                    @endfor
                </select>
            </form>
        </div>

        <div class="relative w-full sm:w-[30%] mt-2 sm:mt-0">
            <form method="GET" action="{{ route('admin.students') }}">
                <div class="relative w-full">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                        <i class="icofont-search-1"></i>
                    </span>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by name or email..."
                        class="w-full pl-10 pr-3 py-2 rounded-xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-[#7F6994] text-sm dark:bg-bodyBg-dark dark:text-white dark:focus:ring-blue">
                </div>
            </form>
        </div>
    </div>

    <!-- Table for Desktop -->
    <div class=" pb-6 hidden sm:block">
        <table class="min-w-full text-sm">
            <thead class="border-b-[1px] sticky top-0 bg-whiteColor dark:bg-whiteColor-dark">
                <tr>
                    @foreach ($columns as $column)
                        <th class="py-4 text-left font-semibold text-[#5E5F60] dark:text-gray-300">{{ ucfirst(str_replace('_', ' ', $column)) }}</th>
                    @endforeach
                    <th class="py-4 text-left font-semibold text-[#5E5F60] dark:text-gray-300">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($students as $student)
                    @php
                        $enrollmentSchoolYear = $student->currentEnrollment?->schoolYear?->label ?? 'No enrollment';
                    @endphp
                    <tr data-student-id="{{ $student->id }}" class="border-b text-[#373737] dark:text-white">
                        <td class="py-2">{{ $student->id }}</td>
                        
                        <td class="py-2">{{ $student->first_name }}</td>
                        <td class="py-2">{{ $student->last_name }}</td>
                        
                        <td class="py-2">{{ $student->email }}</td>
                        <td class="py-2">{{ $student->guardian_email ?? 'N/A' }}</td>
                        
                        <td class="py-2">Grade {{ $student->grade }}</td>
                        <td class="py-2">{{ $student->user_id }}</td>
                        <td class="py-2">{{ $enrollmentSchoolYear }}</td>
                        <td class="py-2 px-4">
                            <div class="relative inline-block text-left">
                                <button class="actionMenuBtn p-2 rounded-full hover:bg-gray-200 dark:hover:bg-gray-700">
                                    <i class="icofont-gear text-lg text-gray-600 dark:text-gray-300"></i>
                                </button>
                                <div class="actionMenu hidden absolute right-8 top-8 mt-2 w-36 bg-white dark:bg-gray-800 rounded-md shadow-lg ring-1 ring-black ring-opacity-5 z-50">
                                    <div class="py-2 flex flex-col">
                                        <a href="{{ route('admin.student.enrollment', array_merge(['id' => $student->id], $studentManagementQuery)) }}"
                                        class="w-full px-4 py-2 text-sm text-left text-[#2563EB]
                                                hover:bg-gray-100 dark:hover:bg-gray-700
                                                flex items-center gap-2">
                                            <i class="icofont-history text-sm"></i> Enrollment
                                        </a>

                                        <a href="{{ route('admin.editstudent', $student->id) }}"
                                        class="w-full px-4 py-2 text-sm text-left text-[#5F2DED]
                                                hover:bg-gray-100 dark:hover:bg-gray-700
                                                flex items-center gap-2">
                                            <i class="icofont-ui-edit text-sm"></i> Update
                                        </a>

                                        <button class="btndeleteStudent w-full px-4 py-2 text-sm text-left text-[#C10007] hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center gap-2">
                                            <i class="icofont-ui-delete text-sm"></i> Delete
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($columns)+1 }}" class="text-center py-4 text-gray-500">No students found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Cards for Mobile -->
    <div class="sm:hidden space-y-4">
        @forelse ($students as $student)
            @php
                $enrollmentSchoolYear = $student->currentEnrollment?->schoolYear?->label ?? 'No enrollment';
            @endphp
            <div 
            class="p-4 bg-whiteColor dark:bg-whiteColor-dark rounded-lg shadow-md border dark:border-gray-700">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="font-semibold text-[#5E5F60] dark:text-white">Name: <span class="font-normal">{{ $student->first_name }} {{ $student->last_name }}</span></p>
                        <p class="text-sm text-gray-500 dark:text-gray-300">Email: {{ $student->email }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-300">Guardian Email: {{ $student->guardian_email }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-300">User ID: {{ $student->user_id }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-300">Grade: {{ $student->grade }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-300">School Year: {{ $enrollmentSchoolYear }}</p>
                    </div>
                    <div class="relative inline-block text-left">
                        <button class="actionMenuBtn p-2 rounded-full hover:bg-gray-200 dark:hover:bg-gray-700">
                            <i class="icofont-gear text-lg text-gray-600 dark:text-gray-300"></i>
                        </button>
                        <div class="actionMenu hidden absolute right-0 mt-2 w-36 bg-white dark:bg-gray-800 rounded-md shadow-lg ring-1 ring-black ring-opacity-5 z-50">
                            <div class="py-1  flex flex-col">
                                <a href="{{ route('admin.student.enrollment', array_merge(['id' => $student->id], $studentManagementQuery)) }}"
                                class="w-full px-4 py-2 text-sm text-left text-[#2563EB]
                                        hover:bg-gray-100 dark:hover:bg-gray-700
                                        flex items-center gap-2">
                                    <i class="icofont-history text-sm"></i> Enrollment
                                </a>

                                <a href="{{ route('admin.editstudent', $student->id) }}"
                                class="w-full px-4 py-2 text-sm text-left text-[#5F2DED]
                                        hover:bg-gray-100 dark:hover:bg-gray-700
                                        flex items-center gap-2">
                                    <i class="icofont-ui-edit text-sm"></i> Update
                                </a>

                                <button class="btndeleteStudent w-full px-4 py-2 text-sm text-left text-[#C10007] hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center gap-2">
                                    <i class="icofont-ui-delete text-sm"></i> Delete
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <p class="text-center py-4 text-gray-500">No students found.</p>
        @endforelse
    </div>

    <!-- Actions & Pagination -->
    <div class="flex justify-end w-full mt-4">
        <nav class="flex justify-center">
            {{ $students->links() }}
        </nav>
    </div>

    <!-- Modal -->
    <x-lmsmodal/>
    <div id="confirmDeleteStudent" class="hidden">
        <div class="flex flex-col justify-center items-center w-full gap-4">
            <p class="dark:text-white">Are you sure you want to delete this student?</p>
            <div class="flex flex-col sm:flex-row gap-4 w-full">
                <button data-close="lmsmodal" class="px-4 flex-1 py-2 border dark:text-white dark:hover:text-black border-[#ABB7C2] rounded">Cancel</button>
                <button id="confirmDeleteStudent" class="px-4 py-2 flex-1 bg-red-500 hover:bg-red-300 text-white rounded">Confirm</button>
            </div>
        </div>
    </div>
</div>
@endsection
