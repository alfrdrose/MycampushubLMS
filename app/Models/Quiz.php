<?php

namespace App\Models;

use App\Enums\QuizStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Quiz extends Model
{
    protected $fillable = [
        'title',
        'grading_period',
        'classwork_type',
        'description',
        'questions',
        'setting',
        'section_model_id',
        'subject_model_id',
        'status',
        'time_posted',
        'due_date',
        'open_date',
        'open_time',
        'is_open',
        'visible',
        'accepting',
        'max_attempt',
        'total_score',
    ];

    protected $casts = [
        'questions' => 'array',
        'setting' => 'array',
        'status' => QuizStatus::class,
        'time_posted' => 'datetime',
        'due_date' => 'datetime',
        'is_open' => 'boolean',
        'visible' => 'boolean',
        'accepting' => 'boolean',
    ];

    public function questions()
    {
        return $this->hasMany(Question::class)->orderBy('order');
    }

    public function section()
    {
        return $this->belongsTo(SectionModel::class, 'section_model_id');
    }

    public function subject()
    {
        return $this->belongsTo(SubjectModel::class, 'subject_model_id');
    }

    public function responses()
    {
        return $this->hasMany(Response::class);
    }

    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable')
            ->withPivot(['relevance_level', 'coverage_ratio'])
            ->withTimestamps();
    }

    public function isDraft(): bool
    {
        return $this->status === QuizStatus::DRAFT;
    }

    public function isOpen(): bool
    {
        return $this->status === QuizStatus::OPEN;
    }

    public function isClosed(): bool
    {
        return $this->status === QuizStatus::CLOSED;
    }

    public function isExpired(): bool
    {
        return $this->status === QuizStatus::EXPIRED;
    }

    public function isPublished(): bool
    {
        return $this->status === QuizStatus::PUBLISHED;
    }

    public function isScheduled(): bool
    {
        return $this->status === QuizStatus::SCHEDULED;
    }

    public function isUnpublished(): bool
    {
        return $this->status === QuizStatus::UNPUBLISHED;
    }
    public function subjectGrading()
    {
        return $this->belongsTo(SubjectGrading::class, 'classwork_type');
    }
}
