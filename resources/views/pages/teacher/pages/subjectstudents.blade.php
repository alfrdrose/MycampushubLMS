@extends('pages.teacher.layout')

@section('content')
<div class="bg-[#FFFFFF] dark:bg-[#0F1724] dark:border-[#5F2DED] shadow-lg border p-8 rounded-lg"> 

    {{-- HEADER --}}
    <div class="border-b border-[#D4D4D8] py-2 dark:border-[#5F2DED]">
        <h1 class="text-3xl font-bold text-[#5E5F60] dark:text-white">
            {{ $subject->subject_name }}
        </h1>
        <p class="mb-4 text-[#5E5F60] dark:text-white">
            Section {{ $section->section_rank }} - {{ $section->section_name }}
        </p>

        {{-- NAV --}}
        <x-teacher-nav active="students" :sectionId="$section->id" :subjectId="$subject->id"/>

        {{-- SORT + SEARCH --}}
        <div class=" flex flex-row p-2 gap-4 items-center ">

                <div class="relative ">
                    <input 
                        id="student-search"
                        type="text"
                        value="{{ $search ?? '' }}"
                        placeholder="Search students…" 
                        class="w-full pl-6 pr-10 py-2 rounded-3xl border border-gray-300
                               focus:outline-none focus:ring-2 focus:ring-[#7F6994] text-sm
                               dark:bg-[#0F1724] dark:text-white"
                    />
                    <span class="absolute inset-y-0 right-0 flex items-center pr-6 text-gray-400">
                        <i class="icofont-search-1"></i>
                    </span>
                </div>

        </div>
    </div>

    {{-- STUDENTS HEADER --}}
    <div class="flex flex-row mt-4 items-center">
        <div class="w-1/2">
            <h1 class="text-xl font-semibold text-gray-700 dark:text-white">Students</h1>
        </div>
        <div class="w-1/2 justify-end flex">
            <p class="text-sm text-gray-700 dark:text-white">
                {{ $students->total() }} Students
            </p>
        </div>
    </div>

    {{-- STUDENT LIST CONTENT --}}
    <div id="student-list-content">
        <div class="flex flex-col mt-3">
            @forelse($students as $student)
                <div class="w-full flex flex-row items-center
                    {{ $loop->odd ? 'bg-[#F0F9FF] dark:bg-[#0B1220]' : 'bg-white dark:bg-[#0F1724]' }}
                    px-4 py-2">

                    <div class="w-1/2 flex flex-row items-center gap-3">

                        <div class="w-8 h-8 rounded-full overflow-hidden border bg-gray-200 dark:bg-gray-700">
                            @if($student->hasMedia('profile_image'))
                                <img src="{{ $student->profileImageUrl() }}"
                                     class="w-full h-full object-cover rounded-full">
                            @else
                                <div class="w-full h-full flex items-center justify-center text-xs font-semibold text-gray-500">
                                    {{ strtoupper(substr($student->first_name, 0, 1)) }}
                                </div>
                            @endif
                        </div>

                        <p class="text-[#373737] dark:text-gray-100">
                            {{ $student->last_name }}, {{ $student->first_name }}
                        </p>
                    </div>
                </div>
            @empty
                <div class="w-full text-center py-4 text-gray-500">
                    No students found
                </div>
            @endforelse
        </div>

        <div class="mt-4">
            {{ $students->links() }}
        </div>
    </div>

</div>
@endsection

{{-- ===============================
     LIVE SEARCH + SORT + PAGINATION
=============================== --}}
@push('scripts')
<script>
let debounceTimer = null;
let isLoading = false;

/* ===============================
   FETCH STUDENTS (AJAX)
=============================== */
function fetchStudents(url = null) {
    if (isLoading) return;
    isLoading = true;

    const search = document.getElementById('student-search').value;

    const fetchUrl = url ?? (() => {
        const u = new URL(window.location.href);
        u.searchParams.set('search', search);
        return u.toString();
    })();

    fetch(fetchUrl, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.text())
    .then(html => {
        document.getElementById('student-list-content').innerHTML = html;
    })
    .catch(error => {
        console.error('Student fetch error:', error);
    })
    .finally(() => {
        isLoading = false;
    });
}

/* 🔥 LIVE SEARCH */
document.getElementById('student-search').addEventListener('input', () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(fetchStudents, 250);
});

/* 🔥 AJAX PAGINATION */
document.addEventListener('click', function (e) {
    const link = e.target.closest('.pagination a');
    if (!link) return;

    e.preventDefault();
    fetchStudents(link.href);
});
</script>

@endpush
