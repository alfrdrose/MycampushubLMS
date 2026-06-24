<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Activity extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;
    
    protected $fillable = [
        'title',
        'description',

        'time_posted',
        'due_date',

        'open_date',
        'open_time',
        'is_open',

        'section_subject_id',

        // NEW
        'visible',
        'accepting',
        'accept_files',
        'accept_text',

        'max_number_files',
        'max_attempt',
        'total_score',

        'grading_period',

        'classwork_type',

        'max_words',
        'min_words',
        'max_file_size',
    ];


    protected $casts = [
        'time_posted' => 'datetime',
        'due_date' => 'datetime',
        'visible' => 'boolean',
        'accepting' => 'boolean',
        'accept_files' => 'boolean',
        'accept_text' => 'boolean',
    ];


    
    public function sectionSubject()
    {
        return $this->belongsTo(SectionSubject::class);
    }
     public function registerMediaCollections(): void
    {
        $this->addMediaCollection('attachments')
            ->useDisk('public'); // just this, no singleFile()
    }
    public function submissions()
{
    return $this->hasMany(ActivitySubmission::class)
        ->whereNull('deleted_at');
}


    // Essay submissions only (optional)
    public function essaySubmissions()
    {
        return $this->hasManyThrough(
            ActivitySubmissionEssay::class,
            ActivitySubmission::class,
            'activity_id',     // Foreign key on ActivitySubmission
            'submission_id',   // Foreign key on ActivitySubmissionEssay
            'id',              // Local key on Activity
            'id'               // Local key on ActivitySubmission
        );
    }

    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable')
            ->withPivot(['relevance_level', 'coverage_ratio'])
            ->withTimestamps();
    }
}
