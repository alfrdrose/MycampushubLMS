<?php

namespace App\Models;

use App\Enums\Role;
use App\Enums\UserStatus;
use App\Models\Concerns\LogsAdminActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property \App\Models\TeacherModel|null $teacher
 * @property \App\Models\StudentModel|null $student
 */
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles, LogsAdminActivity;

    protected static function booted(): void
    {
        static::deleting(function (self $user): void {
            $user->teacher()->delete();
            $user->student()->delete();
        });
    }

    protected $fillable = [
        'name',
        'email',
        'password',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => Role::class,
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === Role::Admin;
    }

    public function isTeacher(): bool
    {
        return $this->role === Role::Teacher;
    }

    public function isStudent(): bool
    {
        return $this->role === Role::Student;
    }

    public function hasInactiveTeacherStatus(): bool
    {
        return $this->hasRole('teacher')
            && $this->statusValue() === UserStatus::Inactive->value;
    }

    public function statusValue(): string
    {
        return $this->status instanceof UserStatus
            ? $this->status->value
            : (string) $this->status;
    }

    public function teacher()
    {
        return $this->hasOne(TeacherModel::class);
    }

    public function student()
    {
        return $this->hasOne(StudentModel::class);
    }
}
