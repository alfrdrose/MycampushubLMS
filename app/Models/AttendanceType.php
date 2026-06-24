<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceType extends Model
{
    protected $fillable = [
        'section_subject_id',
        'grading_period',
        'subject_grading_id',
    ];

    public function sectionSubject()
    {
        return $this->belongsTo(SectionSubject::class, 'section_subject_id');
    }

    public function subjectGrading()
    {
        return $this->belongsTo(SubjectGrading::class, 'subject_grading_id');
    }
}

