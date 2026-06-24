<?php

namespace App\View\Components;

use Illuminate\View\Component;

class SiderBarTeacherMobile extends Component
{
    public array $navs;

    public function __construct(array $navs = [])
    {
        $this->navs = $navs ?: [
            ['name' => 'Dashboard', 'icon' => 'home', 'route' => 'teacher.dashboard'],
            ['name' => 'Classes', 'icon' => 'users-alt-3', 'route' => 'teacher.classes'],
            ['name' => 'Questions', 'icon' => 'question-circle', 'route' => 'teacher.questions'],
        ];
    }

    public function isRouteInvalid($route): bool
    {
        return empty($route) || str_starts_with($route, '#');
    }

    public function getRoute($route): string
    {
        return $this->isRouteInvalid($route) ? '#' : route($route);
    }

    public function isActive($route, $default = false): bool
    {
        if ($default || $this->isRouteInvalid($route)) {
            return $default;
        }

        if (request()->routeIs($route)) {
            return true;
        }

        if ($route === 'teacher.reviewer.index' && request()->routeIs('teacher.reviewer.*')) {
            return true;
        }

        return false;
    }

    public function render()
    {
        return view('components.sider-bar-teacher-mobile');
    }
}
