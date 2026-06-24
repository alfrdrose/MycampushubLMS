@props(['disabled' => false])

<input 
spellcheck="false"
@disabled($disabled) {{ $attributes->merge(['class' => 'w-full h-52px leading-52px pl-5 bg-transparent text-sm focus:ring-primaryColor focus:border-primaryColor text-contentColor dark:text-white border border-borderColor dark:border-borderColor-dark  placeholder:text-placeholder dark:placeholder:text-white placeholder:opacity-80 font-medium rounded']) }}>