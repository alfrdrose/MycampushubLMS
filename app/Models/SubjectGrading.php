<?php

namespace App\Models;

use App\Models\Concerns\LogsAdminActivity;
use Illuminate\Database\Eloquent\Model;

class SubjectGrading extends Model
{
    use LogsAdminActivity;
    protected $table = 'subject_grading';

    protected $fillable = [
        'subject_id',
        'classwork_type',
        'percentage'
    ];

    public function subject()
    {
        return $this->belongsTo(SubjectModel::class, 'subject_id');
    }
    public function attendanceTypes()
    {
        return $this->hasMany(AttendanceType::class, 'subject_grading_id');
    }

}
