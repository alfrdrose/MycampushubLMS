<?php

namespace App\Models;

use App\Models\Concerns\LogsAdminActivity;
use Illuminate\Database\Eloquent\Model;

class SectionSubject extends Model
{
    use LogsAdminActivity;
    protected $table = 'section_subject'; // the actual table name

    protected $fillable = [
        'section_model_id',
        'subject_model_id',
    ];

    public function announcements()
    {
        return $this->hasMany(AnnouncementModel::class, 'section_subject_id');
    }

    public function section()
    {
        return $this->belongsTo(SectionModel::class, 'section_model_id');
    }

    public function subject()
    {
        return $this->belongsTo(SubjectModel::class, 'subject_model_id');
    }
    public function activities()
    {
        return $this->hasMany(\App\Models\Activity::class, 'section_subject_id');
    }
    public function grades()
    {
        return $this->hasMany(Grade::class);
    }
    public function attendanceTypes()
    {
        return $this->hasMany(AttendanceType::class, 'section_subject_id');
    }
    public function evaluations()
    {
        return $this->hasMany(Evaluation::class, 'section_subject_id');
    }

    

}
