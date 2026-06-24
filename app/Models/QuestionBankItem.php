<?php

namespace App\Models;

use App\Models\Concerns\LogsAdminActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class QuestionBankItem extends Model
{
    use HasFactory, LogsAdminActivity;

    protected $fillable = [
        'created_by_teacher_id',
        'subject_model_id',
        'section_model_id',
        'type',
        'text',
        'option',
        'setting',
        'answer_key',
        'required',
        'default_points',
        'is_archived',
        'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'option' => 'array',
            'setting' => 'array',
            'answer_key' => 'array',
            'required' => 'boolean',
            'default_points' => 'integer',
            'is_archived' => 'boolean',
            'archived_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(TeacherModel::class, 'created_by_teacher_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(SubjectModel::class, 'subject_model_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(SectionModel::class, 'section_model_id');
    }

    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable')
            ->withPivot(['relevance_level', 'coverage_ratio'])
            ->withTimestamps();
    }
}
