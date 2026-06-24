@extends('pages.teacher.layout')

@section('content')
@php
    $isAuditOnly = (bool) ($isAuditOnly ?? false);
@endphp
<div class="bg-[#FFFFFF] dark:bg-[#1E1E2D] dark:border-[#5F2DED] shadow-lg border p-8 rounded-lg">
    <div class="border-b border-[#D4D4D8] dark:border-[#5F2DED]">
        <h1 class="text-3xl font-bold mb-4 text-[#5E5F60] dark:text-white">
            {{ $section->section_name }} - {{ $subject->subject_name }}
        </h1>

        <x-teacher-nav :active="'lessons'" :sectionId="$section->id" :subjectId="$subject->id"/>
    </div>

    @php
        $gradingPeriods = $gradingPeriods ?? [
            '1st' => '1st Quarter',
            '2nd' => '2nd Quarter',
            '3rd' => '3rd Quarter',
            '4th' => '4th Quarter',
        ];
    @endphp

    @if($isAuditOnly)
        <div class="mb-6 rounded-lg border border-[#F3C27A] bg-[#FFF7ED] px-4 py-3 text-sm font-medium text-[#9A3412] dark:border-[#7C4A03] dark:bg-[#31210D] dark:text-[#F6C98A]">
            Teacher audit mode is enabled. Lessons in this class are read only.
        </div>
    @endif

    <div 
        x-data="{
            selectedPeriod: '{{ $selectedGradingPeriod ?? '1st' }}',
            editMode: false,
            positions: {},
            init() {
                @foreach($gradingPeriods as $periodKey => $period)
                    @foreach($lessons->where('grading_period', $periodKey) as $lesson)
                        this.positions['{{ $lesson->id }}'] = {{ $lesson->position }};
                    @endforeach
                @endforeach
            },
            hasDuplicate() {
                let values = Object.values(this.positions);
                return new Set(values).size !== values.length;
            },
            hasNegative() {
                return Object.values(this.positions).some(v => v < 1 || isNaN(v));
            },
            isInvalid() {
                return this.hasDuplicate() || this.hasNegative();
            }
        }"
        class="mt-6 w-full"
        >

        <div class="flex flex-wrap items-center gap-3 mb-6">
            <label class="text-[#373737] dark:text-white font-semibold text-sm sm:text-base">
                Grading Period:
            </label>

            <select x-model="selectedPeriod"
                class="px-8 py-2 rounded-lg border border-gray-300 dark:border-[#5F2DED]
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

                <form method="POST"
                    action="{{ route('teacher.lesson.reorder') }}"
                    id="reorder-form-{{ $periodKey }}">
                    @csrf

                    <div class="mb-3">
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

                        <div class="flex items-center">

                            <div x-show="editMode" class="mr-3">
                                <input type="number"
                                    min="1"
                                    data-position
                                    data-id="{{ $lesson->id }}"
                                    x-model.number="positions['{{ $lesson->id }}']"
                                    name="positions[{{ $lesson->id }}]"
                                    value="{{ $lesson->position }}"
                                    class="w-16 px-2 py-1 border rounded text-center
                                            dark:bg-[#2A2A3C] dark:text-white
                                            focus:outline-none"
                                    :class="{
                                            'border-red-500 ring-2 ring-red-400':
                                            positions['{{ $lesson->id }}'] < 1 ||
                                            Object.values(positions).filter(v => v == positions['{{ $lesson->id }}']).length > 1
                                    }"
                                >
                            </div>

                            <a href="{{ route('teacher.lesson.show', [
                                'section_id' => $section->id,
                                'subject_id' => $subject->id,
                                'lesson_id' => $lesson->id,
                                'grading_period' => $periodKey,
                            ]) }}"
                               class="flex flex-col lg:flex-row mb-4 border border-l-8 border-l-[#531B8A] dark:border-[#A88BFF] rounded p-4 w-full lg:hover:scale-105 transition-all cursor-pointer"
                               :class="editMode ? 'pointer-events-none opacity-70' : ''">

                                <div class="px-2 py-2">
                                    <div class="rounded-full w-2 h-2 bg-[#531B8A] dark:bg-[#A88BFF]"></div>
                                </div>

                                <div class="flex flex-col ml-2 mb-2 gap-0 w-full lg:w-1/2">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="font-semibold text-[#373737] dark:text-white text-lg">
                                            {{ $lesson->lesson_title }}
                                        </p>
                                    </div>

                                    @if($lessonDescriptionPreview !== '')
                                        <p class="text-sm text-[#5E5F60] dark:text-gray-300 mt-1">
                                            {{ $lessonDescriptionPreview }}
                                        </p>
                                    @endif
                                </div>

                                <div class="w-full lg:w-1/2 mb-2 flex flex-row justify-end">
                                    <div class="flex flex-col w-full items-start lg:items-end pt-2">
                                        <div class="flex items-center">
                                            <p class="text-[#757575] dark:text-gray-300 text-sm">
                                                {{ \Carbon\Carbon::parse($lesson->created_at)->format('h:i A M d, Y') }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>

                    @empty
                        <p class="text-gray-500 dark:text-gray-400">No lessons uploaded yet.</p>
                    @endforelse

                </form>
            </div>
        @endforeach

        {{-- Bottom Controls --}}
<div class="w-full flex justify-between mt-6">

    {{-- LEFT SIDE --}}
    <div>
        @unless($isAuditOnly)
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
        @endunless
    </div>

    {{-- RIGHT SIDE --}}
    <div>
        @unless($isAuditOnly)
            <a x-show="!editMode"
               x-bind:href="'{{ route('teacher.lesson.create', [$section->id, $subject->id]) }}?grading_period=' + selectedPeriod"
               data-action="add-lesson"
               class="bg-[#E1D6FF] dark:bg-[#5F2DED] text-[#5F2DED] dark:text-white px-3 py-2 rounded-lg flex items-center">
                <i class="icofont-plus mr-2"></i> Add Lesson
            </a>

            <button x-show="editMode"
                    type="button"
                    @click="document.getElementById('reorder-form-' + selectedPeriod).submit()"
                    :disabled="isInvalid && isInvalid()"
                    class="px-4 py-2 rounded-lg text-white transition-all"
                    :class="(isInvalid && isInvalid())
                        ? 'bg-gray-400 cursor-not-allowed'
                        : 'bg-green-500 hover:opacity-90'">
                Save Order
            </button>
        @endunless
    </div>

</div>

    </div>
</div>
@endsection
