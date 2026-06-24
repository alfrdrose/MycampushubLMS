<nav role="navigation" aria-label="Pagination Navigation">
    <div class="inline-flex items-center space-x-2">
        @if ($paginator->onFirstPage())
            <span class="px-3 py-2 mr-1 text-gray-400 text-md rounded-md cursor-not-allowed dark:text-gray-600">
                &lt; Prev
            </span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" class="px-3 py-2 mr-1 text-[#2E2E2E] text-md rounded-md hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-700">
                &lt; Prev
            </a>
        @endif

        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="px-3 py-2 -ml-px text-gray-500 bg-gray-100 rounded-md cursor-default dark:bg-gray-800 dark:text-gray-500">{{ $element }}</span>
            @endif

            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span class="px-3 py-2 -ml-px text-[#5F2DED] border border-[#E2E8F0] rounded-md font-medium dark:border-[#5F2DED]">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}" class="px-3 py-2 -ml-px text-gray-700 rounded-md hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" class="px-3 py-2 ml-1 text-[#2E2E2E] text-md rounded-md hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-700">
                Next &gt;
            </a>
        @else
            <span class="px-3 py-2 ml-1 rounded-md cursor-not-allowed text-gray-500 text-md dark:text-gray-600">
                Next &gt;
            </span>
        @endif
    </div>
</nav>
