@props(['value'])

<label {{ $attributes->merge(['class' => 'text-contentColor dark:text-contentColor-dark mb-10px block']) }}>
    {{ $value ?? $slot }}
</label>
