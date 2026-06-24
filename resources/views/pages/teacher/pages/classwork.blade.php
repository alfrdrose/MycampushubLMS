{{-- {{ dd($classworkTypes) }} --}}

@extends('pages.teacher.layout')

@section('content')
@php
    $isAuditOnly = (bool) ($isAuditOnly ?? false);
@endphp
<div class="bg-[#FFFFFF] dark:bg-[#1E1E2D] dark:border-[#5F2DED] shadow-lg border p-4 sm:p-6 lg:p-8 rounded-lg"> 
    <div class="border-b border-[#D4D4D8] dark:border-[#5F2DED]">
        <h1 class="text-2xl sm:text-3xl font-bold mb-4 text-[#5E5F60] dark:text-white">
            {{ $section->section_name }} - {{ $subject->subject_name }}
        </h1>
        <x-teacher-nav active="classwork" :sectionId="$section->id" :subjectId="$subject->id"/>
    </div>

    @php
        $quizzesByPeriod = $quizzes->groupBy('grading_period');
        $evaluationsByPeriod = $evaluations->groupBy('grading_period');

        $gradingPeriods = [
            '1st' => '1st Quarter',
            '2nd' => '2nd Quarter',
            '3rd' => '3rd Quarter',
            '4th' => '4th Quarter'
        ];
    @endphp

    @if($isAuditOnly)
        <div class="mt-4 rounded-lg border border-[#F3C27A] bg-[#FFF7ED] px-4 py-3 text-sm font-medium text-[#9A3412] dark:border-[#7C4A03] dark:bg-[#31210D] dark:text-[#F6C98A]">
            Teacher audit mode is enabled. This classwork view is read only.
        </div>
    @endif

    <div class="flex flex-row mt-4"> 
        <div class="flex justify-start items-start flex-col w-full">

            <div 
                x-data="{
                    selectedPeriod: '1st',
                    editMode: false,
                    positions: {},
                    init() {
                        @foreach($gradingPeriods as $periodKey => $gradingPeriod)
                            @php
                                $periodActivities = $activities->get($periodKey, collect());
                                $periodQuizzes = $quizzesByPeriod->get($periodKey, collect());
                                $periodEvaluations = $evaluationsByPeriod->get($periodKey, collect());

                                $merged = collect()
                                    ->merge($periodActivities->map(fn($a) => $a->setAttribute('type','activity')))
                                    ->merge($periodQuizzes->map(fn($q) => $q->setAttribute('type','quiz')))
                                    ->merge($periodEvaluations->map(fn($e) => $e->setAttribute('type','evaluation')));
                            @endphp
                            @foreach($merged as $item)
                                this.positions['{{ $item->type }}_{{ $item->id }}'] = {{ $item->position ?? 1 }};
                            @endforeach
                        @endforeach
                    },
                    hasDuplicate() {
                        let values = Object.values(this.positions);
                        return new Set(values).size !== values.length;
                    },
                    hasInvalid() {
                        return Object.values(this.positions).some(v => v < 1 || isNaN(v));
                    },
                    isInvalid() {
                        return this.hasDuplicate() || this.hasInvalid();
                    }
                }"
                class="w-full"
            >

            {{-- ================= EMPTY STATE ================= --}}
            @if($activities->isEmpty() && $quizzes->isEmpty() && $evaluations->isEmpty())
                <div class="w-full flex justify-center items-center">
                    <p class="text-center text-gray-500 dark:text-gray-400 mt-8">
                        No classwork has been added yet.
                    </p>
                </div>
            @endif

            {{-- ================= DROPDOWN + CLASSWORK ================= --}}
            @if(!$activities->isEmpty() || !$quizzes->isEmpty() || !$evaluations->isEmpty())

            <div class="flex flex-wrap items-center gap-3 mb-4">
                <label class="text-[#373737] dark:text-white font-semibold">
                    Grading Period:
                </label>

                <select x-model="selectedPeriod"
                    class="px-8 py-2 rounded-lg border border-gray-300 dark:border-[#5F2DED]
                           bg-white dark:bg-[#2A2A3C] text-[#373737] cursor-pointer dark:text-white">
                    @foreach($gradingPeriods as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            @foreach ($gradingPeriods as $periodKey => $gradingPeriod)
            <form method="POST"
                  action="{{ route('teacher.classwork.reorder') }}"
                  id="reorder-form-{{ $periodKey }}">
                @csrf
                <input type="hidden" name="grading_period" value="{{ $periodKey }}">

                <div x-show="selectedPeriod === '{{ $periodKey }}'" x-cloak>

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

                    <p class="text-[#373737] dark:text-white font-medium text-lg sm:text-xl mb-4 mt-2">
                        {{ $gradingPeriod }}
                    </p>

                    @if($merged->isEmpty())
                        <p class="text-gray-500 dark:text-gray-400 mt-2">
                            No classwork in this grading period.
                        </p>
                    @else

                        @foreach($merged as $item)
                        <div class="flex items-center">

                            {{-- POSITION INPUT --}}
                            <div x-show="editMode" class="mr-3">
                                <input type="number"
                                    min="1"
                                    x-model.number="positions['{{ $item->type }}_{{ $item->id }}']"
                                    name="positions[{{ $item->type }}_{{ $item->id }}]"
                                    class="w-16 px-2 py-1 border rounded text-center
                                           dark:bg-[#2A2A3C] dark:text-white"
                                    :class="{
                                        'border-red-500 ring-2 ring-red-400':
                                        positions['{{ $item->type }}_{{ $item->id }}'] < 1 ||
                                        Object.values(positions).filter(v => v == positions['{{ $item->type }}_{{ $item->id }}']).length > 1
                                    }"
                                >
                            </div>

                            {{-- ================= ACTIVITY ================= --}}
                            @if($item->type === 'activity')
                            <a href="{{ route('teacher.classwork.view.selected', [
                                    'section_id' => $section->id,
                                    'subject_id' => $subject->id,
                                    'activity_id' => $item->id
                                ]) }}"
                                class="flex flex-col lg:flex-row mb-4 border border-l-8 border-l-[#531B8A] dark:border-[#A88BFF] rounded p-4 w-full lg:hover:scale-105 transition-all cursor-pointer"
                                :class="editMode ? 'pointer-events-none opacity-70' : ''">

                                <div class="px-2 py-2">
                                    <div class="rounded-full w-2 h-2 bg-[#531B8A] dark:bg-[#A88BFF]"></div>
                                </div>

                                <div class="flex flex-col ml-2 mb-2 gap-0 w-full lg:w-1/2">
                                    <p class="font-semibold text-[#373737] dark:text-white text-lg">
                                        {{ $item->title }}
                                    </p>

                                    <h1 class="text-4xl sm:text-5xl mt-4 font-bold text-black dark:text-white">
                                        {{ $item->completed_count }}
                                        <span class="text-[#F2277E]">/ {{ $totalStudents }}</span>
                                    </h1>
                                    <p class="text-[#757575] text-sm dark:text-white">Students Completed</p>
                                </div>

                                <div class="w-full lg:w-1/2 mb-2 flex flex-row justify-end">
                                    <div class="flex flex-col w-full items-start lg:items-end">

                                        <div class="px-2 py-2 h-1/2">
                                            <div class="rounded-full w-12 h-12 bg-[#E6D9F5] flex justify-center items-center">
                                                <i class="icofont-notebook text-[#531B8A] text-2xl"></i>
                                            </div>
                                        </div>

                                        <div class="h-1/2 w-full items-start lg:items-end justify-end flex flex-col pt-2">

                                            <div class="flex items-center">
                                                <i class="icofont-clock-time text-xl mr-2 text-[#F2277E]"></i>
                                                <p class="text-[#757575] dark:text-gray-300 text-sm">
                                                    <span class="font-bold text-black dark:text-white">Date Posted:</span>
                                                    {{ ($item->time_posted ?? $item->created_at)->format('h:i A M d, Y') }}
                                                </p>
                                            </div>

                                            <div class="flex items-center">
                                                <i class="icofont-clock-time text-xl mr-2 text-[#F2277E]"></i>
                                                <p class="font-bold text-black dark:text-white text-sm">
                                                    Due Date:
                                                    <span class="font-normal">
                                                        {{ $item->due_date ? $item->due_date->format('h:i A M d, Y') : 'Not Assigned yet' }}
                                                    </span>
                                                </p>
                                            </div>

                                        </div>
                                    </div>
                                </div>

                            </a>
                            {{-- ================= QUIZ ================= --}}
                            @elseif($item->type === 'quiz')
                            @php
                                $hasQuestions = (int)($item->questions_count ?? 0) > 0;
                                $quizManageUrl = $hasQuestions
                                    ? route('teacher.quiz.edit', ['section' => $section->id, 'subject' => $subject->id, 'quiz' => $item->id])
                                    : route('teacher.quiz.add', ['section' => $section->id, 'subject' => $subject->id, 'quiz' => $item->id]);
                            @endphp

                            <a href="{{ $quizManageUrl }}"
                                class="flex flex-col lg:flex-row mb-4 border border-l-8 border-l-[#2964ff] dark:border-[#2964ff] rounded p-4 w-full lg:hover:scale-105 transition-all cursor-pointer"
                                :class="editMode ? 'pointer-events-none opacity-70' : ''">

                                <div class="px-2 py-2">
                                    <div class="rounded-full w-2 h-2 bg-[#2964ff] dark:bg-[#2964ff]"></div>
                                </div>

                                <div class="flex flex-col ml-2 mb-2 gap-0 w-full lg:w-1/2">
                                    <p class="font-semibold text-[#373737] dark:text-white text-lg">
                                        {{ $item->title }}
                                    </p>

                                    <h1 class="text-4xl sm:text-5xl mt-4 font-bold text-black dark:text-white">
                                        {{ $item->completed_count }}
                                        <span class="text-[#F2277E]">/ {{ $totalStudents }}</span>
                                    </h1>
                                    <p class="text-[#757575] text-sm dark:text-white">Students Completed</p>
                                </div>

                                <div class="w-full lg:w-1/2 mb-2 flex flex-row justify-end">
                                    <div class="flex flex-col w-full items-start lg:items-end">
                                        <div class="px-2 py-2 h-1/2">
                                            <div class="rounded-full w-12 h-12 bg-[#c8d5f7] flex justify-center items-center">
                                                <i class="icofont-light-bulb text-[#2964ff] text-2xl"></i>
                                            </div>
                                        </div>

                                        <div class="h-1/2 w-full items-start lg:items-end justify-end flex flex-col pt-2">
                                            <div class="flex items-center">
                                                <i class="icofont-clock-time text-xl mr-2 text-[#F2277E]"></i>
                                                <p class="text-[#757575] dark:text-gray-300 text-sm">
                                                    <span class="font-bold text-black dark:text-white">Date Posted:</span>
                                                    {{ ($item->time_posted ?? $item->created_at)->format('h:i A M d, Y') }}
                                                </p>
                                            </div>

                                            <div class="flex items-center">
                                                <i class="icofont-clock-time text-xl mr-2 text-[#F2277E]"></i>
                                                <p class="font-bold text-black dark:text-white text-sm">
                                                    Due Date:
                                                    <span class="font-normal">
                                                        {{ $item->due_date ? $item->due_date->format('h:i A M d, Y') : 'Not Assigned yet' }}
                                                    </span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </a>


                            {{-- ================= EVALUATION ================= --}}
                            @elseif($item->type === 'evaluation')

                            <a href="{{ route('teacher.evaluation.view', [
                                    'section_id' => $section->id,
                                    'subject_id' => $subject->id,
                                    'evaluation_id' => $item->id
                                ]) }}"
                                class="flex flex-col lg:flex-row mb-4 border border-l-8 border-l-[#F2277E]
                                    rounded p-4 w-full lg:hover:scale-105 transition-all cursor-pointer"
                                :class="editMode ? 'pointer-events-none opacity-70' : ''">

                                <div class="px-2 py-2">
                                    <div class="rounded-full w-2 h-2 bg-[#F2277E]"></div>
                                </div>

                                <div class="flex flex-col ml-2 mb-2 gap-0 w-full lg:w-1/2">
                                    <p class="font-semibold text-[#373737] dark:text-white text-lg">
                                        {{ $item->title }}
                                    </p>

                                    <h1 class="text-4xl sm:text-5xl mt-4 font-bold text-black dark:text-white">
                                        {{ $item->completed_count }}
                                        <span class="text-[#F2277E]">/ {{ $totalStudents }}</span>
                                    </h1>
                                    <p class="text-[#757575] text-sm dark:text-white">Students Rated</p>
                                </div>

                                <div class="w-full lg:w-1/2 mb-2 flex flex-row justify-end">
                                    <div class="flex flex-col w-full items-start lg:items-end">
                                        <div class="px-2 py-2 h-1/2">
                                            <div class="rounded-full w-12 h-12 bg-[#FFE1EB] flex justify-center items-center">
                                                <i class="icofont-star text-[#F2277E] text-2xl"></i>
                                            </div>
                                        </div>

                                        <div class="h-1/2 w-full items-start lg:items-end justify-end flex flex-col pt-2">
                                            <div class="flex items-center">
                                                <i class="icofont-clock-time text-xl mr-2 text-[#F2277E]"></i>
                                                <p class="text-[#757575] dark:text-gray-300 text-sm">
                                                    <span class="font-bold text-black dark:text-white">Date Posted:</span>
                                                    {{ \Carbon\Carbon::parse($item->created_at)->format('h:i A M d, Y') }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </a>
                            @endif

                        </div>
                        @endforeach

                    @endif

                </div>
            </form>
            @endforeach

            @endif

            {{-- ================= ALWAYS SHOW BUTTONS ================= --}}
            <div class="w-full flex justify-between mt-6">

                {{-- LEFT SIDE --}}
                <div>
                    @if(!$isAuditOnly && (!$activities->isEmpty() || !$quizzes->isEmpty() || !$evaluations->isEmpty()))

                        <button x-show="!editMode"
                                @click="editMode = true"
                                type="button"
                                class="bg-[#E1D6FF] dark:bg-[#5F2DED] text-[#5F2DED] dark:text-white px-3 py-2 rounded-lg flex items-center">
                            <i class="icofont-edit mr-2"></i> Edit Order
                        </button>

                        <button x-show="editMode"
                                @click="editMode = false"
                                type="button"
                                class="bg-[#E1D6FF] dark:bg-[#5F2DED] text-[#5F2DED] dark:text-white px-3 py-2 rounded-lg flex items-center">
                            <i class="icofont-close mr-2"></i> Cancel
                        </button>

                    @endif
                </div>

                {{-- RIGHT SIDE --}}
                <div>
                    @unless($isAuditOnly)
                        <button x-show="!editMode"
                                @click="openAddClassworkModal()"
                                type="button"
                                data-action="add-classwork"
                                class="bg-[#E1D6FF] dark:bg-[#5F2DED] text-[#5F2DED] dark:text-white px-3 py-2 rounded-lg flex items-center">
                            <i class="icofont-plus mr-2"></i> Add Classwork
                        </button>

                        <button x-show="editMode"
                                type="button"
                                @click="document.getElementById('reorder-form-' + selectedPeriod).submit()"
                                :disabled="isInvalid()"
                                class="px-4 py-2 rounded-lg text-white transition-all"
                                :class="isInvalid()
                                    ? 'bg-gray-400 cursor-not-allowed'
                                    : 'bg-primaryColor hover:opacity-90'">
                            Save Order
                        </button>
                    @endunless

                </div>

            </div>

            </div>
        </div>
    </div>
</div>
@endsection

<x-lmsmodal />

{{-- Your entire <script> section remains EXACTLY the same --}}


<script>
/* =====================================
   VALIDATION + LOADING (CUSTOM ONLY)
===================================== */
function handleModalSubmit(form) {
    event.preventDefault(); // ⛔ stop native validation

    const submitBtn = form.querySelector('button[type="submit"]');
    let valid = true;

    // remove old errors
    form.querySelectorAll('.field-error').forEach(e => e.remove());
    form.querySelectorAll('.border-red-500').forEach(e => e.classList.remove('border-red-500'));

    // manual validation
    form.querySelectorAll('[data-required]').forEach(field => {
        if (!field.value.trim()) {
            valid = false;

            field.classList.add('border-red-500');

            const error = document.createElement('p');
            error.className = 'field-error text-xs text-red-500 mt-1';
            error.innerText = 'This field is required';

            field.parentElement.appendChild(error);

            field.addEventListener('input', () => {
                field.classList.remove('border-red-500');
                error.remove();
            }, { once: true });
        }
    });

    if (!valid) return false;

    // loading state
    submitBtn.disabled = true;
    submitBtn.classList.add('opacity-70', 'cursor-not-allowed');
    submitBtn.innerHTML = `
        <span class="flex items-center justify-center gap-2">
            <svg class="animate-spin h-4 w-4 text-white"
                 viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10"
                        stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor"
                      d="M4 12a8 8 0 018-8v4
                         a4 4 0 00-4 4H4z"></path>
            </svg>
            Saving…
        </span>
    `;

    form.submit(); // ✅ manually submit
}

/* =====================================
   ADD CLASSWORK PICKER
===================================== */
function openAddClassworkModal() {
    LMSModal.open({
        title: 'Add Classwork',
        content: `
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">

                <div onclick="openCreateQuizModal()"
                     class="cursor-pointer p-4 sm:p-6 rounded-lg border border-dashed border-[#5F2DED]
                            flex flex-col items-center justify-center hover:scale-105 transition-all text-center">
                    <div class="rounded-full mb-3 w-10 h-10 sm:w-12 sm:h-12 bg-[#5F2DED]
                                flex items-center justify-center">
                        <i class="icofont-light-bulb text-white text-xl sm:text-2xl"></i>
                    </div>
                    <p class="font-semibold text-sm sm:text-base dark:text-white">Test</p>
                </div>

                <div onclick="openCreateActivityModal()"
                     class="cursor-pointer p-4 sm:p-6 rounded-lg border border-dashed border-[#5F2DED]
                            flex flex-col items-center justify-center hover:scale-105 transition-all text-center">
                    <div class="rounded-full mb-3 w-10 h-10 sm:w-12 sm:h-12 bg-[#5F2DED]
                                flex items-center justify-center">
                        <i class="icofont-notebook text-white text-xl sm:text-2xl"></i>
                    </div>
                    <p class="font-semibold text-sm sm:text-base dark:text-white">Assignment</p>
                </div>

                <div onclick="openCreateEvaluationModal()"
                     class="cursor-pointer p-4 sm:p-6 rounded-lg border border-dashed border-[#5F2DED]
                            flex flex-col items-center justify-center hover:scale-105 transition-all text-center">
                    <div class="rounded-full mb-3 w-10 h-10 sm:w-12 sm:h-12 bg-[#5F2DED]
                                flex items-center justify-center">
                        <i class="icofont-book text-white text-xl sm:text-2xl"></i>
                    </div>
                    <p class="font-semibold text-sm sm:text-base dark:text-white">Evaluation</p>
                </div>

            </div>
        `
    });
}

/* =====================================
   FORM BUILDER
===================================== */
function buildForm(action, hidden = '') {
    return `
        <form method="POST"
              novalidate
              onsubmit="handleModalSubmit(this)"
              action="${action}"
              class="max-w-full sm:max-w-lg mx-auto">
            @csrf
            ${hidden}

            <div class="mb-4">
                <label class="text-sm">Title</label>
                <input type="text" name="title" data-required
                       class="w-full mt-1 p-2 border rounded">
            </div>

            <div class="mb-4">
                <label class="text-sm">Classwork Type</label>
                <select name="classwork_type" data-required
                        class="w-full mt-1 p-2 border rounded cursor-pointer">
                    <option value="">Select type</option>
                    @foreach($classworkTypes as $type)
                        <option value="{{ $type->id }}">
                            {{ $type->classwork_type }}
                            {{ $type->percentage ? '(' . ($type->percentage + 0) . '%)' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="mb-6">
                <label class="text-sm">Grading Period</label>
                <select name="grading_period" data-required
                        class="w-full mt-1 p-2 border rounded cursor-pointer">
                    <option value="">Select grading period</option>
                    <option value="1st">1st Quarter</option>
                    <option value="2nd">2nd Quarter</option>
                    <option value="3rd">3rd Quarter</option>
                    <option value="4th">4th Quarter</option>
                </select>
            </div>

            <button type="submit"
                    class="w-full bg-[#5F2DED] text-white py-2 mt-2 rounded-lg">
                Save
            </button>
        </form>
    `;
}

/* =====================================
   CREATE MODALS
===================================== */
function openCreateActivityModal() {
    LMSModal.open({
        title: 'Create Activity',
        content: buildForm(
            "{{ route('teacher.activities.store', ['section_id' => $section->id, 'subject_id' => $subject->id]) }}",
            `<input type="hidden" name="section_subject_id" value="{{ $sectionSubject->id }}">`
        )
    });
}

function openCreateQuizModal() {
    LMSModal.open({
        title: 'Create Quiz',
        content: buildForm(
            "{{ route('teacher.quiz.store', [$section->id, $subject->id]) }}"
        )
    });
}

function openCreateEvaluationModal() {
    LMSModal.open({
        title: 'Create Evaluation',
        content: buildForm(
            "{{ route('teacher.evaluation.store', [$section->id, $subject->id]) }}",
            `<input type="hidden" name="section_subject_id" value="{{ $sectionSubject->id }}">`
        )
    });
}
</script>
