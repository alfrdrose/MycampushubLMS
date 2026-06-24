<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | LMSThesis</title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('favicon.ico') }}">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">


    @vite('resources/css/app.css')
    <link rel="stylesheet" href="{{ asset('edurock/assets/css/icofont.min.css') }}">
</head>

<body class="relative font-[Poppins] font-normal text-base leading-[1.8] bg-bodyBg dark:bg-bodyBg-dark"
    style="background-image: url('{{ asset('assets/images/background.png') }}'); background-size: cover; background-position: center; background-repeat: no-repeat;">

    <x-preloader />
    <x-login.header1 />
    <x-login.mainbody />
    <x-themeController />
    <x-footer />

    @vite([
        'resources/js/app.js',
        'resources/js/preloader.js',
        'resources/js/theme.js',
        'resources/js/login.js',
        'resources/js/stickystickyHeader.js',
    ])

    <script src="//unpkg.com/alpinejs" defer></script>
</body>

</html>
