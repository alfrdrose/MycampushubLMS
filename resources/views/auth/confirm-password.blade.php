<x-auth2-layout title="Confirm Password" :breadcrumbs="[
        'home',
        ['title' => 'Confirm Password'],
    ]">

    <div>
        <p class="text-contentColor dark:text-contentColor-dark mb-15px">
            {{ __('This is a secure area of the application. Please confirm your password before continuing.') }}
        </p>
    </div>

    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf

        <!-- Password -->
        <div>
            <x-form.label for="password"> {{ __('Password') }} </x-form.label>
            <x-form.input type="password" id="password" name="password" placeholder="Enter Password" required autofocus
                autocomplete="current-password" />
            <x-form.error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="my-25px text-center">
            <x-button class="w-full"> {{ __('Confirm') }} </x-button>
        </div>
    </form>

</x-auth2-layout>
