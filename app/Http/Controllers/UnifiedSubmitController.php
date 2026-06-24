<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\ActivitySubmission;
use App\Models\ActivitySubmissionEssay;
use App\Models\ActivitySubmissionFile;
use App\Models\SchoolYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class UnifiedSubmitController extends Controller
{
    private function ensureStudentOwnsActivity($student, Activity $activity): void
    {
        $activity->loadMissing('sectionSubject');
        $sectionSubject = $activity->sectionSubject;
        $activeSchoolYearId = SchoolYear::activeId();

        if (!$sectionSubject || (int) $student->section_id !== (int) $sectionSubject->section_model_id) {
            abort(403, 'You are not enrolled in this section.');
        }

        if (! $activeSchoolYearId || (int) optional($sectionSubject->section)->school_year_id !== $activeSchoolYearId) {
            abort(403, 'This activity is not available in the current school year.');
        }
    }

    private function getActivityOpenAt(Activity $activity): ?Carbon
    {
        if (!$activity->open_date) {
            return null;
        }

        $time = $activity->open_time ?: '00:00:00';

        return Carbon::parse($activity->open_date . ' ' . $time);
    }

    private function activityAccessError(Activity $activity): ?string
    {
        $now = now();
        $isVisible = ((int) ($activity->visible ?? 0) === 1);
        $isOpen = ((int) ($activity->is_open ?? 0) === 1);
        $isAccepting = ((int) ($activity->accepting ?? 1) === 1);
        $openAt = $this->getActivityOpenAt($activity);
        $due = $activity->due_date ? Carbon::parse($activity->due_date) : null;
        $duePassed = $due && $now->greaterThan($due);

        if (!$isVisible) {
            return 'This activity is not published yet.';
        }

        if (!$isOpen) {
            if ($openAt && $now->lessThan($openAt)) {
                return 'This activity opens on ' . $openAt->format('F j, Y \\a\\t h:i A') . '.';
            }

            if ($duePassed && (int) ($activity->accepting ?? 0) === 0) {
                return 'The deadline for this activity has passed.';
            }

            return 'This activity is currently closed.';
        }

        if (!$isAccepting) {
            if ($duePassed) {
                return 'The deadline for this activity has passed.';
            }

            return 'This activity is currently closed.';
        }

        return null;
    }

    private function parseRemoveMediaIds($value): array
    {
        if (is_string($value)) {
            $value = trim($value);
            if ($value === '') {
                return [];
            }

            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $value = $decoded;
            } else {
                $value = preg_split('/\s*,\s*/', $value, -1, PREG_SPLIT_NO_EMPTY);
            }
        }

        if (!is_array($value)) {
            return [];
        }

        $ids = [];
        foreach ($value as $id) {
            if (is_numeric($id)) {
                $ids[] = (int) $id;
            }
        }

        return array_values(array_unique($ids));
    }
    /**
     * Handles ALL student submissions
     * One submission can contain: essay, files, or both
     */
    public function submit(Request $request, Activity $activity)
{
    // dd([
    //     'HIT_SUBMIT' => true,
    //     'method'    => $request->method(),
    //     'has_files' => $request->hasFile('files'),
    //     'files'     => $request->file('files'),
    //     'all'       => $request->all(),
    // ]);

    $student = Auth::user()->student;
    if (!$student) {
        return back()->with('error', 'Student account not found.');
    }

    $this->ensureStudentOwnsActivity($student, $activity);

    $accessError = $this->activityAccessError($activity);
    if ($accessError) {
        return back()->with('error', $accessError);
    }

    $due = $activity->due_date ? Carbon::parse($activity->due_date) : null;
    $duePassed = $due && now()->greaterThan($due);

    $maxAttempts = (int) ($activity->max_attempt ?? 0);
    if ($maxAttempts > 0) {
        $attemptCount = ActivitySubmission::withTrashed()
            ->where('activity_id', $activity->id)
            ->where('student_id', $student->id)
            ->count();

        if ($attemptCount >= $maxAttempts) {
            return back()->with('error', 'You have reached the maximum number of attempts for this activity.');
        }
    }

    $timingStatus = $duePassed ? 'late' : 'on_time';

    // ================= VALIDATION =================
    $rules = [];

    if ($activity->accept_text == 1) {
        $rules['essay_text'] = 'required|string';
    }

    if ($activity->accept_files == 1) {
        $rules['files'] = 'required';
        $rules['files.*'] = 'file';

        if ($activity->max_file_size) {
            $rules['files.*'] .= '|max:' . ($activity->max_file_size * 1024);
        }

        if ($activity->max_number_files) {
            $rules['files'] .= '|max:' . $activity->max_number_files;
        }
    }

    if ($activity->accept_files == 0 && $activity->accept_text == 0) {
        return back()->with('error', 'This activity is not accepting any type of submission.');
    }

    $request->validate($rules);

    // ================= WORD COUNT VALIDATION =================
    if ($activity->accept_text == 1) {
        $plain = trim(strip_tags($request->essay_text));
        $wordCount = str_word_count($plain);

        if ($activity->min_words && $wordCount < $activity->min_words) {
            return back()->with('error', "Essay must have at least {$activity->min_words} words.");
        }

        if ($activity->max_words && $wordCount > $activity->max_words) {
            return back()->with('error', "Essay cannot exceed {$activity->max_words} words.");
        }
    }

    // ================= TOTAL FILE SIZE VALIDATION =================
    if ($activity->accept_files == 1 && $request->hasFile('files')) {
        $totalSize = 0;

        foreach ($request->file('files') as $file) {
            $totalSize += $file->getSize();
        }
    }

    // ================= CLEAR PREVIOUS LATEST =================
        ActivitySubmission::active()
        ->where('activity_id', $activity->id)
        ->where('student_id', $student->id)
        ->update(['is_latest' => false]);


    // ================= ATTEMPT NUMBER =================
    $attempt = ActivitySubmission::withTrashed()
        ->where('activity_id', $activity->id)
        ->where('student_id', $student->id)
        ->count() + 1;

    try {
        // ================= CREATE SUBMISSION =================
        $submission = ActivitySubmission::create([
            'activity_id'    => $activity->id,
            'student_id'     => $student->id,
            'type'           => 'both',
            'attempt_number' => $attempt,
            'is_latest'      => true,
            'submitted_at'   => now(),
            'timing_status'  => $timingStatus,
            'status'         => 'submitted',
        ]);

        // ================= SAVE ESSAY =================
        if ($activity->accept_text == 1) {
            ActivitySubmissionEssay::create([
                'submission_id' => $submission->id,
                'essay_text'    => $request->essay_text,
                'word_count'    => $wordCount ?? str_word_count(strip_tags($request->essay_text)),
            ]);
        }

        // ================= SAVE FILES =================
        if ($activity->accept_files == 1 && $request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $fileRecord = ActivitySubmissionFile::create([
                    'submission_id' => $submission->id,
                    'file_name'     => $file->getClientOriginalName(),
                    'file_size'     => $file->getSize(),
                ]);

                $fileRecord->addMedia($file)->toMediaCollection('student_files');
            }
        }

        return back()->with('success', 'Your submission has been successfully submitted!');
    } catch (\Exception $e) {
        Log::error("Unified submission failed: " . $e->getMessage());
        return back()->with('error', 'Submission failed. Please contact support or try again.');
    }
}

public function update(Request $request, Activity $activity)
{
    $student = Auth::user()->student;
    if (!$student) {
        return back()->with('error', 'Student account not found.');
    }

    $this->ensureStudentOwnsActivity($student, $activity);

    // LATEST submission (this will become history after edit)
    $latest = ActivitySubmission::where('activity_id', $activity->id)
        ->where('student_id', $student->id)
        ->where('is_latest', true)
        ->first();

    if (!$latest) {
        return back()->with('error', 'No submission found to update.');
    }

    $accessError = $this->activityAccessError($activity);
    if ($accessError) {
        return back()->with('error', $accessError === 'Submission is no longer being accepted.'
            ? 'Editing is no longer allowed.'
            : $accessError);
    }

    $due = $activity->due_date ? Carbon::parse($activity->due_date) : null;
    $duePassed = $due && now()->greaterThan($due);

    $maxAttempts = (int) ($activity->max_attempt ?? 0);
    if ($maxAttempts > 0) {
        $attemptCount = ActivitySubmission::withTrashed()
            ->where('activity_id', $activity->id)
            ->where('student_id', $student->id)
            ->count();

        if ($attemptCount >= $maxAttempts) {
            return back()->with('error', 'You have reached the maximum number of attempts for this activity.');
        }
    }

    $timingStatus = $duePassed ? 'late' : 'on_time';

    // ================= VALIDATION =================
    $rules = [];

    if ($activity->accept_text == 1) {
        $rules['essay_text'] = 'required|string';
    }

    if ($activity->accept_files == 1) {
        $rules['files'] = 'nullable';
        $rules['files.*'] = 'file';

        if ($activity->max_file_size) {
            $rules['files.*'] .= '|max:' . ($activity->max_file_size * 1024);
        }

        if ($activity->max_number_files) {
            $rules['files'] .= '|max:' . $activity->max_number_files;
        }
    }

    $request->validate($rules);

    $removeMediaIds = $this->parseRemoveMediaIds($request->input('remove_file_ids'));
    $keptExistingMedia = [];
    if ($activity->accept_files == 1) {
        foreach ($latest->files as $oldFile) {
            foreach ($oldFile->getMedia('student_files') as $media) {
                if (in_array($media->id, $removeMediaIds, true)) {
                    continue;
                }
                $keptExistingMedia[] = $media;
            }
        }

        if (!$request->hasFile('files') && count($keptExistingMedia) === 0) {
            return back()->with('error', 'Please upload at least one file.');
        }
    }

    // WORD COUNT VALIDATION
    if ($activity->accept_text == 1) {
        $plain = trim(strip_tags($request->essay_text));
        $wordCount = str_word_count($plain);

        if ($activity->min_words && $wordCount < $activity->min_words) {
            return back()->with('error', "Essay must have at least {$activity->min_words} words.");
        }

        if ($activity->max_words && $wordCount > $activity->max_words) {
            return back()->with('error', "Essay cannot exceed {$activity->max_words} words.");
        }
    }

    // NEW ATTEMPT NUMBER
    $attempt = ActivitySubmission::withTrashed()
        ->where('activity_id', $activity->id)
        ->where('student_id', $student->id)
        ->count() + 1;

    \DB::beginTransaction();

    try {

        // OLD latest becomes NOT latest
        $latest->update(['is_latest' => false]);

        // CREATE NEW SUBMISSION ATTEMPT
        $newSubmission = ActivitySubmission::create([
            'activity_id'    => $activity->id,
            'student_id'     => $student->id,
            'type'           => 'both',
            'attempt_number' => $attempt,
            'is_latest'      => true,
            'submitted_at'   => now(),
            'timing_status'  => $timingStatus,
            'status'         => 'submitted',
        ]);

        // ====== ESSAY HANDLING ======
        if ($activity->accept_text == 1) {
            ActivitySubmissionEssay::create([
                'submission_id' => $newSubmission->id,
                'essay_text'    => $request->essay_text,
                'word_count'    => isset($wordCount)
                    ? $wordCount
                    : str_word_count(strip_tags($request->essay_text)),
            ]);
        }

        // ====== FILE HANDLING ======
        if ($activity->accept_files == 1) {
            foreach ($keptExistingMedia as $media) {
                $clone = ActivitySubmissionFile::create([
                    'submission_id' => $newSubmission->id,
                    'file_name'     => $media->file_name,
                    'file_size'     => $media->size ?? 0,
                ]);

                $clone
                    ->addMedia($media->getPath())
                    ->usingFileName($media->file_name)
                    ->toMediaCollection('student_files');
            }

            if ($request->hasFile('files')) {
                foreach ($request->file('files') as $file) {
                    $fileRecord = ActivitySubmissionFile::create([
                        'submission_id' => $newSubmission->id,
                        'file_name'     => $file->getClientOriginalName(),
                        'file_size'     => $file->getSize(),
                    ]);

                    $fileRecord->addMedia($file)->toMediaCollection('student_files');
                }
            }
        }

        \DB::commit();

        return back()->with('success', 'Your submission has been updated successfully!');

    } catch (\Exception $e) {

        \DB::rollBack();
        Log::error("Unified update failed: " . $e->getMessage());

        return back()->with('error', 'Update failed. Please try again.');
    }
}

public function remove(Activity $activity)
{
    $student = Auth::user()->student;

    if (!$student) {
        return back()->with('error', 'Student account not found.');
    }

    $this->ensureStudentOwnsActivity($student, $activity);

    $accessError = $this->activityAccessError($activity);
    if ($accessError) {
        return back()->with('error', $accessError === 'Submission is no longer being accepted.'
            ? 'Removing submission is no longer allowed.'
            : $accessError);
    }

    // Get latest ACTIVE submission
    $latest = ActivitySubmission::active()
        ->where('activity_id', $activity->id)
        ->where('student_id', $student->id)
        ->where('is_latest', true)
        ->first();

    if (!$latest) {
        return back()->with('error', 'No submission found to remove.');
    }

    \DB::beginTransaction();

    try {

        /* ================= DELETE ESSAY (IF ANY) ================= */
        ActivitySubmissionEssay::where('submission_id', $latest->id)->delete();

        /* ================= DELETE FILES & MEDIA ================= */
        foreach ($latest->files as $fileRecord) {
            $fileRecord->clearMediaCollection('student_files');
            $fileRecord->delete();
        }

        /* ================= MARK + SOFT DELETE ================= */
        $latest->update([
            'is_latest' => false,
        ]);

        // 🔥 THIS IS THE IMPORTANT PART
        $latest->delete(); // soft delete (sets deleted_at)

        // ❌ DO NOT promote previous attempts
        // Student intentionally has NO active submission

        \DB::commit();

        return back()->with('success', 'Submission removed successfully.');

    } catch (\Exception $e) {

        \DB::rollBack();
        \Log::error("Remove submission failed: " . $e->getMessage());

        return back()->with('error', 'Failed to remove submission.');
    }
}











}
