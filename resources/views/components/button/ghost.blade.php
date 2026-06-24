<button {{ $attributes->merge(['type' => 'submit', 'class' => 'text-size-15 text-blackColor2 px-25px py-10px hover:text-whiteColor hover:bg-primaryColor inline-block rounded group dark:hover:text-whiteColor dark:hover:bg-primaryColor-dark']) }}>
    {{ $slot }}
</button>