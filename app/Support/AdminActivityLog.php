<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use App\Models\AdminAnnouncement;
use App\Models\NotificationSetting;
use App\Models\SchoolYear;
use App\Models\SectionModel;
use App\Models\SectionSubject;
use App\Models\StudentModel;
use App\Models\SubjectGrading;
use App\Models\SubjectModel;
use App\Models\SubjectTeacher;
use App\Models\Tag;
use App\Models\TeacherModel;
use App\Models\User;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;

class AdminActivityLog
{
    public static function moduleMap(): array
    {
        return [
            'User' => ['label' => 'User Accounts', 'class' => User::class],
            'StudentModel' => ['label' => 'Student Records', 'class' => StudentModel::class],
            'TeacherModel' => ['label' => 'Teacher Records', 'class' => TeacherModel::class],
            'SubjectModel' => ['label' => 'Subject Catalog', 'class' => SubjectModel::class],
            'SubjectGrading' => ['label' => 'Grading Configuration', 'class' => SubjectGrading::class],
            'SectionModel' => ['label' => 'Section Management', 'class' => SectionModel::class],
            'SectionSubject' => ['label' => 'Section-Subject Links', 'class' => SectionSubject::class],
            'SubjectTeacher' => ['label' => 'Teacher Assignments', 'class' => SubjectTeacher::class],
            'Tag' => ['label' => 'Tag Management', 'class' => Tag::class],
            'AdminAnnouncement' => ['label' => 'Announcements', 'class' => AdminAnnouncement::class],
            'NotificationSetting' => ['label' => 'Notification Settings', 'class' => NotificationSetting::class],
            'SchoolYear' => ['label' => 'School Year Management', 'class' => SchoolYear::class],
        ];
    }

    public static function moduleLabels(): array
    {
        $labels = [];

        foreach (self::moduleMap() as $key => $details) {
            $labels[$key] = $details['label'];
        }

        return $labels;
    }

    public static function shouldLog(): bool
    {
        if (app()->runningInConsole()) {
            return false;
        }

        if (! auth()->check()) {
            return false;
        }

        $user = auth()->user();
        $isAdmin = method_exists($user, 'hasRole')
            ? $user->hasRole('admin')
            : (method_exists($user, 'isAdmin') ? $user->isAdmin() : false);

        if (! $isAdmin) {
            return false;
        }

        $request = request();
        if (! $request) {
            return false;
        }

        return $request->routeIs('admin.*')
            || $request->is('admin')
            || $request->is('admin/*');
    }

    public static function options(): LogOptions
    {
        return LogOptions::defaults()->useLogName('admin');
    }

    public static function tap(Activity $activity): void
    {
        $user = auth()->user();
        if (! $user) {
            return;
        }

        $properties = $activity->properties ?? collect();
        $activity->properties = $properties->merge([
            'username' => $user->name,
        ]);
    }

    public static function log(string $event, Model $subject, array $properties = []): void
    {
        if (! self::shouldLog()) {
            return;
        }

        $user = auth()->user();
        $properties['username'] = $properties['username'] ?? $user?->name;

        activity()
            ->useLog('admin')
            ->event($event)
            ->performedOn($subject)
            ->withProperties($properties)
            ->log($event);
    }
}
