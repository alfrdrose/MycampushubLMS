<?php

namespace App\Models;

use App\Models\Concerns\LogsAdminActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SectionModel extends Model
{
    /** @use HasFactory<\Database\Factories\SectionModelFactory> */
    use HasFactory, LogsAdminActivity;
    protected $fillable = [
        'section_name',
        'section_rank',
        'grade_level',
        'start_year',
        'end_year',
        'school_year_id',
        'teacher_id'
    ];

    public function schoolYear()
    {
        return $this->belongsTo(SchoolYear::class);
    }

    public function students()
    {
        return $this->hasMany(StudentModel::class, 'section_id');
    }

    public function subjects()
    {
        return $this->belongsToMany(
            SubjectModel::class,
            'section_subject',
            'section_model_id',
            'subject_model_id'
        )
            ->withPivot('days', 'start_time', 'end_time')
            ->withTimestamps();
    }
    public function assignedTeachers()
    {
        return $this->hasMany(SubjectTeacher::class, 'section_model_id');
    }

    public function adviser()
    {
        return $this->hasOne(TeacherModel::class, 'section', 'id');
    }


    public function teacher()
    {
        return $this->belongsTo(TeacherModel::class, 'teacher_id');
    }


    public function tasks()
    {
        return $this->subjects->flatMap->tasks;
    }

    public function quizzes()
    {
        return $this->hasMany(Quiz::class, 'section_model_id');
    }
}
