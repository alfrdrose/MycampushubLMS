

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

<body class="relative font-inter font-normal text-base leading-[1.8] bg-bodyBg dark:bg-bodyBg-dark">
    @php
        $title = $title ?? 'Default Title';
        $preloader = $preloader ?? false;
        $darkmode = $darkmode ?? false;
        $scrollup = $scrollup ?? false;
        $sidebar = $sidebar ?? true;
        $banner = $banner ?? false;
    @endphp

    @if ($preloader)
        <!-- preloader -->
        <x-theme.preloader />
    @endif

    <!-- theme fixed shadow -->
    <x-theme.fixed-shadow />

    @if ($darkmode)
        <!-- theme controller -->
        <x-theme.customizer />
    @endif

    @if ($scrollup)
        <!-- scroll up button -->
        <x-theme.scroll-up />
    @endif

    <x-theme.header>
        <x-slot name="navbar_right">
            <div class="flex flex-row  justify-end items-center gap-4">
                <div class="hidden lg:block">
                    <a  class="block" title="Profile">
                        <img src="{{ asset('assets/images/admin.png') }}" alt="Profile Picture"
                            class="transition-all duration-200 size-10  rounded-full border-2 border-primaryColor object-cover ">
                    </a>
                </div>
                <div class="hidden lg:block">
                    <div class="flex flex-col">
                        <p class="text-[#373737] font-medium text-sm dark:text-white">Welcome, {{ Auth::user()?->name }}!
                        </p>
                        <p class="text-[#5E5F60] text-xs dark:text-white">Administrator</p>
                    </div>
                </div>
                <div class="hidden lg:block">
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit"
                            class="text-primaryColor transition-transform duration-200 hover:scale-110" title="Logout">
                            <i class="icofont-logout text-2xl ml-4 "></i>
                        </button>
                        <!-- <x-button.outline onclick="handleLogout()">
                            <i class="icofont-logout text-2xl "></i>
                        </x-button.outline> -->
                    </form>
                </div>
            </div>
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
                        <aside class="lg:col-start-1 lg:col-span-3">
                            <x-dashboard.sidebar />
                        </aside>

                        <!-- Dashboard Content -->
                        <div class="lg:col-start-4 lg:col-span-9">
                            <x-teachers.teachersContent :columns="$columns" :teachers="$teachers" />
                        </div>



                    @else

                        <!-- Dashboard Content -->
                        <div class="lg:col-start-3 lg:col-span-8">
                            <x-teachers.teachersContent :columns="$columns" :teachers="$teachers" />
                        </div>

                    @endif

                </div>
            </div>
        </section>
    </main>
    {{-- notifcation for successful add --}}
    <div id="toast-success"
        class="hidden fixed bottom-5 right-5 flex items-center gap-3 px-4 py-3 rounded-lg shadow-lg bg-primaryColor text-white animate-slide-up">
        <i class="icofont-check-circled text-xl"></i>
        <span id="toast-message">Account added successfully!</span>
        <button id="toast-close" class="ml-2 hidden text-white hover:text-gray-200">&times;</button>
    </div>

    @if(session('success'))
        <script>
        document.addEventListener("DOMContentLoaded", function () {
            const toast = document.getElementById("toast-success");
            if (toast) {
                toast.textContent = "{{ session('success') }}";
                toast.classList.remove("hidden");

                setTimeout(() => {
                    toast.classList.add("hidden");
                }, 3000);
            }
        });
        </script>
        @endif


    @include('layouts.footer')
    @include('layouts.scripts')

    @vite([
        'resources/js/app.js',
    'resources/js/admin-modal-loading.js',
        'resources/js/teachers.js',
        'resources/js/lmsmodal.js'
    ])

    @yield('scripts')
    
</body>

</html>
