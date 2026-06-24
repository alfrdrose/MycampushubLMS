@props(['columns', 'users'])

<div class="flex-1 flex flex-col p-4 sm:p-6 md:p-10 h-auto
            bg-whiteColor dark:bg-darkblack
            shadow-lg rounded-2xl
            transition-all duration-300
            dark:ring-2 dark:ring-blue-500/20">

    <!-- Page Header -->
    <div id="page-header" class="mb-4">
        <h1 class="text-2xl sm:text-3xl font-bold text-[#5E5F60] dark:text-white">
            Accounts Management
        </h1>
        <p class="text-[#ABB7C2] dark:text-gray-400 text-sm sm:text-base">
            Organize and Control User Accounts
        </p>
    </div>

    @php
        $studentCsvImportFeedback = session('studentCsvImportFeedback', []);
        $studentCsvFileIssues = is_array($studentCsvImportFeedback) ? ($studentCsvImportFeedback['file_issues'] ?? []) : [];
        $studentCsvRowIssues = is_array($studentCsvImportFeedback) ? ($studentCsvImportFeedback['row_issues'] ?? []) : [];
        $studentCsvIssueItems = is_array($studentCsvImportFeedback) ? ($studentCsvImportFeedback['issue_items'] ?? []) : [];
        $studentCsvSummary = is_array($studentCsvImportFeedback) ? ($studentCsvImportFeedback['summary'] ?? []) : [];
        $studentCsvShouldAutoOpen = $errors->studentCsv->any() || $studentCsvFileIssues !== [] || $studentCsvRowIssues !== [] || $studentCsvIssueItems !== [];
    @endphp

    <!-- Filters & Search -->
    <div class="flex flex-col lg:flex-row w-full justify-between gap-4 pb-4">

        <!-- Filters -->
        <div class="flex flex-col sm:flex-row flex-wrap items-start sm:items-center gap-4 w-full lg:w-auto">
            <p class="text-[#5E5F60] dark:text-white font-medium">
                Filter by:
            </p>

            <!-- Role Filter -->
            <div class="relative"
                 x-data="{ open:false, selected: '{{ request('role') ? ucfirst(request('role')) : 'All Types' }}' }">
                <button @click="open = !open"
                        class="flex items-center gap-2 border px-4 py-2 rounded-lg
                               border-gray-300 dark:border-gray-700
                               bg-white dark:bg-gray-900">
                    <span class="text-sm text-[#5E5F60] dark:text-white" x-text="selected"></span>
                    <i class="icofont-rounded-down dark:text-white"></i>
                </button>

                <div x-show="open" @click.outside="open=false" x-transition
                     class="absolute left-0 mt-2 w-36
                            bg-white dark:bg-gray-800
                            border dark:border-gray-700
                            rounded-lg shadow-lg z-50">
                    <a href="{{ route('admin.accounts', array_merge(request()->query(), ['role' => null])) }}"
                       @click="selected='All Types'; open=false"
                       class="block px-4 py-2 text-sm
                              text-[#5E5F60] dark:text-white
                              hover:bg-gray-100 dark:hover:bg-gray-700">
                        All Types
                    </a>
                    <a href="{{ route('admin.accounts', array_merge(request()->query(), ['role' => 'admin'])) }}"
                       @click="selected='Admin'; open=false"
                       class="block px-4 py-2 text-sm dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700">
                        Admin
                    </a>
                    <a href="{{ route('admin.accounts', array_merge(request()->query(), ['role' => 'teacher'])) }}"
                       @click="selected='Teacher'; open=false"
                       class="block px-4 py-2 text-sm dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700">
                        Teacher
                    </a>
                    <a href="{{ route('admin.accounts', array_merge(request()->query(), ['role' => 'student'])) }}"
                       @click="selected='Student'; open=false"
                       class="block px-4 py-2 text-sm dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700">
                        Student
                    </a>
                </div>
            </div>

            <!-- Status Filter -->
            <div class="relative"
                 x-data="{ open:false, selected: '{{ request('status') ? ucfirst(request('status')) : 'All Status' }}' }">
                <button @click="open = !open"
                        class="flex items-center gap-2 border px-4 py-2 rounded-lg
                               border-gray-300 dark:border-gray-700
                               bg-white dark:bg-gray-900">
                    <span class="text-sm text-[#5E5F60] dark:text-white" x-text="selected"></span>
                    <i class="icofont-rounded-down dark:text-white"></i>
                </button>

                <div x-show="open" @click.outside="open=false" x-transition
                     class="absolute left-0 mt-2 w-36
                            bg-white dark:bg-gray-800
                            border dark:border-gray-700
                            rounded-lg shadow-lg z-50">
                    <a href="{{ route('admin.accounts', array_merge(request()->query(), ['status' => null])) }}"
                       @click="selected='All Status'; open=false"
                       class="block px-4 py-2 text-sm dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700">
                        All Status
                    </a>
                    <a href="{{ route('admin.accounts', array_merge(request()->query(), ['status' => 'active'])) }}"
                       @click="selected='Active'; open=false"
                       class="block px-4 py-2 text-sm dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700">
                        Active
                    </a>
                    <a href="{{ route('admin.accounts', array_merge(request()->query(), ['status' => 'inactive'])) }}"
                       @click="selected='Inactive'; open=false"
                       class="block px-4 py-2 text-sm dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700">
                        Inactive
                    </a>
                </div>
            </div>

            <div class="relative">
                <button
                    id="openStudentsCsvImportPopover"
                    type="button"
                    aria-expanded="false"
                    title="Import CSV"
                    class="group relative flex h-10 items-center justify-center gap-2 rounded-xl border border-[#D7DCE2] bg-white px-3 text-[#6B7280]
                        shadow-sm transition hover:border-[#C7D2FE] hover:text-[#5F2DED]
                        dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-[#477eec] dark:hover:text-[#93C5FD]">
                    
                    <i class="icofont-upload-alt text-lg"></i>
                    <span class="text-sm font-medium">Import CSV</span>

                    <span
                        class="pointer-events-none absolute left-1/2 top-full z-[70] mt-2 -translate-x-1/2 whitespace-nowrap rounded-lg bg-[#111827] px-2.5 py-1 text-[11px] font-medium text-white opacity-0 shadow-lg transition group-hover:opacity-100">
                        Import CSV
                    </span>
                </button>

                <div
                    id="studentsCsvImportPopover"
                    class="hidden absolute left-0 top-full z-[65] mt-3 w-[min(24rem,calc(100vw-2rem))] rounded-2xl border border-[#E5E7EB] bg-white/95 p-4 shadow-[0_24px_70px_-28px_rgba(15,23,42,0.45)] backdrop-blur dark:border-gray-700 dark:bg-[#111827]/95">
                    @if ($studentCsvIssueItems !== [])
                        <div id="studentsCsvValidationPanel" class="rounded-2xl border border-red-200 bg-red-50/90 p-4 dark:border-red-500/30 dark:bg-red-950/20">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-sm font-semibold text-red-700 dark:text-red-200">Import validation failed</p>
                                    <p class="mt-3 text-xs font-semibold uppercase tracking-[0.2em] text-red-700 dark:text-red-200">Issues</p>
                                </div>
                                <button
                                    type="button"
                                    data-dismiss-student-csv-validation
                                    class="inline-flex h-7 w-7 items-center justify-center rounded-full text-red-500 transition hover:bg-red-100 hover:text-red-700 dark:hover:bg-red-900/30 dark:hover:text-red-100">
                                    <i class="icofont-close-line text-lg"></i>
                                </button>
                            </div>

                            <div class="mt-3 max-h-72 space-y-3 overflow-y-auto pr-1">
                                @foreach ($studentCsvIssueItems as $issueItem)
                                    <div class="rounded-xl border border-red-200/80 bg-white/80 p-3 dark:border-red-500/20 dark:bg-black/20">
                                        <div class="flex flex-wrap items-center gap-2 text-[11px] font-semibold uppercase tracking-[0.12em] text-red-600 dark:text-red-200">
                                            @if (!empty($issueItem['line']))
                                                <span>Line {{ $issueItem['line'] }}</span>
                                            @endif
                                            @if (!empty($issueItem['column']))
                                                <span class="rounded-full bg-red-100 px-2 py-1 text-[10px] dark:bg-red-900/30">
                                                    {{ $issueItem['column'] }}
                                                </span>
                                            @endif
                                        </div>
                                        <p class="mt-2 text-xs leading-5 text-[#5E5F60] dark:text-gray-200">
                                            {{ $issueItem['text'] ?? '' }}
                                        </p>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div class="{{ $studentCsvIssueItems !== [] ? 'mt-4' : '' }}">
                        <form id="studentsCsvUploadForm" action="{{ route('admin.accounts.students.upload') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <label id="studentsCsvDropzone"
                                class="relative flex min-h-[180px] w-full cursor-pointer flex-col items-center justify-center rounded-2xl border-2 border-dashed border-[#CBD5E1] bg-[#F8FAFC] p-6 text-center text-[#6B7280] transition hover:border-[#5F2DED] hover:text-[#5F2DED] dark:border-gray-600 dark:bg-black/10 dark:text-gray-300 dark:hover:border-[#477eec] dark:hover:text-[#93C5FD]">
                                <a href="{{ route('admin.accounts.students.template') }}"
                                    title="Download CSV template"
                                    class="absolute right-3 top-3 text-[#94A3B8] transition hover:text-[#5F2DED] dark:text-gray-400 dark:hover:text-[#93C5FD]">
                                    <i class="icofont-file-alt text-base"></i>
                                </a>

                                <div id="studentsCsvUploadOverlay"
                                    class="absolute inset-0 rounded-2xl bg-white/90 dark:bg-black/70 flex flex-col items-center justify-center gap-2 text-[#5E5F60] dark:text-gray-100 opacity-0 pointer-events-none transition-opacity">
                                    <span class="inline-flex items-center gap-2 text-sm font-medium">
                                        <span class="inline-flex h-5 w-5 rounded-full border-2 border-[#5F2DED]/30 border-t-[#5F2DED] animate-spin"></span>
                                        Uploading CSV
                                    </span>
                                    <span class="text-xs text-[#5E5F60] dark:text-gray-300">Please wait while the file is being validated.</span>
                                </div>

                                <i class="icofont-cloud-upload text-[2.2rem]"></i>

                                <span
                                    id="studentsCsvFilename"
                                    data-empty="No file selected"
                                    class="absolute bottom-4 left-1/2 -translate-x-1/2 rounded-full bg-white/90 px-3 py-1 text-[11px] font-medium text-[#475569] shadow-sm dark:bg-[#0F172A]/90 dark:text-gray-200">
                                    No file selected
                                </span>

                                <input
                                    id="studentsCsvInput"
                                    type="file"
                                    name="students_csv"
                                    accept=".csv,text/csv"
                                    class="sr-only"
                                    required
                                />
                            </label>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search -->
        <div class="w-full lg:w-[30%]">
            <form method="GET" action="{{ route('admin.accounts') }}">
                <div class="relative">
                    <i class="icofont-search-1 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Search by name or email..."
                        class="w-full pl-10 pr-4 py-2 rounded-xl border
                               border-gray-300 dark:border-gray-700
                               bg-white dark:bg-bodyBg-dark
                               text-sm text-[#5E5F60] dark:text-white
                               focus:ring-2 focus:ring-[#7F6994] dark:focus:ring-blue">
                </div>
            </form>
        </div>
    </div>

    <!-- Users Table (Horizontal scroll on mobile) -->
    <div class="w-full overflow-x-auto pb-6">
        <table class="min-w-[900px] w-full text-sm">
            <thead class="border-b sticky top-0
                          bg-whiteColor dark:bg-whiteColor-dark">
                <tr>
                    @foreach ($columns as $column)
                        <th class="py-4 font-semibold text-left
                                   text-[#5E5F60] dark:text-gray-300
                                   whitespace-nowrap
                                   {{ $column === 'actions' ? 'text-right pr-4' : '' }}">
                            {{ ucfirst(str_replace('_', ' ', $column)) }}
                        </th>
                    @endforeach
                </tr>
            </thead>

            <tbody>
                @forelse ($users as $user)
                    <tr class="border-b dark:border-gray-700 text-[#373737] dark:text-white">
                        <td class="py-2">{{ $user->id }}</td>
                        <td class="py-2">{{ $user->name }}</td>
                        <td class="py-2">{{ $user->email }}</td>

                        <td class="py-2">
                            <span class="px-4 py-1 rounded-lg text-xs font-medium
                                {{ $user->status === 'active'
                                    ? 'text-green-700 bg-green-100 dark:text-green-400 dark:bg-green-900/40'
                                    : 'text-red-700 bg-red-100 dark:text-red-400 dark:bg-red-900/40' }}">
                                {{ ucfirst($user->status) }}
                            </span>
                        </td>

                        <td class="py-2">
                            {{ $user->getRoleNames()->first() ?? 'No Role' }}
                        </td>

                        <td class="py-2 text-right pr-6">
                            <div class="relative inline-block">
                                <button class="actionMenuBtn p-2 rounded-full
                                               hover:bg-gray-200 dark:hover:bg-gray-700">
                                    <i class="icofont-gear text-lg text-gray-600 dark:text-gray-300"></i>
                                </button>

                                <div class="actionMenu hidden absolute right-0 mt-2 w-32
                                            bg-white dark:bg-gray-800
                                            rounded-lg shadow-lg z-50">
                                    <button class="btnupdate w-full px-4 py-2 text-sm text-left
                                                   text-[#5F2DED]
                                                   hover:bg-gray-100 dark:hover:bg-gray-700">
                                        Update
                                    </button>
                                    <button class="btndelete w-full px-4 py-2 text-sm text-left
                                                   text-[#C10007]
                                                   hover:bg-gray-100 dark:hover:bg-gray-700">
                                        Delete
                                    </button>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($columns) }}"
                            class="text-center py-6 text-gray-500 dark:text-gray-400">
                            No users found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Footer -->
    <div class="flex flex-col sm:flex-row justify-between items-center gap-4 mt-4">
        <a href="{{ route('admin.addaccount') }}"
           class="w-full sm:w-auto px-6 py-3 flex items-center justify-center gap-2
                  text-[#5F2DED] dark:text-[#477eec]
                  border border-[#5F2DED] dark:border-[#477eec]
                  rounded-xl transition
                  hover:bg-[#5F2DED] hover:text-white
                  dark:hover:bg-[#244890] dark:hover:text-white">
            <i class="icofont-ui-add"></i>
            Add Account
        </a>

        <div class="w-full sm:w-auto">
            {{ $users->links() }}
        </div>
    </div>

    <div id="studentsCsvImportState" class="hidden" data-auto-open="{{ $studentCsvShouldAutoOpen ? '1' : '0' }}"></div>

    <x-lmsmodal/>

    <div id="confirmDeleteModal" class="hidden">
        <div class="flex justify-center items-center flex-col w-full">
            <p class="dark:text-white">Are you sure you want to delete this account?</p>
            <div class="flex gap-4 mt-4 w-full flex-col sm:flex-row">
                <button data-close="lmsmodal" class="px-4 flex-1 py-2 border dark:text-white dark:hover:text-black border-[#ABB7C2] hover:bg-gray-100 rounded">Cancel</button>
                <button id="confirmDelete" class="px-4 py-2 flex-1 bg-red-500 hover:bg-red-300 text-white rounded">Confirm</button>
            </div>
        </div>
    </div>
</div>
