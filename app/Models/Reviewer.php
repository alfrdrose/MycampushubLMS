<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\TeacherModel;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reviewer extends Model
{
    protected $fillable = [
        'title',
        'learning_objective',
        'mini_lesson',
        'mini_quiz',
        'source_classwork_type',
        'source_classwork_id',
        'created_by_teacher_id',
    ];

    protected $casts = [
        'mini_quiz' => 'array',
    ];

    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable')
            ->withPivot(['relevance_level', 'coverage_ratio'])
            ->withTimestamps();
    }

    public function interactions()
    {
        return $this->hasMany(ReviewerInteraction::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(TeacherModel::class, 'created_by_teacher_id');
    }
}
