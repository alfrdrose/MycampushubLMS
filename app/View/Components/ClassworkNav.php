<?php

namespace App\View\Components;

use Illuminate\View\Component;

class ClassworkNav extends Component
{
    public $active;
    public $sectionId;
    public $subjectId;
    public $activityId;
    public $items;

    /**
     * Create a new component instance.
     */
    public function __construct($active = null, $sectionId = null, $subjectId = null, $activityId = null)
    {
        $this->active = $active;
        $this->sectionId = $sectionId;
        $this->subjectId = $subjectId;
        $this->activityId = $activityId;

        // Build the nav items with defensive checks for missing IDs
        $viewSelectedUrl = '#';
        $submissionsUrl = '#';

        if ($this->sectionId && $this->subjectId && $this->activityId) {
            // These are the route names defined in routes/web.php (inside teacher group)
            // -> name('teacher.classwork.view.selected') and name('teacher.classwork.submissions')
            try {
                $viewSelectedUrl = route('teacher.classwork.view.selected', [
                    $this->sectionId,
                    $this->subjectId,
                    $this->activityId
                ]);
            } catch (\Exception $e) {
                $viewSelectedUrl = '#';
            }

            try {
                $submissionsUrl = route('teacher.classwork.submissions', [
                    $this->sectionId,
                    $this->subjectId,
                    $this->activityId
                ]);
            } catch (\Exception $e) {
                $submissionsUrl = '#';
            }
        }

        $this->items = [
            [
                'key' => 'instructions',
                'label' => 'Instructions',
                'url' => $viewSelectedUrl,
            ],
            [
                'key' => 'submissions',
                'label' => 'Submissions',
                'url' => $submissionsUrl,
            ],
        ];
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render()
    {
        return view('components.classwork-nav');
    }
}
