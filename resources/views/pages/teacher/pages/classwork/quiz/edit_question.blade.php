@extends('pages.teacher.layout')

@section('content')

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
            $questionBankRoutes = [
                'index' => route('teacher.question-bank.index', ['section_id' => $section->id, 'subject_id' => $subject->id]),
                'show' => route('teacher.question-bank.show', ['section_id' => $section->id, 'subject_id' => $subject->id, 'item' => '__ITEM__']),
            ];
        @endphp

    <script>
        window.__quizQuestionFormInitEdit = {
            questions: @json(old('questions', collect($questions)->values())),
            pageSize: @json(old('pagination', $paginationValue)),
            currentPage: @json(old('current_page', 1)),
            defaultType: @json(old('default_question_type', 'multiple_choice')),
            errors: @json($errors->toArray()),
            availableTags: @json($availableTags ?? []),
            quizTagMetrics: @json($quizTagMetrics ?? []),
            defaultTagRelevance: @json(config('reviewers.default_tag_relevance', 'medium')),
            questionBankRoutes: @json($questionBankRoutes),
        };
    </script>

    <div
        x-data="quizForm(window.__quizQuestionFormInitEdit.questions, window.__quizQuestionFormInitEdit.pageSize, window.__quizQuestionFormInitEdit.currentPage, window.__quizQuestionFormInitEdit.defaultType, window.__quizQuestionFormInitEdit.errors, window.__quizQuestionFormInitEdit.availableTags, window.__quizQuestionFormInitEdit.quizTagMetrics, window.__quizQuestionFormInitEdit.defaultTagRelevance, window.__quizQuestionFormInitEdit.questionBankRoutes)"
        class="bg-white dark:bg-[#1E1E2D] dark:border-[#5F2DED] shadow-lg border p-8 rounded-lg relative">

        <div class="pb-2 flex flex-wrap gap-3 items-center">
            <button onclick="window.location='{{ route('teacher.classwork.view', [$section->id, $subject->id]) }}'" class="flex items-center gap-2 shrink-0">
                <i class="icofont-arrow-left text-primaryColor text-4xl mb-2"></i>
            </button>

            <div class="flex-1 min-w-0">
                <x-quiz-nav
                    mode="edit"
                    active="edit_questions"
                    :section-id="$section->id"
                    :subject-id="$subject->id"
                    :quiz-id="$quiz->id"
                />
            </div>

            <button type="button" id="openDeleteModal" class="ml-auto inline-flex h-10 items-center px-4 py-2 bg-red-600 text-white text-sm rounded-lg shadow-sm hover:bg-red-700 transition-colors">
                Delete Quiz
            </button>
        </div>

        <div class="border-b border-gray-200 dark:border-[#5F2DED] flex items-end justify-between py-3">
            <div class="mb-4">
                <h2 class="text-3xl font-bold text-gray-800 dark:text-white mb-2">{{ $quiz->title }}</h2>
                <div class="flex flex-col gap-1 text-sm">
                    <div class="flex items-center gap-2">
                        <i class="icofont-clock-time text-green-700 text-lg"></i>
                        <p class="text-gray-600 dark:text-gray-300">
                            <span class="font-semibold">Opened:</span>
                            {{ \Carbon\Carbon::parse($quiz->created_at)->format('h:i A F d, Y') }}
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

                </div>
            </div>

            <div class="mb-4 flex justify-start md:justify-end">
                <div class="flex flex-col items-start md:items-end">
                    <p class="text-sm text-[#5E5F60] dark:text-gray-300 font-semibold">Total Points</p>
                    <p class="text-2xl font-bold text-[#373737] dark:text-white">
                        / <span class="text-[#F2277E] font-bold" x-text="totalPoints"></span> pts
                    </p>
                </div>
            </div>
        </div>

        <form id="quizQuestionsForm" action="{{ route('teacher.quiz.updateQuestions', [$section->id, $subject->id, $quiz->id]) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="space-y-6">

                <input type="hidden" name="current_page" x-model.number="currentPage">
                <input type="hidden" name="pagination" x-model.number="pageSize">
                <input type="hidden" name="default_question_type" x-model="defaultQuestionType">

                {{-- Description --}}
                <div class="my-6 text-[#373737] border-b border-[#D4D4D8] dark:border-[#5F2DED] pb-6">
                    <input id="quiz_description" type="hidden" name="description" value="{{ old('description', $quiz->description) }}">
                    <trix-editor
                        id="quiz_description_editor"
                        input="quiz_description"
                        placeholder="Write your quiz description here..."
                        class="
                            p-3
                            w-full
                            overflow-y-auto
                            max-h-[600px]
                            bg-white dark:bg-[#1E1E2D]
                            text-[#373737] dark:text-white
                            border-none
                            rounded-lg
                        "
                    ></trix-editor>
                </div>
                <style>
                    trix-toolbar [data-trix-button-group="file-tools"],
                    trix-toolbar .trix-button-group--file-tools { display: none !important; }

                    .quiz-question-card,
                    .quiz-question-card input,
                    .quiz-question-card select,
                    .quiz-question-card textarea,
                    .quiz-question-card button,
                    .quiz-question-card label,
                    .quiz-question-card span,
                .quiz-question-card th,
                .quiz-question-card td {
                    font-family: 'Poppins', sans-serif;
                    font-size: 14px;
                    line-height: 1.4;
                }

                .quiz-tag-tone-very-high { border-color: #16a34a; background-color: #dcfce7; color: #14532d; }
                .quiz-tag-tone-high { border-color: #0d9488; background-color: #ccfbf1; color: #134e4a; }
                .quiz-tag-tone-medium { border-color: #84cc16; background-color: #f7fee7; color: #4d7c0f; }
                .quiz-tag-tone-low { border-color: #0ea5e9; background-color: #f0f9ff; color: #0c4a6e; }
                .quiz-tag-tone-low:hover { background-color: #bae6fd; }
                .quiz-tag-tone-very-low { border-color: #1d4ed8; background-color: #dbeafe; color: #172554; }
                .quiz-tag-tone-very-low:hover { background-color: #93c5fd; }

                .dark .quiz-tag-tone-low { border-color: #38bdf8; background-color: rgba(3, 105, 161, 0.38); color: #bae6fd; }
                .dark .quiz-tag-tone-low:hover { background-color: rgba(14, 165, 233, 0.48); }
                .dark .quiz-tag-tone-very-low { border-color: #3b82f6; background-color: rgba(29, 78, 216, 0.58); color: #dbeafe; }
                .dark .quiz-tag-tone-very-low:hover { background-color: rgba(29, 78, 216, 0.7); }

                .quiz-gradient-slider {
                    -webkit-appearance: none;
                    appearance: none;
                    width: 100%;
                    height: 10px;
                    border-radius: 9999px;
                    background: linear-gradient(90deg, #3b82f6 0%, #22c55e 100%);
                    outline: none;
                }

                .quiz-gradient-slider::-webkit-slider-runnable-track {
                    height: 10px;
                    border-radius: 9999px;
                    background: linear-gradient(90deg, #3b82f6 0%, #22c55e 100%);
                }

                .quiz-gradient-slider::-webkit-slider-thumb {
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

                .quiz-gradient-slider::-moz-range-track {
                    height: 10px;
                    border-radius: 9999px;
                    background: linear-gradient(90deg, #3b82f6 0%, #22c55e 100%);
                }

                .quiz-gradient-slider::-moz-range-thumb {
                    height: 16px;
                    width: 16px;
                    border-radius: 9999px;
                    background: #ffffff;
                    border: 2px solid #5f2ded;
                    cursor: pointer;
                }

                .quiz-scrollbar-visible {
                    overflow-y: scroll !important;
                    overflow-x: hidden !important;
                    overscroll-behavior: contain;
                    -webkit-overflow-scrolling: touch;
                    scrollbar-width: thin !important;
                    scrollbar-color: #64748b #e2e8f0 !important;
                    scrollbar-gutter: stable both-edges;
                    background-image: linear-gradient(to bottom, #cbd5e1, #cbd5e1);
                    background-repeat: no-repeat;
                    background-size: 6px calc(100% - 8px);
                    background-position: right 2px top 4px;
                }

                .quiz-scrollbar-visible::-webkit-scrollbar {
                    width: 12px !important;
                    height: 12px !important;
                }

                .quiz-scrollbar-visible::-webkit-scrollbar-track {
                    background: #e2e8f0 !important;
                    border-radius: 9999px;
                }

                .quiz-scrollbar-visible::-webkit-scrollbar-thumb {
                    background: #64748b !important;
                    border-radius: 9999px;
                    border: 2px solid #e2e8f0;
                }

                .dark .quiz-scrollbar-visible {
                    scrollbar-color: #64748b #0f172a !important;
                    background-image: linear-gradient(to bottom, #334155, #334155);
                }

                .dark .quiz-scrollbar-visible::-webkit-scrollbar-track {
                    background: #0f172a !important;
                }

                .dark .quiz-scrollbar-visible::-webkit-scrollbar-thumb {
                    background: #64748b !important;
                    border-color: #0f172a;
                }

                body.quiz-tag-modal-open #sidebarToggle,
                body.quiz-tag-modal-open #sidebarFloatingButton {
                    opacity: 0 !important;
                    pointer-events: none !important;
                }
            </style>
                <script>
                    function isQuizDescriptionEditor(target) {
                        return target && target.id === 'quiz_description_editor';
                    }

                    document.addEventListener('trix-file-accept', function (event) {
                        if (isQuizDescriptionEditor(event.target)) {
                            event.preventDefault();
                        }
                    });

                    document.addEventListener('trix-attachment-add', function (event) {
                        if (isQuizDescriptionEditor(event.target) && event.attachment) {
                            event.attachment.remove();
                        }
                    });
                </script>

                <div x-data="{ showErrors: true }"
                     x-init="setTimeout(() => showErrors = false, 10000)"
                     x-show="(showErrors || clientErrorsVisible) && generalErrors.length"
                     x-transition
                     class="text-sm font-bold text-red-600 dark:text-red-300 inline-block">
                    <template x-for="(msg, mIndex) in generalErrors" :key="'general-error-' + mIndex">
                        <div x-text="msg"></div>
                    </template>
                </div>


                <div class="flex flex-wrap items-center gap-3 text-sm text-gray-700 dark:text-gray-200">
                    <div class="flex items-center gap-2">
                        <span class="text-xs uppercase tracking-wide text-gray-600 dark:text-gray-300 font-semibold">Pagination</span>
                        <input
                            type="number"
                            min="1"
                            max="25"
                            x-model.number="pageSize"
                            @change="onPageSizeChange(pageSize)"
                            class="w-14 h-9 px-2 text-xs text-center border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-[#1E1E2D] text-gray-800 dark:text-white focus:ring-2 focus:ring-purple-400 focus:outline-none"
                        >
                    </div>

                    <div class="flex items-center gap-2">
                        <span class="text-xs uppercase tracking-wide text-gray-600 dark:text-gray-300 font-semibold">Default type</span>
                        <select
                            x-model="defaultQuestionType"
                            class="h-9 min-w-[150px] cursor-pointer text-xs sm:text-sm border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-400 bg-white dark:bg-[#1E1E2D] text-gray-800 dark:text-white">
                            <option value="multiple_choice">Multiple Choice</option>
                            <option value="short_answer">Short Answer</option>
                            <option value="true_false">True/False</option>
                            <option value="matching">Matching Type</option>
                            <option value="essay">Essay</option>
                            <option value="numerical">Numerical Input</option>
                        </select>
                    </div>

                    <button
                        type="button"
                        @click="toggleRequireAll()"
                        :aria-pressed="allQuestionsRequired() ? 'true' : 'false'"
                        :class="[
                            'px-3 py-1 rounded-full border transition text-xs font-semibold',
                            allQuestionsRequired()
                                ? 'bg-[#5F2DED] text-white border-[#5F2DED] shadow-sm'
                                : 'bg-white dark:bg-[#1E1E2D] text-gray-800 dark:text-gray-100 border-gray-300 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-[#2A2A3C]'
                        ]"
                    >
                        Require all
                    </button>

                    <button
                        type="button"
                        @click="openQuestionTagModal(currentPage)"
                        class="px-3 py-1 rounded-full border border-gray-300 bg-white text-xs font-semibold text-gray-800 transition hover:bg-gray-100 dark:border-gray-600 dark:bg-[#1E1E2D] dark:text-gray-100 dark:hover:bg-[#2A2A3C]">
                        Tag
                    </button>

                    <button
                        type="button"
                        @click="openQuestionBankModal()"
                        class="px-3 py-1 rounded-full border border-gray-300 bg-white text-xs font-semibold text-gray-800 transition hover:bg-gray-100 dark:border-gray-600 dark:bg-[#1E1E2D] dark:text-gray-100 dark:hover:bg-[#2A2A3C]">
                        Import Questions
                    </button>
                </div>

                <template x-for="qIndex in allQuestionIndexes" :key="(questions[qIndex]?.id ?? 'new') + '-' + qIndex">
                    <div x-data="{ index: qIndex, question: questions[qIndex] }"
                        x-show="pageOfOrder(question?.order) === (parseInt(currentPage, 10) || 1)"
                        :class="questionHasError(index) ? 'border-red-300 ring-1 ring-red-300' : ''"
                         class="question quiz-question-card bg-white dark:bg-[#2A2A3C] shadow-md border border-gray-200 dark:border-gray-600 p-5 rounded-lg relative transition-all duration-200 hover:shadow-lg hover:bg-gray-50 dark:hover:bg-[#3A3A4F]">

                        <input type="hidden" :name="`questions[${index}][id]`" :value="question.id ?? ''">

                        <div class="mb-2 mt-4" style="max-width: 680px;">
                            <textarea
                                :name="`questions[${index}][text]`"
                                x-model="question.text"
                                placeholder="Enter question"
                                aria-label="Question"
                                class="w-full p-2 text-sm leading-snug border border-gray-300 dark:border-gray-600 rounded-md focus:ring-2 focus:ring-purple-400 focus:outline-none resize-none bg-white dark:bg-[#1E1E2D] text-gray-800 dark:text-white placeholder:text-sm placeholder:opacity-100"
                                rows="3"></textarea>
                        </div>

                        <div x-data="{ showErrors: true }"
                             x-init="setTimeout(() => showErrors = false, 10000)"
                             x-show="(showErrors || clientErrorsVisible) && questionErrors(index).length"
                             x-transition
                             class="mb-3 inline-block rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm font-bold text-red-600 dark:text-red-300">
                            <template x-for="(msg, msgIndex) in questionErrors(index)" :key="'q-err-' + index + '-' + msgIndex">
                                <div x-text="msg"></div>
                            </template>
                        </div>

                        <div x-show="question.type === 'multiple_choice'" class="mb-3 relative">
                            <div class="flex items-end justify-between gap-6">
                                <div class="space-y-2" style="max-width: 680px; width: 100%;">
                                    <template x-for="(opt, optIndex) in question.options" :key="optIndex">
                                        <div class="flex items-center gap-2">
                                    <input
                                        type="checkbox"
                                        x-show="question.showAnswerKey"
                                        :value="String(optIndex)"
                                        x-model="question.answer_key"
                                        :disabled="question.type !== 'multiple_choice'"
                                        class="h-4 w-4 rounded-full border-2 border-green-500 text-green-700 bg-white focus:ring-2 focus:ring-green-500"
                                    >

                                    <input
                                        type="text"
                                        :name="`questions[${index}][option][${optIndex}]`"
                                        x-model="question.options[optIndex]"
                                        :disabled="question.type !== 'multiple_choice'"
                                        :placeholder="`Option ${optIndex + 1}`"
                                            style="max-width: 620px;"
                                        :class="[
                                            'w-full p-2 rounded-md bg-transparent dark:bg-transparent text-gray-800 dark:text-white border border-transparent focus:border-gray-300 dark:focus:border-gray-600 focus:ring-2 focus:ring-purple-400 focus:outline-none transition-colors duration-200',
                                            question.showAnswerKey && (question.answer_key || []).map(String).includes(String(optIndex))
                                                ? 'border-green-500 border-2 bg-green-300 text-green-900 font-semibold shadow-sm ring-2 ring-green-500 dark:border-green-400 dark:bg-green-900/50 dark:text-green-100 dark:ring-green-500 focus:ring-green-500'
                                                : '',
                                            (question.type === 'multiple_choice' && isDuplicateOption(question, optIndex))
                                                ? 'text-red-600 dark:text-red-400'
                                                : ''
                                        ]"
                                    >

                                            <button
                                                type="button"
                                                @click="removeOption(index, optIndex)"
                                                class="text-red-600 dark:text-red-400 font-bold text-lg leading-none"
                                                aria-label="Remove option"
                                            >
                                                &times;
                                            </button>
                                        </div>
                                    </template>

                                    <template x-for="(ak, akIndex) in (question.answer_key || [])" :key="'ak-hidden-' + akIndex">
                                        <input
                                            type="hidden"
                                            :name="`questions[${index}][answer_key][${akIndex}]`"
                                            :value="String(ak)"
                                            :disabled="question.type !== 'multiple_choice'"
                                        >
                                    </template>
                                </div>

                                <button type="button" @click="addOption(question)"
                                        class="flex items-center justify-center w-10 h-10 bg-white dark:bg-purple-600
                                               text-purple-600 dark:text-white rounded-full shadow-md
                                               hover:bg-gray-100 dark:hover:bg-purple-700 transition-all duration-200
                                               focus:outline-none focus:ring-2 focus:ring-purple-400"
                                title="Add Option">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                </svg>
                            </button>
                        </div>
                        </div>

                        <div x-show="question.type === 'short_answer'" class="mb-4">
                            <input
                                type="text"
                                :name="`questions[${index}][answer_key]`"
                                x-model="question.answer_key_text"
                                :disabled="question.type !== 'short_answer'"
                                placeholder="Enter short text answer"
                                class="w-full max-w-md p-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-[#1E1E2D] text-gray-800 dark:text-white"
                            >
                        </div>

                        <div x-show="question.type === 'true_false'" class="mb-4 space-y-2">
                            <label class="text-gray-700 dark:text-gray-300 block cursor-pointer select-none">
                                <input
                                    type="radio"
                                    :name="`questions[${index}][answer_key]`"
                                    value="true"
                                    x-model="question.answer_key_bool"
                                    :disabled="question.type !== 'true_false'"
                                    class="cursor-pointer"
                                > True
                            </label>
                            <label class="text-gray-700 dark:text-gray-300 block cursor-pointer select-none">
                                <input
                                    type="radio"
                                    :name="`questions[${index}][answer_key]`"
                                    value="false"
                                    x-model="question.answer_key_bool"
                                    :disabled="question.type !== 'true_false'"
                                    class="cursor-pointer"
                                > False
                            </label>
                        </div>

                        <div x-show="question.type === 'matching'" class="mb-4 space-y-3">
                            <div class="grid grid-cols-2 gap-2">
                                <div class="space-y-2">
                                    <template x-for="(left, rowIndex) in (question.match_left || [])" :key="'left-' + rowIndex">
                                        <div
                                            x-data="{ hover: false, focus: false }"
                                            @mouseenter="hover = true"
                                            @mouseleave="hover = false"
                                            @focusin="focus = true"
                                            @focusout="focus = false"
                                            class="flex items-center gap-2"
                                        >
                                            <input
                                                x-show="question.use_pair_points"
                                                type="number"
                                                min="0"
                                                step="1"
                                                inputmode="numeric"
                                                :name="`questions[${index}][pair_points][${rowIndex}]`"
                                                x-model.number="question.pair_points[rowIndex]"
                                                class="w-12 p-1 text-[11px] border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-[#1E1E2D] text-gray-800 dark:text-white focus:ring-2 focus:ring-purple-400 focus:outline-none"
                                            >
                                            <input
                                                type="text"
                                                :name="`questions[${index}][match_left][${rowIndex}]`"
                                                x-model="question.match_left[rowIndex]"
                                                :disabled="question.type !== 'matching'"
                                                placeholder="Left Item"
                                                :class="[
                                                    'flex-1 p-2 rounded-md bg-transparent dark:bg-transparent text-gray-800 dark:text-white border border-transparent focus:border-gray-300 dark:focus:border-gray-600 focus:ring-2 focus:ring-purple-400 focus:outline-none transition-colors duration-200',
                                                    (question.type === 'matching' && isDuplicateMatchingLeft(question, rowIndex))
                                                        ? 'text-red-600 dark:text-red-400'
                                                        : ''
                                                ]">
                                            <button type="button"
                                                    @click="removeMatchingLeft(question, rowIndex)"
                                                    :disabled="(question.match_left || []).length <= 1"
                                                    :class="((question.match_left || []).length <= 1)
                                                        ? 'opacity-40 cursor-not-allowed'
                                                        : ''"
                                                    class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-600 transition-colors text-lg font-bold"
                                                    aria-label="Remove left item"
                                            >&times;</button>
                                        </div>
                                    </template>

                                    <div class="flex items-center justify-start">
                                        <button type="button" @click="addLeftPair(question)"
                                                class="flex items-center justify-center w-10 h-10 bg-white dark:bg-purple-600
                                                       text-purple-600 dark:text-white rounded-full shadow-md
                                                       hover:bg-gray-100 dark:hover:bg-purple-700 transition-all duration-200
                                                       focus:outline-none focus:ring-2 focus:ring-purple-400"
                                                title="Add Left">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <template x-for="(right, colIndex) in (question.match_right || [])" :key="'right-' + colIndex">
                                        <div
                                            x-data="{ hover: false, focus: false }"
                                            @mouseenter="hover = true"
                                            @mouseleave="hover = false"
                                            @focusin="focus = true"
                                            @focusout="focus = false"
                                            class="flex items-center gap-2"
                                        >
                                            <input
                                                type="text"
                                                :name="`questions[${index}][match_right][${colIndex}]`"
                                                x-model="question.match_right[colIndex]"
                                                :disabled="question.type !== 'matching'"
                                                placeholder="Right Item"
                                                :class="[
                                                    'flex-1 p-2 rounded-md bg-transparent dark:bg-transparent text-gray-800 dark:text-white border border-transparent focus:border-gray-300 dark:focus:border-gray-600 focus:ring-2 focus:ring-purple-400 focus:outline-none transition-colors duration-200',
                                                    (question.type === 'matching' && isDuplicateMatchingRight(question, colIndex))
                                                        ? 'text-red-600 dark:text-red-400'
                                                        : ''
                                                ]">
                                            <button type="button"
                                                    @click="removeMatchingRight(question, colIndex)"
                                                    :disabled="(question.match_right || []).length <= 1"
                                                    :class="((question.match_right || []).length <= 1)
                                                        ? 'opacity-40 cursor-not-allowed'
                                                        : ''"
                                                    class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-600 transition-colors text-lg font-bold"
                                                    aria-label="Remove right item"
                                            >&times;</button>
                                        </div>
                                    </template>

                                    <div class="flex items-center justify-end">
                                        <button type="button" @click="addRightPair(question)"
                                                class="flex items-center justify-center w-10 h-10 bg-white dark:bg-purple-600
                                                       text-purple-600 dark:text-white rounded-full shadow-md
                                                       hover:bg-gray-100 dark:hover:bg-purple-700 transition-all duration-200
                                                       focus:outline-none focus:ring-2 focus:ring-purple-400"
                                                title="Add Right">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <template x-if="question.showAnswerKey">
                                <div class="overflow-x-auto">
                                    <table class="min-w-full text-sm border border-gray-200 dark:border-gray-600 border-collapse">
                                            <thead>
                                                <tr>
                                                    <th class="text-left font-semibold text-gray-700 dark:text-gray-300 whitespace-nowrap p-2 border-b border-gray-200 dark:border-gray-600"></th>
                                                    <template x-for="(colText, colIndex) in (question.match_right || [])" :key="'hdr-' + colIndex">
                                                        <th class="text-left font-semibold text-gray-700 dark:text-gray-300 whitespace-nowrap p-2 border-b border-gray-200 dark:border-gray-600">
                                                            <span x-text="truncateLabel(colText || ('Column ' + (colIndex + 1)), 12)"></span>
                                                        </th>
                                                    </template>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <template x-for="(rowText, rowIndex) in (question.match_left || [])" :key="'row-' + rowIndex">
                                                    <tr>
                                                        <td class="font-medium text-gray-700 dark:text-gray-300 whitespace-nowrap p-2 border-b border-gray-200 dark:border-gray-600">
                                                            <span x-text="truncateLabel(rowText || ('Row ' + (rowIndex + 1)), 12)"></span>
                                                        </td>

                                                        <template x-for="(colText, colIndex) in (question.match_right || [])" :key="'cell-' + rowIndex + '-' + colIndex">
                                                            <td class="p-2 border-b border-gray-200 dark:border-gray-600">
                                                                <button
                                                                    type="button"
                                                                    @click="toggleMatchSelection(question, rowIndex, colIndex)"
                                                                    :disabled="!rowText || !colText"
                                                                    :class="(
                                                                        (question.match_map && String(question.match_map[rowIndex]) === String(colIndex))
                                                                                ? 'bg-green-300 text-green-900 font-semibold shadow-sm ring-1 ring-green-500 dark:bg-green-900/50 dark:text-green-100 dark:ring-green-500'
                                                                            : ''
                                                                    )"
                                                                            class="w-6 h-6 rounded-full bg-transparent flex items-center justify-center focus:outline-none focus:ring-2 focus:ring-purple-400 disabled:opacity-40 disabled:cursor-not-allowed"
                                                                    :aria-pressed="(question.match_map && String(question.match_map[rowIndex]) === String(colIndex)) ? 'true' : 'false'"
                                                                    title="Select match">
                                                                    <input
                                                                        type="radio"
                                                                        tabindex="-1"
                                                                        :checked="(question.match_map && String(question.match_map[rowIndex]) === String(colIndex))"
                                                                        class="h-4 w-4 rounded-full border-2 border-green-500 text-green-700 bg-white focus:ring-2 focus:ring-green-500 pointer-events-none"
                                                                    >
                                                                </button>
                                                            </td>
                                                        </template>
                                                    </tr>
                                                </template>
                                            </tbody>
                                    </table>
                                </div>
                            </template>

                            <template x-for="(pair, pairIndex) in matchingPairsFor(question)" :key="'pair-hidden-' + pairIndex">
                                <div class="hidden">
                                    <input type="hidden" :name="`questions[${index}][answer_key][${pairIndex}][left]`" :value="pair.left">
                                    <input type="hidden" :name="`questions[${index}][answer_key][${pairIndex}][right]`" :value="pair.right">
                                </div>
                            </template>

                            <template x-for="(colIndex, rowIndex) in (question.match_map || {})" :key="'map-hidden-' + rowIndex">
                                <div class="hidden">
                                    <input type="hidden" :name="`questions[${index}][match_map][${rowIndex}]`" :value="colIndex">
                                </div>
                            </template>
                        </div>

                        <div x-show="question.type === 'numerical'" class="mb-3">
                            <input
                                type="number"
                                step="any"
                                :name="`questions[${index}][answer_key]`"
                                x-model="question.answer_key_num"
                                :disabled="question.type !== 'numerical'"
                                placeholder="Enter numerical input answer"
                                class="w-full max-w-md p-3 border border-gray-300 dark:border-gray-600 rounded-md
                                       focus:ring-2 focus:ring-purple-400 focus:outline-none
                                       bg-white dark:bg-[#1E1E2D] text-gray-800 dark:text-white"
                            >
                        </div>

                        <div class="border-t border-gray-200 dark:border-gray-700 my-4"></div>

                        <div class="flex flex-wrap items-center gap-3 text-sm">
                            <div class="flex flex-wrap items-center gap-2 flex-1 min-w-[260px]">
                                <select
                                    :name="`questions[${index}][type]`"
                                    x-model="question.type"
                                    @change="onTypeChange(question)"
                                    class="h-10 min-w-[170px] cursor-pointer text-xs sm:text-sm border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-400 bg-white dark:bg-[#1E1E2D] text-gray-800 dark:text-white">
                                    <option value="multiple_choice">Multiple Choice</option>
                                    <option value="short_answer">Short Answer</option>
                                    <option value="true_false">True/False</option>
                                    <option value="matching">Matching Type</option>
                                    <option value="essay">Essay</option>
                                    <option value="numerical">Numerical Input</option>
                                </select>

                                <template x-if="question.type === 'matching'">
                                    <button type="button"
                                            @click="question.use_pair_points = !question.use_pair_points; ensurePairPointsLength(question);"
                                            :class="question.use_pair_points
                                                ? 'bg-purple-600 text-white border border-purple-600 dark:bg-purple-500 dark:border-purple-500'
                                                : 'bg-gray-100 text-black border border-gray-400 dark:bg-gray-700 dark:text-white dark:border-gray-600'"
                                            class="px-3 py-2 rounded-full text-xs sm:text-sm font-medium transition-colors duration-200">
                                        <span>Points</span>
                                    </button>
                                </template>

                                <template x-if="question.type !== 'matching'">
                                    <label class="flex items-center gap-2 text-xs uppercase tracking-wide text-gray-600 dark:text-gray-300 font-semibold">
                                        <span>Points</span>
                                        <input
                                            type="number"
                                            min="0"
                                            step="1"
                                            inputmode="numeric"
                                            :name="`questions[${index}][points]`"
                                            x-model.number="question.points"
                                            class="w-14 p-1 text-xs text-center border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-[#1E1E2D] text-gray-800 dark:text-white focus:ring-2 focus:ring-purple-400 focus:outline-none"
                                        >
                                    </label>
                                </template>

                                <template x-if="question.type === 'matching'">
                                    <input type="hidden" :name="`questions[${index}][points]`" :value="effectivePoints(question)">
                                </template>
                                <input type="hidden" :name="`questions[${index}][use_pair_points]`" :value="question.use_pair_points ? 1 : 0">
                            </div>

                            <div class="flex items-center gap-2 ml-auto">
                                <input type="checkbox" :name="`questions[${index}][required]`" x-model="question.required" value="1" class="sr-only">

                                <button type="button"
                                        @click="question.required = !question.required"
                                        :class="question.required
                                            ? 'bg-purple-600 text-white border border-purple-600 dark:bg-purple-500 dark:border-purple-500'
                                            : 'bg-gray-100 text-black border border-gray-400 dark:bg-gray-700 dark:text-white dark:border-gray-600'"
                                        class="px-3 py-2 rounded-full text-xs sm:text-sm font-medium transition-colors duration-200"
                                        :aria-pressed="question.required ? 'true' : 'false'">
                                    Required
                                </button>

                                <button type="button"
                                        @click="question.showAnswerKey = !question.showAnswerKey"
                                        x-show="!['short_answer','numerical','true_false','essay'].includes(question.type)"
                                        :class="question.showAnswerKey
                                            ? 'bg-purple-600 text-white border border-purple-600 dark:bg-purple-500 dark:border-purple-500'
                                            : 'bg-gray-100 text-black border border-gray-400 dark:bg-gray-700 dark:text-white dark:border-gray-600'"
                                        class="px-3 py-2 rounded-full text-xs sm:text-sm font-medium transition-colors duration-200">
                                    Answer Key
                                </button>
                            </div>
                        </div>
                        <div class="mt-2">
                            <div class="flex justify-end">
                                <button type="button"
                                        @click="toggleQuestionTagLabels(index)"
                                        :class="isQuestionTagLabelsOpen(index)
                                            ? 'border border-purple-600 bg-purple-600 text-white shadow-sm dark:border-purple-500 dark:bg-purple-500 dark:text-white'
                                            : 'border border-gray-300 bg-white text-gray-500 hover:border-purple-300 hover:text-purple-600 dark:border-gray-600 dark:bg-[#1E1E2D] dark:text-gray-300 dark:hover:border-purple-400 dark:hover:text-white'"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-full transition-colors duration-200"
                                        :aria-expanded="isQuestionTagLabelsOpen(index) ? 'true' : 'false'"
                                        :aria-pressed="isQuestionTagLabelsOpen(index) ? 'true' : 'false'"
                                        aria-label="Toggle question tags">
                                    <span class="sr-only">Toggle question tags</span>
                                    <svg xmlns="http://www.w3.org/2000/svg"
                                         viewBox="0 0 24 24"
                                         fill="none"
                                         stroke="currentColor"
                                         stroke-width="1.8"
                                         class="h-4 w-4">
                                        <path stroke-linecap="round"
                                              stroke-linejoin="round"
                                              d="M7.5 7.5h.01M3 10.336V6.75A2.25 2.25 0 0 1 5.25 4.5h3.586a2.25 2.25 0 0 1 1.591.659l8.414 8.414a2.25 2.25 0 0 1 0 3.182l-2.086 2.086a2.25 2.25 0 0 1-3.182 0L5.159 11.927A2.25 2.25 0 0 1 4.5 10.336Z" />
                                    </svg>
                                </button>
                            </div>

                            <div class="mt-2 flex flex-wrap gap-2" x-show="isQuestionTagLabelsOpen(index) && (question.tag_ids || []).length">
                                <template x-for="tagId in (question.tag_ids || [])" :key="`selected-tag-${index}-${tagId}`">
                                    <span :class="tagBadgeClass(tagId, index)"
                                          class="inline-flex items-center gap-1 rounded-full border px-2 py-1 text-[11px]">
                                        <span x-text="tagName(tagId)"></span>
                                        <span class="text-[10px] leading-none opacity-80" x-text="`${tagCoveragePercent(tagId, index)}%`"></span>
                                    </span>
                                </template>
                            </div>
                            <p x-show="isQuestionTagLabelsOpen(index) && !(question.tag_ids || []).length"
                               class="mt-2 text-[11px] text-gray-500 dark:text-gray-400">
                                No tags defined
                            </p>
                        </div>

                        <template x-for="(tagId, tagIndex) in (question.tag_ids || [])" :key="`tag-hidden-${index}-${tagIndex}-${tagId}`">
                            <input type="hidden" :name="`questions[${index}][tag_ids][${tagIndex}]`" :value="tagId">
                        </template>
                        <template x-for="tagId in (question.tag_ids || [])" :key="`tag-relevance-hidden-${index}-${tagId}`">
                            <input type="hidden" :name="`questions[${index}][tag_relevance][${tagId}]`" :value="questionTagRelevance(index, tagId)">
                        </template>

                        <div class="absolute top-3 right-3">
                            <input type="hidden"
                                   :name="`questions[${index}][order]`"
                                   x-model="question.order">
                            <button type="button"
                                    @click="deleteQuestion(index)"
                                    class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-600 transition-colors text-lg font-bold">
                                &times;
                            </button>
                        </div>
                    </div>
                </template>

                <div class="flex justify-center mt-4 items-center gap-3">
                    <button type="button"
                            @click="addQuestion()"
                            :disabled="isPageFull(currentPage)"
                            title="Add Question"
                            class="flex items-center justify-center w-10 h-10 bg-white dark:bg-purple-600
                                   text-purple-600 dark:text-white rounded-full shadow-md
                                   hover:bg-gray-100 dark:hover:bg-purple-700 transition-all duration-200
                                   focus:outline-none focus:ring-2 focus:ring-purple-400"
                            :class="isPageFull(currentPage) ? 'opacity-50 cursor-not-allowed' : ''">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                    </button>
                </div>

                <div class="mt-6 relative flex items-center gap-3">
                    <div x-show="toastVisible"
                         x-transition
                         class="flex items-center gap-3 bg-[#5F2DED] border border-[#5F2DED] shadow-lg rounded-lg px-4 py-3">
                        <div class="text-sm font-semibold text-white">Question Deleted</div>
                        <button type="button" @click="undoDelete()" class="text-sm font-semibold text-white hover:text-white/90 underline">
                            Undo
                        </button>
                    </div>

                    <div class="absolute left-1/2 -translate-x-1/2 flex items-center gap-2">
                        <button type="button"
                                @click="prevPage()"
                                :disabled="currentPage <= 1"
                                class="px-3 py-1 text-sm rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-[#1E1E2D] text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-[#2A2A3C]"
                                :class="(currentPage <= 1) ? 'opacity-50 cursor-not-allowed' : ''"
                                aria-label="Previous"
                                title="Previous">
                            &larr;
                        </button>

                        <div class="flex items-center gap-1">
                            <template x-for="p in totalPages" :key="'page-' + p">
                                <button type="button"
                                        @click="goToPage(p)"
                                        class="w-8 h-8 text-sm rounded-md"
                                        :class="(p === currentPage)
                                            ? 'bg-[#5F2DED] text-white'
                                            : 'border border-gray-300 dark:border-gray-600 bg-white dark:bg-[#1E1E2D] text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-[#2A2A3C]'"
                                        x-text="p"></button>
                            </template>
                        </div>

                        <button type="button"
                                @click="nextPage()"
                                :disabled="currentPage >= totalPages"
                                class="px-3 py-1 text-sm rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-[#1E1E2D] text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-[#2A2A3C]"
                                :class="(currentPage >= totalPages) ? 'opacity-50 cursor-not-allowed' : ''"
                                aria-label="Next"
                                title="Next">
                            &rarr;
                        </button>
                    </div>

                    <button type="submit"
                            class="ml-auto flex items-center justify-center bg-[#5F2DED]
                                   text-white px-6 py-3 rounded-full shadow-md
                                   hover:bg-[#4E25C7] transition-all duration-200
                                   focus:outline-none focus:ring-2 focus:ring-purple-400 font-semibold">
                        <i class="icofont-check mr-2"></i> Update Questions
                    </button>
                </div>

            </div>
        </form>

        <div x-show="questionTagModalOpen" @click.self="closeQuestionTagModal()" style="display: none; z-index: 120000;" class="fixed inset-0 flex items-center justify-center bg-black/50 p-4">
            <div class="flex max-h-[90vh] w-full max-w-5xl flex-col overflow-hidden rounded-xl bg-white shadow-xl dark:bg-[#1E1E2D]">
                <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Select Question Tags</h3>
                    <button type="button" @click="closeQuestionTagModal()" class="text-xl leading-none text-gray-500 hover:text-gray-700 dark:text-gray-300 dark:hover:text-white">&times;</button>
                </div>

                <div class="flex min-h-0 flex-1 flex-col space-y-4 overflow-hidden px-5 py-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <button type="button"
                                    @click="openDefineTagModal()"
                                    :disabled="selectedTagQuestionIndexes.length === 0"
                                    class="rounded-full border border-[#5F2DED] px-4 py-2 text-xs font-semibold text-[#5F2DED] transition hover:bg-[#5F2DED] hover:text-white disabled:cursor-not-allowed disabled:opacity-50">
                                Define Tag
                            </button>
                            <button type="button"
                                    @click="selectAllQuestionsForTag()"
                                    :disabled="!allQuestionIndexes.length"
                                    class="rounded-full border border-gray-300 px-4 py-2 text-xs font-semibold text-gray-700 transition hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-600 dark:text-gray-100 dark:hover:bg-[#2A2A3C]">
                                <span x-text="areAllQuestionsSelectedForTag() ? 'Deselect All' : 'Select All'"></span>
                            </button>
                            <div class="ml-1 inline-flex items-center gap-1">
                                <div class="relative"
                                     @mouseenter="openTagPagePicker()"
                                     @mouseleave="closeTagPagePicker()">
                                    <button type="button"
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-gray-300 text-xs font-semibold text-gray-700 transition hover:bg-gray-100 dark:border-gray-600 dark:text-gray-100 dark:hover:bg-[#2A2A3C]"
                                            title="Page"
                                            aria-label="Go to page">
                                        <i class="icofont-search-1 text-sm"></i>
                                    </button>

                                    <div x-show="tagPagePickerOpen"
                                         x-transition
                                         style="display: none;"
                                         class="absolute left-0 top-full z-20 mt-2 min-w-[132px] rounded-md border border-gray-200 bg-white p-2 shadow-lg dark:border-gray-600 dark:bg-[#1E1E2D]">
                                        <p class="mb-1 text-[11px] font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-300">Pages</p>
                                        <div class="max-h-44 space-y-1 overflow-y-auto">
                                            <template x-for="page in getTagQuestionPageNumbers()" :key="`tag-page-nav-${page}`">
                                                <button type="button"
                                                        @click="navigateToTagQuestionPage(page)"
                                                        class="block w-full whitespace-nowrap rounded px-2 py-1 text-left text-xs text-gray-700 transition hover:bg-gray-100 dark:text-gray-100 dark:hover:bg-[#2A2A3C]"
                                                        :class="getTagModalCurrentPage() === page ? 'bg-gray-100 dark:bg-[#2A2A3C] font-semibold' : ''">
                                                    <span x-text="`Page ${page}`"></span>
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                </div>

                                <button type="button"
                                        @click="toggleAllTagQuestionStripsExpanded()"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-gray-300 text-xs font-semibold text-gray-700 transition hover:bg-gray-100 dark:border-gray-600 dark:text-gray-100 dark:hover:bg-[#2A2A3C]"
                                        :title="areAllTagQuestionStripsExpanded() ? 'Collapse All' : 'Expand All'"
                                        :aria-label="areAllTagQuestionStripsExpanded() ? 'Collapse All' : 'Expand All'">
                                    <span x-show="!areAllTagQuestionStripsExpanded()" style="display: none;">
                                        <svg viewBox="0 0 16 16" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5">
                                            <path d="M6 2H2v4M10 2h4v4M14 10v4h-4M2 10v4h4"/>
                                        </svg>
                                    </span>
                                    <span x-show="areAllTagQuestionStripsExpanded()" style="display: none;">
                                        <svg viewBox="0 0 16 16" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5">
                                            <path d="M2 6h4V2M14 6h-4V2M2 10h4v4M14 10h-4v4"/>
                                        </svg>
                                    </span>
                                </button>
                            </div>
                        </div>
                        <p class="text-xs text-gray-600 dark:text-gray-300" x-text="`${selectedTagQuestionIndexes.length} question(s) selected`"></p>
                    </div>

                    <div x-ref="tagQuestionPageScroll"
                         @scroll.passive="onTagQuestionScroll()"
                         class="quiz-scrollbar-visible min-h-0 h-[55vh] max-h-[55vh] rounded-lg border border-gray-200 p-3 pr-2 dark:border-gray-700">
                        <template x-if="!tagQuestionGroups.length">
                            <p class="text-sm text-gray-500 dark:text-gray-300">No questions available.</p>
                        </template>

                        <template x-for="group in tagQuestionGroups" :key="`tag-group-${group.page}`">
                            <div class="mb-4" :data-tag-page="group.page">
                                <div class="mb-2 flex items-center gap-2">
                                    <input type="checkbox"
                                           class="h-4 w-4 rounded border-gray-300 text-[#5F2DED] focus:ring-[#5F2DED]"
                                           :checked="isTagPageFullySelected(group.page)"
                                           @change="toggleTagPageSelection(group.page)">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300"
                                       x-text="`Page ${group.page}`"></p>
                                </div>
                                <div class="rounded-md border border-gray-200 p-2 dark:border-gray-600">
                                    <template x-for="item in group.items" :key="`tag-question-item-${item.index}`">
                                        <div class="mb-2 flex items-start gap-2">
                                            <input type="checkbox"
                                                   class="mt-2 h-4 w-4 rounded border-gray-300 text-[#5F2DED] focus:ring-[#5F2DED]"
                                                   :checked="isQuestionSelectedForTag(item.index)"
                                                   @click.stop
                                                   @change="setQuestionSelectedForTag(item.index, $event.target.checked)">

                                            <div class="w-full cursor-pointer rounded-md border border-gray-200 px-3 py-2 transition-colors hover:bg-gray-50 dark:border-gray-600 dark:hover:bg-[#2A2A3C]"
                                                 @click="toggleQuestionExpandedForTag(item.index)">
                                                <div class="flex items-center gap-2">
                                                    <span class="shrink-0 text-xs font-semibold text-gray-600 dark:text-gray-300" x-text="`Q${item.number}`"></span>
                                                    <span class="min-w-0 flex-1 truncate text-sm text-gray-800 dark:text-gray-100" x-text="questionPreview(item.question, 90)"></span>
                                                    <button type="button"
                                                            class="shrink-0 bg-transparent p-0 text-xs font-semibold text-gray-600 dark:text-gray-200"
                                                            @click.stop="toggleQuestionExpandedForTag(item.index)">
                                                        <span :class="isQuestionExpandedForTag(item.index) ? 'rotate-180' : ''" class="inline-block transition-transform duration-200">v</span>
                                                    </button>
                                                </div>

                                                <div x-show="isQuestionExpandedForTag(item.index)"
                                                     class="mt-2 border-t border-gray-200 pt-2 text-xs dark:border-gray-600">
                                                    <p class="mb-2 text-gray-700 dark:text-gray-200" x-text="item.question.text || 'Untitled question'"></p>
                                                    <p class="mb-2 text-[11px] font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400" x-text="questionTypeLabel(item.question.type)"></p>

                                                    <div class="mb-2">
                                                        <p class="mb-1 text-[11px] font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Tags</p>
                                                        <div class="flex flex-wrap gap-1">
                                                            <template x-if="!(item.question.tag_ids || []).length">
                                                                <span class="text-[11px] text-gray-500 dark:text-gray-400">No tags defined</span>
                                                            </template>
                                                            <template x-for="group in getExpandedStripTagGroups(item.index)" :key="`expanded-tag-group-${item.index}-${group.relevance}`">
                                                                <div class="mb-1 flex w-full items-center gap-2">
                                                                    <span class="shrink-0 text-[10px] font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400"
                                                                          x-text="group.label"></span>
                                                                    <div class="flex flex-wrap gap-1">
                                                                        <template x-for="tagId in group.tagIds" :key="`expanded-tag-${item.index}-${group.relevance}-${tagId}`">
                                                                            <button type="button"
                                                                                    :class="tagBadgeClass(tagId, item.index)"
                                                                                    class="inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-[11px] transition"
                                                                                    @click.stop="openDefineTagRelevanceForQuestionTag(item.index, tagId)"
                                                                                    :title="`Edit ${tagName(tagId)} relevance`">
                                                                                <span x-text="tagName(tagId)"></span>
                                                                                <span class="text-[10px] leading-none opacity-80" x-text="`${tagCoveragePercent(tagId, item.index)}%`"></span>
                                                                            </button>
                                                                        </template>
                                                                    </div>
                                                                </div>
                                                            </template>
                                                        </div>
                                                    </div>

                                                    <template x-if="item.question.type === 'multiple_choice'">
                                                        <ul class="space-y-1">
                                                            <template x-for="(opt, optIndex) in (item.question.options || [])" :key="`preview-opt-${item.index}-${optIndex}`">
                                                                <li class="flex items-center gap-2">
                                                                    <span class="h-1.5 w-1.5 rounded-full bg-gray-400"></span>
                                                                    <span class="text-gray-700 dark:text-gray-200" x-text="opt || `Option ${optIndex + 1}`"></span>
                                                                    <span x-show="isCorrectMultipleChoiceOption(item.question, optIndex)" class="rounded bg-green-100 px-1.5 py-0.5 text-[10px] font-semibold text-green-700 dark:bg-green-900/40 dark:text-green-300">Answer</span>
                                                                </li>
                                                            </template>
                                                        </ul>
                                                    </template>

                                                    <template x-if="item.question.type === 'true_false'">
                                                        <p class="text-gray-700 dark:text-gray-200" x-text="`Answer: ${String(item.question.answer_key_bool || '').toUpperCase()}`"></p>
                                                    </template>

                                                    <template x-if="item.question.type === 'short_answer' || item.question.type === 'essay'">
                                                        <p class="text-gray-700 dark:text-gray-200" x-text="`Answer: ${item.question.answer_key_text || 'N/A'}`"></p>
                                                    </template>

                                                    <template x-if="item.question.type === 'numerical'">
                                                        <p class="text-gray-700 dark:text-gray-200" x-text="`Answer: ${item.question.answer_key_num !== '' ? item.question.answer_key_num : 'N/A'}`"></p>
                                                    </template>

                                                    <template x-if="item.question.type === 'matching'">
                                                        <ul class="space-y-1">
                                                            <template x-for="(pair, pairIndex) in matchingPairsFor(item.question)" :key="`preview-pair-${item.index}-${pairIndex}`">
                                                                <li class="text-gray-700 dark:text-gray-200" x-text="`${pair.left} -> ${pair.right}`"></li>
                                                            </template>
                                                        </ul>
                                                    </template>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <div x-show="defineTagModalOpen" @click.self="closeDefineTagModal()" style="display: none; z-index: 120100;" class="fixed inset-0 flex items-center justify-center bg-black/50 p-4">
            <div class="w-full max-w-2xl rounded-lg bg-white p-6 shadow-xl dark:bg-[#1E1E2D]">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <h3 class="text-xl font-bold text-gray-800 dark:text-white">Select Tags</h3>
                    <div class="flex w-full items-center gap-2 sm:w-auto sm:justify-end">
                        <div class="relative w-full sm:w-52">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                                <i class="icofont-search-1 text-sm"></i>
                            </span>
                            <input
                                type="text"
                                x-model="defineTagSearch"
                                @input="onDefineTagSearchInput()"
                                :placeholder="defineTagRelevanceMode ? 'Search selected tags...' : (defineTagCategoryMode ? 'Search categories...' : 'Search tags...')"
                                class="w-full rounded-full border border-[#ABB7C2] bg-transparent py-2 pl-10 pr-3 text-sm text-[#5E5F60] focus:outline-none focus:ring-2 focus:ring-[#5F2DED] dark:text-white"
                            />
                        </div>
                        <button type="button" @click="closeDefineTagModal()"
                            class="text-gray-500 hover:text-gray-700 dark:text-gray-300">
                            <i class="icofont-close text-xl"></i>
                        </button>
                    </div>
                </div>

                <div class="mt-4 flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <span class="text-sm text-gray-500 dark:text-gray-300">Category:</span>
                        <button type="button"
                            @click="toggleDefineTagCategoryMode()"
                            class="inline-flex items-center gap-2 rounded-md border border-[#ABB7C2] bg-gray-50 px-3 py-1.5 text-sm font-medium text-[#5E5F60] transition-colors hover:border-[#5F2DED] hover:bg-[#F6F2FF] dark:bg-[#2A2A3C] dark:text-white dark:hover:bg-[#34344A]">
                            <span x-text="getDefineTagCategoryLabel()"></span>
                            <i class="icofont-rounded-down text-sm"></i>
                        </button>

                        <button type="button"
                                @click="toggleDefineTagRelevanceMode()"
                                class="inline-flex items-center gap-2 rounded-md border border-[#ABB7C2] bg-gray-50 px-3 py-1.5 text-sm font-medium text-[#5E5F60] transition-colors hover:border-[#5F2DED] hover:bg-[#F6F2FF] dark:bg-[#2A2A3C] dark:text-white dark:hover:bg-[#34344A]">
                            <span>Relevance</span>
                            <i class="icofont-rounded-down text-sm"></i>
                        </button>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button"
                            @click="clearDefineTagSelection()"
                            :disabled="!defineTagSelection.length"
                            class="inline-flex items-center gap-2 rounded-full border border-red-300 px-3 py-1.5 text-sm font-medium text-red-500 transition hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-50">
                            <i class="icofont-close text-sm"></i>
                            <span>Clear all</span>
                        </button>
                        <button type="button"
                            x-show="defineTagCategoryMode || defineTagRelevanceMode"
                            @click="handleDefineTagBack()"
                            class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-[#ABB7C2] text-[#5F2DED] hover:bg-[#F6F2FF] dark:border-[#5F2DED] dark:hover:bg-[#2A2A3C]"
                            title="Back to tags"
                            aria-label="Back to tags">
                            <i class="icofont-simple-left text-sm"></i>
                        </button>
                    </div>
                </div>

                <div x-show="!defineTagCategoryMode && !defineTagRelevanceMode">
                    <div class="quiz-scrollbar-visible mt-4 max-h-72 rounded-lg border border-gray-200 divide-y divide-gray-100 dark:border-gray-700 dark:divide-gray-800">
                        <template x-if="!getDefineTagVisibleTags().length">
                            <div class="px-4 py-6 text-sm text-gray-500 dark:text-gray-300">No tags match your filters.</div>
                        </template>

                        <template x-for="tag in getDefineTagVisibleTags()" :key="`define-tag-${tag.id}`">
                            <label class="flex cursor-pointer items-center gap-3 px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-800">
                                <input type="checkbox"
                                    class="h-4 w-4 rounded border-gray-300"
                                    :value="String(tag.id)"
                                    x-model="defineTagSelection">
                                <div class="flex min-w-0 flex-1 items-center justify-between gap-3">
                                    <div class="flex min-w-0 flex-col">
                                        <span class="text-sm font-medium text-[#5E5F60] dark:text-white" x-text="tag.name"></span>
                                        <span class="text-xs text-gray-500 dark:text-gray-300" x-text="tag.category || 'Uncategorized'"></span>
                                    </div>
                                </div>
                            </label>
                        </template>
                    </div>

                    <div class="mt-4 flex items-center justify-between" x-show="getDefineTagTotalPages() > 1">
                        <button type="button"
                            @click="prevDefineTagPage()"
                            :disabled="getDefineTagCurrentPage() <= 1"
                            class="rounded-lg border border-[#ABB7C2] px-3 py-1.5 text-sm text-[#5E5F60] hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-50 dark:text-white dark:hover:bg-gray-800">
                            Prev
                        </button>
                        <p class="text-sm text-gray-500 dark:text-gray-300"
                           x-text="`Page ${getDefineTagCurrentPage()} of ${getDefineTagTotalPages()}`"></p>
                        <button type="button"
                            @click="nextDefineTagPage()"
                            :disabled="getDefineTagCurrentPage() >= getDefineTagTotalPages()"
                            class="rounded-lg border border-[#ABB7C2] px-3 py-1.5 text-sm text-[#5E5F60] hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-50 dark:text-white dark:hover:bg-gray-800">
                            Next
                        </button>
                    </div>
                </div>

                <div x-show="defineTagRelevanceMode">
                    <div class="mt-4 rounded-lg border border-[#E2E8F0] bg-[#F8FAFC] p-3 dark:border-[#334155] dark:bg-[#0B1220]">
                        <div class="space-y-2">
                            <template x-if="!getDefineTagRelevanceVisibleTagIds().length">
                                <p class="text-xs text-gray-500 dark:text-gray-300">Select at least one tag to configure relevance.</p>
                            </template>

                            <template x-for="tagId in getDefineTagRelevanceVisibleTagIds()" :key="`define-tag-rel-${tagId}`">
                                <div class="border border-[#94A3B8] p-3 transition-colors hover:bg-[#EFF6FF] focus-within:bg-[#DBEAFE] dark:border-[#475569] dark:hover:bg-[#12243D] dark:focus-within:bg-[#1E3A5F]">
                                    <p class="text-sm font-medium text-[#373737] dark:text-white" x-text="tagName(tagId)"></p>
                                    <div class="mt-2 flex items-center gap-2">
                                        <div class="min-w-0 flex-1">
                                            <input type="range"
                                                   min="0"
                                                   max="4"
                                                   step="1"
                                                   class="quiz-gradient-slider w-full"
                                                   :value="getTagRelevanceIndex(tagId)"
                                                   @input="setTagRelevanceByIndex(tagId, $event.target.value)">
                                        </div>
                                        <span style="width: 100px; flex: 0 0 100px;"
                                              class="shrink-0 whitespace-nowrap text-right text-sm font-semibold text-[#334155] dark:text-white"
                                              x-text="tagRelevanceLabel(tagId)"></span>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <div class="mt-3 flex items-center justify-between" x-show="getDefineTagRelevanceTotalPages() > 1">
                            <button type="button"
                                    @click="prevDefineTagRelevancePage()"
                                    :disabled="getDefineTagCurrentRelevancePage() <= 1"
                                    class="rounded-lg border border-[#ABB7C2] px-3 py-1.5 text-sm text-[#5E5F60] hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-50 dark:text-white dark:hover:bg-gray-800">
                                Prev
                            </button>
                            <p class="text-sm text-gray-500 dark:text-gray-300"
                               x-text="`Page ${getDefineTagCurrentRelevancePage()} of ${getDefineTagRelevanceTotalPages()}`"></p>
                            <button type="button"
                                    @click="nextDefineTagRelevancePage()"
                                    :disabled="getDefineTagCurrentRelevancePage() >= getDefineTagRelevanceTotalPages()"
                                    class="rounded-lg border border-[#ABB7C2] px-3 py-1.5 text-sm text-[#5E5F60] hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-50 dark:text-white dark:hover:bg-gray-800">
                                Next
                            </button>
                        </div>
                    </div>
                </div>

                <div x-show="defineTagCategoryMode">
                    <div class="mt-4 rounded-md border border-gray-200 divide-y divide-gray-100 dark:border-gray-700 dark:divide-gray-800">
                        <button type="button"
                            @click="selectDefineTagCategory('')"
                            :class="defineTagCurrentCategory === '' ? 'border-l-[#5F2DED] bg-[#F6F2FF] dark:bg-[#34344A]' : 'border-l-transparent'"
                            class="w-full border-l-4 px-4 py-2.5 text-left text-sm font-medium text-[#5E5F60] transition-colors hover:bg-gray-50 dark:text-white dark:hover:bg-gray-800">
                            All categories
                        </button>

                        <template x-for="category in getDefineTagVisibleCategories()" :key="`define-tag-category-${category}`">
                            <button type="button"
                                @click="selectDefineTagCategory(category)"
                                :class="defineTagCurrentCategory === category ? 'border-l-[#5F2DED] bg-[#F6F2FF] dark:bg-[#34344A]' : 'border-l-transparent'"
                                class="w-full border-l-4 px-4 py-2.5 text-left text-sm font-medium text-[#5E5F60] transition-colors hover:bg-gray-50 dark:text-white dark:hover:bg-gray-800"
                                x-text="category"></button>
                        </template>
                    </div>

                    <p x-show="!getDefineTagVisibleCategories().length" class="mt-3 text-sm text-gray-500 dark:text-gray-300">
                        Category not found.
                    </p>

                    <div class="mt-4 flex items-center justify-between" x-show="getDefineTagCategoryTotalPages() > 1">
                        <button type="button"
                            @click="prevDefineTagCategoryPage()"
                            :disabled="getDefineTagCurrentCategoryPage() <= 1"
                            class="rounded-lg border border-[#ABB7C2] px-3 py-1.5 text-sm text-[#5E5F60] hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-50 dark:text-white dark:hover:bg-gray-800">
                            Prev
                        </button>
                        <p class="text-sm text-gray-500 dark:text-gray-300"
                           x-text="`Page ${getDefineTagCurrentCategoryPage()} of ${getDefineTagCategoryTotalPages()}`"></p>
                        <button type="button"
                            @click="nextDefineTagCategoryPage()"
                            :disabled="getDefineTagCurrentCategoryPage() >= getDefineTagCategoryTotalPages()"
                            class="rounded-lg border border-[#ABB7C2] px-3 py-1.5 text-sm text-[#5E5F60] hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-50 dark:text-white dark:hover:bg-gray-800">
                            Next
                        </button>
                    </div>
                </div>

                <div class="mt-6 flex justify-end">
                    <button type="button"
                        @click="applyDefinedTagsToSelectedQuestions()"
                        class="rounded bg-primaryColor px-4 py-2 text-white hover:opacity-90">
                        Confirm
                    </button>
                </div>
            </div>
        </div>

        <div id="deleteQuizModal"
             class="fixed inset-0 items-center justify-center bg-black bg-opacity-50 hidden z-50">
            <div class="bg-white dark:bg-[#1E1E2D] p-6 rounded-lg w-96">
                <h2 class="text-xl font-bold mb-4 text-red-600">Delete Quiz?</h2>
                <p class="text-[#373737] dark:text-white mb-6">
                    This action cannot be undone. All questions will be deleted.
                </p>
                <div class="flex justify-end gap-3">
                    <div>
                        <button type="button" id="cancelDelete"
                                class="px-4 py-2 bg-gray-300 rounded dark:bg-gray-600 dark:text-white">
                            Cancel
                        </button>
                    </div>
                    <form class="m-0" action="{{ route('teacher.quiz.delete', [$section->id, $subject->id, $quiz->id]) }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                data-loading-text="Deleting..."
                                class="px-4 py-2 bg-red-600 text-white rounded disabled:opacity-75 disabled:cursor-wait">
                            Delete
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div id="saveQuestionsModal"
             class="fixed inset-0 items-center justify-center bg-black bg-opacity-50 hidden z-50">
            <div class="bg-white dark:bg-[#1E1E2D] p-6 rounded-lg w-96">
                <h2 class="text-xl font-bold mb-4 text-gray-800 dark:text-white">Update Questions?</h2>
                <p class="text-[#373737] dark:text-white mb-6">
                    Are you sure you want to update these questions?
                </p>
                <div class="flex justify-end gap-3">
                    <button type="button" id="cancelSaveQuestions"
                            class="px-4 py-2 bg-gray-300 rounded dark:bg-gray-600 dark:text-white">
                        Cancel
                    </button>
                    <button type="button" id="confirmSaveQuestions"
                            data-loading-text="Updating..."
                            class="px-4 py-2 bg-primaryColor text-white rounded hover:opacity-90">
                        Confirm
                    </button>
                </div>
            </div>
        </div>

        <div
            x-cloak
            x-show="questionBankModalOpen"
            class="fixed inset-0 z-[120000] flex items-center justify-center bg-black/50 p-4"
            @click.self="closeQuestionBankModal()">
            <div class="relative flex h-[min(76vh,680px)] w-full max-w-[760px] min-h-0 flex-col rounded-[22px] border border-[#E6EDF4] bg-white p-5 shadow-2xl dark:border-white/10 dark:bg-[#0F172A]">
                <div class="flex items-start justify-between gap-4 border-b border-[#E6EDF4] pb-4 dark:border-white/10">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-[#8B97A7] dark:text-[#7F92A8]">Saved Questions</p>
                        <h3 class="mt-2 text-xl font-bold text-[#394554] dark:text-white">Import Questions</h3>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="relative w-full sm:w-52">
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-[#8B97A7] dark:text-[#7F92A8]"><i class="icofont-search-1 text-xs"></i></span>
                            <input
                                x-model.trim="questionBankFilters.search"
                                @keydown.enter.prevent="fetchQuestionBankItems()"
                                type="text"
                                placeholder="Search"
                                class="h-10 w-full rounded-lg border border-[#D7E1EA] bg-[#F9FBFD] pl-8 pr-3 text-sm text-[#394554] outline-none transition focus:border-primaryColor dark:border-white/10 dark:bg-[#121A29] dark:text-white">
                        </div>
                        <button type="button" @click="closeQuestionBankModal()" class="px-2 text-xl leading-none text-gray-500 transition hover:text-gray-700 dark:text-gray-300 dark:hover:text-white" aria-label="Close question import panel">&times;</button>
                    </div>
                </div>

                <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center">
                    <select
                        x-model="questionBankFilters.type"
                        @change="fetchQuestionBankItems()"
                        class="h-10 w-full rounded-lg border border-[#D7E1EA] bg-[#F9FBFD] px-3 text-sm text-[#394554] outline-none transition focus:border-primaryColor sm:w-[180px] dark:border-white/10 dark:bg-[#121A29] dark:text-white">
                        <option value="">All types</option>
                        <option value="multiple_choice">Multiple Choice</option>
                        <option value="short_answer">Short Answer</option>
                        <option value="true_false">True / False</option>
                        <option value="matching">Matching</option>
                        <option value="essay">Essay</option>
                        <option value="numerical">Numerical</option>
                    </select>
                    <div class="flex items-center gap-2 sm:ml-auto">
                        <button
                            type="button"
                            @click="fetchQuestionBankItems()"
                            class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-[#D7E1EA] text-[#394554] transition hover:bg-[#EEF4FF] dark:border-white/10 dark:text-white dark:hover:bg-[#1B2540]"
                            title="Apply search"
                            aria-label="Apply search">
                            <i class="icofont-search-1 text-sm"></i>
                        </button>
                        <button
                            type="button"
                            @click="resetQuestionBankFilters()"
                            class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-[#D7E1EA] text-[#394554] transition hover:bg-[#EEF4FF] dark:border-white/10 dark:text-white dark:hover:bg-[#1B2540]"
                            title="Reset filters"
                            aria-label="Reset filters">
                            <i class="icofont-refresh text-sm"></i>
                        </button>
                    </div>
                </div>

                <div x-show="questionBankModalError" x-cloak class="mt-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-600 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-200" x-text="questionBankModalError"></div>

                <div class="mt-4 min-h-0 flex-1 overflow-hidden rounded-2xl border border-[#E6EDF4] bg-[#F9FBFD] p-2 dark:border-white/10 dark:bg-[#121A29]">
                    <div class="flex items-center justify-between border-b border-[#E6EDF4] px-3 py-3 dark:border-white/10">
                        <p class="text-sm font-semibold text-[#394554] dark:text-white" x-text="questionBankLoading ? 'Loading saved questions...' : `${questionBankMeta.total} saved question(s)`"></p>
                    </div>

                    <div class="quiz-scrollbar-visible mx-auto h-full max-h-[420px] min-h-0 w-full space-y-2 overflow-y-auto px-1 py-2 pr-1">
                        <template x-if="!questionBankLoading && questionBankItems.length === 0">
                            <div class="rounded-xl border border-dashed border-[#D7E1EA] px-4 py-10 text-center text-sm text-[#8B97A7] dark:border-white/10 dark:text-[#7F92A8]">No saved questions found.</div>
                        </template>

                        <template x-for="item in questionBankItems" :key="`question-bank-item-${item.id}`">
                            <label class="flex cursor-pointer items-start gap-3 rounded-xl border bg-white px-4 py-3 transition dark:bg-[#0F172A]"
                                :class="questionBankSelectedIds.includes(String(item.id))
                                    ? 'border-primaryColor bg-[#F8FBFF] dark:border-[#4B7BFF] dark:bg-[#13203A]'
                                    : 'border-transparent hover:border-primaryColor hover:bg-[#F8FBFF] dark:hover:bg-[#13203A]'">
                                <input
                                    type="checkbox"
                                    class="mt-1 h-4 w-4 rounded border-gray-300 text-[#5F2DED] focus:ring-[#5F2DED]"
                                    :checked="questionBankSelectedIds.includes(String(item.id))"
                                    @change="toggleQuestionBankSelection(item.id)">
                                <div class="min-w-0 flex-1">
                                    <p class="text-[11px] font-semibold uppercase tracking-wide text-[#8B97A7] dark:text-[#7F92A8]" x-text="questionBankItemMetaLabel(item)"></p>
                                    <p class="mt-2 truncate text-sm font-semibold text-[#394554] dark:text-white" x-text="item.text"></p>
                                    <div class="mt-2 flex flex-wrap gap-2 text-[11px] text-[#617184] dark:text-[#96A6BB]">
                                        <span x-text="`${item.default_points} pt(s)`"></span>
                                        <span x-text="item.required ? 'Required' : 'Optional'"></span>
                                    </div>
                                </div>
                            </label>
                        </template>
                    </div>
                </div>

                <div class="mt-3 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-sm text-[#617184] dark:text-[#96A6BB]" x-text="questionBankSelectedIds.length ? `${questionBankSelectedIds.length} saved question(s) selected.` : 'Choose one or more saved questions to import.'"></p>
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            @click="clearQuestionBankSelection()"
                            :disabled="!questionBankSelectedIds.length"
                            class="rounded-xl border border-[#D7E1EA] px-4 py-2 text-sm font-semibold text-[#394554] transition hover:bg-[#EEF4FF] disabled:cursor-not-allowed disabled:opacity-50 dark:border-white/10 dark:text-white dark:hover:bg-[#1B2540]">
                            Clear
                        </button>
                        <button
                            type="button"
                            @click="importSelectedQuestionBankItems()"
                            :disabled="questionBankImporting || !questionBankSelectedIds.length"
                            class="rounded-xl bg-[#5F2DED] px-4 py-2 text-sm font-semibold text-white hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-50">
                            <span x-text="questionBankImporting ? 'Importing...' : 'Import Questions'"></span>
                        </button>
                    </div>
                </div>
            </div>

        </div>

    <script>
    function quizForm(initialQuestions, pageSize = 10, initialPage = 1, initialDefaultType = 'multiple_choice', initialErrors = {}, initialAvailableTags = [], initialQuizTagMetrics = {}, initialDefaultTagRelevance = 'medium', initialQuestionBankRoutes = {}) {
            const parseMaybeJson = (val, fallback) => {
                if (Array.isArray(val)) return val;
                if (val === null || val === undefined || val === '') return fallback;
                if (typeof val === 'object') return val;
                if (typeof val === 'string') {
                    try { return JSON.parse(val) } catch { return fallback; }
                }
                return fallback;
            };

            const normalizeArrayLike = (val) => {
                const parsed = parseMaybeJson(val, []);
                if (Array.isArray(parsed)) return parsed;
                if (parsed && typeof parsed === 'object') {
                    const keys = Object.keys(parsed);
                    keys.sort((a, b) => (parseInt(a, 10) || 0) - (parseInt(b, 10) || 0));
                    return keys.map(k => parsed[k]);
                }
                return [];
            };

            const normalizeTagIds = (val) => {
                const parsed = normalizeArrayLike(val);
                if (!Array.isArray(parsed)) return [];

                return Array.from(new Set(parsed.map((entry) => {
                    if (entry && typeof entry === 'object') {
                        return String(entry.id ?? '').trim();
                    }
                    return String(entry ?? '').trim();
                }).filter(Boolean)));
            };

            const resolveInitialTagIds = (question) => {
                if (question && Object.prototype.hasOwnProperty.call(question, 'tag_ids')) {
                    return normalizeTagIds(question.tag_ids);
                }

                if (question && Object.prototype.hasOwnProperty.call(question, 'tags')) {
                    return normalizeTagIds(question.tags);
                }

                return [];
            };

            const buildErrorMap = (errors) => {
                const out = {};
                if (!errors || typeof errors !== 'object') return out;

                Object.keys(errors).forEach((key) => {
                    const match = key.match(/^questions\.(\d+)(?:\.(.+))?$/);
                    if (!match) return;
                    const idx = parseInt(match[1], 10);
                    if (!Number.isFinite(idx)) return;

                    const field = match[2] || '';
                    const msgs = Array.isArray(errors[key]) ? errors[key] : [String(errors[key] ?? '')];

                    if (!out[idx]) out[idx] = { messages: [], fields: [] };
                    msgs.forEach((m) => {
                        if (m) out[idx].messages.push(String(m));
                    });
                    if (field) out[idx].fields.push(field);
                });

                Object.values(out).forEach((entry) => {
                    entry.messages = Array.from(new Set(entry.messages));
                    entry.fields = Array.from(new Set(entry.fields));
                });

                return out;
            };

            const normalizeInitialQuestions = (val) => {
                const parsed = parseMaybeJson(val, []);
                if (Array.isArray(parsed)) return parsed;
                if (!parsed || typeof parsed !== 'object') return [];

                // If it's a single question object, wrap it.
                const looksLikeQuestion = ('text' in parsed) || ('type' in parsed) || ('order' in parsed) || ('options' in parsed) || ('option' in parsed);
                if (looksLikeQuestion) return [parsed];

                // If it's a sparse numeric-keyed object (common from old() after deletes), turn it into a stable array.
                const keys = Object.keys(parsed);
                keys.sort((a, b) => (parseInt(a, 10) || 0) - (parseInt(b, 10) || 0));
                return keys.map(k => parsed[k]);
            };

            const normalizeQuestion = (q, i) => {
                const type = q?.type ?? 'multiple_choice';

                const parsedOrder = parseInt(q?.order, 10);
                const safeOrder = (Number.isFinite(parsedOrder) && parsedOrder >= 1) ? parsedOrder : (i + 1);

                const optionRaw = (q?.option !== undefined) ? q.option : q?.options;
                const safeOptions = normalizeArrayLike(optionRaw);
                const finalOptions = (type === 'multiple_choice')
                    ? (safeOptions.length ? safeOptions : [''])
                    : safeOptions;

                const rawAK = (typeof q?.answer_key === 'string')
                    ? (() => { try { return JSON.parse(q.answer_key) } catch { return q.answer_key } })()
                    : q?.answer_key;

                let mcIndexes = [];
                if (type === 'multiple_choice') {
                    const akList = normalizeArrayLike(rawAK);
                    if (akList.length) {
                        const allNumeric = akList.every(v => String(v).trim() !== '' && !isNaN(Number(v)));
                        if (allNumeric) {
                            mcIndexes = akList.map(v => String(v));
                        } else {
                            mcIndexes = akList
                                .map(v => finalOptions.indexOf(v))
                                .filter(idx => idx >= 0)
                                .map(idx => String(idx));
                        }
                    }
                }

                const pairsRaw = (q?.pairs !== undefined && q?.pairs !== null)
                    ? parseMaybeJson(q?.pairs, [])
                    : parseMaybeJson(q?.option, []);

                const matchingPairs = Array.isArray(pairsRaw) ? pairsRaw : [];
                let poolLeft = [];
                let poolRight = [];

                if (type === 'matching') {
                    const leftInput = normalizeArrayLike(q?.match_left);
                    const rightInput = normalizeArrayLike(q?.match_right);
                    if (leftInput.length) poolLeft = leftInput;
                    if (rightInput.length) poolRight = rightInput;

                    // NEW shape: option = { left: [], right: [] }
                    if ((!poolLeft.length || !poolRight.length) && pairsRaw && typeof pairsRaw === 'object' && !Array.isArray(pairsRaw)) {
                        if (!poolLeft.length) poolLeft = Array.isArray(pairsRaw.left) ? pairsRaw.left : [];
                        if (!poolRight.length) poolRight = Array.isArray(pairsRaw.right) ? pairsRaw.right : [];
                    }

                    // LEGACY shape: option = [{left,right}, ...]
                    if ((!poolLeft.length || !poolRight.length) && Array.isArray(matchingPairs)) {
                        const legacyPairs = matchingPairs.filter(p => p && typeof p === 'object');
                        if (!poolLeft.length) poolLeft = legacyPairs.map(p => p.left ?? '');
                        if (!poolRight.length) poolRight = legacyPairs.map(p => p.right ?? '');
                    }
                }

                const questionSetting = parseMaybeJson(q?.setting, {});
                const pairPointsArr = normalizeArrayLike(q?.pair_points ?? questionSetting?.pair_points);
                const hasPairPoints = pairPointsArr.length > 0;
                const normalizePairPoint = (val) => {
                    const n = parseInt(val, 10);
                    return Number.isFinite(n) && n >= 0 ? n : 0;
                };

                let normalizedPairPoints = [];
                if (type === 'matching') {
                    const targetLen = poolLeft.length ? poolLeft.length : 1;
                    for (let i = 0; i < targetLen; i++) {
                        const source = (pairPointsArr[i] !== undefined) ? pairPointsArr[i] : (q?.points ?? 1);
                        normalizedPairPoints.push(normalizePairPoint(source));
                    }
                }

                const selectedPairs = normalizeArrayLike(q?.answer_key);

                const matchMap = {};
                if (type === 'matching') {
                    const mapInput = parseMaybeJson(q?.match_map, {});
                    if (Array.isArray(mapInput)) {
                        mapInput.forEach((val, idx) => {
                            const rowIndex = parseInt(idx, 10);
                            const colIndex = parseInt(val, 10);
                            if (!Number.isFinite(rowIndex) || !Number.isFinite(colIndex)) return;
                            if (!poolLeft[rowIndex] || !poolRight[colIndex]) return;
                            matchMap[String(rowIndex)] = String(colIndex);
                        });
                    } else if (mapInput && typeof mapInput === 'object') {
                        Object.keys(mapInput).forEach((k) => {
                            const rowIndex = parseInt(k, 10);
                            const colIndex = parseInt(mapInput[k], 10);
                            if (!Number.isFinite(rowIndex) || !Number.isFinite(colIndex)) return;
                            if (!poolLeft[rowIndex] || !poolRight[colIndex]) return;
                            matchMap[String(rowIndex)] = String(colIndex);
                        });
                    }

                    if (!Object.keys(matchMap).length) {
                        selectedPairs.forEach((p) => {
                            if (!p || typeof p !== 'object') return;
                            const l = String(p.left ?? '').trim();
                            const r = String(p.right ?? '').trim();
                            if (!l || !r) return;
                            const rowIndex = poolLeft.indexOf(l);
                            const colIndex = poolRight.indexOf(r);
                            if (rowIndex >= 0 && colIndex >= 0) {
                                matchMap[String(rowIndex)] = String(colIndex);
                            }
                        });
                    }
                }

                const akText = (typeof q?.answer_key === 'string') ? q.answer_key : '';
                const akBool = (q?.answer_key === true) ? 'true' : (q?.answer_key === false ? 'false' : '');
                const akNum  = (typeof q?.answer_key === 'number') ? q.answer_key : '';
                const initialTagIds = resolveInitialTagIds(q);
                const initialTagRelevance = resolveInitialQuestionTagRelevance(q, initialTagIds);

                return {
                    id: q?.id ?? null,
                    order: safeOrder,
                    page_hint: Math.max(1, parseInt(q?.page_hint ?? pageOfOrderInit(q?.order), 10) || 1),
                    text: q?.text ?? '',
                    type,

                    options: finalOptions,
                    answer_key: mcIndexes,
                    match_left: (type === 'matching') ? (poolLeft.length ? poolLeft : ['']) : [],
                    match_right: (type === 'matching') ? (poolRight.length ? poolRight : ['']) : [],
                    match_map: matchMap,
                    pair_points: (type === 'matching') ? normalizedPairPoints : [],
                    use_pair_points: (type === 'matching')
                        ? !!((q?.use_pair_points ?? questionSetting?.use_pair_points) || hasPairPoints)
                        : false,

                    points: (() => {
                        const n = parseInt(q?.points, 10);
                        return Number.isFinite(n) && n >= 0 ? n : 1;
                    })(),
                    required: !!q?.required,
                    showAnswerKey: false,
                    tag_ids: initialTagIds,
                    tag_relevance: initialTagRelevance,

                    answer_key_text: akText,
                    answer_key_bool: akBool,
                    answer_key_num: akNum,
                };
            };
            const createQuestionFromBankItem = (item, order, pageHint) => {
                const type = String(item?.type ?? 'multiple_choice');
                const defaultPoints = (() => {
                    const value = parseInt(item?.default_points ?? item?.points, 10);
                    return Number.isFinite(value) && value >= 0 ? value : 1;
                })();
                const rawTags = Array.isArray(item?.tags) ? item.tags : [];
                const tagIds = rawTags
                    .map((tag) => String(tag?.id ?? '').trim())
                    .filter(Boolean);
                const tagRelevance = rawTags.reduce((carry, tag) => {
                    const key = String(tag?.id ?? '').trim();
                    if (!key) return carry;
                    carry[key] = normalizeRelevance(tag?.relevance);
                    return carry;
                }, {});

                const question = normalizeQuestion({
                    order,
                    page_hint: pageHint,
                    type,
                    text: String(item?.text ?? '').trim(),
                    option: item?.option ?? [],
                    answer_key: item?.answer_key ?? [],
                    setting: item?.setting ?? null,
                    points: defaultPoints,
                    required: !!item?.required,
                    tag_ids: tagIds,
                    tag_relevance: tagRelevance,
                }, order - 1);

                if (type === 'matching') {
                    const option = (item?.option && typeof item.option === 'object' && !Array.isArray(item.option)) ? item.option : {};
                    const matchLeft = normalizeArrayLike(option.left);
                    const setting = parseMaybeJson(item?.setting, {});
                    const pairPoints = normalizeArrayLike(setting?.pair_points);
                    question.pair_points = Array.from({ length: Math.max(matchLeft.length, 1) }, (_, index) => {
                        const value = parseInt(pairPoints[index] ?? defaultPoints, 10);
                        return Number.isFinite(value) && value >= 0 ? value : defaultPoints;
                    });
                    question.use_pair_points = !!(setting?.use_pair_points || pairPoints.length);
                }

                question.id = null;
                question.order = order;
                question.page_hint = pageHint;
                return question;
            };

        const initialList = normalizeInitialQuestions(initialQuestions);
        const questionBankRoutes = (initialQuestionBankRoutes && typeof initialQuestionBankRoutes === 'object')
            ? initialQuestionBankRoutes
            : {};
        const normalizedAvailableTags = normalizeArrayLike(initialAvailableTags)
            .map((tag) => {
                if (!tag || typeof tag !== 'object') return null;
                const id = String(tag.id ?? '').trim();
                if (!id) return null;

                return {
                    id,
                    name: String(tag.name ?? '').trim() || `Tag ${id}`,
                    category: String(tag.category ?? '').trim(),
                };
            })
            .filter(Boolean);
        const relevanceToneClasses = {
            very_high: 'quiz-tag-tone-very-high',
            high: 'quiz-tag-tone-high',
            medium: 'quiz-tag-tone-medium',
            low: 'quiz-tag-tone-low',
            very_low: 'quiz-tag-tone-very-low',
        };
        const relevanceLabelMap = {
            very_high: 'Very High',
            high: 'High',
            medium: 'Medium',
            low: 'Low',
            very_low: 'Very Low',
        };
        const relevanceSortRank = {
            very_high: 5,
            high: 4,
            medium: 3,
            low: 2,
            very_low: 1,
        };
        const relevanceScale = ['very_low', 'low', 'medium', 'high', 'very_high'];
        const allowedRelevanceValues = ['very_high', 'high', 'medium', 'low', 'very_low'];
        const defaultRelevanceValue = (() => {
            const candidate = String(initialDefaultTagRelevance ?? 'medium').trim().toLowerCase();
            return allowedRelevanceValues.includes(candidate) ? candidate : 'medium';
        })();
        const normalizeRelevance = (value) => {
            const normalized = String(value ?? '').trim().toLowerCase();
            return allowedRelevanceValues.includes(normalized) ? normalized : defaultRelevanceValue;
        };
        const normalizeCoveragePercent = (value) => {
            const numeric = Number(value);
            if (!Number.isFinite(numeric)) return 0;
            return Math.max(0, Math.min(100, Math.round(numeric * 100) / 100));
        };
        const normalizedQuizTagMetrics = Object.entries(parseMaybeJson(initialQuizTagMetrics, {}))
            .reduce((carry, [tagId, metric]) => {
                const key = String(tagId ?? '').trim();
                if (!key) return carry;
                const source = (metric && typeof metric === 'object') ? metric : {};
                carry[key] = {
                    relevance: normalizeRelevance(source.relevance),
                    coverage: normalizeCoveragePercent(source.coverage),
                };
                return carry;
            }, {});
        const normalizeTagRelevanceMap = (value) => {
            const parsed = parseMaybeJson(value, {});
            if (!parsed || typeof parsed !== 'object' || Array.isArray(parsed)) {
                return {};
            }

            return Object.entries(parsed).reduce((carry, [tagId, relevance]) => {
                const key = String(tagId ?? '').trim();
                if (!key) return carry;
                carry[key] = normalizeRelevance(relevance);
                return carry;
            }, {});
        };
        const resolveInitialQuestionTagRelevance = (question, questionTagIds) => {
            const tagIds = normalizeTagIds(questionTagIds);
            if (!tagIds.length) return {};

            const resolved = {};
            const rawMap = normalizeTagRelevanceMap(question?.tag_relevance);
            tagIds.forEach((tagId) => {
                if (Object.prototype.hasOwnProperty.call(rawMap, tagId)) {
                    resolved[tagId] = normalizeRelevance(rawMap[tagId]);
                }
            });

            if (Array.isArray(question?.tags)) {
                question.tags.forEach((tag) => {
                    const tagId = String(tag?.id ?? '').trim();
                    if (!tagId || !tagIds.includes(tagId)) return;
                    const pivotRelevance = tag?.pivot?.relevance_level
                        ?? tag?.pivot?.relevance
                        ?? tag?.relevance_level
                        ?? tag?.relevance;
                    if (pivotRelevance === undefined || pivotRelevance === null || String(pivotRelevance).trim() === '') {
                        return;
                    }
                    resolved[tagId] = normalizeRelevance(pivotRelevance);
                });
            }

            tagIds.forEach((tagId) => {
                if (Object.prototype.hasOwnProperty.call(resolved, tagId)) return;
                const metric = (normalizedQuizTagMetrics && normalizedQuizTagMetrics[tagId]) ? normalizedQuizTagMetrics[tagId] : {};
                resolved[tagId] = normalizeRelevance(metric?.relevance);
            });

            return resolved;
        };
        const clampPageSizeValue = (val) => {
            const n = parseInt(val, 10);
            if (!Number.isFinite(n)) return 10;
            if (n < 1) return 1;
            if (n > 25) return 25;
            return n;
        };
        const pageOfOrderInit = (order) => {
            const o = parseInt(order, 10);
            const size = clampPageSizeValue(pageSize);
            if (!Number.isFinite(o) || o < 1) return 1;
            return Math.floor((o - 1) / size) + 1;
        };
        const defaultTypeInitial = initialDefaultType || (initialList[0]?.type ?? 'multiple_choice');

            return {
                lastDeleted: null,
                lastDeletedOption: null,
            toastVisible: false,
            toastKind: 'question',
            toastTimer: null,

            errorsByIndex: buildErrorMap(initialErrors),
            generalErrors: Array.isArray(initialErrors?.questions) ? initialErrors.questions : [],
            clientErrorsVisible: false,
            availableTags: normalizedAvailableTags,
                tagMetrics: normalizedQuizTagMetrics,
                questionTagModalOpen: false,
                defineTagModalOpen: false,
                tagModalCurrentPage: 1,
                tagPagePickerOpen: false,
                selectedTagQuestionIndexes: [],
                expandedTagQuestionIndexes: [],
                expandedQuestionTagLabelIndexes: [],
                defineTagSelection: [],
                defineTagSearch: '',
                defineTagCategoryMode: false,
                defineTagRelevanceMode: false,
                defineTagCurrentCategory: '',
                defineTagCurrentTagPage: 1,
                defineTagCurrentCategoryPage: 1,
                defineTagCurrentRelevancePage: 1,
                defineTagFocusedTagId: null,
                defineTagFocusedQuestionIndex: null,
                questionBankRoutes,
                questionBankModalOpen: false,
                questionBankLoading: false,
                questionBankImporting: false,
                questionBankModalError: '',
                questionBankItems: [],
                questionBankSelectedIds: [],
                questionBankFilters: { search: '', type: '' },
                questionBankMeta: { total: 0 },

                pageSize: clampPageSizeValue(pageSize),
            defaultQuestionType: defaultTypeInitial,
            currentPage: (() => {
                const n = parseInt(initialPage, 10);
                if (!Number.isFinite(n) || n < 1) return 1;
                return Math.min(10, n);
            })(),

                pageRange(page) {
                    const p = Math.max(1, parseInt(page, 10) || 1);
                    const size = this.pageSize || 10;
                    const start = ((p - 1) * size) + 1;
                    const end = (p * size);
                    return { start, end };
                },

                pageOfOrder(order) {
                    const o = parseInt(order, 10);
                    const size = this.pageSize || 10;
                    if (!Number.isFinite(o) || o < 1) return 1;
                    return Math.floor((o - 1) / size) + 1;
                },

                indexesForPage(page) {
                    const out = [];
                    if (!Array.isArray(this.questions)) return out;
                    const { start, end } = this.pageRange(page);
                    for (let i = 0; i < this.questions.length; i++) {
                        const o = parseInt(this.questions[i]?.order, 10);
                        if (!Number.isFinite(o)) continue;
                        if (o >= start && o <= end) out.push(i);
                    }
                    out.sort((a, b) => (parseInt(this.questions[a]?.order, 10) || 0) - (parseInt(this.questions[b]?.order, 10) || 0));
                    return out;
                },

                isPageFull(page) {
                    return this.indexesForPage(page).length >= (this.pageSize || 10);
                },

                nextAvailableOrderForPage(page) {
                    const { start, end } = this.pageRange(page);
                    const used = new Set(this.indexesForPage(page).map(i => parseInt(this.questions[i]?.order, 10)).filter(n => Number.isFinite(n)));
                    for (let o = start; o <= end; o++) {
                        if (!used.has(o)) return o;
                    }
                    return null;
                },

                compactOrdersForPage(page) {
                    const idxs = this.indexesForPage(page);
                    const { start } = this.pageRange(page);
                    for (let pos = 0; pos < idxs.length; pos++) {
                        const i = idxs[pos];
                        if (!this.questions[i]) continue;
                        this.questions[i].order = start + pos;
                    }
                },

                get totalPages() {
                    const maxFromOrders = (Array.isArray(this.questions) ? this.questions : [])
                        .map(q => this.pageOfOrder(q?.order))
                        .reduce((m, p) => Math.max(m, p), 1);
                    return Math.min(10, Math.max(1, maxFromOrders, (this.currentPage + 1)));
                },

                get totalPoints() {
                    return (Array.isArray(this.questions) ? this.questions : [])
                        .reduce((sum, q) => sum + this.effectivePoints(q), 0);
                },

                get visibleQuestionIndexes() {
                    return this.indexesForPage(this.currentPage);
                },

            get allQuestionIndexes() {
                return Array.isArray(this.questions) ? this.questions.map((_, i) => i) : [];
            },

            questionErrors(index) {
                return this.errorsByIndex?.[index]?.messages ?? [];
            },

            questionHasError(index) {
                return this.questionErrors(index).length > 0;
            },

            questionHasFieldError(index, prefixes = []) {
                const fields = this.errorsByIndex?.[index]?.fields ?? [];
                if (!fields.length) return false;
                return fields.some((f) => prefixes.some((p) => f === p || f.startsWith(p)));
            },

            tagName(tagId) {
                const target = String(tagId ?? '').trim();
                if (!target) return 'Tag';

                const matched = (this.availableTags || []).find((tag) => String(tag?.id ?? '').trim() === target);
                return matched?.name || `Tag ${target}`;
            },

            tagCategory(tagId) {
                const target = String(tagId ?? '').trim();
                if (!target) return '';

                const matched = (this.availableTags || []).find((tag) => String(tag?.id ?? '').trim() === target);
                return String(matched?.category ?? '').trim();
            },

            normalizeQuestionTagRelevance(question) {
                if (!question || typeof question !== 'object') return {};

                const tagIds = normalizeTagIds(question.tag_ids);
                const rawMap = (question.tag_relevance && typeof question.tag_relevance === 'object' && !Array.isArray(question.tag_relevance))
                    ? question.tag_relevance
                    : {};
                const nextMap = {};

                tagIds.forEach((tagId) => {
                    const fallbackMetric = (this.tagMetrics && this.tagMetrics[tagId]) ? this.tagMetrics[tagId] : {};
                    const candidate = Object.prototype.hasOwnProperty.call(rawMap, tagId)
                        ? rawMap[tagId]
                        : fallbackMetric?.relevance;
                    nextMap[tagId] = normalizeRelevance(candidate);
                });

                question.tag_relevance = nextMap;
                return nextMap;
            },

            questionTagRelevance(questionIndex, tagId) {
                const idx = Number.parseInt(questionIndex, 10);
                const key = String(tagId ?? '').trim();
                const fallbackMetric = (this.tagMetrics && this.tagMetrics[key]) ? this.tagMetrics[key] : {};
                if (!key) return normalizeRelevance(fallbackMetric?.relevance);
                if (!Number.isFinite(idx) || idx < 0 || !this.questions?.[idx]) {
                    return normalizeRelevance(fallbackMetric?.relevance);
                }

                const question = this.questions[idx];
                const rawMap = (question.tag_relevance && typeof question.tag_relevance === 'object' && !Array.isArray(question.tag_relevance))
                    ? question.tag_relevance
                    : {};
                if (Object.prototype.hasOwnProperty.call(rawMap, key)) {
                    return normalizeRelevance(rawMap[key]);
                }

                const map = this.normalizeQuestionTagRelevance(question);
                if (Object.prototype.hasOwnProperty.call(map, key)) {
                    return normalizeRelevance(map[key]);
                }

                return normalizeRelevance(fallbackMetric?.relevance);
            },

            setQuestionTagRelevance(questionIndex, tagId, relevanceValue) {
                const idx = Number.parseInt(questionIndex, 10);
                const key = String(tagId ?? '').trim();
                if (!key || !Number.isFinite(idx) || idx < 0 || !this.questions?.[idx]) return;

                const map = this.normalizeQuestionTagRelevance(this.questions[idx]);
                map[key] = normalizeRelevance(relevanceValue);
                this.questions[idx].tag_relevance = { ...map };
            },

            getDefineTagMetricContextQuestionIndex() {
                const selected = (Array.isArray(this.selectedTagQuestionIndexes) ? this.selectedTagQuestionIndexes : [])
                    .map((value) => Number.parseInt(value, 10))
                    .filter((value) => Number.isFinite(value) && value >= 0 && this.questions?.[value])
                    .sort((a, b) => a - b);
                const focused = Number.parseInt(this.defineTagFocusedQuestionIndex, 10);
                if (Number.isFinite(focused) && selected.includes(focused)) {
                    return focused;
                }

                return selected.length ? selected[0] : null;
            },

            resolveTagMetric(tagId, questionIndex = null) {
                const key = String(tagId ?? '').trim();
                if (!key) {
                    return {
                        relevance: normalizeRelevance(initialDefaultTagRelevance),
                        coverage: 0,
                    };
                }

                const idx = Number.parseInt(questionIndex, 10);
                const metric = (this.tagMetrics && this.tagMetrics[key]) ? this.tagMetrics[key] : {};
                const relevance = Number.isFinite(idx)
                    ? this.questionTagRelevance(idx, key)
                    : normalizeRelevance(metric?.relevance);
                return {
                    relevance,
                    coverage: normalizeCoveragePercent(metric?.coverage),
                };
            },

            tagRelevanceLabel(tagId, questionIndex = null) {
                let contextQuestionIndex = Number.parseInt(questionIndex, 10);
                if (!Number.isFinite(contextQuestionIndex) && this.defineTagRelevanceMode) {
                    contextQuestionIndex = this.getDefineTagMetricContextQuestionIndex();
                }
                const relevance = this.resolveTagMetric(tagId, contextQuestionIndex).relevance;
                return relevanceLabelMap[relevance] || relevanceLabelMap.medium;
            },

            tagCoveragePercent(tagId, questionIndex = null) {
                return this.resolveTagMetric(tagId, questionIndex).coverage;
            },

            tagBadgeClass(tagId, questionIndex = null) {
                const relevance = this.resolveTagMetric(tagId, questionIndex).relevance;
                return relevanceToneClasses[relevance] || relevanceToneClasses.medium;
            },

            refreshTagCoverageMetrics() {
                const counts = {};
                const questions = Array.isArray(this.questions) ? this.questions : [];

                questions.forEach((question) => {
                    const tagIds = Array.from(new Set(normalizeTagIds(question?.tag_ids)));
                    tagIds.forEach((tagId) => {
                        counts[tagId] = (counts[tagId] ?? 0) + 1;
                    });
                });

                const totalAssignments = Object.values(counts).reduce((sum, count) => sum + (Number(count) || 0), 0);
                const nextMetrics = {};
                const existingKeys = new Set([
                    ...Object.keys(this.tagMetrics || {}),
                    ...Object.keys(counts),
                ]);

                existingKeys.forEach((tagId) => {
                    const prior = (this.tagMetrics && this.tagMetrics[tagId]) ? this.tagMetrics[tagId] : {};
                    const relevance = normalizeRelevance(prior?.relevance);
                    const count = counts[tagId] ?? 0;
                    const coverage = totalAssignments > 0 ? normalizeCoveragePercent((count / totalAssignments) * 100) : 0;
                    nextMetrics[tagId] = { relevance, coverage };
                });

                this.tagMetrics = nextMetrics;
            },

            questionPreview(question, limit = 80) {
                const text = String(question?.text ?? '').trim().replace(/\s+/g, ' ');
                if (!text) return 'Untitled question';
                if (text.length <= limit) return text;
                return `${text.slice(0, limit)}...`;
            },

            questionTypeLabel(type) {
                const map = {
                    multiple_choice: 'Multiple Choice',
                    short_answer: 'Short Answer',
                    true_false: 'True / False',
                    matching: 'Matching',
                    essay: 'Essay',
                    numerical: 'Numerical Input',
                };

                return map[String(type ?? '').trim()] || 'Question';
            },

            getExpandedStripTagGroups(questionIndex) {
                const idx = Number.parseInt(questionIndex, 10);
                if (!Number.isFinite(idx) || idx < 0 || !this.questions?.[idx]) return [];

                const orderedRelevance = ['very_high', 'high', 'medium', 'low', 'very_low'];
                const grouped = orderedRelevance.reduce((carry, level) => {
                    carry[level] = [];
                    return carry;
                }, {});
                const tagIds = normalizeTagIds(this.questions[idx]?.tag_ids);

                tagIds.forEach((tagId) => {
                    const relevance = this.resolveTagMetric(tagId, idx).relevance;
                    const key = normalizeRelevance(relevance);
                    if (!grouped[key]) {
                        grouped[key] = [];
                    }
                    grouped[key].push(tagId);
                });

                return orderedRelevance
                    .map((relevance) => ({
                        relevance,
                        label: relevanceLabelMap[relevance] || 'Medium',
                        tagIds: (grouped[relevance] || [])
                            .slice()
                            .sort((a, b) => this.tagName(a).localeCompare(this.tagName(b))),
                    }))
                    .filter((group) => group.tagIds.length > 0);
            },

            isCorrectMultipleChoiceOption(question, optionIndex) {
                const selected = Array.isArray(question?.answer_key) ? question.answer_key : [];
                return selected.map((value) => String(value)).includes(String(optionIndex));
            },

            get tagQuestionGroups() {
                const groups = {};
                const items = (Array.isArray(this.questions) ? this.questions : [])
                    .map((question, index) => ({
                        index,
                        question,
                        order: Number.parseInt(question?.order, 10) || (index + 1),
                    }))
                    .sort((a, b) => a.order - b.order);

                let number = 1;
                items.forEach((item) => {
                    const page = this.pageOfOrder(item.order);
                    groups[page] ??= [];
                    groups[page].push({
                        ...item,
                        number: number++,
                    });
                });

                return Object.keys(groups)
                    .map((page) => Number.parseInt(page, 10))
                    .filter((page) => Number.isFinite(page))
                    .sort((a, b) => a - b)
                    .map((page) => ({
                        page,
                        items: groups[page],
                    }));
            },

            getTagQuestionPageNumbers() {
                const pages = (this.tagQuestionGroups || [])
                    .map((group) => Number.parseInt(group?.page, 10))
                    .filter((page) => Number.isFinite(page))
                    .sort((a, b) => a - b);
                return Array.from(new Set(pages));
            },

            openTagPagePicker() {
                this.tagPagePickerOpen = true;
            },

            closeTagPagePicker() {
                this.tagPagePickerOpen = false;
            },

            navigateToTagQuestionPage(page) {
                this.scrollToTagQuestionPage(page);
                this.tagPagePickerOpen = false;
            },

            normalizeTagModalTargetPage(page) {
                const pages = this.getTagQuestionPageNumbers();
                if (!pages.length) return 1;

                const requested = Number.parseInt(page, 10);
                if (!Number.isFinite(requested)) return pages[0];
                if (pages.includes(requested)) return requested;

                const nextHigher = pages.find((value) => value > requested);
                return Number.isFinite(nextHigher) ? nextHigher : pages[pages.length - 1];
            },

            getTagModalCurrentPage() {
                this.tagModalCurrentPage = this.normalizeTagModalTargetPage(this.tagModalCurrentPage);
                return this.tagModalCurrentPage;
            },

            hasPreviousTagQuestionPage() {
                const pages = this.getTagQuestionPageNumbers();
                if (!pages.length) return false;
                const idx = pages.indexOf(this.getTagModalCurrentPage());
                return idx > 0;
            },

            hasNextTagQuestionPage() {
                const pages = this.getTagQuestionPageNumbers();
                if (!pages.length) return false;
                const idx = pages.indexOf(this.getTagModalCurrentPage());
                return idx >= 0 && idx < (pages.length - 1);
            },

            goToPreviousTagQuestionPage() {
                const pages = this.getTagQuestionPageNumbers();
                if (!pages.length) return;
                const idx = pages.indexOf(this.getTagModalCurrentPage());
                if (idx <= 0) return;
                this.scrollToTagQuestionPage(pages[idx - 1]);
            },

            goToNextTagQuestionPage() {
                const pages = this.getTagQuestionPageNumbers();
                if (!pages.length) return;
                const idx = pages.indexOf(this.getTagModalCurrentPage());
                if (idx < 0 || idx >= (pages.length - 1)) return;
                this.scrollToTagQuestionPage(pages[idx + 1]);
            },

            scrollToTagQuestionPage(page, instant = false) {
                const normalized = this.normalizeTagModalTargetPage(page);
                this.tagModalCurrentPage = normalized;

                const container = this.$refs?.tagQuestionPageScroll;
                if (!container) return;

                const target = container.querySelector(`[data-tag-page="${normalized}"]`);
                if (!target) return;

                container.scrollTo({
                    top: Math.max(0, target.offsetTop - 4),
                    behavior: instant ? 'auto' : 'smooth',
                });
            },

            onTagQuestionScroll() {
                const container = this.$refs?.tagQuestionPageScroll;
                if (!container) return;

                const groups = Array.from(container.querySelectorAll('[data-tag-page]'));
                if (!groups.length) return;

                const marker = container.scrollTop + 12;
                let activePage = Number.parseInt(groups[0].getAttribute('data-tag-page'), 10) || 1;
                let bestTop = -Infinity;

                groups.forEach((group) => {
                    const page = Number.parseInt(group.getAttribute('data-tag-page'), 10);
                    if (!Number.isFinite(page)) return;
                    const top = group.offsetTop;
                    if (top <= marker && top > bestTop) {
                        bestTop = top;
                        activePage = page;
                    }
                });

                this.tagModalCurrentPage = activePage;
            },

            getTagQuestionIndexesForPage(page) {
                const targetPage = Number.parseInt(page, 10);
                if (!Number.isFinite(targetPage)) return [];

                const group = (this.tagQuestionGroups || [])
                    .find((entry) => Number.parseInt(entry?.page, 10) === targetPage);

                if (!group || !Array.isArray(group.items)) return [];

                return group.items
                    .map((item) => Number.parseInt(item?.index, 10))
                    .filter((index) => Number.isFinite(index));
            },

            isTagPageFullySelected(page) {
                const pageIndexes = this.getTagQuestionIndexesForPage(page);
                if (!pageIndexes.length) return false;

                const selected = new Set((this.selectedTagQuestionIndexes || [])
                    .map((value) => Number.parseInt(value, 10))
                    .filter((value) => Number.isFinite(value)));

                return pageIndexes.every((index) => selected.has(index));
            },

            toggleTagPageSelection(page) {
                const pageIndexes = this.getTagQuestionIndexesForPage(page);
                if (!pageIndexes.length) return;

                const selected = new Set((this.selectedTagQuestionIndexes || [])
                    .map((value) => Number.parseInt(value, 10))
                    .filter((value) => Number.isFinite(value)));

                const shouldDeselect = pageIndexes.every((index) => selected.has(index));

                pageIndexes.forEach((index) => {
                    if (shouldDeselect) {
                        selected.delete(index);
                    } else {
                        selected.add(index);
                    }
                });

                this.selectedTagQuestionIndexes = Array.from(selected).sort((a, b) => a - b);
            },

            areAllTagQuestionStripsExpanded() {
                const all = (this.allQuestionIndexes || [])
                    .map((value) => Number.parseInt(value, 10))
                    .filter((value) => Number.isFinite(value))
                    .sort((a, b) => a - b);
                if (!all.length) return false;

                const expanded = new Set((this.expandedTagQuestionIndexes || [])
                    .map((value) => Number.parseInt(value, 10))
                    .filter((value) => Number.isFinite(value)));

                return all.every((index) => expanded.has(index));
            },

            toggleAllTagQuestionStripsExpanded() {
                if (this.areAllTagQuestionStripsExpanded()) {
                    this.expandedTagQuestionIndexes = [];
                    return;
                }

                this.expandedTagQuestionIndexes = (this.allQuestionIndexes || [])
                    .map((value) => Number.parseInt(value, 10))
                    .filter((value) => Number.isFinite(value))
                    .sort((a, b) => a - b);
            },

            syncQuestionTagModalLayerState() {
                if (typeof document === 'undefined' || !document.body) return;
                const hasOpenTagModal = !!(this.questionTagModalOpen || this.defineTagModalOpen);
                document.body.classList.toggle('quiz-tag-modal-open', hasOpenTagModal);
            },

            openQuestionTagModal(targetPage = null) {
                this.questionTagModalOpen = true;
                this.expandedTagQuestionIndexes = [];
                this.defineTagFocusedTagId = null;
                this.defineTagFocusedQuestionIndex = null;
                this.tagModalCurrentPage = this.normalizeTagModalTargetPage(targetPage ?? this.currentPage);
                this.tagPagePickerOpen = false;
                this.syncQuestionTagModalLayerState();
                this.$nextTick(() => {
                    this.scrollToTagQuestionPage(this.tagModalCurrentPage, true);
                    this.onTagQuestionScroll();
                });
            },

            closeQuestionTagModal() {
                this.questionTagModalOpen = false;
                this.defineTagModalOpen = false;
                this.defineTagCategoryMode = false;
                this.defineTagRelevanceMode = false;
                this.defineTagFocusedTagId = null;
                this.defineTagFocusedQuestionIndex = null;
                this.expandedTagQuestionIndexes = [];
                this.tagPagePickerOpen = false;
                this.syncQuestionTagModalLayerState();
            },

            setQuestionSelectedForTag(index, checked) {
                const idx = Number.parseInt(index, 10);
                if (!Number.isFinite(idx) || idx < 0) return;

                const set = new Set((this.selectedTagQuestionIndexes || []).map((value) => Number.parseInt(value, 10)).filter((value) => Number.isFinite(value)));
                if (checked) {
                    set.add(idx);
                } else {
                    set.delete(idx);
                }
                this.selectedTagQuestionIndexes = Array.from(set).sort((a, b) => a - b);
            },

            isQuestionSelectedForTag(index) {
                const idx = Number.parseInt(index, 10);
                if (!Number.isFinite(idx)) return false;
                return (this.selectedTagQuestionIndexes || []).map((value) => Number.parseInt(value, 10)).includes(idx);
            },

            areAllQuestionsSelectedForTag() {
                const all = (this.allQuestionIndexes || [])
                    .map((value) => Number.parseInt(value, 10))
                    .filter((value) => Number.isFinite(value))
                    .sort((a, b) => a - b);
                if (!all.length) return false;

                const selected = new Set((this.selectedTagQuestionIndexes || [])
                    .map((value) => Number.parseInt(value, 10))
                    .filter((value) => Number.isFinite(value)));

                return all.every((idx) => selected.has(idx));
            },

            selectAllQuestionsForTag() {
                if (this.areAllQuestionsSelectedForTag()) {
                    this.selectedTagQuestionIndexes = [];
                    return;
                }

                this.selectedTagQuestionIndexes = (this.allQuestionIndexes || [])
                    .map((value) => Number.parseInt(value, 10))
                    .filter((value) => Number.isFinite(value))
                    .sort((a, b) => a - b);
            },

            toggleQuestionExpandedForTag(index) {
                const idx = Number.parseInt(index, 10);
                if (!Number.isFinite(idx) || idx < 0) return;
                const set = new Set((this.expandedTagQuestionIndexes || []).map((value) => Number.parseInt(value, 10)).filter((value) => Number.isFinite(value)));
                if (set.has(idx)) {
                    set.delete(idx);
                } else {
                    set.add(idx);
                }
                this.expandedTagQuestionIndexes = Array.from(set).sort((a, b) => a - b);
            },

            isQuestionExpandedForTag(index) {
                const idx = Number.parseInt(index, 10);
                if (!Number.isFinite(idx)) return false;
                return (this.expandedTagQuestionIndexes || []).map((value) => Number.parseInt(value, 10)).includes(idx);
            },

            toggleQuestionTagLabels(index) {
                const idx = Number.parseInt(index, 10);
                if (!Number.isFinite(idx) || idx < 0) return;
                const set = new Set((this.expandedQuestionTagLabelIndexes || [])
                    .map((value) => Number.parseInt(value, 10))
                    .filter((value) => Number.isFinite(value)));
                if (set.has(idx)) {
                    set.delete(idx);
                } else {
                    set.add(idx);
                }
                this.expandedQuestionTagLabelIndexes = Array.from(set).sort((a, b) => a - b);
            },

            isQuestionTagLabelsOpen(index) {
                const idx = Number.parseInt(index, 10);
                if (!Number.isFinite(idx)) return false;
                return (this.expandedQuestionTagLabelIndexes || [])
                    .map((value) => Number.parseInt(value, 10))
                    .includes(idx);
            },

            getDefineTagCategories() {
                const categories = new Set();
                (this.availableTags || []).forEach((tag) => {
                    const category = String(tag?.category ?? '').trim();
                    if (category) categories.add(category);
                });
                return Array.from(categories).sort((a, b) => a.localeCompare(b));
            },

            getDefineTagCategoryLabel() {
                return this.defineTagCurrentCategory || 'All categories';
            },

            getDefineTagFilteredTags() {
                const query = String(this.defineTagSearch ?? '').trim().toLowerCase();
                const selectedCategory = String(this.defineTagCurrentCategory ?? '').trim().toLowerCase();
                const contextQuestionIndex = this.getDefineTagMetricContextQuestionIndex();
                const filtered = (this.availableTags || []).filter((tag) => {
                    const tagName = String(tag?.name ?? '').toLowerCase();
                    const tagCategory = String(tag?.category ?? '').trim();
                    const tagCategoryLower = tagCategory.toLowerCase();

                    if (selectedCategory && tagCategoryLower !== selectedCategory) {
                        return false;
                    }

                    if (!query) return true;
                    return tagName.includes(query) || tagCategoryLower.includes(query);
                });

                const sorted = [...filtered].sort((a, b) => {
                    const aId = String(a?.id ?? '').trim();
                    const bId = String(b?.id ?? '').trim();
                    const aMetric = this.resolveTagMetric(aId, contextQuestionIndex);
                    const bMetric = this.resolveTagMetric(bId, contextQuestionIndex);

                    const aRank = relevanceSortRank[aMetric.relevance] ?? relevanceSortRank.medium;
                    const bRank = relevanceSortRank[bMetric.relevance] ?? relevanceSortRank.medium;
                    if (bRank !== aRank) {
                        return bRank - aRank;
                    }

                    return String(a?.name ?? '').localeCompare(String(b?.name ?? ''));
                });

                return sorted;
            },

            getDefineTagTotalPages() {
                const total = Math.ceil(this.getDefineTagFilteredTags().length / 6);
                return Number.isFinite(total) && total > 0 ? total : 0;
            },

            getDefineTagCurrentPage() {
                const totalPages = this.getDefineTagTotalPages();
                if (totalPages <= 0) return 1;
                const requested = Number.parseInt(this.defineTagCurrentTagPage, 10);
                if (!Number.isFinite(requested)) return 1;
                return Math.min(totalPages, Math.max(1, requested));
            },

            getDefineTagVisibleTags() {
                const page = this.getDefineTagCurrentPage();
                const start = (page - 1) * 6;
                return this.getDefineTagFilteredTags().slice(start, start + 6);
            },

            prevDefineTagPage() {
                const page = this.getDefineTagCurrentPage();
                if (page <= 1) return;
                this.defineTagCurrentTagPage = page - 1;
            },

            nextDefineTagPage() {
                const page = this.getDefineTagCurrentPage();
                const totalPages = this.getDefineTagTotalPages();
                if (page >= totalPages) return;
                this.defineTagCurrentTagPage = page + 1;
            },

            clearDefineTagSelection() {
                this.defineTagSelection = [];
                this.defineTagFocusedTagId = null;
            },

            toggleDefineTagRelevanceMode() {
                if (this.defineTagRelevanceMode) {
                    this.closeDefineTagRelevanceMode();
                    return;
                }

                this.defineTagFocusedTagId = null;
                this.defineTagCategoryMode = false;
                this.defineTagRelevanceMode = true;
                this.defineTagCurrentRelevancePage = 1;
            },

            closeDefineTagRelevanceMode() {
                this.defineTagRelevanceMode = false;
                this.defineTagCurrentTagPage = 1;
                this.defineTagFocusedTagId = null;
            },

            handleDefineTagBack() {
                if (this.defineTagRelevanceMode) {
                    this.closeDefineTagRelevanceMode();
                    return;
                }

                if (this.defineTagCategoryMode) {
                    this.closeDefineTagCategoryMode();
                }
            },

            getDefineTagRelevanceTagIds() {
                const selectedIds = normalizeTagIds(this.defineTagSelection);
                const contextQuestionIndex = this.getDefineTagMetricContextQuestionIndex();
                const query = String(this.defineTagSearch ?? '').trim().toLowerCase();
                const focusedTagId = String(this.defineTagFocusedTagId ?? '').trim();
                const relevanceIds = (focusedTagId && selectedIds.includes(focusedTagId))
                    ? [focusedTagId]
                    : selectedIds;

                const matching = relevanceIds.filter((tagId) => {
                    const name = String(this.tagName(tagId) ?? '').toLowerCase();
                    const category = String(this.tagCategory(tagId) ?? '').toLowerCase();
                    if (!query) return true;
                    return name.includes(query) || category.includes(query);
                });

                return matching.sort((a, b) => {
                    const aMetric = this.resolveTagMetric(a, contextQuestionIndex);
                    const bMetric = this.resolveTagMetric(b, contextQuestionIndex);
                    const aRank = relevanceSortRank[aMetric.relevance] ?? relevanceSortRank.medium;
                    const bRank = relevanceSortRank[bMetric.relevance] ?? relevanceSortRank.medium;
                    if (bRank !== aRank) return bRank - aRank;
                    return this.tagName(a).localeCompare(this.tagName(b));
                });
            },

            getDefineTagRelevanceTotalPages() {
                const total = Math.ceil(this.getDefineTagRelevanceTagIds().length / 6);
                return Number.isFinite(total) && total > 0 ? total : 0;
            },

            getDefineTagCurrentRelevancePage() {
                const totalPages = this.getDefineTagRelevanceTotalPages();
                if (totalPages <= 0) return 1;
                const requested = Number.parseInt(this.defineTagCurrentRelevancePage, 10);
                if (!Number.isFinite(requested)) return 1;
                return Math.min(totalPages, Math.max(1, requested));
            },

            getDefineTagRelevanceVisibleTagIds() {
                const page = this.getDefineTagCurrentRelevancePage();
                const start = (page - 1) * 6;
                return this.getDefineTagRelevanceTagIds().slice(start, start + 6);
            },

            prevDefineTagRelevancePage() {
                const page = this.getDefineTagCurrentRelevancePage();
                if (page <= 1) return;
                this.defineTagCurrentRelevancePage = page - 1;
            },

            nextDefineTagRelevancePage() {
                const page = this.getDefineTagCurrentRelevancePage();
                const totalPages = this.getDefineTagRelevanceTotalPages();
                if (page >= totalPages) return;
                this.defineTagCurrentRelevancePage = page + 1;
            },

            getTagRelevanceIndex(tagId) {
                const contextQuestionIndex = this.getDefineTagMetricContextQuestionIndex();
                const relevance = this.resolveTagMetric(tagId, contextQuestionIndex).relevance;
                const idx = relevanceScale.indexOf(relevance);
                return idx >= 0 ? idx : relevanceScale.indexOf(defaultRelevanceValue);
            },

            setTagRelevanceByIndex(tagId, sliderValue) {
                const key = String(tagId ?? '').trim();
                if (!key) return;

                const index = Math.max(0, Math.min(relevanceScale.length - 1, Number.parseInt(sliderValue, 10) || 0));
                const nextRelevance = normalizeRelevance(relevanceScale[index]);
                const targetIndexes = (Array.isArray(this.selectedTagQuestionIndexes) ? this.selectedTagQuestionIndexes : [])
                    .map((value) => Number.parseInt(value, 10))
                    .filter((value) => Number.isFinite(value) && value >= 0 && this.questions?.[value]);

                targetIndexes.forEach((questionIndex) => {
                    const question = this.questions?.[questionIndex];
                    if (!question) return;
                    const rawMap = (question.tag_relevance && typeof question.tag_relevance === 'object' && !Array.isArray(question.tag_relevance))
                        ? question.tag_relevance
                        : {};
                    question.tag_relevance = {
                        ...rawMap,
                        [key]: nextRelevance,
                    };
                });
            },

            toggleDefineTagCategoryMode() {
                if (this.defineTagCategoryMode) {
                    this.closeDefineTagCategoryMode();
                    return;
                }

                this.defineTagRelevanceMode = false;
                this.defineTagFocusedTagId = null;
                this.defineTagCategoryMode = true;
                this.defineTagCurrentCategoryPage = 1;
            },

            closeDefineTagCategoryMode() {
                this.defineTagCategoryMode = false;
                this.defineTagCurrentTagPage = 1;
            },

            onDefineTagSearchInput() {
                if (this.defineTagCategoryMode) {
                    this.defineTagCurrentCategoryPage = 1;
                    return;
                }
                if (this.defineTagRelevanceMode) {
                    this.defineTagCurrentRelevancePage = 1;
                    return;
                }
                this.defineTagCurrentTagPage = 1;
            },

            getDefineTagFilteredCategories() {
                const query = String(this.defineTagSearch ?? '').trim().toLowerCase();
                const allCategories = this.getDefineTagCategories();
                if (!query) return allCategories;
                return allCategories.filter((category) => category.toLowerCase().includes(query));
            },

            getDefineTagCategoryTotalPages() {
                const total = Math.ceil(this.getDefineTagFilteredCategories().length / 6);
                return Number.isFinite(total) && total > 0 ? total : 0;
            },

            getDefineTagCurrentCategoryPage() {
                const totalPages = this.getDefineTagCategoryTotalPages();
                if (totalPages <= 0) return 1;
                const requested = Number.parseInt(this.defineTagCurrentCategoryPage, 10);
                if (!Number.isFinite(requested)) return 1;
                return Math.min(totalPages, Math.max(1, requested));
            },

            getDefineTagVisibleCategories() {
                const page = this.getDefineTagCurrentCategoryPage();
                const start = (page - 1) * 6;
                return this.getDefineTagFilteredCategories().slice(start, start + 6);
            },

            prevDefineTagCategoryPage() {
                const page = this.getDefineTagCurrentCategoryPage();
                if (page <= 1) return;
                this.defineTagCurrentCategoryPage = page - 1;
            },

            nextDefineTagCategoryPage() {
                const page = this.getDefineTagCurrentCategoryPage();
                const totalPages = this.getDefineTagCategoryTotalPages();
                if (page >= totalPages) return;
                this.defineTagCurrentCategoryPage = page + 1;
            },

            selectDefineTagCategory(category) {
                this.defineTagCurrentCategory = String(category ?? '').trim();
                this.defineTagCategoryMode = false;
                this.defineTagCurrentTagPage = 1;
            },

            openDefineTagModal(options = {}) {
                const requestedFocusQuestion = Number.parseInt(options?.focusQuestionIndex, 10);
                let indexes = (Array.isArray(this.selectedTagQuestionIndexes) ? this.selectedTagQuestionIndexes : [])
                    .map((value) => Number.parseInt(value, 10))
                    .filter((value) => Number.isFinite(value) && value >= 0 && this.questions?.[value]);

                if (Number.isFinite(requestedFocusQuestion) && requestedFocusQuestion >= 0 && this.questions?.[requestedFocusQuestion]) {
                    if (!indexes.includes(requestedFocusQuestion)) {
                        indexes = [requestedFocusQuestion];
                    }
                }

                indexes = Array.from(new Set(indexes)).sort((a, b) => a - b);
                if (!indexes.length) {
                    return;
                }

                this.selectedTagQuestionIndexes = indexes;
                const focusQuestionIndex = Number.isFinite(requestedFocusQuestion) && indexes.includes(requestedFocusQuestion)
                    ? requestedFocusQuestion
                    : indexes[0];
                this.defineTagFocusedQuestionIndex = focusQuestionIndex;

                const focusQuestion = this.questions?.[focusQuestionIndex];
                this.normalizeQuestionTagRelevance(focusQuestion);
                this.defineTagSelection = normalizeTagIds(focusQuestion?.tag_ids);
                this.defineTagFocusedTagId = null;
                const focusTagId = String(options?.focusTagId ?? '').trim();
                if (focusTagId && this.defineTagSelection.includes(focusTagId)) {
                    this.defineTagFocusedTagId = focusTagId;
                }
                this.defineTagSearch = '';
                this.defineTagCategoryMode = false;
                this.defineTagRelevanceMode = !!options?.openRelevance;
                this.defineTagCurrentTagPage = 1;
                this.defineTagCurrentCategoryPage = 1;
                this.defineTagCurrentRelevancePage = 1;
                this.defineTagModalOpen = true;
                this.syncQuestionTagModalLayerState();
            },

            openDefineTagRelevanceForQuestionTag(questionIndex, tagId) {
                const idx = Number.parseInt(questionIndex, 10);
                const key = String(tagId ?? '').trim();
                if (!key || !Number.isFinite(idx) || idx < 0 || !this.questions?.[idx]) return;

                this.selectedTagQuestionIndexes = [idx];
                this.openDefineTagModal({
                    focusQuestionIndex: idx,
                    focusTagId: key,
                    openRelevance: true,
                });
            },

            closeDefineTagModal() {
                this.defineTagModalOpen = false;
                this.defineTagCategoryMode = false;
                this.defineTagRelevanceMode = false;
                this.defineTagFocusedTagId = null;
                this.defineTagFocusedQuestionIndex = null;
                this.syncQuestionTagModalLayerState();
            },

            applyDefinedTagsToSelectedQuestions() {
                const nextTagIds = normalizeTagIds(this.defineTagSelection);
                const indexes = Array.isArray(this.selectedTagQuestionIndexes) ? this.selectedTagQuestionIndexes : [];
                indexes.forEach((index) => {
                    const idx = Number.parseInt(index, 10);
                    if (!Number.isFinite(idx) || idx < 0 || !this.questions?.[idx]) return;
                    const question = this.questions[idx];
                    const rawRelevance = (question.tag_relevance && typeof question.tag_relevance === 'object' && !Array.isArray(question.tag_relevance))
                        ? { ...question.tag_relevance }
                        : {};
                    const normalizedCurrent = this.normalizeQuestionTagRelevance(question);
                    const currentRelevance = { ...normalizedCurrent, ...rawRelevance };
                    const nextRelevance = {};
                    nextTagIds.forEach((tagId) => {
                        if (Object.prototype.hasOwnProperty.call(currentRelevance, tagId)) {
                            nextRelevance[tagId] = normalizeRelevance(currentRelevance[tagId]);
                        } else {
                            nextRelevance[tagId] = normalizeRelevance((this.tagMetrics?.[tagId] || {}).relevance);
                        }
                    });
                    this.questions[idx].tag_ids = [...nextTagIds];
                    this.questions[idx].tag_relevance = nextRelevance;
                });

                this.refreshTagCoverageMetrics();
                this.defineTagModalOpen = false;
                this.defineTagFocusedTagId = null;
                this.defineTagFocusedQuestionIndex = null;
                this.syncQuestionTagModalLayerState();
            },

                validateBeforeSubmit() {
                    const nextErrors = {};
                    const nextGeneralErrors = [];

                    const addQuestionError = (index, field, message) => {
                        if (!Number.isFinite(index) || index < 0) return;
                        if (!nextErrors[index]) nextErrors[index] = { messages: [], fields: [] };
                        if (message && !nextErrors[index].messages.includes(message)) {
                            nextErrors[index].messages.push(message);
                        }
                        if (field && !nextErrors[index].fields.includes(field)) {
                            nextErrors[index].fields.push(field);
                        }
                    };

                    const normalize = (value) => String(value ?? '').trim();
                    const normalizeLower = (value) => normalize(value).toLowerCase();

                    const list = Array.isArray(this.questions) ? this.questions : [];
                    if (list.length < 1) {
                        nextGeneralErrors.push('Please add at least 1 question.');
                    }

                    list.forEach((question, index) => {
                        if (!question) {
                            addQuestionError(index, 'text', 'Please enter a question.');
                            return;
                        }

                        const type = String(question.type || 'multiple_choice');
                        const text = normalize(question.text);

                        if (!text) {
                            addQuestionError(index, 'text', 'Please enter a question.');
                        }

                        if (type === 'multiple_choice') {
                            const optionsRaw = Array.isArray(question.options) ? question.options : [];
                            const optionsFilled = optionsRaw
                                .map((opt, optIndex) => ({ value: normalize(opt), rawIndex: optIndex }))
                                .filter((opt) => !!opt.value);

                            if (optionsFilled.length < 2) {
                                addQuestionError(index, 'option', 'Multiple choice needs at least 2 options.');
                            }

                            const seenOptions = new Set();
                            const hasDuplicateOptions = optionsFilled.some((opt) => {
                                const key = normalizeLower(opt.value);
                                if (!key) return false;
                                if (seenOptions.has(key)) return true;
                                seenOptions.add(key);
                                return false;
                            });
                            if (hasDuplicateOptions) {
                                addQuestionError(index, 'option', 'Multiple choice options cannot be the same.');
                            }

                            const selectedRaw = Array.isArray(question.answer_key)
                                ? question.answer_key
                                : (question.answer_key === null || question.answer_key === undefined || question.answer_key === '')
                                    ? []
                                    : [question.answer_key];
                            const selected = selectedRaw.map((v) => String(v).trim()).filter(Boolean);
                            if (selected.length < 1) {
                                addQuestionError(index, 'answer_key', 'Please select at least 1 correct answer.');
                            } else {
                                const validIndexes = new Set(optionsFilled.map((opt) => String(opt.rawIndex)));
                                const hasInvalidSelection = selected.some((raw) => !validIndexes.has(String(parseInt(raw, 10))));
                                if (hasInvalidSelection) {
                                    addQuestionError(index, 'answer_key', 'Selected answer is invalid.');
                                }
                            }
                        } else if (type === 'true_false') {
                            if (!['true', 'false'].includes(String(question.answer_key_bool ?? ''))) {
                                addQuestionError(index, 'answer_key', 'Please choose True or False.');
                            }
                        } else if (type === 'numerical') {
                            const numericalAnswer = normalize(question.answer_key_num);
                            if (!numericalAnswer) {
                                addQuestionError(index, 'answer_key', 'Please enter a numerical answer.');
                            } else if (Number.isNaN(Number(numericalAnswer))) {
                                addQuestionError(index, 'answer_key', 'Numerical answer must be a number.');
                            }
                        } else if (type === 'matching') {
                            this.ensureMatchingArrays(question);

                            const leftRaw = Array.isArray(question.match_left) ? question.match_left : [];
                            const rightRaw = Array.isArray(question.match_right) ? question.match_right : [];

                            const leftFilled = leftRaw
                                .map((item, rowIndex) => ({ value: normalize(item), rowIndex }))
                                .filter((item) => !!item.value);
                            const rightFilled = rightRaw
                                .map((item, colIndex) => ({ value: normalize(item), colIndex }))
                                .filter((item) => !!item.value);

                            if (leftFilled.length < 1) {
                                addQuestionError(index, 'match_left', 'Matching needs at least 1 left item.');
                            }
                            if (rightFilled.length < 1) {
                                addQuestionError(index, 'match_right', 'Matching needs at least 1 right item.');
                            }

                            const leftSeen = new Set();
                            const hasDuplicateLeft = leftFilled.some((entry) => {
                                const key = normalizeLower(entry.value);
                                if (!key) return false;
                                if (leftSeen.has(key)) return true;
                                leftSeen.add(key);
                                return false;
                            });
                            if (hasDuplicateLeft) {
                                addQuestionError(index, 'match_left', 'Matching left items cannot be the same.');
                            }

                            const rightSeen = new Set();
                            const hasDuplicateRight = rightFilled.some((entry) => {
                                const key = normalizeLower(entry.value);
                                if (!key) return false;
                                if (rightSeen.has(key)) return true;
                                rightSeen.add(key);
                                return false;
                            });
                            if (hasDuplicateRight) {
                                addQuestionError(index, 'match_right', 'Matching right items cannot be the same.');
                            }

                            const matchMap = (question.match_map && typeof question.match_map === 'object') ? question.match_map : {};
                            const selectedCount = Object.keys(matchMap).reduce((count, key) => {
                                const rowIndex = parseInt(key, 10);
                                const colIndex = parseInt(matchMap[key], 10);
                                if (!Number.isFinite(rowIndex) || !Number.isFinite(colIndex)) return count;
                                const leftText = normalize(leftRaw[rowIndex]);
                                const rightText = normalize(rightRaw[colIndex]);
                                if (!leftText || !rightText) return count;
                                return count + 1;
                            }, 0);
                            if (selectedCount < 1) {
                                addQuestionError(index, 'answer_key', 'Please select at least 1 match.');
                            }
                        } else if (type === 'short_answer') {
                            if (!normalize(question.answer_key_text)) {
                                addQuestionError(index, 'answer_key', 'Please enter the answer key text.');
                            }
                        }
                    });

                    this.errorsByIndex = nextErrors;
                    this.generalErrors = nextGeneralErrors;

                    const errorIndexes = Object.keys(nextErrors)
                        .map((k) => parseInt(k, 10))
                        .filter((n) => Number.isFinite(n))
                        .sort((a, b) => a - b);

                    if (!errorIndexes.length && !nextGeneralErrors.length) {
                        this.clientErrorsVisible = false;
                        return true;
                    }

                    this.clientErrorsVisible = true;

                    if (errorIndexes.length) {
                        const firstQuestion = this.questions?.[errorIndexes[0]];
                        if (firstQuestion) {
                            const targetPage = Math.min(10, Math.max(1, this.pageOfOrder(firstQuestion.order)));
                            this.currentPage = targetPage;
                        } else {
                            this.currentPage = 1;
                        }

                        errorIndexes.forEach((idx) => {
                            const q = this.questions?.[idx];
                            if (!q) return;
                            if (this.questionHasFieldError(idx, ['answer_key', 'option', 'match_left', 'match_right', 'pairs', 'match_map'])) {
                                q.showAnswerKey = true;
                            }
                        });
                    } else {
                        this.currentPage = 1;
                    }

                    return false;
                },

                goToPage(p) {
                    const n = parseInt(p, 10);
                    if (!Number.isFinite(n)) return;
                    this.currentPage = Math.min(Math.max(1, n), this.totalPages);
                },

                prevPage() {
                    this.goToPage(this.currentPage - 1);
                },

                nextPage() {
                    this.goToPage(this.currentPage + 1);
                },

                normalizeDupKey(val) {
                    const s = String(val ?? '').trim();
                    if (!s) return '';
                    return s.toLowerCase();
                },

                isDuplicateInArray(arr, idx) {
                    if (!Array.isArray(arr)) return false;
                    if (idx < 0 || idx >= arr.length) return false;
                    const key = this.normalizeDupKey(arr[idx]);
                    if (!key) return false;
                    let count = 0;
                    for (let i = 0; i < arr.length; i++) {
                        const k = this.normalizeDupKey(arr[i]);
                        if (!k) continue;
                        if (k === key) count++;
                        if (count > 1) return true;
                    }
                    return false;
                },

                isDuplicateOption(question, optIndex) {
                    return this.isDuplicateInArray(question?.options, optIndex);
                },

                isDuplicateMatchingLeft(question, rowIndex) {
                    return this.isDuplicateInArray(question?.match_left, rowIndex);
                },

                isDuplicateMatchingRight(question, colIndex) {
                    return this.isDuplicateInArray(question?.match_right, colIndex);
                },

                normalizePointValue(val) {
                    const n = parseInt(val, 10);
                    return Number.isFinite(n) && n >= 0 ? n : 0;
                },

                ensurePairPointsLength(question) {
                    if (!question) return;
                    if (!Array.isArray(question.pair_points)) question.pair_points = [];
                    const needed = Array.isArray(question.match_left) ? question.match_left.length : 0;
                    if (!needed) {
                        question.pair_points = [];
                        return;
                    }
                    while (question.pair_points.length < needed) {
                        question.pair_points.push(this.normalizePointValue(question.points ?? 1));
                    }
                    if (question.pair_points.length > needed) {
                        question.pair_points.length = needed;
                    }
                    question.pair_points = question.pair_points.map(v => this.normalizePointValue(v));
                },

                clampPageSize(val) {
                    const n = parseInt(val, 10);
                    if (!Number.isFinite(n)) return this.pageSize || 10;
                    if (n < 1) return 1;
                    if (n > 25) return 25;
                    return n;
                },

                getPageForQuestion(q) {
                    const hinted = parseInt(q?.page_hint, 10);
                    if (Number.isFinite(hinted) && hinted >= 1) return hinted;
                    return this.pageOfOrder(q?.order);
                },

                onPageSizeChange(val) {
                    const next = this.clampPageSize(val);
                    if (next === this.pageSize) return;
                    this.pageSize = next;
                    this.reflowOrdersByPage();
                    this.goToPage(1);
                },

                reflowOrdersByPage() {
                    const perPage = this.pageSize || 10;
                    if (!Array.isArray(this.questions)) return;

                    const buckets = {};
                    (this.questions || []).forEach((q) => {
                        const page = this.getPageForQuestion(q);
                        buckets[page] ??= [];
                        buckets[page].push(q);
                    });

                    Object.keys(buckets).forEach((pStr) => {
                        const pageNum = parseInt(pStr, 10) || 1;
                        const start = ((pageNum - 1) * perPage) + 1;
                        buckets[pStr]
                            .sort((a, b) => (parseInt(a?.order, 10) || 0) - (parseInt(b?.order, 10) || 0))
                            .forEach((q, idx) => {
                                if (q) {
                                    q.order = start + idx;
                                    q.page_hint = pageNum;
                                }
                            });
                    });

                    this.questions = [...this.questions].sort((a, b) => (parseInt(a?.order, 10) || 0) - (parseInt(b?.order, 10) || 0));
                },

                effectivePoints(question) {
                    if (!question) return 0;
                    if (question.type === 'matching') {
                        if (question.use_pair_points && Array.isArray(question.pair_points)) {
                            const sum = question.pair_points.reduce((s, v) => {
                                const n = parseInt(v, 10);
                                return s + (Number.isFinite(n) && n >= 0 ? n : 0);
                            }, 0);
                            if (sum > 0) return sum;
                        }
                    }
                    const pts = parseInt(question.points, 10);
                    return Number.isFinite(pts) && pts >= 0 ? pts : 1;
                },

                questions: (Array.isArray(initialList) && initialList.length)
                    ? initialList.map((q, i) => normalizeQuestion(q, i))
                : [{
                    id: null,
                    order: 1,
                    text: '',
                    type: 'multiple_choice',
                        options: [''],
                        answer_key: [],
                        match_left: [''],
                        match_right: [''],
                        match_map: {},
                        pair_points: [1],
                        use_pair_points: false,
                        points: 1,
                        required: false,
                        showAnswerKey: false,
                        tag_ids: [],
                        tag_relevance: {},
                        answer_key_text: '',
                    answer_key_bool: '',
                    answer_key_num: '',
                }],

                allQuestionsRequired() {
                    return Array.isArray(this.questions)
                        && this.questions.length > 0
                        && this.questions.every(q => !!(q && q.required));
                },

                toggleRequireAll() {
                    const makeRequired = !this.allQuestionsRequired();
                    (this.questions || []).forEach((q) => {
                        if (q) q.required = makeRequired;
                    });
                },

                init() {
                    window.addEventListener('keydown', (e) => {
                        const isUndo = (e.ctrlKey || e.metaKey) && (String(e.key).toLowerCase() === 'z');
                        if (!isUndo) return;

                        const active = document.activeElement;
                        const tag = active?.tagName?.toLowerCase();
                        const isTyping = tag === 'input' || tag === 'textarea' || active?.isContentEditable;
                        if (isTyping) return;

                        if (this.lastDeletedOption || (this.toastVisible && this.lastDeleted)) {
                            e.preventDefault();
                            this.undoDelete();
                        }
                    });

                    // After validation redirects, old() can contain empty/invalid order values.
                    // Ensure at least one page renders instead of showing a blank state.
                    if (Array.isArray(this.questions)) {
                        this.questions.forEach((qq, idx) => {
                            const o = parseInt(qq?.order, 10);
                            if (!Number.isFinite(o) || o < 1) {
                                if (this.questions[idx]) this.questions[idx].order = idx + 1;
                            }

                            if (qq?.type === 'matching') {
                                this.ensureMatchingArrays(qq);
                            }
                            this.normalizeQuestionTagRelevance(qq);
                        });

                        if (this.visibleQuestionIndexes.length === 0 && this.questions.length > 0) {
                            const firstOrder = parseInt(this.questions[0]?.order, 10);
                            const targetPage = this.pageOfOrder(Number.isFinite(firstOrder) ? firstOrder : 1);
                            this.goToPage(targetPage);
                        }
                    }
                    this.refreshTagCoverageMetrics();
                    this.syncQuestionTagModalLayerState();

                    const errorIndexes = Object.keys(this.errorsByIndex || {})
                        .map((k) => parseInt(k, 10))
                        .filter((n) => Number.isFinite(n))
                        .sort((a, b) => a - b);

                    if (errorIndexes.length) {
                        const firstIndex = errorIndexes[0];
                        const firstQuestion = this.questions?.[firstIndex];
                        if (firstQuestion) {
                            const targetPage = Math.min(10, Math.max(1, this.pageOfOrder(firstQuestion.order)));
                            this.currentPage = targetPage;
                        }

                        errorIndexes.forEach((idx) => {
                            const q = this.questions?.[idx];
                            if (!q) return;
                            if (this.questionHasFieldError(idx, ['answer_key', 'option', 'match_left', 'match_right', 'pairs', 'match_map'])) {
                                q.showAnswerKey = true;
                            }
                        });
                    }
                },

                showToast(kind) {
                    this.toastKind = kind;

                    if (this.toastTimer) {
                        clearTimeout(this.toastTimer);
                        this.toastTimer = null;
                    }

                    this.toastVisible = false;
                    this.$nextTick(() => {
                        this.toastVisible = true;
                    });

                    this.toastTimer = setTimeout(() => {
                        this.toastVisible = false;
                        this.toastTimer = null;
                    }, 7500);
                },

                addQuestion() {
                    if (this.isPageFull(this.currentPage)) return;
                    const nextOrder = this.nextAvailableOrderForPage(this.currentPage);
                    if (!nextOrder) return;
                    const q = normalizeQuestion({
                        order: nextOrder,
                        type: this.defaultQuestionType || 'multiple_choice',
                        required: this.allQuestionsRequired(),
                        page_hint: this.currentPage,
                    });
                    if (q.type === 'matching') {
                        this.ensureMatchingArrays(q);
                    }
                    this.questions.push(q);
                    this.refreshTagCoverageMetrics();
                },

                openQuestionBankModal() {
                    this.questionBankModalOpen = true;
                    this.questionBankModalError = '';
                    if (!this.questionBankItems.length) {
                        this.fetchQuestionBankItems();
                    }
                },

                closeQuestionBankModal() {
                    this.questionBankModalOpen = false;
                    this.questionBankModalError = '';
                    this.questionBankImporting = false;
                },

                resetQuestionBankFilters() {
                    this.questionBankFilters = { search: '', type: '' };
                    this.fetchQuestionBankItems();
                },

                clearQuestionBankSelection() {
                    this.questionBankSelectedIds = [];
                },

                toggleQuestionBankSelection(itemId) {
                    const key = String(itemId ?? '').trim();
                    if (!key) return;
                    if (this.questionBankSelectedIds.includes(key)) {
                        this.questionBankSelectedIds = this.questionBankSelectedIds.filter((value) => value !== key);
                        return;
                    }
                    this.questionBankSelectedIds = [...this.questionBankSelectedIds, key];
                },

                questionBankItemRoute(template, itemId) {
                    return String(template || '').replace('__ITEM__', String(itemId ?? '').trim());
                },

                questionBankTypeLabel(type) {
                    const labels = {
                        multiple_choice: 'Multiple Choice',
                        short_answer: 'Short Answer',
                        true_false: 'True / False',
                        matching: 'Matching',
                        essay: 'Essay',
                        numerical: 'Numerical',
                    };
                    return labels[type] || String(type ?? 'Question');
                },

                questionBankItemMetaLabel(item) {
                    const parts = [
                        this.questionBankTypeLabel(item?.type || ''),
                        item?.scope === 'section' ? 'Section' : 'Subject',
                    ];

                    return parts.filter(Boolean).join(' • ');
                },

                async fetchQuestionBankItems() {
                    if (!this.questionBankRoutes?.index) {
                        this.questionBankModalError = 'Question bank routes are unavailable for this quiz.';
                        return;
                    }

                    this.questionBankLoading = true;
                    this.questionBankModalError = '';

                    try {
                        const params = new URLSearchParams({
                            all: '1',
                            search: this.questionBankFilters.search || '',
                            type: this.questionBankFilters.type || '',
                            scope: 'all',
                            state: 'active',
                        });

                        const response = await fetch(`${this.questionBankRoutes.index}?${params.toString()}`, {
                            headers: { Accept: 'application/json' },
                        });
                        if (!response.ok) {
                            throw new Error('Unable to load question bank items.');
                        }

                        const payload = await response.json();
                        this.questionBankItems = Array.isArray(payload.data) ? payload.data : [];
                        this.questionBankMeta = {
                            total: payload.meta?.total || 0,
                        };
                    } catch (error) {
                        this.questionBankModalError = error.message || 'Unable to load question bank items.';
                    } finally {
                        this.questionBankLoading = false;
                    }
                },

                nextQuestionImportOrder(offset = 0) {
                    const currentMax = (Array.isArray(this.questions) ? this.questions : [])
                        .map((question) => parseInt(question?.order, 10))
                        .filter((value) => Number.isFinite(value) && value >= 1)
                        .reduce((max, value) => Math.max(max, value), 0);
                    return currentMax + 1 + offset;
                },

                async importSelectedQuestionBankItems() {
                    if (!this.questionBankSelectedIds.length) {
                        return;
                    }

                    const maxQuestions = (this.pageSize || 10) * 10;
                    const currentCount = Array.isArray(this.questions) ? this.questions.length : 0;
                    if ((currentCount + this.questionBankSelectedIds.length) > maxQuestions) {
                        this.questionBankModalError = `Only ${maxQuestions} questions fit in this quiz with the current pagination.`;
                        return;
                    }

                    this.questionBankImporting = true;
                    this.questionBankModalError = '';

                    try {
                        const importedQuestions = [];
                        for (const itemId of this.questionBankSelectedIds) {
                            const response = await fetch(this.questionBankItemRoute(this.questionBankRoutes.show, itemId), {
                                headers: { Accept: 'application/json' },
                            });
                            if (!response.ok) {
                                throw new Error('Unable to load one of the selected saved questions.');
                            }

                            const payload = await response.json();
                            if (!payload?.data) {
                                throw new Error('The selected saved question did not return any data.');
                            }

                            const order = this.nextQuestionImportOrder(importedQuestions.length);
                            const pageHint = this.pageOfOrder(order);
                            importedQuestions.push(createQuestionFromBankItem(payload.data, order, pageHint));
                        }

                        importedQuestions.forEach((question) => {
                            if (question.type === 'matching') {
                                this.ensureMatchingArrays(question);
                            }
                            this.questions.push(question);
                        });

                        this.refreshTagCoverageMetrics();
                        const lastImported = importedQuestions[importedQuestions.length - 1];
                        if (lastImported) {
                            this.currentPage = this.pageOfOrder(lastImported.order);
                        }

                        this.clearQuestionBankSelection();
                        this.closeQuestionBankModal();
                    } catch (error) {
                        this.questionBankModalError = error.message || 'Unable to import the selected saved questions.';
                    } finally {
                        this.questionBankImporting = false;
                    }
                },

                removeQuestion(idx) {
                    const page = this.pageOfOrder(this.questions?.[idx]?.order);
                    this.questions.splice(idx, 1);
                    this.compactOrdersForPage(page);
                    this.refreshTagCoverageMetrics();
                },

            deleteQuestion(idx) {
                const removed = this.questions.splice(idx, 1)[0];
                if (!removed) return;

                this.lastDeletedOption = null;

                this.lastDeleted = {
                    index: idx,
                    question: JSON.parse(JSON.stringify(removed)),
                };

                this.selectedTagQuestionIndexes = (this.selectedTagQuestionIndexes || [])
                    .map((value) => {
                        const current = Number.parseInt(value, 10);
                        if (!Number.isFinite(current)) return null;
                        if (current === idx) return null;
                        return current > idx ? current - 1 : current;
                    })
                    .filter((value) => Number.isFinite(value));
                this.expandedTagQuestionIndexes = (this.expandedTagQuestionIndexes || [])
                    .map((value) => {
                        const current = Number.parseInt(value, 10);
                        if (!Number.isFinite(current)) return null;
                        if (current === idx) return null;
                        return current > idx ? current - 1 : current;
                    })
                    .filter((value) => Number.isFinite(value));
                this.expandedQuestionTagLabelIndexes = (this.expandedQuestionTagLabelIndexes || [])
                    .map((value) => {
                        const current = Number.parseInt(value, 10);
                        if (!Number.isFinite(current)) return null;
                        if (current === idx) return null;
                        return current > idx ? current - 1 : current;
                    })
                    .filter((value) => Number.isFinite(value));
                this.reflowOrdersByPage();
                this.currentPage = Math.min(this.currentPage, this.totalPages);
                this.refreshTagCoverageMetrics();
                this.showToast('question');
            },

            undoDelete() {
                if (this.toastTimer) {
                        clearTimeout(this.toastTimer);
                        this.toastTimer = null;
                    }

                    if (this.toastKind === 'option') {
                        if (!this.lastDeletedOption) return;

                        const { qIndex, optIndex, text, wasSelected } = this.lastDeletedOption;
                        const q = this.questions[qIndex];
                        if (!q || !Array.isArray(q.options)) {
                            this.lastDeletedOption = null;
                            this.toastVisible = false;
                            return;
                        }

                        const safeIndex = Math.max(0, Math.min(optIndex, q.options.length));
                        q.options.splice(safeIndex, 0, text);

                        q.answer_key = (q.answer_key || [])
                            .map(v => String(v))
                            .map(v => {
                                const n = Number(v);
                                return (!isNaN(n) && n >= safeIndex) ? String(n + 1) : String(v);
                            });

                        if (wasSelected) {
                            const target = String(safeIndex);
                            if (!q.answer_key.map(String).includes(target)) {
                                q.answer_key.push(target);
                            }
                        }

                        q.answer_key = Array.from(new Set((q.answer_key || []).map(String)));

                        this.lastDeletedOption = null;
                        this.toastVisible = false;
                        return;
                    }

                if (!this.lastDeleted) return;
                const insertAt = Math.min(this.lastDeleted.index, this.questions.length);
                this.questions.splice(insertAt, 0, this.lastDeleted.question);
                this.lastDeleted = null;
                this.selectedTagQuestionIndexes = (this.selectedTagQuestionIndexes || [])
                    .map((value) => {
                        const current = Number.parseInt(value, 10);
                        if (!Number.isFinite(current)) return null;
                        return current >= insertAt ? current + 1 : current;
                    })
                    .filter((value) => Number.isFinite(value));
                this.expandedTagQuestionIndexes = (this.expandedTagQuestionIndexes || [])
                    .map((value) => {
                        const current = Number.parseInt(value, 10);
                        if (!Number.isFinite(current)) return null;
                        return current >= insertAt ? current + 1 : current;
                    })
                    .filter((value) => Number.isFinite(value));
                this.expandedQuestionTagLabelIndexes = (this.expandedQuestionTagLabelIndexes || [])
                    .map((value) => {
                        const current = Number.parseInt(value, 10);
                        if (!Number.isFinite(current)) return null;
                        return current >= insertAt ? current + 1 : current;
                    })
                    .filter((value) => Number.isFinite(value));
                this.reflowOrdersByPage();
                const targetPage = this.pageOfOrder(this.questions?.[insertAt]?.order);
                this.currentPage = Math.min(Math.max(1, targetPage), this.totalPages);
                this.refreshTagCoverageMetrics();
                this.toastVisible = false;
            },

                addOption(question) {
                    if (!Array.isArray(question.options)) question.options = [];
                    question.options.push('');
                },

                removeOption(qIndex, optIndex) {
                    const q = this.questions[qIndex];
                    if (!q || !Array.isArray(q.options)) return;

                    this.lastDeleted = null;

                    if (this.toastTimer) {
                        clearTimeout(this.toastTimer);
                        this.toastTimer = null;
                    }
                    this.toastVisible = false;
                    this.toastKind = 'option';

                    const removedText = q.options[optIndex];
                    const wasSelected = (q.answer_key || []).map(String).includes(String(optIndex));

                    q.options.splice(optIndex, 1);

                    q.answer_key = (q.answer_key || [])
                        .map(v => String(v))
                        .filter(v => Number(v) !== optIndex)
                        .map(v => {
                            const n = Number(v);
                            return (n > optIndex) ? String(n - 1) : String(n);
                        });

                    this.lastDeletedOption = {
                        qIndex,
                        optIndex,
                        text: removedText,
                        wasSelected,
                    };
                },

                ensureMatchingArrays(question) {
                    if (!question) return;
                    if (!Array.isArray(question.match_left)) question.match_left = [];
                    if (!Array.isArray(question.match_right)) question.match_right = [];
                    if (!question.match_map || typeof question.match_map !== 'object') question.match_map = {};
                    if (typeof question.use_pair_points !== 'boolean') question.use_pair_points = false;
                    if (!Array.isArray(question.pair_points)) question.pair_points = [];

                    if (question.match_left.length === 0 && question.match_right.length === 0) {
                        question.match_left = [''];
                        question.match_right = [''];
                    }

                    this.ensurePairPointsLength(question);
                },

                addLeftPair(question) {
                    this.ensureMatchingArrays(question);
                    question.use_pair_points = true;
                    if (question.match_left.length < question.match_right.length) {
                        question.match_left.push('');
                        this.ensurePairPointsLength(question);
                        return;
                    }
                    question.match_left.push('');
                    this.ensurePairPointsLength(question);
                },

                addRightPair(question) {
                    this.ensureMatchingArrays(question);
                    question.use_pair_points = true;
                    if (question.match_right.length < question.match_left.length) {
                        question.match_right.push('');
                        this.ensurePairPointsLength(question);
                        return;
                    }
                    question.match_right.push('');
                    this.ensurePairPointsLength(question);
                },

                removeMatchingLeft(question, rowIndex) {
                    if (!question) return;
                    this.ensureMatchingArrays(question);

                    if ((question.match_left || []).length <= 1) return;

                    if (Array.isArray(question.match_left) && rowIndex >= 0 && rowIndex < question.match_left.length) {
                        question.match_left.splice(rowIndex, 1);
                    }
                    if (Array.isArray(question.pair_points) && rowIndex >= 0 && rowIndex < question.pair_points.length) {
                        question.pair_points.splice(rowIndex, 1);
                    }

                    const nextMap = {};
                    Object.keys(question.match_map || {}).forEach((k) => {
                        const r = Number(k);
                        if (isNaN(r)) return;
                        if (r === rowIndex) return;
                        const newR = (r > rowIndex) ? (r - 1) : r;
                        nextMap[String(newR)] = String(question.match_map[k]);
                    });
                    question.match_map = nextMap;
                    this.ensurePairPointsLength(question);
                },

                removeMatchingRight(question, colIndex) {
                    if (!question) return;
                    this.ensureMatchingArrays(question);

                    if ((question.match_right || []).length <= 1) return;

                    if (Array.isArray(question.match_right) && colIndex >= 0 && colIndex < question.match_right.length) {
                        question.match_right.splice(colIndex, 1);
                    }

                    const nextMap = {};
                    Object.keys(question.match_map || {}).forEach((k) => {
                        const selected = Number(question.match_map[k]);
                        if (isNaN(selected)) return;
                        if (selected === colIndex) return;
                        const newSelected = (selected > colIndex) ? (selected - 1) : selected;
                        nextMap[String(k)] = String(newSelected);
                    });
                    question.match_map = nextMap;
                    this.ensurePairPointsLength(question);
                },

                toggleMatchSelection(question, rowIndex, colIndex) {
                    if (!question) return;
                    this.ensureMatchingArrays(question);

                    // Replace the object to ensure Alpine reacts and regenerates hidden inputs.
                    const next = { ...(question.match_map || {}) };
                    next[rowIndex] = String(colIndex);
                    question.match_map = next;
                },

                matchingPairsFor(question) {
                    if (!question || question.type !== 'matching') return [];
                    this.ensureMatchingArrays(question);

                    const left = Array.isArray(question.match_left) ? question.match_left : [];
                    const right = Array.isArray(question.match_right) ? question.match_right : [];
                    const map = (question.match_map && typeof question.match_map === 'object') ? question.match_map : {};

                    const out = [];

                    const fallbackPairs = () => {
                        const leftFilled = left.map(v => String(v ?? '').trim()).filter(Boolean);
                        const rightFilled = right.map(v => String(v ?? '').trim()).filter(Boolean);

                        const max = Math.min(leftFilled.length, rightFilled.length);
                        const fallbackOut = [];
                        for (let i = 0; i < max; i++) {
                            fallbackOut.push({ left: leftFilled[i], right: rightFilled[i] });
                        }
                        return fallbackOut;
                    };

                    const hasMap = Object.keys(map).length > 0;
                    if (!hasMap) {
                        return fallbackPairs();
                    }

                    Object.keys(map).forEach((rk) => {
                        const r = Number(rk);
                        const c = Number(map[rk]);
                        if (isNaN(r) || isNaN(c)) return;
                        const ltxt = (left[r] ?? '').toString().trim();
                        const rtxt = (right[c] ?? '').toString().trim();
                        if (!ltxt || !rtxt) return;
                        out.push({ left: ltxt, right: rtxt });
                    });

                    // If current selections are invalid (e.g., blank row/col text),
                    // fall back to pairing first non-empty entries to avoid empty submissions.
                    return out.length ? out : fallbackPairs();
                },

                truncateLabel(value, limit = 20) {
                    const s = String(value ?? '').trim();
                    if (!s) return '';
                    return (s.length > limit) ? (s.slice(0, limit) + ' ...') : s;
                },

                onTypeChange(question) {
                    if (['short_answer', 'numerical', 'true_false'].includes(question.type)) {
                        question.showAnswerKey = true;
                    }

                    if (question.type === 'multiple_choice') {
                        if (!Array.isArray(question.options) || question.options.length === 0) question.options = [''];
                        if (!Array.isArray(question.answer_key)) question.answer_key = [];
                    } else {
                        question.answer_key = [];
                    }

                    if (question.type === 'matching') {
                        this.ensureMatchingArrays(question);
                        this.ensurePairPointsLength(question);
                    } else {
                        if (!Array.isArray(question.match_left)) question.match_left = [];
                        if (!Array.isArray(question.match_right)) question.match_right = [];
                        if (!question.match_map || typeof question.match_map !== 'object') question.match_map = {};
                        question.pair_points = [];
                        question.use_pair_points = false;
                    }

                }
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            // Delete Quiz modal
            const deleteModal = document.getElementById('deleteQuizModal');
            const openDeleteBtn = document.getElementById('openDeleteModal');
            const cancelDeleteBtn = document.getElementById('cancelDelete');

            openDeleteBtn?.addEventListener('click', () => {
                deleteModal?.classList.remove('hidden');
                deleteModal?.classList.add('flex');
                document.body.classList.add('modal-open');
            });

            cancelDeleteBtn?.addEventListener('click', () => {
                deleteModal?.classList.add('hidden');
                deleteModal?.classList.remove('flex');
                document.body.classList.remove('modal-open');
            });

            // Update Questions confirmation modal
            const form = document.getElementById('quizQuestionsForm');
            const saveModal = document.getElementById('saveQuestionsModal');
            const cancelSaveBtn = document.getElementById('cancelSaveQuestions');
            const confirmSaveBtn = document.getElementById('confirmSaveQuestions');

            let allowSubmit = false;

            const openSaveModal = () => {
                saveModal?.classList.remove('hidden');
                saveModal?.classList.add('flex');
                document.body.classList.add('modal-open');
            };

            const closeSaveModal = () => {
                saveModal?.classList.add('hidden');
                saveModal?.classList.remove('flex');
                document.body.classList.remove('modal-open');
            };

            const getQuizFormData = () => {
                const root = form?.closest('[x-data]');
                if (!root) return null;
                if (window.Alpine && typeof window.Alpine.$data === 'function') {
                    try {
                        return window.Alpine.$data(root);
                    } catch (_error) {
                        // no-op fallback to internal Alpine store below
                    }
                }
                return root.__x?.$data ?? null;
            };

            form?.addEventListener('submit', (e) => {
                if (allowSubmit) return;
                e.preventDefault();
                openSaveModal();
            });

            cancelSaveBtn?.addEventListener('click', () => {
                closeSaveModal();
            });

            confirmSaveBtn?.addEventListener('click', () => {
                if (!form) return;

                const quizFormData = getQuizFormData();
                if (quizFormData && typeof quizFormData.validateBeforeSubmit === 'function') {
                    const isValid = quizFormData.validateBeforeSubmit();
                    if (!isValid) {
                        closeSaveModal();
                        return;
                    }
                }

                if (window.AdminModalLoading && typeof window.AdminModalLoading.setLoading === 'function') {
                    window.AdminModalLoading.setLoading(confirmSaveBtn, {
                        text: confirmSaveBtn?.dataset?.loadingText || 'Updating...',
                    });
                }

                allowSubmit = true;
                // Ensure Alpine flushes reactive DOM updates (e.g., hidden answer_key inputs)
                // before submitting the form.
                const doSubmit = () => requestAnimationFrame(() => form.submit());
                if (window.Alpine && typeof window.Alpine.nextTick === 'function') {
                    window.Alpine.nextTick(doSubmit);
                } else {
                    doSubmit();
                }
            });
        });
    </script>

@endsection
