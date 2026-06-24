<?php

namespace App\Models;

use App\Enums\SubmissionStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubmissionModel extends Model
{
    /** @use HasFactory<\Database\Factories\SubmissionModelFactory> */
    use HasFactory;
    protected $fillable = [
        'task_model_id',
        'student_model_id',
        'content',
        'file_path',
        'submitted_at',
        'status',
        'grade',
    ];
    
    protected $casts = [
        'submitted_at' => 'datetime',
        'status' => SubmissionStatus::class,
    ];

    public function task()
    {
        return $this->belongsTo(TaskModel::class, 'task_model_id');
    }

    public function student()
    {
        return $this->belongsTo(StudentModel::class, 'student_model_id');
    }
}
