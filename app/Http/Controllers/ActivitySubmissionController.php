<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\ActivitySubmission;
use App\Models\ActivitySubmissionEssay;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;


class ActivitySubmissionController extends Controller
{
    /**
     * Submit an essay
     */
    public function submitEssay(Request $request, Activity $activity)
{
    $request->validate([
        'essay_text' => 'required|string',
    ], [
        'essay_text.required' => 'You must write something before submitting.'
    ]);

    $student = Auth::user()->student;
    if (!$student) {
        abort(403, 'No student record linked to your account.');
    }

    $now = Carbon::now();
    $due = $activity->due_date ? Carbon::parse($activity->due_date) : null;
    $timingStatus = ($due && $now->greaterThan($due)) ? 'late' : 'on_time';

    // Mark previous attempts as not latest
    ActivitySubmission::where('activity_id', $activity->id)
        ->where('student_id', $student->id)
        ->update(['is_latest' => false]);

    // Calculate next attempt number including soft deleted submissions
    $attempt = ActivitySubmission::withTrashed()
        ->where('activity_id', $activity->id)
        ->where('student_id', $student->id)
        ->count() + 1;

    try {
        $submission = ActivitySubmission::create([
            'activity_id'    => $activity->id,
            'student_id'     => $student->id,
            'type'           => 'essay',
            'attempt_number' => $attempt,
            'is_latest'      => true,
            'submitted_at'   => now(),
            'timing_status'  => $timingStatus,
            'status'         => 'submitted',
        ]);

        ActivitySubmissionEssay::create([
            'submission_id' => $submission->id,
            'essay_text'    => $request->essay_text,
            'word_count'    => str_word_count(strip_tags($request->essay_text)),
        ]);

        return back()->with('success', 'Your essay has been submitted.');
    } catch (\Exception $e) {
        Log::error("Essay submission failed: " . $e->getMessage());
        return back()->with('error', 'Submission failed. Check logs.');
    }
}




   public function deleteEssay(ActivitySubmission $submission)
{
    $student = Auth::user()->student;
    if ($submission->student_id !== $student->id) {
        abort(403, 'Unauthorized');
    }

    try {
        // Soft delete essay and submission
        $submission->essay()->delete();  // Soft delete ActivitySubmissionEssay
        $submission->delete();           // Soft delete submission

        // Set latest submission if any
        $latest = ActivitySubmission::where('activity_id', $submission->activity_id)
            ->where('student_id', $student->id)
            ->whereNull('deleted_at')
            ->latest('attempt_number')
            ->first();

        if ($latest) {
            $latest->update(['is_latest' => true]);
        }

        return back()->with('success', 'Your submission has been removed.');
    } catch (\Exception $e) {
        Log::error("Failed to delete essay submission: " . $e->getMessage());
        return back()->with('error', 'Failed to delete submission.');
    }
}



    // Handle the update
    public function updateEssay(Request $request, ActivitySubmission $submission)
{
    $request->validate([
        'essay_text' => 'required|string',
    ], [
        'essay_text.required' => 'You must write something before submitting.'
    ]);

    $student = Auth::user()->student;
    if (!$student || $submission->student_id !== $student->id) {
        abort(403, 'Unauthorized');
    }

    $now = Carbon::now();
    $due = $submission->activity->due_date ? Carbon::parse($submission->activity->due_date) : null;
    $timingStatus = ($due && $now->greaterThan($due)) ? 'late' : 'on_time';

    // Only update submission status and timestamp
    $submission->update([
        'timing_status' => $timingStatus,
        'status' => 'submitted',
    ]);
    $submission->touch();

    // Update essay text
    $submission->essay()->update([
        'essay_text' => $request->essay_text,
        'word_count' => str_word_count(strip_tags($request->essay_text)),
    ]);

    return back()->with('success', 'Your submission has been updated.');
}






    /**
     * Submit files
     */
    

}
