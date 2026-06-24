<?php

use App\Models\SchoolYear;
use App\Models\SectionModel;
use App\Models\Activity;
use App\Models\ActivitySubmission;
use App\Models\StudentEnrollment;
use App\Models\StudentModel;
use App\Models\SubjectModel;
use App\Models\SubjectTeacher;
use App\Models\TeacherModel;
use App\Models\User;
use App\Models\Quiz;
use App\Models\Response as QuizResponse;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::findOrCreate('admin', 'web');
    Role::findOrCreate('teacher', 'web');
    Role::findOrCreate('student', 'web');
});

it('shows empty-state school year creation actions when no school years exist', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->get(route('admin.school-year'))
        ->assertOk()
        ->assertSee('No school years have been created yet.')
        ->assertSee('Add Current Decade')
        ->assertSee('Add Individual School Year');
});

it('seeds a full decade of school years from the decade control', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    SchoolYear::create([
        'start_year' => 2025,
        'end_year' => 2026,
        'is_active' => true,
    ]);

    $response = $this
        ->actingAs($admin)
        ->post(route('admin.school-year.decade.store'), [
            'selected_decade' => 2020,
            'target_decade' => 2030,
        ]);

    $response->assertRedirect(route('admin.school-year', ['selected_school_year' => SchoolYear::where('start_year', 2030)->value('id')]));

    foreach (range(2030, 2039) as $startYear) {
        $this->assertDatabaseHas('school_years', [
            'start_year' => $startYear,
            'end_year' => $startYear + 1,
            'teacher_audit_enabled' => 1,
        ]);
    }

    expect(SchoolYear::query()->whereBetween('start_year', [2030, 2039])->count())->toBe(10)
        ->and(SchoolYear::query()->where('is_active', true)->count())->toBe(1)
        ->and(SchoolYear::query()->where('start_year', 2030)->first()?->is_active)->toBeFalse();
});

it('adds an individual school year from school year management', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $existingSchoolYear = SchoolYear::create([
        'start_year' => 2026,
        'end_year' => 2027,
        'is_active' => true,
    ]);

    $response = $this
        ->actingAs($admin)
        ->post(route('admin.school-year.store'), [
            'start_year' => 2027,
            'end_year' => 2028,
        ]);

    $newSchoolYearId = SchoolYear::query()
        ->where('start_year', 2027)
        ->where('end_year', 2028)
        ->value('id');

    $response->assertRedirect(route('admin.school-year', ['selected_school_year' => $newSchoolYearId]));

    $this->assertDatabaseHas('school_years', [
        'start_year' => 2027,
        'end_year' => 2028,
        'is_active' => 0,
        'teacher_audit_enabled' => 1,
    ]);

    expect($existingSchoolYear->fresh()->is_active)->toBeTrue();
});

it('archives an inactive school year from school year management', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $activeSchoolYear = SchoolYear::create([
        'start_year' => 2026,
        'end_year' => 2027,
        'is_active' => true,
    ]);

    $oldSchoolYear = SchoolYear::create([
        'start_year' => 2025,
        'end_year' => 2026,
        'is_active' => false,
    ]);

    $response = $this
        ->actingAs($admin)
        ->post(route('admin.school-year.archive', $oldSchoolYear));

    $response->assertRedirect(route('admin.school-year', ['selected_school_year' => $activeSchoolYear->id]));

    expect($oldSchoolYear->fresh()->is_archived)->toBeTrue()
        ->and($oldSchoolYear->fresh()->archived_at)->not->toBeNull()
        ->and($oldSchoolYear->fresh()->is_active)->toBeFalse();
});

it('executes a promotion batch and moves the student into the target school year', function () {
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

    $targetSection = SectionModel::factory()->create([
        'grade_level' => 8,
        'section_rank' => 1,
        'section_name' => 'A',
        'start_year' => 2026,
        'end_year' => 2027,
        'school_year_id' => $targetSchoolYear->id,
    ]);

    $student = StudentModel::factory()->create([
        'grade' => '7',
        'section_id' => $sourceSection->id,
    ]);

    $currentEnrollment = StudentEnrollment::create([
        'student_id' => $student->id,
        'school_year_id' => $sourceSchoolYear->id,
        'section_id' => $sourceSection->id,
        'grade_level' => 7,
        'status' => 'active',
        'is_current' => true,
    ]);

    $response = $this
        ->actingAs($admin)
        ->post(route('admin.school-year.promotions.execute'), [
            'source_school_year_id' => $sourceSchoolYear->id,
            'target_school_year_id' => $targetSchoolYear->id,
            'selected_school_year_id' => $targetSchoolYear->id,
            'activate_target_school_year' => '1',
            'actions' => [
                $student->id => 'promoted',
            ],
        ]);

    $response->assertRedirect(route('admin.school-year', ['selected_school_year' => $targetSchoolYear->id]));

    expect($student->fresh()->section_id)->toBe($targetSection->id)
        ->and($student->fresh()->grade)->toBe('8')
        ->and($sourceSchoolYear->fresh()->is_active)->toBeTrue()
        ->and($targetSchoolYear->fresh()->is_active)->toBeFalse();

    $currentEnrollment->refresh();

    expect($currentEnrollment->status)->toBe('promoted')
        ->and($currentEnrollment->is_current)->toBeFalse()
        ->and($currentEnrollment->ended_at)->not->toBeNull();

    $this->assertDatabaseHas('student_enrollments', [
        'student_id' => $student->id,
        'school_year_id' => $targetSchoolYear->id,
        'section_id' => $targetSection->id,
        'grade_level' => 8,
        'status' => 'active',
        'is_current' => 1,
        'previous_enrollment_id' => $currentEnrollment->id,
    ]);
});

it('can scope promotion to a specific source section', function () {
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

    $sourceSectionA = SectionModel::factory()->create([
        'grade_level' => 7,
        'section_rank' => 1,
        'section_name' => 'A',
        'start_year' => 2025,
        'end_year' => 2026,
        'school_year_id' => $sourceSchoolYear->id,
    ]);

    $sourceSectionB = SectionModel::factory()->create([
        'grade_level' => 7,
        'section_rank' => 2,
        'section_name' => 'B',
        'start_year' => 2025,
        'end_year' => 2026,
        'school_year_id' => $sourceSchoolYear->id,
    ]);

    $targetSectionA = SectionModel::factory()->create([
        'grade_level' => 8,
        'section_rank' => 1,
        'section_name' => 'A',
        'start_year' => 2026,
        'end_year' => 2027,
        'school_year_id' => $targetSchoolYear->id,
    ]);

    $targetSectionB = SectionModel::factory()->create([
        'grade_level' => 8,
        'section_rank' => 2,
        'section_name' => 'B',
        'start_year' => 2026,
        'end_year' => 2027,
        'school_year_id' => $targetSchoolYear->id,
    ]);

    $studentA = StudentModel::factory()->create([
        'grade' => '7',
        'section_id' => $sourceSectionA->id,
    ]);

    $studentB = StudentModel::factory()->create([
        'grade' => '7',
        'section_id' => $sourceSectionB->id,
    ]);

    $enrollmentA = StudentEnrollment::create([
        'student_id' => $studentA->id,
        'school_year_id' => $sourceSchoolYear->id,
        'section_id' => $sourceSectionA->id,
        'grade_level' => 7,
        'status' => 'active',
        'is_current' => true,
    ]);

    $enrollmentB = StudentEnrollment::create([
        'student_id' => $studentB->id,
        'school_year_id' => $sourceSchoolYear->id,
        'section_id' => $sourceSectionB->id,
        'grade_level' => 7,
        'status' => 'active',
        'is_current' => true,
    ]);

    $response = $this
        ->actingAs($admin)
        ->post(route('admin.school-year.promotions.execute'), [
            'source_school_year_id' => $sourceSchoolYear->id,
            'target_school_year_id' => $targetSchoolYear->id,
            'selected_school_year_id' => $targetSchoolYear->id,
            'source_section_id' => $sourceSectionA->id,
            'activate_target_school_year' => '1',
            'actions' => [
                $studentA->id => 'promoted',
            ],
        ]);

    $response->assertRedirect(route('admin.school-year', ['selected_school_year' => $targetSchoolYear->id]));

    expect($studentA->fresh()->section_id)->toBe($targetSectionA->id)
        ->and($studentA->fresh()->grade)->toBe('8')
        ->and($studentB->fresh()->section_id)->toBe($sourceSectionB->id)
        ->and($studentB->fresh()->grade)->toBe('7');

    expect($enrollmentA->fresh()->is_current)->toBeFalse()
        ->and($enrollmentB->fresh()->is_current)->toBeTrue();

    $this->assertDatabaseHas('student_enrollments', [
        'student_id' => $studentA->id,
        'school_year_id' => $targetSchoolYear->id,
        'section_id' => $targetSectionA->id,
        'grade_level' => 8,
        'status' => 'active',
        'is_current' => 1,
        'previous_enrollment_id' => $enrollmentA->id,
    ]);

    $this->assertDatabaseMissing('student_enrollments', [
        'student_id' => $studentB->id,
        'school_year_id' => $targetSchoolYear->id,
        'section_id' => $targetSectionB->id,
    ]);
});

it('shows inactive-school-year classes to teachers when audit defaults to enabled', function () {
    $teacherUser = User::factory()->create();
    $teacherUser->assignRole('teacher');

    $teacher = TeacherModel::factory()->create([
        'user_id' => $teacherUser->id,
        'section' => null,
    ]);

    $activeSchoolYear = SchoolYear::create([
        'start_year' => 2026,
        'end_year' => 2027,
        'is_active' => true,
    ]);

    $oldSchoolYear = SchoolYear::create([
        'start_year' => 2025,
        'end_year' => 2026,
        'is_active' => false,
    ]);

    $activeSection = SectionModel::factory()->create([
        'grade_level' => 8,
        'section_rank' => 1,
        'section_name' => 'Current A',
        'start_year' => 2026,
        'end_year' => 2027,
        'school_year_id' => $activeSchoolYear->id,
    ]);

    $oldSection = SectionModel::factory()->create([
        'grade_level' => 7,
        'section_rank' => 1,
        'section_name' => 'Old A',
        'start_year' => 2025,
        'end_year' => 2026,
        'school_year_id' => $oldSchoolYear->id,
    ]);

    $activeSubject = SubjectModel::factory()->create([
        'subject_name' => 'Active Math',
        'grade_level' => 8,
    ]);

    $oldSubject = SubjectModel::factory()->create([
        'subject_name' => 'Old Science',
        'grade_level' => 7,
    ]);

    SubjectTeacher::create([
        'teacher_model_id' => $teacher->id,
        'section_model_id' => $activeSection->id,
        'subject_model_id' => $activeSubject->id,
    ]);

    SubjectTeacher::create([
        'teacher_model_id' => $teacher->id,
        'section_model_id' => $oldSection->id,
        'subject_model_id' => $oldSubject->id,
    ]);

    $this->actingAs($teacherUser)
        ->get(route('teacher.classes'))
        ->assertOk()
        ->assertSee('Active Math')
        ->assertSee('Current A')
        ->assertSee('Old Science')
        ->assertSee('Old A');
});

it('shows audit-enabled archived classes to teachers', function () {
    $teacherUser = User::factory()->create();
    $teacherUser->assignRole('teacher');

    $teacher = TeacherModel::factory()->create([
        'user_id' => $teacherUser->id,
        'section' => null,
    ]);

    $activeSchoolYear = SchoolYear::create([
        'start_year' => 2026,
        'end_year' => 2027,
        'is_active' => true,
    ]);

    $archivedSchoolYear = SchoolYear::create([
        'start_year' => 2025,
        'end_year' => 2026,
        'is_active' => false,
        'is_archived' => true,
        'teacher_audit_enabled' => true,
        'archived_at' => now(),
    ]);

    $activeSection = SectionModel::factory()->create([
        'grade_level' => 8,
        'section_rank' => 1,
        'section_name' => 'Current A',
        'start_year' => 2026,
        'end_year' => 2027,
        'school_year_id' => $activeSchoolYear->id,
    ]);

    $archivedSection = SectionModel::factory()->create([
        'grade_level' => 7,
        'section_rank' => 1,
        'section_name' => 'Archived A',
        'start_year' => 2025,
        'end_year' => 2026,
        'school_year_id' => $archivedSchoolYear->id,
    ]);

    $activeSubject = SubjectModel::factory()->create([
        'subject_name' => 'Active Math',
        'grade_level' => 8,
    ]);

    $archivedSubject = SubjectModel::factory()->create([
        'subject_name' => 'Archived Science',
        'grade_level' => 7,
    ]);

    SubjectTeacher::create([
        'teacher_model_id' => $teacher->id,
        'section_model_id' => $activeSection->id,
        'subject_model_id' => $activeSubject->id,
    ]);

    SubjectTeacher::create([
        'teacher_model_id' => $teacher->id,
        'section_model_id' => $archivedSection->id,
        'subject_model_id' => $archivedSubject->id,
    ]);

    DB::table('section_subject')->insert([
        'section_model_id' => $archivedSection->id,
        'subject_model_id' => $archivedSubject->id,
        'coverImage' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($teacherUser)
        ->get(route('teacher.classes'))
        ->assertOk()
        ->assertSee('Active Math')
        ->assertSee('Current A')
        ->assertSee('Archived Science')
        ->assertSee('Archived A')
        ->assertSee('Audit')
        ->assertSee('Read Only');

    $this->actingAs($teacherUser)
        ->get(route('teacher.class.show', [$archivedSection->id, $archivedSubject->id]))
        ->assertOk();
});

it('hides teacher add controls for audit-only classes', function () {
    $teacherUser = User::factory()->create();
    $teacherUser->assignRole('teacher');

    $teacher = TeacherModel::factory()->create([
        'user_id' => $teacherUser->id,
        'section' => null,
    ]);

    SchoolYear::create([
        'start_year' => 2026,
        'end_year' => 2027,
        'is_active' => true,
    ]);

    $auditSchoolYear = SchoolYear::create([
        'start_year' => 2025,
        'end_year' => 2026,
        'is_active' => false,
        'teacher_audit_enabled' => true,
    ]);

    $auditSection = SectionModel::factory()->create([
        'grade_level' => 7,
        'section_rank' => 1,
        'section_name' => 'Legacy A',
        'start_year' => 2025,
        'end_year' => 2026,
        'school_year_id' => $auditSchoolYear->id,
    ]);

    $auditSubject = SubjectModel::factory()->create([
        'subject_name' => 'Legacy Science',
        'grade_level' => 7,
    ]);

    SubjectTeacher::create([
        'teacher_model_id' => $teacher->id,
        'section_model_id' => $auditSection->id,
        'subject_model_id' => $auditSubject->id,
    ]);

    $sectionSubjectId = DB::table('section_subject')->insertGetId([
        'section_model_id' => $auditSection->id,
        'subject_model_id' => $auditSubject->id,
        'coverImage' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($teacherUser)
        ->get(route('teacher.classwork.view', [$auditSection->id, $auditSubject->id]))
        ->assertOk()
        ->assertDontSee('data-action="add-classwork"', false);

    $this->actingAs($teacherUser)
        ->get(route('teacher.lesson', [$auditSection->id, $auditSubject->id]))
        ->assertOk()
        ->assertDontSee('data-action="add-lesson"', false);

    $this->actingAs($teacherUser)
        ->get(route('teacher.attendance.view', [$auditSection->id, $auditSubject->id]))
        ->assertOk()
        ->assertDontSee('data-action="add-attendance"', false);
});

it('keeps promoted students visible in audited teacher activity records', function () {
    $teacherUser = User::factory()->create();
    $teacherUser->assignRole('teacher');

    $teacher = TeacherModel::factory()->create([
        'user_id' => $teacherUser->id,
        'section' => null,
    ]);

    $studentUser = User::factory()->create();
    $studentUser->assignRole('student');

    $activeSchoolYear = SchoolYear::create([
        'start_year' => 2026,
        'end_year' => 2027,
        'is_active' => true,
    ]);

    $auditSchoolYear = SchoolYear::create([
        'start_year' => 2025,
        'end_year' => 2026,
        'is_active' => false,
        'teacher_audit_enabled' => true,
    ]);

    $activeSection = SectionModel::factory()->create([
        'grade_level' => 8,
        'section_rank' => 1,
        'section_name' => 'Current A',
        'start_year' => 2026,
        'end_year' => 2027,
        'school_year_id' => $activeSchoolYear->id,
    ]);

    $auditSection = SectionModel::factory()->create([
        'grade_level' => 7,
        'section_rank' => 1,
        'section_name' => 'Legacy A',
        'start_year' => 2025,
        'end_year' => 2026,
        'school_year_id' => $auditSchoolYear->id,
    ]);

    $auditSubject = SubjectModel::factory()->create([
        'subject_name' => 'Legacy Science',
        'grade_level' => 7,
    ]);

    SubjectTeacher::create([
        'teacher_model_id' => $teacher->id,
        'section_model_id' => $auditSection->id,
        'subject_model_id' => $auditSubject->id,
    ]);

    $sectionSubjectId = DB::table('section_subject')->insertGetId([
        'section_model_id' => $auditSection->id,
        'subject_model_id' => $auditSubject->id,
        'coverImage' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $student = StudentModel::factory()->create([
        'user_id' => $studentUser->id,
        'first_name' => 'Alicia',
        'last_name' => 'Legacy',
        'grade' => '8',
        'section_id' => $activeSection->id,
    ]);

    StudentEnrollment::create([
        'student_id' => $student->id,
        'school_year_id' => $auditSchoolYear->id,
        'section_id' => $auditSection->id,
        'grade_level' => 7,
        'status' => 'promoted',
        'is_current' => false,
        'ended_at' => now(),
    ]);

    StudentEnrollment::create([
        'student_id' => $student->id,
        'school_year_id' => $activeSchoolYear->id,
        'section_id' => $activeSection->id,
        'grade_level' => 8,
        'status' => 'active',
        'is_current' => true,
    ]);

    $quiz = Quiz::create([
        'title' => 'Legacy Quiz',
        'grading_period' => '1st',
        'section_model_id' => $auditSection->id,
        'subject_model_id' => $auditSubject->id,
        'status' => 'published',
        'time_posted' => now(),
        'visible' => true,
        'accepting' => true,
        'is_open' => true,
        'total_score' => 10,
    ]);

    QuizResponse::create([
        'quiz_id' => $quiz->id,
        'session_token' => 'legacy-audit-token',
        'user_id' => $studentUser->id,
        'score' => 8,
        'meta' => [],
    ]);

    $classworkTypeId = DB::table('subject_grading')->insertGetId([
        'subject_id' => $auditSubject->id,
        'classwork_type' => 'Performance Task',
        'percentage' => 100,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $activity = Activity::create([
        'title' => 'Legacy Activity',
        'section_subject_id' => $sectionSubjectId,
        'time_posted' => now(),
        'visible' => true,
        'accepting' => true,
        'grading_period' => '1st',
        'classwork_type' => $classworkTypeId,
        'total_score' => 10,
    ]);

    ActivitySubmission::create([
        'activity_id' => $activity->id,
        'student_id' => $student->id,
        'type' => 'essay',
        'attempt_number' => 1,
        'is_latest' => true,
        'submitted_at' => now(),
        'timing_status' => 'on_time',
        'status' => 'graded',
        'score' => 9,
    ]);

    $this->actingAs($teacherUser)
        ->get(route('teacher.students', [$auditSection->id, $auditSubject->id]))
        ->assertOk()
        ->assertSee('Legacy, Alicia');

    $this->actingAs($teacherUser)
        ->get(route('teacher.quiz.submissions.show', [$auditSection->id, $auditSubject->id, $quiz->id, $student->id]))
        ->assertOk()
        ->assertSee('Legacy')
        ->assertSee('Alicia');

    $this->actingAs($teacherUser)
        ->get(route('teacher.classwork.submissions', [$auditSection->id, $auditSubject->id, $activity->id]))
        ->assertOk()
        ->assertSee('Legacy')
        ->assertSee('Alicia');
});

it('blocks teacher write requests for audit-only classes', function () {
    $teacherUser = User::factory()->create();
    $teacherUser->assignRole('teacher');

    $teacher = TeacherModel::factory()->create([
        'user_id' => $teacherUser->id,
        'section' => null,
    ]);

    SchoolYear::create([
        'start_year' => 2026,
        'end_year' => 2027,
        'is_active' => true,
    ]);

    $auditSchoolYear = SchoolYear::create([
        'start_year' => 2025,
        'end_year' => 2026,
        'is_active' => false,
        'teacher_audit_enabled' => true,
    ]);

    $auditSection = SectionModel::factory()->create([
        'grade_level' => 7,
        'section_rank' => 1,
        'section_name' => 'Legacy A',
        'start_year' => 2025,
        'end_year' => 2026,
        'school_year_id' => $auditSchoolYear->id,
    ]);

    $auditSubject = SubjectModel::factory()->create([
        'subject_name' => 'Legacy Science',
        'grade_level' => 7,
    ]);

    SubjectTeacher::create([
        'teacher_model_id' => $teacher->id,
        'section_model_id' => $auditSection->id,
        'subject_model_id' => $auditSubject->id,
    ]);

    $sectionSubjectId = DB::table('section_subject')->insertGetId([
        'section_model_id' => $auditSection->id,
        'subject_model_id' => $auditSubject->id,
        'coverImage' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($teacherUser)
        ->post(route('teacher.announcements.store'), [
            'title' => 'Archived notice',
            'body' => 'This should not be saved.',
            'section_subject_id' => $sectionSubjectId,
        ])
        ->assertForbidden();

    expect(DB::table('announcements_models')->count())->toBe(0);
});

it('preloads promotion preview from historical enrollments on school year navigation', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $selectedSchoolYear = SchoolYear::create([
        'start_year' => 2025,
        'end_year' => 2026,
        'is_active' => false,
    ]);

    $nextSchoolYear = SchoolYear::create([
        'start_year' => 2026,
        'end_year' => 2027,
        'is_active' => true,
    ]);

    $selectedSection = SectionModel::factory()->create([
        'grade_level' => 7,
        'section_rank' => 1,
        'section_name' => 'Legacy A',
        'start_year' => 2025,
        'end_year' => 2026,
        'school_year_id' => $selectedSchoolYear->id,
    ]);

    $nextSection = SectionModel::factory()->create([
        'grade_level' => 8,
        'section_rank' => 1,
        'section_name' => 'Current A',
        'start_year' => 2026,
        'end_year' => 2027,
        'school_year_id' => $nextSchoolYear->id,
    ]);

    $student = StudentModel::factory()->create([
        'first_name' => 'Alicia',
        'last_name' => 'Legacy',
        'grade' => '8',
        'section_id' => $nextSection->id,
    ]);

    StudentEnrollment::create([
        'student_id' => $student->id,
        'school_year_id' => $selectedSchoolYear->id,
        'section_id' => $selectedSection->id,
        'grade_level' => 7,
        'status' => 'promoted',
        'is_current' => false,
        'ended_at' => now(),
    ]);

    StudentEnrollment::create([
        'student_id' => $student->id,
        'school_year_id' => $nextSchoolYear->id,
        'section_id' => $nextSection->id,
        'grade_level' => 8,
        'status' => 'active',
        'is_current' => true,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.school-year', ['selected_school_year' => $selectedSchoolYear->id]))
        ->assertOk()
        ->assertSee('Legacy, Alicia');
});

it('clears the live grade when a student is transferred out during promotion', function () {
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
        'section_name' => 'Legacy A',
        'start_year' => 2025,
        'end_year' => 2026,
        'school_year_id' => $sourceSchoolYear->id,
    ]);

    $student = StudentModel::factory()->create([
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
        ->post(route('admin.school-year.promotions.execute'), [
            'selected_school_year_id' => $sourceSchoolYear->id,
            'source_school_year_id' => $sourceSchoolYear->id,
            'target_school_year_id' => $targetSchoolYear->id,
            'source_section_id' => $sourceSection->id,
            'actions' => [
                $student->id => 'transferred_out',
            ],
        ])
        ->assertRedirect(route('admin.school-year', ['selected_school_year' => $targetSchoolYear->id]));

    $student->refresh();

    expect($student->section_id)->toBeNull()
        ->and($student->grade)->toBeNull();
});

it('assigns new sections to the active school year', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $activeSchoolYear = SchoolYear::create([
        'start_year' => 2026,
        'end_year' => 2027,
        'is_active' => true,
    ]);

    SchoolYear::create([
        'start_year' => 2027,
        'end_year' => 2028,
        'is_active' => false,
    ]);

    $this->actingAs($admin)
        ->post(route('admin.storesection'), [
            'grade_level' => 7,
            'section_name' => 'A',
        ])
        ->assertRedirect(route('admin.sections', ['grade_level' => 7]));

    $this->assertDatabaseHas('section_models', [
        'grade_level' => 7,
        'section_rank' => 1,
        'section_name' => 'A',
        'start_year' => $activeSchoolYear->start_year,
        'end_year' => $activeSchoolYear->end_year,
        'school_year_id' => $activeSchoolYear->id,
    ]);
});

it('shows only active-school-year sections in admin section management', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $activeSchoolYear = SchoolYear::create([
        'start_year' => 2026,
        'end_year' => 2027,
        'is_active' => true,
    ]);

    $inactiveSchoolYear = SchoolYear::create([
        'start_year' => 2025,
        'end_year' => 2026,
        'is_active' => false,
    ]);

    SectionModel::factory()->create([
        'grade_level' => 7,
        'section_rank' => 1,
        'section_name' => 'Visible Section',
        'start_year' => 2026,
        'end_year' => 2027,
        'school_year_id' => $activeSchoolYear->id,
    ]);

    SectionModel::factory()->create([
        'grade_level' => 7,
        'section_rank' => 2,
        'section_name' => 'Hidden Section',
        'start_year' => 2025,
        'end_year' => 2026,
        'school_year_id' => $inactiveSchoolYear->id,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.sections', ['grade_level' => 7]))
        ->assertOk()
        ->assertSee('Visible Section')
        ->assertDontSee('Hidden Section');
});

it('filters admin section management by the selected school year', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $activeSchoolYear = SchoolYear::create([
        'start_year' => 2026,
        'end_year' => 2027,
        'is_active' => true,
    ]);

    $inactiveSchoolYear = SchoolYear::create([
        'start_year' => 2025,
        'end_year' => 2026,
        'is_active' => false,
    ]);

    SectionModel::factory()->create([
        'grade_level' => 7,
        'section_rank' => 1,
        'section_name' => 'Current Section',
        'start_year' => 2026,
        'end_year' => 2027,
        'school_year_id' => $activeSchoolYear->id,
    ]);

    SectionModel::factory()->create([
        'grade_level' => 7,
        'section_rank' => 2,
        'section_name' => 'Legacy Section',
        'start_year' => 2025,
        'end_year' => 2026,
        'school_year_id' => $inactiveSchoolYear->id,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.sections', [
            'grade_level' => 7,
            'school_year' => $inactiveSchoolYear->id,
        ]))
        ->assertOk()
        ->assertSee('School Year')
        ->assertSee('List of School Years')
        ->assertSee('Select School Year')
        ->assertSee('All Years')
        ->assertSee('Inactive')
        ->assertDontSee('id="school_year"', false)
        ->assertSee('2025 - 2026')
        ->assertDontSee('SY 2025 - 2026')
        ->assertSee('Legacy Section')
        ->assertDontSee('Current Section');
});

it('shows the school year label beside the section summary heading', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $activeSchoolYear = SchoolYear::create([
        'start_year' => 2026,
        'end_year' => 2027,
        'is_active' => true,
    ]);

    $section = SectionModel::factory()->create([
        'grade_level' => 7,
        'section_rank' => 1,
        'section_name' => 'A',
        'start_year' => 2026,
        'end_year' => 2027,
        'school_year_id' => $activeSchoolYear->id,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.editsection', $section->id))
        ->assertOk()
        ->assertSee('Summary')
        ->assertSee('SY 2026 - 2027');
});

it('blocks admin access to inactive-school-year sections in section management', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    SchoolYear::create([
        'start_year' => 2026,
        'end_year' => 2027,
        'is_active' => true,
    ]);

    $inactiveSchoolYear = SchoolYear::create([
        'start_year' => 2025,
        'end_year' => 2026,
        'is_active' => false,
    ]);

    $inactiveSection = SectionModel::factory()->create([
        'grade_level' => 7,
        'section_rank' => 1,
        'section_name' => 'Legacy A',
        'start_year' => 2025,
        'end_year' => 2026,
        'school_year_id' => $inactiveSchoolYear->id,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.editsection', $inactiveSection->id))
        ->assertNotFound();
});

it('allows admin to read inactive-school-year sections when the selected school year matches', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    SchoolYear::create([
        'start_year' => 2026,
        'end_year' => 2027,
        'is_active' => true,
    ]);

    $inactiveSchoolYear = SchoolYear::create([
        'start_year' => 2025,
        'end_year' => 2026,
        'is_active' => false,
    ]);

    $inactiveSection = SectionModel::factory()->create([
        'grade_level' => 7,
        'section_rank' => 1,
        'section_name' => 'Legacy A',
        'start_year' => 2025,
        'end_year' => 2026,
        'school_year_id' => $inactiveSchoolYear->id,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.editsection', [
            'id' => $inactiveSection->id,
            'school_year' => $inactiveSchoolYear->id,
            'grade_level' => 7,
        ]))
        ->assertOk()
        ->assertSee('Legacy A')
        ->assertSee('Viewing records for SY 2025 - 2026');
});

it('blocks admin write actions for inactive-school-year sections even when a school year filter is selected', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    SchoolYear::create([
        'start_year' => 2026,
        'end_year' => 2027,
        'is_active' => true,
    ]);

    $inactiveSchoolYear = SchoolYear::create([
        'start_year' => 2025,
        'end_year' => 2026,
        'is_active' => false,
    ]);

    $inactiveSection = SectionModel::factory()->create([
        'grade_level' => 7,
        'section_rank' => 1,
        'section_name' => 'Legacy A',
        'start_year' => 2025,
        'end_year' => 2026,
        'school_year_id' => $inactiveSchoolYear->id,
    ]);

    $this->actingAs($admin)
        ->delete(route('admin.section.delete', [
            'id' => $inactiveSection->id,
            'school_year' => $inactiveSchoolYear->id,
        ]))
        ->assertNotFound();

    $this->assertDatabaseHas('section_models', [
        'id' => $inactiveSection->id,
    ]);
});

it('shows a validation error when no school year is active while adding a section', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    SchoolYear::create([
        'start_year' => 2026,
        'end_year' => 2027,
        'is_active' => false,
    ]);

    $this->actingAs($admin)
        ->post(route('admin.storesection'), [
            'grade_level' => 7,
            'section_name' => 'A',
        ])
        ->assertSessionHasErrors([
            'school_year' => 'Activate a school year in School Year Management before adding sections.',
        ]);
});

it('hides inactive-school-year classes from students', function () {
    $studentUser = User::factory()->create();
    $studentUser->assignRole('student');

    $activeSchoolYear = SchoolYear::create([
        'start_year' => 2026,
        'end_year' => 2027,
        'is_active' => true,
    ]);

    $inactiveSchoolYear = SchoolYear::create([
        'start_year' => 2025,
        'end_year' => 2026,
        'is_active' => false,
    ]);

    $inactiveSection = SectionModel::factory()->create([
        'grade_level' => 7,
        'section_rank' => 2,
        'section_name' => 'Hidden Section',
        'start_year' => 2025,
        'end_year' => 2026,
        'school_year_id' => $inactiveSchoolYear->id,
    ]);

    StudentModel::factory()->create([
        'user_id' => $studentUser->id,
        'grade' => '7',
        'section_id' => $inactiveSection->id,
    ]);

    $this->actingAs($studentUser)
        ->get(route('student.classes'))
        ->assertOk()
        ->assertSee('No classes are available for the active school year.')
        ->assertDontSee('Hidden Section');
});
