<button {{ $attributes->merge(['type' => 'submit', 'class' => 'text-size-15 text-whiteColor bg-secondaryColor px-25px py-10px border border-secondaryColor hover:text-secondaryColor hover:bg-whiteColor inline-block rounded group dark:hover:text-whiteColor dark:hover:bg-whiteColor-dark']) }}>
    {{ $slot }}
</button>
