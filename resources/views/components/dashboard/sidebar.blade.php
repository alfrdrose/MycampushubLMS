@php
    use App\Models;

    $account_count = Models\User::count();

    $main_navs = [
        [
            'name' => 'Dashboard',
            'icon' => 'home',
            'route' => 'admin.dashboard',
            'extras' => [
                'data-page' => 'dashboard',
            ],
        ],
        [
            'name' => 'Announcements',
            'icon' => 'bullhorn',
            'route' => 'admin.announcements',
        ],
        [
            'name' => 'Accounts',
            'icon' => 'users-alt-3',
            'route' => 'admin.accounts',
        ],
        [
            'name' => 'Teachers',
            'icon' => 'teacher',
            'route' => 'admin.teachers',
        ],
        [
            'name' => 'Students',
            'icon' => 'group-students',
            'route' => 'admin.students',
        ],
        [
            'name' => 'Subjects',
            'icon' => 'building-alt',
            'route' => 'admin.subjects'
        ],
        [
            'name' => 'School Year',
            'icon' => 'calendar',
            'route' => 'admin.school-year'
        ],
        [
            'name' => 'Class Sections',
            'icon' => 'group-students',
            'route' => 'admin.sections'
        ],
        [
            'name' => 'Tags',
            'icon' => 'tag',
            'route' => 'admin.tags',
        ],
        [
            'name' => 'Analytics',
            'icon' => 'chart-bar-graph',
            'route' => 'admin.analytics',
        ],
        [
            'name' => 'Notification Settings',
            'icon' => 'settings-alt',
            'route' => 'admin.emailnotification',
            'extras' => [
                'data-page' => 'settings',
            ],
        ],
        [
            'name' => 'Logs',
            'icon' => 'history',
            'route' => 'admin.logs',
            'extras' => [
                'data-page' => 'logs',
            ],
        ],

    ];

    if (!function_exists('isRouteInvalid')) {
        function isRouteInvalid($route)
        {
            return empty($route) || str_starts_with($route, '#');
        }
    }

    if (!function_exists('getRoute')) {
        function getRoute($route)
        {
            if (isRouteInvalid($route)) {
                return '#';
            }
            return route($route);
        }
    }

    if (!function_exists('isActive')) {
        function isActive($route, $default = false)
        {
            return $default || (!isRouteInvalid($route) && Request::routeIs($route));
        }
    }
@endphp

<x-theme.sidebar>
    
        <div class=" py-4 font-semibold text-gray-700 dark:text-white">
            Side Navigation
        </div>

    <x-theme.sidebar.menu>

        @foreach ($main_navs as $item)
            <x-theme.sidebar.menu-item class="flex justify-between items-center">
                <x-theme.sidebar.menu-link
                    :href="getRoute($item['route'])"
                    :active="isActive($item['route'], $item['active'] ?? false)"
                    {{ $attributes->merge($item['extras'] ?? []) }}
                >
                    <i class="icofont-{{ $item['icon'] }} text-xl"></i>
                    {{ $item['name'] }}
                </x-theme.sidebar.menu-link>

                @if(!empty($item['badge']) && ($item['badge']['show'] ?? true))
                    <x-badge :variant="$item['badge']['variant'] ?? 'default'">
                        {{ $item['badge']['value'] }}
                    </x-badge>
                @endif
            </x-theme.sidebar.menu-item>
        @endforeach

    </x-theme.sidebar.menu>

    {{-- MOBILE-ONLY LOGOUT --}}
    {{-- <div class="mt-auto pt-3 border-t border-gray-200 dark:border-gray-700 lg:hidden">
        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button
                type="submit"
                class="flex items-center gap-3 w-full
                       text-primaryColor hover:bg-red-50
                       dark:hover:bg-red-900/20
                       rounded transition"
            >
                <i class="icofont-logout text-xl"></i>
                <span class="font-medium">Logout</span>
            </button>
        </form>
    </div> --}}

</x-theme.sidebar>
