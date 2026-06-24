<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class H5pReviewer extends Model
{
    use HasFactory;

    protected $fillable = [
        'teacher_id',
        'title',
        'embed_url',
    ];

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(TeacherModel::class, 'teacher_id');
    }

    public function getEmbedUrlAttribute($value)
    {
        if (!$value) {
            return $value;
        }
        return html_entity_decode((string) $value, ENT_QUOTES);
    }

    public function getResizerUrlAttribute(): string
    {
        $embedUrl = $this->embed_url;
        if (!$embedUrl) {
            return '';
        }

        $parts = parse_url($embedUrl);
        if (!$parts || empty($parts['scheme']) || empty($parts['host'])) {
            return '';
        }

        $base = $parts['scheme'] . '://' . $parts['host'];
        if (!empty($parts['port'])) {
            $base .= ':' . $parts['port'];
        }

        $path = $parts['path'] ?? '';
        $basePath = '';
        if ($path !== '') {
            $wpAdminPos = strpos($path, '/wp-admin/');
            if ($wpAdminPos !== false) {
                $basePath = substr($path, 0, $wpAdminPos);
            } else {
                $dir = rtrim(dirname($path), '/');
                $basePath = $dir === '.' ? '' : $dir;
            }
        }

        $base = rtrim($base . $basePath, '/');
        return $base . '/wp-content/plugins/h5p/h5p-php-library/js/h5p-resizer.js';
    }

    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable')
            ->withPivot(['relevance_level', 'coverage_ratio'])
            ->withTimestamps();
    }
}
