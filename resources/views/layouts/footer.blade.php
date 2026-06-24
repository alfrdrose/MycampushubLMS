<footer class="bg-darkblack">
    <div class="container pt-65px">
        <!-- footer top or subscription -->
        <div>
            <div
                class="grid grid-cols-1 md:grid-cols-2 gap-y-5 md:gap-y-0 items-center pb-45px border-b border-darkcolor">
                <div data-aos="fade-up">
                    <a href="/" class="w-full flex">
                        <img src="{{ asset('assets/images/brand/white-logo.png') }}" alt=""
                            class="mb-6 mx-auto h-14 sm:h-20 md:mx-0 md:h-14 lg:h-20">
                    </a>
                </div>
            </div>
        </div>

        <!-- footer copyright  -->
        <div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 py-5 lg:py-10 items-center border-t border-darkcolor">
                <div>
                    <p class="text-base text-darkgray">
                        © {{ date('Y') }}
                        <a href="/" class="hover:text-primaryColor">{{ __('layout.footer.brand') }}</a>.
                        All Rights Reserved | Powered by
                        <a href="#" class="hover:text-primaryColor">{{ __('layout.footer.powered_by') }}</a>
                    </p>
                </div>

                <div>
                    <ul class="flex items-center justify-end">
                        <li>
                            <a href="#"
                                class="text-base text-darkgray hover:text-primaryColor pr-4 border-r border-darkgray leading-1">
                                Terms of Use
                            </a>
                        </li>
                        <li>
                            <a href="#" class="text-base text-darkgray hover:text-primaryColor pl-4">
                                Privacy Policy
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</footer>