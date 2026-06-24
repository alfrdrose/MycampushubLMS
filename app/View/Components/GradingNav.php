<?php
namespace App\View\Components;

use Illuminate\View\Component;

class GradingNav extends Component
{
    public $section;
    public $subject;

    public function __construct($section, $subject)
    {
        $this->section = $section;
        $this->subject = $subject;
    }

    public function render()
    {
        return view('components.grading-nav');
    }
}
