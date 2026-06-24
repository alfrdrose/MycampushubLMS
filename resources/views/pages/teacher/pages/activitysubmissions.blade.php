{{-- resources/views/pages/teacher/pages/submissions/index.blade.php --}}

@extends('pages.teacher.layout')

@section('content')
@php
    $isAuditOnly = (bool) ($isAuditOnly ?? false);
@endphp

<div class="bg-white dark:bg-[#1E1E2D] dark:border-[#5F2DED] shadow-lg border p-6 rounded-lg">
    {{-- Header + nav --}}
    <div class="flex flex-col lg:flex-row lg:items-center gap-4 mb-4">
        <div class="flex items-center gap-3 w-full lg:w-auto">
            <button onclick="window.location='{{ route('teacher.classwork.view', [$section->id, $subject->id]) }}'"
                    class="flex items-center">
                <i class="icofont-arrow-left text-primaryColor text-3xl"></i>
            </button>

            <x-classwork-nav 
                active="submissions"
                :section-id="$section->id"
                :subject-id="$subject->id"
                :activity-id="$activity->id"
            />
        </div>

        <div class="ml-auto w-full lg:w-auto">
            <input id="liveSearch"
       placeholder="Search student..."
       class="flex-1 px-3 py-2 border rounded-lg dark:bg-[#111118] dark:border-[#2A2A3B] focus:outline-none" />

        </div>
    </div>

    {{-- Activity header --}}
    <div class="mb-4">
        <h2 class="text-xl font-semibold text-[#373737] dark:text-white">
            {{ $activity->title }} — Submissions
        </h2>
        <p class="text-sm text-[#5E5F60] dark:text-gray-300">
            {{ $subject->subject_name }} · {{ $section->section_name }} ·
            <span class="font-medium">{{ $totalSubmissions ?? ($groupedSubmissions->sum(fn($g) => $g->attempt_count) ?? 0) }}</span> submission(s)
        </p>
    </div>
    @if($isAuditOnly)
        <div class="mb-4 rounded-lg border border-[#F3C27A] bg-[#FFF7ED] px-4 py-3 text-sm font-medium text-[#9A3412] dark:border-[#7C4A03] dark:bg-[#31210D] dark:text-[#F6C98A]">
            Teacher audit mode is enabled. Historical submissions are shown in read only mode.
        </div>
    @endif

    {{-- Submission Cards --}}
    <div class="w-full flex flex-col gap-4 mt-4 p-2"> 

        @foreach($students as $student)
            @php
            // lookup grouped entry (controller provides $groupedSubmissions keyed by student_id)
            $entry = $groupedSubmissions->get($student->id) ?? null;
            $hasSubmission = !is_null($entry);
            $latest = $hasSubmission ? ($entry->latest ?? ($entry['latest'] ?? null)) : null;
            $attempts = $hasSubmission ? ($entry->attempts ?? ($entry['attempts'] ?? collect())) : collect();
            // build view url (use actual route in your app)
            $viewUrl = $hasSubmission ? '#' : null;
            
            // --- Build submission payload safely in PHP to avoid Blade parse errors ---
            $submissionPayload = null;
if ($hasSubmission) {

    $filesArr = [];
    if (isset($latest->files)) {
        foreach ($latest->files as $file) {
            foreach ($file->getMedia('student_files') as $media) {
                $filesArr[] = [
                    'name' => $media->file_name,
                    'url'  => $media->getUrl(),   // ← REAL URL
                ];
            }
        }
    }

    $submissionPayload = [
        'id'        => $latest->id,
        'type'      => $latest->type,   // may be: file, essay, both
        'essay'     => optional($latest->essay)->essay_text,
        'files'     => $filesArr,
        'submitter' => ($student->last_name ?? $student->name ?? 'Student') . ', ' . ($student->first_name ?? ''),
        'score'     => $latest->score ?? null,
    ];
}

        @endphp

            @if($hasSubmission)
                {{-- Keep your original clickable card exactly as-is when there's a submission --}}
                <a href="{{ $viewUrl }}"
                class="submission-card border border-l-12 px-8 py-6 border-l-[#bd9ad1] 
                        shadow-md rounded hover:scale-105 cursor-pointer
                        grid grid-cols-1 md:grid-cols-3 gap-4 items-center"
                data-name="{{ strtolower(($student->last_name ?? '').' '.($student->first_name ?? '')) }}"
                data-id="{{ $student->id }}"
                data-submission='@json($submissionPayload)'>


                    {{-- Student name + timing --}}
                    <div class="flex flex-col">
                        <p class="font-semibold text-[#373737] dark:text-white text-2xl">
                            {{ $student->last_name ?? $student->name ?? 'Student' }}, {{ $student->first_name ?? '' }}
                        </p>

                        <div class="flex flex-row gap-2 items-center mt-1">
                            @if(($latest->timing_status ?? '') === 'late')
                                <div class="w-3 h-3 rounded-full bg-red-800 dark:bg-red-500"></div>
                                <p class="text-red-800 dark:text-red-500 text-sm">Late Submission</p>
                            @else
                                <div class="w-3 h-3 rounded-full bg-green-800 dark:bg-green-500"></div>
                                <p class="text-green-800 dark:text-green-500 text-sm">Submitted on Time</p>
                            @endif
                        </div>
                    </div>

                    {{-- Submission date + time --}}
                    <div class="flex flex-col md:items-center">
                        <div class="flex flex-row gap-2 items-center">
                            <i class="icofont-calendar text-primaryColor text-lg"></i>
                            <p class="text-sm text-[#5E5F60] dark:text-gray-300">
                                {{ \Carbon\Carbon::parse($latest->submitted_at ?? $latest->created_at ?? now())->format('M d, Y') }}
                            </p>
                        </div>

                        <div class="flex flex-row gap-2 items-center">
                            <i class="icofont-clock-time text-primaryColor text-lg"></i>
                            <p class="text-sm text-[#5E5F60] dark:text-gray-300">
                                {{ \Carbon\Carbon::parse($latest->submitted_at ?? $latest->created_at ?? now())->format('h:i A') }}
                            </p>
                        </div>
                    </div>

                    {{-- Submission Grade --}}
                    <div class="flex justify-start md:justify-end">
                        <div class="flex flex-col items-start md:items-end">
                            <p class="text-3xl font-bold text-[#373737] dark:text-white">
                                <span class="card-score">{{ $latest->score ?? '0' }}</span> /
                                <span class="text-[#f2277e]">{{ $activity->total_score }}</span>
                            </p>

                            <p class="text-sm text-[#5E5F60] dark:text-gray-300">Submission Grade</p>
                        </div>
                    </div>

                </a>

            @else
                {{-- If no submission: non-clickable card, keep identical styling but muted badge/text --}}
                <div 
                class="border border-l-12 px-8 py-6 border-l-gray-300 dark:border-l-gray-600 
                        shadow-md rounded cursor-default
                        grid grid-cols-1 md:grid-cols-3 gap-4 items-center "
                 data-name="{{ strtolower(($student->last_name ?? '').' '.($student->first_name ?? '')) }}"       
                        data-id="{{ $student->id }}">

                    {{-- Student name + timing (gray 'No submission yet') --}}
                    <div class="flex flex-col">
                        <p class="font-semibold text-[#373737] dark:text-white text-2xl">
                            {{ $student->last_name ?? $student->name ?? 'Student' }}, {{ $student->first_name ?? '' }}
                        </p>

                        <div class="flex flex-row gap-2 items-center mt-1">
                            <div class="w-3 h-3 rounded-full bg-gray-300 dark:bg-gray-600"></div>
                            <p class="text-gray-500 dark:text-gray-400 text-sm">No submission yet</p>
                        </div>
                    </div>

                    {{-- Empty date/time placeholders (keeps layout identical) --}}
                    <div class="flex flex-col md:items-center">
                        <div class="flex flex-row gap-2 items-center">
                            <i class="icofont-calendar text-gray-300 dark:gray-600 text-lg"></i>
                            <p class="text-sm text-[#5E5F60] dark:text-gray-300">—</p>
                        </div>

                        <div class="flex flex-row gap-2 items-center">
                            <i class="icofont-clock-time text-gray-300 dark:gray-600 text-lg"></i>
                            <p class="text-sm text-[#5E5F60] dark:text-gray-300">—</p>
                        </div>
                    </div>

                    {{-- Grade column: show muted dash, not clickable --}}
                    <div class="flex justify-start md:justify-end">
                        <div class="flex flex-col items-start md:items-end">
                            <p class="text-3xl font-bold text-gray-400 dark:text-gray-500">
                                —
                            </p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">No submission</p>
                        </div>
                    </div>

                </div>
            @endif

        @endforeach

    </div>

</div>
@endsection

{{-- Submission Detail Modal (editable templates inside) --}}
<div id="submissionModalBackdrop" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 items-center justify-center">
  <div id="submissionModal" class="w-full max-w-3xl mx-4 md:mx-0 bg-white dark:bg-[#1E1E2D] rounded-lg shadow-lg overflow-hidden">
    {{-- header --}}
    <div class="flex items-center justify-between px-6 py-4 border-b dark:border-[#2A2A3B]">
        <div> 
            <p class="text-[#373737] dark:text-white text-sm">Submitted by:</p>
            <h3 id="submissionModalTitle" class="text-xl font-semibold text-[#373737] dark:text-white">Submission</h3>
        </div>
        
      <button id="submissionModalClose" class="text-gray-600 dark:text-gray-300 hover:text-gray-800 dark:hover:text-white">✕</button>
    </div>

    {{-- body --}}
    <div id="submissionModalBody" class="px-6 py-6 text-sm text-[#373737] dark:text-gray-200">
      {{-- Files section (hidden by default) --}}
      <div id="filesSection" class="hidden">
        <div class="flex items-center justify-between mb-2">
          <div class="font-medium text-sm text-[#373737] dark:text-white">Attached Files</div>
          <div class="text-xs text-[#5E5F60] dark:text-gray-300">Download Files</div>
        </div>

        {{-- container where JS will append file rows --}}
        <div id="filesContainer" class="bg-white dark:bg-[#1E1E2D] border border-gray-100 dark:border-[#1A1A24] rounded-md overflow-hidden"></div>
      </div>

      {{-- Essay section (hidden by default) --}}
      <div id="essaySection" class="hidden mt-6">
        <div class="font-medium text-sm text-[#373737] dark:text-white mb-2">Essay</div>
        {{-- editable wrapper for essay content; JS will set innerHTML (or text) here --}}
        <div id="essayContainer" class="prose max-w-none  dark:prose-invert text-sm max-h-[300px] overflow-x-auto"></div>
      </div>

      {{-- Fallback/empty message --}}
      <div id="emptySection" class="text-sm text-gray-600 dark:text-gray-400 hidden">
        No submission content available.
      </div>
      <template id="fileRowTemplate">
        <div class="flex items-center justify-between py-2 px-3 border-b last:border-b-0">
          <div class="truncate text-sm text-[#373737] dark:text-white file-name">File name</div>
          <div class="flex-shrink-0 ml-4">
            <a class="inline-flex items-center justify-center w-9 h-9 rounded-md hover:bg-gray-100 dark:hover:bg-[#111118] file-open" target="_blank" rel="noopener" href="#">
              <!-- edit this icon markup if you like -->
              <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-[#5F2DED]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v12m0 0l4-4m-4 4-4-4M21 21H3" />
              </svg>
            </a>
          </div>
        </div>
      </template>
    </div>

    {{-- footer --}}
    <div id="gradeSection" class="mt-4 m-6 border-t pt-4">
        <label class="block text-regular font-semibold text-[#373737] dark:text-gray-300 mb-2">Grade the Submission</label>

        <div class="flex items-center gap-3">
            <input id="gradeInput"
                type="number"
                min="0"
                step="1"
                placeholder="Enter score"
                class="rounded-lg px-3 py-2 border w-32 text-center"
                aria-label="Grade input" />

            <div class="text-[#f2277e] font-bold text-xl">
            / <span id="activityTotal">{{ $activity->total_score }}</span>
            </div>

            <button id="openConfirmGrade"
            class="ml-4 px-4 py-2 bg-[#5F2DED] text-white rounded-lg disabled:opacity-50 disabled:cursor-not-allowed">
                Save
            </button>

            <button id="removeGradeBtn"
            class="px-4 py-2 bg-red-600 text-white rounded-lg hidden">
                Remove Grade
            </button>

        </div>

        <div id="gradeError" class="mt-2 text-sm text-red-600 dark:text-red-400 hidden"></div>
    </div>

        {{-- confirmation modal (small) --}}
        <div id="confirmGradeBackdrop" class="fixed inset-0 bg-black bg-opacity-30 hidden z-60 items-center justify-center">
            <div class="bg-white dark:bg-[#1E1E2D] rounded-lg p-5 w-full max-w-md mx-4">
                <h4 class="text-lg font-semibold mb-2 text-[#373737] dark:text-white">Confirm Grade</h4>
                <p id="confirmMsg" class="text-base text-[#5E5F60] dark:text-gray-300 mb-4">Are you sure you want to set the score?</p>

                <div class="flex justify-end gap-3">
                <button id="cancelConfirm" class="px-4 py-2 rounded bg-gray-200 dark:bg-[#111118]">Cancel</button>
                <button id="confirmGrade"
                    class="px-4 py-2 rounded bg-[#5F2DED] text-white flex items-center gap-2 disabled:opacity-60">

                    <svg
                        id="gradeLoadingSpinner"
                        class="w-4 h-4 animate-spin hidden"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24">
                        <circle
                            class="opacity-25"
                            cx="12"
                            cy="12"
                            r="10"
                            stroke="currentColor"
                            stroke-width="4"></circle>
                        <path
                            class="opacity-75"
                            fill="currentColor"
                            d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                    </svg>

                    <span id="confirmGradeText">Confirm</span>
                </button>

                </div>
            </div>
        </div>

  </div>
</div>

{{-- Minimal JS to populate modal (DO NOT change unless needed) --}}
<script>
    document.addEventListener('DOMContentLoaded', function () {
    // --- Element lookups (with guards) ---
    const backdrop = document.getElementById('submissionModalBackdrop');
    const titleEl = document.getElementById('submissionModalTitle');
    const closeTop = document.getElementById('submissionModalClose');
    const closeFooter = document.getElementById('submissionModalCloseFooter'); // may be null

    const filesSection = document.getElementById('filesSection');
    const filesContainer = document.getElementById('filesContainer');
    const fileTemplate = document.getElementById('fileRowTemplate');

    const essaySection = document.getElementById('essaySection');
    const essayContainer = document.getElementById('essayContainer');

    const emptySection = document.getElementById('emptySection');

    // Grade elements
    const gradeInput = document.getElementById('gradeInput');
    const activityTotalEl = document.getElementById('activityTotal');
    const openConfirmBtn = document.getElementById('openConfirmGrade');
    const gradeError = document.getElementById('gradeError');

    const totalScore = Number(activityTotalEl?.textContent || 0);

    function validateScore() {
        const value = gradeInput.value.trim();

        // empty
        if (value === "") {
            openConfirmBtn.disabled = true;
            return;
        }

        const num = Number(value);

        // not number or negative
        if (Number.isNaN(num) || num < 0) {
            openConfirmBtn.disabled = true;
            return;
        }

        // exceeds total
        if (totalScore && num > totalScore) {
            openConfirmBtn.disabled = true;
            return;
        }

        // valid 🎉
        openConfirmBtn.disabled = false;
    }

    // run whenever typing
    gradeInput.addEventListener("input", validateScore);

    // also run once when modal opens
    validateScore();


    // confirm modal elements
    const confirmBackdrop = document.getElementById('confirmGradeBackdrop');
    const confirmMsg = document.getElementById('confirmMsg');
    const cancelConfirm = document.getElementById('cancelConfirm');
    const confirmGradeBtn = document.getElementById('confirmGrade');

    // small checks
    if (!backdrop || !titleEl) {
        console.error('Modal required elements missing: submission modal not present in DOM');
        return;
    }

    // utils
    function openModal() {
        backdrop.classList.remove('hidden');
        backdrop.classList.add('flex');
        document.body.style.overflow = 'hidden';
    }
    function closeModal() {
        backdrop.classList.add('hidden');
        backdrop.classList.remove('flex');
        // clear contents
        if (filesContainer) filesContainer.innerHTML = '';
        if (essayContainer) essayContainer.innerHTML = '';
        if (filesSection) filesSection.classList.add('hidden');
        if (essaySection) essaySection.classList.add('hidden');
        if (emptySection) emptySection.classList.add('hidden');
        titleEl.textContent = 'Submission';
        document.body.style.overflow = '';
    }

    // attach close handlers safely
    if (closeTop) closeTop.addEventListener('click', closeModal);
    if (closeFooter) closeFooter.addEventListener('click', closeModal);
    if (backdrop) {
        backdrop.addEventListener('click', function(e){
        if (e.target === backdrop) closeModal();
        });
    }
    document.addEventListener('keydown', function(e){
        if (e.key === 'Escape' && !backdrop.classList.contains('hidden')) closeModal();
    });

    // escape / normalize helpers
    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
        .replaceAll('&','&amp;')
        .replaceAll('<','&lt;')
        .replaceAll('>','&gt;')
        .replaceAll('"','&quot;')
        .replaceAll("'","&#039;");
    }
    function normalizeUrl(url) {
        if (!url) return '';
        if (url.startsWith('http://')) {
        return window.location.protocol + '//' + url.replace(/^https?:\/\//, '').replace(/^\/\//, '');
        }
        if (url.startsWith('//')) {
        return window.location.protocol + url;
        }
        return url;
    }

    // --- Submission state kept globally for grading ---
    let currentSubmissionPayload = null;
    let currentCardEl = null;

    // helper to show grade error
    function showGradeError(msg) {
        if (!gradeError) return;
        gradeError.textContent = msg;
        gradeError.classList.remove('hidden');
    }
    function clearGradeError() {
        if (!gradeError) return;
        gradeError.textContent = '';
        gradeError.classList.add('hidden');
    }

    // Confirm modal controls
    function openConfirm(text) {
        if (!confirmBackdrop || !confirmMsg) return;
        confirmMsg.textContent = text;
        confirmBackdrop.classList.remove('hidden');
        confirmBackdrop.classList.add('flex');
        document.body.style.overflow = 'hidden';
    }
    function closeConfirm() {
        if (!confirmBackdrop) return;
        confirmBackdrop.classList.add('hidden');
        confirmBackdrop.classList.remove('flex');
        document.body.style.overflow = '';
    }
    if (cancelConfirm) cancelConfirm.addEventListener('click', closeConfirm);
    if (confirmBackdrop) {
        confirmBackdrop.addEventListener('click', function(e){
        if (e.target === confirmBackdrop) closeConfirm();
        });
    }

    // Attach click handlers to submission cards
    document.querySelectorAll('.submission-card').forEach(card => {
        card.addEventListener('click', function (e) {
        e.preventDefault();

        const raw = card.getAttribute('data-submission');
        if (!raw) return;
        let payload;
        try {
            payload = JSON.parse(raw);
        } catch (err) {
            console.error('Invalid JSON payload on submission card', err);
            return;
        }

        // set global references for grading actions
        currentSubmissionPayload = payload;
        currentCardEl = card;

        // set modal title to student name if provided
        titleEl.textContent = payload.submitter ?? 'Student';

        // reset
        if (filesSection) filesSection.classList.add('hidden');
        if (essaySection) essaySection.classList.add('hidden');
        if (emptySection) emptySection.classList.add('hidden');
        if (filesContainer) filesContainer.innerHTML = '';
        if (essayContainer) essayContainer.innerHTML = '';

        // populate files or essay
        const hasFiles = payload.files && payload.files.length > 0;
    const hasEssay = payload.essay && payload.essay.trim() !== "";

    // Reset view
    filesSection.classList.add('hidden');
    essaySection.classList.add('hidden');
    emptySection.classList.add('hidden');
    filesContainer.innerHTML = '';
    essayContainer.innerHTML = '';

    if (hasFiles) {
        filesSection.classList.remove('hidden');
        payload.files.forEach(f => {
            const node = fileTemplate.content.cloneNode(true);
            node.querySelector('.file-name').textContent = f.name ?? 'file';
            node.querySelector('.file-open').href = f.url ?? '#';
            filesContainer.appendChild(node);
        });
    }

    if (hasEssay) {
        essaySection.classList.remove('hidden');
        essayContainer.innerHTML = payload.essay;
    }

    // If nothing at all
    if(!hasFiles && !hasEssay){
        emptySection.classList.remove('hidden');
    }


        // set grade input if present
        if (gradeInput) {
            const existingScore = (payload.score !== undefined && payload.score !== null) ? payload.score : '';
            gradeInput.value = existingScore === '' ? '' : Number(existingScore);

            const removeBtn = document.getElementById("removeGradeBtn");

    if (payload.score !== null && payload.score !== undefined) {
        removeBtn.classList.remove("hidden");
    } else {
        removeBtn.classList.add("hidden");
    }

        }
        clearGradeError();

        openModal();
        });
    });

    // --- Grading logic ---
    // guard elements
    const total = activityTotalEl ? Number(activityTotalEl.textContent || 0) : 0;

    if (openConfirmBtn) {
        openConfirmBtn.addEventListener('click', function () {
        clearGradeError();
        if (!currentSubmissionPayload) {
            showGradeError('No submission selected.');
            return;
        }

        if (!gradeInput) {
            showGradeError('Grade input missing.');
            return;
        }

        const val = (gradeInput.value ?? '').toString().trim();
        if (val === '') {
            showGradeError('Please enter a score.');
            return;
        }
        const num = Number(val);
        if (Number.isNaN(num)) {
            showGradeError('Score must be a number.');
            return;
        }
        if (num < 0) {
            showGradeError('Score cannot be negative.');
            return;
        }
        if (total && num > total) {
            showGradeError(`Score cannot exceed ${total}.`);
            return;
        }

        const studentName = currentSubmissionPayload.submitter ?? 'Student';
        openConfirm(`Set ${studentName}'s score to ${num} / ${total || '0'}?`);
        });
    }

    if (confirmGradeBtn) {
        confirmGradeBtn.addEventListener('click', function () {

            const spinner = document.getElementById("gradeLoadingSpinner");
            const btnText = document.getElementById("confirmGradeText");

            // lock UI
            confirmGradeBtn.disabled = true;
            spinner.classList.remove("hidden");
            btnText.textContent = "Saving...";

            if (!currentSubmissionPayload || !gradeInput) {
                resetGradeLoading();
                closeConfirm();
                return;
            }

            const score = Number(gradeInput.value.trim());
            const submissionId = currentSubmissionPayload.id;

            if (!submissionId) {
                showGradeError('Submission id missing.');
                resetGradeLoading();
                closeConfirm();
                return;
            }

            const endpoint = `/teacher/submission/${submissionId}/grade`;
            const tokenMeta = document.querySelector('meta[name="csrf-token"]');
            const csrfToken = tokenMeta ? tokenMeta.getAttribute('content') : '';

            fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ score: score })
            })
            .then(async res => {
                if (!res.ok) {
                    const json = await res.json().catch(()=>({}));
                    return Promise.reject(json || { message: 'Server error' });
                }
                return res.json();
            })
            .then(json => {
                // success
                closeConfirm();

                currentSubmissionPayload.score = score;

                if (currentCardEl) {
                    const scoreNode = currentCardEl.querySelector('.card-score');
                    if (scoreNode) scoreNode.textContent = score;
                }

                window.location.reload();
            })
            .catch(err => {
                console.error('Grade save error', err);
                closeConfirm();

                if (err?.errors?.score) {
                    showGradeError(err.errors.score.join(' '));
                } else if (err?.message) {
                    showGradeError(err.message);
                } else {
                    showGradeError('Unable to save score. Try again.');
                }

                resetGradeLoading();
            });

            function resetGradeLoading() {
                confirmGradeBtn.disabled = false;
                spinner.classList.add("hidden");
                btnText.textContent = "Confirm";
            }
        });
    }

    // Live search
    const search = document.getElementById("liveSearch");
    const cards = document.querySelectorAll("[data-name]");

    search.addEventListener("input", function () {
        const term = this.value.toLowerCase().trim();

        cards.forEach(card => {
            const name = (card.dataset.name || "").toLowerCase();
            card.classList.toggle("hidden", !name.includes(term));
        });
    });
    //Remove grade
    const removeGradeBtn = document.getElementById("removeGradeBtn");

    if (removeGradeBtn) {
        removeGradeBtn.addEventListener("click", function () {
            if (!currentSubmissionPayload) return;

            const submissionId = currentSubmissionPayload.id;
            const endpoint = `/teacher/submission/${submissionId}/remove-grade`;

            const tokenMeta = document.querySelector('meta[name="csrf-token"]');
            const csrfToken = tokenMeta ? tokenMeta.getAttribute('content') : '';

            fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                }
            }).then(async res => {
                if (!res.ok) {
                    const json = await res.json().catch(()=>({}));
                    return Promise.reject(json || { message: 'Server error' });
                }
                return res.json();
            }).then(json => {

                // Reset UI
                gradeInput.value = "";
                currentSubmissionPayload.score = null;
                removeGradeBtn.classList.add("hidden");

                if (currentCardEl) {
                    const scoreNode = currentCardEl.querySelector('.card-score');
                    if (scoreNode) scoreNode.textContent = "0";
                }

                window.location.reload();
            }).catch(err => {
                console.error(err);
                showGradeError("Unable to remove grade. Try again.");
            });
        });
    }


    }); // DOMContentLoaded
</script>
