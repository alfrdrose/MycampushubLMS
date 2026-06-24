<?php

use App\Models\SchoolYear;
use App\Models\SectionModel;
use App\Models\StudentEnrollment;
use App\Models\StudentModel;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::findOrCreate('admin', 'web');
    Role::findOrCreate('teacher', 'web');
    Role::findOrCreate('student', 'web');
});

it('shows the enrolled school year in admin student management', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $schoolYear = SchoolYear::create([
        'start_year' => 2026,
        'end_year' => 2027,
        'is_active' => true,
    ]);

    $student = StudentModel::factory()->create([
        'first_name' => 'Alicia',
        'last_name' => 'Legacy',
        'grade' => '8',
    ]);

    StudentEnrollment::create([
        'student_id' => $student->id,
        'school_year_id' => $schoolYear->id,
        'section_id' => null,
        'grade_level' => 8,
        'status' => 'active',
        'is_current' => true,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.students'))
        ->assertOk()
        ->assertSee('School year')
        ->assertSee('2026 - 2027')
        ->assertSee('Alicia')
        ->assertSee('Legacy');
});

it('shows a fallback when a student has no current enrollment in admin student management', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    StudentModel::factory()->create([
        'first_name' => 'Noel',
        'last_name' => 'Enrolled',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.students'))
        ->assertOk()
        ->assertSee('No enrollment')
        ->assertSee('Noel')
        ->assertSee('Enrolled');
});

it('shows the student enrollment history page from student management', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $previousSchoolYear = SchoolYear::create([
        'start_year' => 2025,
        'end_year' => 2026,
        'is_active' => false,
    ]);

    $currentSchoolYear = SchoolYear::create([
        'start_year' => 2026,
        'end_year' => 2027,
        'is_active' => true,
    ]);

    $previousSection = SectionModel::create([
        'grade_level' => 7,
        'section_rank' => 1,
        'section_name' => 'Narra',
        'start_year' => 2025,
        'end_year' => 2026,
        'school_year_id' => $previousSchoolYear->id,
    ]);

    $currentSection = SectionModel::create([
        'grade_level' => 8,
        'section_rank' => 2,
        'section_name' => 'Maple',
        'start_year' => 2026,
        'end_year' => 2027,
        'school_year_id' => $currentSchoolYear->id,
    ]);

    $student = StudentModel::factory()->create([
        'first_name' => 'Elena',
        'last_name' => 'History',
        'grade' => '8',
        'section_id' => $currentSection->id,
    ]);

    $previousEnrollment = StudentEnrollment::create([
        'student_id' => $student->id,
        'school_year_id' => $previousSchoolYear->id,
        'section_id' => $previousSection->id,
        'grade_level' => 7,
        'status' => 'promoted',
        'is_current' => false,
        'ended_at' => now()->subMonth(),
    ]);

    StudentEnrollment::create([
        'student_id' => $student->id,
        'school_year_id' => $currentSchoolYear->id,
        'section_id' => $currentSection->id,
        'grade_level' => 8,
        'status' => 'active',
        'is_current' => true,
        'previous_enrollment_id' => $previousEnrollment->id,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.student.enrollment', $student->id))
        ->assertOk()
        ->assertSee('Student Enrollment')
        ->assertSee('Student Details')
        ->assertSee('Enrollment Records')
        ->assertSee('Elena')
        ->assertSee('History')
        ->assertSee('2025 - 2026')
        ->assertSee('2026 - 2027')
        ->assertSee('Grade 7 - Section 1 (Narra)')
        ->assertSee('Grade 8 - Section 2 (Maple)')
        ->assertSee('Current');
});
