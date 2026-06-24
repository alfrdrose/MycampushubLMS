<?php

namespace App\Http\Controllers;

use App\Support\AdminActivityLog;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class AdminLogController extends Controller
{
    public function index(Request $request)
    {
        $moduleMap = AdminActivityLog::moduleMap();

        $module = $request->string('module')->toString();
        $action = $request->string('action')->toString();
        $search = $request->string('search')->toString();

        $activities = Activity::query()
            ->select([
                'id',
                'log_name',
                'subject_type',
                'subject_id',
                'event',
                'description',
                'properties',
                'causer_id',
                'causer_type',
                'created_at',
            ])
            ->where('log_name', 'admin')
            ->with(['causer:id,name'])
            ->latest();

        if ($action !== '') {
            $activities->where('event', $action);
        }

        if ($module !== '' && isset($moduleMap[$module])) {
            $activities->where('subject_type', $moduleMap[$module]['class']);
        }

        if ($search !== '') {
            $activities->where(function ($query) use ($search) {
                $query->where('description', 'like', "%{$search}%")
                    ->orWhere('event', 'like', "%{$search}%")
                    ->orWhere('subject_id', 'like', "%{$search}%")
                    ->orWhere('subject_type', 'like', "%{$search}%")
                    ->orWhere('properties->username', 'like', "%{$search}%")
                    ->orWhere('properties->module', 'like', "%{$search}%")
                    ->orWhereHas('causer', function ($causerQuery) use ($search) {
                        $causerQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $moduleLabels = AdminActivityLog::moduleLabels();

        $activities = $activities
            ->paginate(8)
            ->withQueryString();

        return view('pages.admin.logs', [
            'title' => 'Logs',
            'preloader' => false,
            'darkmode' => true,
            'scrollup' => true,
            'banner' => false,
            'sidebar' => true,
            'activities' => $activities,
            'moduleLabels' => $moduleLabels,
        ]);
    }
}
