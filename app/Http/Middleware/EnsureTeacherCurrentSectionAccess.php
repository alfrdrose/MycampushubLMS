<?php

namespace App\Http\Middleware;

use App\Models\SchoolYear;
use App\Models\Activity;
use App\Models\ActivitySubmission;
use App\Models\AnnouncementModel;
use App\Models\Attendance;
use App\Models\Evaluation;
use App\Models\LessonModel;
use App\Models\Quiz;
use App\Models\SectionModel;
use App\Models\SectionSubject;
use App\Models\SubjectModel;
use App\Models\SubjectTeacher;
use Closure;
use Illuminate\Http\Request;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\Response;

class EnsureTeacherCurrentSectionAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $section = $this->resolveSection($request);
        if (! $section) {
            return $next($request);
        }

        $teacher = $request->user()?->teacher;
        $activeSchoolYearId = SchoolYear::activeId();
        $schoolYear = $section->schoolYear;

        if (! $teacher || ! $schoolYear) {
            abort(403, 'No school year is currently available for this class.');
        }

        $isActiveSchoolYear = $activeSchoolYearId !== null
            && (int) $section->school_year_id === $activeSchoolYearId;
        $isAuditAccessible = (bool) $schoolYear->teacher_audit_enabled;

        if (! $isActiveSchoolYear && ! $isAuditAccessible) {
            abort(403, 'This class is not accessible outside the current or audit-enabled school years.');
        }

        if (
            ! $isActiveSchoolYear
            && $isAuditAccessible
            && ! $this->isAuditSafeRequest($request)
        ) {
            abort(403, 'Audit-mode classes are read-only.');
        }

        $subjectId = $this->resolveSubjectId($request);

        $assignmentQuery = SubjectTeacher::query()
            ->where('teacher_model_id', $teacher->id)
            ->where('section_model_id', $section->id);

        if ($subjectId !== null) {
            $assignmentQuery->where('subject_model_id', $subjectId);
        }

        if (! $assignmentQuery->exists()) {
            abort(403, 'You are not assigned to this class.');
        }

        return $next($request);
    }

    private function resolveSection(Request $request): ?SectionModel
    {
        foreach (['section', 'section_id', 'sectionId', 'section_model_id'] as $parameter) {
            $value = $request->route($parameter);

            if ($value instanceof SectionModel) {
                return $value;
            }

            if (is_numeric($value)) {
                return SectionModel::find((int) $value);
            }
        }

        if ($section = $this->sectionFromSectionSubject($request)) {
            return $section;
        }

        foreach (['activity', 'activity_id'] as $parameter) {
            $value = $request->route($parameter);

            if (! is_numeric($value)) {
                continue;
            }

            $activity = Activity::with('sectionSubject.section')->find((int) $value);
            if ($activity?->sectionSubject?->section) {
                return $activity->sectionSubject->section;
            }
        }

        $submission = $request->route('submission');
        if (is_numeric($submission)) {
            $submissionModel = ActivitySubmission::with('activity.sectionSubject.section')->find((int) $submission);
            if ($submissionModel?->activity?->sectionSubject?->section) {
                return $submissionModel->activity->sectionSubject->section;
            }
        }

        foreach (['lesson', 'lesson_id'] as $parameter) {
            $value = $request->route($parameter);

            if (! is_numeric($value)) {
                continue;
            }

            $lesson = LessonModel::with('section')->find((int) $value);
            if ($lesson?->section) {
                return $lesson->section;
            }
        }

        $attendance = $request->route('attendance');
        if (is_numeric($attendance)) {
            $attendanceModel = Attendance::with('sectionSubject.section')->find((int) $attendance);
            if ($attendanceModel?->sectionSubject?->section) {
                return $attendanceModel->sectionSubject->section;
            }
        }

        foreach (['quiz', 'quizId', 'quiz_id'] as $parameter) {
            $value = $request->route($parameter);

            if (! is_numeric($value)) {
                continue;
            }

            $quiz = Quiz::with('section')->find((int) $value);
            if ($quiz?->section) {
                return $quiz->section;
            }
        }

        foreach (['evaluation', 'evaluation_id'] as $parameter) {
            $value = $request->route($parameter);

            if (! is_numeric($value)) {
                continue;
            }

            $evaluation = Evaluation::with('sectionSubject.section')->find((int) $value);
            if ($evaluation?->sectionSubject?->section) {
                return $evaluation->sectionSubject->section;
            }
        }

        $media = $request->route('media');
        if (is_numeric($media)) {
            $mediaModel = Media::find((int) $media);
            $mediaParent = $mediaModel?->model;

            if ($mediaParent instanceof Activity) {
                $mediaParent->loadMissing('sectionSubject.section');
                return $mediaParent->sectionSubject?->section;
            }

            if ($mediaParent instanceof LessonModel) {
                $mediaParent->loadMissing('section');
                return $mediaParent->section;
            }
        }

        $routeName = $request->route()?->getName();
        $genericId = $request->route('id');
        if (is_numeric($genericId)) {
            if ($routeName === 'teacher.activity.delete') {
                $activity = Activity::with('sectionSubject.section')->find((int) $genericId);
                if ($activity?->sectionSubject?->section) {
                    return $activity->sectionSubject->section;
                }
            }

            if (
                in_array($routeName, ['teacher.announcements.destroy', 'teacher.announcements.reply'], true)
            ) {
                $announcement = AnnouncementModel::with('sectionSubject.section')->find((int) $genericId);
                if ($announcement?->sectionSubject?->section) {
                    return $announcement->sectionSubject->section;
                }
            }
        }

        return null;
    }

    private function resolveSubjectId(Request $request): ?int
    {
        if ($sectionSubject = $this->resolveSectionSubject($request)) {
            return (int) $sectionSubject->subject_model_id;
        }

        foreach (['subject', 'subject_id', 'subjectId', 'subject_model_id'] as $parameter) {
            $value = $request->route($parameter);

            if ($value instanceof SubjectModel) {
                return (int) $value->id;
            }

            if (is_numeric($value)) {
                return (int) $value;
            }
        }

        return null;
    }

    private function isAuditSafeRequest(Request $request): bool
    {
        if (in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true)) {
            return true;
        }

        return in_array($request->route()?->getName(), [
            'teacher.grade.preview',
        ], true);
    }

    private function resolveSectionSubject(Request $request): ?SectionSubject
    {
        $sectionSubjectId = $request->route('section_subject_id') ?? $request->input('section_subject_id');

        if (! is_numeric($sectionSubjectId)) {
            return null;
        }

        return SectionSubject::with('section')->find((int) $sectionSubjectId);
    }

    private function sectionFromSectionSubject(Request $request): ?SectionModel
    {
        return $this->resolveSectionSubject($request)?->section;
    }
}
