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
                    Start <x-icons.feather.arrow-right />
                </x-link.button>
            </div>
        </div>
    </div>
</section>