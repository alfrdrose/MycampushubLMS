<?php

namespace App\Http\Controllers;

use App\Models\NotificationSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;

class NotificationSettingController extends Controller
{

    public function forceSendWeeklyAttendance(): JsonResponse
    {
        try {
            Artisan::call('attendance:send-guardian-emails', [
                '--force' => true,
            ]);
        } catch (\Throwable $e) {
            Log::error('Force weekly attendance email failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed. Check logs.',
            ], 500);
        }

        return response()->json([
            'status' => 'ok',
            'message' => 'Weekly attendance email sent (forced).',
        ]);
    }

    public function show()
    {
        $settings = NotificationSetting::first();

        return response()->json([
            'enabled' => $settings?->enabled ?? false,
            'start_day' => $settings?->start_day,
            'end_day' => $settings?->end_day,
            'send_out_time' => $settings?->send_out_time,
        ]);
    }
    public function store(Request $request)
{
    // ✅ Validate ONLY if notifications are enabled
    if ($request->boolean('enabled')) {
        $request->validate([
            'start_day' => 'required',
            'end_day' => 'required',
            'send_out_time' => 'required',
        ]);
    }

    // ✅ Always overwrite the SINGLE settings row
    NotificationSetting::updateOrCreate(
        ['key' => 'email_notifications'], // singleton key
        [
            'enabled' => $request->boolean('enabled'),

            // Only store schedule data when enabled
            'start_day' => $request->boolean('enabled')
                ? $request->start_day
                : null,

            'end_day' => $request->boolean('enabled')
                ? $request->end_day
                : null,

            'send_out_time' => $request->boolean('enabled')
                ? $request->send_out_time
                : null,
        ]
    );

    return response()->json([
        'message' => 'Notification settings saved successfully',
    ]);
}



}
