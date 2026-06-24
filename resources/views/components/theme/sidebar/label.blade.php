<h5
    {{ $attributes->merge([
        'class' => '
            inline-flex items-center
            w-fit max-w-full

            text-xs sm:text-sm
            font-semibold uppercase tracking-wide
            text-contentColor dark:text-contentColor-dark
            bg-lightGrey5 dark:bg-[#161838]

            px-3 sm:px-4
            py-1.5 sm:py-2

            mt-3 sm:mt-4
            mb-4 sm:mb-6

            rounded-md
            leading-tight
            whitespace-nowrap
        '
    ]) }}
>
    {{ $slot }}
</h5>
