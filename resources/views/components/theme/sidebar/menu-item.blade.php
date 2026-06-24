<li {{ $attributes->merge([
    'class' => '
        py-2 sm:py-2.5
        last:border-b-0
    '
]) }}>
    {{ $slot }}
</li>
