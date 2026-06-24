@extends('pages.student.layout')

@section('content')

@php
    $access = $studentResultAccess ?? [];
    $studentCanReview = (bool)($access['canReview'] ?? true);
    $studentCanViewScore = (bool)($access['canViewScore'] ?? true);
    $showCorrectAnswers = (bool)($access['showCorrectAnswers'] ?? true);

    $quizSettingRaw = $quiz->setting ?? [];
    if (is_string($quizSettingRaw)) {
        $decoded = json_decode($quizSettingRaw, true);
        $quizSetting = is_array($decoded) ? $decoded : [];
    } elseif (is_array($quizSettingRaw)) {
        $quizSetting = $quizSettingRaw;
    } else {
        $quizSetting = [];
    }

    $paginationValue = isset($paginationValue) ? (int) $paginationValue : (int)($quizSetting['pagination'] ?? 10);
    if ($paginationValue < 1) $paginationValue = 1;
    if ($paginationValue > 25) $paginationValue = 25;

    $answerMeta = ($answers ?? collect());
    if ($answerMeta instanceof \Illuminate\Support\Collection) {
        $answerMeta = $answerMeta->keyBy('question_id');
    } else {
        $answerMeta = collect($answerMeta)->keyBy('question_id');
    }

    $responseAnswers = ($response->answers ?? collect());
    if (!($responseAnswers instanceof \Illuminate\Support\Collection)) {
        $responseAnswers = collect($responseAnswers);
    }
    // Prevent mismatched totals when there are orphaned answers (question deleted/unlinked).
    $responseAnswers = $responseAnswers
        ->filter(fn($a) => $a && ($a->question ?? null))
        ->values();
    $totalQuestions = $responseAnswers->count();

    $totalQuizScore = 0;
    foreach ($responseAnswers as $a) {
        $q = $a->question ?? null;
        $p = $q?->points ?? 1;
        $totalQuizScore += is_numeric($p) ? (int) $p : 1;
    }

    $maxOrder = 0;
    foreach ($responseAnswers as $i => $a) {
        $q = $a->question ?? null;
        $maxOrder = max($maxOrder, (int) ($q?->order ?? ($i + 1)));
    }
    $maxOrder = max($maxOrder, $totalQuestions);

    $totalPages = $studentCanReview
        ? ($paginationValue > 0 ? (int) ceil($maxOrder / $paginationValue) : 1)
        : 1;
    if ($totalPages < 1) $totalPages = 1;

    $submittedAt = $response->created_at ? \Carbon\Carbon::parse($response->created_at) : null;
    $quizOpenAt = null;
    if ($quiz->open_date) {
        $datePart = \Carbon\Carbon::parse($quiz->open_date)->format('Y-m-d');
        $timePart = $quiz->open_time
            ? \Carbon\Carbon::parse($quiz->open_time)->format('H:i:s')
            : '00:00:00';
        $quizOpenAt = \Carbon\Carbon::parse($datePart . ' ' . $timePart);
    }
    $dueAt = $quiz->due_date ? \Carbon\Carbon::parse($quiz->due_date) : null;
    $isLate = $submittedAt && $dueAt ? $submittedAt->greaterThan($dueAt) : false;
    $timeSpentSeconds = isset($timeSpentSeconds) ? (int) $timeSpentSeconds : (int) ($response->meta['time_spent_seconds'] ?? 0);
    $timeSpentFormatted = $timeSpentSeconds > 0 ? gmdate('H:i:s', $timeSpentSeconds) : null;

    $normalizeValue = function ($value) use (&$normalizeValue) {
        if (is_bool($value)) return $value ? 'true' : 'false';
        if (is_numeric($value)) return (string) (0 + $value);

        if (is_array($value)) {
            if (array_key_exists('text', $value) && !is_array($value['text'])) {
                return trim((string) $value['text']);
            }
            if (array_key_exists('value', $value) && !is_array($value['value'])) {
                return trim((string) $value['value']);
            }
            if (array_key_exists('id', $value) && !is_array($value['id'])) {
                return trim((string) $value['id']);
            }

            $parts = [];
            foreach ($value as $v) {
                $normalized = $normalizeValue($v);
                if ($normalized !== '') {
                    $parts[] = $normalized;
                }
            }

            return implode(', ', $parts);
        }

        if (is_object($value) && method_exists($value, '__toString')) {
            return trim((string) $value);
        }

        // Avoid "Array to string conversion" notices for non-stringable values
        if (is_object($value)) {
            return '';
        }

        return trim((string) $value);
    };

    $formatAnswerValue = function ($value) use (&$formatAnswerValue) {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? 'True' : 'False';
        }

        if (is_numeric($value)) {
            return (string) (0 + $value);
        }

        if (is_array($value)) {
            if (array_key_exists('text', $value) && !is_array($value['text'])) {
                return trim((string) $value['text']);
            }
            if (array_key_exists('value', $value) && !is_array($value['value'])) {
                return trim((string) $value['value']);
            }
            if (array_key_exists('id', $value) && !is_array($value['id'])) {
                return trim((string) $value['id']);
            }

            $parts = [];
            foreach ($value as $v) {
                $parts[] = $formatAnswerValue($v);
            }
            $parts = array_values(array_filter($parts, fn($s) => $s !== ''));
            return implode(', ', $parts);
        }

        return trim((string) $value);
    };
@endphp

<div class="bg-[#FFFFFF] dark:bg-[#1E1E2D] dark:border-[#5F2DED] shadow-lg border p-8 rounded-lg">
    <div class="border-b border-[#D4D4D8] dark:border-[#5F2DED] flex items-center justify-between py-3">
        <div class="mb-4">
            <div class="flex items-center gap-3">
                <a href="{{ route('student.classwork', [$section->id, $subject->id]) }}"
                   class="flex items-center gap-2"
                   aria-label="Back"
                   title="Back">
                    <i class="icofont-arrow-left text-primaryColor text-4xl mb-2"></i>
                </a>

                <h2 class="text-3xl font-bold text-[#5E5F60] dark:text-white">{{ $quiz->title }}</h2>
            </div>

            <div class="flex flex-col gap-1 text-sm mt-2">
                <div class="flex items-center gap-2">
                    <i class="icofont-clock-time text-green-700 text-lg"></i>
                    <p class="text-gray-600 dark:text-gray-300">
                        <span class="font-semibold">Opened:</span>
                        {{ $quizOpenAt ? $quizOpenAt->format('h:i A F d, Y') : 'Not assigned yet' }}
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <i class="icofont-clock-time text-red-600 text-lg"></i>
                    <p class="text-gray-600 dark:text-gray-300">
                        <span class="font-semibold">Due:</span>
                        {{ $quiz->due_date
                            ? \Carbon\Carbon::parse($quiz->due_date)->format('h:i A F d, Y')
                            : 'No due date' }}
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <i class="icofont-check-circled text-[#5F2DED] text-lg"></i>
                    <p class="text-gray-600 dark:text-gray-300">
                        <span class="font-semibold">Submitted:</span>
                        {{ $submittedAt ? $submittedAt->format('m:d:Y H:i:s') : 'N/A' }}
                        @if($timeSpentFormatted)
                            <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-md text-xs font-semibold bg-gray-100 dark:bg-[#0F1724] text-gray-700 dark:text-gray-200">
                                Time spent: {{ $timeSpentFormatted }}
                            </span>
                        @endif
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <i class="icofont-info-circle text-[#5F2DED] text-lg"></i>
                    <p class="text-gray-600 dark:text-gray-300">
                        <span class="font-semibold">Status:</span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-semibold {{ $isLate ? 'bg-red-100 text-red-700 dark:bg-[#0F1724] dark:text-red-300' : 'bg-green-100 text-green-700 dark:bg-[#0F1724] dark:text-green-300' }}">
                            {{ $isLate ? 'Late' : 'Submitted' }}
                        </span>
                    </p>
                </div>

                @if($quiz->description)
                    <div class="text-gray-600 dark:text-gray-300 mt-1">{!! $quiz->description !!}</div>
                @endif
            </div>
        </div>

        <div class="text-right">
            <div class="text-sm text-gray-500 dark:text-gray-400">Your score</div>
            @if($studentCanViewScore)
                <div class="text-2xl font-bold text-gray-700 dark:text-gray-200">
                    <span class="text-[#F2277E] font-bold">{{ is_numeric($response->score ?? null) ? (int) $response->score : 'N/A' }}</span>
                    <span class="text-gray-500 dark:text-gray-400">/ {{ $totalQuizScore }}</span>
                    <span class="text-gray-500 dark:text-gray-400">pts</span>
                </div>
            @else
                <div class="text-sm text-gray-600 dark:text-gray-300">Score hidden by your teacher.</div>
            @endif
        </div>
    </div>

    <div class="mt-6" data-quiz-response>
        <input type="hidden" value="1" data-current-page>

        @if(!$studentCanReview)
            <div class="p-4 border border-dashed border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-[#0F1724] text-gray-700 dark:text-gray-200">
                Detailed answers are hidden by your teacher.
            </div>
        @else
        @if(!$showCorrectAnswers)
            <div class="mb-4 text-sm text-gray-600 dark:text-gray-300">
                Correct answers are hidden by your teacher. You will only see your submitted responses.
            </div>
        @endif
        <div class="question-wrapper">
            @forelse($responseAnswers as $index => $answerModel)
                @php
                    $question = $answerModel->question;
                    $questionType = (string) ($question->type ?? '');
                    $required = (bool) ($question->required ?? false);
                    $points = is_numeric($question->points ?? null) ? (int) $question->points : 1;

                    $maxPoints = $points;
                    if ($questionType === 'matching') {
                        $settingRaw = $question->setting ?? [];
                        if (is_string($settingRaw)) {
                            $decoded = json_decode($settingRaw, true);
                            if (json_last_error() === JSON_ERROR_NONE) {
                                $settingRaw = $decoded;
                            }
                        }
                        $settingRaw = is_array($settingRaw) ? $settingRaw : [];
                        $pairPoints = $settingRaw['pair_points'] ?? [];
                        $pairPoints = is_array($pairPoints) ? array_values($pairPoints) : [];
                        $hasPairPoints = count(array_filter($pairPoints, fn ($v) => (int) $v > 0)) > 0;
                        $usePairPoints = (bool) ($settingRaw['use_pair_points'] ?? false);
                        if (!$usePairPoints && $hasPairPoints) {
                            $usePairPoints = true;
                        }
                        if ($usePairPoints && $hasPairPoints) {
                            $maxPoints = array_sum(array_map(fn ($v) => max(0, (int) $v), $pairPoints));
                        }
                    }

                    $essayPointsMap = (is_array($response->meta ?? null) && is_array(($response->meta['essay_points'] ?? null)))
                        ? ($response->meta['essay_points'] ?? [])
                        : [];
                    $essayAwardedRaw = ($question && array_key_exists((string) $question->id, $essayPointsMap))
                        ? $essayPointsMap[(string) $question->id]
                        : null;
                    $essayAwarded = is_numeric($essayAwardedRaw) ? (int) $essayAwardedRaw : null;

                    $earnedPoints = null;
                    if ($questionType === 'essay') {
                        $earnedPoints = $essayAwarded;
                    } elseif (method_exists($answerModel, 'scoreValue')) {
                        $earnedPoints = max(0, (int) $answerModel->scoreValue());
                    } elseif (method_exists($answerModel, 'isCorrect')) {
                        $earnedPoints = $answerModel->isCorrect() ? max(0, (int) $points) : 0;
                    }

                    $options = $question->option ?? [];
                    $options = is_string($options) ? json_decode($options, true) : $options;
                    $options = is_array($options) ? $options : [];

                    $answerKey = $question->answer_key ?? [];
                    if (is_string($answerKey)) {
                        $decoded = json_decode($answerKey, true);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            $answerKey = $decoded;
                        }
                    }
                    $answerKey = is_array($answerKey) ? $answerKey : [$answerKey];

                    $userAnswer = $answerModel->answer ?? [];
                    $userAnswer = is_array($userAnswer) ? $userAnswer : [$userAnswer];

                    $normalizeForQuestion = function ($value) use ($normalizeValue, $questionType) {
                        if ($questionType === 'true_false') {
                            if ($value === true || $value === 'true' || $value === 1 || $value === '1') return 'true';
                            if ($value === false || $value === 'false' || $value === 0 || $value === '0') return 'false';
                        }
                        return $normalizeValue($value);
                    };

                    $correctNormalized = $showCorrectAnswers
                        ? array_values(array_unique(array_map($normalizeForQuestion, $answerKey)))
                        : [];
                    $userNormalized = array_values(array_unique(array_map($normalizeForQuestion, $userAnswer)));

                    $isCorrect = $showCorrectAnswers && method_exists($answerModel, 'isCorrect') ? $answerModel->isCorrect() : null;
                    $isEssayGraded = ($questionType === 'essay') && ($essayAwarded !== null);
                    $isEssayCorrect = ($showCorrectAnswers && $isEssayGraded) ? ($essayAwarded > 0) : null;
                    $qOrder = (int) ($question->order ?? ($index + 1));
                    $pageHintRaw = $question->page_hint ?? null;
                    $questionPage = is_numeric($pageHintRaw)
                        ? (int) $pageHintRaw
                        : (int) ceil($qOrder / max(1, $paginationValue));
                    $questionPage = max(1, $questionPage);
                @endphp

                @if($question)
                    <div class="question bg-white dark:bg-[#2A2A3C] shadow-md border border-gray-200 dark:border-gray-600 p-6 rounded-lg mb-6 relative transition-all duration-200 hover:shadow-lg hover:bg-gray-50 dark:hover:bg-[#3A3A4F] {{ $questionPage !== 1 ? 'hidden' : '' }}"
                         data-question-id="{{ $question->id }}"
                         data-question-order="{{ $qOrder }}"
                         data-question-page="{{ $questionPage }}"
                         data-question-type="{{ $questionType }}">

                        <div class="absolute top-4 right-4 text-xs text-gray-500 dark:text-gray-300 text-right">
                            @if($earnedPoints === null)
                                <span class="opacity-80">—</span>
                            @else
                                {{ $earnedPoints }}
                            @endif
                            <span class="opacity-80">/ {{ $maxPoints }}</span>
                            <span class="opacity-80">{{ $maxPoints === 1 ? 'pt' : 'pts' }}</span>
                        </div>

                        <div class="mb-3 flex items-start justify-between gap-4">
                            <div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                    Question {{ $index + 1 }} of {{ $totalQuestions }}
                                    @if($required)
                                        <span class="ml-2 text-pink-500 font-bold" aria-label="Required">*</span>
                                    @endif
                                    @if($earnedPoints !== null)
                                        @php
                                            $isEarnedCorrect = ((int) $earnedPoints) > 0;
                                        @endphp
                                        <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-md text-xs font-semibold {{ $isEarnedCorrect ? 'bg-green-100 text-green-700 dark:bg-[#0F1724] dark:text-green-300' : 'bg-red-100 text-red-700 dark:bg-[#0F1724] dark:text-red-300' }}">
                                            {{ $isEarnedCorrect ? 'Correct' : 'Incorrect' }}
                                        </span>
                                    @endif
                                </div>

                                <h3 class="text-lg font-semibold text-gray-700 dark:text-white mt-2">{!! nl2br(e($question->text)) !!}</h3>
                            </div>
                        </div>

                        @if($questionType === 'multiple_choice')
                            <div class="options mt-3 space-y-2">
                                @foreach($options as $key => $opt)
                                    @php
                                        if (is_array($opt)) {
                                            $optValue = $opt['value'] ?? $opt['id'] ?? ($opt['text'] ?? $key);
                                            $optText = $opt['text'] ?? $optValue;
                                        } else {
                                            $optValue = $opt;
                                            $optText = $opt;
                                        }

                                        $optNorm = $normalizeValue($optValue);
                                        $selected = in_array($optNorm, $userNormalized, true);
                                        $isKey = $showCorrectAnswers && in_array($optNorm, $correctNormalized, true);

                                        $rowClass = '';
                                        if ($isKey) {
                                            $rowClass = 'border border-green-200 bg-green-50 dark:bg-[#0F1724]';
                                        } elseif ($selected) {
                                            $rowClass = $showCorrectAnswers
                                                ? 'border border-red-200 bg-red-50 dark:bg-[#0F1724]'
                                                : 'border border-gray-200 bg-gray-50 dark:border-gray-600 dark:bg-[#0F1724]';
                                        }
                                    @endphp

                                    <div class="block">
                                        <div class="inline-flex max-w-full rounded-md px-3 py-2 {{ $rowClass }}">
                                            <label class="inline-flex items-center gap-3 cursor-default flex-wrap">
                                                <input type="checkbox"
                                                       disabled
                                                       {{ $selected ? 'checked' : '' }}
                                                       class="quiz-choice-input">
                                                <span class="text-gray-700 dark:text-gray-200 break-words">{{ $formatAnswerValue($optText) }}</span>

                                                <span class="flex items-center gap-2 text-xs">
                                                    @if($isKey)
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md font-semibold bg-green-100 text-green-700 dark:bg-[#1E1E2D] dark:text-green-300">Correct</span>
                                                    @endif
                                                    @if($selected)
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md font-semibold bg-gray-100 text-gray-700 dark:bg-[#1E1E2D] dark:text-gray-200">Your answer</span>
                                                    @endif
                                                </span>
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @elseif($questionType === 'true_false')
                            @php
                                $tfOptions = ['true' => 'True', 'false' => 'False'];
                            @endphp
                            <div class="mt-3 space-y-2">
                                @foreach($tfOptions as $val => $label)
                                    @php
                                        $valNorm = $normalizeValue($val);
                                        $selected = in_array($valNorm, $userNormalized, true);
                                        $isKey = $showCorrectAnswers && in_array($valNorm, $correctNormalized, true);
                                        $rowClass = '';
                                        if ($isKey) {
                                            $rowClass = 'border border-green-200 bg-green-50 dark:bg-[#0F1724]';
                                        } elseif ($selected) {
                                            $rowClass = $showCorrectAnswers
                                                ? 'border border-red-200 bg-red-50 dark:bg-[#0F1724]'
                                                : 'border border-gray-200 bg-gray-50 dark:border-gray-600 dark:bg-[#0F1724]';
                                        }
                                    @endphp
                                    <div class="block">
                                        <div class="inline-flex max-w-full rounded-md px-3 py-2 {{ $rowClass }}">
                                            <label class="inline-flex items-center gap-2 text-gray-700 dark:text-gray-200 cursor-default flex-wrap">
                                                <input type="radio" disabled {{ $selected ? 'checked' : '' }} class="form-radio text-[#5F2DED] focus:ring-[#5F2DED]">
                                                <span>{{ $label }}</span>

                                                <span class="flex items-center gap-2 text-xs">
                                                    @if($isKey)
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md font-semibold bg-green-100 text-green-700 dark:bg-[#1E1E2D] dark:text-green-300">Correct</span>
                                                    @endif
                                                    @if($selected)
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md font-semibold bg-gray-100 text-gray-700 dark:bg-[#1E1E2D] dark:text-gray-200">Your answer</span>
                                                    @endif
                                                </span>
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @elseif($questionType === 'matching')
                            @php
                                // Match the "Answer Key" grid/table style used when building matching questions.
                                // Options may be stored as:
                                // - NEW: { left: [...], right: [...] }
                                // - LEGACY: [ {left: '...', right: '...'}, ... ]
                                $matchingLeft = [];
                                $matchingRight = [];

                                if (is_array($options) && array_key_exists('left', $options) && array_key_exists('right', $options)) {
                                    $matchingLeft = is_array($options['left']) ? array_values($options['left']) : [];
                                    $matchingRight = is_array($options['right']) ? array_values($options['right']) : [];
                                } elseif (is_array($options)) {
                                    $pairs = array_values(array_filter($options, fn($p) => is_array($p) && (isset($p['left']) || isset($p['right']))));
                                    $matchingLeft = array_values(array_unique(array_map(fn($p) => (string) ($p['left'] ?? ''), $pairs)));
                                    $matchingRight = array_values(array_unique(array_map(fn($p) => (string) ($p['right'] ?? ''), $pairs)));
                                }

                                $matchingLeft = array_values(array_filter(array_map(fn($v) => trim((string) $v), $matchingLeft), fn($v) => $v !== ''));
                                $matchingRight = array_values(array_filter(array_map(fn($v) => trim((string) $v), $matchingRight), fn($v) => $v !== ''));

                                $userPairsRaw = is_array($userAnswer) ? array_values($userAnswer) : [];
                                $correctPairsRaw = is_array($answerKey) ? array_values($answerKey) : [];

                                $formatPairs = function ($pairs) {
                                    $pairs = is_array($pairs) ? array_values($pairs) : [];
                                    $out = [];
                                    foreach ($pairs as $p) {
                                        if (!is_array($p)) continue;
                                        $l = trim((string) ($p['left'] ?? ''));
                                        $r = trim((string) ($p['right'] ?? ''));
                                        if ($l === '' || $r === '') continue;
                                        $out[] = $l . ' -> ' . $r;
                                    }
                                    return $out;
                                };

                                $userPairsDisplay = $formatPairs($userPairsRaw);
                                $correctPairsDisplay = $showCorrectAnswers ? $formatPairs($correctPairsRaw) : [];

                                // Map: rowIndex => colIndex
                                $buildMap = function ($pairs) use ($matchingLeft, $matchingRight) {
                                    $map = [];
                                    foreach ($pairs as $p) {
                                        if (!is_array($p)) continue;
                                        $l = trim((string) ($p['left'] ?? ''));
                                        $r = trim((string) ($p['right'] ?? ''));
                                        if ($l === '' || $r === '') continue;
                                        $rowIndex = array_search($l, $matchingLeft, true);
                                        $colIndex = array_search($r, $matchingRight, true);
                                        if ($rowIndex === false || $colIndex === false) continue;
                                        $map[(int) $rowIndex] = (int) $colIndex;
                                    }
                                    return $map;
                                };

                                $userMap = $buildMap($userPairsRaw);
                                $correctMap = $showCorrectAnswers ? $buildMap($correctPairsRaw) : [];
                            @endphp
                            <div class="mt-3">
                                @if(empty($matchingLeft) || empty($matchingRight))
                                    <div class="text-gray-600 dark:text-gray-300">No matching options configured for this question.</div>
                                @else
                                    <div class="overflow-x-auto">
                                        <table class="min-w-full text-sm border border-gray-200 dark:border-gray-600 border-collapse">
                                            <thead>
                                                <tr>
                                                    <th class="text-left font-semibold text-gray-700 dark:text-gray-300 whitespace-nowrap p-2 border-b border-gray-200 dark:border-gray-600"></th>
                                                    @foreach($matchingRight as $colIndex => $rightText)
                                                        <th class="text-center font-semibold text-gray-700 dark:text-gray-300 whitespace-nowrap p-2 border-b border-gray-200 dark:border-gray-600 min-w-[3rem]">
                                                            {{ \Illuminate\Support\Str::limit((string)($rightText ?: ('Column ' . ($colIndex + 1))), 12) }}
                                                        </th>
                                                    @endforeach
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($matchingLeft as $rowIndex => $leftText)
                                                    @php
                                                        $userCol = array_key_exists($rowIndex, $userMap) ? (int) $userMap[$rowIndex] : null;
                                                        $correctCol = array_key_exists($rowIndex, $correctMap) ? (int) $correctMap[$rowIndex] : null;
                                                    @endphp
                                                    <tr>
                                                        <td class="font-medium text-gray-700 dark:text-gray-300 whitespace-nowrap p-2 border-b border-gray-200 dark:border-gray-600">
                                                            {{ \Illuminate\Support\Str::limit((string)($leftText ?: ('Row ' . ($rowIndex + 1))), 12) }}
                                                        </td>

                                                        @foreach($matchingRight as $colIndex => $rightText)
                                                            @php
                                                                $isUser = ($userCol !== null && $userCol === (int) $colIndex);
                                                                $isCorrectCell = ($correctCol !== null && $correctCol === (int) $colIndex);

                                                                // Cell styles:
                                                                // - User+Correct: strong green
                                                                // - User wrong: red
                                                                // - Correct (not chosen): low-opacity green fill
                                                                $cellClass = '';
                                                                if ($isUser && $isCorrectCell) {
                                                                    $cellClass = 'bg-green-300 text-green-900 font-semibold shadow-sm ring-1 ring-green-500 dark:bg-green-900/50 dark:text-green-100 dark:ring-green-500';
                                                                } elseif ($isUser && !$isCorrectCell) {
                                                                    $cellClass = $showCorrectAnswers
                                                                        ? 'bg-red-200 text-red-900 font-semibold shadow-sm ring-1 ring-red-400 dark:bg-red-900/40 dark:text-red-100 dark:ring-red-500'
                                                                        : 'bg-gray-200 text-gray-900 font-semibold shadow-sm ring-1 ring-gray-300 dark:bg-gray-800 dark:text-gray-100 dark:ring-gray-500';
                                                                } elseif (!$isUser && $isCorrectCell && $showCorrectAnswers) {
                                                                    $cellClass = 'bg-green-200 text-green-900 ring-1 ring-green-400 opacity-60 dark:bg-green-900/30 dark:text-green-100 dark:ring-green-500';
                                                                }

                                                                $radioClass = 'h-4 w-4 rounded-full border-2 bg-white pointer-events-none';
                                                                if ($isUser && $isCorrectCell) {
                                                                    $radioClass .= ' border-green-600 text-green-700 focus:ring-2 focus:ring-green-500';
                                                                } elseif ($isUser && !$isCorrectCell && $showCorrectAnswers) {
                                                                    $radioClass .= ' border-red-500 text-red-700 focus:ring-2 focus:ring-red-500';
                                                                } else {
                                                                    $radioClass .= ' border-green-500 text-green-700 focus:ring-2 focus:ring-green-500';
                                                                }
                                                            @endphp

                                                            <td class="p-2 border-b border-gray-200 dark:border-gray-600 text-center align-middle min-w-[3rem]">
                                                                <button type="button"
                                                                        disabled
                                                                        class="w-6 h-6 rounded-full bg-transparent inline-flex items-center justify-center mx-auto cursor-default {{ $cellClass }}"
                                                                        aria-label="Selection">
                                                                    <input
                                                                        type="radio"
                                                                        tabindex="-1"
                                                                        disabled
                                                                        class="{{ $radioClass }}"
                                                                        {{ $isUser ? 'checked' : '' }}
                                                                    >
                                                                </button>
                                                            </td>
                                                        @endforeach
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>

                                @endif
                            </div>
                        @elseif($questionType === 'short_answer')
                            @php
                                $val = count($userAnswer) ? $formatAnswerValue($userAnswer[0] ?? '') : '';
                                $correctText = count($answerKey) ? $formatAnswerValue($answerKey[0] ?? '') : '';
                            @endphp
                            <div class="mt-3">
                                <input type="text"
                                       readonly
                                       value="{{ $val }}"
                                       class="w-full max-w-md p-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-[#1E1E2D] text-gray-800 dark:text-white">

                                @if($showCorrectAnswers && $correctText !== '')
                                    <div class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                                        <span class="font-semibold">Correct answer:</span> {{ $correctText }}
                                    </div>
                                @endif
                            </div>
                        @elseif($questionType === 'numerical')
                            @php
                                $val = count($userAnswer) ? $formatAnswerValue($userAnswer[0] ?? '') : '';
                                $correctText = count($answerKey) ? $formatAnswerValue($answerKey[0] ?? '') : '';
                            @endphp
                            <div class="mt-3">
                                <input type="text"
                                       readonly
                                       value="{{ $val }}"
                                       class="w-full max-w-sm p-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-[#1E1E2D] text-gray-800 dark:text-white">

                                @if($showCorrectAnswers && $correctText !== '')
                                    <div class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                                        <span class="font-semibold">Correct answer:</span> {{ $correctText }}
                                    </div>
                                @endif
                            </div>
                        @else
                            @php
                                $val = count($userAnswer) ? $formatAnswerValue($userAnswer[0] ?? '') : '';
                                $correctText = count($answerKey) ? implode(', ', array_map($formatAnswerValue, $answerKey)) : '';
                            @endphp
                            <div class="mt-3">
                                <textarea rows="4"
                                          readonly
                                          class="w-full p-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-[#1E1E2D] text-gray-800 dark:text-white">{{ $val }}</textarea>

                                @if($showCorrectAnswers && $correctText !== '')
                                    <div class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                                        <span class="font-semibold">Correct answer:</span> {{ $correctText }}
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                @endif
            @empty
                <div class="text-center text-gray-500 dark:text-gray-400 p-6">No questions found for this submission.</div>
            @endforelse
        </div>
        @endif

        @if($totalPages > 1)
            <div class="mt-6 relative flex items-center gap-3">
                <div class="absolute left-1/2 -translate-x-1/2 flex items-center gap-2" data-pagination>
                    <button type="button"
                            class="px-3 py-1 text-sm rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-[#1E1E2D] text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-[#2A2A3C]"
                            aria-label="Previous"
                            title="Previous"
                            data-prev-page>
                        &larr;
                    </button>

                    <div class="flex items-center gap-1" data-page-buttons>
                        @for($p = 1; $p <= $totalPages; $p++)
                            <button type="button"
                                    class="w-8 h-8 text-sm rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-[#1E1E2D] text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-[#2A2A3C]"
                                    data-page="{{ $p }}">{{ $p }}</button>
                        @endfor
                    </div>

                    <button type="button"
                            class="px-3 py-1 text-sm rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-[#1E1E2D] text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-[#2A2A3C]"
                            aria-label="Next"
                            title="Next"
                            data-next-page>
                        &rarr;
                    </button>
                </div>
            </div>
        @endif
    </div>
</div>

<script>
    (function () {
        const root = document.querySelector('[data-quiz-response]');
        if (!root) return;

        const pageSize = Number(@json($paginationValue)) || 10;
        const totalPages = Number(@json($totalPages)) || 1;
        if (totalPages <= 1) return;

        const currentPageInput = root.querySelector('[data-current-page]');
        const questions = Array.from(root.querySelectorAll('.question[data-question-order]'));
        const prevBtn = root.querySelector('[data-prev-page]');
        const nextBtn = root.querySelector('[data-next-page]');
        const pageButtonsWrap = root.querySelector('[data-page-buttons]');

        const clampPage = (p) => Math.min(Math.max(1, p), totalPages);
        const pageOfOrder = (order) => {
            const n = Number(order) || 1;
            const size = pageSize || 10;
            return Math.floor((Math.max(1, n) - 1) / size) + 1;
        };
        const getQuestionPage = (q) => {
            const attr = q.getAttribute('data-question-page');
            if (attr) {
                const parsed = parseInt(attr, 10);
                if (!Number.isNaN(parsed)) return parsed;
            }
            return pageOfOrder(q.getAttribute('data-question-order'));
        };

        let currentPage = clampPage(parseInt(currentPageInput?.value || '1', 10) || 1);

        const setActivePageButton = () => {
            if (!pageButtonsWrap) return;
            const buttons = Array.from(pageButtonsWrap.querySelectorAll('[data-page]'));
            buttons.forEach((btn) => {
                const p = parseInt(btn.getAttribute('data-page') || '0', 10);
                if (p === currentPage) {
                    btn.classList.remove('border', 'border-gray-300', 'dark:border-gray-600', 'bg-white', 'dark:bg-[#1E1E2D]', 'text-gray-700', 'dark:text-gray-200', 'hover:bg-gray-100', 'dark:hover:bg-[#2A2A3C]');
                    btn.classList.add('bg-[#5F2DED]', 'text-white');
                } else {
                    btn.classList.remove('bg-[#5F2DED]', 'text-white');
                    btn.classList.add('border', 'border-gray-300', 'dark:border-gray-600', 'bg-white', 'dark:bg-[#1E1E2D]', 'text-gray-700', 'dark:text-gray-200', 'hover:bg-gray-100', 'dark:hover:bg-[#2A2A3C]');
                }
            });
        };

        const updatePagerDisabled = () => {
            if (prevBtn) {
                const disabled = currentPage <= 1;
                prevBtn.disabled = disabled;
                prevBtn.classList.toggle('opacity-50', disabled);
                prevBtn.classList.toggle('cursor-not-allowed', disabled);
            }
            if (nextBtn) {
                const disabled = currentPage >= totalPages;
                nextBtn.disabled = disabled;
                nextBtn.classList.toggle('opacity-50', disabled);
                nextBtn.classList.toggle('cursor-not-allowed', disabled);
            }
        };

        const showPage = (page) => {
            currentPage = clampPage(page);
            if (currentPageInput) currentPageInput.value = String(currentPage);
            questions.forEach((q) => {
                const qPage = getQuestionPage(q);
                q.classList.toggle('hidden', qPage !== currentPage);
            });
            setActivePageButton();
            updatePagerDisabled();
        };

        if (pageButtonsWrap) {
            pageButtonsWrap.addEventListener('click', (e) => {
                const btn = e.target.closest('[data-page]');
                if (!btn) return;
                const p = parseInt(btn.getAttribute('data-page') || '1', 10);
                showPage(p);
            });
        }

        if (prevBtn) {
            prevBtn.addEventListener('click', () => showPage(currentPage - 1));
        }
        if (nextBtn) {
            nextBtn.addEventListener('click', () => showPage(currentPage + 1));
        }

        showPage(currentPage);
    })();
</script>

@endsection
