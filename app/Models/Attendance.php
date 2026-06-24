<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    protected $fillable = [
        'section_subject_id',
        'day', 
        'date',
        'grading_period'
    ];

    public function records()
    {
        return $this->hasMany(AttendanceRecord::class, 'attendance_id', 'id');
    }

    public function sectionSubject()
    {
        return $this->belongsTo(SectionSubject::class);
    }
}

