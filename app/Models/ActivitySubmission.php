<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class ActivitySubmission extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;
    use SoftDeletes;

    protected $fillable = [
        'activity_id',
        'student_id',
        'type',
        'attempt_number',
        'is_latest',
        'submitted_at',
        'timing_status',
        'status',
        'score',
        'feedback',
    ];

    protected $casts = [
        'score' => 'integer',
        'is_latest' => 'boolean',
    ];

    protected $dates = [
        'submitted_at',
        'deleted_at',
    ];

    /* ==================== SCOPES ==================== */

    public function scopeActive($query)
    {
        return $query->whereNull('deleted_at');
    }

    public function scopeLatestValid($query)
{
    return $query
        ->where('is_latest', 1)
        ->withoutTrashed();
}


    /* ==================== RELATIONSHIPS ==================== */

    public function activity()
    {
        return $this->belongsTo(Activity::class);
    }

    public function student()
    {
        return $this->belongsTo(StudentModel::class, 'student_id');
    }

    public function essay()
    {
        return $this->hasOne(ActivitySubmissionEssay::class, 'submission_id');
    }

    public function files()
    {
        return $this->hasMany(ActivitySubmissionFile::class, 'submission_id');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('submission_files');
    }
}
