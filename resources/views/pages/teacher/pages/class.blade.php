@extends('pages.teacher.layout')

@section('content')
<div class="bg-[#FFFFFF] dark:bg-whiteColor-dark dark:border shadow-lg dark:border-[#5F2DED] p-4 sm:p-6 md:p-8 rounded-lg">

    {{-- Header --}}
    <div class="border-b border-[#D4D4D8] dark:border-white pb-4">
        <h1 class="text-2xl sm:text-3xl font-bold mb-4 text-[#5E5F60] dark:text-white">
            Classes
        </h1>

        {{-- Filters --}}
        <div class="my-4 flex flex-col lg:flex-row gap-4 lg:items-center">

            {{-- Grade Filter --}}
            <div class="flex flex-wrap gap-3 lg:flex-[2] items-center">

                {{-- All --}}
                <a
                    href="{{ route('teacher.classes', ['search' => $search]) }}"
                    class="
                        border border-[#ABB7C2]
                        text-[#373737] dark:text-white
                        px-6 sm:px-12
                        rounded-xl
                        py-1
                        whitespace-nowrap
                        {{ is_null($selectedGrade) ? 'bg-primaryColor text-white border-none' : '' }}
                    "
                >
                    All
                </a>

                {{-- Grade Dropdown --}}
                <form method="GET" action="{{ route('teacher.classes') }}">
                    <input type="hidden" name="search" value="{{ $search }}">
                    <div class="relative">
                        <select
                            name="grade"
                            onchange="this.form.submit()"
                            class="
                                border border-[#ABB7C2]
                                text-[#373737] dark:text-white
                                px-6 sm:px-12
                                py-1.5
                                rounded-xl
                                appearance-none
                                cursor-pointer
                                bg-white
                                dark:bg-whiteColor-dark
                                pr-10
                            "
                        >
                            @foreach ($availableGrades as $grade)
                                <option
                                    value="{{ $grade }}"
                                    @selected($grade == $selectedGrade)
                                >
                                    Grade {{ $grade }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </form>
            </div>

            {{-- Search --}}
            <div class="w-full lg:flex-[1]">
                <form method="GET" action="{{ route('teacher.classes') }}">
                    <input type="hidden" name="grade" value="{{ $selectedGrade }}">
                    <input
                        type="text"
                        name="search"
                        value="{{ $search }}"
                        placeholder="Search subject or section"
                        class="w-full border border-[#D4D4D8] dark:text-white dark:bg-whiteColor-dark rounded-lg px-4 py-2
                               focus:outline-none focus:ring-2 focus:ring-[#5F2DED]"
                    >
                </form>
            </div>
        </div>
    </div>

    {{-- Classes Grid --}}
    <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 sm:gap-6">

        @forelse ($assignments as $assign)
            @php
                $subject = $assign->subject;
                $section = $assign->section;
                $isAuditClass = (bool) ($assign->is_audit_only ?? false);

                $start = $assign->schedule?->start_time
                    ? \Carbon\Carbon::parse($assign->schedule->start_time)->format('h:i A')
                    : '—';

                $end = $assign->schedule?->end_time
                    ? \Carbon\Carbon::parse($assign->schedule->end_time)->format('h:i A')
                    : '—';
            @endphp

            <a
                href="{{ route('teacher.class.show', [$section->id, $subject->id]) }}"
                class="border rounded p-4 hover:scale-105 transition-all cursor-pointer flex flex-col h-full
                       {{ $isAuditClass
                            ? 'border-[#D97706] bg-[#FFF7ED] dark:bg-[#3A2A11]'
                            : 'border-[#5F2DED] bg-white dark:bg-whiteColor-dark' }}"
            >
                <div class="w-full h-32 sm:h-36 border rounded overflow-hidden {{ $isAuditClass ? 'border-[#D97706]' : 'border-[#5F2DED]' }}">
                    <img
                        src="{{ optional($assign->schedule)->coverImage
                            ? Storage::url($assign->schedule->coverImage)
                            : asset('classbg.jpg') }}"
                        alt="Cover Image"
                        class="w-full h-full object-cover rounded"
                    >

                </div>

                <div class="w-full flex-1 flex flex-col justify-between mt-3">
                    <div class="border-l-4 pl-2 flex flex-col justify-between h-full {{ $isAuditClass ? 'border-[#D97706]' : 'border-[#5F2DED]' }}">
                        <div>
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="font-semibold text-[#373737] dark:text-white">
                                        {{ $subject->subject_name }}
                                    </p>
                                    <p class="text-[#373737] dark:text-white text-sm mt-1">
                                        {{ $section->section_name }} | Section {{ $section->section_rank }}
                                    </p>
                                </div>

                                @if ($isAuditClass)
                                    <div class="flex flex-col items-end gap-1 shrink-0 text-[10px] uppercase tracking-[0.18em]">
                                        <span class="rounded-full bg-[#FED7AA] px-2 py-1 font-semibold text-[#9A3412]">
                                            Audit
                                        </span>
                                        <span class="rounded-full bg-[#E2E8F0] px-2 py-1 font-semibold text-[#475569]">
                                            Read Only
                                        </span>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="mt-4 text-xs text-[#373737] dark:text-white leading-tight">
                            <p class="font-medium">
                                {{ $start }} – {{ $end }}
                            </p>
                            <p class="text-[#757575] dark:text-gray-300">
                                {{ $assign->days_label }}
                            </p>

                            @if ($isAuditClass)
                                <p class="mt-3 text-[11px] font-medium uppercase tracking-[0.16em] text-[#B45309]">
                                    Teacher audit mode only
                                </p>
                            @endif
                        </div>


                    </div>
                </div>
            </a>

        @empty
            <p class="col-span-full text-center text-gray-500">
                No classes match your search.
            </p>
        @endforelse

    </div>
</div>
@endsection
