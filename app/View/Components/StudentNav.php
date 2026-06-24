<?php

namespace App\View\Components;

use Illuminate\View\Component;

class StudentNav extends Component
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
        return [
            [
                'label' => 'Board',
                'url' => route('student.class.subject', [$this->sectionId, $this->subjectId]),
                'key' => 'board'
            ],
            [
                'label' => 'Lessons',
                'url' => route('student.lesson', [$this->sectionId, $this->subjectId]),
                'key' => 'lessons'
            ],
            [
                'label' => 'Classwork',
                'url' => route('student.classwork', [$this->sectionId, $this->subjectId]),
                'key' => 'classwork'
            ],
            [
                'label' => 'Students',
                'url' => route('student.students', [$this->sectionId, $this->subjectId]),
                'key' => 'students'
            ],
            [
                'label' => 'Grade',
                'url' => route('student.grade', [$this->sectionId, $this->subjectId]),
                'key' => 'grade'
            ],
            [
                'label' => 'Attendance',
                'url' => ($this->sectionId && $this->subjectId) 
                    ? route('student.attendance', [$this->sectionId, $this->subjectId]) 
                    : '#',
                'key' => 'attendance'
            ],
            [
                'label' => 'Analytics',
                'url' => ($this->sectionId && $this->subjectId)
                    ? route('student.analytics', [$this->sectionId, $this->subjectId])
                    : '#',
                'key' => 'analytics'
            ],

        ];
    }

    public function render()
    {
        return view('components.student-nav', [
            'items' => $this->getItems(),
            'active' => $this->active,
        ]);
    }
}
