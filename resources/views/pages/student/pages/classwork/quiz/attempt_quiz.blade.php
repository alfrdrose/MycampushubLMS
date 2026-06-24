@extends('pages.student.layout')
@section('content')

@php
    $settingRaw = $quiz->setting ?? [];
    if (is_string($settingRaw)) {
        $decoded = json_decode($settingRaw, true);
        $quizSetting = is_array($decoded) ? $decoded : [];
    } elseif (is_array($settingRaw)) {
        $quizSetting = $settingRaw;
    } else {
        $quizSetting = [];
    }
    $paginationValue = (int)($quizSetting['pagination'] ?? 10);
    if ($paginationValue < 1) $paginationValue = 1;
    if ($paginationValue > 25) $paginationValue = 25;

    // Only count/render questions that will actually display.
    // This prevents "Question X of N" mismatches when a quiz has a blank/invalid question.
    $displayQuestions = ($questions instanceof \Illuminate\Support\Collection)
        ? $questions
        : collect($questions ?? []);
    $displayQuestions = $displayQuestions
        ->filter(fn($q) => $q && trim((string) ($q->text ?? '')) !== '')
        ->values();
    $totalQuestions = $displayQuestions->count();

    // Helper to determine a question's page, preferring explicit page_hint.
    $deriveQuestionPage = function ($q) use ($paginationValue) {
        $order = (int) ($q->order ?? 0);
        $pageHintRaw = $q->page_hint ?? null;
        $hint = is_numeric($pageHintRaw) ? (int) $pageHintRaw : null;
        if ($hint && $hint > 0) {
            return $hint;
        }
        $size = max(1, $paginationValue);
        return (int) ceil(max(1, $order) / $size);
    };

    $questionPages = $displayQuestions->map(function ($q) use ($deriveQuestionPage) {
        return $deriveQuestionPage($q);
    });

    $totalQuizScore = (int) ($quiz->total_score ?? 0);
    if ($totalQuizScore <= 0) {
        if ($displayQuestions instanceof \Illuminate\Support\Collection) {
            $totalQuizScore = (int) $displayQuestions->sum(function ($q) {
                $p = $q->points ?? 1;
                return is_numeric($p) ? (int) $p : 1;
            });
        }
    }

    // Teacher add/edit pagination is driven by question `order`.
    // Use max(order) to determine how many pages exist (handles gaps and non-sequential orders).
    $maxOrder = 0;
    if ($displayQuestions instanceof \Illuminate\Support\Collection) {
        $maxOrder = (int) $displayQuestions->max(function ($q) {
            return (int) ($q->order ?? 0);
        });
    }
    $maxOrder = max($maxOrder, $totalQuestions);

    $totalPages = $paginationValue > 0 ? (int) ceil($maxOrder / $paginationValue) : 1;
    // Respect explicit page hints even if order-based totalPages would be smaller.
    $maxHintPage = $questionPages->max() ?? 1;
    if ($maxHintPage > $totalPages) {
        $totalPages = $maxHintPage;
    }
    if ($totalPages < 1) $totalPages = 1;

    // Start on the first page that actually has questions (avoid empty page 1 when all questions are on page 2+).
    $initialPage = (int) old('current_page', $questionPages->min() ?? 1);
    if ($initialPage < 1) $initialPage = 1;
    if ($initialPage > $totalPages) $initialPage = $totalPages;

    $quizOpenAt = null;
    if ($quiz->open_date) {
        $datePart = \Carbon\Carbon::parse($quiz->open_date)->format('Y-m-d');
        $timePart = $quiz->open_time
            ? \Carbon\Carbon::parse($quiz->open_time)->format('H:i:s')
            : '00:00:00';
        $quizOpenAt = \Carbon\Carbon::parse($datePart . ' ' . $timePart);
    }
@endphp

<div class="bg-[#FFFFFF] dark:bg-[#1E1E2D] dark:border-[#5F2DED] shadow-lg border p-8 rounded-lg">
    <div class="border-b border-[#D4D4D8] dark:border-[#5F2DED] flex items-center justify-between py-3">
        <div class="mb-4">
            <div class="flex items-center gap-3">
                <a href="{{ route('student.classwork', [$section->id, $subject->id]) }}"
                   data-back-link
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

                @if($quiz->description)
                    <div class="text-gray-600 dark:text-gray-300 mt-1">{!! $quiz->description !!}</div>
                @endif
            </div>
        </div>

        <div class="text-right flex flex-col items-end gap-2">
            <div class="flex items-center gap-2 text-gray-600 dark:text-gray-300">
                <i class="icofont-stopwatch text-lg"></i>
                <div class="text-sm">
                    <span class="font-semibold">Time spent:</span>
                    <span class="font-mono" data-quiz-timer>00:00</span>
                </div>
            </div>
            <div class="text-sm text-gray-500 dark:text-gray-400">Total quiz score</div>
            <div class="text-2xl font-bold text-gray-700 dark:text-gray-200">
                / <span class="text-[#F2277E] font-bold">{{ $totalQuizScore }}</span> pts
            </div>
        </div>
    </div>
    <div class="mt-6"></div>

    <form action="{{ route('student.quiz.submit', [$section->id, $subject->id, $quiz->id]) }}" method="POST" class="mt-2" data-quiz-attempt-form>
        @csrf
        <input type="hidden" name="start_time" value="{{ now()->toIso8601String() }}" data-start-time>
        <input type="hidden" name="time_spent_seconds" value="0" data-time-spent-seconds>

        <input type="hidden" name="current_page" value="{{ $initialPage }}" data-current-page>

        <div class="question-wrapper">
            @forelse($displayQuestions as $index => $question)
                @php
                    $options = $question->option ?? [];
                    $options = is_string($options) ? json_decode($options, true) : $options;
                    $options = is_array($options) ? $options : [];

                    $matchingLeft = [];
                    $matchingRight = [];
                    if (($question->type ?? '') === 'matching') {
                        $matchingLeft = isset($options['left']) && is_array($options['left']) ? array_values($options['left']) : [];
                        $matchingRight = isset($options['right']) && is_array($options['right']) ? array_values($options['right']) : [];
                    }

                    $answerKey = $question->answer_key ?? [];
                    if (is_string($answerKey)) {
                        $decoded = json_decode($answerKey, true);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            $answerKey = $decoded;
                        }
                    }
                    $answerKey = is_array($answerKey) ? $answerKey : [$answerKey];
                    $isMultiSelect = ($question->type ?? '') === 'multiple_choice'
                        && count(array_filter($answerKey, fn($v) => $v !== null && $v !== '')) > 1;
                    $required = (bool) ($question->required ?? false);
                    $points = is_numeric($question->points ?? null) ? (int) $question->points : 1;
                    $questionOrder = (int) ($question->order ?? 0);
                    if ($questionOrder < 1) {
                        $questionOrder = $index + 1;
                    }
                    $questionPage = $deriveQuestionPage($question);
                @endphp
                 <div class="question bg-white dark:bg-[#2A2A3C] shadow-md border border-gray-200 dark:border-gray-600 p-6 rounded-lg mb-6 relative transition-all duration-200 hover:shadow-lg hover:bg-gray-50 dark:hover:bg-[#3A3A4F] {{ $questionPage !== 1 ? 'hidden' : '' }}"
                     data-question-id="{{ $question->id }}"
                     data-question-order="{{ $questionOrder }}"
                     data-question-page="{{ $questionPage }}"
                     data-question-type="{{ (string) ($question->type ?? '') }}"
                     data-required="{{ $required ? '1' : '0' }}"
                     data-multiselect="{{ $isMultiSelect ? '1' : '0' }}"
                     {{ ($required && $isMultiSelect) ? 'data-required-multiselect=1' : '' }}
                 >
                    <div class="absolute top-4 right-4 text-xs text-gray-500 dark:text-gray-300">
                        {{ $points }} {{ $points === 1 ? 'pt' : 'pts' }}
                    </div>

                    <div class="mb-3 flex items-start justify-between gap-4">
                        <div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                Question {{ $index + 1 }} of {{ $totalQuestions }}
                                @if($required)
                                    <span class="ml-2 text-pink-500 font-bold" aria-label="Required">*</span>
                                @endif
                                @if(($question->type ?? '') === 'essay')
                                    <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-md text-xs font-semibold bg-gray-100 dark:bg-[#0F1724] text-gray-700 dark:text-gray-200">Essay</span>
                                @endif
                            </div>
                            <h3 class="text-lg font-semibold text-gray-700 dark:text-white mt-2">{!! nl2br(e($question->text)) !!}</h3>
                        </div>
                    </div>

                    @if(($question->type ?? '') === 'multiple_choice')
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
                                @endphp
                                <label class="flex items-center space-x-3">
                                    @if($isMultiSelect)
                                        <input type="checkbox"
                                               name="answers[{{ $question->id }}][]"
                                               value="{{ $optValue }}"
                                               class="quiz-choice-input">
                                    @else
                                        <input type="radio"
                                               name="answers[{{ $question->id }}]"
                                               value="{{ $optValue }}"
                                               class="quiz-choice-input"
                                               >
                                    @endif
                                    <span class="ml-2 text-gray-700 dark:text-gray-200"> {{ $optText }}</span>
                                </label>
                            @endforeach

                            @if($required && $isMultiSelect)
                                <p class="mt-2 text-sm text-red-500 hidden" data-multiselect-error>
                                    Please select at least one option.
                                </p>
                            @endif
                        </div>
                    @elseif(($question->type ?? '') === 'short_answer')
                        <div class="mt-3">
                            <input type="text"
                                   name="answers[{{ $question->id }}]"
                                   class="w-full max-w-md p-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-[#1E1E2D] text-gray-800 dark:text-white focus:ring-2 focus:ring-[#5F2DED] focus:border-transparent"
                                   placeholder="Your answer"
                                   >
                        </div>
                    @elseif(($question->type ?? '') === 'matching')
                        <div class="mt-3">
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
                                        @forelse($matchingLeft as $rowIndex => $leftText)
                                            <tr>
                                                <td class="font-medium text-gray-700 dark:text-gray-300 whitespace-nowrap p-2 border-b border-gray-200 dark:border-gray-600">
                                                    {{ \Illuminate\Support\Str::limit((string)($leftText ?: ('Row ' . ($rowIndex + 1))), 12) }}
                                                </td>

                                                @foreach($matchingRight as $colIndex => $rightText)
                                                    <td class="p-2 border-b border-gray-200 dark:border-gray-600 text-center align-middle min-w-[3rem]">
                                                        <label class="w-full min-h-10 flex items-center justify-center cursor-pointer">
                                                            <input
                                                                type="radio"
                                                                name="answers[{{ $question->id }}][match_map][{{ $rowIndex }}]"
                                                                value="{{ $colIndex }}"
                                                                class="h-5 w-5 accent-green-600"
                                                                {{ trim((string) $leftText) === '' || trim((string) $rightText) === '' ? 'disabled' : '' }}>
                                                        </label>
                                                    </td>
                                                @endforeach
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="{{ 1 + count($matchingRight) }}" class="p-3 text-gray-500 dark:text-gray-400">
                                                    No matching items configured for this question.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @elseif(($question->type ?? '') === 'true_false')
                        <div class="mt-3 space-y-2">
                            <label class="flex items-center gap-2 text-gray-700 dark:text-gray-200">
                                <input type="radio"
                                       name="answers[{{ $question->id }}]"
                                       value="true"
                                       class="form-radio text-[#5F2DED] focus:ring-[#5F2DED]"
                                       >
                                <span>True</span>
                            </label>
                            <label class="flex items-center gap-2 text-gray-700 dark:text-gray-200">
                                <input type="radio"
                                       name="answers[{{ $question->id }}]"
                                       value="false"
                                       class="form-radio text-[#5F2DED] focus:ring-[#5F2DED]"
                                       >
                                <span>False</span>
                            </label>
                        </div>
                    @elseif(($question->type ?? '') === 'numerical')
                        <div class="mt-3">
                            <input type="number"
                                   step="any"
                                   name="answers[{{ $question->id }}]"
                                   class="w-full max-w-sm p-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-[#1E1E2D] text-gray-800 dark:text-white focus:ring-2 focus:ring-[#5F2DED] focus:border-transparent"
                                   placeholder="Enter a number"
                                   >
                        </div>
                    @else
                        <div class="mt-3">
                            <textarea name="answers[{{ $question->id }}]"
                                      rows="4"
                                      class="w-full p-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-[#1E1E2D] text-gray-800 dark:text-white focus:ring-2 focus:ring-[#5F2DED] focus:border-transparent"
                                      placeholder="Write your answer here"
                                      ></textarea>
                        </div>
                    @endif

                    <p class="mt-2 text-sm text-red-500 hidden" data-question-error>
                        This question is required.
                    </p>
                </div>
            @empty
                <div class="text-center text-gray-500 dark:text-gray-400 p-6">No questions available for this quiz.</div>
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

                <button type="submit"
                        class="ml-auto flex items-center justify-center bg-[#5F2DED]
                               text-white px-6 py-3 rounded-full shadow-md
                               hover:bg-[#4E25C7] transition-all duration-200
                               focus:outline-none focus:ring-2 focus:ring-purple-400 font-semibold">
                    <i class="icofont-check mr-2"></i> Submit Quiz
                </button>
            </div>
        @else
            <div class="flex justify-end mt-6">
                <button type="submit"
                        class="ml-auto flex items-center justify-center bg-[#5F2DED]
                               text-white px-6 py-3 rounded-full shadow-md
                               hover:bg-[#4E25C7] transition-all duration-200
                               focus:outline-none focus:ring-2 focus:ring-purple-400 font-semibold">
                    <i class="icofont-check mr-2"></i> Submit Quiz
                </button>
            </div>
        @endif
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        (function () {
        const form = document.querySelector('[data-quiz-attempt-form]') || document.querySelector('form[action*="quiz"]');
        if (!form) return;

        // Time spent timer
        const timerEl = document.querySelector('[data-quiz-timer]');
        const startTimeInput = form.querySelector('[data-start-time]');
        const timeSpentInput = form.querySelector('[data-time-spent-seconds]');

        const parseStartMs = () => {
            const raw = (startTimeInput && startTimeInput.value) ? String(startTimeInput.value) : '';
            const ms = raw ? Date.parse(raw) : NaN;
            return Number.isNaN(ms) ? Date.now() : ms;
        };
        const startedAtMs = parseStartMs();

        const formatDuration = (totalSeconds) => {
            const s = Math.max(0, Math.floor(Number(totalSeconds) || 0));
            const h = Math.floor(s / 3600);
            const m = Math.floor((s % 3600) / 60);
            const ss = s % 60;
            const pad2 = (n) => String(n).padStart(2, '0');
            if (h > 0) return `${pad2(h)}:${pad2(m)}:${pad2(ss)}`;
            return `${pad2(m)}:${pad2(ss)}`;
        };

        const updateTimer = () => {
            const seconds = Math.floor((Date.now() - startedAtMs) / 1000);
            if (timerEl) timerEl.textContent = formatDuration(seconds);
            if (timeSpentInput) timeSpentInput.value = String(Math.max(0, seconds));
        };
        updateTimer();
        window.setInterval(updateTimer, 1000);

        const backLink = document.querySelector('[data-back-link]');
        const backModal = document.querySelector('[data-back-modal]');
        const backCancel = document.querySelector('[data-back-cancel]');
        const backConfirm = document.querySelector('[data-back-confirm]');
        let isDirty = false;

        const pageSize = Number(@json($paginationValue)) || 10;
        const totalPages = Number(@json($totalPages)) || 1;
        const currentPageInput = form.querySelector('[data-current-page]');

        const questions = Array.from(form.querySelectorAll('.question[data-question-order]'));
        const prevBtn = form.querySelector('[data-prev-page]');
        const nextBtn = form.querySelector('[data-next-page]');
        const pageButtonsWrap = form.querySelector('[data-page-buttons]');

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

        const defaultPage = clampPage(parseInt(currentPageInput?.value || '1', 10) || 1);
        let currentPage = defaultPage;

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

        const clearQuestionError = (questionEl) => {
            const err = questionEl.querySelector('[data-question-error]');
            if (err) err.classList.add('hidden');
            const msErr = questionEl.querySelector('[data-multiselect-error]');
            if (msErr) msErr.classList.add('hidden');

            questionEl.classList.remove('ring-2', 'ring-red-400', 'border-red-400');
        };

        const showQuestionError = (questionEl, message) => {
            const err = questionEl.querySelector('[data-question-error]');
            if (err) {
                err.textContent = message || 'This question is required.';
                err.classList.remove('hidden');
            }

            questionEl.classList.add('ring-2', 'ring-red-400', 'border-red-400');
        };

        const isRequired = (questionEl) => (questionEl.getAttribute('data-required') === '1');
        const getType = (questionEl) => (questionEl.getAttribute('data-question-type') || '').trim();
        const isMultiSelect = (questionEl) => (questionEl.getAttribute('data-multiselect') === '1');

        const validateQuestion = (questionEl) => {
            clearQuestionError(questionEl);
            if (!isRequired(questionEl)) return true;

            const type = getType(questionEl);

            if (type === 'multiple_choice') {
                if (isMultiSelect(questionEl)) {
                    const checkboxes = questionEl.querySelectorAll('input[type="checkbox"][name^="answers["]');
                    const ok = Array.from(checkboxes).some(cb => cb.checked);
                    if (!ok) {
                        const msErr = questionEl.querySelector('[data-multiselect-error]');
                        if (msErr) msErr.classList.remove('hidden');
                        else showQuestionError(questionEl, 'Please select at least one option.');
                    }
                    return ok;
                }
                const radios = questionEl.querySelectorAll('input[type="radio"][name^="answers["]');
                const ok = Array.from(radios).some(r => r.checked);
                if (!ok) showQuestionError(questionEl, 'Please select an option.');
                return ok;
            }

            if (type === 'true_false') {
                const radios = questionEl.querySelectorAll('input[type="radio"][name^="answers["]');
                const ok = Array.from(radios).some(r => r.checked);
                if (!ok) showQuestionError(questionEl, 'Please choose True or False.');
                return ok;
            }

            if (type === 'numerical' || type === 'short_answer') {
                const input = questionEl.querySelector('input[name^="answers["]');
                const ok = !!(input && String(input.value || '').trim() !== '');
                if (!ok) showQuestionError(questionEl, 'Please provide an answer.');
                return ok;
            }

            if (type === 'matching') {
                const radios = Array.from(questionEl.querySelectorAll('input[type="radio"][name*="[match_map]"]'));
                if (radios.length < 1) {
                    showQuestionError(questionEl, 'Matching options are not configured.');
                    return false;
                }
                const names = Array.from(new Set(radios.map(r => r.getAttribute('name')).filter(Boolean)));
                const ok = names.every((name) => {
                    const group = questionEl.querySelectorAll(`input[type="radio"][name="${CSS.escape(name)}"]`);
                    return Array.from(group).some(r => r.checked);
                });
                if (!ok) showQuestionError(questionEl, 'Please select a match for each row.');
                return ok;
            }

            const textarea = questionEl.querySelector('textarea[name^="answers["]');
            if (textarea) {
                const ok = String(textarea.value || '').trim() !== '';
                if (!ok) showQuestionError(questionEl, 'Please provide an answer.');
                return ok;
            }

            const any = questionEl.querySelector('input[name^="answers["]');
            const ok = !!(any && String(any.value || '').trim() !== '');
            if (!ok) showQuestionError(questionEl, 'Please provide an answer.');
            return ok;
        };

        const goToFirstInvalid = () => {
            for (const q of questions) {
                if (!validateQuestion(q)) {
                    const order = q.getAttribute('data-question-order');
                    showPage(pageOfOrder(order));
                    q.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    return { valid: false, question: q };
                }
            }
            return { valid: true, question: null };
        };

        // Missing-required modal
        const missingModal = document.getElementById('missingRequiredModal');
        const missingModalClose = document.querySelector('[data-missing-close]');
        const missingModalMessage = document.querySelector('[data-missing-message]');
        let pendingInvalidQuestion = null;

        const openMissingModal = (questionEl) => {
            pendingInvalidQuestion = questionEl || null;
            if (missingModalMessage) {
                missingModalMessage.textContent = 'This question is required. Please answer it before submitting.';
            }
            if (missingModal) missingModal.classList.remove('hidden');
        };
        const closeMissingModal = () => {
            if (missingModal) missingModal.classList.add('hidden');
        };

        // Submit confirmation modal
        const submitModal = document.getElementById('submitConfirmModal');
        const submitCancel = document.querySelector('[data-submit-cancel]');
        const submitConfirm = document.querySelector('[data-submit-confirm]');
        let submitConfirmed = false;
        let submitInProgress = false;

        const setSubmitLoading = () => {
            if (!submitConfirm) return;

            if (window.AdminModalLoading && typeof window.AdminModalLoading.setLoading === 'function') {
                window.AdminModalLoading.setLoading(submitConfirm, {
                    text: submitConfirm?.dataset?.loadingText || 'Submitting...',
                });
                return;
            }

            submitConfirm.disabled = true;
            submitConfirm.classList.add('opacity-75', 'cursor-wait');
            submitConfirm.textContent = submitConfirm?.dataset?.loadingText || 'Submitting...';
        };

        const openSubmitModal = () => {
            if (!submitModal) return;
            submitModal.classList.remove('hidden');
            submitModal.classList.add('flex');
        };
        const closeSubmitModal = () => {
            if (!submitModal) return;
            if (submitInProgress) return;
            submitModal.classList.add('hidden');
            submitModal.classList.remove('flex');
        };

        form.addEventListener('submit', function (e) {
            if (submitConfirmed) {
                // allow navigating away without warning after a valid submit
                isDirty = false;
                return;
            }

            const { valid, question } = goToFirstInvalid();
            if (!valid) {
                e.preventDefault();
                openMissingModal(question);
                return;
            }

            e.preventDefault();
            updateTimer();
            openSubmitModal();
        });

        if (submitCancel) {
            submitCancel.addEventListener('click', function () {
                closeSubmitModal();
            });
        }
        if (submitConfirm) {
            submitConfirm.addEventListener('click', function () {
                if (submitInProgress) return;
                submitInProgress = true;
                setSubmitLoading();
                if (submitCancel) submitCancel.disabled = true;
                submitConfirmed = true;
                updateTimer();
                form.submit();
            });
        }
        if (submitModal) {
            submitModal.addEventListener('click', function (e) {
                if (e.target === submitModal) {
                    closeSubmitModal();
                }
            });
        }

        // Mark dirty when user changes any answer.
        form.addEventListener('input', function (ev) {
            const t = ev.target;
            if (!t) return;
            if (t.name && t.name.startsWith('answers[')) {
                isDirty = true;
            }
        });
        form.addEventListener('change', function (ev) {
            const t = ev.target;
            if (!t) return;
            if (t.name && t.name.startsWith('answers[')) {
                isDirty = true;
            }
        });

        if (missingModalClose) {
            missingModalClose.addEventListener('click', function () {
                closeMissingModal();
                if (pendingInvalidQuestion) {
                    pendingInvalidQuestion.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        }
        if (missingModal) {
            missingModal.addEventListener('click', function (e) {
                if (e.target === missingModal || (e.target && e.target.classList && e.target.classList.contains('bg-black/50'))) {
                    closeMissingModal();
                }
            });
        }

        const openBackModal = () => {
            if (!backModal) return;
            backModal.classList.remove('hidden');
        };
        const closeBackModal = () => {
            if (!backModal) return;
            backModal.classList.add('hidden');
        };

        if (backLink) {
            backLink.addEventListener('click', function (e) {
                if (!isDirty) return;
                e.preventDefault();
                openBackModal();
            });
        }
        if (backCancel) {
            backCancel.addEventListener('click', function () {
                closeBackModal();
            });
        }
        if (backModal) {
            backModal.addEventListener('click', function (e) {
                // click outside closes
                if (e.target === backModal || (e.target && e.target.classList && e.target.classList.contains('bg-black/50'))) {
                    closeBackModal();
                }
            });
        }
        if (backConfirm) {
            backConfirm.addEventListener('click', function () {
                isDirty = false;
            });
        }

        if (prevBtn) {
            prevBtn.addEventListener('click', () => showPage(currentPage - 1));
        }
        if (nextBtn) {
            nextBtn.addEventListener('click', () => showPage(currentPage + 1));
        }
        if (pageButtonsWrap) {
            pageButtonsWrap.addEventListener('click', (ev) => {
                const btn = ev.target && ev.target.closest && ev.target.closest('[data-page]');
                if (!btn) return;
                const p = parseInt(btn.getAttribute('data-page') || '1', 10);
                showPage(p);
            });
        }

        // Initial render
        showPage(currentPage);
    })();
    });
</script>



@endsection

{{-- Back confirm modal --}}
<div class="fixed inset-0 z-50 hidden" data-back-modal>
    <div class="absolute inset-0 bg-black/50"></div>
    <div class="relative mx-auto mt-24 w-full max-w-lg rounded-lg bg-white dark:bg-[#1E1E2D] border border-gray-200 dark:border-gray-600 shadow-lg p-6">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-white">Leave this quiz?</h3>
        <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
            Are you sure you want to return? You have unsaved answers and will lose your progress.
        </p>
        <div class="mt-6 flex items-center justify-end gap-3">
            <button type="button"
                    class="px-4 py-2 text-sm rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-[#0F1724] text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-[#111217]"
                    data-back-cancel>
                Stay
            </button>
            <a href="{{ route('student.classwork', [$section->id, $subject->id]) }}"
               class="px-4 py-2 text-sm rounded-md bg-red-600 text-white hover:bg-red-700"
               data-back-confirm>
                Return
            </a>
        </div>
    </div>
</div>

{{-- Missing required question modal --}}
<div class="fixed inset-0 z-50 hidden" id="missingRequiredModal" data-missing-modal>
    <div class="absolute inset-0 bg-black/50"></div>
    <div class="relative z-10 flex items-center justify-center min-h-screen p-4">
        <div class="w-full max-w-lg rounded-lg bg-white dark:bg-[#1E1E2D] border border-gray-200 dark:border-gray-600 shadow-lg p-6">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white">Required question</h3>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-300" data-missing-message>
                This question is required. Please answer it before submitting.
            </p>
            <div class="mt-6 flex items-center justify-end">
                <button type="button"
                        class="px-4 py-2 text-sm rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-[#0F1724] text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-[#111217]"
                        data-missing-close>
                    OK
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Submit confirmation modal --}}
<div id="submitConfirmModal"
     class="fixed inset-0 items-center justify-center bg-black bg-opacity-50 hidden z-50"
     data-submit-modal>
    <div class="bg-white dark:bg-[#1E1E2D] p-6 rounded-lg w-96">
        <h2 class="text-xl font-bold mb-4 text-gray-800 dark:text-white">Submit quiz?</h2>
        <p class="text-[#373737] dark:text-white mb-6">
            Are you sure you want to submit your answers? You won’t be able to change them after submitting.
        </p>
        <div class="flex justify-end gap-3">
            <button type="button"
                    class="px-4 py-2 bg-gray-300 rounded dark:bg-gray-600 dark:text-white"
                    data-submit-cancel>
                Cancel
            </button>
            <button type="button"
                    class="px-4 py-2 bg-primaryColor text-white rounded hover:opacity-90"
                    data-loading-text="Submitting..."
                    data-submit-confirm>
                Confirm
            </button>
        </div>
    </div>
</div>
