<?php

namespace App\Models;

use App\Models\Concerns\LogsAdminActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubjectModel extends Model
{
    /** @use HasFactory<\Database\Factories\SubjectModelFactory> */
    use HasFactory, LogsAdminActivity;
    protected $fillable = [
        'subject_name', 'grade_level',
    ];
    public function sections()
    {
        return $this->belongsToMany(
            SectionModel::class,
            'section_subject',
            'subject_model_id',
            'section_model_id'
        );
    }

    public function teachers()
    {
        return $this->belongsToMany(
            TeacherModel::class,
            'subject_teacher',
            'subject_model_id',
            'teacher_model_id'
        )->withPivot('section_model_id')
        ->withTimestamps();
    }

    public function tasks()
    {
        return $this->hasMany(TaskModel::class, 'subject_model_id');
    }
    public function gradings()
    {
        return $this->hasMany(SubjectGrading::class, 'subject_id');
    }

}
