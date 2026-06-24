<?php

use App\Models\AdminAnnouncement;
use App\Models\SchoolYear;
use App\Models\StudentModel;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::findOrCreate('admin', 'web');
    Role::findOrCreate('student', 'web');
});

it('associates new admin announcements with the active school year', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $activeSchoolYear = SchoolYear::create([
        'start_year' => 2025,
        'end_year' => 2026,
        'is_active' => true,
    ]);

    SchoolYear::create([
        'start_year' => 2026,
        'end_year' => 2027,
        'is_active' => false,
    ]);

    $response = $this
        ->actingAs($admin)
        ->post(route('admin.storeAnnouncement'), [
            'title' => 'Enrollment Schedule',
            'description' => '<div>Enrollment for the active school year is now open.</div>',
        ]);

    $response->assertRedirect(route('admin.announcements', ['school_year' => $activeSchoolYear->id]));

    $this->assertDatabaseHas('admin_announcements', [
        'title' => 'Enrollment Schedule',
        'school_year_id' => $activeSchoolYear->id,
    ]);
});

it('shows students only the announcements assigned to the active school year', function () {
    $activeSchoolYear = SchoolYear::create([
        'start_year' => 2025,
        'end_year' => 2026,
        'is_active' => true,
    ]);

    $nextSchoolYear = SchoolYear::create([
        'start_year' => 2026,
        'end_year' => 2027,
        'is_active' => false,
    ]);

    AdminAnnouncement::create([
        'title' => 'Active SY Announcement',
        'description' => 'Visible while the 2025-2026 school year is active.',
        'date_posted' => now()->subDay(),
        'school_year_id' => $activeSchoolYear->id,
    ]);

    AdminAnnouncement::create([
        'title' => 'Next SY Announcement',
        'description' => 'Only visible after the school year changes.',
        'date_posted' => now(),
        'school_year_id' => $nextSchoolYear->id,
    ]);

    $studentUser = User::factory()->create();
    $studentUser->assignRole('student');

    StudentModel::factory()->create([
        'user_id' => $studentUser->id,
    ]);

    $this->actingAs($studentUser)
        ->get(route('student.dashboard'))
        ->assertOk()
        ->assertSee('Active SY Announcement')
        ->assertDontSee('Next SY Announcement');

    $nextSchoolYear->activate();

    $this->actingAs($studentUser)
        ->get(route('student.dashboard'))
        ->assertOk()
        ->assertSee('Next SY Announcement')
        ->assertDontSee('Active SY Announcement');
});

it('blocks posting admin announcements when no school year is active', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    SchoolYear::create([
        'start_year' => 2025,
        'end_year' => 2026,
        'is_active' => false,
    ]);

    $response = $this
        ->actingAs($admin)
        ->from(route('admin.addAnnouncement'))
        ->post(route('admin.storeAnnouncement'), [
            'title' => 'Orphan Announcement',
            'description' => 'This should not be saved without an active school year.',
        ]);

    $response->assertRedirect(route('admin.addAnnouncement'))
        ->assertSessionHasErrors('school_year');

    expect(AdminAnnouncement::query()->count())->toBe(0);
});

it('filters admin announcements by the selected school year in the admin module', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $activeSchoolYear = SchoolYear::create([
        'start_year' => 2025,
        'end_year' => 2026,
        'is_active' => true,
    ]);

    $inactiveSchoolYear = SchoolYear::create([
        'start_year' => 2024,
        'end_year' => 2025,
        'is_active' => false,
    ]);

    AdminAnnouncement::create([
        'title' => 'Current Announcement',
        'description' => 'Current school year item.',
        'date_posted' => now()->subDay(),
        'school_year_id' => $activeSchoolYear->id,
    ]);

    AdminAnnouncement::create([
        'title' => 'Legacy Announcement',
        'description' => 'Inactive school year item.',
        'date_posted' => now(),
        'school_year_id' => $inactiveSchoolYear->id,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.announcements', ['school_year' => $inactiveSchoolYear->id]))
        ->assertOk()
        ->assertSee('Legacy Announcement')
        ->assertDontSee('Current Announcement')
        ->assertSee('Viewing announcements for SY 2024 - 2025. All announcement actions are disabled while this school year is inactive.')
        ->assertDontSee('Add Announcement');
});

it('blocks admin announcement updates and deletes for inactive school years', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $inactiveSchoolYear = SchoolYear::create([
        'start_year' => 2024,
        'end_year' => 2025,
        'is_active' => false,
    ]);

    $announcement = AdminAnnouncement::create([
        'title' => 'Legacy Announcement',
        'description' => 'Inactive school year item.',
        'date_posted' => now(),
        'school_year_id' => $inactiveSchoolYear->id,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.editAnnouncement', [
            'announcement' => $announcement->id,
            'school_year' => $inactiveSchoolYear->id,
        ]))
        ->assertOk()
        ->assertSee('Viewing records for SY 2024 - 2025. All announcement actions are disabled while this school year is inactive.')
        ->assertSee('aria-disabled="true"', false);

    $this->actingAs($admin)
        ->put(route('admin.updateAnnouncement', [
            'announcement' => $announcement->id,
            'school_year' => $inactiveSchoolYear->id,
        ]), [
            'title' => 'Updated Title',
            'description' => 'Updated description.',
        ])
        ->assertRedirect(route('admin.editAnnouncement', [
            'announcement' => $announcement->id,
            'school_year' => $inactiveSchoolYear->id,
        ]));

    $this->assertDatabaseHas('admin_announcements', [
        'id' => $announcement->id,
        'title' => 'Legacy Announcement',
    ]);

    $this->actingAs($admin)
        ->delete(route('admin.deleteAnnouncement', [
            'announcement' => $announcement->id,
            'school_year' => $inactiveSchoolYear->id,
        ]))
        ->assertRedirect(route('admin.announcements', ['school_year' => $inactiveSchoolYear->id]));

    $this->assertDatabaseHas('admin_announcements', [
        'id' => $announcement->id,
        'title' => 'Legacy Announcement',
    ]);
});
