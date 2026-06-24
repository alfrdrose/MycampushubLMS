<section>
    <div class="container-fluid-2">
        <div
            class="bg-skycolor p-5 md:p-10 rounded-5 flex justify-center md:justify-between items-center flex-wrap gap-2">
            <div class="flex items-center flex-wrap justify-center sm:justify-start">
                <div class="mr-5">
                    <img src="{{ asset('edurock/assets/images/teacher/teacher__2.png') }}" alt=""
                        class="w-27 h-27 md:w-22 md:h-22 lg:w-27 lg:h-27 rounded-full p-1 border-2 border-darkdeep7 box-content">
                </div>
                <div class="text-whiteColor font-bold text-center sm:text-start">
                    <h5 class="text-xl leading-1.2 mb-5px">Hello</h5>
                    <h2 class="text-2xl leading-1.24">{{ Auth::user()->fullname }}</h2>
                </div>
            </div>
            <div>
                <x-link.button href="#" variant="secondary">
                    Start
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" class="inline-block ml-1 align-middle">
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                        <polyline points="12 5 19 12 12 19"></polyline>
                    </svg>
                </x-link.button>
            </div>
        </div>
    </div>
</section>