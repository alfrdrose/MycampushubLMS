<header>
    <!-- header top start -->
    <div class="bg-blackColor2 dark:bg-lightGrey10-dark hidden lg:block">
        <div
            class="container 3xl:container2-lg 4xl:container mx-auto text-whiteColor text-size-12 xl:text-sm py-5px xl:py-9px">
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
                                <a class="hover:text-primaryColor" href="#"><i class="icofont-facebook"></i></a>
                            </li>
                            <li>
                                <a class="hover:text-primaryColor" href="#"><i class="icofont-twitter"></i></a>
                            </li>
                            <li>
                                <a class="hover:text-primaryColor" href="#"><i class="icofont-instagram"></i></a>
                            </li>
                            <li>
                                <a class="hover:text-primaryColor" href="#"><i class="icofont-youtube-play"></i></a>
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
            <div class="py-15px lg:py-0 px-15px lg:container 3xl:container2-lg 4xl:container mx-auto relative">
                <div class="grid grid-cols-2 lg:grid-cols-12 items-center gap-15px">
                    <!-- navbar left -->
                    <div class="lg:col-start-1 lg:col-span-2">
                        <a href="/" class="block">
                            <img src="{{ asset('assets/images/brand/indigo-logo.png') }}" alt="Logo"
                                class="w-logo-sm lg:w-auto py-2">
                        </a>
                    </div>
                </div>
            </div>
        </nav>
    </div>
</header>