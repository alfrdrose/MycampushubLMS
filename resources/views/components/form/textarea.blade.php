@props(['disabled' => false, 'value' => ''])

<textarea @disabled($disabled) {{ $attributes->merge(['class' => 'w-full leading-52px pl-5 bg-transparent text-sm focus:outline-none text-contentColor dark:text-contentColor-dark border border-borderColor dark:border-borderColor-dark placeholder:text-placeholder placeholder:opacity-80 font-medium rounded']) }}>{{ $value ?? $slot }}</textarea>
