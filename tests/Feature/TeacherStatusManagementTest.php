<?php

use App\Models\TeacherModel;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::findOrCreate('admin', 'web');
    Role::findOrCreate('teacher', 'web');
    Role::findOrCreate('student', 'web');
});

it('blocks inactive teachers from authenticating on the login screen', function () {
    $teacherUser = User::factory()->create([
        'email' => 'inactive-teacher@example.com',
        'password' => Hash::make('password'),
        'status' => 'inactive',
    ]);
    $teacherUser->assignRole('teacher');

    TeacherModel::factory()->create([
        'user_id' => $teacherUser->id,
        'email' => $teacherUser->email,
    ]);

    $this->post(route('login'), [
        'email' => $teacherUser->email,
        'password' => 'password',
    ])
        ->assertSessionHasErrors([
            'email' => 'Your teacher account is inactive. Contact an administrator.',
        ]);

    $this->assertGuest();
});

it('redirects inactive teachers away from teacher routes', function () {
    $teacherUser = User::factory()->create([
        'status' => 'inactive',
    ]);
    $teacherUser->assignRole('teacher');

    TeacherModel::factory()->create([
        'user_id' => $teacherUser->id,
        'email' => $teacherUser->email,
    ]);

    $this->actingAs($teacherUser)
        ->get(route('teacher.dashboard'))
        ->assertRedirect(route('login'));

    $this->assertGuest();
});

it('shows teacher login status in teacher management and lets admin toggle it', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $teacherUser = User::factory()->create([
        'status' => 'active',
    ]);
    $teacherUser->assignRole('teacher');

    $teacher = TeacherModel::factory()->create([
        'user_id' => $teacherUser->id,
        'first_name' => 'Alicia',
        'last_name' => 'Teacher',
        'email' => $teacherUser->email,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.teachers'))
        ->assertOk()
        ->assertSee('Status')
        ->assertSee('Active');

    $this->actingAs($admin)
        ->post(route('admin.teachers.toggle-status', $teacher->id))
        ->assertOk()
        ->assertJson([
            'success' => true,
            'status' => 'inactive',
            'label' => 'Inactive',
        ]);

    expect($teacherUser->fresh()->status)->toBe('inactive');

    $this->actingAs($admin)
        ->get(route('admin.teachers', ['status' => 'inactive']))
        ->assertOk()
        ->assertSee('Inactive')
        ->assertSee('Alicia')
        ->assertDontSee('No teachers found.');
});
