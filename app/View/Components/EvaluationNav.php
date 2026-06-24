<?php

namespace App\View\Components;

use Illuminate\View\Component;

class EvaluationNav extends Component
{
    public $active;
    public $sectionId;
    public $subjectId;
    public $evaluationId;
    public $items;

    /**
     * Create a new component instance.
     */
    public function __construct($active = null, $sectionId = null, $subjectId = null, $evaluationId = null)
    {
        $this->active = $active;
        $this->sectionId = $sectionId;
        $this->subjectId = $subjectId;
        $this->evaluationId = $evaluationId;

        // Build the nav items with defensive checks for missing IDs
        $viewSelectedUrl = '#';
        $settingsUrl = '#';

        if ($this->sectionId && $this->subjectId && $this->evaluationId) {
            // These are the route names defined in routes/web.php (inside teacher group)
            // -> name('teacher.classwork.view.selected') and name('teacher.classwork.submissions')
            try {
                $viewSelectedUrl = route('teacher.evaluation.view', [
                    $this->sectionId,
                    $this->subjectId,
                    $this->evaluationId
                ]);
            } catch (\Exception $e) {
                $viewSelectedUrl = '#';
            }

            try {
                $settingsUrl = route('teacher.evaluation.settings', [
                    $this->sectionId,
                    $this->subjectId,
                    $this->evaluationId
                ]);
            } catch (\Exception $e) {
                $settingsUrl = '#';
            }
        }

        $this->items = [
            [
                'key' => 'Scores',
                'label' => 'Scores',
                'url' => $viewSelectedUrl,
            ],
            [
                'key' => 'Settings',
                'label' => 'Settings',
                'url' => $settingsUrl,
            ],
        ];
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render()
    {
        return view('components.evaluation-nav');
    }
}
