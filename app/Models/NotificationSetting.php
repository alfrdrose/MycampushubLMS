<?php

namespace App\Models;

use App\Models\Concerns\LogsAdminActivity;
use Illuminate\Database\Eloquent\Model;

class NotificationSetting extends Model
{
    use LogsAdminActivity;
    protected $fillable = [
        'key',          // 🔥 REQUIRED for singleton
        'enabled',
        'start_day',
        'end_day',
        'send_out_time',
    ];

    protected $casts = [
        'enabled' => 'boolean',
    ];
}
