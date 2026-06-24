<?php

namespace App\Models;

use App\Models\Concerns\LogsAdminActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Tag extends Model
{
    use LogsAdminActivity;
    protected $fillable = [
        'name',
        'slug',
        'category',
    ];

    public function quizzes(): MorphToMany
    {
        return $this->morphedByMany(Quiz::class, 'taggable')
            ->withPivot(['relevance_level', 'coverage_ratio'])
            ->withTimestamps();
    }

    public function questions(): MorphToMany
    {
        return $this->morphedByMany(Question::class, 'taggable')
            ->withPivot(['relevance_level', 'coverage_ratio'])
            ->withTimestamps();
    }

    public function questionBankItems(): MorphToMany
    {
        return $this->morphedByMany(QuestionBankItem::class, 'taggable')
            ->withPivot(['relevance_level', 'coverage_ratio'])
            ->withTimestamps();
    }

    public function activities(): MorphToMany
    {
        return $this->morphedByMany(Activity::class, 'taggable')
            ->withPivot(['relevance_level', 'coverage_ratio'])
            ->withTimestamps();
    }

    public function evaluations(): MorphToMany
    {
        return $this->morphedByMany(Evaluation::class, 'taggable')
            ->withPivot(['relevance_level', 'coverage_ratio'])
            ->withTimestamps();
    }

    public function lessons(): MorphToMany
    {
        return $this->morphedByMany(LessonModel::class, 'taggable')
            ->withPivot(['relevance_level', 'coverage_ratio'])
            ->withTimestamps();
    }

    public function reviewers(): MorphToMany
    {
        return $this->morphedByMany(Reviewer::class, 'taggable')
            ->withPivot(['relevance_level', 'coverage_ratio'])
            ->withTimestamps();
    }

    public function h5pReviewers(): MorphToMany
    {
        return $this->morphedByMany(H5pReviewer::class, 'taggable')
            ->withPivot(['relevance_level', 'coverage_ratio'])
            ->withTimestamps();
    }
}
