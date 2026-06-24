<?php

use App\Models\SchoolYear;
use App\Models\SectionModel;
use App\Models\StudentEnrollment;
use App\Models\StudentModel;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::findOrCreate('admin', 'web');
    Role::findOrCreate('teacher', 'web');
    Role::findOrCreate('student', 'web');
});

it('backfills missing current enrollments into promotion preview for the selected source section', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $sourceSchoolYear = SchoolYear::create([
        'start_year' => 2025,
        'end_year' => 2026,
        'is_active' => true,
    ]);

    $targetSchoolYear = SchoolYear::create([
        'start_year' => 2026,
        'end_year' => 2027,
        'is_active' => false,
    ]);

    $sourceSection = SectionModel::factory()->create([
        'grade_level' => 7,
        'section_rank' => 1,
        'section_name' => 'A',
        'start_year' => 2025,
        'end_year' => 2026,
        'school_year_id' => $sourceSchoolYear->id,
    ]);

    SectionModel::factory()->create([
        'grade_level' => 8,
        'section_rank' => 1,
        'section_name' => 'A',
        'start_year' => 2026,
        'end_year' => 2027,
        'school_year_id' => $targetSchoolYear->id,
    ]);

    $student = StudentModel::factory()->create([
        'first_name' => 'Alicia',
        'last_name' => 'Legacy',
        'email' => 'alicia.legacy@student.test',
        'grade' => '7',
        'section_id' => $sourceSection->id,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.school-year', ['selected_school_year' => $sourceSchoolYear->id]))
        ->assertOk()
        ->assertSee('Legacy, Alicia');

    $this->assertDatabaseHas('student_enrollments', [
        'student_id' => $student->id,
        'school_year_id' => $sourceSchoolYear->id,
        'section_id' => $sourceSection->id,
        'grade_level' => 7,
        'status' => 'active',
        'is_current' => 1,
    ]);
});

it('creates a current enrollment when a student is added to a section', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $sourceSchoolYear = SchoolYear::create([
        'start_year' => 2025,
        'end_year' => 2026,
        'is_active' => true,
    ]);

    $targetSchoolYear = SchoolYear::create([
        'start_year' => 2026,
        'end_year' => 2027,
        'is_active' => false,
    ]);

    $sourceSection = SectionModel::factory()->create([
        'grade_level' => 7,
        'section_rank' => 1,
        'section_name' => 'A',
        'start_year' => 2025,
        'end_year' => 2026,
        'school_year_id' => $sourceSchoolYear->id,
    ]);

    SectionModel::factory()->create([
        'grade_level' => 8,
        'section_rank' => 1,
        'section_name' => 'A',
        'start_year' => 2026,
        'end_year' => 2027,
        'school_year_id' => $targetSchoolYear->id,
    ]);

    $student = StudentModel::factory()->create([
        'first_name' => 'Mia',
        'last_name' => 'Ramos',
        'email' => 'mia.ramos@student.test',
        'grade' => '7',
        'section_id' => null,
    ]);

    $this->actingAs($admin)
        ->post(route('admin.section.addstudents', ['id' => $sourceSection->id]), [
            'student_ids' => json_encode([$student->id]),
        ])
        ->assertRedirect(route('admin.section.students', ['id' => $sourceSection->id]));

    expect($student->fresh()->section_id)->toBe($sourceSection->id);

    $this->assertDatabaseHas('student_enrollments', [
        'student_id' => $student->id,
        'school_year_id' => $sourceSchoolYear->id,
        'section_id' => $sourceSection->id,
        'grade_level' => 7,
        'status' => 'active',
        'is_current' => 1,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.school-year', ['selected_school_year' => $sourceSchoolYear->id]))
        ->assertOk()
        ->assertSee('Ramos, Mia');
});

it('creates a current enrollment when a student is imported from csv into a section', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $sourceSchoolYear = SchoolYear::create([
        'start_year' => 2025,
        'end_year' => 2026,
        'is_active' => true,
    ]);

    $targetSchoolYear = SchoolYear::create([
        'start_year' => 2026,
        'end_year' => 2027,
        'is_active' => false,
    ]);

    $sourceSection = SectionModel::factory()->create([
        'grade_level' => 7,
        'section_rank' => 1,
        'section_name' => 'A',
        'start_year' => 2025,
        'end_year' => 2026,
        'school_year_id' => $sourceSchoolYear->id,
    ]);

    SectionModel::factory()->create([
        'grade_level' => 8,
        'section_rank' => 1,
        'section_name' => 'A',
        'start_year' => 2026,
        'end_year' => 2027,
        'school_year_id' => $targetSchoolYear->id,
    ]);

    $csvContent = implode("\r\n", [
        'username,account_email,account_password,role,first_name,last_name,student_email,guardian_email,grade,section_id',
        'alicialegacy,alicia.legacy@example.test,password123,student,Alicia,Legacy,alicia.legacy@student.test,guardian.legacy@example.test,7,' . $sourceSection->id,
    ]);

    $file = UploadedFile::fake()->createWithContent('students.csv', $csvContent);

    $this->actingAs($admin)
        ->post(route('admin.accounts.students.upload'), [
            'students_csv' => $file,
        ])
        ->assertRedirect(route('admin.accounts'));

    $student = StudentModel::query()
        ->where('email', 'alicia.legacy@student.test')
        ->firstOrFail();

    $this->assertDatabaseHas('student_enrollments', [
        'student_id' => $student->id,
        'school_year_id' => $sourceSchoolYear->id,
        'section_id' => $sourceSection->id,
        'grade_level' => 7,
        'status' => 'active',
        'is_current' => 1,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.school-year', ['selected_school_year' => $sourceSchoolYear->id]))
        ->assertOk()
        ->assertSee('Legacy, Alicia');
});

it('blocks the csv import when duplicate values or section-grade mismatches are found', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $schoolYear = SchoolYear::create([
        'start_year' => 2025,
        'end_year' => 2026,
        'is_active' => true,
    ]);

    $gradeSevenSection = SectionModel::factory()->create([
        'grade_level' => 7,
        'section_rank' => 1,
        'section_name' => 'A',
        'start_year' => 2025,
        'end_year' => 2026,
        'school_year_id' => $schoolYear->id,
    ]);

    $csvContent = implode("\r\n", [
        'username,account_email,account_password,role,first_name,last_name,student_email,guardian_email,grade,section_id',
        'repeatuser,ana.one@example.test,password123,student,Ana,One,ana.one@student.test,guardian.one@example.test,7,' . $gradeSevenSection->id,
        'repeatuser,ben.two@example.test,password123,student,Ben,Two,ben.two@student.test,guardian.two@example.test,8,' . $gradeSevenSection->id,
    ]);

    $file = UploadedFile::fake()->createWithContent('students.csv', $csvContent);

    $this->actingAs($admin)
        ->from(route('admin.accounts'))
        ->post(route('admin.accounts.students.upload'), [
            'students_csv' => $file,
        ])
        ->assertRedirect(route('admin.accounts'))
        ->assertSessionHasErrorsIn('studentCsv', ['students_csv'])
        ->assertSessionHas('studentCsvImportFeedback', function ($feedback) {
            $rowIssues = collect($feedback['row_issues'] ?? []);

            return ($feedback['summary']['rows_invalid'] ?? null) === 2
                && $rowIssues->contains(function ($rowIssue) {
                    return collect($rowIssue['issues'] ?? [])->contains(
                        fn ($issue) => str_contains($issue, 'duplicated within the CSV file')
                    );
                })
                && $rowIssues->contains(function ($rowIssue) {
                    return collect($rowIssue['issues'] ?? [])->contains(
                        fn ($issue) => str_contains($issue, 'grade column contains')
                    );
                });
        });

    $this->assertDatabaseMissing('users', [
        'email' => 'ana.one@example.test',
    ]);

    $this->assertDatabaseMissing('users', [
        'email' => 'ben.two@example.test',
    ]);
});

it('blocks the csv import when the header does not match the template', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $csvContent = implode("\r\n", [
        'username,account_email,account_password,role,first_name,last_name,student_email,guardian_email,grade,wrong_column',
        'alicialegacy,alicia.legacy@example.test,password123,student,Alicia,Legacy,alicia.legacy@student.test,guardian.legacy@example.test,7,10',
    ]);

    $file = UploadedFile::fake()->createWithContent('students.csv', $csvContent);

    $this->actingAs($admin)
        ->from(route('admin.accounts'))
        ->post(route('admin.accounts.students.upload'), [
            'students_csv' => $file,
        ])
        ->assertRedirect(route('admin.accounts'))
        ->assertSessionHasErrorsIn('studentCsv', ['students_csv'])
        ->assertSessionHas('studentCsvImportFeedback', function ($feedback) {
            $fileIssues = collect($feedback['file_issues'] ?? []);

            return $fileIssues->contains(
                fn ($issue) => str_contains($issue, 'Missing required column')
            ) && $fileIssues->contains(
                fn ($issue) => str_contains($issue, 'Unexpected column')
            );
        });

    $this->assertDatabaseMissing('users', [
        'email' => 'alicia.legacy@example.test',
    ]);
});

it('creates a current enrollment when a student account is created directly into a section', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $sourceSchoolYear = SchoolYear::create([
        'start_year' => 2025,
        'end_year' => 2026,
        'is_active' => true,
    ]);

    $targetSchoolYear = SchoolYear::create([
        'start_year' => 2026,
        'end_year' => 2027,
        'is_active' => false,
    ]);

    $sourceSection = SectionModel::factory()->create([
        'grade_level' => 7,
        'section_rank' => 1,
        'section_name' => 'A',
        'start_year' => 2025,
        'end_year' => 2026,
        'school_year_id' => $sourceSchoolYear->id,
    ]);

    SectionModel::factory()->create([
        'grade_level' => 8,
        'section_rank' => 1,
        'section_name' => 'A',
        'start_year' => 2026,
        'end_year' => 2027,
        'school_year_id' => $targetSchoolYear->id,
    ]);

    $this->actingAs($admin)
        ->post(route('admin.storestudentwithaccount'), [
            'account_name' => 'alicialegacy',
            'account_email' => 'alicia.legacy@example.test',
            'account_password' => 'password123',
            'account_password_confirmation' => 'password123',
            'account_role' => 'student',
            'first_name' => 'Alicia',
            'last_name' => 'Legacy',
            'guardian_email' => 'guardian.legacy@example.test',
            'grade' => '7',
            'section_id' => $sourceSection->id,
        ])
        ->assertRedirect(route('admin.accounts'));

    $student = StudentModel::query()
        ->where('email', 'alicia.legacy@example.test')
        ->firstOrFail();

    expect($student->section_id)->toBe($sourceSection->id);

    $this->assertDatabaseHas('student_enrollments', [
        'student_id' => $student->id,
        'school_year_id' => $sourceSchoolYear->id,
        'section_id' => $sourceSection->id,
        'grade_level' => 7,
        'status' => 'active',
        'is_current' => 1,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.school-year', ['selected_school_year' => $sourceSchoolYear->id]))
        ->assertOk()
        ->assertSee('Legacy, Alicia');
});

it('clears the current enrollment section when a student is removed from a section', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $sourceSchoolYear = SchoolYear::create([
        'start_year' => 2025,
        'end_year' => 2026,
        'is_active' => true,
    ]);

    $sourceSection = SectionModel::factory()->create([
        'grade_level' => 7,
        'section_rank' => 1,
        'section_name' => 'A',
        'start_year' => 2025,
        'end_year' => 2026,
        'school_year_id' => $sourceSchoolYear->id,
    ]);

    $student = StudentModel::factory()->create([
        'first_name' => 'Noah',
        'last_name' => 'Cruz',
        'email' => 'noah.cruz@student.test',
        'grade' => '7',
        'section_id' => $sourceSection->id,
    ]);

    StudentEnrollment::create([
        'student_id' => $student->id,
        'school_year_id' => $sourceSchoolYear->id,
        'section_id' => $sourceSection->id,
        'grade_level' => 7,
        'status' => 'active',
        'is_current' => true,
    ]);

    $this->actingAs($admin)
        ->delete(route('admin.section.removestudent', [
            'section' => $sourceSection->id,
            'student' => $student->id,
        ]))
        ->assertRedirect();

    expect($student->fresh()->section_id)->toBeNull();

    $this->assertDatabaseHas('student_enrollments', [
        'student_id' => $student->id,
        'school_year_id' => $sourceSchoolYear->id,
        'section_id' => null,
        'grade_level' => 7,
        'status' => 'active',
        'is_current' => 1,
    ]);
});
