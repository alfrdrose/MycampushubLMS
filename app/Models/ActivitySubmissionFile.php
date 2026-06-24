<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class ActivitySubmissionFile extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'submission_id',
        'file_name',
        'file_path',
        'file_size',
    ];

    public function submission()
    {
        return $this->belongsTo(ActivitySubmission::class, 'submission_id');
    }
}
