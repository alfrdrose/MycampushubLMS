@extends('layouts.dashboard')

@section('content')
<div class="flex-1 flex flex-col p-10 h-auto bg-whiteColor shadow-lg dark:bg-darkblack rounded-2xl transition-all
        duration-300 dark:ring-2 dark:ring-blue-500/20 dark:ring-offset-0">
    <div id="page-header" class="mb-4">
        <h1 class="text-3xl font-bold text-[#5E5F60] dark:text-white">Add Tag</h1>
        <p class="text-[#ABB7C2]">Kindly fill out the form to add a new tag.</p>
    </div>

    <form id="formaddtag" action="{{ route('admin.storetag') }}" method="POST">
        @csrf

        <div class="flex flex-col gap-10 mt-4">

            <!-- Tag Name -->
            <div class="flex flex-row gap-6">
                <div class="flex flex-1 flex-col gap-1">
                    <p class="text-[#373737] font-medium dark:text-white">Tag Name</p>

                    <input type="text" 
                        id="tagName"
                        name="name"
                        class="p-3 border border-[#ABB7C2] rounded-md text-base dark:bg-whiteColor-dark dark:text-white"
                        placeholder="Enter tag name">

                    <p id="tagNameError"
                        class="text-red-500 text-sm hidden">
                        Tag name is required.
                    </p>
                </div>
            </div>

            <!-- Category -->
            <div class="flex flex-row gap-6">
                <div class="flex flex-1 flex-col gap-1">
                    <p class="text-[#373737] font-medium dark:text-white">Category</p>

                    <div class="flex gap-3">
                        <input type="text"
                            id="selectedCategory"
                            name="category"
                            readonly
                            class="flex-1 p-3 border border-[#ABB7C2] rounded-md text-base dark:bg-whiteColor-dark dark:text-white"
                            placeholder="Select subject">

                        <button type="button"
                            onclick="openSubjectModal()"
                            class="px-4 bg-[#5F2DED] text-white rounded-md hover:bg-[#4e26c6]">
                            Select
                        </button>
                    </div>

                    <p id="categoryError"
                        class="text-red-500 text-sm hidden">
                        Category is required.
                    </p>
                </div>
            </div>

            <!-- Buttons -->
            <div class="flex justify-between">
                <a href="{{ route('admin.tags') }}"
                    class="px-6 py-2 border border-primaryColor text-primaryColor rounded-lg hover:bg-[#4d1db8] hover:text-white transition">
                    Back
                </a>

                <button type="submit"
                    class="px-6 py-2 bg-[#5F2DED] text-white rounded-md hover:bg-[#4e26c6]">
                    Add Tag
                </button>
            </div>

        </div>
    </form>
</div>

<!-- SUBJECT MODAL -->
<div id="subjectModal"
    class="fixed inset-0 bg-black/60 hidden items-center justify-center z-50">

    <div class="relative bg-white dark:bg-[#1F2937] w-[700px] rounded-xl shadow-xl p-6
                border border-gray-200 dark:border-gray-700">

        <!-- Close -->
        <button type="button"
            onclick="closeSubjectModal()"
            class="absolute top-4 right-4
                   text-gray-500 dark:text-gray-400
                   hover:text-black dark:hover:text-white
                   transition text-xl font-bold">
            &times;
        </button>

        <h2 class="text-xl font-bold mb-4 text-[#373737] dark:text-white">
            Select Subject
        </h2>

        <!-- Search + Filter -->
        <div class="flex gap-10 mb-4">

            <input type="text"
                id="subjectSearch"
                placeholder="Search subject..."
                class="w-[300px] bg-transparent dark:bg-[#111827]
                       text-[#373737] dark:text-white
                       p-2 border border-gray-300 dark:border-gray-600
                       rounded focus:outline-none
                       focus:ring-2 focus:ring-[#5F2DED]">

            <select id="gradeFilter"
                class="flex-1 bg-transparent dark:bg-[#111827]
                       text-[#373737] dark:text-white
                       border border-gray-300 dark:border-gray-600
                       p-2 rounded
                       focus:outline-none
                       focus:ring-2 focus:ring-[#5F2DED]">

                <option value="">All Grades</option>

                @foreach($subjects->pluck('grade_level')->unique() as $grade)
                    <option value="{{ $grade }}">
                        Grade {{ $grade }}
                    </option>
                @endforeach

            </select>
        </div>

        <!-- Subject List -->
        <div id="subjectList"
            class="max-h-[300px] overflow-y-auto
                   border border-gray-200 dark:border-gray-700
                   rounded p-2 space-y-2
                   bg-gray-50 dark:bg-[#111827]">
            <!-- GENERAL OPTION (Always Available) -->
            <div class="subject-item flex justify-between items-center
                        p-3 rounded border
                        border-gray-200 dark:border-gray-700
                        bg-white dark:bg-[#1F2937]
                        hover:bg-gray-100 dark:hover:bg-[#2A3441]
                        transition"
                data-name="general"
                data-grade="">

                <div>
                    <p class="font-medium text-[#373737] dark:text-white">
                        General
                    </p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Applicable to all grades
                    </p>
                </div>

                <button type="button"
                    onclick="selectSubject('General')"
                    class="px-3 py-1 bg-[#5F2DED] text-white rounded hover:bg-[#4e26c6]">
                    Select
                </button>
            </div>
            @foreach($subjects as $subject)
                <div class="subject-item flex justify-between items-center
                            p-3 rounded border
                            border-gray-200 dark:border-gray-700
                            bg-white dark:bg-[#1F2937]
                            hover:bg-gray-100 dark:hover:bg-[#2A3441]
                            transition"
                     data-name="{{ strtolower($subject->subject_name) }}"
                     data-grade="{{ $subject->grade_level }}">

                    <div>
                        <p class="font-medium text-[#373737] dark:text-white">
                            {{ $subject->subject_name }}
                        </p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Grade {{ $subject->grade_level }}
                        </p>
                    </div>

                    <button type="button"
                        onclick="selectSubject('{{ $subject->subject_name }}')"
                        class="px-3 py-1 bg-[#5F2DED] text-white rounded hover:bg-[#4e26c6]">
                        Select
                    </button>
                </div>
            @endforeach

            <div id="noResults"
                class="hidden text-center py-6 text-gray-500 dark:text-gray-400">
                No subjects found.
            </div>
        </div>
    </div>
</div>

@if ($errors->has('name'))
<div id="duplicateTagModal"
     class="fixed inset-0 bg-black/60 flex items-center justify-center z-50">

    <div class="bg-white dark:bg-[#1F2937] p-6 rounded-xl shadow-xl
                w-[400px] border border-gray-200 dark:border-gray-700">

        <h2 class="text-lg font-bold text-red-600 dark:text-red-400 mb-3">
            Tag Already Exists
        </h2>

        <p class="text-gray-600 dark:text-gray-300 mb-4">
            {{ $errors->first('name') }}
        </p>

        <div class="flex justify-end">
            <button onclick="document.getElementById('duplicateTagModal').remove()"
                    class="px-4 py-2 bg-[#5F2DED] text-white rounded hover:bg-[#4e26c6]">
                OK
            </button>
        </div>

    </div>
</div>
@endif


<!-- SCRIPT -->
<script>

const form = document.getElementById('formaddtag');
const tagName = document.getElementById('tagName');
const category = document.getElementById('selectedCategory');
const tagNameError = document.getElementById('tagNameError');
const categoryError = document.getElementById('categoryError');

/* ---------- FORM VALIDATION ---------- */

form.addEventListener('submit', function(e) {

    let isValid = true;

    if (tagName.value.trim() === '') {
        tagNameError.classList.remove('hidden');
        isValid = false;
    }

    if (category.value.trim() === '') {
        categoryError.classList.remove('hidden');
        isValid = false;
    }

    if (!isValid) {
        e.preventDefault();
    }
});

/* Hide errors when typing/selecting */
tagName.addEventListener('input', function() {
    if (this.value.trim() !== '') {
        tagNameError.classList.add('hidden');
    }
});

function selectSubject(name) {
    category.value = name;
    categoryError.classList.add('hidden');
    closeSubjectModal();
}

/* ---------- MODAL ---------- */

function openSubjectModal() {
    document.getElementById('subjectModal').classList.remove('hidden');
    document.getElementById('subjectModal').classList.add('flex');
}

function closeSubjectModal() {
    document.getElementById('subjectModal').classList.add('hidden');
}

/* ---------- SEARCH + FILTER ---------- */

function filterSubjects() {
    const search = document.getElementById('subjectSearch').value.toLowerCase();
    const grade = document.getElementById('gradeFilter').value;
    const items = document.querySelectorAll('.subject-item');
    const noResults = document.getElementById('noResults');

    let visibleCount = 0;

    items.forEach(item => {
        const name = item.dataset.name;
        const itemGrade = item.dataset.grade;

        const matchesSearch = name.includes(search);
        const isGeneral = name === "general";
        const matchesGrade = isGeneral || grade === "" || grade === itemGrade;


        if (matchesSearch && matchesGrade) {
            item.style.display = "flex";
            visibleCount++;
        } else {
            item.style.display = "none";
        }
    });

    if (visibleCount === 0) {
        noResults.classList.remove('hidden');
    } else {
        noResults.classList.add('hidden');
    }
}

document.getElementById('subjectSearch').addEventListener('input', filterSubjects);
document.getElementById('gradeFilter').addEventListener('change', filterSubjects);

</script>

@endsection
