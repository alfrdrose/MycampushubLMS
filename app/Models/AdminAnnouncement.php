<?php

namespace App\Models;

use App\Models\Concerns\LogsAdminActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminAnnouncement extends Model
{
    use HasFactory, LogsAdminActivity;

    protected $fillable = [
        'title',
        'description',
        'image',
        'date_posted',
        'school_year_id',
    ];

    protected $casts = [
        'date_posted' => 'datetime',
    ];

    public function schoolYear()
    {
        return $this->belongsTo(SchoolYear::class);
    }

    public function scopeForSchoolYear(Builder $query, ?int $schoolYearId): Builder
    {
        if ($schoolYearId === null) {
            return $query->whereRaw('0 = 1');
        }

        return $query->where('school_year_id', $schoolYearId);
    }

    public function scopeForActiveSchoolYear(Builder $query): Builder
    {
        return $query->forSchoolYear(SchoolYear::activeId());
    }
}
