<div class="inline-block max-w-full bg-[#F1F5F9] dark:bg-[#2A2A3B] rounded-xl mb-0 p-2 overflow-x-auto no-scrollbar">
    <div class="flex lg:gap-6 sm:gap-4 md:gap-4 min-w-max">
        @php
            $lockNav = ($active === 'add_questions');
        @endphp
        @foreach($items as $item)
            @php
                $isActive = ($active === $item['key']);
                $base = 'font-semibold rounded-xl px-3 sm:px-4 md:px-5 lg:px-6 py-1 sm:py-1.5 md:py-2 whitespace-nowrap flex-shrink-0 transition sm:text-xs md:text-xs lg:text-regular';
                $activeClasses = 'bg-[#5F2DED] text-white';
                $inactiveClasses = 'text-[#5E5F60] dark:text-gray-300 hover:text-[#5F2DED] dark:hover:text-[#A88BFF]';

                $isLockedItem = $lockNav && in_array($item['key'], ['settings', 'submissions'], true);
                $lockedClasses = 'text-[#5E5F60] dark:text-gray-300 opacity-60 cursor-not-allowed';
            @endphp

            <a
                href="{{ $isLockedItem ? '#' : $item['url'] }}"
                @if($isLockedItem)
                    title="{{ $item['label'] }}"
                    onclick="return false;"
                @endif
                class="{{ $base }} {{ $isActive ? $activeClasses : ($isLockedItem ? $lockedClasses : $inactiveClasses) }}"
            >
                {{ $item['label'] }}
            </a>
        @endforeach

        {{-- Optional slot for adding right-side buttons --}}
        {{ $slot }}
    </div>
</div>
