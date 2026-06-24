@extends('pages.student.layout')

@section('content')
    <div
        class="
            bg-[#FFFFFF] dark:bg-whiteColor-dark dark:border shadow-lg dark:border-[#5F2DED]
            p-4 sm:p-6 lg:p-8
            rounded-lg
        "
    > 
        {{-- Header --}}
        <div class="border-b border-[#D4D4D8] dark:border-white pb-4 flex flex-col">
            <h1 class="text-2xl sm:text-3xl font-bold text-[#5E5F60] dark:text-white">
                Classes
            </h1>
            <p class="text-sm text-[#757575] dark:text-white mt-2">
                @if ($section)
                    Section {{ $section->section_rank }} - {{ $section->section_name }}
                @else
                    No classes are available for the active school year.
                @endif
            </p>
        </div>

        {{-- Cards Grid --}}
        <div
            class="
                mt-6
                grid
                grid-cols-1
                sm:grid-cols-2
                lg:grid-cols-3
                xl:grid-cols-4
                gap-4 sm:gap-5 lg:gap-6
            "
        >
            @forelse ($subjects as $subject)
                <a
                    href="{{ route('student.class.subject', [$section->id, $subject->subject_id]) }}"
                    class="
                        border border-[#5F2DED]
                        rounded-lg
                        p-3 sm:p-4
                        hover:scale-105 transition-all
                        cursor-pointer
                        flex flex-col
                        bg-white dark:bg-whiteColor-dark
                    "
                >
                    {{-- Image --}}
                    <div
                        class="
                            w-full
                            h-28 sm:h-32 lg:h-36
                            border border-[#5F2DED]
                            rounded
                            overflow-hidden
                        "
                    >
                        <img
                            src="{{ $subject->coverImage
                                ? Storage::url($subject->coverImage)
                                : asset('classbg.jpg') }}"
                            alt="Cover Image"
                            class="w-full h-full object-cover"
                        >


                    </div>

                    {{-- Content --}}
                    <div class="mt-3 border-l-4 border-[#5F2DED] pl-2 flex flex-col">
                        {{-- Subject --}}
                        <p class="font-semibold text-sm sm:text-base text-[#373737] dark:text-white">
                            {{ $subject->subject_name }}
                        </p>

                        {{-- Teacher --}}
                        <p class="text-xs sm:text-sm text-[#373737] dark:text-white mt-1">
                            {{ $subject->teacher_name }}
                        </p>

                        @php
                            $daysRaw = $subject->days ? json_decode($subject->days, true) : null;

                            $dayMap = [
                                'Mon' => 'Monday',
                                'Tue' => 'Tuesday',
                                'Wed' => 'Wednesday',
                                'Thu' => 'Thursday',
                                'Fri' => 'Friday',
                                'Sat' => 'Saturday',
                                'Sun' => 'Sunday',
                            ];

                            if (!$daysRaw || empty($daysRaw)) {
                                $daysLabel = 'N/A';
                            } else {
                                $ordered = array_values(array_intersect(
                                    array_keys($dayMap),
                                    $daysRaw
                                ));

                                if (count($ordered) > 1) {
                                    $daysLabel = $dayMap[$ordered[0]] . ' – ' . $dayMap[end($ordered)];
                                } else {
                                    $daysLabel = $dayMap[$ordered[0]];
                                }
                            }
                        @endphp

                        <p class="text-sm text-[#373737] dark:text-white mt-4">
                            {{ $daysLabel }}
                        </p>


                        {{-- Time --}}
                        <p class="text-xs text-[#373737] dark:text-white leading-tight">
                            @php
                                $start = $subject->start_time
                                    ? \Carbon\Carbon::parse($subject->start_time)->format('h:i A')
                                    : 'TBD';

                                $end = $subject->end_time
                                    ? \Carbon\Carbon::parse($subject->end_time)->format('h:i A')
                                    : 'TBD';
                            @endphp
                            {{ $start }} – {{ $end }}
                        </p>
                    </div>
                </a>
            @empty
                <p class="col-span-full text-gray-500">
                    No classes assigned.
                </p>
            @endforelse
        </div>
    </div>
@endsection
