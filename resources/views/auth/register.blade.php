<x-auth-layout title="Sign Up" :breadcrumbs="['Home', 'Sign Up']">

    <div>
        <!-- heading -->
        <div class="text-center">
            <h3 class="text-size-32 font-bold text-blackColor dark:text-blackColor-dark mb-2 leading-normal">
                Sign Up
            </h3>
            <p class="text-contentColor dark:text-contentColor-dark mb-15px">
                Already have an account? <x-link :href="route('login')">Log In</x-link>
            </p>
        </div>

        <form method="POST" action="{{ route('register') }}" class="pt-25px" data-aos="fade-up">
            @csrf

            <!-- Name -->
            <div class="mb-25px">
                <div>
                    <x-form.label for="name"> {{ __('Name') }} </x-form.label>
                    <x-form.input type="text" id="name" name="name" :value="old('name')" placeholder="Your Name"
                        required autofocus autocomplete="name" />
                    <x-form.error :messages="$errors->get('name')" class="mt-2" />
                </div>

                <!-- Session Status -->
                <x-auth-session-status class="mb-4" :status="session('status')" />
            </div>

            <!-- Email Address -->
            <div class="mb-25px">
                <x-form.label for="email"> {{ __('Email') }} </x-form.label>
                <x-form.input type="email" id="email" name="email" :value="old('email')" placeholder="Your email"
                    required autocomplete="username" />
                <x-form.error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <!-- Password -->
            <div class="mb-25px">
                <x-form.label for="password"> {{ __('Password') }} </x-form.label>
                <x-form.input type="password" id="password" name="password" placeholder="Password" required
                    autocomplete="new-password" />
                <x-form.error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <!-- Confirm Password -->
            <div class="mb-25px">
                <x-form.label for="password_confirmation"> {{ __('Confirm Password') }} </x-form.label>
                <x-form.input type="password" id="password_confirmation" name="password_confirmation"
                    placeholder="Re-enter Password" required autocomplete="new-password" />
            </div>

            <div class="my-25px text-center">
                <x-button class="w-full"> {{ __('Register') }} </x-button>
            </div>

        </form>
    </div>

</x-auth-layout>