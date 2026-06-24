@extends('pages.student.layout')

@section('content')
<div class="bg-[#FFFFFF] dark:bg-[#1E1E2D] dark:border-[#5F2DED] shadow-lg border p-8 rounded-lg">
    <div class="border-b border-[#D4D4D8] dark:border-[#5F2DED]">
        <h1 class="text-3xl font-bold mb-4 text-[#5E5F60] dark:text-white">
            {{ $section->section_name }} - {{ $subject->subject_name }}
        </h1>

        <x-student-nav :active="'lessons'" :sectionId="$section->id" :subjectId="$subject->id"/>
    </div>

    <div class="gap-6 flex flex-col mt-6 w-full">
        @php
            $gradingPeriods = [
                '1st' => '1st Quarter',
                '2nd' => '2nd Quarter',
                '3rd' => '3rd Quarter',
                '4th' => '4th Quarter',
            ];
        @endphp

        <div x-data="{ selectedPeriod: '{{ request('grading_period', '1st') }}' }" class="w-full">
            <div class="flex items-center gap-3 mb-6">
                <label class="text-[#373737] dark:text-white font-semibold text-sm sm:text-base">
                    Grading Period:
                </label>

                <select x-model="selectedPeriod"
                    class="px-8 py-2 rounded-lg border cursor-pointer border-gray-300 dark:border-[#5F2DED]
                        bg-white dark:bg-[#2A2A3C] text-[#373737] dark:text-white text-sm sm:text-base">
                    @foreach($gradingPeriods as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            @foreach($gradingPeriods as $periodKey => $period)
                @php
                    $lessonsInPeriod = $lessons->where('grading_period', $periodKey);
                @endphp

                <div x-show="selectedPeriod === '{{ $periodKey }}'" x-cloak>
                    <div class="pb-2">
                        <h2 class="text-2xl font-semibold text-[#373737] dark:text-[#A88BFF]">
                            {{ $period }}
                        </h2>
                    </div>

                    @forelse($lessonsInPeriod as $lesson)
                        @php
                            $openAt = null;
                            if ($lesson->open_date) {
                                $datePart = \Carbon\Carbon::parse($lesson->open_date)->format('Y-m-d');
                                $timePart = $lesson->open_time
                                    ? \Carbon\Carbon::parse($lesson->open_time)->format('H:i:s')
                                    : '00:00:00';
                                $openAt = \Carbon\Carbon::parse($datePart . ' ' . $timePart);
                            }
                            $lessonDescriptionPreview = \Illuminate\Support\Str::limit(
                                \Illuminate\Support\Str::squish(strip_tags($lesson->description ?? '')),
                                135
                            );
                        @endphp

                        <a href="{{ route('student.lesson.show', [
                            'section_id' => $section->id,
                            'subject_id' => $subject->id,
                            'lesson_id' => $lesson->id,
                            'grading_period' => $periodKey,
                        ]) }}"
                           class="flex flex-col lg:flex-row mb-4 border border-l-8 border-l-[#531B8A] dark:border-[#A88BFF] rounded p-4 w-full lg:hover:scale-105 transition-all cursor-pointer">
                            <div class="px-2 py-2">
                                <div class="rounded-full w-2 h-2 bg-[#531B8A] dark:bg-[#A88BFF]"></div>
                            </div>

                            <div class="flex flex-col ml-2 mb-2 gap-0 w-full lg:w-1/2">
                                <p class="font-semibold text-[#373737] dark:text-white text-lg">
                                    {{ $lesson->lesson_title }}
                                </p>

                                @if($lessonDescriptionPreview !== '')
                                    <p class="text-sm text-[#5E5F60] dark:text-gray-300 mt-1 max-w-full overflow-hidden break-words"
                                       style="display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:3;overflow-wrap:anywhere;">
                                        {{ $lessonDescriptionPreview }}
                                    </p>
                                @endif
                            </div>

                            <div class="w-full lg:w-1/2 mb-2 flex flex-row justify-end">
                                <div class="flex flex-col w-full items-start lg:items-end">
                                    <div class="px-2 py-2 h-1/2">
                                        <div class="rounded-full w-12 h-12 bg-[#E1D6FF] flex justify-center items-center">
                                            <i class="icofont-ebook text-[#5F2DED] text-2xl"></i>
                                        </div>
                                    </div>

                                    <div class="h-1/2 w-full items-start lg:items-end justify-end flex flex-col pt-2">
                                        <div class="flex items-center">
                                            <i class="icofont-clock-time text-xl mr-2 text-[#F2277E]"></i>
                                            <p class="text-[#757575] dark:text-gray-300 text-sm">
                                                <span class="font-bold text-black dark:text-white">Date Posted:</span>
                                                {{ \Carbon\Carbon::parse($lesson->created_at)->format('h:i A M d, Y') }}
                                            </p>
                                        </div>

                                        <div class="flex items-center">
                                            <i class="icofont-clock-time text-xl mr-2 text-[#F2277E]"></i>
                                            <p class="font-bold text-black dark:text-white text-sm">
                                                Open Date:
                                                <span class="font-normal">
                                                    {{ $openAt ? $openAt->format('h:i A M d, Y') : 'Immediately' }}
                                                </span>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </a>
                    @empty
                        <p class="text-gray-500 dark:text-gray-400 ml-2 text-sm">
                            No lessons yet for this quarter.
                        </p>
                    @endforelse
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
