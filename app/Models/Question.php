<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Question extends Model
{
    use HasFactory;

    protected $fillable = [
        'order',
        'page_hint',
        'type',
        'text',
        'option',
        'setting',
        'answer_key',
        'required',
        'points',
    ];

    protected $casts = [
        'option' => 'array',
        'setting' => 'array',
        'answer_key' => 'array',
        'required' => 'boolean',
        'page_hint' => 'integer',
        'points' => 'integer',
    ];

    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }
    public function answers()
    {
        return $this->hasMany(Answer::class);
    }

    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable')
            ->withPivot(['relevance_level', 'coverage_ratio'])
            ->withTimestamps();
    }
}
