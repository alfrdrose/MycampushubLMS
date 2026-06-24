<x-auth2-layout title="Forgot Password" :breadcrumbs="[
        'Home',
        ['title' => 'Forgot Password'],
    ]">

    <div>
        <div class="text-center my-8">
            <span class="rounded-xl border-2 p-3 text-6xl text-slate-800">
                <i class="icofont-ui-password"></i>
            </span>
        </div>

        <p class="text-contentColor dark:text-contentColor-dark mb-15px">
            {{ __('Forgot your password? No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one.') }}
        </p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <!-- Email Address -->
        <div>
            <x-form.label for="email"> {{ __('Email') }} </x-form.label>
            <x-form.input type="email" id="email" name="email" :value="old('email')" placeholder="Enter Email" required
                autofocus autocomplete="username" />
            <x-form.error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="my-25px text-center">
            <x-button class="w-full mb-5"> {{ __('Reset Password') }} </x-button>
            <x-link :href="route('login')">Back to login</x-link>
        </div>
    </form>

</x-auth2-layout>