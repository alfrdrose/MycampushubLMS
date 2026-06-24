<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivitySubmissionEssay extends Model
{
    use HasFactory;

    protected $fillable = [
        'submission_id',
        'essay_text',
        'word_count',
    ];

    // Relation: Essay belongs to a Submission
    public function submission()
    {
        return $this->belongsTo(ActivitySubmission::class);
    }
}
