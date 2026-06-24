<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReviewerInteraction extends Model
{
    protected $fillable = [
        'reviewer_id',
        'student_id',
        'status',
        'score',
        'completed_at',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
        'score' => 'integer',
    ];

    public function reviewer()
    {
        return $this->belongsTo(Reviewer::class);
    }

    public function student()
    {
        return $this->belongsTo(StudentModel::class, 'student_id');
    }
}
