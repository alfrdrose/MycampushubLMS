<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnnouncementModel extends Model
{
    use HasFactory;

    // Table name
    protected $table = 'announcements_models';

    // Mass assignable fields
    protected $fillable = [
        'section_subject_id',
        'teacher_id',
        'title',
        'body',
    ];

    /**
     * The teacher who posted the announcement
     */
    public function teacher()
    {
        return $this->belongsTo(\App\Models\TeacherModel::class, 'teacher_id');
    }

    /**
     * The section_subject this announcement belongs to
     */
    public function sectionSubject()
    {
        return $this->belongsTo(SectionSubject::class, 'section_subject_id');
    }

    public function replies()
    {
        return $this->hasMany(AnnouncementReply::class, 'announcement_id');
    }
    

}
