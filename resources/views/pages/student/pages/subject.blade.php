@extends('pages.student.layout')

@section('content')
<div
    class="bg-[#FFFFFF] dark:bg-[#1E1E2D] dark:border-[#5F2DED]
           shadow-lg border p-4 sm:p-6 lg:p-8 rounded-lg"
> 
    {{-- Header --}}
    <div class="border-b border-[#D4D4D8] dark:border-[#5F2DED]">
        <h1 class="text-2xl sm:text-3xl font-bold text-[#5E5F60] dark:text-white">
            {{ $subject->subject_name }}
        </h1>
        <p class="mb-4 text-[#5E5F60] dark:text-white">
            Section {{ $section->section_rank }} - {{ $section->section_name }}
        </p>
        <x-student-nav active="board" :sectionId="$section->id" :subjectId="$subject->id"/>
    </div>

    {{-- Layout --}}
    <div class="flex flex-col lg:flex-row gap-6 lg:gap-8 mt-6">

        {{-- ================= LEFT SIDEBAR ================= --}}
        <div class="w-full lg:w-1/4 flex flex-col gap-6">

            {{-- Cover --}}
            <div class="border border-[#5F2DED] rounded p-4">
                <div class="w-full aspect-[16/9] max-h-44 md:max-h-36 border border-[#5F2DED] rounded overflow-hidden">
                    <img
                            src="{{ $schedule->coverImage && Storage::disk('public')->exists($schedule->coverImage)
                                ? Storage::url($schedule->coverImage)
                                : asset('classbg.jpg') }}"
                            class="w-full h-full object-cover"
                        >

                </div>

                <div class="border-l-4 border-[#5F2DED] mt-4 pl-2">
                    <p class="font-semibold text-[#373737] dark:text-white">
                        Section {{ $section->section_rank }}
                    </p>
                    <p class="text-sm text-[#373737] dark:text-white">
                        {{ $subject->subject_name }}
                    </p>

                    @php
                        $start = $schedule->start_time
                            ? \Carbon\Carbon::parse($schedule->start_time)->format('h:i A')
                            : 'TBD';

                        $end = $schedule->end_time
                            ? \Carbon\Carbon::parse($schedule->end_time)->format('h:i A')
                            : 'TBD';

                        $dayMap = [
                            'Mon'=>'Monday','Tue'=>'Tuesday','Wed'=>'Wednesday',
                            'Thu'=>'Thursday','Fri'=>'Friday','Sat'=>'Saturday','Sun'=>'Sunday'
                        ];

                        $daysRaw = $schedule->days ? json_decode($schedule->days, true) : null;

                        if (!$daysRaw) {
                            $daysLabel = 'N/A';
                        } else {
                            $ordered = array_values(array_intersect(array_keys($dayMap), $daysRaw));
                            $daysLabel = count($ordered) > 1
                                ? $dayMap[$ordered[0]].' – '.$dayMap[end($ordered)]
                                : $dayMap[$ordered[0]];
                        }
                    @endphp

                    <p class="text-sm text-[#757575] dark:text-white mt-4">
                        {{ $daysLabel }}
                    </p>
                    <p class="text-xs text-[#757575] dark:text-white">
                        {{ $start }} – {{ $end }}
                    </p>
                </div>
            </div>

            {{-- Upcoming --}}
            <div class="border border-[#5F2DED] rounded p-4">
                <p class="text-lg font-bold text-[#757575] dark:text-white">
                    Upcoming
                </p>

                @forelse($upcomingItems as $item)
                    <div class="flex gap-3 mt-4">
                        <i class="{{ $item['icon'] }} text-2xl text-primaryColor"></i>
                        <div>
                            <p class="text-xs font-semibold text-[#373737] dark:text-white">
                                {{ $item['type'] }}: {{ $item['title'] }}
                            </p>
                            <p class="text-xs text-[#7A7A7A] dark:text-white">
                                {{ $item['due_label'] }}
                            </p>
                        </div>
                    </div>
                @empty
                    <p class="text-xs text-[#7A7A7A] dark:text-white mt-4">
                        No upcoming classwork.
                    </p>
                @endforelse
            </div>
        </div>

        {{-- ================= MAIN CONTENT ================= --}}
        <div class="w-full lg:w-3/4 flex flex-col">

            <div class="border border-[#5F2DED] p-4 sm:p-6 rounded-lg flex flex-col gap-6">

                <div class="flex items-center gap-2 border-b pb-2">
                    <i class="icofont-bullhorn text-2xl sm:text-3xl text-primaryColor"></i>
                    <h2 class="text-xl sm:text-2xl font-bold text-[#373737] dark:text-white">
                        Class Announcements
                    </h2>
                </div>

                @forelse($announcements as $announcement)
                    <div class="shadow-lg border-l-4 border-[#5F2DED] p-4">

                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full overflow-hidden flex-shrink-0">
                                <img
                                    src="{{ optional($announcement->teacher)->profileImageUrl() ?? asset('assets/images/admin.png') }}"
                                    alt="Teacher Avatar"
                                    class="w-full h-full object-cover"
                                >
                            </div>

                            <div>
                                <p class="text-sm font-semibold text-[#373737] dark:text-white">
                                    {{ optional($announcement->teacher)->first_name }}
                                    {{ optional($announcement->teacher)->last_name }}
                                </p>
                                <p class="text-xs text-[#757575] dark:text-white">
                                    {{ $announcement->created_at->diffForHumans() }}
                                </p>
                            </div>
                        </div>


                        <p class="mt-4 text-lg sm:text-xl font-semibold text-[#373737] dark:text-white">
                            {{ $announcement->title }}
                        </p>

                        <div class="mt-2 text-[#5E5F60] pb-4 dark:text-white
            overflow-hidden
            [overflow-wrap:anywhere]
            [&_a]:text-[#2964ff]
            [&_a]:underline
            [&_a:hover]:text-[#2964ff]
            dark:[&_a]:text-blue-400
            dark:[&_a:hover]:text-blue-300">

                        {!! preg_replace(
                            '~(https?://[^\s]+|www\.[^\s]+)~',
                            '<a href="$1" target="_blank" rel="noopener noreferrer">$1</a>',
                            $announcement->body
                        ) !!}

                    </div>

                        <div class="mt-4 border-t pt-2 text-sm font-medium text-[#2964ff] flex gap-2">
                            <i class="icofont-comment"></i> Comments
                        </div>

                        {{-- Replies --}}
                        <div class="mt-4 flex flex-col gap-4">
                            @foreach($announcement->replies as $reply)
                                <div class="flex gap-3">
                                    <div class="w-9 h-9 rounded-full overflow-hidden">
                                        <img src="{{ 
                                            $reply->user->hasRole('teacher')
                                                ? $reply->user->teacher?->profileImageUrl()
                                                : $reply->user->student?->profileImageUrl()
                                                ?? asset('assets/images/admin.png')
                                        }}" class="w-full h-full object-cover">
                                    </div>

                                    <div>
                                        <p class="text-sm font-semibold {{ $reply->user->hasRole('teacher') ? 'text-[#2864FF]' : 'text-[#373737] dark:text-white' }}">
                                            {{ $reply->user->hasRole('teacher')
                                                ? $reply->user->teacher->first_name.' '.$reply->user->teacher->last_name
                                                : $reply->user->student->first_name.' '.$reply->user->student->last_name }}
                                        </p>
                                        <p class="text-sm text-[#5E5F60] dark:text-white">
                                            {{ $reply->reply }}
                                        </p>
                                        <p class="text-xs text-gray-400">
                                            {{ $reply->created_at->diffForHumans() }}
                                        </p>
                                    </div>
                                </div>
                            @endforeach

                            {{-- Reply Form --}}
                            <form
                                action="{{ route('student.announcements.reply', $announcement->id) }}"
                                method="POST"
                                onsubmit="return validateReplyBeforeSubmit(this)"
                                class="flex flex-col gap-2"
                            >
                                @csrf
                                <div class="flex gap-2">
                                    <input
                                        type="text"
                                        name="reply"
                                        required
                                        class="w-full border rounded-lg p-2 text-sm dark:text-white dark:bg-[#1E1E2D]"
                                        placeholder="Write a comment..."
                                    >
                                    <button type="submit" class="text-primaryColor">
                                        <i class="icofont-location-arrow"></i>
                                    </button>
                                </div>

                                {{-- Loading --}}
                                <div class="reply-loading hidden flex items-center gap-2 text-xs text-[#757575]">
                                    <svg class="animate-spin h-4 w-4 text-[#5F2DED]" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10"
                                                stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor"
                                              d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                                    </svg>
                                    Posting reply…
                                </div>
                            </form>
                        </div>
                    </div>
                @empty
                    <p class="text-center text-gray-400">
                        No Announcements Posted
                    </p>
                @endforelse
            </div>
        </div>
    </div>
</div>

<!-- Invalid Input Modal -->
<div id="invalidInputModal"
     class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white dark:bg-[#1E1E2D] rounded-lg shadow-lg max-w-sm w-full p-6 text-center">
        <h2 class="text-lg font-semibold text-red-600 mb-2">Invalid Input</h2>
        <p class="text-sm text-gray-600 dark:text-gray-300 mb-4">
            You cannot enter long unbroken text. Please add spaces.
        </p>
        <button onclick="closeInvalidInputModal()"
                class="px-4 py-2 bg-[#5F2DED] text-white rounded w-full">
            OK
        </button>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const MAX_WORD_LENGTH = 15;

    /* ================= MODAL ================= */

    function openInvalidInputModal() {
        document.getElementById('invalidInputModal')
            .classList.replace('hidden', 'flex');
    }

    function closeInvalidInputModal() {
        document.getElementById('invalidInputModal')
            .classList.replace('flex', 'hidden');
    }

    /* ================= REPLY HANDLING ================= */

    function handleReplySubmit(form) {
        const input = form.querySelector('input[name="reply"]');
        const button = form.querySelector('button[type="submit"]');
        const loader = form.querySelector('.reply-loading');

        input.readOnly = true;
        button.disabled = true;
        loader.classList.remove('hidden');
    }

    function hasInvalidWord(text) {
        const regex = new RegExp(`\\b\\w{${MAX_WORD_LENGTH + 1},}\\b`);
        return regex.test(text);
    }

    function validateReplyBeforeSubmit(form) {
        const replyInput = form.querySelector('input[name="reply"]');
        const value = replyInput.value.trim();

        if (hasInvalidWord(value)) {
            openInvalidInputModal();
            return false;
        }

        handleReplySubmit(form);
        return true;
    }

    /* ================= PASTE GUARD ================= */

    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('input[name="reply"]').forEach(input => {
            input.addEventListener('paste', e => {
                const pasted = (e.clipboardData || window.clipboardData).getData('text');
                if (hasInvalidWord(pasted)) {
                    e.preventDefault();
                    openInvalidInputModal();
                }
            });
        });
    });
</script>
@endpush

