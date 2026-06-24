<?php

namespace App\Models;

use App\Models\Concerns\LogsAdminActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class TeacherModel extends Model implements HasMedia
{
    /** @use HasFactory<\Database\Factories\TeacherModelFactory> */
    use HasFactory, LogsAdminActivity;
    use HasFactory, InteractsWithMedia;

    protected $table = 'teacher_models';

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'faculty',
        'section',
        'user_id',
        'section_id',
    ];

    /**
     * Register Spatie media collections
     */
    public function registerMediaCollections(): void
    {
        $this
            ->addMediaCollection('profile_picture')
            ->singleFile()
            ->useDisk('public');
    }

    /**
     * The user that owns this teacher profile
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Subjects handled by the teacher
     */
    public function subjects()
    {
        return $this->belongsToMany(
            SubjectModel::class,
            'subject_teacher',
            'teacher_model_id',
            'subject_model_id'
        )
        ->withPivot('section_model_id')
        ->withTimestamps();
    }

    /**
     * Tasks created by the teacher
     */
    public function tasks()
    {
        return $this->hasMany(TaskModel::class, 'teacher_model_id');
    }

    /**
     * Sections handled by the teacher
     */
    public function sections()
    {
        return $this->hasMany(SectionModel::class, 'id');
    }

    /**
     * Helper: get profile image URL with fallback
     */
    public function profileImageUrl(): string
    {
        return $this->getFirstMediaUrl('profile_picture')
            ?: asset('assets/images/admin.png');
    }
}
