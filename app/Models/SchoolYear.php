<?php

namespace App\Models;

use App\Models\Concerns\LogsAdminActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class SchoolYear extends Model
{
    use HasFactory, LogsAdminActivity;

    protected static function booted(): void
    {
        static::creating(function (self $schoolYear): void {
            if ($schoolYear->teacher_audit_enabled === null) {
                $schoolYear->teacher_audit_enabled = true;
            }
        });
    }

    protected $fillable = [
        'start_year',
        'end_year',
        'is_active',
        'is_archived',
        'teacher_audit_enabled',
        'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_archived' => 'boolean',
            'teacher_audit_enabled' => 'boolean',
            'archived_at' => 'datetime',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_archived', false);
    }

    public function scopeArchived(Builder $query): Builder
    {
        return $query->where('is_archived', true);
    }

    public static function activeYear(): ?self
    {
        return static::query()->active()->first();
    }

    public static function activeId(): ?int
    {
        $activeId = static::query()->active()->value('id');

        return $activeId === null ? null : (int) $activeId;
    }

    public function activate(): void
    {
        DB::transaction(function (): void {
            static::query()->where('is_active', true)->update(['is_active' => false]);

            $this->forceFill([
                'is_active' => true,
                'is_archived' => false,
                'archived_at' => null,
            ])->save();
        });
    }

    public function archive(): void
    {
        DB::transaction(function (): void {
            $this->forceFill([
                'is_archived' => true,
                'is_active' => false,
                'archived_at' => now(),
            ])->save();
        });
    }

    public function toggleTeacherAudit(): void
    {
        $this->forceFill([
            'teacher_audit_enabled' => ! $this->teacher_audit_enabled,
        ])->save();
    }

    public function sections()
    {
        return $this->hasMany(SectionModel::class);
    }

    public function enrollments()
    {
        return $this->hasMany(StudentEnrollment::class);
    }

    public function getLabelAttribute(): string
    {
        return $this->start_year . ' - ' . $this->end_year;
    }
}
