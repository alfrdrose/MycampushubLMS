@props(['grade_level' => null, 'activeSchoolYear' => null])

<div
    class="flex-1 flex flex-col p-6 sm:p-10 h-auto
           bg-whiteColor shadow-lg dark:bg-darkblack
           rounded-2xl transition-all duration-300
           dark:ring-2 dark:ring-blue-500/20 dark:ring-offset-0"
>
    <!-- Header -->
    <div id="page-header" class="mb-6">
        <h1 id="page-title" class="text-2xl sm:text-3xl font-bold text-[#5E5F60] dark:text-white">
            Add Section
        </h1>
        <p id="page-subtitle" class="text-sm sm:text-base text-[#ABB7C2]">
            Kindly fill out the form to add a new section.
        </p>
    </div>

    <!-- Form -->
    <form id="formaddsection" action="{{ route('admin.storesection') }}" method="POST">
        @csrf

        <div class="flex flex-col gap-8">

            <!-- Grade Level + Section Name -->
            <div class="flex flex-col sm:flex-row gap-6">
                <!-- Grade Level -->
                <div class="flex flex-1 flex-col gap-1">
                    <p class="text-[#373737] font-medium dark:text-white">Grade Level</p>

                    <!-- Display only -->
                    <input
                        type="text"
                        value="Grade {{ $grade_level }}"
                        readonly
                        disabled
                        class="p-3 border border-[#ABB7C2] rounded-md text-base
                               dark:bg-whiteColor-dark dark:text-white"
                    />

                    <!-- Hidden value -->
                    <input type="hidden" name="grade_level" value="{{ $grade_level }}">
                </div>

                <!-- Section Name -->
                <div class="flex flex-1 flex-col gap-1">
                    <p class="text-[#373737] font-medium dark:text-white">Section Name</p>
                    <input
                        type="text"
                        id="txtfn"
                        name="section_name"
                        value="{{ old('section_name') }}"
                        placeholder="Enter section name"
                        class="p-3 border border-[#ABB7C2] rounded-md text-base
                               dark:bg-whiteColor-dark dark:text-white"
                    >
                    <p id="errorsectionname" class="text-red-400 text-sm hidden">
                        Section Name is required.
                    </p>
                    @error('section_name')
                        <p class="text-sm text-red-400">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- School Year -->
            <div class="flex flex-col gap-2 w-full">
                <p class="text-[#373737] font-medium dark:text-white">School Year</p>

                <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <input
                        type="text"
                        id="txtSchoolYearLabel"
                        value="{{ $activeSchoolYear ? 'SY ' . $activeSchoolYear->label : 'No active school year selected' }}"
                        readonly
                        data-active-school-year="{{ $activeSchoolYear ? '1' : '0' }}"
                        class="w-full p-3 border rounded-md text-base select-none focus:outline-none
                               {{ $activeSchoolYear ? 'border-[#ABB7C2] dark:bg-whiteColor-dark dark:text-white' : 'border-red-300 text-red-500 dark:border-red-400 dark:bg-whiteColor-dark dark:text-red-300' }}"
                    >

                    @if ($activeSchoolYear)
                        <span class="inline-flex items-center justify-center rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-medium uppercase tracking-[0.12em] text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300">
                            Active
                        </span>
                    @endif
                </div>

                <p id="errorSchoolYear" class="text-red-400 text-sm {{ $activeSchoolYear ? 'hidden' : '' }}">
                    Activate a school year in School Year Management before adding sections.
                </p>

                @if ($activeSchoolYear)
                    <p class="text-xs text-[#ABB7C2] dark:text-white">
                        New sections in this module are always created under the active school year.
                    </p>
                @endif

                @error('school_year')
                    <p class="text-sm text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Buttons -->
            <div class="flex flex-col sm:flex-row w-full gap-4 sm:gap-0">
                <!-- Back Button -->
                <div class="w-full sm:w-1/2 flex justify-start">
                    <a
                        href="{{ route('admin.sections', ['grade_level' => $grade_level]) }}"
                        class="inline-flex justify-center items-center w-full sm:w-auto
                               px-10 py-2 border border-primaryColor
                               text-primaryColor font-medium rounded-lg
                               hover:bg-[#4d1db8] hover:text-white transition"
                    >
                        Back
                    </a>
                </div>

                <!-- Action Buttons -->
                <div class="w-full sm:w-1/2 flex flex-col sm:flex-row justify-end gap-3">
                    <button
                        type="button"
                        id="btnclearaddsection"
                        class="w-full sm:flex-1 px-4 py-3
                               border border-[#ABB7C2] text-[#5E5F60] font-medium
                               dark:text-[#ABB7C2] dark:hover:text-white
                               rounded-md hover:bg-red-400 hover:text-white
                               transition-colors duration-200"
                    >
                        Clear
                    </button>

                    <button
                        type="button"
                        id="openAddSectionModal"
                        {{ $activeSchoolYear ? '' : 'disabled' }}
                        class="w-full sm:flex-1 px-4 py-3
                               bg-[#5F2DED] text-white font-medium text-base
                               rounded-md hover:bg-[#4e26c6]
                               transition-colors duration-200
                               disabled:cursor-not-allowed disabled:bg-gray-300 disabled:text-gray-600"
                    >
                        Add Section
                    </button>
                </div>
            </div>

        </div>
    </form>
</div>

<!-- Modal -->
<x-lmsmodal />

<div id="confirmSectionContent" class="hidden">
    <div class="flex flex-col items-center w-full gap-4">
        <p class="dark:text-white text-center">
            Are you sure you want to add this section?
        </p>

        <div class="flex flex-col sm:flex-row gap-4 w-full">
            <button
                data-close="lmsmodal"
                class="flex-1 px-4 py-2 border border-[#ABB7C2]
                       dark:text-white dark:hover:text-black
                       hover:bg-gray-100 rounded"
            >
                Cancel
            </button>

            <button
                id="confirmAddSection"
                form="formaddsection"
                class="flex-1 px-4 py-2
                       bg-[#5F2DED] hover:bg-[#4f23c6]
                       text-white rounded"
            >
                Confirm
            </button>
        </div>
    </div>
</div>
