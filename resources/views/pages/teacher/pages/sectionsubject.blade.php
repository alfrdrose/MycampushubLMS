@extends('pages.teacher.layout')

@section('content')
<div class="bg-white dark:bg-[#1E1E2D] dark:border-[#5F2DED] shadow-lg border p-4 sm:p-6 lg:p-8 rounded-lg">

    {{-- Header --}}
    <div class="border-b border-[#D4D4D8] dark:border-[#5F2DED] pb-4 mb-6">
        <h1 class="text-2xl sm:text-3xl font-bold text-[#5E5F60] dark:text-white">
            {{ $subject->subject_name }}
        </h1>
        <p class="text-sm sm:text-base text-[#5E5F60] dark:text-white">
            Section {{ $section->section_rank }} - {{ $section->section_name }}
        </p>

        <div class="mt-4">
            <x-teacher-nav active="board" :sectionId="$section->id" :subjectId="$subject->id"/>
        </div>
    </div>

    {{-- Layout --}}
    <div class="flex flex-col lg:flex-row gap-6">

        {{-- ================= LEFT SIDEBAR ================= --}}
        <aside class="w-full lg:w-1/4 flex flex-col gap-6">

            {{-- Cover Image --}}
            <div class="border border-[#5F2DED] rounded p-4">
                <form action="{{ route('teacher.class.updateCover', [$section->id, $subject->id]) }}"
                      method="POST" enctype="multipart/form-data" id="coverForm">
                    @csrf

                    <div class="relative aspect-[16/9] w-full max-h-44 md:max-h-40 lg:max-h-48 border border-[#5F2DED] rounded overflow-hidden">
                        <img
                            src="{{ $schedule->coverImage
                                ? Storage::url($schedule->coverImage)
                                : asset('classbg.jpg') }}"
                            alt="Cover Image"
                            class="w-full h-full object-cover pointer-events-none"
                        >



                        <button type="button"
                                class="absolute top-2 right-2 z-10 bg-[#5F2DED] text-white text-xs px-3 py-1 rounded shadow hover:bg-purple-700"
                                onclick="document.getElementById('coverImageInput').click()">
                            Upload
                        </button>


                        <input type="file" name="coverImage" id="coverImageInput" class="hidden"
                               onchange="document.getElementById('coverForm').submit();">
                    </div>
                </form>

                {{-- Info --}}
                <div class="border-l-4 border-[#5F2DED] mt-4 pl-3">
                    <p class="font-semibold text-[#373737] dark:text-white">
                        Section {{ $section->section_rank }}
                    </p>
                    <p class="text-sm text-[#373737] dark:text-white">
                        {{ $subject->subject_name }}
                    </p>
                    @if($schedule && $schedule->start_time && $schedule->end_time)
                    @php
                        $days = json_decode($schedule->days ?? '[]', true);

                        $daysLabel = collect($days)
                            ->map(fn ($d) => ucfirst($d))
                            ->join(', ');

                        $start = \Carbon\Carbon::parse($schedule->start_time)->format('g:i A');
                        $end   = \Carbon\Carbon::parse($schedule->end_time)->format('g:i A');
                    @endphp

                    <p class="text-sm text-[#757575] font-semibold dark:text-white mt-2">
                        {{ $daysLabel }}
                    </p>

                    <p class="text-sm  text-[#373737] dark:text-white">
                        {{ $start }} – {{ $end }}
                    </p>
                @else
                    <p class="text-sm italic text-gray-400 mt-2">
                        No schedule assigned
                    </p>
                @endif

                </div>
            </div>

            {{-- Stats --}}
            <div class="border border-[#5F2DED] rounded p-4">
                <p class="text-[#757575] dark:text-gray-50 text-sm">Currently Enrolled Students</p>
                <p class="text-[#343434] dark:text-white text-4xl sm:text-5xl font-bold mt-3">
                    {{ $students->count() }}
                </p>
                <p class="text-[#757575 ] dark:text-gray-50 text-sm mt-4">
                    Number of lessons:
                    <span class="font-bold text-[#343434] dark:text-white">{{ $lessonCount }}</span>
                </p>
                <p class="text-[#757575] dark:text-gray-50 text-sm mt-2">
                    Number of classwork:
                    <span class="font-bold text-[#343434] dark:text-white">{{ $classworkCount }}</span>
                </p>
            </div>

        </aside>

        {{-- ================= MAIN CONTENT ================= --}}
        <section class="w-full lg:w-3/4 flex flex-col gap-6">

            {{-- Post Input --}}
            <div class="relative">
                <i class="icofont-pencil absolute left-3 top-1/2 -translate-y-1/2 text-[#5F2DED] text-xl"></i>

                <input
                    type="text"
                    readonly
                    placeholder="Write something..."
                    onclick="toggleModal()"
                    class="w-full border border-[#5F2DED] rounded-lg pl-10 pr-28 py-4
                           focus:outline-none focus:ring-2 focus:ring-[#5F2DED]
                           dark:bg-[#2A2A3B] dark:text-white"
                >

                <button
                    onclick="toggleModal()"
                    class="absolute right-2 top-1/2 -translate-y-1/2
                           bg-[#E1D6FF] text-[#5F2DED] font-semibold px-5 py-1 rounded-md
                           hover:bg-[#4A23C6] hover:text-white transition"
                >
                    Post
                </button>
            </div>

            {{-- Announcements --}}
            <div class="border border-[#5F2DED] rounded-lg p-4 sm:p-6 flex flex-col gap-6">

                @forelse($announcements as $announcement)
                <div class="flex flex-col border-l-4 border-[#5F2DED] shadow-md p-4 rounded">

                    {{-- Header --}}
                    <div class="flex justify-between items-start">
                        <div class="flex gap-3 items-center">
                            <img
                                src="{{ optional($announcement->teacher)->profileImageUrl() }}"
                                class="w-10 h-10 rounded-full object-cover border-2 border-[#5F2DED]"
                                alt="Teacher Avatar"
                            >
                            <div>
                                <p class="font-semibold text-[#373737] dark:text-white">
                                    {{ optional($announcement->teacher)->first_name }}
                                    {{ optional($announcement->teacher)->last_name }}
                                </p>
                                <p class="text-xs text-[#757575] dark:text-white">
                                    {{ $announcement->created_at->diffForHumans() }}
                                </p>
                            </div>
                        </div>

                        <button
                            onclick="openDeleteModal({{ $announcement->id }})"
                            class=" text-red-600 hover:text-red-700"
                        >
                            <i class="icofont-trash text-xl"></i>
                        </button>
                    </div>

                    {{-- Content --}}
                    <p class="mt-4 text-lg font-semibold text-[#373737] dark:text-white
                    ">
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

                    <div class="py-4 border-t gap-2 flex items-center font-medium text-[#2964ff] text-sm"> 
                        <i class="icofont-comment"></i>
                        Comments
                    </div>

                    {{-- Replies --}}
                    <div class="mt-2 flex flex-col gap-4">

                        @foreach($announcement->replies as $reply)
                        <div class="flex gap-3 items-start">
                            <div class="w-9 h-9 rounded-full overflow-hidden flex-shrink-0">
                                @if($reply->user->hasRole('teacher'))
                                    <img src="{{ optional($reply->user->teacher)->profileImageUrl() }}"
                                         class="w-full h-full object-cover">
                                @elseif($reply->user->student)
                                    <img src="{{ $reply->user->student->profileImageUrl() }}"
                                        class="w-full h-full object-cover">
                                @else
                                    <img src="{{ asset('assets/images/admin.png') }}"
                                         class="w-full h-full object-cover">
                                @endif
                            </div>
                            
                            <div class="flex flex-col">
                                <p class="text-sm font-semibold
                                    {{ $reply->user->hasRole('teacher') ? 'text-[#2864FF]' : 'text-[#373737] dark:text-white' }}">
                                    {{ $reply->user->hasRole('teacher')
                                        ? optional($reply->user->teacher)->first_name . ' ' . optional($reply->user->teacher)->last_name
                                        : optional($reply->user->student)->first_name . ' ' . optional($reply->user->student)->last_name
                                    }}
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
                            action="{{ route('teacher.announcements.reply', $announcement->id) }}"
                            method="POST"
                            class="flex flex-col gap-2"
                            onsubmit="return validateReplyBeforeSubmit(this)"
                        >
                            @csrf

                            <div class="flex gap-2">
                                <input
                                    type="text"
                                    name="reply"
                                    required
                                    placeholder="Write a comment..."
                                    class="w-full border border-[#D4D4D8] dark:border-[#5F2DED]
                                        dark:bg-[#1E1E2D] rounded-lg p-2 text-sm dark:text-white
                                        focus:outline-none focus:ring-2 focus:ring-primaryColor"
                                >

                                <button
                                    type="submit"
                                    class="text-primaryColor flex items-center justify-center"
                                >
                                    <i class="icofont-location-arrow"></i>
                                </button>
                            </div>

                            <div class="reply-loading hidden flex items-center gap-2 text-sm text-[#757575] dark:text-gray-300">
                                <svg class="animate-spin h-4 w-4 text-[#5F2DED]" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z">
                                    </path>
                                </svg>
                                <span>Posting reply…</span>
                            </div>
                        </form>

                    </div>
                </div>
                @empty
                    <p class="text-center text-[#ABB7C2] dark:text-gray-400">
                        No Announcements Posted
                    </p>
                @endforelse

            </div>
        </section>
    </div>
</div>
@endsection

@section('modals')
@include('pages.teacher.partials.modals')
@endsection

@push('scripts')
<script>
/* ======================================================
   CONSTANTS & STATE
====================================================== */

const MAX_TITLE = 30;
const MAX_WORD_LENGTH = 15;

let hasInteracted = false;
let titleInput, bodyInput, postBtn, counter, titleError, bodyError;

/* ======================================================
   REPLY HANDLING (HARD BLOCK)
====================================================== */

function handleReplySubmit(form) {
    const input = form.querySelector('input[name="reply"]');
    const button = form.querySelector('button[type="submit"]');
    const loader = form.querySelector('.reply-loading');

    input.readOnly = true;
    button.disabled = true;
    loader.classList.remove('hidden');
}

function validateReplyBeforeSubmit(form) {
    const replyInput = form.querySelector('input[name="reply"]');
    const value = replyInput.value.trim();

    const hasInvalidWord = new RegExp(`\\S{${MAX_WORD_LENGTH + 1}}`).test(value);

    if (hasInvalidWord) {
        openInvalidInputModal();
        return false; // ⛔ HARD STOP
    }

    handleReplySubmit(form);
    return true;
}

/* ======================================================
   INVALID INPUT MODAL
====================================================== */

function openInvalidInputModal() {
    const modal = document.getElementById('invalidInputModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeInvalidInputModal() {
    const modal = document.getElementById('invalidInputModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

/* ======================================================
   DELETE
====================================================== */

function handleDeleteSubmit(form) {
    const deleteBtn = form.querySelector('#confirmDeleteBtn');
    const cancelBtn = form.querySelector('#cancelDeleteBtn');
    const loader = form.querySelector('.delete-loading');

    deleteBtn.disabled = true;
    cancelBtn.disabled = true;
    deleteBtn.classList.add('opacity-70', 'cursor-not-allowed');
    loader.classList.remove('hidden');
}

/* ======================================================
   MODALS
====================================================== */

function toggleModal() {
    const modal = document.getElementById('announcementModal');
    modal.classList.toggle('hidden');
    modal.classList.toggle('flex');

    hasInteracted = false;

    if (titleError) titleError.classList.add('hidden');
    if (bodyError) bodyError.classList.add('hidden');

    updateTitleCounter(true);

    if (postBtn) {
        postBtn.disabled = true;
        postBtn.classList.add('opacity-50', 'cursor-not-allowed');
    }
}

function openDeleteModal(id) {
    const modal = document.getElementById('deleteModal');
    const form = document.getElementById('deleteForm');

    form.action = `/teacher/announcements/${id}`;
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeDeleteModal() {
    const modal = document.getElementById('deleteModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

/* ======================================================
   ANNOUNCEMENT VALIDATION
====================================================== */

function validateAnnouncement() {
    if (!titleInput || !bodyInput || !postBtn) return;

    const titleLength = titleInput.value.trim().length;
    const bodyValid = bodyInput.value.trim().length > 0;
    const titleValid = titleLength > 0 && titleLength <= MAX_TITLE;

    if (hasInteracted) {
        titleError.classList.toggle('hidden', titleValid);
        bodyError.classList.toggle('hidden', bodyValid);
    }

    if (titleValid && bodyValid) {
        postBtn.disabled = false;
        postBtn.classList.remove('opacity-50', 'cursor-not-allowed');
    } else {
        postBtn.disabled = true;
        postBtn.classList.add('opacity-50', 'cursor-not-allowed');
    }
}

/* ======================================================
   TITLE COUNTER
====================================================== */

function updateTitleCounter(silent = false) {
    if (!counter || !titleInput) return;

    const length = titleInput.value.length;
    counter.textContent = `${length} / ${MAX_TITLE}`;

    if (!silent && (length === 0 || length > MAX_TITLE)) {
        counter.classList.add('text-red-500');
    } else {
        counter.classList.remove('text-red-500');
    }
}

/* ======================================================
   ANNOUNCEMENT SUBMIT LOADING
====================================================== */

function handleAnnouncementSubmit() {
    const cancelBtn = document.getElementById('cancelPostBtn');
    const loader = document.getElementById('postLoading');

    postBtn.disabled = true;
    cancelBtn.disabled = true;
    postBtn.classList.add('opacity-70', 'cursor-not-allowed');
    loader.classList.remove('hidden');
}

/* ======================================================
   DOM READY
====================================================== */

document.addEventListener('DOMContentLoaded', () => {

    /* Announcement fields */
    titleInput = document.getElementById('announcementTitle');
    bodyInput  = document.getElementById('announcementBody');
    postBtn    = document.getElementById('postBtn');
    counter    = document.getElementById('titleCounter');
    titleError = document.getElementById('titleError');
    bodyError  = document.getElementById('bodyError');

    if (titleInput) {
        titleInput.addEventListener('input', () => {
            hasInteracted = true;
            updateTitleCounter();
            validateAnnouncement();
        });
    }

    document.addEventListener('trix-change', () => {
        hasInteracted = true;
        validateAnnouncement();
    });

    updateTitleCounter(true);
    validateAnnouncement();

    /* Reply paste guard (UX only, submit guard already exists) */
    document.querySelectorAll('form[action*="announcements.reply"]').forEach(form => {
        const replyInput = form.querySelector('input[name="reply"]');
        if (!replyInput) return;

        replyInput.addEventListener('paste', (e) => {
            const pasted = (e.clipboardData || window.clipboardData).getData('text');
            if (new RegExp(`\\S{${MAX_WORD_LENGTH + 1}}`).test(pasted)) {
                e.preventDefault();
                openInvalidInputModal();
            }
        });
    });
});
</script>



@endpush

