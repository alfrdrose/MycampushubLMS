<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use App\Models\Activity;
use App\Models\ActivitySubmission;
use App\Models\ActivitySubmissionFile;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ActivityFileSubmissionController extends Controller
{
    /**
     * Initial file submission (creates NEW attempt)
     */
    public function submitFiles(Request $request, Activity $activity)
{
    $request->validate([
        'files.*' => 'required|file|max:10240',
    ]);

    $student = Auth::user()->student;
    if (!$student) {
        return response()->json(['error' => 'No student record found.'], 403);
    }

    $now = Carbon::now();
    $due = $activity->due_date ? Carbon::parse($activity->due_date) : null;
    $timingStatus = ($due && $now->greaterThan($due)) ? 'late' : 'on_time';

    // Mark previous submissions as not latest
    ActivitySubmission::where('activity_id', $activity->id)
        ->where('student_id', $student->id)
        ->update(['is_latest' => false]);

    // Calculate next attempt number including deleted submissions
    $attempt = ActivitySubmission::withTrashed()
        ->where('activity_id', $activity->id)
        ->where('student_id', $student->id)
        ->count() + 1;

    try {
        $submission = ActivitySubmission::create([
            'activity_id'    => $activity->id,
            'student_id'     => $student->id,
            'type'           => 'file',
            'attempt_number' => $attempt,
            'is_latest'      => true,
            'submitted_at'   => now(),
            'timing_status'  => $timingStatus,
            'status'         => 'submitted',
        ]);

        // Save uploaded files
        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $fileRecord = ActivitySubmissionFile::create([
                    'submission_id' => $submission->id,
                    'file_name'     => $file->getClientOriginalName(),
                    'file_size'     => $file->getSize(),
                ]);

                $fileRecord->addMedia($file)->toMediaCollection('student_files');
            }
        }

        return response()->json(['success' => 'Your files have been submitted.']);
    } catch (\Exception $e) {
        \Log::error("File submission failed: " . $e->getMessage());
        return response()->json(['error' => 'Submission failed.'], 500);
    }
}



    /**
     * Edit existing submission (does NOT create new attempt)
     */
    /**
 * Edit existing submission (does NOT create new attempt)
 */
public function updateFiles(Request $request, ActivitySubmission $submission)
{
    $request->validate([
        'new_files.*'     => 'nullable|file|max:10240',
        'removed_files'   => 'nullable|array',
        'removed_files.*' => 'nullable|integer',
    ]);

    $student = Auth::user()->student;
    if (!$student) {
        return back()->with('error', 'No student record found.');
    }

    // Fetch the activity like submitFiles
    $activity = Activity::find($submission->activity_id);
    if (!$activity) {
        return back()->with('error', 'Associated activity not found.');
    }

    $now = Carbon::now();
    $due = $activity->due_date ? Carbon::parse($activity->due_date) : null;
    $timingStatus = ($due && $now->greaterThan($due)) ? 'late' : 'on_time';

    // Mark previous submissions as not latest
    ActivitySubmission::where('activity_id', $activity->id)
        ->where('student_id', $student->id)
        ->update(['is_latest' => false]);

    // Determine next attempt number (including trashed)
    $attempt = ActivitySubmission::withTrashed()
        ->where('activity_id', $activity->id)
        ->where('student_id', $student->id)
        ->count() + 1;

    try {
        // Create a new submission (new attempt)
        $newSubmission = ActivitySubmission::create([
            'activity_id'    => $activity->id,
            'student_id'     => $student->id,
            'type'           => 'file',
            'attempt_number' => $attempt,
            'is_latest'      => true,
            'submitted_at'   => now(),
            'timing_status'  => $timingStatus,
            'status'         => 'submitted',
        ]);

        // First, remove any media marked for removal
        if ($request->removed_files) {
            foreach ($request->removed_files as $mediaId) {
                if ($mediaId) {
                    $media = \Spatie\MediaLibrary\MediaCollections\Models\Media::find($mediaId);
                    if ($media) {
                        $fileRecord = $media->model; // the associated ActivitySubmissionFile
                        $media->delete();            // remove the media file
                        $fileRecord->delete();       // remove the file record
                    }
                }
            }
        }

        // Copy over remaining files from the old submission
        foreach ($submission->files as $fileRecord) {
            // Skip files that were marked as removed
            $mediaIds = $fileRecord->getMedia('student_files')->pluck('id')->toArray();
            if ($request->removed_files) {
                if (count(array_intersect($mediaIds, $request->removed_files)) > 0) {
                    continue;
                }
            }

            $copy = ActivitySubmissionFile::create([
                'submission_id' => $newSubmission->id,
                'file_name'     => $fileRecord->file_name,
                'file_size'     => $fileRecord->file_size,
            ]);

            foreach ($fileRecord->getMedia('student_files') as $media) {
                $copy->addMedia($media->getPath())
                     ->usingName($media->name)
                     ->toMediaCollection('student_files');
            }
        }

        // Add any newly uploaded files
        if ($request->hasFile('new_files')) {
            foreach ($request->file('new_files') as $file) {
                $fileRecord = ActivitySubmissionFile::create([
                    'submission_id' => $newSubmission->id,
                    'file_name'     => $file->getClientOriginalName(),
                    'file_size'     => $file->getSize(),
                ]);
                $fileRecord->addMedia($file)->toMediaCollection('student_files');
            }
        }

        return back()->with('success', 'Submission updated successfully! New attempt recorded.');
    } catch (\Exception $e) {
        \Log::error("File update failed: " . $e->getMessage());
        return back()->with('error', 'Update failed.');
    }
}





public function deleteSubmission(ActivitySubmission $submission)
{
    try {
        // Delete all associated files + media
        foreach ($submission->files as $fileRecord) {
            $fileRecord->clearMediaCollection('student_files');
            $fileRecord->delete();
        }

        // Soft delete the submission
        $submission->delete();

        // ❗ FIX: Do NOT restore previous submissions
        // Just ensure ALL remaining submissions are NOT marked latest
        ActivitySubmission::where('activity_id', $submission->activity_id)
            ->where('student_id', $submission->student_id)
            ->update(['is_latest' => false]);

        return back()->with('success', 'Submission deleted successfully!');
    } catch (\Exception $e) {
        \Log::error("Failed to delete submission: " . $e->getMessage());
        return back()->with('error', 'Failed to delete submission.');
    }
}




}
