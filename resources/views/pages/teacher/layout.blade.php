<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="app-url" content="{{ config('app.url') }}">

    <title>My Campus Hub LMS | Teachers</title>

    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@100;200;300;400;500;600;700;800;900&display=swap"
        rel="stylesheet">

    <!-- Stylesheets -->
    <link rel="stylesheet" href="{{ asset('edurock/assets/css/icofont.min.css') }}">
    <link rel="stylesheet" href="{{ asset('edurock/assets/css/video-modal.css')}}">
    <link rel="stylesheet" href="{{ asset('edurock/assets/css/aos.css')}}">

    @vite('resources/css/app.css')
    @yield('styles')
</head>

<body class="relative font-inter font-normal text-base leading-[1.8] bg-bodyBg dark:bg-bodyBg-dark overflow-x-hidden">

    <!-- Floating Sidebar Button -->
    <button id="sidebarFloatingButton"
        class="hidden fixed left-4 top-1/2 -translate-y-1/2 bg-primaryColor text-white w-10 h-10 rounded-full shadow-lg z-50">
        <i class="icofont-navigation-menu text-xl"></i>
    </button>

    {{-- Global UI Components --}}
    @if ($preloader)
        <x-theme.preloader />
    @endif

    <x-theme.fixed-shadow />

    @if ($darkmode)
        <x-theme.customizer />
    @endif

    @if ($scrollup)
        <x-theme.scroll-up />
    @endif

    <!-- ================= MOBILE HEADER ================= -->
    <div class="block lg:hidden">

        <header class="w-full bg-primaryColor text-white px-4 py-3 flex items-center justify-between shadow">
            <button id="mobileMenuToggle" class="text-2xl">
                <i class="icofont-navigation-menu"></i>
            </button>

            <img src="{{ asset('assets/images/brand/white-logo.png') }}"
                 class="w-36 sm:w-40"
                 alt="logo">

            <a href="{{ route('teacher.profile') }}">
                <img
                    src="{{ optional(Auth::user()->teacher)->profileImageUrl() }}"
                    class="w-9 h-9 rounded-full border-2 border-white object-cover"
                    alt="profile"
                >
            </a>
        </header>

        <!-- Mobile Sidebar -->
        <div id="mobileSidebar"
             class="fixed top-0 left-0 w-64 h-full bg-white dark:bg-gray-800 shadow-lg transform -translate-x-full transition-transform duration-300 z-50">

            <div class="px-4 py-4 flex items-center justify-between border-b border-gray-200 dark:border-gray-700">
                <p class="text-lg font-semibold text-gray-700 dark:text-gray-100">
                    Menu
                </p>
                <button id="mobileSidebarClose" class="text-2xl text-gray-600 dark:text-gray-300">
                    <i class="icofont-close"></i>
                </button>
            </div>

            @php
                $main_navs = [
                    ['name' => 'Dashboard', 'icon' => 'home', 'route' => 'teacher.dashboard'],
                    ['name' => 'Classes', 'icon' => 'users-alt-3', 'route' => 'teacher.classes'],
                    ['name' => 'Questions', 'icon' => 'question-circle', 'route' => 'teacher.questions'],
                    ['name' => 'Reviewer', 'icon' => 'book', 'route' => 'teacher.reviewer.index'],
                ];
            @endphp

            <x-sider-bar-teacher-mobile :navs="$main_navs" />
        </div>

        <div id="mobileOverlay"
             class="fixed inset-0 bg-black bg-opacity-40 hidden z-40"></div>
    </div>

    <!-- ================= DESKTOP HEADER ================= -->
    <div class="hidden lg:block">
        <x-theme.header>
            <x-slot name="navbar_right">
                <ul class="nav-list flex justify-end items-center gap-4">

                    <li>
                        <a href="{{ route('teacher.profile') }}" class="block">
                            <img
                                src="{{ optional(Auth::user()->teacher)->profileImageUrl() }}"
                                alt="Profile Picture"
                                class="transition-all size-10 hover:size-12 rounded-full border-2 border-primaryColor object-cover"
                            >
                        </a>
                    </li>

                    <li class="hidden xl:block">
                        <div class="flex flex-col">
                            <p class="text-[#373737] dark:text-white text-sm font-medium">
                                Welcome, {{ Auth::user()?->name }}!
                            </p>
                            <p class="text-[#5E5F60] dark:text-white text-xs">Teacher</p>
                        </div>
                    </li>

                    <li>
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit"
                                class="text-primaryColor transition hover:scale-110"
                                title="Logout">
                                <i class="icofont-logout text-2xl"></i>
                            </button>
                        </form>
                    </li>
                </ul>
            </x-slot>
        </x-theme.header>
    </div>

    <!-- ================= MAIN CONTENT ================= -->
    <main class="relative mt-4 sm:mt-6">
        <section>
            <div class="px-4 sm:px-6 lg:px-14">
                <div class="dashboard-wrapper">

                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 sm:gap-6 xl:gap-8 pt-4 sm:pt-6 pb-16">

                        <!-- Desktop Sidebar -->
                        <aside
    id="teacherSidebar"
    class="hidden lg:block lg:col-span-3 relative transition-all duration-300"
>
    <button id="sidebarToggle"
        class="absolute top-4 right-4 text-primaryColor text-2xl z-10">
        <i class="icofont-navigation-menu"></i>
    </button>

    <div id="sidebarContent">
        <x-dashboard.teachersidebar />
    </div>
</aside>


                        <!-- Shared Content -->
                        <div id="dashboardContent"
                             class="lg:col-span-9 space-y-6 w-full min-w-0">

                            @if ($banner)
                                <x-dashboard.teacher_profilebanner />
                            @endif

                            @yield('content')

                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- ================= FOOTER + SCRIPTS ================= -->
    @include('layouts.footer')
    @include('layouts.scripts')

    @vite([
        'resources/js/lmsmodal.js',
        'resources/js/TeacherPage.js',
        'resources/js/activityOptions.js',
        'resources/js/admin-modal-loading.js'
    ])

    @yield('scripts')
    @stack('scripts')
    @yield('modals')

</body>
</html>
