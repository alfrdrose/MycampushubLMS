<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class LessonModel extends Model implements HasMedia
{
    use InteractsWithMedia;
    protected $table = 'lessons';
    protected $fillable = [
        'lesson_title',
        'section_model_id',
        'subject_model_id',
        'description',
        'grading_period',
        'visible',
        'open_date',
        'open_time',
    ];

    protected $casts = [
        'visible' => 'boolean',
        'open_date' => 'date',
    ];

    public function section()
    {
        return $this->belongsTo(SectionModel::class, 'section_model_id');
    }

    public function subject()
    {
        return $this->belongsTo(SubjectModel::class, 'subject_model_id');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('attachments');
    }

    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable')
            ->withPivot(['relevance_level', 'coverage_ratio'])
            ->withTimestamps();
    }
}
