<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentEnrollment extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'school_year_id',
        'section_id',
        'grade_level',
        'status',
        'is_current',
        'previous_enrollment_id',
        'ended_at',
    ];

    protected function casts(): array
    {
        return [
            'is_current' => 'boolean',
            'ended_at' => 'datetime',
        ];
    }

    public function student()
    {
        return $this->belongsTo(StudentModel::class, 'student_id');
    }

    public function schoolYear()
    {
        return $this->belongsTo(SchoolYear::class);
    }

    public function section()
    {
        return $this->belongsTo(SectionModel::class, 'section_id');
    }

    public function previousEnrollment()
    {
        return $this->belongsTo(self::class, 'previous_enrollment_id');
    }
}
