<header class="shadow-xl shadow-[#d1beff41] dark:shadow-[#181a5d33]">
    <!-- header top start -->
    <div class="bg-blackColor2 dark:bg-lightGrey10-dark hidden lg:block">
        <div
            class="container 3xl:container2-lg 4xl:container mx-auto
                   text-whiteColor text-size-12 xl:text-sm
                   leading-tight
                   py-2px xl:py-5px">
            <div class="flex justify-between items-center">
                <div>
                    <p>{{ __('layout.header.contact') }}</p>
                </div>

                <div class="flex gap-37px items-center">
                    <div>
                        <p>
                            <i class="icofont-location-pin text-primaryColor text-size-15 mr-5px"></i>
                            <span>{{ __('layout.header.address') }}</span>
                        </p>
                    </div>

                    <div>
                        <!-- header social list -->
                        <ul class="flex gap-13px text-size-15">
                            <li>
                                <a class="hover:text-primaryColor" href="#">
                                    <i class="icofont-facebook"></i>
                                </a>
                            </li>
                            <li>
                                <a class="hover:text-primaryColor" href="#">
                                    <i class="icofont-twitter"></i>
                                </a>
                            </li>
                            <li>
                                <a class="hover:text-primaryColor" href="#">
                                    <i class="icofont-instagram"></i>
                                </a>
                            </li>
                            <li>
                                <a class="hover:text-primaryColor" href="#">
                                    <i class="icofont-youtube-play"></i>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- header top end -->

    <!-- navbar start -->
    <div class="transition-all duration-500 sticky-header z-medium dark:bg-whiteColor-dark p-4">
        <nav>
            <div
                class="py-8px lg:py-0 px-15px
                       lg:container 3xl:container2-lg 4xl:container
                       mx-auto relative">
                <div class="grid grid-cols-2 lg:grid-cols-12 items-center gap-15px">

                    <!-- navbar left -->
                    <div class="lg:col-start-1 lg:col-span-2 flex items-center gap-3">

                        <!-- Logo -->
                        <a href="/" class="block">
                            <!-- Light Logo -->
                            <img
                                src="{{ asset('assets/images/brand/indigo-logo.png') }}"
                                class="w-logo-sm lg:w-auto  dark:hidden"
                                alt="Logo"
                            >

                            <!-- Dark Logo -->
                            <img
                                src="{{ asset('assets/images/brand/white-logo.png') }}"
                                class="w-logo-sm lg:w-auto hidden dark:block"
                                alt="Logo"
                            >
                        </a>

                    </div>

                    <!-- navbar right -->
                    @isset($navbar_right)
                        <div class="lg:col-start-9 lg:col-span-4">
                            {{ $navbar_right }}
                        </div>
                    @endisset

                </div>
            </div>
        </nav>
    </div>
    <!-- navbar end -->
</header>
