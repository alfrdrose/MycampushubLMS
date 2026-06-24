<?php

namespace App\View\Components;

use Illuminate\View\Component;
use App\Models\Quiz;

class QuizNav extends Component
{
    public $active;
    public $sectionId;
    public $subjectId;
    public $quizId;
    public $items;

    public function __construct($active = null, $sectionId = null, $subjectId = null, $quizId = null)
    {
        $this->active = $active;
        $this->sectionId = $sectionId;
        $this->subjectId = $subjectId;
        $this->quizId = $quizId;
        $this->items = [];

        $quiz = $quizId ? Quiz::find($quizId) : null;
        $hasQuestions = $quiz && $quiz->questions()->exists();

        $questionsUrl = $hasQuestions
            ? route('teacher.quiz.edit', [$sectionId, $subjectId, $quizId])
            : route('teacher.quiz.add', [$sectionId, $subjectId, $quizId]);

        $this->items[] = [
            'key' => $hasQuestions ? 'edit_questions' : 'add_questions',
            'label' => $hasQuestions ? 'Edit Questions' : 'Add Questions',
            'url' => $questionsUrl,
        ];

        $this->items[] = [
            'key' => 'settings',
            'label' => 'Settings',
            'url' => $quizId ? route('teacher.quiz.settings', [$sectionId, $subjectId, $quizId]) : '#',
        ];

        $this->items[] = [
            'key' => 'submissions',
            'label' => 'Submissions',
            'url' => $quizId ? route('teacher.quiz.submissions', [$sectionId, $subjectId, $quizId]) : '#',
        ];
    }

    public function render()
    {
        return view('components.quiz-nav');
    }
}
