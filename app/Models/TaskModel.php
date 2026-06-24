<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskModel extends Model
{
    /** @use HasFactory<\Database\Factories\TaskModelFactory> */
    use HasFactory;
    protected $fillable = [
        'title',
        'description',
        'due_date',
        'subject_model_id',
        'teacher_model_id',
    ];

    protected $casts = [
        'due_date' => 'datetime',
    ];

    public function teacher()
    {
        return $this->belongsTo(TeacherModel::class, 'teacher_model_id');
    }

    public function subject()
    {
        return $this->belongsTo(SubjectModel::class, 'subject_model_id');
    }

    public function submissions()
    {
        return $this->hasMany(SubmissionModel::class, 'task_model_id');
    }
}
