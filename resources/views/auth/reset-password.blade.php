<x-auth2-layout title="Reset Password" :breadcrumbs="[
        'Home',
        'Reset Password',
    ]">

    <div class="text-center my-8">
        <span class="rounded-xl border-2 p-3 text-6xl text-slate-800">
            <i class="icofont-ui-password"></i>
        </span>
    </div>

    <form method="POST" action="{{ route('password.store') }}">
        @csrf

        <!-- Password Reset Token -->
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <!-- Email Address -->
        <div>
            <x-form.label for="email"> {{ __('Email') }} </x-form.label>
            <x-form.input type="email" id="email" name="email" :value="old('email', $request->email)"
                placeholder="Enter Email" required autofocus autocomplete="username" />
            <x-form.error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-form.label for="password"> {{ __('Password') }} </x-form.label>
            <x-form.input type="password" id="password" name="password" placeholder="Enter New Password" required
                autocomplete="new-password" />
            <x-form.error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-form.label for="password_confirmation"> {{ __('Password') }} </x-form.label>
            <x-form.input type="password" id="password_confirmation" name="password_confirmation"
                placeholder="Confirm Password" required autocomplete="new-password" />
            <x-form.error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="my-25px text-center">
            <x-button class="w-full mb-4"> {{ __('Reset Password') }} </x-button>
            <x-link :href="route('login')">Back to login</x-link>
        </div>
    </form>

</x-auth2-layout>
