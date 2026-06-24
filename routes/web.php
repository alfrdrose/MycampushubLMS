<?php

use App\Http\Controllers\AccountsController;
use App\Http\Controllers\ActivityFileSubmissionController;
use App\Http\Controllers\ActivitySubmissionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SectionController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\Teacher\TeacherClassController;
use App\Http\Controllers\Teacher\QuestionBankController;
use App\Http\Controllers\Teacher\ReviewerController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\EvaluationController;
use App\Http\Controllers\NotificationSettingController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\SectionScheduleController;
use App\Http\Controllers\StudentClassController;
use App\Http\Controllers\UnifiedSubmitController;
use Illuminate\Support\Facades\Route;
use App\Mail\GuardianAttendanceMail;
use Illuminate\Support\Facades\Mail;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AdminAnalyticsController;
use App\Http\Controllers\AdminLogController;
use App\Http\Controllers\GradePageController;
use App\Http\Controllers\SchoolYearController;
use App\Http\Controllers\StudentProfileController;
use App\Http\Controllers\TeacherProfileController;




Route::get('/preview-weekly-attendance-email', function () {

    $student = (object) [
        'first_name' => 'Harry',
        'last_name'  => 'Potter',
    ];

    $weekDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

    $attendance = [
        'Math' => [
            'Monday'    => 'Present',
            'Tuesday'   => 'Present',
            'Wednesday' => 'Absent',
            'Thursday'  => 'Present',
            'Friday'    => 'Present',
        ],
        'English' => [
            'Monday'    => 'Present',
            'Tuesday'   => 'Late',
            'Wednesday' => 'Present',
            'Thursday'  => 'Present',
            'Friday'    => 'Absent',
        ],
    ];

    return (new GuardianAttendanceMail(
        $student,
        $weekDays,
        $attendance,
        now()->startOfWeek()->toFormattedDateString(),
        now()->endOfWeek()->toFormattedDateString()
    ))->render();
});

Route::get('/test-weekly-email/{email}', function ($email) {

    $student = (object)[
        'first_name' => 'Harry',
        'last_name' => 'Potter',
    ];

    $weekDays = ['Monday','Tuesday','Wednesday','Thursday','Friday'];

    $attendance = [
        'Math' => [
            'Monday' => 'Present',
            'Tuesday' => 'Present',
            'Wednesday' => 'Absent',
            'Thursday' => 'Present',
            'Friday' => 'Present',
        ],
        'English' => [
            'Monday' => 'Present',
            'Tuesday' => 'Late',
            'Wednesday' => 'Present',
            'Thursday' => 'Present',
            'Friday' => 'Absent',
        ],
    ];

    Mail::to($email)->send(
        new GuardianAttendanceMail(
            $student,
            $weekDays,
            $attendance,
            now()->startOfWeek()->toFormattedDateString(),
            now()->endOfWeek()->toFormattedDateString()
        )
    );

    return "✅ Weekly email sent to {$email}";
});


Route::get('/ping', function () {
    return 'pong';
});

Route::get('/', function () {
    return redirect()->route('redirect.user');
})->name('home');

Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {


    Route::post(
    '/notification-settings/force-send',
    [NotificationSettingController::class, 'forceSendWeeklyAttendance']
)->name('notification-settings.force-send');



       Route::get('/dashboard', [AdminDashboardController::class, 'index'])
            ->name('dashboard');
        Route::get('/announcements', [AdminDashboardController::class, 'announcements'])
            ->name('announcements');
        Route::get('/announcements/add', [AdminDashboardController::class, 'addAnnouncement'])
            ->name('addAnnouncement');
        Route::post('/announcements/add', [AdminDashboardController::class, 'storeAnnouncement'])
            ->name('storeAnnouncement');
        Route::get('/announcements/{announcement}/edit', [AdminDashboardController::class, 'editAnnouncement'])
            ->name('editAnnouncement');
        Route::put('/announcements/{announcement}', [AdminDashboardController::class, 'updateAnnouncement'])
            ->name('updateAnnouncement');
        Route::delete('/announcements/{announcement}', [AdminDashboardController::class, 'deleteAnnouncement'])
            ->name('deleteAnnouncement');


        //For Notification Settings
        Route::view('/emailnotification', 'pages.admin.emailnotification',
            [
                'title'     => 'Email Notification Settings',
                'preloader' => false,
                'darkmode'  => true,
                'scrollup'  => true,
                'banner'    => false,
                'sidebar'   => true,
            ]
        )->name('emailnotification');

        Route::get('/logs', [AdminLogController::class, 'index'])
            ->name('logs');

        Route::get('/admin/notification-settings',[NotificationSettingController::class, 'show']
            )->name('notification-settings.show');
        Route::post('/admin/notification-settings',[NotificationSettingController::class, 'store'])
        ->name('notification-settings.store');

        

        Route::prefix('accounts')->controller(AccountsController::class)->group(function () {
            Route::get('/', 'index')->name('accounts');
            Route::get('/students/csv-template', 'downloadStudentCsvTemplate')->name('accounts.students.template');
            Route::post('/students/csv-upload', 'uploadStudentCsv')->name('accounts.students.upload');
            Route::get('/add', 'create')->name('addaccount');
            Route::post('/add', 'store')->name('store');
            Route::post('/add/teacher-account', 'storeTeacherAccount')->name('storeteacheraccount');
            Route::post('/add/student-account', 'storeStudentAccount')->name('storestudentaccount');
            Route::post('/add/teacher-with-account', 'storeTeacherWithAccount')->name('storeteacherwithaccount');
            Route::post('/add/student-with-account', 'storeStudentWithAccount')->name('storestudentwithaccount');
            Route::get('/{id}/edit', 'edit')->name('editaccount');
            Route::post('/{id}/update', 'update')->name('updateaccount');
            Route::delete('/{id}', 'destroy')->name('deleteaccount');
        });

        Route::prefix('subjects')->controller(SubjectController::class)->group(function () {
            Route::get('/', 'index')->name('subjects');
            Route::get('/add', 'create')->name('addsubject');
            Route::post('/add', 'store')->name('storesubject');
            Route::get('/{id}/edit', 'edit')->name('editsubject');
            Route::post('/{id}/update', 'update')->name('updatesubject');
            Route::delete('/{id}', 'destroy')->name('deletesubject');
            Route::get('/{id}/grading', 'grading')->name('subject.grading');
            Route::post('/{id}/grading/store', 'storeGrading')->name('subject.grading.store');
            Route::delete('/{id}/grading/clear', 'clearGrading')->name('subject.grading.clear');
        });

        Route::prefix('school-year')->controller(SchoolYearController::class)->group(function () {
            Route::get('/', 'index')->name('school-year');
            Route::post('/', 'store')->name('school-year.store');
            Route::post('/decade', 'storeDecade')->name('school-year.decade.store');
            Route::post('/{schoolYear}/audit', 'toggleAudit')->name('school-year.audit.toggle');
            Route::post('/{schoolYear}/archive', 'archive')->name('school-year.archive');
            Route::post('/{schoolYear}/activate', 'activate')->name('school-year.activate');
            Route::post('/promotions/preview', 'previewPromotions')->name('school-year.promotions.preview');
            Route::post('/promotions/execute', 'executePromotions')->name('school-year.promotions.execute');
        });

        Route::prefix('tags')->controller(TagController::class)->group(function () {
            Route::get('/', 'index')->name('tags');
            Route::get('/add', 'create')->name('addtag');
            Route::post('/add', 'store')->name('storetag');
            Route::get('/{id}/edit', 'edit')->name('edittag');
            Route::post('/{id}/update', 'update')->name('updatetag');
            Route::delete('/{id}', 'destroy')->name('deletetag');
        });

        Route::get('/analytics', [AdminAnalyticsController::class, 'index'])
            ->name('analytics');

        Route::prefix('teachers')->controller(TeacherController::class)->group(function () {
            Route::get('/', 'index')->name('teachers');
            Route::get('/add', 'create')->name('addteacher');
            Route::post('/add', 'store')->name('storeteacher');
            Route::get('/{id}/edit', 'edit')->name('editteacher');
            Route::post('/{id}/update', 'update')->name('updateteacher');
            Route::post('/{id}/status', 'toggleStatus')->name('teachers.toggle-status');
            Route::delete('/{id}', 'destroy')->name('deleteteacher');
        });

        Route::prefix('students')->controller(StudentController::class)->group(function () {
            Route::get('/', 'index')->name('students');
            Route::get('/add', 'create')->name('addstudent');
            Route::post('/add', 'store')->name('storestudent');
            Route::get('/{id}/enrollment', 'enrollment')->name('student.enrollment');
            Route::get('/{id}/edit', 'edit')->name('editstudent');
            Route::post('/{id}/update', 'update')->name('updatestudent');
            Route::delete('/{id}', 'destroy')->name('deletestudent');
        });

        Route::prefix('sections')->controller(SectionController::class)->middleware('admin.current.section')->group(function () {
            Route::get('/', 'index')->name('sections');
            Route::get('/add', 'create')->name('addsection');
            Route::post('/add', 'store')->name('storesection');
            Route::get('/{id}/edit', 'edit')->name('editsection');

             Route::delete('/{id}/delete', 'destroy')->name('section.delete');

            Route::get('/{id}/adviser', 'adviser')->name('section.adviser');
            Route::get('/{id}/addadviser', 'addadviser')->name('section.addadviser');
            Route::post('/{id}/assignadviser', 'assignAdviser')->name('section.assignadviser');
            Route::delete('/{id}/removeadviser', 'removeAdviser')->name('section.removeadviser');

            Route::get('/{id}/students', 'students')->name('section.students');
            Route::get('/{id}/addstudent', 'addStudent')->name('section.addstudent');
            Route::post('/{id}/students/add', 'addStudents')->name('section.addstudents');
            Route::delete('/{section}/students/{student}/remove', 'removeStudent')->name('section.removestudent');

            Route::get('/{id}/subjects', 'subjects')->name('section.subjects');
            Route::get('/{id}/addsubject', 'addSubject')->name('section.addsubject');
            Route::post('/{id}/addsubject', 'addSubjects')->name('section.addsubjects');
            Route::delete('/{section}/subjects/{subject}/remove', 'removeSubject')->name('section.removesubject');
            Route::post('/{section}/subjects/{subject}/schedule', [SectionController::class, 'saveSchedule'])->name('admin.section.subject.schedule');
            Route::delete(
    '/{section}/subjects/{subject}/remove-schedule',
    'removeSchedule'
)->name('section.subject.removeSchedule');

            
            //For scheduling (new)
            Route::get(
                '/admin/sections/{section}/schedule',
                [SectionScheduleController::class, 'index']
            )->name('section.schedule');



            Route::get('/{section_id}/subjects/{subject_id}/assign-teacher', 'assignTeacher')->name('section.assignteacher');
            Route::post('/{section_id}/subjects/{subject_id}/teachers/{teacher_id}/assign', 'saveAssignment')->name('section.saveassignment');
            Route::get('/{section_id}/subjects/{subject_id}/teachers/search', 'searchTeachers')->name('section.searchteachers');
            Route::delete('/{section_id}/subjects/{subject_id}/teachers/{teacher_id}/remove', 'removeAssignment')->name('section.removeassignment');
        });
    });

Route::middleware(['auth', 'role:teacher', 'teacher.active', 'teacher.current.section'])
    ->prefix('teacher')
    ->name('teacher.')
    ->group(function () {
        Route::get('/dashboard', [TeacherClassController::class, 'dashboard'])
            ->name('dashboard');

        
        //Edit Profile
        Route::get('/teacher/profile', [TeacherProfileController::class, 'edit'])
            ->name('profile');
        // Save edit profile
        Route::post('/teacher/teacher/profile', [TeacherProfileController::class, 'update'])
            ->name('profile.update');


        Route::get('/classes', [TeacherClassController::class, 'index'])->name('classes');
        Route::get('/questions', [QuestionBankController::class, 'page'])->name('questions');
        Route::prefix('/question-bank/subjects/{subject_id}')
            ->controller(QuestionBankController::class)
            ->name('question-bank.subject.')
            ->group(function () {
                Route::get('/', 'subjectIndex')->name('index');
                Route::post('/', 'subjectStore')->name('store');
                Route::get('/quizzes', 'subjectQuizzes')->name('quizzes');
                Route::post('/import-to-quiz', 'subjectImportToQuiz')->name('import-to-quiz');
                Route::get('/{item}', 'subjectShow')->name('show');
                Route::put('/{item}', 'subjectUpdate')->name('update');
                Route::delete('/{item}', 'subjectArchive')->name('archive');
                Route::post('/{item}/restore', 'subjectRestore')->name('restore');
            });

        // Head Grouping
        Route::get('/class/{section_id}/{subject_id}', [TeacherClassController::class, 'show'])->name('class.show');

        Route::post('/class/{sectionId}/{subjectId}/cover', [TeacherClassController::class, 'updateCover'])->name('class.updateCover');

        Route::prefix('announcements')->controller(AnnouncementController::class)->group(function () {
            Route::get('/', 'index')->name('announcements.index');
            Route::get('/create', 'create')->name('announcements.create');
            Route::post('/', 'store')->name('announcements.store');
            Route::delete('/{id}', 'destroy')->name('announcements.destroy');
            Route::post('/{id}/reply', 'reply')->name('announcements.reply');
        });

        Route::get('/class/{section_id}/{subject_id}/students', [TeacherClassController::class, 'showStudents'])->name('students');

        Route::get('/class/{section_id}/{subject_id}/lessons', [TeacherClassController::class, 'showLessons'])
            ->name('lesson');
        Route::get('/class/{section_id}/{subject_id}/lessons/create', [TeacherClassController::class, 'createLesson'])
            ->name('lesson.create');
        Route::get('/class/{section_id}/{subject_id}/lessons/{lesson_id}', [TeacherClassController::class, 'viewLesson'])
            ->name('lesson.show');
        Route::post('/class/{lesson}/upload', [TeacherClassController::class, 'uploadLesson'])
            ->name('lesson.upload');
        Route::post('/class/{section}/{subject}/lessons', [TeacherClassController::class, 'storeLesson'])
            ->name('lesson.store');
        Route::put('/class/{section}/{subject}/lessons/{lesson_id}', [TeacherClassController::class, 'updateLesson'])
            ->name('lesson.update');
        Route::delete('/class/lesson/{lesson_id}/files/{media_id}', [TeacherClassController::class, 'removeLessonFile'])
            ->name('lesson.file.remove');
        Route::delete('/class/lesson/{lesson_id}', [TeacherClassController::class, 'deleteLesson'])
            ->name('lesson.delete');
        //For rearranging lessons
        Route::post('/class/lessons/reorder', [TeacherClassController::class, 'reorder'])
            ->name('lesson.reorder');

        Route::get('/class/{section_id}/{subject_id}/questions', [TeacherClassController::class, 'showQuestions'])
            ->name('questions.legacy');
        Route::prefix('/class/{section_id}/{subject_id}/question-bank')
            ->controller(QuestionBankController::class)
            ->group(function () {
                Route::get('/', 'index')->name('question-bank.index');
                Route::post('/', 'store')->name('question-bank.store');
                Route::get('/quizzes', 'quizzes')->name('question-bank.quizzes');
                Route::post('/import-to-quiz', 'importToQuiz')->name('question-bank.import-to-quiz');
                Route::get('/{item}', 'show')->name('question-bank.show');
                Route::put('/{item}', 'update')->name('question-bank.update');
                Route::delete('/{item}', 'archive')->name('question-bank.archive');
                Route::post('/{item}/restore', 'restore')->name('question-bank.restore');
            });

        Route::get('/reviewers', [ReviewerController::class, 'index'])
            ->name('reviewer.index');
        Route::get('/reviewers/create', [ReviewerController::class, 'create'])
            ->name('reviewer.create');
        Route::post('/reviewers', [ReviewerController::class, 'store'])
            ->name('reviewer.store');
        Route::get('/reviewers/{reviewer}/edit', [ReviewerController::class, 'edit'])
            ->name('reviewer.edit');
        Route::put('/reviewers/{reviewer}', [ReviewerController::class, 'update'])
            ->name('reviewer.update');
        Route::get('/reviewers/{reviewer}', [ReviewerController::class, 'show'])
            ->name('reviewer.show');
        Route::delete('/reviewers/{reviewer}', [ReviewerController::class, 'destroy'])
            ->name('reviewer.destroy');

        //For h5p integration
        Route::get('/reviewers/h5p/create', [ReviewerController::class, 'h5pIndex'])
            ->name('reviewer.h5p');
        //For h5p saving
        Route::post('/reviewers/h5p/create/save', [ReviewerController::class, 'h5pSave'])
            ->name('reviewer.h5p.save');
        //For h5p preview
        Route::get('/reviewers/h5p/{reviewer}', [ReviewerController::class, 'h5pShow'])
            ->name('reviewers.h5p.show');

        // For deleting H5P reviewers (teacher)
        Route::delete('/reviewers/h5p/{reviewer}', [ReviewerController::class, 'h5pDestroy'])
            ->name('reviewer.h5p.destroy');

        

        Route::get('/classwork/{section_id}/{subject_id}', [TeacherClassController::class, 'classwork'])->name('classwork.view');
        //For rearranging lessons
        Route::post('/classwork/reorder', [TeacherClassController::class, 'reorderClasswork'])
            ->name('classwork.reorder');

        Route::post('/announcements/{announcement}/reply', [AnnouncementController::class, 'reply'])
            ->name('announcements.reply');

        Route::get('/attendance/{section_id}/{subject_id}', [TeacherClassController::class, 'attendance'])
            ->name('attendance.view');
        Route::get('/attendance/score/{section_id}/{subject_id}', [TeacherClassController::class, 'attendanceScore'])
            ->name('attendance.score');
        Route::post('attendance/{section_id}/{subject_id}', [TeacherClassController::class, 'storeAttendance'])
            ->name('attendance.store');
        Route::get('teacher/attendance/record/{attendance}', [TeacherClassController::class, 'attendanceRecord'])->name('attendance.record');
        Route::post('/teacher/attendance/{attendance}/save', [TeacherClassController::class, 'saveAttendanceStatus'])
            ->name('attendance.saveStatus');
        Route::post(
            '/teacher/attendance/score/{section}/{subject}',
            [TeacherClassController::class, 'storeAttendanceConfiguration']
        )->name('attendance.scores.store');
        Route::post(
            '/teacher/grade/preview/{section}/{subject}',
            [TeacherClassController::class, 'previewGrades']
        )->name('grade.preview');

        



        Route::post('/classwork//{section_id}/{subject_id}/activities/store', [TeacherClassController::class, 'storeActivity'])
            ->name('activities.store');
        Route::get('/teacher/classwork/{section_id}/{subject_id}/{activity_id}', [TeacherClassController::class, 'viewSelected'])
            ->name('classwork.view.selected');

        Route::post('/teacher/activity/{activity}/update', [TeacherClassController::class, 'updateActivity'])
            ->name('activity.update');
            
        Route::delete('/teacher/activity/file/{media}', [TeacherClassController::class, 'removeActivityFile'])
            ->name('activity.file.remove');
        Route::delete('/teacher/activity/{id}/delete', [TeacherClassController::class, 'destroyActivity'])
            ->name('activity.delete');
        Route::post('/class/{section_id}/{subject_id}/activity/{activity}/tags', [TeacherClassController::class, 'updateActivityTags'])
            ->name('activity.tags.update');
        Route::get('teacher/sections/{section_id}/subjects/{subject_id}/activities/{activity_id}/submissions', [TeacherClassController::class, 'viewSubmissions'])
            ->name('classwork.submissions');
        Route::post('/submission/{submission}/grade', [TeacherClassController::class, 'gradeSubmission'])
            ->name('submission.grade');
        Route::post(
            'submission/{submission}/remove-grade',
            [TeacherClassController::class, 'removeGrade']
        );

        //Storing evaluations
        Route::post('/classwork/{section_id}/{subject_id}/evaluation/store', [EvaluationController::class, 'storeEvaluation'])
            ->name('evaluation.store');
        //View Evluation
        Route::get('/teacher/section/{section_id}/subject/{subject_id}/evaluation/{evaluation_id}', 
            [EvaluationController::class, 'viewEvaluation']
        )->name('evaluation.view');
        //View Evluation Settings
        Route::get('/teacher/section/{section_id}/subject/{subject_id}/evaluation/{evaluation_id}/settings', 
            [EvaluationController::class, 'evaluationSettings']
        )->name('evaluation.settings');
        // save settings
        Route::put(
            '/teacher/section/{section_id}/subject/{subject_id}/evaluation/{evaluation_id}/update',
            [EvaluationController::class, 'updateEvaluation']
        )->name('evaluation.update');
        Route::post(
            '/teacher/section/{section_id}/subject/{subject_id}/evaluation/{evaluation_id}/tags',
            [EvaluationController::class, 'updateEvaluationTags']
        )->name('evaluation.tags.update');

        //delete evluation
        Route::delete(
            '/teacher/section/{section_id}/subject/{subject_id}/evaluation/{evaluation_id}/delete',
            [EvaluationController::class, 'deleteEvaluation']
        )->name('evaluation.delete');
        //Save evluation Scores
        Route::post(
            '/teacher/section/{section_id}/subject/{subject_id}/evaluation/{evaluation_id}/scores/save',
            [EvaluationController::class, 'saveScores']
        )->name('evaluation.scores.save');




        Route::get('/class/{section}/{subject}/classwork/quiz', [QuizController::class, 'create'])
            ->name('quiz.create');
        Route::post('/class/{section}/{subject}/classwork/quiz/store', [QuizController::class, 'store'])
            ->name('quiz.store');
        Route::get('/class/{section}/{subject}/classwork/quiz/{quiz}/add', [QuizController::class, 'add'])
            ->name('quiz.add');
        Route::get('/class/{section}/{subject}/classwork/quiz/{quiz}/edit', [QuizController::class, 'edit'])
            ->name('quiz.edit');
        Route::get('/class/{section}/{subject}/classwork/quiz/{quiz}/settings', [QuizController::class, 'settings'])
            ->name('quiz.settings');
        Route::put('/class/{section}/{subject}/classwork/quiz/{quiz}/settings', [QuizController::class, 'settingsUpdate'])
            ->name('quiz.settingsUpdate');
        Route::post('/class/{section}/{subject}/classwork/quiz/{quiz}/tags', [QuizController::class, 'updateTags'])
            ->name('quiz.tags.update');
        Route::put('/class/{section}/{subject}/classwork/quiz/{quiz}/update', [QuizController::class, 'update'])
            ->name('quiz.update');
        Route::post('/teacher/section/{section}/subject/{subject}/quiz/{quiz}/questions', [QuizController::class, 'storeQuestions'])
            ->name('quiz.storeQuestions');
        Route::put('/teacher/section/{section}/subject/{subject}/quiz/{quiz}/questions', [QuizController::class, 'updateQuestions'])
            ->name('quiz.updateQuestions');
        Route::get(
                '/section/{section}/subject/{subject}/quiz/{quiz}/submissions',
                [QuizController::class, 'viewSubmissions']
            )->name('quiz.submissions');

            Route::get(
                '/section/{section}/subject/{subject}/quiz/{quiz}/submissions/student/{student}',
                [QuizController::class, 'viewSubmissionDetail']
            )->name('quiz.submissions.show');

            Route::post(
                '/section/{section}/subject/{subject}/quiz/{quiz}/submissions/student/{student}',
                [QuizController::class, 'updateSubmissionDetail']
            )->name('quiz.submissions.update');


        Route::delete('/class/{section}/{subject}/classwork/quiz/{quiz}/delete', [QuizController::class, 'destroy'])
            ->name('quiz.delete');

       Route::get('/class/{section_id}/{subject_id}/grade',[TeacherClassController::class, 'gradePage']
            )->name('grade.report');
        

        Route::get('/class/{section_id}/{subject_id}/analytics', [TeacherClassController::class, 'analytics'])
            ->name('analytics');
        Route::post('/class/{section_id}/{subject_id}/analytics/seed', [TeacherClassController::class, 'seedAnalyticsData'])
            ->name('analytics.seed');
        Route::delete('/class/{section_id}/{subject_id}/analytics/seed', [TeacherClassController::class, 'clearAnalyticsData'])
            ->name('analytics.clear');

        Route::get('/class/{section_id}/{subject_id}/grade/setup',[TeacherClassController::class, 'gradeSetup']
            )->name('grade.setup');
        

        Route::post( '/class/{section_id}/{subject_id}/grade/compute',[TeacherClassController::class, 'computeGrades']
)->name('grade.compute');




    });

Route::middleware(['auth', 'role:student', 'student.current.section'])
    ->prefix('student')
    ->name('student.')
    ->group(function () {
         Route::get('/dashboard', [StudentClassController::class, 'dashboard'])
            ->name('dashboard');
        Route::get('/calendar', [StudentClassController::class, 'calendar'])
            ->name('calendar');
        //For viewing overall grades
        Route::get('/grades', [GradePageController::class, 'index'])
            ->name('grades');

        //Edit Profile
        Route::get('/student/profile', [StudentProfileController::class, 'edit'])
            ->name('profile');
        Route::post('/student/profile', [StudentProfileController::class, 'update'])
            ->name('profile.update');

        Route::get('/classes', [StudentClassController::class, 'index'])
            ->name('classes');
        Route::get('/class/{section_id}/{subject_id}', [StudentClassController::class, 'show'])
            ->name('class.subject');
        Route::get('/class/{section_id}/{subject_id}/lessons', [StudentClassController::class, 'showLessons'])
            ->name('lesson');
        Route::get('/class/{section_id}/{subject_id}/lessons/{lesson_id}', [StudentClassController::class, 'showLesson'])
            ->name('lesson.show');
        Route::get('/reviewers/{reviewer}', [StudentClassController::class, 'showReviewer'])
            ->name('reviewer.show');
        Route::post('/reviewers/{reviewer}/complete', [StudentClassController::class, 'completeReviewer'])
            ->name('reviewer.complete');
        Route::get('/reviewers/h5p/{reviewer}', [StudentClassController::class, 'showH5pReviewer'])
            ->name('reviewer.h5p.show');
        Route::get('/class/{section_id}/{subject_id}/students', [StudentClassController::class, 'showStudents'])
            ->name('students');
        Route::get('/class/{section_id}/{subject_id}/grade', [StudentClassController::class, 'grade'])
        ->name('grade');
        Route::post(
            '/student/class/{section_id}/{subject_id}/grade/preview',
            [StudentClassController::class, 'gradePreview']
        )->name('grade.preview');
        Route::get('/class/{section_id}/{subject_id}/classwork', [StudentClassController::class, 'showClasswork'])
            ->name('classwork');
        Route::get('/class/classwork/{section_id}/{subject_id}/{activity_id}', [StudentClassController::class, 'viewActivity'])->name('classwork.view.activity');
        Route::get('/class/{section_id}/{subject_id}/analytics', [StudentClassController::class, 'analytics'])
            ->name('analytics');

        Route::get('/reviewer-recommendation', [StudentClassController::class, 'reviewerRecommendation'])
            ->name('reviewer.recommendation');
        Route::post('/reviewer-recommendation/generate',[StudentClassController::class, 'reviewerGenerate']
            )->name('reviewer.generate');
        Route::get(
            '/reviewer-recommendation/generated_reviewers',
            [StudentClassController::class, 'generatedReviewers']
        )->name('reviewer.generated.index');

        Route::get(
            '/reviewer-recommendation/generated_reviewers/{generatedReviewer}',
            [StudentClassController::class, 'generatedView']
        )->name('reviewer.generated.view');

        Route::delete('/reviewer/generated/{reviewer}',
            [StudentClassController::class, 'deleteGenerated']
        )->name('reviewer.generated.delete');
        Route::post('/student/reviewer/generated/{reviewer}/complete',
            [StudentClassController::class, 'completeGenerated']
        )->name('reviewer.generated.complete');

        Route::post('/announcements/{id}/reply', [AnnouncementController::class, 'reply'])->name('announcements.reply');

        // Route::post('/activity/{activity}/submit-essay', [ActivitySubmissionController::class, 'submitEssay'])
        //     ->name('submission.essay');
        // Route::put('/submission/essay/{submission}', [ActivitySubmissionController::class, 'updateEssay'])
        //     ->name('submission.essay.update');

        // Route::delete('/submission/essay/{submission}', [ActivitySubmissionController::class, 'deleteEssay'])->name('submission.essay.delete');

        // Route::post(
        //     '/activity/{activity}/submit-files',
        //     [ActivityFileSubmissionController::class, 'submitFiles']
        // )->name('submission.files');

        // Route::put(
        //     '/student/submission/files/{submission}/edit',
        //     [ActivityFileSubmissionController::class, 'updateFiles']
        // )->name('submission.files.edit');

        // Route::delete(
        //     '/student/submission/files/{submission}/delete',
        //     [ActivityFileSubmissionController::class, 'deleteSubmission']
        // )->name('submission.files.delete');

        Route::post('/student/activity/{activity}/submit',
    [UnifiedSubmitController::class, 'submit'])
    ->name('activity.unified.submit');

Route::post('/student/activity/{activity}/update',
    [UnifiedSubmitController::class, 'update'])
    ->name('activity.unified.update');

Route::post('/student/activity/{activity}/remove',
    [UnifiedSubmitController::class, 'remove'])
    ->name('activity.unified.remove');





        Route::get('/class/{section_id}/{subject_id}/attendance', [StudentClassController::class, 'attendance'])
            ->name('attendance');

        Route::get('/class/{section_id}/{subject_id}/quiz/{quiz_id}/preview', [StudentClassController::class, 'previewQuiz'])
            ->name('quiz.preview');

        Route::get('/class/{section_id}/{subject_id}/quiz/{quiz_id}/attempt', [StudentClassController::class, 'attemptQuiz'])
            ->name('quiz.take');

        Route::post('/class/{section}/{subject}/classwork/quiz/{quiz}/submit', [StudentClassController::class, 'submitQuiz'])
            ->name('quiz.submit');

        Route::get('/class/{section_id}/{subject_id}/classwork/quiz/{quiz_id}/response', [StudentClassController::class, 'showQuizResponse'])
            ->name('classwork.quiz.response');
    });


Route::get('/redirect-user', function () {
    $user = auth()->user();

    if ($user->hasRole('admin')) {
        return redirect()->route('admin.dashboard');
    } elseif ($user->hasRole('teacher')) {
        return redirect()->route('teacher.dashboard');
    } elseif ($user->hasRole('student')) {
        return redirect()->route('student.dashboard');
    }

    return redirect()->route('home');
})->middleware('auth')->name('redirect.user');



require __DIR__ . '/auth.php';
