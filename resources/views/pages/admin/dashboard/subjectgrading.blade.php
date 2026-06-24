<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="app-url" content="{{ config('app.url') }}">

    <title>{{ $title ?? 'Subject Grading' }}</title>

    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">

    <!-- Stylesheets -->
    <link rel="stylesheet" href="{{ asset('edurock/assets/css/icofont.min.css') }}">
    <link rel="stylesheet" href="{{ asset('edurock/assets/css/video-modal.css') }}">
    <link rel="stylesheet" href="{{ asset('edurock/assets/css/aos.css') }}">

    <style>
        @keyframes slide-up {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-slide-up {
            animation: slide-up 0.3s ease-out;
        }
    </style>

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
        <div class="flex flex-row justify-end items-center gap-4">
            <div class="hidden lg:block">
                <img
                    src="{{ asset('assets/images/admin.png') }}"
                    alt="Profile"
                    class="size-10 rounded-full border-2 border-primaryColor object-cover"
                >
            </div>
            <div class="hidden lg:block">
                <p class="text-sm font-medium text-[#373737] dark:text-white">
                    Welcome, {{ Auth::user()?->name }}!
                </p>
                <p class="text-xs text-[#5E5F60] dark:text-white">Administrator</p>
            </div>
            <div class="hidden lg:block">
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button class="text-primaryColor hover:scale-110 transition">
                        <i class="icofont-logout text-2xl"></i>
                    </button>
                </form>
            </div>
        </div>
    </x-slot>
</x-theme.header>

<!-- Page Content -->
<main class="relative mt-6">
    <section>
        <div class="px-8">
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
                        <x-subjects.subjectgradingContent
                            :subject="$subject"
                            :gradings="$gradings"
                        />
                    </div>
                @else
                    <div class="lg:col-span-12">
                        <x-subjects.subjectgradingContent
                            :subject="$subject"
                            :gradings="$gradings"
                        />
                    </div>
                @endif

            </div>
        </div>
    </section>
</main>

{{-- Success Toast --}}
<div
    id="toast-success"
    class="hidden fixed bottom-5 right-5 flex items-center gap-3 px-4 py-3
           rounded-lg shadow-lg bg-primaryColor text-white animate-slide-up"
>
    <i class="icofont-check-circled text-xl"></i>
    <span id="toast-message">Success</span>
</div>

@if(session('success'))
<script>
document.addEventListener('DOMContentLoaded', () => {
    const toast = document.getElementById('toast-success');
    if (toast) {
        document.getElementById('toast-message').textContent = "{{ session('success') }}";
        toast.classList.remove('hidden');
        setTimeout(() => toast.classList.add('hidden'), 3000);
    }
});
</script>
@endif

@include('layouts.footer')
@include('layouts.scripts')

@vite([
    'resources/js/app.js',
    'resources/js/admin-modal-loading.js',
    'resources/js/lmsmodal.js',
    'resources/js/subjects.js',
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

