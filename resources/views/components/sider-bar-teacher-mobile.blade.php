@php
    $getRoute = fn($route) => empty($route) || str_starts_with($route, '#') ? '#' : route($route);
    $isActive = function ($route) {
        if (empty($route) || str_starts_with($route, '#')) {
            return false;
        }
        if (request()->routeIs($route)) {
            return true;
        }
        if ($route === 'teacher.reviewer.index' && request()->routeIs('teacher.reviewer.*')) {
            return true;
        }
        return false;
    };
@endphp

<div class="px-4 py-4">
    <nav class="mt-4 space-y-2">
        @foreach ($navs as $item)
            <a href="{{ $getRoute($item['route']) }}"
               class="flex items-center gap-2 py-2 px-2 rounded hover:bg-primaryColor hover:text-white
                      {{ $isActive($item['route']) ? 'bg-primaryColor text-white' : 'text-gray-700 dark:text-gray-200' }}">
                <i class="icofont-{{ $item['icon'] }}"></i>
                {{ $item['name'] }}
            </a>
        @endforeach

        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button class="py-2 text-left text-red-500 w-full">Logout</button>
        </form>
    </nav>
</div>
