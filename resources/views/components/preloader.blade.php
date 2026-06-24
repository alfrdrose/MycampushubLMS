@vite('resources/js/preloader.js')
<!-- preloader -->
<div
    class="preloader flex fixed top-0 left-0 h-screen w-full items-center justify-center z-xxl bg-whiteColor opacity-100 visible transition-all duration-700">
    <!-- spinner -->
    <div
        class="w-90px h-90px border-5px border-t-blue border-r-blue border-b-blue-light border-l-blue-light rounded-full animate-spin-infinit">
    </div>
    <div class="absolute top-1/2 left-1/2 -translate-y-1/2 -translate-x-1/2">
        <img src="{{ asset('assets/images/pre.png') }}" alt="Preloader" class="h-10 w-10 block">
    </div>
</div>
