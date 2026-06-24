<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="app-url" content="{{ config('app.url') }}">

    <title>{{ $title }}</title>

    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap"
        rel="stylesheet">

    <!-- Stylesheets -->
    <link rel="stylesheet" href="{{ asset('edurock/assets/css/icofont.min.css') }}">
    <link rel="stylesheet" href="{{ asset('edurock/assets/css/video-modal.css')}}">
    <link rel="stylesheet" href="{{ asset('edurock/assets/css/aos.css')}}">

    @vite('resources/css/app.css')
    @yield('styles')
</head>

<body
    x-data="{ sidebarOpen: true }"
    class="relative font-inter font-normal text-base leading-[1.8] bg-bodyBg dark:bg-bodyBg-dark"
>
@php
    $title     = $title ?? 'Default Title';
    $preloader = $preloader ?? false;
    $darkmode  = $darkmode ?? false;
    $scrollup  = $scrollup ?? false;
    $sidebar   = $sidebar ?? true;
    $banner    = $banner ?? false;
@endphp

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

<x-theme.header>
    <x-slot name="navbar_right">
        <ul class="relative nav-list flex justify-end items-center gap-4">
            <li class="hidden lg:block">
                <a href="#" class="block text-primaryColor hover:scale-110 transition" title="Notifications">
                    <i class="icofont-notification text-3xl"></i>
                </a>
            </li>
            <li class="hidden lg:block">
                <a href="{{ url('/testinglms') }}" class="block text-primaryColor hover:scale-110 transition"
                   title="Go to Testing Page">
                    <i class="icofont-test-bulb text-3xl"></i>
                </a>
            </li>
            <li class="hidden lg:block">
                <a href="{{ route('teacher.profile') }}" title="Profile">
                    <img
                        src="{{ asset('assets/images/admin.png') }}"
                        alt="Profile Picture"
                        class="size-10 hover:size-12 transition-all rounded-full border-2 border-primaryColor object-cover"
                    >
                </a>
            </li>
            <li class="hidden lg:block">
                <div class="flex flex-col">
                    <p class="text-[#373737] font-medium text-base">
                        Welcome, {{ Auth::user()?->name }}!
                    </p>
                    <p class="text-[#5E5F60] text-sm">Administrator</p>
                </div>
            </li>
            <li class="hidden lg:block">
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button class="text-primaryColor hover:scale-110 transition" title="Logout">
                        <i class="icofont-logout text-3xl"></i>
                    </button>
                </form>
            </li>
        </ul>
    </x-slot>
</x-theme.header>

<!-- Page Content -->
<main class="relative mt-6">
    @if ($banner)
        <x-dashboard.banner />
    @endif

    <section>
        <div class="container-fluid-2">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-30px pt-30px pb-100px">

                @if ($sidebar)
                    <!-- Sidebar -->
                    <aside
                        x-show="sidebarOpen"
                        x-transition
                        class="lg:col-span-3 relative transition-all duration-300"
                    >
                        <!-- Collapse button -->
                        <button
                            @click="sidebarOpen = false"
                            class="absolute top-2 right-2 p-2 rounded-lg"
                            title="Collapse sidebar"
                        >
                            <i class="icofont-navigation-menu text-xl text-primaryColor"></i>
                        </button>

                        <x-dashboard.sidebar />
                    </aside>

                    <!-- Content -->
                    <div
                        :class="sidebarOpen ? 'lg:col-span-9' : 'lg:col-span-12'"
                        class="transition-all duration-300"
                    >
                        <x-sections.addsectionContent :grade_level="$grade_level" :active-school-year="$activeSchoolYear" />
                    </div>

                @else
                    <!-- Full-width Content -->
                    <div class="lg:col-span-12">
                        <x-sections.addsectionContent :grade_level="$grade_level" :active-school-year="$activeSchoolYear" />
                    </div>
                @endif

            </div>
        </div>
    </section>
</main>

@include('layouts.footer')
@include('layouts.scripts')

@vite([
    'resources/js/app.js',
    'resources/js/admin-modal-loading.js',
    'resources/js/lmsmodal.js',
    'resources/js/sections.js'
])

@yield('scripts')

<!-- Floating open-sidebar button -->
<div
    x-show="!sidebarOpen"
    x-transition
    class="fixed left-4 top-1/2 -translate-y-1/2 z-[9999]"
>
    <button
        @click="sidebarOpen = true"
        class="w-12 h-12 rounded-full bg-primaryColor text-white
               flex items-center justify-center shadow-lg
               hover:scale-110 transition"
        title="Open sidebar"
    >
        <i class="icofont-navigation-menu text-2xl"></i>
    </button>
</div>

</body>
</html>
