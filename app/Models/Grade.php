<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Grade extends Model
{
    protected $fillable = [
        'grade',
        'student_id',
        'section_subject_id',
        'grading_period',
    ];

    /**
     * Student who owns this grade
     */
    public function student()
    {
        return $this->belongsTo(StudentModel::class);
    }

    /**
     * Section + Subject pivot
     */
    public function sectionSubject()
    {
        return $this->belongsTo(SectionSubject::class);
    }
}
