@extends('layouts.dashboard')

@php
    $selectedStatus = request('status');
@endphp

@section('content')
<div class="flex-1 flex flex-col p-6 sm:p-10 h-auto bg-whiteColor shadow-lg dark:bg-darkblack rounded-2xl transition-all
        duration-300 dark:ring-2 dark:ring-blue-500/20 dark:ring-offset-0">
    <div id="page-header" class="mb-6">
        <h1 id="page-title" class="text-2xl sm:text-3xl font-bold text-[#5E5F60] dark:text-white">Teachers Management</h1>
        <p id="page-subtitle" class="text-sm sm:text-base text-[#ABB7C2]">Control Teacher Information</p>
    </div>

    <!-- Filters & Search -->
    <div class="flex flex-col sm:flex-row w-full gap-6 sm:gap-0 sm:justify-between items-start sm:items-center pb-6">
        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-6 sm:gap-12 w-full sm:w-auto">
            <p class="text-[#5E5F60] dark:text-white">Filter by:</p>

            <!-- Faculty filter -->
            <form method="GET" action="{{ route('admin.teachers') }}" class="flex items-center gap-3 w-full sm:w-auto">
                @if (request()->filled('search'))
                    <input type="hidden" name="search" value="{{ request('search') }}">
                @endif
                @if (request()->filled('status'))
                    <input type="hidden" name="status" value="{{ request('status') }}">
                @endif
                <label class="font-medium text-[#5E5F60] dark:text-white text-sm">Faculty</label>
                <select name="faculty" onchange="this.form.submit()"
                    class="w-full sm:w-auto border border-gray-300 dark:border-gray-700 cursor-pointer text-sm px-4 py-2 dark:bg-bodyBg-dark dark:text-white rounded-md">
                    <option value="">All Faculties</option>
                    <option value="english" {{ request('faculty') == 'english' ? 'selected' : '' }}>English</option>
                    <option value="mathematics" {{ request('faculty') == 'mathematics' ? 'selected' : '' }}>Mathematics</option>
                    <option value="science" {{ request('faculty') == 'science' ? 'selected' : '' }}>Science</option>
                    <option value="filipino" {{ request('faculty') == 'filipino' ? 'selected' : '' }}>Filipino</option>
                    <option value="ap" {{ request('faculty') == 'ap' ? 'selected' : '' }}>Araling Panlipunan</option>
                    <option value="mapeh" {{ request('faculty') == 'mapeh' ? 'selected' : '' }}>MAPEH</option>
                    <option value="esp" {{ request('faculty') == 'esp' ? 'selected' : '' }}>ESP (Values Education)</option>
                    <option value="tle" {{ request('faculty') == 'tle' ? 'selected' : '' }}>TLE / ICT</option>
                    <option value="shs_core" {{ request('faculty') == 'shs_core' ? 'selected' : '' }}>Senior High Core</option>
                    <option value="shs_specialized" {{ request('faculty') == 'shs_specialized' ? 'selected' : '' }}>Senior High Specialized</option>
                </select>
            </form>

            <form method="GET" action="{{ route('admin.teachers') }}" class="flex items-center gap-3 w-full sm:w-auto">
                @if (request()->filled('search'))
                    <input type="hidden" name="search" value="{{ request('search') }}">
                @endif
                @if (request()->filled('faculty'))
                    <input type="hidden" name="faculty" value="{{ request('faculty') }}">
                @endif
                <label class="font-medium text-[#5E5F60] dark:text-white text-sm">Status</label>
                <select name="status" onchange="this.form.submit()"
                    class="w-full sm:w-auto border border-gray-300 dark:border-gray-700 cursor-pointer text-sm px-8 py-2 dark:bg-bodyBg-dark dark:text-white rounded-md">
                    <option value="">All Status</option>
                    <option value="active" {{ $selectedStatus === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ $selectedStatus === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </form>
        </div>

        <!-- Search Bar -->
        <div class="relative w-full sm:w-[30%] mt-4 sm:mt-0">
            <form method="GET" action="{{ route('admin.teachers') }}">
                @if (request()->filled('faculty'))
                    <input type="hidden" name="faculty" value="{{ request('faculty') }}">
                @endif
                @if (request()->filled('status'))
                    <input type="hidden" name="status" value="{{ request('status') }}">
                @endif
                <div class="relative w-full">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                        <i class="icofont-search-1"></i>
                    </span>
                    <input type="text" name="search" value="{{ request('search') }}"
                        placeholder="Search by name or email..."
                        class="w-full pl-10 pr-3 py-3 rounded-xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-[#7F6994] text-sm dark:bg-bodyBg-dark dark:text-white dark:focus:ring-blue">
                </div>
            </form>
        </div>
    </div>

    <div class="pb-6">
    <div class="">
        <!-- Desktop Table -->
        <table class="min-w-full text-sm hidden sm:table">
            <thead class="border-b-[1px] sticky top-0 bg-whiteColor dark:bg-whiteColor-dark">
                <tr>
                    @foreach ($columns as $column)
                        <th class="py-5 text-left font-semibold text-[#5E5F60] dark:text-gray-300">
                            {{ ucfirst(str_replace('_', ' ', $column)) }}
                        </th>
                    @endforeach
                    <th class="py-5 text-left font-semibold text-[#5E5F60] dark:text-gray-300">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($teachers as $teacher)
                    @php
                        $teacherStatus = $teacher->user?->statusValue();
                        $statusLabel = $teacherStatus ? ucfirst($teacherStatus) : 'No Login';
                        $statusButtonClass = match ($teacherStatus) {
                            'active' => 'border-green-200 bg-green-100 text-green-700 dark:border-green-500/30 dark:bg-green-900/30 dark:text-green-300',
                            'inactive' => 'border-red-200 bg-red-100 text-red-700 dark:border-red-500/30 dark:bg-red-900/30 dark:text-red-300',
                            default => 'border-slate-200 bg-slate-100 text-slate-600 dark:border-slate-500/30 dark:bg-slate-800 dark:text-slate-300',
                        };
                    @endphp
                    <tr data-teacher-id="{{ $teacher->id }}" class="border-b text-[#373737] dark:text-white">
                        <td class="py-3">{{ $teacher->id }}</td>
                        <td class="py-3">{{ $teacher->user_id }}</td>
                        <td class="py-3">{{ $teacher->first_name }}</td>
                        <td class="py-3">{{ $teacher->last_name }}</td>
                        <td class="py-3">{{ $teacher->email }}</td>
                        <td class="py-3">{{ $teacher->faculty }}</td>
                        <td class="py-3">
                            <button
                                type="button"
                                class="teacherStatusToggle inline-flex items-center rounded-full border px-3 py-1 text-xs font-semibold transition {{ $statusButtonClass }} {{ $teacher->user ? 'cursor-pointer hover:brightness-95' : 'cursor-not-allowed opacity-75' }}"
                                data-teacher-status-id="{{ $teacher->id }}"
                                data-status="{{ $teacherStatus ?? '' }}"
                                @disabled(! $teacher->user)
                                title="{{ $teacher->user ? 'Toggle teacher login status' : 'No linked login account' }}">
                                {{ $statusLabel }}
                            </button>
                        </td>
                        <td class="py-3 px-4">
                            <div class="relative inline-block text-left">
                                <button class="actionMenuBtn p-3 rounded-full hover:bg-gray-200 dark:hover:bg-gray-700">
                                    <i class="icofont-gear text-lg text-gray-600 dark:text-gray-300"></i>
                                </button>
                                <div class="actionMenu hidden absolute right-10 bottom-2 mt-2 w-24 bg-white dark:bg-gray-800 rounded-md shadow-lg ring-1 ring-black ring-opacity-5 z-50">
                                    <div class="py-1 flex flex-col">
                                        <button class="btnupdateTeacher w-full px-4 py-2 text-sm text-left text-[#5F2DED] hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center gap-2">
                                            <i class="icofont-ui-edit text-sm"></i> Update
                                        </button>
                                        <button class="btndeleteTeacher w-full px-4 py-2 text-sm text-left text-[#C10007] hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center gap-2">
                                            <i class="icofont-ui-delete text-sm"></i> Delete
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($columns)+1 }}" class="text-center py-6 text-gray-500">No teachers found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <!-- Mobile Card View -->
        <div class="sm:hidden flex flex-col gap-4">
            @forelse ($teachers as $teacher)
                @php
                    $teacherStatus = $teacher->user?->statusValue();
                    $statusLabel = $teacherStatus ? ucfirst($teacherStatus) : 'No Login';
                    $statusButtonClass = match ($teacherStatus) {
                        'active' => 'border-green-200 bg-green-100 text-green-700 dark:border-green-500/30 dark:bg-green-900/30 dark:text-green-300',
                        'inactive' => 'border-red-200 bg-red-100 text-red-700 dark:border-red-500/30 dark:bg-red-900/30 dark:text-red-300',
                        default => 'border-slate-200 bg-slate-100 text-slate-600 dark:border-slate-500/30 dark:bg-slate-800 dark:text-slate-300',
                    };
                @endphp
                <div 
                data-teacher-id="{{ $teacher->id }}"
                class="bg-whiteColor dark:bg-whiteColor-dark rounded-xl p-4 shadow-sm flex flex-col gap-2 border border-gray-200 dark:border-gray-700">
                    <div class="flex justify-between items-center">
                        <p class="font-semibold text-[#5E5F60] dark:text-white">ID: {{ $teacher->id }}</p>
                        <div class="relative inline-block text-left">
                            <button class="actionMenuBtn p-2 rounded-full hover:bg-gray-200 dark:hover:bg-gray-700">
                                <i class="icofont-gear text-lg text-gray-600 dark:text-gray-300"></i>
                            </button>
                            <div class="actionMenu hidden absolute right-0 mt-2 w-36 bg-white dark:bg-gray-800 rounded-md shadow-lg ring-1 ring-black ring-opacity-5 z-50">
                                <div class="py-1 flex flex-col">
                                    <button class="btnupdateTeacher w-full px-4 py-2 text-sm text-left text-[#5F2DED] hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center gap-2">
                                        <i class="icofont-ui-edit text-sm"></i> Update
                                    </button>
                                    <button class="btndeleteTeacher w-full px-4 py-2 text-sm text-left text-[#C10007] hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center gap-2">
                                        <i class="icofont-ui-delete text-sm"></i> Delete
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <p><span class="font-medium">User ID:</span> {{ $teacher->user_id }}</p>
                    <p><span class="font-medium">Name:</span> {{ $teacher->first_name }} {{ $teacher->last_name }}</p>
                    <p><span class="font-medium">Email:</span> {{ $teacher->email }}</p>
                    <p><span class="font-medium">Faculty:</span> {{ $teacher->faculty }}</p>
                    <div class="pt-2">
                        <button
                            type="button"
                            class="teacherStatusToggle inline-flex items-center rounded-full border px-3 py-1 text-xs font-semibold transition {{ $statusButtonClass }} {{ $teacher->user ? 'cursor-pointer hover:brightness-95' : 'cursor-not-allowed opacity-75' }}"
                            data-teacher-status-id="{{ $teacher->id }}"
                            data-status="{{ $teacherStatus ?? '' }}"
                            @disabled(! $teacher->user)
                            title="{{ $teacher->user ? 'Toggle teacher login status' : 'No linked login account' }}">
                            {{ $statusLabel }}
                        </button>
                    </div>
                </div>
            @empty
                <p class="text-center text-gray-500">No teachers found.</p>
            @endforelse
        </div>
    </div>
</div>


    <!-- Actions & Pagination -->
    <div class="flex justify-end w-full">
        <nav class="flex justify-center">
            {{ $teachers->links() }}
        </nav>
    </div>

    <!-- Modals -->
    <x-lmsmodal/>
    <div id="confirmDeleteTeacherModal" class="hidden">
        <div class="flex flex-col justify-center items-center w-full gap-6">
            <p class="dark:text-white text-center">Are you sure you want to delete this teacher?</p>
            <div class="flex flex-col sm:flex-row gap-4 w-full">
                <button data-close="lmsmodal" class="px-4 py-3 flex-1 border dark:text-white dark:hover:text-black border-[#ABB7C2] rounded">Cancel</button>
                <button id="confirmDeleteTeacher" class="px-4 py-3 flex-1 bg-red-500 hover:bg-red-300 text-white rounded">Confirm</button>
            </div>
        </div>
    </div>
</div>
@endsection
