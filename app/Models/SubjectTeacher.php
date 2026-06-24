<?php

namespace App\Models;

use App\Models\Concerns\LogsAdminActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubjectTeacher extends Model
{
    use HasFactory, LogsAdminActivity;

    protected $table = 'subject_teacher';

    protected $fillable = [
        'subject_model_id',
        'teacher_model_id',
        'section_model_id',
    ];

    public function subject()
    {
        return $this->belongsTo(SubjectModel::class, 'subject_model_id');
    }

    public function teacher()
    {
        return $this->belongsTo(TeacherModel::class, 'teacher_model_id');
    }

    public function section()
    {
        return $this->belongsTo(SectionModel::class, 'section_model_id');
    }
    public function schedule()
    {
        return $this->belongsToMany(SubjectModel::class, 'section_subject', 'section_id', 'subject_id')
                    ->withPivot('days', 'start_time', 'end_time')
                    ->wherePivot('subject_id', $this->subject_model_id);
    }
}
