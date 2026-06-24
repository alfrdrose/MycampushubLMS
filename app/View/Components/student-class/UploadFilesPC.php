<?php

namespace App\View\Components\student-class;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class UploadFilesPC extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct($maxFiles = 5)
    {
        $this->maxFiles = $maxFiles;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.student-class.upload-files-p-c');
    }
}
