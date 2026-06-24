@extends('pages.student.layout')

@section('content')
<div class="bg-white dark:bg-[#0F1724] dark:border-[#5F2DED]
            shadow-lg border rounded-lg
            p-4 sm:p-6 lg:p-8">

    {{-- HEADER --}}
    <div class="border-b border-[#D4D4D8] dark:border-[#5F2DED] pb-4">
        <h1 class="text-xl sm:text-2xl lg:text-3xl font-bold
                   text-[#5E5F60] dark:text-white">
            {{ $subject->subject_name }}
        </h1>

        <p class="mb-3 text-sm sm:text-base
                  text-[#5E5F60] dark:text-gray-300">
            Section {{ $section->section_rank }} - {{ $section->section_name }}
        </p>

        <x-student-nav
            active="students"
            :sectionId="$section->id"
            :subjectId="$subject->id"
        />
    </div>

    {{-- TITLE --}}
    <div class="mt-6">
        <h2 class="text-lg sm:text-xl font-semibold
                   text-gray-700 dark:text-white">
            Students
        </h2>
    </div>

    {{-- STUDENTS LIST --}}
    <div class="mt-4 flex flex-col gap-2">

        @forelse ($students as $student)
            <div
                class="flex items-center gap-3 sm:gap-4
                       px-3 py-3 rounded-md
                       {{ $loop->odd
                           ? 'bg-[#F0F9FF] dark:bg-[#0B1220]'
                           : 'bg-white dark:bg-[#0F1724]' }}">

                {{-- AVATAR --}}
                <img
                    src="{{ $student->profileImageUrl() }}"
                    alt="{{ $student->first_name }}"
                    class="w-9 h-9 sm:w-11 sm:h-11
                           rounded-full object-cover
                           bg-gray-200 dark:bg-gray-700
                           flex-shrink-0"
                >

                {{-- NAME --}}
                <p class="font-medium
                          text-sm sm:text-base
                          text-[#373737] dark:text-gray-100">
                    {{ $student->last_name }},
                    {{ $student->first_name }}
                </p>
            </div>
        @empty
            <div class="py-6 text-center">
                <p class="text-gray-500 dark:text-gray-400">
                    No students enrolled in this class.
                </p>
            </div>
        @endforelse
        {{-- PAGINATION --}}
@if ($students instanceof \Illuminate\Pagination\LengthAwarePaginator)
    <div class="mt-6 flex justify-center">
        <div class="
            [&>nav]:w-full
            [&>nav>div]:flex-col
            [&>nav>div]:gap-2
            sm:[&>nav>div]:flex-row
            sm:[&>nav>div]:items-center
            sm:[&>nav>div]:justify-between
            text-sm
            text-gray-700
            dark:text-gray-200
        ">
            {{ $students->links() }}
        </div>
    </div>
@endif


    </div>
</div>
@endsection
