@extends('layouts.dashboard')

@section('content')
<div class="w-full p-4 sm:p-6 md:p-10 bg-whiteColor dark:bg-whiteColor-dark shadow-accordion dark:shadow-accordion-dark rounded-lg">

    {{-- HEADER --}}
    <div id="page-header" class="mb-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 id="page-title" class="text-2xl sm:text-3xl font-bold text-[#5E5F60] dark:text-white">
                Dashboard
            </h1>
        </div>

        <div id="spinner-template" class="hidden">
            <div class="flex items-center justify-center h-full w-full">
                <x-spinner />
            </div>
        </div>
    </div>

    {{-- ================= STATS CARDS ================= --}}
    <div class="border-t border-[#D4D4D8] dark:border-white mb-7 py-7">

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4">

            {{-- CARD --}}
            <div class="rounded-lg border-2 border-[#E1D6FF] px-4 py-5 flex gap-3">
                <div class="border-l-4 border-[#578ABF]"></div>
                <div>
                    <p class="text-sm text-[#373737] dark:text-white">Total Accounts</p>
                    <p class="text-2xl sm:text-3xl font-bold text-[#373737] dark:text-white mt-2">
                        {{ $totalAccounts ?? 0 }}
                    </p>
                </div>
            </div>

            <div class="rounded-lg border-2 border-[#E1D6FF] px-4 py-5 flex gap-3">
                <div class="border-l-4 border-[#531B8A]"></div>
                <div>
                    <p class="text-sm text-[#373737] dark:text-white">Total Teachers</p>
                    <p class="text-2xl sm:text-3xl font-bold text-[#373737] dark:text-white mt-2">
                        {{ $totalTeachers ?? 0 }}
                    </p>
                </div>
            </div>

            <div class="rounded-lg border-2 border-[#E1D6FF] px-4 py-5 flex gap-3">
                <div class="border-l-4 border-[#519385]"></div>
                <div>
                    <p class="text-sm text-[#373737] dark:text-white">Total Students</p>
                    <p class="text-2xl sm:text-3xl font-bold text-[#373737] dark:text-white mt-2">
                        {{ $totalStudents ?? 0 }}
                    </p>
                </div>
            </div>

            <div class="rounded-lg border-2 border-[#E1D6FF] px-4 py-5 flex gap-3">
                <div class="border-l-4 border-[#E5BB91]"></div>
                <div>
                    <p class="text-sm text-[#373737] dark:text-white">Total Subjects</p>
                    <p class="text-2xl sm:text-3xl font-bold text-[#373737] dark:text-white mt-2">
                        {{ $totalSubjects ?? 0 }}
                    </p>
                </div>
            </div>

            <div class="rounded-lg border-2 border-[#E1D6FF] px-4 py-5 flex gap-3">
                <div class="border-l-4 border-[#F59C9A]"></div>
                <div>
                    <p class="text-sm text-[#373737] dark:text-white">Total Sections</p>
                    <p class="text-2xl sm:text-3xl font-bold text-[#373737] dark:text-white mt-2">
                        {{ $totalSections ?? 0 }}
                    </p>
                </div>
            </div>

        </div>

        {{-- ================= RECENT ACTIVITIES ================= --}}
        <div class="rounded-lg border-2 border-[#E1D6FF] mt-8 px-4 sm:px-5 py-4">
            <p class="text-base text-[#373737] dark:text-white font-semibold pb-3">
                Recent Activities
            </p>

            {{-- MOBILE NOTE --}}
            <p class="text-xs text-gray-400 sm:hidden mb-2">
                Swipe left to view more →
            </p>

            {{-- SCROLLABLE TABLE --}}
            <div class="overflow-x-auto">
                <div class="min-w-[900px]">

                    {{-- TABLE HEADER --}}
                    <div class="flex border-b border-[#E2E8F0] pb-3 text-sm text-[#5E5F60] dark:text-white">
                        <div class="flex-[2.5]">ID</div>
                        <div class="flex-[3]">Action</div>
                        <div class="flex-[3.5]">Module</div>
                        <div class="flex-[2] text-center">Done by</div>
                        <div class="flex-[2] text-center">Time</div>
                        <div class="flex-[2] text-center">Date</div>
                    </div>

                    @forelse ($recentActivities as $activity)
                        @php
                        $actor = $activity->causer?->name
                            ?? data_get($activity->properties, 'username', 'System');
                        $event = $activity->event ?? $activity->description;
                        $moduleKey = $activity->subject_type ? class_basename($activity->subject_type) : null;
                        $moduleOverride = data_get($activity->properties, 'module');
                        $module = $moduleOverride ?: ($moduleKey ? ($moduleLabels[$moduleKey] ?? $moduleKey) : 'System');
                        $targetId = $activity->subject_id ?? '-';
                    @endphp
                        <div class="flex py-3 text-sm">
                            <div class="flex-[2.5] text-[#ABB7C2]">{{ $targetId }}</div>
                            <div class="flex-[3] dark:text-white capitalize">{{ $event }}</div>
                            <div class="flex-[3.5] dark:text-white">{{ $module }}</div>
                            <div class="flex-[2] text-center dark:text-white">{{ $actor }}</div>
                            <div class="flex-[2] text-center dark:text-white">{{ optional($activity->created_at)->format('h:i A') }}</div>
                            <div class="flex-[2] text-center dark:text-white">{{ optional($activity->created_at)->format('M d, Y') }}</div>
                        </div>
                    @empty
                        <div class="py-6 text-sm text-gray-500 dark:text-gray-300 text-center">
                            No recent activity recorded yet.
                        </div>
                    @endforelse

                </div>
            </div>

        </div>
    </div>
</div>
@endsection
