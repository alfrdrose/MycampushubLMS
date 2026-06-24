<header>

    <div class="p-4 shadow-lg transition-all duration-500 sticky-header z-medium dark:bg-whiteColor-dark bg-whiteColor">
        <nav>
            <div class="py-15px lg:py-0 px-15px lg:container 3xl:container-secondary-lg 4xl:container mx-auto relative">
                <div class="grid grid-cols-2 lg:grid-cols-12 items-center gap-15px">
                    <!-- navbar left -->
                    <div class="lg:col-start-1 lg:col-span-8">
                        <div class="flex items-center gap-8">
                            <a href="index.html" class="block flex flex-row justify-start items-center flex-shrink-0">
                                <a href="index.html" class="flex items-center gap-3 flex-shrink-0">
                                    <!-- Light mode logo -->
                                    <img src="{{ asset('assets/images/logo/au-logo.png') }}" alt="Logo"
                                        class="w-10 h-10" />

                                    <!-- Light mode text -->
                                    <span class="text-xl font-bold text-primaryColor dark:hidden">
                                        Arellano <span class="text-black">University</span>
                                    </span>
                                    <!-- Dark mode text -->
                                    <span class="text-xl font-bold text-primaryColor hidden dark:block">
                                        Arellano <span class="text-white">University</span>
                                    </span>
                                </a>


                                <!-- Main menu - moved closer to logo -->

                        </div>
                    </div>

                    <!-- navbar right -->
                    <div class="lg:col-start-9 lg:col-span-4">
                        <ul class="relative nav-list flex justify-end items-center">
                            <li class="px-5 lg:px-10px 2xl:px-5 lg:py-4 2xl:py-26px 3xl:py-9 group">

                                <div class="dropdown absolute top-full right-0 lg:right-8 z-medium hidden opacity-0"
                                    style="transition: 0.3s">

                            </li>
                            <div class="flex flex-row items-center justify-center gap-6  w-full">
                                <!-- Notification Icon -->
                                <i class="icofont-notification text-3xl text-primaryColor cursor-pointer
                      transition-transform duration-200 hover:scale-110"></i>

                                <!-- Profile Picture -->
                                <img src="{{ asset('assets/images/admin.png') }}" alt="Profile Picture"
                                    class="w-10 h-10 rounded-full border-2 border-primaryColor object-cover ">

                                <!-- Text Info -->
                                <div class="flex flex-col">
                                    <p class="text-[#373737] font-medium text-base">Welcome, {{ Auth::user()->name}}!
                                    </p>
                                    <p class="text-[#5E5F60] text-sm">Administrator</p>
                                </div>

                                <!-- Logout Button/Icon -->
                                <form action="{{route('logout')}}" method="POST">
                                    @csrf
                                    <button
                                        class=" p-2 rounded-full   text-primaryColor  transition-transform duration-200 hover:scale-110"
                                        onclick="handleLogout()">
                                        <i class="icofont-logout text-2xl "></i>
                                    </button>
                                </form>

                            </div>
                            <li class="block lg:hidden">
                                <button
                                    class="open-mobile-menu text-3xl text-darkdeep1 hover:text-white hover:bg-primaryColor dark:text-whiteColor ">
                                    <i class="icofont-navigation-menu"></i>
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </nav>
    </div>
    <!-- navbar end -->

    <!-- mobile menu -->
    <div
        class="mobile-menu w-mobile-menu-sm md:w-mobile-menu-lg fixed top-0 -right-[280px] md:-right-[330px] transition-all duration-500 w-mobile-menu h-full shadow-dropdown-secodary bg-whiteColor dark:bg-whiteColor-dark z-high block lg:hidden">
        <button
            class="close-mobile-menu text-lg bg-darkdeep1 hover:bg-secondaryColor text-white px-[11px] py-[6px] absolute top-0 right-full hidden">
            <i class="icofont icofont-close-line"></i>
        </button>
        <!-- mobile menu wrapper -->
        <div class="px-5 md:px-30px pt-5 md:pt-10 pb-50px h-full overflow-y-auto">

            <!-- mobile menu accordions -->
            <div class="pt-8 pb-6 border-b border-borderColor dark:border-borderColor-dark">
                <ul class="accordion-container">
                    <li class="accordion">
                        <!-- accordion header -->
                        <div class="flex items-center justify-between">
                            <a class="leading-1 py-11px text-darkdeep1 font-medium hover:text-secondaryColor dark:text-whiteColor dark:hover:text-secondaryColor"
                                href="index.html">Extra Link 1</a>
                        </div>
                    </li>
                    <li class="accordion">
                        <!-- accordion header -->
                        <div class="flex items-center justify-between">
                            <a class="leading-1 py-11px text-darkdeep1 font-medium hover:text-secondaryColor dark:text-whiteColor dark:hover:text-secondaryColor"
                                href="#">Extra Link 2</a>
                        </div>
                    </li>
                </ul>
            </div>
            <!-- Mobile menu social area -->
            <div>
                <ul class="flex gap-6 items-center mb-5">
                    <li>
                        <a class="facebook" href="#"><i
                                class="icofont icofont-facebook text-fb-color dark:text-whiteColor dark:hover:text-secondaryColor"></i></a>
                    </li>
                    <li>
                        <a class="twitter" href="#"><i
                                class="icofont icofont-twitter text-twiter-color dark:text-whiteColor dark:hover:text-secondaryColor"></i></a>
                    </li>
                    <li>
                        <a class="pinterest" href="#"><i
                                class="icofont icofont-pinterest dark:text-whiteColor dark:hover:text-secondaryColor"></i></a>
                    </li>
                    <li>
                        <a class="instagram" href="#"><i
                                class="icofont icofont-instagram dark:text-whiteColor dark:hover:text-secondaryColor"></i></a>
                    </li>
                    <li>
                        <a class="google" href="#"><i
                                class="icofont icofont-youtube-play dark:text-whiteColor dark:hover:text-secondaryColor"></i></a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</header>