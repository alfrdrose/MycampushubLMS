@props(['active'])

@php
    $btn_classes = ($active ?? false)
        ? 'bg-white dark:bg-whiteColor-dark shadow-bottom'
        : 'bg-lightGrey7 dark:bg-lightGrey7-dark';

    $span_classes = ($active ?? false)
        ? 'w-full'
        : 'w-0';
@endphp

<button {{ $attributes->merge(['class' => "py-9px lg:py-6 hover:text-primaryColor dark:hover:text-primaryColor dark:text-whiteColor hover:bg-white dark:hover:bg-whiteColor-dark relative group/btn hover:shadow-bottom dark:shadow-standard-dark disabled:cursor-pointer rounded-standard {$btn_classes}"]) }}>
    <span class="absolute h-1 bg-primaryColor top-0 left-0 group-hover/btn:w-full {{ $span_classes }}"></span>
    {{ $slot }}
</button>