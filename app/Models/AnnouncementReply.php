<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnnouncementReply extends Model
{
    use HasFactory;

    protected $fillable = [
        'announcement_id',
        'user_id',
        'reply',
    ];

    public function announcement()
    {
        return $this->belongsTo(AnnouncementModel::class, 'announcement_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function student()
    {
        return $this->belongsTo(StudentModel::class, 'user_id', 'user_id');
    }
    public function teacher()
    {
        return $this->belongsTo(TeacherModel::class, 'teacher_id');
    }


}
