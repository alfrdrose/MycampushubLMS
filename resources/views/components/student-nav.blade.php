<div
    class="
        bg-[#F1F5F9] dark:bg-[#2A2A3B]
        rounded-xl mb-4 p-2
        w-full xl:w-fit
        overflow-x-auto no-scrollbar
    "
>
    <div class="flex items-center gap-1 sm:gap-2 min-w-max">
        @foreach($items as $item)
            @php
                $isActive = $active && $active === $item['key'];

                $base = '
                    font-semibold rounded-xl
                    px-3 sm:px-4 md:px-5 lg:px-5
                    py-1 sm:py-1.5 md:py-2
                    whitespace-nowrap flex-shrink-0
                    transition
                    text-xs sm:text-sm md:text-sm lg:text-sm
                ';

                $activeClasses = 'bg-[#5F2DED] text-white';

                $inactiveClasses = '
                    text-[#5E5F60] dark:text-gray-300
                    hover:text-[#5F2DED]
                    dark:hover:text-[#A88BFF]
                ';
            @endphp

            <a
                href="{{ $item['url'] }}"
                class="{{ $base }} {{ $isActive ? $activeClasses : $inactiveClasses }}"
            >
                {{ $item['label'] }}
            </a>
        @endforeach

        {{ $slot }}
    </div>
</div>
