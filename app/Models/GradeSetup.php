<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GradeSetup extends Model
{
    protected $fillable = [
        'section_subject_id',
        'activity_id',
        'quiz_id',
        'percentage',
        'grading_period',
        'evaluation_id',
    ];

    // Pivot
    public function sectionSubject()
    {
        return $this->belongsTo(SectionSubject::class);
    }

    // Optional relationships
    public function activity()
{
    return $this->belongsTo(Activity::class)
        ->whereColumn('activities.grading_period', 'grade_setups.grading_period');
}



    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }

    /**
     * Helper: determine type
     */
    public function getTypeAttribute(): string
    {
        return $this->activity_id ? 'activity' : 'quiz';
    }

    public function getItemAttribute()
    {
        return $this->activity_id
            ? $this->activity
            : $this->quiz;
    }
}

