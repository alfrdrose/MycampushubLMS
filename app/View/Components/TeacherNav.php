<?php

namespace App\View\Components;

use Illuminate\View\Component;

class TeacherNav extends Component
{
    public $active;
    public $sectionId;
    public $subjectId;

    public function __construct($active = null, $sectionId = null, $subjectId = null)
    {
        $this->active = $active;
        $this->sectionId = $sectionId;
        $this->subjectId = $subjectId;
    }

    public function getItems()
    {
        $baseRoute = route('teacher.class.show', [$this->sectionId, $this->subjectId]);
        $studentRoute = route('teacher.students', [$this->sectionId, $this->subjectId]);
        $lessonRoute = route('teacher.lesson', [$this->sectionId, $this->subjectId]);
        $attendanceRoute = route('teacher.attendance.view', [$this->sectionId, $this->subjectId]);
        $classworkRoute = route('teacher.classwork.view' , [$this->sectionId, $this->subjectId]);
        $gradesRoute = route('teacher.grade.report' , [$this->sectionId, $this->subjectId]);
        $analyticsRoute = route('teacher.analytics', [$this->sectionId, $this->subjectId]);

        return [
            ['label' => 'Board',     'url' => $baseRoute, 'key' => 'board'],
            ['label' => 'Lessons',   'url' => $lessonRoute, 'key' => 'lessons'],
            ['label' => 'Classwork', 'url' => $classworkRoute, 'key' => 'classwork'],
            ['label' => 'Students',  'url' => $studentRoute, 'key' => 'students'],
            ['label' => 'Attendance',     'url' => $attendanceRoute, 'key' => 'attendance'],
            ['label' => 'Grade',     'url' => $gradesRoute, 'key' => 'grade'],
            ['label' => 'Analytics', 'url' => $analyticsRoute, 'key' => 'analytics'],
            
        ];
    }

    public function render()
    {
        return view('components.teacher-nav', [
            'items' => $this->getItems(),
            'active' => $this->active,
        ]);
    }
}
