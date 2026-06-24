@props(['href' => '#', 'active' => false])

@php
    $stateClass = $active ?? false
        ? 'active text-primaryColor dark:text-primaryColor'
        : '';
@endphp

<a href="{{ $href }}" {{ $attributes->merge(['class' => "text-contentColor dark:text-contentColor-dark hover:text-primaryColor dark:hover:text-primaryColor leading-1.8 flex items-center gap-3 text-nowrap {$stateClass}"]) }}>
    {{ $slot }}
</a>