@props(['title' => 'New Page', 'breadcrumbs' => []])


<x-guest-layout>

    <x-theme.banner :title="$title" :breadcrumbs="$breadcrumbs" />

    <section class="relative z-1">

        <div class="container py-100px">

            <div class="md:w-2/3 mx-auto">
                <div
                    class="grid grid-cols-2 gap-11px text-blackColor text-lg lg:text-size-22 font-semibold font-hind mb-43px mt-30px md:mt-0">

                </div>

                <div
                    class="shadow-container bg-whiteColor dark:bg-whiteColor-dark pt-10px px-5 pb-10 md:p-50px md:pt-30px rounded-5px">

                    {{ $slot }}

                </div>
            </div>

        </div>

    </section>

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

</x-guest-layout>