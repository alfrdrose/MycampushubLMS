<div {{ $attributes->merge(['class' => 'shadow-container bg-whiteColor dark:bg-whiteColor-dark pt-10px px-5 pb-10 md:p-50px md:pt-30px rounded-5px']) }}>
    <div class="tab-contents">
        {{ $slot }}
    </div>
</div>
