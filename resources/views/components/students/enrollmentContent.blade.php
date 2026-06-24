@extends('layouts.dashboard')

@section('content')
@php
    $studentManagementQuery = $studentManagementQuery ?? [];
    $backQuery = array_filter(
        $studentManagementQuery,
        fn ($value) => $value !== null && $value !== ''
    );

    $formatSectionLabel = function ($section) {
        if (! $section) {
            return 'No section assigned';
        }

        $label = 'Grade ' . $section->grade_level . ' - Section ' . $section->section_rank;

        if (! empty($section->section_name)) {
            $label .= ' (' . $section->section_name . ')';
        }

        return $label;
    };

    $currentEnrollment = $student->currentEnrollment;
    $currentSectionLabel = $formatSectionLabel($currentEnrollment?->section ?? $student->section);
    $currentSchoolYearLabel = $currentEnrollment?->schoolYear?->label ?? 'No current enrollment';
@endphp

<div class="flex-1 flex flex-col p-4 sm:p-10 h-auto bg-whiteColor shadow-lg dark:bg-darkblack rounded-2xl transition-all duration-300 dark:ring-2 dark:ring-blue-500/20 dark:ring-offset-0">
    <div class="flex flex-col gap-4 border-b border-[#E4E7EC] pb-6 dark:border-white/10 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-[#5E5F60] dark:text-white sm:text-3xl">Student Enrollment</h1>
            <p class="text-sm text-[#ABB7C2] sm:text-base">Student details and enrollment records across school years.</p>
        </div>

        <a href="{{ route('admin.students', $backQuery) }}"
           class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-[#D7E1EA] text-[#5F2DED] transition hover:border-[#5F2DED] hover:bg-[#F6F2FF] dark:border-white/10 dark:text-[#B8A2FF] dark:hover:border-[#8B7CFF] dark:hover:bg-white/10"
           aria-label="Return to students"
           title="Return to students">
            <i class="icofont-simple-left text-lg"></i>
        </a>
    </div>

    <div class="mt-6 border-b border-[#E4E7EC] pb-6 dark:border-white/10">
        <p class="text-lg font-semibold text-[#373737] dark:text-white">Student Details</p>
        <dl class="mt-4 grid grid-cols-1 gap-x-8 gap-y-4 text-sm sm:grid-cols-2 xl:grid-cols-4">
            <div class="min-w-0">
                <dt class="text-[11px] font-semibold uppercase tracking-[0.18em] text-[#98A2B3]">Student</dt>
                <dd class="mt-1 text-base font-semibold text-[#1D2939] dark:text-white">{{ $student->first_name }} {{ $student->last_name }}</dd>
                <dd class="text-sm text-[#667085] dark:text-[#C5CED8]">ID {{ $student->id }}</dd>
            </div>

            <div class="min-w-0">
                <dt class="text-[11px] font-semibold uppercase tracking-[0.18em] text-[#98A2B3]">Contact</dt>
                <dd class="mt-1 truncate font-medium text-[#1D2939] dark:text-white">{{ $student->email }}</dd>
                <dd class="truncate text-[#667085] dark:text-[#C5CED8]">{{ $student->guardian_email ?? 'No guardian email' }}</dd>
            </div>

            <div class="min-w-0">
                <dt class="text-[11px] font-semibold uppercase tracking-[0.18em] text-[#98A2B3]">Current Section</dt>
                <dd class="mt-1 font-medium text-[#1D2939] dark:text-white">{{ $currentSectionLabel }}</dd>
                <dd class="text-[#667085] dark:text-[#C5CED8]">Grade {{ $student->grade }}</dd>
            </div>

            <div class="min-w-0">
                <dt class="text-[11px] font-semibold uppercase tracking-[0.18em] text-[#98A2B3]">Current School Year</dt>
                <dd class="mt-1 font-medium text-[#1D2939] dark:text-white">{{ $currentSchoolYearLabel }}</dd>
                <dd class="text-[#667085] dark:text-[#C5CED8]">{{ $currentEnrollment?->is_current ? 'Current enrollment on file' : 'No active enrollment record' }}</dd>
            </div>
        </dl>
    </div>

    <div class="mt-8">
        <div class="flex items-center justify-between gap-4">
            <div>
                <p class="text-lg font-semibold text-[#373737] dark:text-white">Enrollment Records</p>
                <p class="text-sm text-[#98A2B3]">Historical and current section assignments for this student.</p>
            </div>
            <span class="inline-flex items-center rounded-full bg-[#EEF2FF] px-3 py-1 text-xs font-semibold text-[#4338CA] dark:bg-[#312E81]/40 dark:text-[#C7D2FE]">
                {{ $enrollments->count() }} {{ \Illuminate\Support\Str::plural('record', $enrollments->count()) }}
            </span>
        </div>

        <div class="mt-4 hidden overflow-x-auto rounded-2xl border border-[#E4E7EC] dark:border-white/10 sm:block">
            <table class="min-w-full text-sm">
                <thead class="bg-[#F8FAFC] dark:bg-white/5">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-[#5E5F60] dark:text-gray-300">School Year</th>
                        <th class="px-4 py-3 text-left font-semibold text-[#5E5F60] dark:text-gray-300">Grade Level</th>
                        <th class="px-4 py-3 text-left font-semibold text-[#5E5F60] dark:text-gray-300">Previous Section</th>
                        <th class="px-4 py-3 text-left font-semibold text-[#5E5F60] dark:text-gray-300">Section</th>
                        <th class="px-4 py-3 text-left font-semibold text-[#5E5F60] dark:text-gray-300">Status</th>
                        <th class="px-4 py-3 text-left font-semibold text-[#5E5F60] dark:text-gray-300">Record</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($enrollments as $enrollment)
                        @php
                            $previousSectionLabel = $enrollment->previousEnrollment
                                ? $formatSectionLabel($enrollment->previousEnrollment->section)
                                : 'Initial record';
                            $sectionLabel = $formatSectionLabel($enrollment->section);
                            $statusLabel = ucfirst(str_replace('_', ' ', $enrollment->status ?? 'active'));
                            $recordLabel = $enrollment->is_current
                                ? 'Current'
                                : ($enrollment->ended_at ? 'Ended ' . $enrollment->ended_at->format('M d, Y') : 'Historical');
                        @endphp
                        <tr class="border-t border-[#E4E7EC] text-[#373737] dark:border-white/10 dark:text-white {{ $enrollment->is_current ? 'bg-[#F5F3FF] dark:bg-[#2A2148]' : '' }}">
                            <td class="px-4 py-3">{{ $enrollment->schoolYear?->label ?? 'No school year' }}</td>
                            <td class="px-4 py-3">Grade {{ $enrollment->grade_level ?? $student->grade }}</td>
                            <td class="px-4 py-3">{{ $previousSectionLabel }}</td>
                            <td class="px-4 py-3">{{ $sectionLabel }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $enrollment->is_current ? 'bg-[#DCFCE7] text-[#166534] dark:bg-[#14532D]/50 dark:text-[#BBF7D0]' : 'bg-[#F4F4F5] text-[#52525B] dark:bg-white/10 dark:text-[#D4D4D8]' }}">
                                    {{ $statusLabel }}
                                </span>
                            </td>
                            <td class="px-4 py-3">{{ $recordLabel }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">No enrollment records available.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4 space-y-4 sm:hidden">
            @forelse ($enrollments as $enrollment)
                @php
                    $previousSectionLabel = $enrollment->previousEnrollment
                        ? $formatSectionLabel($enrollment->previousEnrollment->section)
                        : 'Initial record';
                    $sectionLabel = $formatSectionLabel($enrollment->section);
                    $statusLabel = ucfirst(str_replace('_', ' ', $enrollment->status ?? 'active'));
                    $recordLabel = $enrollment->is_current
                        ? 'Current'
                        : ($enrollment->ended_at ? 'Ended ' . $enrollment->ended_at->format('M d, Y') : 'Historical');
                @endphp
                <div class="rounded-2xl border border-[#E4E7EC] bg-white p-4 shadow-sm dark:border-white/10 dark:bg-white/5">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="font-semibold text-[#1D2939] dark:text-white">{{ $enrollment->schoolYear?->label ?? 'No school year' }}</p>
                            <p class="text-sm text-[#667085] dark:text-[#C5CED8]">Grade {{ $enrollment->grade_level ?? $student->grade }}</p>
                        </div>
                        <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $enrollment->is_current ? 'bg-[#DCFCE7] text-[#166534] dark:bg-[#14532D]/50 dark:text-[#BBF7D0]' : 'bg-[#F4F4F5] text-[#52525B] dark:bg-white/10 dark:text-[#D4D4D8]' }}">
                            {{ $statusLabel }}
                        </span>
                    </div>

                    <div class="mt-4 space-y-2 text-sm text-[#475467] dark:text-[#D0D5DD]">
                        <p><span class="font-medium text-[#101828] dark:text-white">Previous:</span> {{ $previousSectionLabel }}</p>
                        <p><span class="font-medium text-[#101828] dark:text-white">Section:</span> {{ $sectionLabel }}</p>
                        <p><span class="font-medium text-[#101828] dark:text-white">Record:</span> {{ $recordLabel }}</p>
                    </div>
                </div>
            @empty
                <div class="rounded-2xl border border-dashed border-[#D0D5DD] px-4 py-8 text-center text-sm text-gray-500 dark:border-white/10 dark:text-gray-400">
                    No enrollment records available.
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
