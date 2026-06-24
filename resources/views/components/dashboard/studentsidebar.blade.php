@php
    $main_navs = [
        [
            'name' => 'Dashboard',
            'icon' => 'home',
            'route' => 'student.dashboard',
        ],
        [
            'name' => 'Classes',
            'icon' => 'users-alt-3',
            'route' => 'student.classes'
        ],
        [
            'name' => 'Calendar',
            'icon' => 'ui-calendar',
            'route' => 'student.calendar'
        ],
        [
            'name' => 'Reviewer',
            'icon' => 'book',
            'route' => 'student.reviewer.recommendation'
        ],
        [
            'name' => 'Grades',
            'icon' => 'graduate',
            'route' => 'student.grades'
        ],
    ];

    if (!function_exists('isRouteInvalid')) {
        function isRouteInvalid($route) { return empty($route) || str_starts_with($route, '#'); }
    }
    if (!function_exists('getRoute')) {
        function getRoute($route) { return isRouteInvalid($route) ? '#' : route($route); }
    }
    if (!function_exists('isActive')) {
        function isActive($route, $default = false) {
            if ($default || isRouteInvalid($route)) {
                return $default;
            }
            if (Request::routeIs($route)) {
                return true;
            }
            if ($route === 'student.reviewer.recommendation' && Request::routeIs('student.reviewer.*')) {
                return true;
            }
            return false;
        }
    }
@endphp

<x-theme.sidebar>
    <nav class="flex flex-col h-full">

        <div class="px-4 py-4 font-semibold text-gray-700 dark:text-white">
            Side Navigation
        </div>

        <x-theme.sidebar.menu class="flex flex-col">
            @foreach ($main_navs as $item)
                <x-theme.sidebar.menu-item class="border-t border-gray-200 dark:border-gray-700">
                    <x-theme.sidebar.menu-link
                        :href="getRoute($item['route'])"
                        :active="isActive($item['route'])"
                        class="flex items-center gap-3 px-4 py-1"
                    >
                        <i class="icofont-{{ $item['icon'] }} text-xl"></i>
                        {{ $item['name'] }}
                    </x-theme.sidebar.menu-link>

                    @if(!empty($item['badge']) && $item['badge']['show'] ?? true)
                        <x-badge :variant="$item['badge']['variant'] ?? 'default'">{{ $item['badge']['value'] }}</x-badge>
                    @endif
                </x-theme.sidebar.menu-item>
            @endforeach
        </x-theme.sidebar.menu>
    </nav>
</x-theme.sidebar>
