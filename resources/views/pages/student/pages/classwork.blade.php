@extends('pages.student.layout')

@section('content')
<div class="bg-[#FFFFFF] dark:bg-[#1E1E2D] dark:border-[#5F2DED] shadow-lg border p-4 sm:p-8 rounded-lg">
    <div class="border-b border-[#D4D4D8] dark:border-[#5F2DED]">
        <h1 class="text-xl sm:text-3xl font-bold mb-4 text-[#5E5F60] dark:text-white">
            {{ $section->section_name }} - {{ $subject->subject_name }}
        </h1>
        <x-student-nav active="classwork" :sectionId="$section->id" :subjectId="$subject->id"/>
    </div>

    @php
        $quizzesByPeriod = $quizzes->groupBy('grading_period');
        $evaluationsByPeriod = $evaluations->groupBy('grading_period');

        $gradingPeriods = [
            '1st' => '1st Quarter',
            '2nd' => '2nd Quarter',
            '3rd' => '3rd Quarter',
            '4th' => '4th Quarter',
        ];
    @endphp

    <div class="flex flex-col mt-4">
        <div class="flex flex-col w-full">

            @if($activities->isEmpty() && $quizzes->isEmpty() && $evaluations->isEmpty())
                <div class="w-full flex justify-center items-center">
                    <p class="text-center text-gray-500 dark:text-gray-400 mt-8 text-sm">
                        No classwork has been added yet.
                    </p>
                </div>
            @else

                {{-- DROPDOWN FILTER --}}
                <div x-data="{ selectedPeriod: '1st' }" class="w-full">

                    <div class="flex items-center gap-3 mb-6">
                        <label class="text-[#373737] dark:text-white font-semibold text-sm sm:text-base">
                            Grading Period:
                        </label>

                        <select x-model="selectedPeriod"
                            class="px-6 cursor-pointer py-2 rounded-lg border border-gray-300 dark:border-[#5F2DED]
                                bg-white dark:bg-[#2A2A3C] text-[#373737] dark:text-white text-sm sm:text-base">
                            @foreach($gradingPeriods as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- SHOW ONLY SELECTED PERIOD --}}
                    @foreach ($gradingPeriods as $periodKey => $periodLabel)
                        @php
                            $periodActivities = $activities->get($periodKey, collect());
                            $periodQuizzes = $quizzesByPeriod->get($periodKey, collect());
                            $periodEvaluations = $evaluationsByPeriod->get($periodKey, collect());

                            $merged = collect()
                                ->merge($periodActivities->map(fn($a) => $a->setAttribute('type','activity')))
                                ->merge($periodQuizzes->map(fn($q) => $q->setAttribute('type','quiz')))
                                ->merge($periodEvaluations->map(fn($e) => $e->setAttribute('type','evaluation')))
                                ->sortBy('position')
                                ->values();
                        @endphp
                        <div x-show="selectedPeriod === '{{ $periodKey }}'" x-cloak>


                            @if($merged->isNotEmpty())
                                <p class="text-base sm:text-xl text-[#373737] dark:text-white font-medium mb-4 mt-2">
                                    {{ $periodLabel }}
                                </p>
                            @else
                                <p class="text-gray-500 dark:text-gray-400 mt-2 text-sm">
                                    No classwork in this grading period.
                                </p>
                            @endif
                            @foreach($merged as $item)
                            {{-- ============================= ACTIVITIES ============================= --}}
                             @if($item->type === 'activity')

                                {{-- Completely hide invisible activities --}}
                                @if($item->visible == 0)
                                    @continue
                                @endif

                                @php
                                    $studentId = Auth::user()->student->id;

                                    $submission = $item->submissions()
                                        ->where('student_id', $studentId)
                                        ->whereNull('deleted_at')
                                        ->latest()
                                        ->first();



                                    $submissionStatus = $submission
                                        ? ($submission->score !== null ? 'graded' : 'submitted')
                                        : 'pending';

                                    $submissionStyles = [
                                        'pending' => [
                                            'label'  => 'Pending',
                                            'bg'     => 'bg-[#E1D6FF]',
                                            'text'   => 'text-primaryColor dark:text-[#A88BFF]',
                                            'dot'    => 'bg-primaryColor dark:bg-[#A88BFF]',
                                            'border' => 'border-primaryColor dark:border-[#A88BFF]',
                                        ],
                                        'submitted' => [
                                            'label'  => 'Submitted',
                                            'bg'     => 'bg-[#FFF4CC] dark:bg-[#3A2F14]',
                                            'text'   => 'text-[#D08700] dark:text-[#F6C453]',
                                            'dot'    => 'bg-[#D08700] dark:bg-[#F6C453]',
                                            'border' => 'border-[#FFD966] dark:border-[#8A6A1E]',
                                        ],
                                        'graded' => [
                                            'label'  => 'Graded',
                                            'bg'     => 'bg-[#D1FAE5] dark:bg-[#064E3B]',
                                            'text'   => 'text-[#047857] dark:text-[#6EE7B7]',
                                            'dot'    => 'bg-[#047857] dark:bg-[#6EE7B7]',
                                            'border' => 'border-[#6EE7B7]',
                                        ],
                                    ];

                                    $submissionUI = $submissionStyles[$submissionStatus];

                                    $now = now();
                                    $duePassed = $item->due_date && $now->greaterThan($item->due_date);

                                    // Status Logic
                                    $statusText = "";
                                    $statusColor = "";

                                    if ($item->is_open == 0 && $item->visible == 1) {
                                        if ($item->open_date) {
                                            $openDatePart = \Carbon\Carbon::parse($item->open_date)->format('Y-m-d');
                                            $openTimePart = $item->open_time
                                                ? \Carbon\Carbon::parse($item->open_time)->format('H:i:s')
                                                : '00:00:00';
                                            $statusText = "Opens " . \Carbon\Carbon::parse(
                                                $openDatePart . ' ' . $openTimePart
                                            )->format('F j, Y \\a\\t h:i A');
                                        } else {
                                            $statusText = "Activity is currently closed";
                                        }

                                        $statusColor = "text-yellow";
                                    } elseif ($item->is_open == 1 && $item->visible == 1) {
                                        if ($item->accepting == 0) {
                                            $statusText = "Not accepting submissions anymore";
                                            $statusColor = "text-red-500";
                                        } elseif ($duePassed && $item->accepting == 1) {
                                            $statusText = "Accepting late submissions";
                                            $statusColor = "text-yellow";
                                        } else {
                                            $statusText = "Deadline " . \Carbon\Carbon::parse(
                                                $item->due_date . ' ' . $item->due_time
                                            )->diffForHumans();

                                            $statusColor = "text-green-500";
                                        }
                                    }

                                    // Disable if closed or not accepting submissions.
                                    $disabled = ($item->is_open == 0) || ((int) $item->accepting === 0);
                                @endphp

                                <div class="
                                    flex flex-col lg:flex-row
                                    border border-l-8 border-l-[#531B8A] dark:border-[#A88BFF]
                                    rounded p-4 w-full mb-4 transition-all
                                    {{ $disabled ? 'opacity-50 pointer-events-none bg-gray-200 dark:bg-[#2A2A3C]' : 'hover:scale-105 cursor-pointer' }}
                                "
                                @if(!$disabled)
                                    onclick="window.location='{{ route('student.classwork.view.activity', [
                                        'section_id' => $section->id,
                                        'subject_id' => $subject->id,
                                        'activity_id' => $item->id
                                    ]) }}'"
                                @endif>

                                    <div class="px-2 py-2">
                                        <div class="rounded-full w-2 h-2 bg-[#531B8A] dark:bg-[#A88BFF]"></div>
                                    </div>

                                    <div class="flex flex-col ml-2 mb-2 gap-0 w-full lg:w-1/2">
                                        <p class="font-semibold text-sm sm:text-lg text-[#373737] dark:text-white">
                                            {{ $item->title }}
                                        </p>

                                        {{-- Submission pill --}}
                                        <div class="flex flex-row px-4 py-2 items-center gap-2 border rounded-xl my-4
                                            w-full sm:w-[70%] md:w-[60%]
                                            {{ $submissionUI['bg'] }} {{ $submissionUI['border'] }}">

                                            <div class="w-[15%] flex items-center justify-center">
                                                <div class="w-2 h-2 rounded-full {{ $submissionUI['dot'] }}"></div>
                                            </div>

                                            <div class="flex items-center justify-center w-full mr-6">
                                                <p class="font-medium text-xs sm:text-base {{ $submissionUI['text'] }}">
                                                    {{ $submissionUI['label'] }}
                                                </p>
                                            </div>
                                        </div>

                                        {{-- STATUS --}}
                                        <div class="flex items-center gap-2 mt-2">
                                            <i class="icofont-info-circle {{ $statusColor }} text-lg"></i>
                                            <p class="text-xs sm:text-sm {{ $statusColor }}">
                                                {{ $statusText }}
                                            </p>
                                        </div>
                                    </div>

                                    {{-- RIGHT SIDE --}}
                                    <div class="w-full lg:w-1/2 mb-2 flex flex-row justify-end mt-4 lg:mt-0">
                                        <div class="flex flex-col w-full items-end">
                                            <div class="px-2 py-2 h-1/2">
                                                <div class="rounded-full w-12 h-12 bg-[#E1D6FF] flex justify-center items-center">
                                                    <i class="icofont-paper text-[#5F2DED] text-2xl"></i>
                                                </div>
                                            </div>

                                            <div class="h-1/2 w-full items-end justify-end flex flex-col pt-2">
                                                <div class="flex justify-center items-center">
                                                    <i class="icofont-clock-time text-lg mr-2 text-[#F2277E]"></i>
                                                    <p class="text-xs sm:text-sm text-[#757575] dark:text-gray-300">
                                                        <span class="font-bold text-black dark:text-white">Date Opened:</span>
                                                        {{ \Carbon\Carbon::parse($item->open_date . ' ' . $item->open_time)->format('h:i A M d, Y') }}
                                                    </p>
                                                </div>

                                                <div class="flex justify-center items-center">
                                                    <i class="icofont-clock-time text-lg mr-2 text-[#F2277E]"></i>
                                                    <p class="font-bold text-black dark:text-white text-xs sm:text-sm">
                                                        Due Date:
                                                        <span class="text-[#373737] dark:text-white font-normal">
                                                            {{ $item->due_date ? \Carbon\Carbon::parse($item->due_date)->format('h:i A M d, Y') : 'Not assigned yet' }}
                                                        </span>
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            

                            {{-- ============================= QUIZZES ============================= --}}
                            @elseif($item->type === 'quiz')
                                @php
                                    if ((int)($item->visible ?? 0) !== 1) continue;

                                    $student = Auth::user()->student;
                                    $response = $item->responses->first();
                                    $quizStatus = $response ? 'attempted' : 'not_attempted';

                                    $quizStyles = [
                                        'not_attempted' => [
                                            'label'  => 'Not Attempted',
                                            'bg'     => 'bg-[#E1D6FF]',
                                            'text'   => 'text-primaryColor dark:text-[#A88BFF]',
                                            'dot'    => 'bg-primaryColor dark:bg-[#A88BFF]',
                                            'border' => 'border-primaryColor dark:border-[#A88BFF]',
                                        ],
                                        'attempted' => [
                                            'label'  => 'Attempted',
                                            'bg'     => 'bg-[#D1FAE5] dark:bg-[#064E3B]',
                                            'text'   => 'text-[#047857] dark:text-[#6EE7B7]',
                                            'dot'    => 'bg-[#047857] dark:bg-[#6EE7B7]',
                                            'border' => 'border-[#6EE7B7]',
                                        ],
                                    ];

                                    $quizUI = $quizStyles[$quizStatus];

                                    $quizSettingRaw = $item->setting ?? [];
                                    if (is_string($quizSettingRaw)) {
                                        $decoded = json_decode($quizSettingRaw, true);
                                        $quizSetting = is_array($decoded) ? $decoded : [];
                                    } elseif (is_array($quizSettingRaw)) {
                                        $quizSetting = $quizSettingRaw;
                                    } else {
                                        $quizSetting = [];
                                    }

                                    $studentCanReview = array_key_exists('student_can_review', $quizSetting)
                                        ? (bool)$quizSetting['student_can_review']
                                        : true;

                                    $studentCanViewScore = array_key_exists('student_can_view_score', $quizSetting)
                                        ? (bool)$quizSetting['student_can_view_score']
                                        : true;

                                    $resultsAvailable = $studentCanReview || $studentCanViewScore;

                                    $now = now();
                                    $isOpen = (int)($item->is_open ?? 0) === 1;
                                    $openAt = null;
                                    if ($item->open_date) {
                                        $datePart = \Carbon\Carbon::parse($item->open_date)->format('Y-m-d');
                                        $timePart = $item->open_time
                                            ? \Carbon\Carbon::parse($item->open_time)->format('H:i:s')
                                            : '00:00:00';
                                        $openAt = \Carbon\Carbon::parse($datePart . ' ' . $timePart);
                                    }
                                    $dueAt = $item->due_date ? \Carbon\Carbon::parse($item->due_date) : null;
                                    $duePassed = $dueAt && $now->greaterThan($dueAt);
                                    $disabled = !$isOpen || ($response && !$resultsAvailable);

                                    $quizStatusText = 'Quiz is open';
                                    $quizStatusColor = 'text-green-500';

                                    if (!$isOpen) {
                                        $quizStatusText = 'Opens ' . ($openAt
                                            ? $openAt->format('F j, Y \\a\\t h:i A')
                                            : 'soon');
                                        $quizStatusColor = 'text-yellow';
                                    } elseif ($duePassed && $item->accepting == 1) {
                                        $quizStatusText = 'Accepting late submissions';
                                        $quizStatusColor = 'text-yellow';
                                    } elseif ($duePassed && $item->accepting == 0) {
                                        $quizStatusText = 'Not accepting submissions anymore';
                                        $quizStatusColor = 'text-red-500';
                                    } else {
                                        $quizStatusText = 'Deadline ' . ($dueAt
                                            ? $dueAt->diffForHumans()
                                            : 'not assigned yet');
                                        $quizStatusColor = 'text-green-500';
                                    }
                                @endphp

                                <div class="
                                    flex flex-col lg:flex-row
                                    border border-l-8 border-l-[#531B8A] dark:border-[#A88BFF]
                                    rounded p-4 w-full mb-4 transition-all
                                    {{ $disabled ? 'opacity-50 pointer-events-none bg-gray-200 dark:bg-[#2A2A3C]' : 'hover:scale-105 cursor-pointer' }}
                                "
                                @if(!$disabled && !$response)
                                    onclick="window.location='{{ route('student.quiz.take', [
                                        'section_id' => $section->id,
                                        'subject_id' => $subject->id,
                                        'quiz_id' => $item->id
                                    ]) }}'"
                                @elseif($response && $resultsAvailable)
                                    onclick="window.location='{{ route('student.classwork.quiz.response', [
                                        'section_id' => $section->id,
                                        'subject_id' => $subject->id,
                                        'quiz_id' => $item->id
                                    ]) }}'"
                                @endif
                                >

                                    <div class="px-2 py-2">
                                        <div class="rounded-full w-2 h-2 bg-[#531B8A] dark:bg-[#A88BFF]"></div>
                                    </div>

                                    <div class="flex flex-col ml-2 mb-2 gap-0 w-full lg:w-1/2">
                                        <p class="font-semibold text-sm sm:text-lg text-[#373737] dark:text-white">
                                            {{ $item->title }}
                                        </p>

                                        <div class="flex flex-row px-4 py-2 items-center gap-2 border rounded-xl my-4
                                            w-full sm:w-[70%] md:w-[60%]
                                            {{ $quizUI['bg'] }} {{ $quizUI['border'] }}">

                                            <div class="w-[15%] flex items-center justify-center">
                                                <div class="w-2 h-2 rounded-full {{ $quizUI['dot'] }}"></div>
                                            </div>

                                            <div class="flex items-center justify-center w-full mr-6">
                                                <p class="font-medium text-xs sm:text-base {{ $quizUI['text'] }}">
                                                    {{ $quizUI['label'] }}
                                                </p>
                                            </div>
                                        </div>

                                        <div class="flex items-center gap-2 mt-2">
                                            <i class="icofont-info-circle {{ $quizStatusColor }} text-lg"></i>
                                            <p class="text-xs sm:text-sm {{ $quizStatusColor }}">
                                                {{ $quizStatusText }}
                                            </p>
                                        </div>

                                        @if($response && !$resultsAvailable)
                                            <p class="text-xs text-red-600 dark:text-red-400 mt-2">
                                                Results are hidden by your teacher.
                                            </p>
                                        @endif
                                    </div>

                                    <div class="w-full lg:w-1/2 mb-2 flex flex-row justify-end mt-4 lg:mt-0">
                                        <div class="flex flex-col w-full items-end">

                                            <div class="px-2 py-2 h-1/2">
                                                <div class="rounded-full w-12 h-12 bg-[#E1D6FF] flex justify-center items-center">
                                                    <i class="icofont-light-bulb text-[#5F2DED] text-2xl"></i>
                                                </div>
                                            </div>

                                            <div class="h-1/2 w-full items-end justify-end flex flex-col pt-2">
                                                <div class="flex justify-center items-center">
                                                    <i class="icofont-clock-time text-lg mr-2 text-[#F2277E]"></i>
                                                    <p class="text-xs sm:text-sm text-[#757575] dark:text-gray-300">
                                                        <span class="font-bold text-black dark:text-white">Date Opened:</span>
                                                        {{ $openAt ? $openAt->format('h:i A M d, Y') : 'Not assigned yet' }}
                                                    </p>
                                                </div>

                                                <div class="flex justify-center items-center">
                                                    <i class="icofont-clock-time text-lg mr-2 text-[#F2277E]"></i>
                                                    <p class="font-bold text-black dark:text-white text-xs sm:text-sm">
                                                        Due Date:
                                                        <span class="text-[#373737] dark:text-white font-normal">
                                                            {{ $item->due_date ? \Carbon\Carbon::parse($item->due_date)->format('h:i A M d, Y') : 'Not assigned yet' }}
                                                        </span>
                                                    </p>
                                                </div>
                                            </div>

                                        </div>
                                    </div>

                                </div>

                            {{-- ============================= EVALUATIONS ============================= --}}
                           @elseif($item->type === 'evaluation')

                                @php
                                    if($item->visible == 0) continue;

                                    $studentId = Auth::user()->student->id;

                                    $studentScore = ($evaluationScores->get($item->id)?->score ?? null);

                                    $studentScore = $studentScore ?? 0;

                                    $totalScore = (int) ($item->total_score ?? 0);

                                    $statusLabel = "Not graded";
                                    $statusBg = "bg-[#E1D6FF]";
                                    $statusText = "text-primaryColor dark:text-[#A88BFF]";
                                    $statusDot = "bg-primaryColor dark:bg-[#A88BFF]";
                                    $statusBorder = "border-primaryColor dark:border-[#A88BFF]";

                                    if ($totalScore > 0 && $studentScore > 0) {
                                        $statusLabel = "Scored";
                                        $statusBg = "bg-[#D1FAE5] dark:bg-[#064E3B]";
                                        $statusText = "text-[#047857] dark:text-[#6EE7B7]";
                                        $statusDot = "bg-[#047857] dark:bg-[#6EE7B7]";
                                        $statusBorder = "border-[#6EE7B7]";
                                    }

                                    $disabled = false;
                                @endphp

                                <div class="
                                    flex flex-col lg:flex-row
                                    border border-l-8 border-l-[#531B8A] dark:border-[#A88BFF]
                                    rounded p-4 w-full mb-4 transition-all
                                    {{ $disabled ? 'opacity-50 pointer-events-none bg-gray-200 dark:bg-[#2A2A3C]' : 'hover:scale-100' }}
                                ">

                                    <div class="px-2 py-2">
                                        <div class="rounded-full w-2 h-2 bg-[#531B8A] dark:bg-[#A88BFF]"></div>
                                    </div>

                                    <div class="flex flex-col ml-2 mb-2 gap-0 w-full lg:w-1/2">
                                        <p class="font-semibold text-sm sm:text-lg text-[#373737] dark:text-white">
                                            {{ $item->title }}
                                        </p>

                                        <div class="flex flex-row px-4 py-2 items-center gap-2 border rounded-xl my-4
                                            w-full sm:w-[70%] md:w-[60%]
                                            {{ $statusBg }} {{ $statusBorder }}">

                                            <div class="w-[15%] flex items-center justify-center">
                                                <div class="w-2 h-2 rounded-full {{ $statusDot }}"></div>
                                            </div>

                                            <div class="flex items-center justify-center w-full mr-6">
                                                <p class="font-medium text-xs sm:text-base {{ $statusText }}">
                                                    {{ $statusLabel }}
                                                </p>
                                            </div>
                                        </div>

                                        <div class="flex items-center gap-2 mt-2">
                                            <i class="icofont-star text-[#F2277E] text-lg"></i>
                                            <p class="text-xs sm:text-sm text-[#757575] dark:text-gray-300">
                                                <span class="font-bold text-black dark:text-white">Score:</span>
                                                {{ $studentScore }} / {{ $totalScore }}
                                            </p>
                                        </div>
                                    </div>

                                    <div class="w-full lg:w-1/2 mb-2 flex flex-row justify-end mt-4 lg:mt-0">
                                        <div class="flex flex-col w-full items-end">

                                            <div class="px-2 py-2 h-1/2">
                                                <div class="rounded-full w-12 h-12 bg-[#E1D6FF] flex justify-center items-center">
                                                    <i class="icofont-ui-rating text-[#5F2DED] text-2xl"></i>
                                                </div>
                                            </div>

                                            <div class="h-1/2 w-full items-end justify-end flex flex-col pt-2">
                                                <div class="flex justify-center items-center">
                                                    <i class="icofont-clock-time text-lg mr-2 text-[#F2277E]"></i>
                                                    <p class="text-xs sm:text-sm text-[#757575] dark:text-gray-300">
                                                        <span class="font-bold text-black dark:text-white">Created:</span>
                                                        {{ \Carbon\Carbon::parse($item->created_at)->format('h:i A M d, Y') }}
                                                    </p>
                                                </div>

                                                <div class="flex justify-center items-center">
                                                    <i class="icofont-clock-time text-lg mr-2 text-[#F2277E]"></i>
                                                    <p class="font-bold text-black dark:text-white text-xs sm:text-sm">
                                                        Due Date:
                                                        <span class="text-[#373737] dark:text-white font-normal">
                                                            {{ $item->due_date ? \Carbon\Carbon::parse($item->due_date)->format('h:i A M d, Y') : 'Not assigned yet' }}
                                                        </span>
                                                    </p>
                                                </div>
                                            </div>

                                        </div>
                                    </div>

                                </div>
                                 @endif
                            @endforeach

                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
