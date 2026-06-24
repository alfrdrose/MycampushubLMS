@extends('pages.teacher.layout')

@section('content')
<div class="bg-[#FFFFFF] dark:bg-[#0F1724] dark:border-[#5F2DED] shadow-lg border p-8 rounded-lg"> 

    {{-- HEADER --}}
    <div class="border-b border-[#D4D4D8] dark:border-[#334155]">
        <h1 class="text-3xl font-bold mb-4 text-[#5E5F60] dark:text-white">
            {{ $section->section_name }}
        </h1>

        <x-teacher-nav 
            active="grade"
            :sectionId="$section->id"
            :subjectId="$subject->id"
        />

        {{-- FILTER BAR --}}
        <div class="w-full flex flex-col sm:flex-row gap-4 p-2"> 

            {{-- GRADING PERIOD --}}
            <div class="flex items-center gap-2 sm:w-1/2">
                <label class="font-medium text-sm dark:text-white h-[40px] flex items-center">
                    Grading Period:
                </label>

                <form method="GET">
                    <select
                        name="grading_period"
                        onchange="this.form.submit()"
                        class="h-[40px] border border-[#ABB7C2]
                               dark:bg-[#0F1724] dark:text-white
                               rounded-lg px-8 text-sm cursor-pointer"
                    >
                        @foreach (['1st','2nd','3rd','4th'] as $period)
                            <option value="{{ $period }}" @selected($gradingPeriod === $period)>
                                {{ $period }} Grading
                            </option>
                        @endforeach
                    </select>
                </form>
            </div>

            {{-- SEARCH --}}
            <div class="sm:w-1/2 flex justify-end">
                <div class="relative w-full sm:w-[60%] h-10">
                    <input 
                        type="text"
                        id="studentSearch"
                        placeholder="Search student"
                        class="w-full h-10 pl-6 pr-12 rounded-full border border-gray-300
                               text-sm
                               focus:outline-none focus:ring-2 focus:ring-[#7F6994]
                               dark:bg-[#0F1724] dark:text-white"
                    />

                    <div class="absolute inset-0 grid place-items-center pointer-events-none">
                        <i class="icofont-search-1 text-gray-400 text-lg justify-self-end mr-4"></i>
                    </div>
                </div>
            </div>
        </div>

        <x-grading-nav :section="$section" :subject="$subject" />
    </div>

    {{-- TABLE HEADER --}}
    <div class="flex mt-4">
        <div class="w-1/2 text-xl font-medium dark:text-gray-200">
            Student Name
        </div>
        <div class="w-1/2 text-xl font-medium text-right dark:text-gray-200">
            Grades
        </div>
    </div>

    {{-- STUDENT LIST --}}
    <div id="studentList" class="flex flex-col mt-4 space-y-3">
        @foreach ($students as $student)
            @php $grade = $grades[$student->id] ?? null; @endphp

            <div
                class="student-row w-full flex items-center
                       {{ $loop->even ? 'bg-[#F0F9FF] dark:bg-[#0B1220]' : '' }}
                       p-4 rounded-md cursor-pointer hover:opacity-90 transition-all"
                data-name="{{ strtolower($student->last_name . ' ' . $student->first_name) }}"
                onclick="openPreview({{ $student->id }})"
            >
                <div class="w-1/2">
                    <p class="dark:text-gray-100">
                        {{ $student->last_name }}, {{ $student->first_name }}
                    </p>
                </div>

                <div class="w-1/2 text-right">
                    @if ($grade)
                        <span class="{{ $grade->grade >= 75 ? 'text-green-500' : 'text-red-500' }}">
                            {{ $grade->grade >= 75 ? 'Passed' : 'Failed' }} {{ $grade->grade }}%
                        </span>
                    @else
                        <span class="text-[#5F2DED]">No Grade Yet</span>
                    @endif
                </div>
            </div>
        @endforeach

        <div id="noStudentsFound"
             class="hidden text-center py-6 text-gray-500 dark:text-gray-400">
            No students found.
        </div>
    </div>
</div>

@endsection


{{-- ================= MODAL ================= --}}
<div id="gradePreviewModal"
     class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">

    <div class="bg-white dark:bg-[#0F1724]
                w-full max-w-xl max-h-[80vh] overflow-y-auto
                rounded-lg p-6">

        <div class="flex justify-between items-center mb-4">
            <h2 id="previewStudentName"
                class="text-lg font-bold dark:text-white">
                Grade Computation
            </h2>

            <button onclick="closePreview()"
                    class="text-xl text-gray-500 dark:text-gray-300">
                &times;
            </button>
        </div>

        <div id="previewContent"
             class="space-y-4 text-sm dark:text-gray-300">
        </div>

        <div class="mt-6 text-right">
            <button onclick="closePreview()"
                    class="px-4 py-2 bg-red-500 text-white rounded">
                Close
            </button>
        </div>
    </div>
</div>


{{-- ================= SCRIPT ================= --}}
<script>
document.addEventListener('DOMContentLoaded', function () {

    const searchInput = document.getElementById('studentSearch');
    const rows = document.querySelectorAll('.student-row');
    const noResults = document.getElementById('noStudentsFound');

    searchInput.addEventListener('input', function () {
        const query = this.value.toLowerCase().trim();
        let visible = 0;

        rows.forEach(row => {
            const name = row.dataset.name;
            row.classList.toggle('hidden', !name.includes(query));
            if (name.includes(query)) visible++;
        });

        noResults.classList.toggle('hidden', visible !== 0);
    });
});


async function openPreview(studentId) {

    const modal = document.getElementById('gradePreviewModal');
    const content = document.getElementById('previewContent');
    const title = document.getElementById('previewStudentName');

    modal.classList.remove('hidden');
    content.innerHTML = '<p class="text-gray-400">Loading...</p>';

    try {
        const res = await fetch(
            "{{ route('teacher.grade.preview', [$section->id, $subject->id]) }}",
            {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    grading_period: "{{ $gradingPeriod }}"
                })
            }
        );

        const data = await res.json();

        const studentData = data.results.find(
            s => s.student_id === studentId
        );

        if (!studentData) {
            content.innerHTML = '<p class="text-red-500">No grade data found.</p>';
            return;
        }

        title.textContent = studentData.student;

        let html = '';

        studentData.groups.forEach(group => {

            html += `
                <div class="border p-4 rounded-md dark:border-gray-700">
                    <h3 class="font-bold text-md text-[#5F2DED] mb-2">
                        ${group.group} (${group.weight}%)
                    </h3>
            `;

            group.items.forEach(item => {
                html += `
                    <div class="flex justify-between mb-1">
                        <span>${item.label}</span>
                        <span class="font-medium">${item.score}</span>
                    </div>
                `;
            });

            html += `
                <div class="mt-3 text-gray-600 dark:text-gray-400">
                    PS = (${group.earned} / ${group.possible}) × 100 = 
                    <span class="font-semibold">${group.ps}</span>
                </div>
                <div class="font-semibold mt-1">
                    WS = ${group.ps} × ${group.weight}% = 
                    <span class="text-[#5F2DED]">${group.ws}</span>
                </div>
                </div>
            `;
        });

        html += `
            <div class="mt-6 pt-4 border-t dark:border-gray-700">
                <div class="flex justify-between text-lg font-bold">
                    <span>Initial Grade</span>
                    <span>${studentData.initial}</span>
                </div>

                <div class="flex justify-between text-lg font-bold mt-2">
                    <span>Transmuted Grade</span>
                    <span class="text-[#5F2DED]">
                        ${studentData.transmuted}
                    </span>
                </div>
            </div>
        `;

        content.innerHTML = html;

    } catch (err) {
        console.error(err);
        content.innerHTML = `
            <p class="text-red-500 font-semibold">
                Failed to load computation.
            </p>
        `;
    }
}

function closePreview() {
    document.getElementById('gradePreviewModal').classList.add('hidden');
}
</script>