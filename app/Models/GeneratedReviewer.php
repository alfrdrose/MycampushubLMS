<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GeneratedReviewer extends Model
{
    protected $table = 'generated_reviewers';

    protected $fillable = [
        'user_id',
        'reviewer_number',
        'data',
    ];

    protected $casts = [
        'data' => 'array',
        'completed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}