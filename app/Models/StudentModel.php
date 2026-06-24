<?php

namespace App\Models;

use App\Models\Concerns\LogsAdminActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class StudentModel extends Model implements HasMedia
{
    /** @use HasFactory<\Database\Factories\StudentModelFactory> */
    use HasFactory, LogsAdminActivity;
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'guardian_email',
        'grade',
        'section_id',
        'user_id',
    ];

    /* =========================================================
     | Media Library
     ========================================================= */

    /**
     * Register media collections
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('profile_image')
            ->singleFile() // only ONE profile picture
            ->useDisk('public');
    }

    /**
     * Register media conversions (optional)
     */
    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(300)
            ->height(300)
            ->sharpen(10)
            ->nonQueued();
    }

    /**
     * Get profile image URL (safe fallback)
     */
    public function profileImageUrl(): string
    {
        return $this->getFirstMediaUrl('profile_image')
            ?: asset('assets/images/admin.png');
    }

    /* =========================================================
     | Relationships
     ========================================================= */

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function section()
    {
        return $this->belongsTo(SectionModel::class, 'section_id');
    }

    public function enrollments()
    {
        return $this->hasMany(StudentEnrollment::class, 'student_id');
    }

    public function currentEnrollment()
    {
        return $this->hasOne(StudentEnrollment::class, 'student_id')->where('is_current', true);
    }

    public function tasks()
    {
        return $this->section
            ? $this->section->tasks()
            : collect();
    }

    public function submissions()
    {
        return $this->hasMany(ActivitySubmission::class, 'student_id');
    }

    public function grades()
    {
        return $this->hasMany(Grade::class, 'student_id');
    }

    public function evaluationScores()
    {
        return $this->hasMany(EvaluationScore::class, 'student_id');
    }

    public function attendanceRecords()
    {
        return $this->hasMany(AttendanceRecord::class, 'student_id');
    }

    /**
     * All submissions of a student
     */
    public function activitySubmissions()
    {
        return $this->hasMany(ActivitySubmission::class, 'student_id');
    }

    /**
     * Essay submissions (through activity submissions)
     */
    public function essaySubmissions()
    {
        return $this->hasManyThrough(
            ActivitySubmissionEssay::class,
            ActivitySubmission::class,
            'student_id',     // FK on ActivitySubmission
            'submission_id',  // FK on ActivitySubmissionEssay
            'id',
            'id'
        );
    }
}
