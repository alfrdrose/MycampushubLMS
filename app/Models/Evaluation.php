<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Evaluation extends Model
{
    use HasFactory;

    protected $fillable = [
    'section_subject_id',
    'title',
    'description',
    'time_posted',
    'visible',
    'grading_period',
    'classwork_type',
    'total_score',
];


    // Relationship: Evaluation belongs to SectionSubject
    public function sectionSubject()
    {
        return $this->belongsTo(SectionSubject::class, 'section_subject_id');
    }
    public function scores()
    {
        return $this->hasMany(EvaluationScore::class, 'evaluation_id');
    }

    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable')
            ->withPivot(['relevance_level', 'coverage_ratio'])
            ->withTimestamps();
    }

}
