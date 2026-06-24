@extends('pages.teacher.layout')

@section('content')

@php
    $quizSetting = is_array($quiz->setting ?? null) ? ($quiz->setting ?? []) : [];
    $paginationValue = (int)($quizSetting['pagination'] ?? 10);
    if ($paginationValue < 1) $paginationValue = 1;
    if ($paginationValue > 25) $paginationValue = 25;

    $responseAnswers = ($response->answers ?? collect());
    if (!($responseAnswers instanceof \Illuminate\Support\Collection)) {
        $responseAnswers = collect($responseAnswers);
    }
    // Prevent mismatched totals when there are orphaned answers (question deleted/unlinked).
    $responseAnswers = $responseAnswers
        ->filter(fn($a) => $a && ($a->question ?? null))
        ->values();
    $totalQuestions = $responseAnswers->count();

    $totalQuizScore = is_numeric($quiz->total_score ?? null) ? (int) $quiz->total_score : 0;
    if ($totalQuizScore <= 0) {
        $totalQuizScore = $quiz->questions()->get()->sum(function ($q) {
            return is_numeric($q->points ?? null) ? (int) $q->points : 1;
        });
    }

    $maxOrder = 0;
    foreach ($responseAnswers as $i => $a) {
        $q = $a->question ?? null;
        $maxOrder = max($maxOrder, (int) ($q?->order ?? ($i + 1)));
    }
    $maxOrder = max($maxOrder, $totalQuestions);

    $totalPages = $paginationValue > 0 ? (int) ceil($maxOrder / $paginationValue) : 1;
    if ($totalPages < 1) $totalPages = 1;

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

    $timeSpentSeconds = isset($timeSpentSeconds) ? (int) $timeSpentSeconds : (int) ($response->meta['time_spent_seconds'] ?? 0);
@endphp

<div class="bg-[#FFFFFF] dark:bg-[#1E1E2D] dark:border-[#5F2DED] shadow-lg border p-8 rounded-lg">
    <div class="pb-4 flex flex-wrap gap-3 items-center">
        <button onclick="window.location='{{ route('teacher.quiz.submissions', [$section->id, $subject->id, $quiz->id]) }}'" class="flex items-center gap-2">
            <i class="icofont-arrow-left text-primaryColor text-3xl"></i>
        </button>

        <x-quiz-nav
            mode="view"
            active="submissions"
            :section-id="$section->id"
            :subject-id="$subject->id"
            :quiz-id="$quiz->id"
        />

        <div class="ml-auto flex items-center gap-2">
            <div class="flex gap-2">
                <a href="{{ $prevStudent ? route('teacher.quiz.submissions.show', [$section->id, $subject->id, $quiz->id, $prevStudent->id]) : '#' }}"
                   class="px-3 py-1 border rounded-lg text-sm {{ $prevStudent ? 'text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-[#2A2A3C]' : 'text-gray-400 cursor-not-allowed' }}"
                   aria-disabled="{{ $prevStudent ? 'false' : 'true' }}">
                    &larr; Prev
                </a>
                <a href="{{ $nextStudent ? route('teacher.quiz.submissions.show', [$section->id, $subject->id, $quiz->id, $nextStudent->id]) : '#' }}"
                   class="px-3 py-1 border rounded-lg text-sm {{ $nextStudent ? 'text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-[#2A2A3C]' : 'text-gray-400 cursor-not-allowed' }}"
                   aria-disabled="{{ $nextStudent ? 'false' : 'true' }}">
                    Next &rarr;
                </a>
            </div>
        </div>
    </div>

    <div class="space-y-4">
        <form method="POST" id="scoreAdjustForm" action="{{ route('teacher.quiz.submissions.update', [$section->id, $subject->id, $quiz->id, $student->id]) }}">
            @csrf

            <div class="border border-gray-200 dark:border-gray-600 rounded-lg p-4 bg-white dark:bg-[#2A2A3C] shadow-sm">
                @php
                    $initialDisplayedScore = $response ? (int) ($response->score ?? 0) : 0;
                @endphp

                <div class="flex items-start justify-between gap-3 border-b border-gray-200 dark:border-gray-600 pb-3">
                        <div class="flex flex-col gap-1">
                            <h2 class="text-2xl font-bold text-gray-800 dark:text-white">{{ $student->last_name }}, {{ $student->first_name }}</h2>
                            <p class="text-sm text-gray-500 dark:text-gray-300">{{ $section->section_name }} - {{ $subject->subject_name }}</p>
                        </div>

                        <div class="flex flex-col items-end gap-1" data-score-card>
                            <div class="text-xs text-gray-500 dark:text-gray-300">Score</div>

                            <div class="flex items-center gap-2">
                                <button type="button"
                                        class="w-8 h-8 flex items-center justify-center rounded-full text-sm text-gray-700 dark:text-gray-200 bg-transparent hover:bg-gray-100 dark:hover:bg-[#2A2A3C] active:bg-gray-200 dark:active:bg-[#2A2A3C]"
                                        aria-label="Decrease score"
                                        title="Decrease score"
                                        data-score-dec>
                                    &minus;
                                </button>

                                <div class="text-3xl font-bold">
                                    <span class="cursor-text text-[#F2277E]" data-score-display>{{ $initialDisplayedScore }}</span>
                                    <input type="number"
                                           class="hidden w-16 text-2xl font-semibold bg-transparent border border-transparent rounded-md px-1 py-0 text-[#F2277E] focus:outline-none focus:border-gray-300 dark:focus:border-gray-600"
                                           data-score-input
                                           inputmode="numeric"
                                           min="0"
                                           max="{{ $totalQuizScore }}"
                                           step="1"
                                           value="{{ $initialDisplayedScore }}" />
                                    <span class="text-gray-500 dark:text-gray-300 text-lg font-normal">/ {{ $totalQuizScore }}</span>
                                </div>

                                <button type="button"
                                        class="w-8 h-8 flex items-center justify-center rounded-full text-sm text-gray-700 dark:text-gray-200 bg-transparent hover:bg-gray-100 dark:hover:bg-[#2A2A3C] active:bg-gray-200 dark:active:bg-[#2A2A3C]"
                                        aria-label="Increase score"
                                        title="Increase score"
                                        data-score-inc>
                                    +
                                </button>

                                <input type="hidden" value="{{ $initialDisplayedScore }}" data-override-score data-override-name="override_score">
                            </div>
                        </div>
                    </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-4 text-sm text-gray-700 dark:text-gray-200">
                    <div class="flex flex-col">
                            <div class="flex items-center gap-2">
                                <i class="icofont-clock-time text-green-700 text-lg"></i>
                                <span class="font-semibold">Opened:</span>
                                <span>{{ \Carbon\Carbon::parse($quiz->created_at)->format('h:i A F d, Y') }}</span>
                            </div>
                            <div class="flex items-center gap-2 mt-1">
                                <i class="icofont-clock-time text-red-600 text-lg"></i>
                                <span class="font-semibold">Due:</span>
                                <span>
                                    {{ $quiz->due_date
                                        ? \Carbon\Carbon::parse($quiz->due_date)->format('h:i A F d, Y')
                                        : 'No due date' }}
                                </span>
                            </div>
                    </div>
                    <div class="flex flex-col">
                            <div class="flex items-center gap-2">
                                <i class="icofont-check-circled text-[#5F2DED] text-lg"></i>
                                <span class="font-semibold">Submitted:</span>
                                <span>
                                    {{ $response && $submittedAt ? $submittedAt->format('m/d/Y H:i:s') : 'N/A' }}
                                </span>
                                <span class="text-gray-400 dark:text-gray-500">|</span>
                                <span class="font-semibold">Time spent:</span>
                                <span>
                                    @if($timeSpentSeconds && $timeSpentSeconds > 0)
                                        {{ gmdate('H:i:s', $timeSpentSeconds) }}
                                    @elseif($response)
                                        Not tracked
                                    @else
                                        N/A
                                    @endif
                                </span>
                            </div>

                            <div class="flex items-center gap-2 mt-1">
                                <i class="icofont-info-circle text-[#5F2DED] text-lg"></i>
                                <span class="font-semibold">Status:</span>

                                <div>
                                    @if(!$response)
                                        <span class="px-2 py-0.5 rounded-md bg-gray-100 text-gray-700 dark:bg-[#0F1724] dark:text-gray-200">No submission</span>
                                    @elseif($isLate)
                                        <span class="px-2 py-0.5 rounded-md bg-red-100 text-red-700 dark:bg-[#0F1724] dark:text-red-300">Late</span>
                                    @else
                                        <span class="px-2 py-0.5 rounded-md bg-green-100 text-green-700 dark:bg-[#0F1724] dark:text-green-300">Submitted</span>
                                    @endif
                                </div>
                            </div>
                    </div>

                </div>

                <div class="mt-3 flex justify-end">
                    <button type="submit" class="px-4 py-2 bg-[#5F2DED] text-white rounded-lg hover:opacity-90 transition">Save</button>
                </div>
            </div>

            <div class="hidden fixed inset-0 z-50" data-save-modal>
                <div class="absolute inset-0 bg-black/50"></div>
                <div class="relative w-full h-full flex items-center justify-center p-4">
                    <div class="w-full max-w-md rounded-lg bg-white dark:bg-[#2A2A3C] border border-gray-200 dark:border-gray-600 shadow-lg p-5">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-white">Confirm Save</h3>
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">Save changes to this submission?</p>

                        <div class="mt-4 flex justify-end gap-2">
                            <button type="button" class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-[#1E1E2D]" data-save-cancel>
                                Cancel
                            </button>
                            <button type="button"
                                    class="px-4 py-2 rounded-lg bg-[#5F2DED] text-white hover:opacity-90"
                                    data-save-confirm
                                    data-loading-text="Saving...">
                                Confirm
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="border border-gray-200 dark:border-gray-600 rounded-lg p-6 bg-white dark:bg-[#2A2A3C] shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-white">Student Answers</h3>
                    </div>
                    <div class="text-sm text-gray-500 dark:text-gray-300">
                        Page size: {{ $paginationValue }} - Total questions: {{ $totalQuestions }}
                    </div>
                </div>

                @if(!$response)
                    <div class="text-gray-600 dark:text-gray-300">No submission from this student yet.</div>
                @else
                    <div class="question-wrapper">
                        <input type="hidden" value="1" data-current-page>
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

                                $earnedPoints = null;
                                if ($questionType === 'essay') {
                                    // Use teacher-entered points when graded; otherwise show pending.
                                    $earnedPoints = null;
                                } elseif (method_exists($answerModel, 'scoreValue')) {
                                    $earnedPoints = max(0, (int) $answerModel->scoreValue());
                                } elseif (method_exists($answerModel, 'isCorrect')) {
                                    $earnedPoints = $answerModel->isCorrect() ? max(0, (int) $points) : 0;
                                }

                                $essayAwardedRaw = ($question && is_array($essayPoints ?? null) && array_key_exists((string) $question->id, $essayPoints))
                                    ? $essayPoints[(string) $question->id]
                                    : null;
                                $essayAwarded = is_numeric($essayAwardedRaw) ? (int) $essayAwardedRaw : null;
                                if ($questionType === 'essay') {
                                    $earnedPoints = $essayAwarded;
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

                                    $correctNormalized = array_values(array_unique(array_map($normalizeForQuestion, $answerKey)));
                                    $userNormalized = array_values(array_unique(array_map($normalizeForQuestion, $userAnswer)));

                                    $isCorrect = method_exists($answerModel, 'isCorrect') ? $answerModel->isCorrect() : null;
                                    $isEssayGraded = ($questionType === 'essay') && ($essayAwarded !== null);
                                    $isEssayCorrect = $isEssayGraded ? ($essayAwarded > 0) : null;
                                    $displayCorrect = ($questionType === 'essay' && $isEssayCorrect !== null)
                                        ? $isEssayCorrect
                                        : $isCorrect;
                                    $qOrder = (int) ($question->order ?? ($index + 1));
                                @endphp

                                @if($question)
                                    <div class="question bg-white dark:bg-[#2A2A3C] shadow-md border border-gray-200 dark:border-gray-600 p-6 rounded-lg mb-6 relative transition-all duration-200 hover:shadow-lg hover:bg-gray-50 dark:hover:bg-[#3A3A4F]"
                                         data-question-id="{{ $question->id }}"
                                         data-question-order="{{ $qOrder }}"
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
                                                        $isKey = in_array($optNorm, $correctNormalized, true);

                                                        $rowClass = '';
                                                        if ($isKey) {
                                                            $rowClass = 'border border-green-200 bg-green-50 dark:bg-[#0F1724]';
                                                        } elseif ($selected) {
                                                            $rowClass = 'border border-red-200 bg-red-50 dark:bg-[#0F1724]';
                                                        }
                                                    @endphp

                                                    <div class="block">
                                                        <div class="inline-flex max-w-full rounded-md px-3 py-2 {{ $rowClass }}">
                                                            <label class="inline-flex items-center gap-3 cursor-default flex-wrap">
                                                                <input type="checkbox"
                                                                       disabled
                                                                       {{ $selected ? 'checked' : '' }}
                                                                       class="form-checkbox text-[#5F2DED] focus:ring-[#5F2DED]">
                                                                <span class="text-gray-700 dark:text-gray-200 break-words">{{ $formatAnswerValue($optText) }}</span>

                                                                <span class="flex items-center gap-2 text-xs">
                                                                    @if($isKey)
                                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md font-semibold bg-green-100 text-green-700 dark:bg-[#1E1E2D] dark:text-green-300">Correct</span>
                                                                    @endif
                                                                    @if($selected)
                                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md font-semibold bg-gray-100 text-gray-700 dark:bg-[#1E1E2D] dark:text-gray-200">Student answer</span>
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
                                                        $isKey = in_array($valNorm, $correctNormalized, true);
                                                        $rowClass = '';
                                                        if ($isKey) {
                                                            $rowClass = 'border border-green-200 bg-green-50 dark:bg-[#0F1724]';
                                                        } elseif ($selected) {
                                                            $rowClass = 'border border-red-200 bg-red-50 dark:bg-[#0F1724]';
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
                                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md font-semibold bg-gray-100 text-gray-700 dark:bg-[#1E1E2D] dark:text-gray-200">Student answer</span>
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
                                                $correctMap = $buildMap($correctPairsRaw);
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
                                                                                // - Correct (not chosen): subtle green outline
                                                                                $cellClass = '';
                                                                                if ($isUser && $isCorrectCell) {
                                                                                    $cellClass = 'bg-green-300 text-green-900 font-semibold shadow-sm ring-1 ring-green-500 dark:bg-green-900/50 dark:text-green-100 dark:ring-green-500';
                                                                                } elseif ($isUser && !$isCorrectCell) {
                                                                                    $cellClass = 'bg-red-200 text-red-900 font-semibold shadow-sm ring-1 ring-red-400 dark:bg-red-900/40 dark:text-red-100 dark:ring-red-500';
                                                                                } elseif (!$isUser && $isCorrectCell) {
                                                                                    $cellClass = 'ring-1 ring-green-500 dark:ring-green-500';
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
                                                                                        class="h-4 w-4 rounded-full border-2 border-green-500 text-green-700 bg-white focus:ring-2 focus:ring-green-500 pointer-events-none"
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

                                                @if($correctText !== '')
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

                                                @if($correctText !== '')
                                                    <div class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                                                        <span class="font-semibold">Correct answer:</span> {{ $correctText }}
                                                    </div>
                                                @endif
                                            </div>
                                        @elseif($questionType === 'essay')
                                            @php
                                                $val = count($userAnswer) ? $formatAnswerValue($userAnswer[0] ?? '') : '';
                                                $essayScore = $essayPoints[$question->id] ?? '';
                                            @endphp
                                            <div class="mt-3 space-y-2">
                                                <textarea rows="4"
                                                          readonly
                                                          class="w-full p-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-[#1E1E2D] text-gray-800 dark:text-white">{{ $val }}</textarea>
                                                <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
                                                    <span class="font-semibold">Points</span>
                                                    <input type="number"
                                                           step="1"
                                                           min="0"
                                                           max="{{ $points }}"
                                                           inputmode="numeric"
                                                           pattern="\d*"
                                                           name="essay_points[{{ $question->id }}]"
                                                           form="scoreAdjustForm"
                                                           value="{{ $essayScore !== '' ? $essayScore : '' }}"
                                                           placeholder="0"
                                                           data-essay-points
                                                           data-essay-max="{{ $points }}"
                                                           class="w-12 p-1 text-xs text-center border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-[#1E1E2D] text-gray-800 dark:text-white focus:ring-2 focus:ring-purple-400 focus:outline-none">
                                                </label>
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

                                                @if($correctText !== '')
                                                    <div class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                                                        <span class="font-semibold">Correct answer:</span> {{ $correctText }}
                                                    </div>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            @empty
                                <div class="text-gray-600 dark:text-gray-300">No questions found for this submission.</div>
                            @endforelse
                        </div>

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
                @endif
            </div>
        </form>
    </div>
</div>

@if($response)
<script>
    (function () {
        const root = document.querySelector('[data-quiz-response]') || document.querySelector('.question-wrapper');
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
                const order = q.getAttribute('data-question-order');
                const qPage = pageOfOrder(order);
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

    (function () {
        const scoreCard = document.querySelector('[data-score-card]');
        if (!scoreCard) return;

        if (scoreCard.dataset.scoreBound === '1') return;
        scoreCard.dataset.scoreBound = '1';

        const displayEl = scoreCard.querySelector('[data-score-display]');
        const inputEl = scoreCard.querySelector('[data-score-input]');
        const decBtn = scoreCard.querySelector('[data-score-dec]');
        const incBtn = scoreCard.querySelector('[data-score-inc]');
        const overrideInput = scoreCard.querySelector('[data-override-score]');

        const enableOverride = () => {
            if (!overrideInput) return;
            if (overrideInput.name) return;
            overrideInput.name = overrideInput.getAttribute('data-override-name') || 'override_score';
        };

        const maxScore = Number(@json($totalQuizScore)) || 0;
        const clamp = (n) => Math.min(Math.max(0, n), maxScore);

        const getCurrent = () => {
            const raw = overrideInput?.value ?? displayEl?.textContent ?? '0';
            const n = parseInt(String(raw), 10);
            return clamp(Number.isFinite(n) ? n : 0);
        };

        const setCurrent = (n) => {
            const v = clamp(n);
            if (displayEl) displayEl.textContent = String(v);
            if (inputEl) inputEl.value = String(v);
            if (overrideInput) overrideInput.value = String(v);

            if (decBtn) {
                const disabled = v <= 0;
                decBtn.disabled = disabled;
                decBtn.classList.toggle('opacity-50', disabled);
                decBtn.classList.toggle('cursor-not-allowed', disabled);
            }
            if (incBtn) {
                const disabled = v >= maxScore;
                incBtn.disabled = disabled;
                incBtn.classList.toggle('opacity-50', disabled);
                incBtn.classList.toggle('cursor-not-allowed', disabled);
            }
        };

        // Essay grading points should affect the total score shown in the header.
        // We treat the current header score as: otherParts + sum(essay_points).
        // When essay points change, apply the delta so otherParts stays intact.
        const essayInputs = Array.from(document.querySelectorAll('[data-essay-points]'));

        const clampEssayInput = (el) => {
            if (!el) return 0;
            const maxAttr = el.getAttribute('data-essay-max') ?? el.getAttribute('max');
            const parsedMax = parseInt(String(maxAttr ?? ''), 10);
            const hasCap = maxAttr !== null && String(maxAttr).trim() !== '' && Number.isFinite(parsedMax);
            const cap = hasCap ? Math.max(0, parsedMax) : null;

            const raw = el.value;
            const trimmed = String(raw ?? '').trim();
            if (trimmed === '') {
                return 0;
            }

            let n = parseInt(trimmed, 10);
            if (!Number.isFinite(n) || n < 0) n = 0;
            if (cap !== null && n > cap) n = cap;

            if (String(el.value) !== String(n)) {
                el.value = String(n);
            }
            return n;
        };

        const sumEssayPoints = () => {
            return essayInputs.reduce((sum, el) => sum + clampEssayInput(el), 0);
        };
        let essaySumPrev = sumEssayPoints();

        const onEssayChange = () => {
            const nextSum = sumEssayPoints();
            const nextTotal = getCurrent() - essaySumPrev + nextSum;
            enableOverride();
            setCurrent(nextTotal);
            essaySumPrev = nextSum;
        };

        if (essayInputs.length) {
            essayInputs.forEach((el) => {
                el.addEventListener('input', onEssayChange);
                el.addEventListener('change', onEssayChange);
            });
        }

        setCurrent(getCurrent());

        decBtn?.addEventListener('click', () => {
            enableOverride();
            setCurrent(getCurrent() - 1);
        });
        incBtn?.addEventListener('click', () => {
            enableOverride();
            setCurrent(getCurrent() + 1);
        });

        const beginEdit = () => {
            if (!displayEl || !inputEl) return;
            enableOverride();
            displayEl.classList.add('hidden');
            inputEl.classList.remove('hidden');
            inputEl.focus();
            inputEl.select();
        };

        const commitEdit = () => {
            if (!displayEl || !inputEl) return;
            const raw = inputEl.value;
            const parsed = parseInt(String(raw), 10);
            setCurrent(Number.isFinite(parsed) ? parsed : getCurrent());
            inputEl.classList.add('hidden');
            displayEl.classList.remove('hidden');
        };

        displayEl?.addEventListener('click', beginEdit);
        inputEl?.addEventListener('blur', commitEdit);
        inputEl?.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                commitEdit();
            }
            if (e.key === 'Escape') {
                e.preventDefault();
                setCurrent(getCurrent());
                inputEl.classList.add('hidden');
                displayEl.classList.remove('hidden');
            }
        });
    })();

    (function () {
        const form = document.getElementById('scoreAdjustForm');
        const modal = document.querySelector('[data-save-modal]');
        const cancelBtn = document.querySelector('[data-save-cancel]');
        const confirmBtn = document.querySelector('[data-save-confirm]');
        if (!form || !modal || !cancelBtn || !confirmBtn) return;

        if (form.dataset.saveConfirmBound === '1') return;
        form.dataset.saveConfirmBound = '1';
        let isSubmitting = false;

        const setConfirmLoading = () => {
            if (window.AdminModalLoading && typeof window.AdminModalLoading.setLoading === 'function') {
                window.AdminModalLoading.setLoading(confirmBtn, {
                    text: confirmBtn?.dataset?.loadingText || 'Saving...',
                });
                return;
            }
            confirmBtn.disabled = true;
            confirmBtn.classList.add('opacity-75', 'cursor-wait');
            confirmBtn.textContent = confirmBtn?.dataset?.loadingText || 'Saving...';
        };

        const open = () => {
            modal.classList.remove('hidden');
        };
        const close = () => {
            if (isSubmitting) return;
            modal.classList.add('hidden');
        };

        form.addEventListener('submit', (e) => {
            if (form.dataset.saveConfirmed === '1') return;
            e.preventDefault();
            open();
        });

        cancelBtn.addEventListener('click', close);
        confirmBtn.addEventListener('click', () => {
            if (isSubmitting) return;
            if (typeof form.checkValidity === 'function' && !form.checkValidity()) {
                if (typeof form.reportValidity === 'function') {
                    form.reportValidity();
                }
                return;
            }

            isSubmitting = true;
            setConfirmLoading();
            cancelBtn.disabled = true;
            form.dataset.saveConfirmed = '1';
            form.requestSubmit ? form.requestSubmit() : form.submit();
        });

        modal.addEventListener('click', (e) => {
            if (e.target === modal) close();
        });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
                e.preventDefault();
                close();
            }
        });
    })();
</script>
@endif

@endsection
