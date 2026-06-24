@extends('pages.teacher.layout')

@section('content')
    <div class="bg-white dark:bg-[#1E1E2D] shadow-lg border border-gray-200 dark:border-[#5F2DED] p-8 rounded-lg">
        <div class="pb-2 flex flex-wrap gap-3 items-center">
            <button onclick="window.location='{{ route('teacher.classwork.view', [$section->id, $subject->id]) }}'" 
                    class="flex items-center gap-2 shrink-0">
                <i class="icofont-arrow-left text-primaryColor text-4xl mb-2"></i>
            </button>

            <div class="flex-1 min-w-0">
                <x-quiz-nav 
                    active="settings" 
                    :section-id="$section->id" 
                    :subject-id="$subject->id" 
                    :quiz-id="$quiz->id" 
                />
            </div>
            <div class="ml-auto flex gap-2">
                <button 
                    type="button"
                    id="openDeleteModal"
                    class="inline-flex h-10 items-center px-4 py-2 bg-red-600 text-white text-sm rounded-lg shadow-sm hover:bg-red-700 transition-colors">
                    Delete Quiz
                </button>
            </div>
        </div>

        <div class="border-b border-gray-200 dark:border-[#5F2DED] flex items-center justify-between pb-4">
            <div>
                <h2 class="text-3xl font-bold text-[#5E5F60] dark:text-white">
                    {{ $quiz->title }}
                </h2>
                @if($quiz->description)
                    <div class="mt-4 text-gray-600 dark:text-gray-300">{!! $quiz->description !!}</div>
                @else
                    <p class="mt-4 text-gray-600 dark:text-gray-300">No description.</p>
                @endif
            </div>
        </div>

        <div class="mt-6">
            @php
                $quizSettingRaw = $quiz->setting ?? [];
                if (is_string($quizSettingRaw)) {
                    $decoded = json_decode($quizSettingRaw, true);
                    $quizSetting = is_array($decoded) ? $decoded : [];
                } elseif (is_array($quizSettingRaw)) {
                    $quizSetting = $quizSettingRaw;
                } else {
                    $quizSetting = [];
                }

                $paginationValue = (int)($quizSetting['pagination'] ?? 10);
                if ($paginationValue < 1) $paginationValue = 1;
                if ($paginationValue > 25) $paginationValue = 25;

                $shuffleOnPublish = (bool)($quizSetting['shuffle_questions_on_publish'] ?? false);
                $studentCanReview = array_key_exists('student_can_review', $quizSetting)
                    ? (bool)$quizSetting['student_can_review']
                    : true;
                $studentCanViewScore = array_key_exists('student_can_view_score', $quizSetting)
                    ? (bool)$quizSetting['student_can_view_score']
                    : true;
                $studentCanViewCorrectAnswers = array_key_exists('student_can_view_correct_answers', $quizSetting)
                    ? (bool)$quizSetting['student_can_view_correct_answers']
                    : true;

                $quizVisible = (bool)($quiz->visible ?? false);
                $quizIsOpen = (bool)($quiz->is_open ?? false);
                $now = \Carbon\Carbon::now('Asia/Manila');
                $openAt = null;
                if ($quiz->open_date) {
                    $datePart = \Carbon\Carbon::parse($quiz->open_date)->format('Y-m-d');
                    $timePart = $quiz->open_time
                        ? \Carbon\Carbon::parse($quiz->open_time)->format('H:i:s')
                        : '00:00:00';
                    $openAt = \Carbon\Carbon::parse($datePart . ' ' . $timePart);
                }
                $dueAt = $quiz->due_date ? \Carbon\Carbon::parse($quiz->due_date) : null;
                $duePassed = $dueAt && $dueAt->lt($now);

                $quizStatusText = 'Quiz is visible and open';
                $quizStatusClass = 'text-green-500';

                if ($quizIsOpen == 0 && $quizVisible == 0) {
                    $quizStatusText = 'Quiz is not visible and closed';
                    $quizStatusClass = 'text-red-500';
                } elseif ($quizIsOpen == 1 && $quizVisible == 0) {
                    $quizStatusText = 'Quiz is open but not visible';
                    $quizStatusClass = 'text-yellow';
                } elseif ($quizIsOpen == 0 && $quizVisible == 1) {
                    if ($openAt && $openAt->gt($now)) {
                        $quizStatusText = 'Opens ' . $openAt->format('F j, Y \\a\\t h:i A');
                    } else {
                        $quizStatusText = 'Quiz is visible but not open';
                    }
                    $quizStatusClass = 'text-yellow';
                } elseif ($quizIsOpen == 1 && $quizVisible == 1) {
                    if ($quiz->accepting) {
                        $quizStatusText = $duePassed ? 'Accepting late submissions' : 'Quiz is visible and open';
                        $quizStatusClass = $duePassed ? 'text-yellow' : 'text-green-500';
                    } else {
                        $quizStatusText = $duePassed ? 'Quiz is visible but not accepting anymore' : 'Quiz is visible and open';
                        $quizStatusClass = $duePassed ? 'text-red-500' : 'text-green-500';
                    }
                }
            @endphp

            <div id="quizVisibilityHeader"
                class="w-full mb-2 flex flex-row justify-between hover:bg-gray-100 dark:hover:bg-gray-800 
                    text-[#5E5F60] dark:text-white rounded cursor-pointer py-2 px-1 transition-all">
                <h1 class="text-lg font-bold">Visibility Options</h1>
                <i id="quizVisibilityArrow" class="icofont-rounded-down text-xl transition-all duration-300"></i>
            </div>

            <div id="quizVisibilityContent" class="flex flex-col overflow-hidden transition-all duration-300 border-b">
                <div class="flex flex-col md:flex-row items-center gap-4 pt-2 pb-4">
                    <div class="flex flex-col w-full md:w-1/2 gap-2">
                        <div>
                            <label class="block mb-1 text-sm font-medium text-[#5E5F60] dark:text-white">
                                Open Date
                            </label>
                            <input type="text" id="open_date"
                                value="{{ old('open_date', $quiz->open_date ? \Carbon\Carbon::parse($quiz->open_date)->format('Y-m-d') : '') }}"
                                placeholder="Select date"
                                class="w-full border border-[#ABB7C2] rounded-lg px-3 py-2 text-[#5E5F60] dark:text-white 
                                    bg-transparent focus:outline-none focus:ring-2 focus:ring-[#5F2DED]">
                        </div>

                        <div>
                            <label class="block mb-1 text-sm font-medium text-[#5E5F60] dark:text-white">
                                Open Time
                            </label>
                            <input type="text" id="open_time"
                                value="{{ old('open_time', $quiz->open_time ? \Carbon\Carbon::parse($quiz->open_time)->format('H:i') : '') }}"
                                placeholder="Select time"
                                class="w-full border dark:bg-[#1E1E2D] border-[#ABB7C2] rounded-lg px-3 py-2 text-[#5E5F60] 
                                    dark:text-white bg-transparent focus:outline-none focus:ring-2 focus:ring-[#5F2DED]">
                        </div>
                        <p id="openDateTimeError"
                           class="mt-1 text-sm font-semibold text-red-600 {{ ($errors->has('open_date') || $errors->has('open_time')) ? '' : 'hidden' }}">
                            {{ $errors->first('open_date') ?: ($errors->first('open_time') ?: 'Open date and open time must both be provided.') }}
                        </p>
                    </div>

                    <div class="flex flex-col w-full md:w-1/2 gap-2">
                        <div>
                            <label class="block mb-1 text-sm font-medium text-[#5E5F60] dark:text-white">
                                Due Date
                            </label>
                            <input type="text" id="due_date"
                                value="{{ old('due_date', $dueAt ? $dueAt->format('Y-m-d') : '') }}"
                                placeholder="Select date"
                                class="w-full border border-[#ABB7C2] rounded-lg px-3 py-2 text-[#5E5F60] dark:text-white 
                                    bg-transparent focus:outline-none focus:ring-2 focus:ring-[#5F2DED]">
                        </div>

                        <div>
                            <label class="block mb-1 text-sm font-medium text-[#5E5F60] dark:text-white">
                                Due Time
                            </label>
                            <input type="text" id="due_time"
                                value="{{ old('due_time', $dueAt ? $dueAt->format('H:i') : '') }}"
                                placeholder="Select time"
                                class="w-full border border-[#ABB7C2] rounded-lg px-3 py-2 text-[#5E5F60] dark:text-white 
                                    bg-transparent focus:outline-none focus:ring-2 focus:ring-[#5F2DED]">
                        </div>
                        <p id="dueDateTimeError"
                           class="mt-1 text-sm font-semibold text-red-600 {{ ($errors->has('due_date') || $errors->has('due_time')) ? '' : 'hidden' }}">
                            {{ $errors->first('due_date') ?: ($errors->first('due_time') ?: 'Due date and due time are required.') }}
                        </p>
                    </div>
                </div>

                <div class="mt-2 flex items-center gap-2 ">
                    <label class="text-sm font-medium text-[#5E5F60] dark:text-white">
                        Classwork is visible to students
                    </label>

                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" id="quiz_is_visible"
                            @checked($quizVisible)
                            class="sr-only peer">

                        <div class="w-11 h-6 bg-gray-300 peer-focus:outline-none rounded-full peer dark:bg-gray-700
                                    peer-checked:bg-[#5F2DED] dark:peer-checked:bg-blue-500
                                    transition-all duration-300"></div>

                        <span class="absolute left-1 top-[3px] w-4 h-4 bg-white rounded-full transition-all
                                    peer-checked:translate-x-5 dark:bg-gray-200"></span>
                    </label>
                </div>

                <div class="mt-2 flex items-center gap-2 mb-4">
                    <label class="text-sm font-medium text-[#5E5F60] dark:text-white">
                        Quiz Status:
                    </label>
                    <span class="{{ $quizStatusClass }} font-semibold text-sm">
                        {{ $quizStatusText }}
                    </span>
                </div>

                <input type="hidden" id="is_open_status" value="{{ (int)($quiz->is_open ?? 0) }}">
            </div>

            <div id="quizRestrictionHeader"
                class="w-full mb-2 mt-4 flex flex-row justify-between hover:bg-gray-100 dark:hover:bg-gray-800 
                    text-[#5E5F60] dark:text-white rounded cursor-pointer py-2 px-1 transition-all">
                <h1 class="text-lg font-bold">Restriction</h1>
                <i id="quizRestrictionArrow" class="icofont-rounded-down text-xl transition-all duration-300"></i>
            </div>

            <div id="quizRestrictionContent" class="flex flex-col overflow-hidden transition-all duration-300 border-b">
                <div class="flex flex-col md:flex-row gap-4 pt-2 pb-4">
                    <div class="w-full md:w-1/2">
                        <label class="block mb-1 text-sm font-medium text-[#5E5F60] dark:text-white">Allow Late Submissions</label>
                        <select id="accepting"
                                class="w-full border dark:bg-[#1E1E2D] border-[#ABB7C2] rounded-lg px-3 py-2 text-[#5E5F60] dark:text-white bg-transparent focus:outline-none focus:ring-2 focus:ring-[#5F2DED]">
                            <option value="1" {{ $quiz->accepting ? 'selected' : '' }}>Yes</option>
                            <option value="0" {{ !$quiz->accepting ? 'selected' : '' }}>No</option>
                        </select>
                    </div>

                    <div class="w-full md:w-1/2">
                        <label class="block mb-1 text-sm font-medium text-[#5E5F60] dark:text-white">Maximum number of Attempts</label>
                        <input type="number" min="1" step="1" inputmode="numeric" id="max_attempt"
                                value="{{ old('max_attempt', $quiz->max_attempt) }}"
                                class="w-full border border-[#ABB7C2] rounded-lg px-3 py-2 text-[#5E5F60] dark:text-white bg-transparent focus:outline-none focus:ring-2 focus:ring-[#5F2DED]">
                        <p id="maxAttemptError"
                           class="mt-1 text-sm font-semibold text-red-600 {{ $errors->has('max_attempt') ? '' : 'hidden' }}">
                            {{ $errors->first('max_attempt') ?: 'Maximum attempts must be at least 1.' }}
                        </p>
                    </div>
                </div>
            </div>

            <div id="configHeader"
                class="w-full mb-2 mt-4 flex flex-row justify-between hover:bg-gray-100 dark:hover:bg-gray-800 
                    text-[#5E5F60] dark:text-white rounded cursor-pointer py-2 px-1 transition-all">
                <h1 class="text-lg font-bold">Configuration</h1>
                <i id="configArrow" class="icofont-rounded-down text-xl transition-all duration-300"></i>
            </div>

            <div id="configContent" class="flex flex-col overflow-hidden transition-all duration-300 border-b">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2 pb-4">
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                        <div class="flex items-start gap-3">
                            <input type="checkbox"
                                   id="shuffle_questions_on_publish"
                                   class="mt-1 h-4 w-4 rounded border-gray-300 dark:border-[#ABB7C2]"
                                   {{ $shuffleOnPublish ? 'checked' : '' }}>
                            <div>
                                <p class="text-sm font-semibold text-[#5E5F60] dark:text-white">Shuffle questions on publish</p>
                                <p class="text-xs text-gray-600 dark:text-gray-300 mt-1">Randomize question order when the quiz is published.</p>
                            </div>
                        </div>
                    </div>

                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                        <div class="flex items-start gap-3">
                            <input type="checkbox"
                                   id="student_can_review"
                                   class="mt-1 h-4 w-4 rounded border-gray-300 dark:border-[#ABB7C2]"
                                   {{ $studentCanReview ? 'checked' : '' }}>
                            <div>
                                <p class="text-sm font-semibold text-[#5E5F60] dark:text-white">Allow students to review their submission</p>
                                <p class="text-xs text-gray-600 dark:text-gray-300 mt-1">Controls whether students can open their quiz attempt to see their submitted answers.</p>
                            </div>
                        </div>
                    </div>

                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                        <div class="flex items-start gap-3">
                            <input type="checkbox"
                                   id="student_can_view_score"
                                   class="mt-1 h-4 w-4 rounded border-gray-300 dark:border-[#ABB7C2]"
                                   {{ $studentCanViewScore ? 'checked' : '' }}>
                            <div>
                                <p class="text-sm font-semibold text-[#5E5F60] dark:text-white">Show total score to students</p>
                                <p class="text-xs text-gray-600 dark:text-gray-300 mt-1">When disabled, students can still open their submission (if allowed) but will not see their score.</p>
                            </div>
                        </div>
                    </div>

                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                        <div class="flex items-start gap-3">
                            <input type="checkbox"
                                   id="student_can_view_correct_answers"
                                   class="mt-1 h-4 w-4 rounded border-gray-300 dark:border-[#ABB7C2]"
                                {{ $studentCanViewCorrectAnswers ? 'checked' : '' }}>
                            <div>
                                <p class="text-sm font-semibold text-[#5E5F60] dark:text-white">Reveal correct answers</p>
                                <p class="text-xs text-gray-600 dark:text-gray-300 mt-1">Hide or show the correct answers and correctness indicators on the student's response page.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div id="tagsHeader"
                class="w-full mb-2 mt-4 flex flex-row justify-between hover:bg-gray-100 dark:hover:bg-gray-800 
                    text-[#5E5F60] dark:text-white rounded cursor-pointer py-2 px-1 transition-all">
                <h1 class="text-lg font-bold">Tags</h1>
                <i id="tagsArrow" class="icofont-rounded-down text-xl transition-all duration-300"></i>
            </div>

            <div id="tagsContent" class="flex flex-col overflow-hidden transition-all duration-300 border-b pb-6">
                <div class="flex flex-wrap items-center gap-2 pt-2">
                    <div id="tagPills" class="flex flex-wrap items-center gap-2">
                        @foreach ($assignedTags as $tag)
                            @php
                                $relevanceLevel = strtolower((string) (optional($tag->pivot)->relevance_level ?? config('reviewers.default_tag_relevance', 'medium')));
                                $relevanceClass = match ($relevanceLevel) {
                                    'very_high' => 'tag-pill-tone-very-high',
                                    'high' => 'tag-pill-tone-high',
                                    'low' => 'tag-pill-tone-low',
                                    'very_low' => 'tag-pill-tone-very-low',
                                    default => 'tag-pill-tone-medium',
                                };
                                $coveragePercent = optional($tag->pivot)->coverage_ratio !== null
                                    ? round(((float) optional($tag->pivot)->coverage_ratio) * 100, 2)
                                    : 0;
                            @endphp
                            <span
                                class="tag-pill inline-flex items-center gap-1.5 px-3 py-1 rounded-full border transition {{ $relevanceClass }}"
                                data-tag-id="{{ $tag->id }}">
                                <span class="text-sm">{{ $tag->name }}</span>
                                <span class="ml-1 text-[10px] leading-none opacity-80">{{ rtrim(rtrim(number_format($coveragePercent, 2, '.', ''), '0'), '.') }}%</span>
                            </span>
                        @endforeach
                    </div>
                </div>
                <div class="mt-2 flex items-center justify-between">
                    <p id="noTagsMessage" class="text-sm text-gray-500 dark:text-gray-300 {{ $assignedTags->isEmpty() ? '' : 'hidden' }}">
                        No tags assigned yet.
                    </p>
                    <p id="quizTagDerivedNote" class="text-xs text-gray-500 dark:text-gray-300">
                        Read only, aggregated from question tags.
                    </p>
                </div>
            </div>
            </div>

            <div class="flex justify-end mt-6">
                    <button type="button" id="openSaveModal"
                        class="px-5 py-2 bg-[#5F2DED] hover:bg-purple-700 text-white rounded-lg transition">
                    Save
                </button>
            </div>
        </div>
    </div>

    <form id="quizForm"
        action="{{ route('teacher.quiz.settingsUpdate', [$section->id, $subject->id, $quiz->id]) }}"
        method="POST"
        class="hidden">
        @csrf
        @method('PUT')

        <input type="hidden" name="visible" id="visible_input">
        <input type="hidden" name="is_open" id="is_open_input">
        <input type="hidden" name="open_date" id="open_date_input">
        <input type="hidden" name="open_time" id="open_time_input">
        <input type="hidden" name="due_date" id="due_date_input">
        <input type="hidden" name="due_time" id="due_time_input">
        <input type="hidden" name="accepting" id="accepting_input">
        <input type="hidden" name="max_attempt" id="max_attempt_input">
        <input type="hidden" name="shuffle_questions_on_publish" id="shuffle_questions_on_publish_input">
        <input type="hidden" name="student_can_review" id="student_can_review_input">
        <input type="hidden" name="student_can_view_score" id="student_can_view_score_input">
        <input type="hidden" name="student_can_view_correct_answers" id="student_can_view_correct_answers_input">
        {{-- <input type="hidden" name="total_score" id="total_score_input"> --}}
    </form>

    <style>
        .tag-pill-tone-very-high {
            border-color: #16a34a;
            background-color: #dcfce7;
            color: #14532d;
        }

        .tag-pill-tone-very-high:hover {
            background-color: #bbf7d0;
        }

        .tag-pill-tone-high {
            border-color: #14b8a6;
            background-color: #ccfbf1;
            color: #134e4a;
        }

        .tag-pill-tone-high:hover {
            background-color: #99f6e4;
        }

        .tag-pill-tone-medium {
            border-color: #84cc16;
            background-color: #f7fee7;
            color: #4d7c0f;
        }

        .tag-pill-tone-medium:hover {
            background-color: #ecfccb;
        }

        .tag-pill-tone-low {
            border-color: #0ea5e9;
            background-color: #f0f9ff;
            color: #0c4a6e;
        }

        .tag-pill-tone-low:hover {
            background-color: #bae6fd;
        }

        .tag-pill-tone-very-low {
            border-color: #1d4ed8;
            background-color: #dbeafe;
            color: #172554;
        }

        .tag-pill-tone-very-low:hover {
            background-color: #93c5fd;
        }

        .dark .tag-pill-tone-very-high {
            border-color: #4ade80;
            background-color: rgba(22, 163, 74, 0.35);
            color: #dcfce7;
        }

        .dark .tag-pill-tone-very-high:hover {
            background-color: rgba(22, 163, 74, 0.5);
        }

        .dark .tag-pill-tone-high {
            border-color: #2dd4bf;
            background-color: rgba(15, 118, 110, 0.4);
            color: #ccfbf1;
        }

        .dark .tag-pill-tone-high:hover {
            background-color: rgba(20, 184, 166, 0.5);
        }

        .dark .tag-pill-tone-medium {
            border-color: #a3e635;
            background-color: rgba(132, 204, 22, 0.12);
            color: #d9f99d;
        }

        .dark .tag-pill-tone-medium:hover {
            background-color: rgba(132, 204, 22, 0.2);
        }

        .dark .tag-pill-tone-low {
            border-color: #38bdf8;
            background-color: rgba(3, 105, 161, 0.38);
            color: #bae6fd;
        }

        .dark .tag-pill-tone-low:hover {
            background-color: rgba(14, 165, 233, 0.48);
        }

        .dark .tag-pill-tone-very-low {
            border-color: #3b82f6;
            background-color: rgba(29, 78, 216, 0.58);
            color: #dbeafe;
        }

        .dark .tag-pill-tone-very-low:hover {
            background-color: rgba(29, 78, 216, 0.7);
        }

        .tag-gradient-slider {
            -webkit-appearance: none;
            appearance: none;
            width: 100%;
            height: 10px;
            border-radius: 9999px;
            background: linear-gradient(90deg, #3b82f6 0%, #22c55e 100%);
            outline: none;
        }

        .tag-gradient-slider::-webkit-slider-runnable-track {
            height: 10px;
            border-radius: 9999px;
            background: linear-gradient(90deg, #3b82f6 0%, #22c55e 100%);
        }

        .tag-gradient-slider::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            height: 16px;
            width: 16px;
            border-radius: 9999px;
            background: #ffffff;
            border: 2px solid #5f2ded;
            margin-top: -3px;
            cursor: pointer;
        }

        .tag-gradient-slider::-moz-range-track {
            height: 10px;
            border-radius: 9999px;
            background: linear-gradient(90deg, #3b82f6 0%, #22c55e 100%);
        }

        .tag-gradient-slider::-moz-range-thumb {
            height: 16px;
            width: 16px;
            border-radius: 9999px;
            background: #ffffff;
            border: 2px solid #5f2ded;
            cursor: pointer;
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const visibilityHeader = document.getElementById('quizVisibilityHeader');
            const visibilityContent = document.getElementById('quizVisibilityContent');
            const visibilityArrow = document.getElementById('quizVisibilityArrow');
            const configHeader = document.getElementById('configHeader');
            const configContent = document.getElementById('configContent');
            const configArrow = document.getElementById('configArrow');
            const restrictionHeader = document.getElementById('quizRestrictionHeader');
            const restrictionContent = document.getElementById('quizRestrictionContent');
            const restrictionArrow = document.getElementById('quizRestrictionArrow');

            let visibilityOpen = false;
            let configOpen = false;
            let restrictionOpen = false;

            const ensureSectionAnimator = (content) => {
                if (!content || content.dataset.sectionAnimatorReady === '1') return;
                content.dataset.sectionAnimatorReady = '1';

                content.addEventListener('transitionend', (event) => {
                    if (event.propertyName !== 'max-height') return;
                    if (content.dataset.sectionOpen !== '1') return;

                    content.style.maxHeight = 'none';
                    content.style.overflow = 'visible';
                });
            };

            const setSectionState = (content, arrow, isOpen) => {
                if (!content || !arrow) return;
                ensureSectionAnimator(content);
                content.dataset.sectionOpen = isOpen ? '1' : '0';

                if (isOpen) {
                    content.style.overflow = 'hidden';
                    content.style.maxHeight = `${content.scrollHeight}px`;
                } else {
                    if (content.style.maxHeight === 'none') {
                        content.style.maxHeight = `${content.scrollHeight}px`;
                        void content.offsetHeight;
                    }
                    content.style.overflow = 'hidden';
                    content.style.maxHeight = '0px';
                }
                arrow.classList.toggle('rotate-180', !isOpen);
            };

            const resizeVisibility = () => {
                if (!visibilityContent) return;
                if (visibilityOpen && visibilityContent.style.maxHeight !== 'none') {
                    visibilityContent.style.maxHeight = visibilityContent.scrollHeight + 'px';
                }
            };

            if (visibilityHeader && visibilityContent && visibilityArrow) {
                setSectionState(visibilityContent, visibilityArrow, visibilityOpen);

                visibilityHeader.addEventListener('click', () => {
                    visibilityOpen = !visibilityOpen;
                    setSectionState(visibilityContent, visibilityArrow, visibilityOpen);
                });
            }

            if (configHeader && configContent && configArrow) {
                setSectionState(configContent, configArrow, configOpen);

                configHeader.addEventListener('click', () => {
                    configOpen = !configOpen;
                    setSectionState(configContent, configArrow, configOpen);
                });
            }

            if (restrictionHeader && restrictionContent && restrictionArrow) {
                setSectionState(restrictionContent, restrictionArrow, restrictionOpen);

                restrictionHeader.addEventListener('click', () => {
                    restrictionOpen = !restrictionOpen;
                    setSectionState(restrictionContent, restrictionArrow, restrictionOpen);
                });
            }            const tagsHeader = document.getElementById('tagsHeader');
            const tagsContent = document.getElementById('tagsContent');
            const tagsArrow = document.getElementById('tagsArrow');
            let tagsOpen = false;

            const resizeTagsSection = () => {
                if (!tagsContent || !tagsOpen) return;
                if (tagsContent.style.maxHeight !== 'none') {
                    tagsContent.style.maxHeight = tagsContent.scrollHeight + 'px';
                }
            };

            const setTagsState = () => {
                if (!tagsContent || !tagsArrow) return;
                setSectionState(tagsContent, tagsArrow, tagsOpen);
                tagsContent.style.paddingBottom = tagsOpen ? '' : '0px';
                tagsContent.style.borderBottomWidth = tagsOpen ? '' : '0px';
            };

            if (tagsHeader && tagsContent && tagsArrow) {
                setTagsState();
                tagsHeader.addEventListener('click', () => {
                    tagsOpen = !tagsOpen;
                    setTagsState();
                });
            }

            const escapeHtml = (value) => {
                return String(value ?? '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#39;');
            };

const tagInputsContainer = document.getElementById('quizTagInputs');
        const tagPills = document.getElementById('tagPills');
        const noTagsMessage = document.getElementById('noTagsMessage');
        const openTagsModalBtn = document.getElementById('openTagsModal');
        const clearTagsBtn = document.getElementById('clearTagsBtn');
        const tagsModal = document.getElementById('tagsModal');
        const closeTagsModalBtn = document.getElementById('closeTagsModal');
        const confirmTagsModalBtn = document.getElementById('confirmTagsModal');
        const tagSearchInput = document.getElementById('tagSearchInput');
        const tagListContainer = document.getElementById('tagListContainer');
        const tagListEmpty = document.getElementById('tagListEmpty');
        const tagPagination = document.getElementById('tagPagination');
        const tagPrevPageBtn = document.getElementById('tagPrevPage');
        const tagNextPageBtn = document.getElementById('tagNextPage');
        const tagPageInfo = document.getElementById('tagPageInfo');
        const openCategoryPickerBtn = document.getElementById('openCategoryPicker');
        const currentCategoryLabel = document.getElementById('currentCategoryLabel');
        const backToTagsBtn = document.getElementById('backToTags');
        const tagCategoryPicker = document.getElementById('tagCategoryPicker');
        const categoryPagination = document.getElementById('categoryPagination');
        const categoryPrevPageBtn = document.getElementById('categoryPrevPage');
        const categoryNextPageBtn = document.getElementById('categoryNextPage');
        const categoryPageInfo = document.getElementById('categoryPageInfo');
        const categoryListEmpty = document.getElementById('categoryListEmpty');
        const metricModeRelevancyBtn = document.getElementById('modeRelevancyBtn');
        const metricModeCoverageBtn = document.getElementById('modeCoverageBtn');
        const metricModeSlider = document.getElementById('tagMetricModeSlider');
        const tagRelevancyContainer = document.getElementById('tagRelevancyContainer');
        const tagMetricsEditor = document.getElementById('tagMetricsEditor');
        const relevancyPagination = document.getElementById('relevancyPagination');
        const relevancyPrevPageBtn = document.getElementById('relevancyPrevPage');
        const relevancyNextPageBtn = document.getElementById('relevancyNextPage');
        const relevancyPageInfo = document.getElementById('relevancyPageInfo');
        const derivedTagNote = document.getElementById('quizTagDerivedNote');

        const tagOptions = Array.from(document.querySelectorAll('.tag-option'));
        const tagCheckboxes = Array.from(document.querySelectorAll('.tag-checkbox'));
        const categoryOptions = Array.from(document.querySelectorAll('.tag-category-option'));

        const tagsPerPage = 6;
        const categoriesPerPage = 6;
        const relevancyCardsPerPage = 3;
        let currentTagPage = 1;
        let currentCategoryPage = 1;
        let currentRelevancyPage = 1;
        let filteredTagOptions = [...tagOptions];
        let filteredCategoryOptions = [...categoryOptions];
        let currentCategory = '';
        let isCategoryMode = false;
        let isRelevancyMode = false;
        let currentMetricMode = 'relevance';
        let focusedMetricTagId = null;
        let isApplyingTagAction = false;
        let derivedTagIds = new Set();
        const minTagLoadingMs = 250;
        const categoryActiveClasses = ['bg-[#F3EEFF]', 'dark:bg-[#34255F]', 'border-[#5F2DED]', 'text-[#5F2DED]', 'dark:text-white'];
        const categoryInactiveClasses = ['bg-transparent', 'border-transparent', 'text-[#5E5F60]', 'dark:text-white'];
        const relevanceLevels = ['very_high', 'high', 'medium', 'low', 'very_low'];
        const relevanceScale = ['very_low', 'low', 'medium', 'high', 'very_high'];
        const relevanceLabels = {
            very_high: 'Very High',
            high: 'High',
            medium: 'Medium',
            low: 'Low',
            very_low: 'Very Low',
        };
        const relevanceRank = {
            very_high: 0,
            high: 1,
            medium: 2,
            low: 3,
            very_low: 4,
        };
        const relevancePillToneClasses = {
            very_high: 'tag-pill-tone-very-high',
            high: 'tag-pill-tone-high',
            medium: 'tag-pill-tone-medium',
            low: 'tag-pill-tone-low',
            very_low: 'tag-pill-tone-very-low',
        };
        const defaultRelevance = @json(config('reviewers.default_tag_relevance', 'medium'));
        const tagMetrics = {};
        const selectedTagIds = new Set(
            Array.from(tagInputsContainer?.querySelectorAll('input[name="tag_ids[]"]') || [])
                .map((input) => String(input.value))
        );

        const getCheckedTagIds = () => {
            return tagCheckboxes
                .filter((checkbox) => checkbox.checked)
                .map((checkbox) => String(checkbox.value));
        };

        const getMetricTargetTagIds = () => {
            if (focusedMetricTagId && selectedTagIds.has(focusedMetricTagId)) {
                return [focusedMetricTagId];
            }
            return getCheckedTagIds();
        };

        const getTagNameById = (tagId) => {
            const option = tagOptions.find((item) => item.dataset.tagId === String(tagId));
            return option?.dataset.tagName || 'Concept';
        };

        const normalizeRelevance = (value) => {
            const normalized = String(value || '').trim().toLowerCase();
            if (relevanceLevels.includes(normalized)) {
                return normalized;
            }
            return relevanceLevels.includes(defaultRelevance) ? defaultRelevance : 'medium';
        };

        const normalizeCoverage = (value) => {
            if (value === null || value === undefined || value === '') {
                return '5';
            }
            const parsed = Number(value);
            if (!Number.isFinite(parsed)) {
                return '5';
            }
            const bounded = Math.min(100, Math.max(5, parsed));
            const snapped = Math.round(bounded / 5) * 5;
            return String(snapped);
        };

        tagOptions.forEach((option) => {
            const tagId = String(option.dataset.tagId || '').trim();
            if (!tagId) return;
            tagMetrics[tagId] = {
                relevance: normalizeRelevance(option.dataset.tagRelevance),
                coverage: normalizeCoverage(option.dataset.tagCoverage),
            };
        });

        const syncTagInputs = () => {
            if (!tagInputsContainer) return;
            tagInputsContainer.innerHTML = '';
            Array.from(selectedTagIds).forEach((tagId) => {
                if (!tagMetrics[tagId]) {
                    tagMetrics[tagId] = {
                        relevance: normalizeRelevance(defaultRelevance),
                        coverage: '5',
                    };
                }

                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'tag_ids[]';
                input.value = tagId;
                tagInputsContainer.appendChild(input);

                const relevanceInput = document.createElement('input');
                relevanceInput.type = 'hidden';
                relevanceInput.name = `tag_relevance[${tagId}]`;
                relevanceInput.value = tagMetrics[tagId].relevance;
                tagInputsContainer.appendChild(relevanceInput);

                const coverageInput = document.createElement('input');
                coverageInput.type = 'hidden';
                coverageInput.name = `tag_coverage[${tagId}]`;
                coverageInput.value = tagMetrics[tagId].coverage;
                tagInputsContainer.appendChild(coverageInput);
            });
        };

        const updateDerivedTagNote = () => {
            if (!derivedTagNote) return;
            if (derivedTagIds.size > 0) {
                derivedTagNote.classList.remove('hidden');
            } else {
                derivedTagNote.classList.add('hidden');
            }
        };

        const renderTagPills = () => {
            if (!tagPills) return;
            tagPills.innerHTML = '';
            const tagEntries = Array.from(selectedTagIds)
                .map((tagId) => {
                    const metric = tagMetrics[tagId] || {
                        relevance: normalizeRelevance(defaultRelevance),
                        coverage: '5',
                    };
                    const relevance = normalizeRelevance(metric.relevance);
                    const coverage = Number.parseInt(normalizeCoverage(metric.coverage), 10);
                    const normalizedCoverage = Number.isFinite(coverage) ? coverage : 5;
                    return {
                        id: tagId,
                        name: getTagNameById(tagId),
                        relevance,
                        coverage: normalizedCoverage,
                        relevanceRank: relevanceRank[relevance] ?? relevanceRank.medium,
                    };
                })
                .sort((a, b) => {
                    if (a.relevanceRank !== b.relevanceRank) {
                        return a.relevanceRank - b.relevanceRank;
                    }
                    if (a.coverage !== b.coverage) {
                        return b.coverage - a.coverage;
                    }
                    return a.name.localeCompare(b.name);
                });

            if (tagEntries.length === 0) {
                noTagsMessage?.classList.remove('hidden');
                updateDerivedTagNote();
            resizeTagsSection();
                return;
            }

            noTagsMessage?.classList.add('hidden');
            tagEntries.forEach((tag) => {
                const pill = document.createElement('button');
                pill.type = 'button';
                const toneClass = relevancePillToneClasses[tag.relevance] || relevancePillToneClasses.medium;
                pill.className = `tag-pill inline-flex items-center gap-1.5 px-3 py-1 rounded-full border transition ${toneClass}`;
                pill.dataset.tagId = String(tag.id);
                pill.title = 'Edit relevance';
                pill.innerHTML = `
                    <span class="text-sm">${escapeHtml(tag.name)}</span>
                    <span class="ml-1 text-[10px] leading-none opacity-80">${tag.coverage}%</span>
                    <span class="tag-pill-remove text-xs leading-none opacity-70 hover:opacity-100 hover:text-red-500">x</span>
                `;
                tagPills.appendChild(pill);
            });
            updateDerivedTagNote();
            resizeTagsSection();
        };

        const clearTagSelection = () => {
            selectedTagIds.clear();
            focusedMetricTagId = null;
            tagCheckboxes.forEach((checkbox) => {
                checkbox.checked = false;
            });
            syncTagInputs();
            renderTagPills();
        };

        const setTagSelections = (tagIds) => {
            selectedTagIds.clear();
            tagIds.forEach((tagId) => {
                if (tagId !== null && tagId !== '') {
                    selectedTagIds.add(String(tagId));
                }
            });
            if (focusedMetricTagId && !selectedTagIds.has(focusedMetricTagId)) {
                focusedMetricTagId = null;
            }
            tagCheckboxes.forEach((checkbox) => {
                checkbox.checked = selectedTagIds.has(String(checkbox.value));
            });
            syncTagInputs();
            renderTagPills();
        };

        if (selectedTagIds.size > 0) {
            syncTagInputs();
            renderTagPills();
        }

        const toggleTagsModal = (show) => {
            if (!tagsModal) return;
            tagsModal.classList.toggle('hidden', !show);
            tagsModal.classList.toggle('flex', show);
            document.body.classList.toggle('modal-open', show);
        };

        const updateSearchPlaceholder = () => {
            if (!tagSearchInput) return;
            if (isRelevancyMode) {
                tagSearchInput.placeholder = 'Search selected tags...';
                return;
            }
            tagSearchInput.placeholder = isCategoryMode ? 'Search categories...' : 'Search tags...';
        };

        const setMetricModeSwitchState = () => {
            const isCoverageMode = currentMetricMode === 'coverage';
            if (metricModeSlider) {
                metricModeSlider.style.opacity = isRelevancyMode ? '1' : '0';
                metricModeSlider.style.transform = isCoverageMode ? 'translateX(100%)' : 'translateX(0)';
            }

            const setButtonState = (button, isActive) => {
                if (!button) return;
                button.classList.toggle('text-white', isRelevancyMode && isActive);
                button.classList.toggle('text-[#5E5F60]', !(isRelevancyMode && isActive));
                button.classList.toggle('dark:text-gray-300', !(isRelevancyMode && isActive));
            };

            setButtonState(metricModeRelevancyBtn, !isCoverageMode);
            setButtonState(metricModeCoverageBtn, isCoverageMode);
        };

        const getRelevancyTotalPages = (selectedIds) => {
            if (!Array.isArray(selectedIds) || selectedIds.length === 0) {
                return 0;
            }
            return Math.ceil(selectedIds.length / relevancyCardsPerPage);
        };

        const getFilteredRelevancyTagIds = (selectedIds) => {
            if (!Array.isArray(selectedIds)) {
                return [];
            }
            const term = (tagSearchInput?.value || '').trim().toLowerCase();
            if (!term) {
                return selectedIds;
            }

            return selectedIds.filter((id) => {
                const tagId = String(id);
                const option = tagOptions.find((item) => item.dataset.tagId === tagId);
                const name = String(option?.dataset.tagName || '').toLowerCase();
                const category = String(option?.dataset.tagCategory || '').toLowerCase();
                return name.includes(term) || category.includes(term);
            });
        };

        const syncRelevancyPaginationState = (totalPages) => {
            if (!relevancyPagination || !relevancyPrevPageBtn || !relevancyNextPageBtn || !relevancyPageInfo) {
                return;
            }

            const hasPages = totalPages > 0;
            const clampedPages = hasPages ? totalPages : 1;
            const page = Math.min(clampedPages, Math.max(1, currentRelevancyPage));
            currentRelevancyPage = page;

            relevancyPageInfo.textContent = `Page ${page} of ${clampedPages}`;
            relevancyPrevPageBtn.disabled = page <= 1;
            relevancyNextPageBtn.disabled = page >= clampedPages;
            relevancyPagination.classList.toggle('hidden', !isRelevancyMode || totalPages <= 1);
        };

        const setCategoryButtonState = (button, isActive) => {
            if (!button) return;
            if (isActive) {
                categoryInactiveClasses.forEach((cls) => button.classList.remove(cls));
                categoryActiveClasses.forEach((cls) => button.classList.add(cls));
                return;
            }
            categoryActiveClasses.forEach((cls) => button.classList.remove(cls));
            categoryInactiveClasses.forEach((cls) => button.classList.add(cls));
        };

        const refreshCategoryButtonStates = () => {
            const selectedCategory = String(currentCategory || '').trim().toLowerCase();
            categoryOptions.forEach((button) => {
                const buttonCategory = String(button.dataset.tagCategory || '').trim().toLowerCase();
                setCategoryButtonState(button, buttonCategory === selectedCategory);
            });
        };

        const setTagActionLoading = (button, fallbackText) => {
            if (!button) return;
            if (window.AdminModalLoading && typeof window.AdminModalLoading.setLoading === 'function') {
                window.AdminModalLoading.setLoading(button, {
                    text: button.dataset.loadingText || fallbackText,
                });
                return;
            }
            button.disabled = true;
            button.classList.add('opacity-75', 'cursor-wait');
        };

        const clearTagActionLoading = (button) => {
            if (!button) return;
            if (window.AdminModalLoading && typeof window.AdminModalLoading.clearLoading === 'function') {
                window.AdminModalLoading.clearLoading(button);
                return;
            }
            button.disabled = false;
            button.classList.remove('opacity-75', 'cursor-wait');
        };

        const finishTagAction = (triggerBtn, startedAt) => {
            const elapsed = Date.now() - startedAt;
            const remaining = Math.max(0, minTagLoadingMs - elapsed);

            window.setTimeout(() => {
                clearTagActionLoading(triggerBtn);
                if (confirmTagsModalBtn && triggerBtn !== confirmTagsModalBtn) {
                    confirmTagsModalBtn.disabled = false;
                }
                if (clearTagsBtn && triggerBtn !== clearTagsBtn) {
                    clearTagsBtn.disabled = false;
                }
                isApplyingTagAction = false;
            }, remaining);
        };

        const toggleCategoryPicker = (show) => {
            if (isRelevancyMode) {
                return;
            }
            isCategoryMode = show;
            tagCategoryPicker?.classList.toggle('hidden', !show);
            tagListContainer?.classList.toggle('hidden', show);
            tagRelevancyContainer?.classList.add('hidden');
            backToTagsBtn?.classList.toggle('hidden', !(show || isRelevancyMode));
            if (tagPagination) {
                tagPagination.classList.toggle('hidden', show);
            }
            if (categoryPagination) {
                categoryPagination.classList.toggle('hidden', !show);
            }
            categoryListEmpty?.classList.add('hidden');
            updateSearchPlaceholder();
            if (show) refreshCategoryButtonStates();
        };

        const toggleRelevancyMode = (show, mode = currentMetricMode) => {
            currentMetricMode = mode === 'coverage' ? 'coverage' : 'relevance';
            isRelevancyMode = show;

            if (show) {
                currentRelevancyPage = 1;
                isCategoryMode = false;
                tagCategoryPicker?.classList.add('hidden');
                tagListContainer?.classList.add('hidden');
                tagRelevancyContainer?.classList.remove('hidden');
                backToTagsBtn?.classList.remove('hidden');
                tagPagination?.classList.add('hidden');
                categoryPagination?.classList.add('hidden');
                categoryListEmpty?.classList.add('hidden');
                tagListEmpty?.classList.add('hidden');
                renderTagMetricsEditor(getMetricTargetTagIds());
            } else {
                tagRelevancyContainer?.classList.add('hidden');
                syncRelevancyPaginationState(0);
                toggleCategoryPicker(false);
                filterTags();
            }

            updateSearchPlaceholder();
            setMetricModeSwitchState();
        };

        const getTagTotalPages = () => {
            if (filteredTagOptions.length === 0) return 0;
            return Math.ceil(filteredTagOptions.length / tagsPerPage);
        };

        const getCategoryTotalPages = () => {
            if (filteredCategoryOptions.length === 0) return 0;
            return Math.ceil(filteredCategoryOptions.length / categoriesPerPage);
        };

        const applyTagPagination = () => {
            if (tagOptions.length === 0) {
                tagListEmpty?.classList.add('hidden');
                if (tagPagination) tagPagination.classList.add('hidden');
                return;
            }

            const totalPages = getTagTotalPages();
            if (currentTagPage > totalPages) {
                currentTagPage = totalPages || 1;
            }

            const start = (currentTagPage - 1) * tagsPerPage;
            const end = start + tagsPerPage;
            const visibleOptions = new Set(filteredTagOptions.slice(start, end));

            tagOptions.forEach((option) => {
                option.classList.toggle('hidden', !visibleOptions.has(option));
            });

            if (tagPageInfo) {
                tagPageInfo.textContent = totalPages > 0
                    ? `Page ${currentTagPage} of ${totalPages}`
                    : 'Page 0 of 0';
            }
            if (tagPagination) {
                tagPagination.classList.toggle('hidden', totalPages <= 1);
            }
            if (tagPrevPageBtn) {
                tagPrevPageBtn.disabled = currentTagPage <= 1;
            }
            if (tagNextPageBtn) {
                tagNextPageBtn.disabled = currentTagPage >= totalPages;
            }
            if (tagListEmpty) {
                tagListEmpty.classList.toggle('hidden', filteredTagOptions.length > 0);
            }
        };

        const applyCategoryPagination = () => {
            if (categoryOptions.length === 0) {
                categoryPagination?.classList.add('hidden');
                categoryListEmpty?.classList.add('hidden');
                return;
            }

            const totalPages = getCategoryTotalPages();
            if (currentCategoryPage > totalPages) {
                currentCategoryPage = totalPages || 1;
            }

            const start = (currentCategoryPage - 1) * categoriesPerPage;
            const end = start + categoriesPerPage;
            const visibleOptions = new Set(filteredCategoryOptions.slice(start, end));

            categoryOptions.forEach((option) => {
                option.classList.toggle('hidden', !visibleOptions.has(option));
            });

            if (categoryPageInfo) {
                categoryPageInfo.textContent = totalPages > 0
                    ? `Page ${currentCategoryPage} of ${totalPages}`
                    : 'Page 0 of 0';
            }
            if (categoryPagination) {
                categoryPagination.classList.toggle('hidden', totalPages <= 1);
            }
            if (categoryPrevPageBtn) {
                categoryPrevPageBtn.disabled = currentCategoryPage <= 1;
            }
            if (categoryNextPageBtn) {
                categoryNextPageBtn.disabled = currentCategoryPage >= totalPages;
            }
            if (categoryListEmpty) {
                categoryListEmpty.classList.toggle('hidden', filteredCategoryOptions.length > 0);
            }
            refreshCategoryButtonStates();
        };

        const filterTags = () => {
            const query = (tagSearchInput?.value || '').toLowerCase().trim();
            const categoryValue = currentCategory.toLowerCase();
            filteredTagOptions = tagOptions.filter((option) => {
                const name = (option.dataset.tagName || '').toLowerCase();
                const category = (option.dataset.tagCategory || '').toLowerCase();
                const matchesCategory = !categoryValue || category === categoryValue;
                const matchesQuery = !query || name.includes(query) || category.includes(query);
                return matchesCategory && matchesQuery;
            });

            currentTagPage = 1;
            applyTagPagination();
        };

        const filterCategories = () => {
            const query = (tagSearchInput?.value || '').toLowerCase().trim();
            filteredCategoryOptions = categoryOptions.filter((option) => {
                const label = (option.dataset.tagCategory || 'All categories').toLowerCase();
                return !query || label.includes(query);
            });
            currentCategoryPage = 1;
            applyCategoryPagination();
        };

        const renderTagMetricsEditor = (selectedIds) => {
            if (!tagMetricsEditor) return;

            if (!selectedIds.length) {
                tagMetricsEditor.innerHTML = '<p class="text-xs text-gray-500 dark:text-gray-300">Select at least one tag to configure relevance and coverage.</p>';
                syncRelevancyPaginationState(0);
                return;
            }

            const filteredSelectedIds = getFilteredRelevancyTagIds(selectedIds);
            if (!filteredSelectedIds.length) {
                tagMetricsEditor.innerHTML = '<p class="text-xs text-gray-500 dark:text-gray-300">No selected tags match your search.</p>';
                syncRelevancyPaginationState(0);
                return;
            }

            const totalPages = getRelevancyTotalPages(filteredSelectedIds);
            if (currentRelevancyPage > totalPages) {
                currentRelevancyPage = totalPages || 1;
            }

            const start = (currentRelevancyPage - 1) * relevancyCardsPerPage;
            const end = start + relevancyCardsPerPage;
            const visibleSelectedIds = filteredSelectedIds.slice(start, end);

            const rows = visibleSelectedIds.map((id) => {
                const tagId = String(id);
                const option = tagOptions.find((item) => item.dataset.tagId === tagId);
                const tagName = option?.dataset.tagName || 'Tag';
                const metric = tagMetrics[tagId] || {
                    relevance: normalizeRelevance(defaultRelevance),
                    coverage: '5',
                };
                const currentRelevance = normalizeRelevance(metric.relevance);
                const relevanceIndex = Math.max(0, relevanceScale.indexOf(currentRelevance));
                const currentCoverage = normalizeCoverage(metric.coverage);
                const isCoverageMode = currentMetricMode === 'coverage';

                return `
                    <div class="border border-[#94A3B8] dark:border-[#475569] rounded-none p-3 transition-colors hover:bg-[#EFF6FF] dark:hover:bg-[#12243D] focus-within:bg-[#DBEAFE] dark:focus-within:bg-[#1E3A5F]">
                        <p class="text-sm text-[#373737] dark:text-white font-medium">${escapeHtml(tagName)}</p>
                        <div class="mt-2 ${isCoverageMode ? 'hidden' : ''}" data-metric-relevance-panel="${tagId}">
                            <div class="flex items-center gap-3">
                                <div class="flex-1" style="padding-right: 0.5in;">
                                    <input type="range"
                                        min="0" max="4" step="1"
                                        value="${relevanceIndex}"
                                        data-metric-relevance-slider="${tagId}"
                                        class="tag-gradient-slider w-full">
                                </div>
                                <span data-metric-relevance-current="${tagId}" style="width: 100px; flex: 0 0 100px;" class="shrink-0 whitespace-nowrap text-right text-sm font-semibold font-poppins text-[#334155] dark:text-white">
                                    ${escapeHtml(relevanceLabels[currentRelevance] || 'Medium')}
                                </span>
                            </div>
                        </div>
                        <div class="mt-2 ${isCoverageMode ? '' : 'hidden'}" data-metric-coverage-panel="${tagId}">
                            <div class="flex items-center gap-3">
                                <div class="flex-1" style="padding-right: 0.5in;">
                                    <input type="range"
                                        min="5" max="100" step="5"
                                        value="${currentCoverage}"
                                        data-metric-coverage-slider="${tagId}"
                                        class="tag-gradient-slider w-full">
                                </div>
                                <span data-metric-coverage-current="${tagId}" style="width: 100px; flex: 0 0 100px;" class="shrink-0 whitespace-nowrap text-right text-sm font-semibold font-poppins text-[#334155] dark:text-white">
                                    ${currentCoverage}%
                                </span>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');

            tagMetricsEditor.innerHTML = rows;

            tagMetricsEditor.querySelectorAll('[data-metric-relevance-slider]').forEach((element) => {
                element.addEventListener('input', (event) => {
                    const target = event.target;
                    if (!(target instanceof HTMLInputElement)) return;
                    const tagId = String(target.getAttribute('data-metric-relevance-slider') || '');
                    if (!tagId) return;
                    const rawIndex = Number.parseInt(target.value, 10);
                    const clampedIndex = Number.isFinite(rawIndex)
                        ? Math.min(4, Math.max(0, rawIndex))
                        : 0;
                    target.value = String(clampedIndex);
                    const relevanceValue = normalizeRelevance(relevanceScale[clampedIndex] || defaultRelevance);

                    if (!tagMetrics[tagId]) {
                        tagMetrics[tagId] = { relevance: normalizeRelevance(defaultRelevance), coverage: '5' };
                    }
                    tagMetrics[tagId].relevance = relevanceValue;
                    const relevanceLabel = tagMetricsEditor.querySelector(`[data-metric-relevance-current="${tagId}"]`);
                    if (relevanceLabel) {
                        relevanceLabel.textContent = relevanceLabels[relevanceValue] || 'Medium';
                    }
                    syncTagInputs();
                    renderTagPills();
                });
            });

            tagMetricsEditor.querySelectorAll('[data-metric-coverage-slider]').forEach((element) => {
                element.addEventListener('input', (event) => {
                    const target = event.target;
                    if (!(target instanceof HTMLInputElement)) return;
                    const tagId = String(target.getAttribute('data-metric-coverage-slider') || '');
                    if (!tagId) return;
                    if (!tagMetrics[tagId]) {
                        tagMetrics[tagId] = { relevance: normalizeRelevance(defaultRelevance), coverage: '5' };
                    }
                    const normalizedCoverage = normalizeCoverage(target.value);
                    target.value = normalizedCoverage;
                    tagMetrics[tagId].coverage = normalizedCoverage;
                    const coverageLabel = tagMetricsEditor.querySelector(`[data-metric-coverage-current="${tagId}"]`);
                    if (coverageLabel) {
                        coverageLabel.textContent = `${normalizedCoverage}%`;
                    }
                    syncTagInputs();
                    renderTagPills();
                });
            });

            syncRelevancyPaginationState(totalPages);
        };

        const updateTagErrorState = () => {
            const tagError = document.getElementById('reviewerTagError');
            const count = document.querySelectorAll(
                '#quizTagInputs input[name="tag_ids[]"]'
            ).length;

            if (count > 0) {
                tagError?.classList.add('hidden');
            }
        };

        const runTagAction = (triggerBtn, fallbackText, action) => {
            if (isApplyingTagAction) return;
            isApplyingTagAction = true;
            const actionStartAt = Date.now();

            setTagActionLoading(triggerBtn, fallbackText);
            if (confirmTagsModalBtn && triggerBtn !== confirmTagsModalBtn) {
                confirmTagsModalBtn.disabled = true;
            }
            if (clearTagsBtn && triggerBtn !== clearTagsBtn) {
                clearTagsBtn.disabled = true;
            }

            requestAnimationFrame(() => {
                try {
                    action();
                } finally {
                    finishTagAction(triggerBtn, actionStartAt);
                }
            });
        };

        openTagsModalBtn?.addEventListener('click', () => {
            focusedMetricTagId = null;
            if (tagSearchInput) {
                tagSearchInput.value = '';
            }
            toggleRelevancyMode(false);
            toggleCategoryPicker(false);
            filterTags();
            tagCheckboxes.forEach((checkbox) => {
                checkbox.checked = selectedTagIds.has(String(checkbox.value));
            });
            toggleTagsModal(true);
        });

        closeTagsModalBtn?.addEventListener('click', () => {
            focusedMetricTagId = null;
            toggleRelevancyMode(false);
            toggleCategoryPicker(false);
            toggleTagsModal(false);
        });

        confirmTagsModalBtn?.addEventListener('click', () => {
            runTagAction(confirmTagsModalBtn, 'Saving...', () => {
                const selected = tagCheckboxes
                    .filter((checkbox) => checkbox.checked)
                    .map((checkbox) => checkbox.value);
                setTagSelections(selected);
                updateTagErrorState();
                renderTagMetricsEditor(Array.from(selectedTagIds));
                focusedMetricTagId = null;
                toggleCategoryPicker(false);
                toggleRelevancyMode(false);
                toggleTagsModal(false);
            });
        });

        tagSearchInput?.addEventListener('input', () => {
            if (isRelevancyMode) {
                currentRelevancyPage = 1;
                renderTagMetricsEditor(getMetricTargetTagIds());
                return;
            }
            if (isCategoryMode) {
                filterCategories();
            } else {
                filterTags();
            }
        });

        tagPrevPageBtn?.addEventListener('click', () => {
            if (currentTagPage <= 1) return;
            currentTagPage -= 1;
            applyTagPagination();
        });

        tagNextPageBtn?.addEventListener('click', () => {
            const totalPages = getTagTotalPages();
            if (currentTagPage >= totalPages) return;
            currentTagPage += 1;
            applyTagPagination();
        });

        categoryPrevPageBtn?.addEventListener('click', () => {
            if (currentCategoryPage <= 1) return;
            currentCategoryPage -= 1;
            applyCategoryPagination();
        });

        categoryNextPageBtn?.addEventListener('click', () => {
            const totalPages = getCategoryTotalPages();
            if (currentCategoryPage >= totalPages) return;
            currentCategoryPage += 1;
            applyCategoryPagination();
        });

        metricModeRelevancyBtn?.addEventListener('click', () => {
            toggleRelevancyMode(true, 'relevance');
        });

        metricModeCoverageBtn?.addEventListener('click', () => {
            toggleRelevancyMode(true, 'coverage');
        });

        relevancyPrevPageBtn?.addEventListener('click', () => {
            if (currentRelevancyPage <= 1) return;
            currentRelevancyPage -= 1;
            renderTagMetricsEditor(getMetricTargetTagIds());
        });

        relevancyNextPageBtn?.addEventListener('click', () => {
            const totalPages = getRelevancyTotalPages(getFilteredRelevancyTagIds(getMetricTargetTagIds()));
            if (currentRelevancyPage >= totalPages) return;
            currentRelevancyPage += 1;
            renderTagMetricsEditor(getMetricTargetTagIds());
        });

        openCategoryPickerBtn?.addEventListener('click', () => {
            if (isRelevancyMode) {
                toggleRelevancyMode(false);
            }
            currentCategoryPage = 1;
            toggleCategoryPicker(true);
            filterCategories();
        });

        backToTagsBtn?.addEventListener('click', () => {
            if (isRelevancyMode) {
                focusedMetricTagId = null;
                toggleRelevancyMode(false);
                return;
            }
            toggleCategoryPicker(false);
            filterTags();
        });

        categoryOptions.forEach((button) => {
            button.addEventListener('click', () => {
                currentCategory = button.dataset.tagCategory || '';
                if (currentCategoryLabel) {
                    currentCategoryLabel.textContent = currentCategory || 'All categories';
                }
                refreshCategoryButtonStates();
                toggleCategoryPicker(false);
                filterTags();
            });
        });

        tagPills?.addEventListener('click', (event) => {
            const target = event.target;
            if (!(target instanceof Element)) return;
            const pill = target.closest('.tag-pill');
            if (!pill) return;
            const tagId = String(pill.dataset.tagId || '');
            if (!tagId) return;

            const removeBtn = target.closest('.tag-pill-remove');
            if (removeBtn) {
                selectedTagIds.delete(tagId);
                if (focusedMetricTagId === tagId) {
                    focusedMetricTagId = null;
                }
                tagCheckboxes.forEach((checkbox) => {
                    checkbox.checked = selectedTagIds.has(String(checkbox.value));
                });
                syncTagInputs();
                renderTagPills();
                return;
            }

            if (!selectedTagIds.has(tagId)) {
                return;
            }

            focusedMetricTagId = tagId;
            currentMetricMode = 'relevance';
            currentRelevancyPage = 1;
            if (tagSearchInput) {
                tagSearchInput.value = '';
            }
            toggleCategoryPicker(false);
            filterTags();
            tagCheckboxes.forEach((checkbox) => {
                checkbox.checked = selectedTagIds.has(String(checkbox.value));
            });
            toggleTagsModal(true);
            toggleRelevancyMode(true, 'relevance');
        });

        tagCheckboxes.forEach((checkbox) => {
            checkbox.addEventListener('change', () => {
                if (!isRelevancyMode) return;
                currentRelevancyPage = 1;
                renderTagMetricsEditor(getMetricTargetTagIds());
            });
        });

        clearTagsBtn?.addEventListener('click', () => {
            runTagAction(clearTagsBtn, 'Clearing...', () => {
                clearTagSelection();
            });
        });

        
            window.addEventListener('resize', resizeTagsSection);

            const visibilityToggle = document.getElementById('quiz_is_visible');
            const openDate = document.getElementById('open_date');
            const openTime = document.getElementById('open_time');
            const dueDate = document.getElementById('due_date');
            const dueTime = document.getElementById('due_time');
            const openDateTimeError = document.getElementById('openDateTimeError');
            const dueDateTimeError = document.getElementById('dueDateTimeError');
            const maxAttemptInput = document.getElementById('max_attempt');
            const maxAttemptError = document.getElementById('maxAttemptError');
            const modal = document.getElementById('deleteQuizModal');
            const openBtn = document.getElementById('openDeleteModal');
            const closeBtn = document.getElementById('cancelDelete');
            const saveModal = document.getElementById('saveSettingsModal');
            const cancelSaveBtn = document.getElementById('cancelSaveSettings');
            const confirmSaveBtn = document.getElementById('confirmSaveSettings');

            const parseMaxAttempt = () => {
                if (!maxAttemptInput) return NaN;
                const parsed = parseInt(String(maxAttemptInput.value ?? '').trim(), 10);
                return Number.isFinite(parsed) ? parsed : NaN;
            };

            const clearMaxAttemptError = () => {
                if (!maxAttemptError) return;
                maxAttemptError.classList.add('hidden');
            };

            const showMaxAttemptError = (message = 'Maximum attempts must be at least 1.') => {
                if (!maxAttemptError) return;
                maxAttemptError.textContent = message;
                maxAttemptError.classList.remove('hidden');
            };

            const validateMaxAttempt = () => {
                const parsed = parseMaxAttempt();
                if (!Number.isInteger(parsed) || parsed < 1) {
                    showMaxAttemptError('Maximum attempts must be at least 1.');
                    return null;
                }
                clearMaxAttemptError();
                return parsed;
            };

            const clearOpenDateTimeError = () => {
                if (!openDateTimeError) return;
                openDateTimeError.classList.add('hidden');
            };

            const showOpenDateTimeError = (message = 'Open date and open time must both be provided.') => {
                if (!openDateTimeError) return;
                openDateTimeError.textContent = message;
                openDateTimeError.classList.remove('hidden');
            };

            const clearDueDateTimeError = () => {
                if (!dueDateTimeError) return;
                dueDateTimeError.classList.add('hidden');
            };

            const showDueDateTimeError = (message = 'Due date and due time are required.') => {
                if (!dueDateTimeError) return;
                dueDateTimeError.textContent = message;
                dueDateTimeError.classList.remove('hidden');
            };

            const handleVisibilityToggle = () => {
                // Keep date/time inputs editable regardless of visibility toggle.
                resizeVisibility();
            };

            maxAttemptInput?.addEventListener('input', () => {
                clearMaxAttemptError();
            });
            maxAttemptInput?.addEventListener('blur', () => {
                const currentValue = String(maxAttemptInput?.value ?? '').trim();
                if (!currentValue) return;
                validateMaxAttempt();
            });
            openDate?.addEventListener('input', clearOpenDateTimeError);
            openTime?.addEventListener('input', clearOpenDateTimeError);
            openDate?.addEventListener('input', clearDueDateTimeError);
            openTime?.addEventListener('input', clearDueDateTimeError);
            dueDate?.addEventListener('input', clearDueDateTimeError);
            dueTime?.addEventListener('input', clearDueDateTimeError);

            handleVisibilityToggle();
            visibilityToggle?.addEventListener('change', handleVisibilityToggle);

            openBtn?.addEventListener('click', () => {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            });
            closeBtn?.addEventListener('click', () => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            });

                const openSaveSettingsModal = () => {
                    saveModal?.classList.remove('hidden');
                    saveModal?.classList.add('flex');
                    document.body.classList.add('modal-open');
                };

                const closeSaveSettingsModal = () => {
                    saveModal?.classList.add('hidden');
                    saveModal?.classList.remove('flex');
                    document.body.classList.remove('modal-open');
                };

                document.getElementById('openSaveModal').addEventListener('click', () => {
                    openSaveSettingsModal();
                });

                cancelSaveBtn?.addEventListener('click', () => {
                    closeSaveSettingsModal();
                });

                confirmSaveBtn?.addEventListener('click', () => {
                    const openDateValue = openDate?.value || '';
                    const openTimeValue = openTime?.value || '';
                    const dueDateValue = dueDate?.value || '';
                    const dueTimeValue = dueTime?.value || '';
                    const isVisible = visibilityToggle?.checked;
                    const maxAttemptValue = validateMaxAttempt();

                    if (maxAttemptValue === null) {
                        closeSaveSettingsModal();
                        maxAttemptInput?.focus();
                        return;
                    }

                    if (isVisible) {
                        const hasOpenDate = Boolean(openDateValue);
                        const hasOpenTime = Boolean(openTimeValue);
                        if ((hasOpenDate && !hasOpenTime) || (!hasOpenDate && hasOpenTime) || !hasOpenDate || !hasOpenTime) {
                            showOpenDateTimeError('Open date and open time are required.');
                            closeSaveSettingsModal();
                            if (!hasOpenDate) {
                                openDate?.focus();
                            } else {
                                openTime?.focus();
                            }
                            return;
                        }

                        if (!dueDateValue || !dueTimeValue) {
                            showDueDateTimeError('Due date and due time are required.');
                            closeSaveSettingsModal();
                            if (!dueDateValue) {
                                dueDate?.focus();
                            } else {
                                dueTime?.focus();
                            }
                            return;
                        }

                        const openAt = new Date(`${openDateValue}T${openTimeValue}`);
                        const dueAtValue = new Date(`${dueDateValue}T${dueTimeValue}`);

                        if (!Number.isNaN(openAt.getTime()) && !Number.isNaN(dueAtValue.getTime()) && dueAtValue < openAt) {
                            showDueDateTimeError('Due date and time must be on or after the open date and time.');
                            closeSaveSettingsModal();
                            dueDate?.focus();
                            return;
                        }

                        clearOpenDateTimeError();
                        clearDueDateTimeError();
                    }

                    document.getElementById('visible_input').value = isVisible ? '1' : '0';
                    document.getElementById('is_open_input').value = isVisible
                        ? (document.getElementById('is_open_status')?.value || '0')
                        : '0';
                    document.getElementById('open_date_input').value = openDateValue;
                    document.getElementById('open_time_input').value = openTimeValue;
                    document.getElementById('due_date_input').value = dueDateValue;
                    document.getElementById('due_time_input').value = dueTimeValue;
                    document.getElementById('accepting_input').value = document.getElementById('accepting').value;
                    document.getElementById('max_attempt_input').value = String(maxAttemptValue);
                    document.getElementById('shuffle_questions_on_publish_input').value = document.getElementById('shuffle_questions_on_publish')?.checked ? '1' : '0';
                    document.getElementById('student_can_review_input').value = document.getElementById('student_can_review')?.checked ? '1' : '0';
                    document.getElementById('student_can_view_score_input').value = document.getElementById('student_can_view_score')?.checked ? '1' : '0';
                    document.getElementById('student_can_view_correct_answers_input').value = document.getElementById('student_can_view_correct_answers')?.checked ? '1' : '0';
                    // document.getElementById('total_score_input').value = document.getElementById('total_score').value;

                    if (window.AdminModalLoading && typeof window.AdminModalLoading.setLoading === 'function') {
                        window.AdminModalLoading.setLoading(confirmSaveBtn, {
                            text: confirmSaveBtn?.dataset?.loadingText || 'Saving...',
                        });
                    } else {
                        confirmSaveBtn.disabled = true;
                        confirmSaveBtn.classList.add('opacity-75', 'cursor-wait');
                    }
                    cancelSaveBtn && (cancelSaveBtn.disabled = true);

                    document.getElementById('quizForm').submit();
                });
        });
    </script>

    <x-lmsmodal />
@endsection

<div id="deleteQuizModal" 
         class="fixed inset-0 items-center justify-center bg-black bg-opacity-50 hidden z-50">
        <div class="bg-white dark:bg-[#1E1E2D] p-6 rounded-lg w-96">
            <h2 class="text-xl font-bold mb-4 text-red-600">Delete Quiz?</h2>
            <p class="text-[#373737] dark:text-white mb-6">
                This action cannot be undone. All questions will be deleted.
            </p>
            <div class="flex justify-end gap-3">
                <div>
                    <button 
                        type="button"
                        id="cancelDelete"
                        class="px-4 py-2 bg-gray-300 rounded dark:bg-gray-600 dark:text-white">
                        Cancel
                    </button>
                </div>
                <form class="m-0" action="{{ route('teacher.quiz.delete', [$section->id, $subject->id, $quiz->id]) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button 
                        type="submit"
                        data-loading-text="Deleting..."
                        class="px-4 py-2 bg-red-600 text-white rounded disabled:opacity-75 disabled:cursor-wait">
                        Delete
                    </button>
                </form>
            </div>
        </div>
    </div>

<div id="saveSettingsModal"
     class="fixed inset-0 items-center justify-center bg-black bg-opacity-50 hidden z-50">
    <div class="bg-white dark:bg-[#1E1E2D] p-6 rounded-lg w-96">
        <h2 class="text-xl font-bold mb-4 text-gray-800 dark:text-white">Save Settings?</h2>
        <p class="text-[#373737] dark:text-white mb-6">
            Are you sure you want to save these quiz settings?
        </p>
        <div class="flex justify-end gap-3">
            <button
                type="button"
                id="cancelSaveSettings"
                class="px-4 py-2 bg-gray-300 rounded dark:bg-gray-600 dark:text-white">
                Cancel
            </button>
            <button
                type="button"
                id="confirmSaveSettings"
                data-loading-text="Saving..."
                class="px-4 py-2 bg-primaryColor text-white rounded hover:opacity-90">
                Confirm
            </button>
        </div>
    </div>
</div>

