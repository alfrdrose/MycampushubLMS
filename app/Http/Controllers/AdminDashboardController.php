<?php

namespace App\Http\Controllers;

use App\Models\AdminAnnouncement;
use App\Models\SchoolYear;
use App\Models\User;
use App\Models\TeacherModel;
use App\Models\StudentModel;
use App\Models\SubjectModel;
use App\Models\SectionModel;
use App\Support\AdminActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Activity;


class AdminDashboardController extends Controller
{
    public function index()
    {
        $recentActivities = Activity::query()
            ->select([
                'id',
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
            ->latest()
            ->limit(5)
            ->get();

        return view('pages.admin.dashboard.partials.dashboard', [
            'title' => 'Admin Dashboard',
            'preloader' => false,
            'darkmode'  => true,
            'scrollup'  => true,
            'banner'    => false,
            'sidebar'   => true,

            // totals (real)
            'totalAccounts' => User::count(),
            'totalTeachers' => TeacherModel::count(),
            'totalStudents' => StudentModel::count(),
            'totalSubjects' => SubjectModel::count(),
            'totalSections' => SectionModel::count(),
            'recentActivities' => $recentActivities,
            'moduleLabels' => AdminActivityLog::moduleLabels(),
        ]);
    }
    public function announcements(Request $request)
    {
        ['activeSchoolYear' => $activeSchoolYear, 'schoolYears' => $schoolYears, 'selectedSchoolYear' => $selectedSchoolYear] = $this->announcementSchoolYearContext($request);

        $announcements = AdminAnnouncement::with('schoolYear')
            ->when(
                $selectedSchoolYear,
                fn ($query) => $query->where('school_year_id', $selectedSchoolYear->id),
                fn ($query) => $query->whereRaw('1 = 0')
            )
            ->orderBy('date_posted', 'desc')
            ->paginate(6)
            ->withQueryString();

        return view('pages.admin.dashboard.partials.announcements', [
            'title' => 'Admin Announcements',
            'preloader' => false,
            'darkmode'  => true,
            'scrollup'  => true,
            'banner'    => false,
            'sidebar'   => true,

            'announcements' => $announcements,
            'activeSchoolYear' => $activeSchoolYear,
            'schoolYears' => $schoolYears,
            'selectedSchoolYear' => $selectedSchoolYear,
        ]);
    }
    public function addAnnouncement(Request $request)
    {
        $activeSchoolYear = SchoolYear::activeYear();

        return view('pages.admin.dashboard.partials.addAnnouncement', [
            'title' => 'Add Announcement',
            'preloader' => false,
            'darkmode'  => true,
            'scrollup'  => true,
            'banner'    => false,
            'sidebar'   => true,
            'activeSchoolYear' => $activeSchoolYear,
            'announcementNavigationQuery' => $this->announcementQueryParams($request, $activeSchoolYear?->id),
        ]);
    }
    public function storeAnnouncement(Request $request)
{
    $request->validate([
        'title' => 'required|string|max:255',
        'description' => 'required|string',
        'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
    ]);

    $activeSchoolYear = SchoolYear::activeYear();

    if (! $activeSchoolYear) {
        return back()
            ->withInput()
            ->withErrors([
                'school_year' => 'Activate a school year in School Year Management before posting announcements.',
            ]);
    }

    $imagePath = null;

    if ($request->hasFile('image')) {
        // stores in: storage/app/public/media/...
        $imagePath = $request->file('image')->store('media', 'public');
    }

    AdminAnnouncement::create([
        'title' => $request->title,
        'description' => $request->description,
        'image' => $imagePath,
        'date_posted' => now(),
        'school_year_id' => $activeSchoolYear->id,
    ]);

    return redirect()
        ->route('admin.announcements', $this->announcementQueryParams($request, $activeSchoolYear->id))
        ->with('success', 'Announcement posted successfully!');
}

public function editAnnouncement(Request $request, AdminAnnouncement $announcement)
{
    $announcement->loadMissing('schoolYear');
    $announcementReadOnly = $this->announcementReadOnly($announcement);

    return view('pages.admin.dashboard.partials.edit-announcement', [
        'title' => 'Edit Announcement',
        'preloader' => false,
        'darkmode'  => true,
        'scrollup'  => true,
        'banner'    => false,
        'sidebar'   => true,

        'announcement' => $announcement,
        'announcementReadOnly' => $announcementReadOnly,
        'announcementNavigationQuery' => $this->announcementQueryParams($request, $announcement->school_year_id),
    ]);
}

public function updateAnnouncement(Request $request, AdminAnnouncement $announcement)
{
    $announcement->loadMissing('schoolYear');

    if ($this->announcementReadOnly($announcement)) {
        return redirect()
            ->route('admin.editAnnouncement', array_merge(
                ['announcement' => $announcement->id],
                $this->announcementQueryParams($request, $announcement->school_year_id)
            ))
            ->with('error', 'Announcements from inactive school years are read-only.');
    }

    $request->validate([
        'title' => 'required|string|max:255',
        'description' => 'required|string',
        'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        'remove_image' => 'nullable|boolean',
    ]);

    // Remove image if user checked remove
    if ($request->boolean('remove_image')) {
        if ($announcement->image && Storage::disk('public')->exists($announcement->image)) {
            Storage::disk('public')->delete($announcement->image);
        }
        $announcement->image = null;
    }

    // Replace image if new uploaded
    if ($request->hasFile('image')) {
        if ($announcement->image && Storage::disk('public')->exists($announcement->image)) {
            Storage::disk('public')->delete($announcement->image);
        }

        $announcement->image = $request->file('image')->store('media', 'public');
    }

    $announcement->title = $request->title;
    $announcement->description = $request->description;
    $announcement->save();

    return redirect()
        ->route('admin.announcements', $this->announcementQueryParams($request, $announcement->school_year_id))
        ->with('success', 'Announcement updated successfully!');
}

public function deleteAnnouncement(Request $request, AdminAnnouncement $announcement)
{
    $announcement->loadMissing('schoolYear');

    if ($this->announcementReadOnly($announcement)) {
        return redirect()
            ->route('admin.announcements', $this->announcementQueryParams($request, $announcement->school_year_id))
            ->with('error', 'Announcements from inactive school years are read-only.');
    }

    if ($announcement->image && Storage::disk('public')->exists($announcement->image)) {
        Storage::disk('public')->delete($announcement->image);
    }

    $announcement->delete();

    return redirect()
        ->route('admin.announcements', $this->announcementQueryParams($request, $announcement->school_year_id))
        ->with('success', 'Announcement deleted successfully!');
}

private function announcementSchoolYearContext(Request $request): array
{
    $activeSchoolYear = SchoolYear::activeYear();
    $schoolYears = SchoolYear::query()
        ->orderByDesc('start_year')
        ->orderByDesc('end_year')
        ->get();

    $selectedSchoolYear = is_numeric($request->input('school_year'))
        ? $schoolYears->firstWhere('id', (int) $request->input('school_year'))
        : null;

    $selectedSchoolYear ??= $activeSchoolYear;

    return [
        'activeSchoolYear' => $activeSchoolYear,
        'schoolYears' => $schoolYears,
        'selectedSchoolYear' => $selectedSchoolYear,
    ];
}

private function announcementReadOnly(AdminAnnouncement $announcement): bool
{
    return ! (bool) $announcement->schoolYear?->is_active;
}

private function announcementQueryParams(Request $request, ?int $fallbackSchoolYearId = null): array
{
    $schoolYearId = $request->query('school_year');

    if (is_numeric($schoolYearId)) {
        return ['school_year' => (int) $schoolYearId];
    }

    if ($fallbackSchoolYearId !== null) {
        return ['school_year' => $fallbackSchoolYearId];
    }

    return [];
}

}
