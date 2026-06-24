@props(['title' => 'New Page', 'breadcrumbs' => []])

<!-- banner section -->
<section>
    <!-- banner section -->
    <div class="bg-lightGrey10 dark:bg-lightGrey10-dark relative z-0 overflow-y-visible min-h-44 py-8">
        <!-- animated icons -->
        <div>
            <img class="absolute left-0 bottom-0 md:left-[14px] lg:left-[50px] lg:bottom-[21px] 2xl:left-[165px] 2xl:bottom-[60px] animate-move-var z-10"
                src="{{ asset('edurock/assets/images/herobanner/herobanner__1.png') }}" alt="">
            <img class="absolute left-0 top-0 lg:left-[50px] lg:top-[100px] animate-spin-slow"
                src="{{ asset('edurock/assets/images/herobanner/herobanner__2.png') }}" alt="">
            <img class="absolute right-[30px] top-0 md:right-10 lg:right-[575px] 2xl:top-20 animate-move-var2 opacity-50 hidden md:block"
                src="{{ asset('edurock/assets/images/herobanner/herobanner__3.png') }}" alt="">

            <img class="absolute right-[30px] top-[212px] md:right-10 md:top-[157px] lg:right-[45px] lg:top-[100px] animate-move-hor"
                src="{{ asset('edurock/assets/images/herobanner/herobanner__5.png') }}" alt="">
        </div>
        <div class="container flex flex-col h-full z-[11] relative">
            <div class="text-center my-auto">
                <h1
                    class="text-3xl md:text-size-40 2xl:text-size-55 font-bold text-blackColor dark:text-blackColor-dark mb-7 md:mb-6 pt-3">
                    {{ $title }}
                </h1>

                @if ($breadcrumbs)
                    <ul class="flex gap-1 justify-center">
                        @foreach ($breadcrumbs as $i => $breadcrumb)
                            <li>
                                @if ($breadcrumb === 'Home')
                                    <a href="/" class="text-lg text-blackColor2 dark:text-blackColor2-dark">
                                        <span class="hover:underline">Home</span>
                                        <i class="icofont-simple-right"></i>
                                    </a>
                                @elseif ($i < count($breadcrumbs) - 1)
                                    <a href="{{ $breadcrumb['route'] ?? '#' }}"
                                        class="text-lg text-blackColor2 dark:text-blackColor2-dark">
                                        <span class="hover:underline">{{ $breadcrumb['title'] ?? 'item' }}</span>
                                        <i class="icofont-simple-right"></i>
                                    </a>
                                @else
                                    <span class="text-lg text-blackColor2 dark:text-blackColor2-dark">
                                        {{ is_string($breadcrumb) ? $breadcrumb : $breadcrumb['title'] }}
                                    </span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
</section>