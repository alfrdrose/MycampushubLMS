@extends('pages.student.layout')

@section('content')
<div class="bg-[#FFFFFF] dark:bg-[#0F1724] dark:border-[#5F2DED] shadow-lg border p-4 sm:p-6 lg:p-8 rounded-lg"> 

    {{-- HEADER --}}
    <div class="border-b border-[#D4D4D8] dark:border-[#334155] pb-4">
        <h1 class="text-xl sm:text-2xl lg:text-3xl font-bold mb-4 text-[#5E5F60] dark:text-white">
            {{ $section->section_name }} - {{ $subject->subject_name }}
        </h1>

        <x-student-nav :active="'grade'" :sectionId="$section->id" :subjectId="$subject->id" />

        <div class="w-full flex flex-col sm:flex-row gap-3 sm:items-center mt-4"> 
            <div class="flex flex-row gap-2 items-center">
                <p class="font-medium text-sm text-[#373737] dark:text-white">
                    Grading Period:
                </p>

                <form method="GET">
                    <select
                        name="grading_period"
                        onchange="this.form.submit()"
                        class="border border-[#ABB7C2] dark:border-[#334155]
                               bg-white dark:bg-[#0F1724]
                               text-sm text-[#373737] dark:text-white
                               rounded-lg px-8 py-2"
                    >
                        @foreach (['1st','2nd','3rd','4th'] as $period)
                            <option value="{{ $period }}" @selected($gradingPeriod === $period)>
                                {{ $period }} Grading
                            </option>
                        @endforeach
                    </select>
                </form>
            </div>

            <div class="sm:ml-auto text-sm text-gray-500 dark:text-gray-400">
                Click your name to view computation
            </div>
        </div>
    </div>

    {{-- TABLE HEADER --}}
    <div class="flex flex-row mt-6 px-2 text-sm sm:text-base"> 
        <div class="w-1/2 font-medium text-[#373737] dark:text-gray-200">
            Student Name
        </div>
        <div class="w-1/2 font-medium text-right text-[#373737] dark:text-gray-200">
            Grade
        </div>
    </div>

    {{-- STUDENT ROW --}}
    <div class="flex flex-col mt-3 space-y-3">
        <div class="w-full flex flex-col sm:flex-row sm:items-center
                    bg-[#F0F9FF] dark:bg-[#0B1220]
                    p-4 rounded-md cursor-pointer
                    hover:opacity-90 transition-all"
             onclick="openPreview()">

            <div class="w-full sm:w-1/2">
                <p class="text-[#373737] dark:text-gray-100 font-medium">
                    {{ $student->last_name }}, {{ $student->first_name }}
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    Tap to view computation
                </p>
            </div>

            <div class="w-full sm:w-1/2 flex justify-start sm:justify-end mt-2 sm:mt-0">
                @if ($grade)
                    @if ($grade->grade >= 75)
                        <p class="text-[#1EC902] dark:text-[#86efac] font-semibold">
                            Passed {{ $grade->grade }}%
                        </p>
                    @else
                        <p class="text-[#B83330] dark:text-[#F87171] font-semibold">
                            Failed {{ $grade->grade }}%
                        </p>
                    @endif
                @else
                    <p class="text-[#5F2DED] font-semibold">
                        No Grade Yet
                    </p>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- ================= SCRIPT ================= --}}
<script>
async function openPreview() {

    const modal = document.getElementById('gradePreviewModal');
    const content = document.getElementById('previewContent');
    const title = document.getElementById('previewStudentName');
    const storedGrade = {{ $grade ? $grade->grade : 'null' }};

    content.innerHTML = '<p class="text-gray-400 dark:text-gray-500">Loading...</p>';
    modal.classList.remove('hidden');

    try {
        const res = await fetch(
            "{{ route('student.grade.preview', [$section->id, $subject->id]) }}",
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

        if (!data || !data.student) {
            content.innerHTML = '<p class="text-red-500">No grade data found.</p>';
            return;
        }

        title.textContent = data.student;

        let html = '';

        data.groups.forEach(group => {

    html += `
        <div class="border p-4 rounded-md dark:border-gray-700 mb-4">
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
            PS = (${group.earned} / ${group.possible}) x 100 =
            <span class="font-semibold">${group.ps}</span>
        </div>
        <div class="font-semibold mt-1">
            WS = ${group.ps} x ${group.weight}% =
            <span class="text-[#5F2DED]">${group.ws}</span>
        </div>
        </div>
    `;
});

html += `
    <div class="mt-6 pt-4 border-t dark:border-gray-700 flex justify-between">
        <span class="text-lg font-bold dark:text-white">Initial Grade</span>
        <span class="text-lg font-bold text-[#5F2DED]">${data.initial}</span>
    </div>
`;

html += `
    <div class="flex justify-between mt-2">
        <span class="text-lg font-bold dark:text-white">Final Grade</span>
        <span class="text-lg font-bold text-[#5F2DED]">
            ${storedGrade !== null ? storedGrade : data.initial}
        </span>
    </div>
`;

        content.innerHTML = html;

    } catch (err) {
        console.error(err);
        content.innerHTML = `
            <p class="text-red-500 font-semibold">
                Something went wrong loading the preview.
            </p>
        `;
    }
}

function closePreview() {
    document.getElementById('gradePreviewModal').classList.add('hidden');
}
</script>
@endsection


{{-- ================= MODAL ================= --}}
<div id="gradePreviewModal"
     class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">

    <div class="bg-white dark:bg-[#0F1724]
                w-full max-w-lg max-h-[80vh] overflow-y-auto
                rounded-lg p-5 sm:p-6">

        <div class="flex justify-between items-center mb-4">
            <h2 id="previewStudentName"
                class="text-lg font-bold text-[#373737] dark:text-white">
                Grade Computation
            </h2>

            <button onclick="closePreview()"
                    class="text-gray-500 hover:text-gray-700 dark:text-gray-300 text-xl">
                &times;
            </button>
        </div>

        <div id="previewContent"
             class="space-y-3 text-sm text-[#373737] dark:text-gray-300">
        </div>

        <div class="mt-6 text-right">
            <button onclick="closePreview()"
                    class="px-4 py-2 bg-red-500 hover:opacity-90 text-white rounded">
                Close
            </button>
        </div>
    </div>
</div>