<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="app-url" content="{{ config('app.url') }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

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

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @yield('styles')
</head>

<body class="relative font-inter font-normal text-base leading-[1.8] bg-bodyBg dark:bg-bodyBg-dark">

    <!-- preloader -->
    <x-theme.preloader />

    <!-- theme fixed shadow -->
    <x-theme.fixed-shadow />

    <!-- theme controller -->
    <x-theme.customizer />

    <!-- scroll up button -->
    <x-theme.scroll-up />

    <div class="min-h-screen bg-gray-100">
        @include('layouts.header')

        <!-- Page Content -->
        <main>
            {{ $slot }}
        </main>
    </div>

    <div>
        <img loading="lazy" class="absolute right-[14%] top-[30%] animate-move-var"
            src="{{ asset('edurock/assets/images/education/hero_shape2.png') }}" alt="Shape">
        <img loading="lazy" class="absolute left-[5%] top-1/2 animate-move-hor"
            src="{{ asset('edurock/assets/images/education/hero_shape3.png') }}" alt="Shape">
        <img loading="lazy" class="absolute left-1/2 bottom-[60px] animate-spin-slow"
            src="{{ asset('edurock/assets/images/education/hero_shape4.png') }}" alt="Shape">
        <img loading="lazy" class="absolute left-1/2 top-10 animate-spin-slow"
            src="{{ asset('edurock/assets/images/education/hero_shape5.png') }}" alt="Shape">
    </div>

    @include('layouts.footer')
    @include('layouts.scripts')
    @yield('scripts')
</body>

</html>