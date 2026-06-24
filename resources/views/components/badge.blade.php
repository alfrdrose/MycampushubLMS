@props(['variant' => 'default'])

@php
    $classNames = match ($variant) {
        'secondary' => 'bg-secondaryColor',
        default => 'bg-primaryColor',
    };
@endphp

<span {{ $attributes->merge(['class' => "text-xs font-medium text-white px-2.5 rounded-full {$classNames}"]) }}>
    {{ $slot }}
</span>