<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="app-url" content="{{ config('app.url') }}">

    <title>{{ $title ?? 'Default Title' }}</title>

    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Stylesheets -->
    <link rel="stylesheet" href="{{ asset('edurock/assets/css/icofont.min.css') }}">
    <link rel="stylesheet" href="{{ asset('edurock/assets/css/video-modal.css') }}">
    <link rel="stylesheet" href="{{ asset('edurock/assets/css/aos.css') }}">

    @vite('resources/css/app.css')
    @yield('styles')
</head>

<body
    x-data="{ sidebarOpen: true }"
    class="relative font-inter font-normal text-base leading-[1.8] bg-bodyBg dark:bg-bodyBg-dark"
>

@php
    $preloader = $preloader ?? false;
    $darkmode  = $darkmode ?? false;
    $scrollup  = $scrollup ?? false;
    $sidebar   = $sidebar ?? true;
    $banner    = $banner ?? false;
    $sectionNavigationQuery = request()->only(['grade_level', 'school_year', 'search']);
    $sectionSchoolYearLabel = $section->schoolYear?->label ?? ($section->start_year . ' - ' . $section->end_year);
    $sectionReadOnly = $sectionReadOnly ?? (! $section->schoolYear?->is_active);
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
        <ul class="flex justify-end items-center gap-4">
            <!-- Notifications, Profile, Logout etc. -->
        </ul>
    </x-slot>
</x-theme.header>

<!-- Page Content -->
<main class="relative mt-6">
    @if ($banner)
        <x-dashboard.banner />
    @endif

    <section>
        <div class="container-fluid-2 px-4 sm:px-6">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 sm:gap-8 pt-6 pb-16">

                @if ($sidebar)
                    <!-- Sidebar -->
                    <aside
                        x-show="sidebarOpen"
                        x-transition
                        class="lg:col-span-3 w-full relative transition-all duration-300"
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

                    <!-- Main content -->
                    <div
                        :class="sidebarOpen ? 'lg:col-span-9' : 'lg:col-span-12'"
                        class="w-full transition-all duration-300"
                    >
                        <div class="flex flex-col bg-whiteColor dark:bg-darkblack p-4 sm:p-8 rounded-2xl shadow-lg transition-all duration-300 dark:ring-2 dark:ring-blue-500/20 dark:ring-offset-0">

                            <!-- Header -->
                            <div class="mb-4">
                                <h1 class="text-2xl sm:text-3xl font-bold text-[#5E5F60] dark:text-white">
                                    Grade {{ $section->grade_level }} - Section {{ $section->section_rank }}
                                </h1>

                                @if (!empty($section->section_name))
                                    <p class="text-[#ABB7C2] dark:text-white">
                                        Section Name: {{ $section->section_name }}
                                    </p>
                                @endif

                                @if (! $section->schoolYear?->is_active)
                                    <div class="mt-4 rounded-xl border border-[#F3D7A3] bg-[#FFF7E8] px-4 py-3 text-sm text-[#8A5A00] dark:border-[#5B4721] dark:bg-[#2B2112] dark:text-[#F2C889]">
                                        Viewing records for SY {{ $sectionSchoolYearLabel }}. All section actions are disabled while this school year is inactive.
                                    </div>
                                @endif
                            </div>

                            <!-- Content with internal navigation -->
                            <div class="flex flex-col md:flex-row w-full gap-4">

                                <!-- Internal sidebar -->
                                <div class="w-full md:w-[15%] flex flex-col gap-4">
                                    <a href="{{ route('admin.editsection', array_merge(['id' => $section->id], $sectionNavigationQuery)) }}"
                                       class="font-medium p-2 rounded transition-colors duration-200
                                       {{ request()->routeIs('admin.editsection')
                                            ? 'bg-[#5F2DED]/20 text-[#5F2DED]'
                                            : 'text-[#5E5F60] dark:text-white hover:bg-[#EDEDED] dark:hover:bg-gray-700' }}">
                                        Summary
                                    </a>

                                    <a href="{{ route('admin.section.adviser', array_merge(['id' => $section->id], $sectionNavigationQuery)) }}"
                                       class="font-medium p-2 rounded transition-colors duration-200
                                       {{ request()->routeIs('admin.section.adviser')
                                            ? 'bg-[#5F2DED]/20 text-[#5F2DED]'
                                            : 'text-[#5E5F60] dark:text-white hover:bg-[#EDEDED] dark:hover:bg-gray-700' }}">
                                        Section Adviser
                                    </a>

                                    <a href="{{ route('admin.section.students', array_merge(['id' => $section->id], $sectionNavigationQuery)) }}"
                                       class="font-medium p-2 rounded transition-colors duration-200
                                       {{ request()->routeIs('admin.section.students')
                                            ? 'bg-[#5F2DED]/20 text-[#5F2DED]'
                                            : 'text-[#5E5F60] dark:text-white hover:bg-[#EDEDED] dark:hover:bg-gray-700' }}">
                                        Students List
                                    </a>

                                    <a href="{{ route('admin.section.subjects', array_merge(['id' => $section->id], $sectionNavigationQuery)) }}"
                                       class="font-medium p-2 rounded transition-colors duration-200
                                       {{ request()->routeIs('admin.section.subjects')
                                            ? 'bg-[#5F2DED]/20 text-[#5F2DED]'
                                            : 'text-[#5E5F60] dark:text-white hover:bg-[#EDEDED] dark:hover:bg-gray-700' }}">
                                        Subjects List
                                    </a>

                                    <a href="{{ route('admin.section.schedule', array_merge(['section' => $section->id], $sectionNavigationQuery)) }}"
                                       class="font-medium p-2 rounded transition-colors duration-200
                                       {{ request()->routeIs('admin.section.schedule')
                                            ? 'bg-[#5F2DED]/20 text-[#5F2DED]'
                                            : 'text-[#5E5F60] dark:text-white hover:bg-[#EDEDED] dark:hover:bg-gray-700' }}">
                                        Schedule
                                    </a>

                                    <a href="{{ route('admin.sections', array_filter([
                                        'grade_level' => $sectionNavigationQuery['grade_level'] ?? $section->grade_level,
                                        'school_year' => $sectionNavigationQuery['school_year'] ?? null,
                                        'search' => $sectionNavigationQuery['search'] ?? null,
                                    ])) }}"
                                       class="border border-[#5F2DED] text-center py-2 rounded text-sm text-[#5F2DED]
                                              hover:bg-[#5F2DED] hover:text-white transition-all mt-auto">
                                        Back
                                    </a>
                                </div>

                                <!-- Main panel -->
                                <div
                                    class="w-full md:w-[80%] border-t md:border-t-0 md:border-l
                                           border-gray-200 dark:border-gray-700
                                           pt-4 md:pt-0 md:pl-4"
                                >
                                    <div
                                        @if ($sectionReadOnly) inert aria-disabled="true" @endif
                                        class="{{ $sectionReadOnly ? 'pointer-events-none select-none opacity-70' : '' }}"
                                    >
                                        @yield('content')
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                @else
                    <!-- Content without sidebar -->
                    <div class="lg:col-span-12 w-full">
                        <div class="flex flex-col bg-whiteColor dark:bg-darkblack p-4 sm:p-8 rounded-2xl shadow-lg">
                            @yield('content')
                        </div>
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
    'resources/js/sections.js',
    'resources/js/admin-modal-loading.js',
    'resources/js/lmsmodal.js'
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

<!-- Global LMS Modal -->
<x-lmsmodal />

</body>
</html>
