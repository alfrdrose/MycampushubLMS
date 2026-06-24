<button {{ $attributes->merge(['class' => 'inline w-fit hover:text-primaryColor relative after:absolute after:left-0 after:bottom-0.5 after:w-0 after:h-0.5 after:bg-primaryColor after:transition-all after:duration-300 hover:after:w-full']) }}>
    {{ $slot }}
</button>