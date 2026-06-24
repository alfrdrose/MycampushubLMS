<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceScore extends Model
{
    protected $fillable = [
        'student_id',
        'section_subject_id',
        'grading_period',
        'present',
        'absent',
        'excused',
        'late',
        'score',
    ];

    // student_models table
    public function student()
    {
        return $this->belongsTo(StudentModel::class, 'student_id');
    }

    // section_subject pivot
    public function sectionSubject()
    {
        return $this->belongsTo(SectionSubject::class, 'section_subject_id');
    }
}
