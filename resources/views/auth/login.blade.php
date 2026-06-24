<x-auth-layout title="Welcome!" :breadcrumbs="['Home', 'Login']">

    <div id="login-form-wrapper">

        <!-- heading -->
        <div class="text-center">
            <h3 class="text-size-32 font-bold text-blackColor dark:text-blackColor-dark mb-2 leading-normal">
                Login
            </h3>
            <p class="text-contentColor dark:text-contentColor-dark mb-15px">
                Enter your login details below
            </p>
        </div>

        <form
            method="POST"
            action="{{ route('login') }}"
            class="pt-25px"
            data-aos="fade-up"
            id="login-form"
        >
            @csrf

            <!-- Email Address -->
            <div class="mb-25px">
                <x-form.label for="email">Email</x-form.label>
                <x-form.input
                    type="text"
                    id="email"
                    name="email"
                    placeholder="Your username or email"
                    value="{{ old('email') }}"
                />
                <p id="email-error" class="text-red-500 text-sm mt-2 hidden">
                    Email is required.
                </p>
                <x-form.error :messages="$errors->get('email')" class="mt-2" />

                <!-- Session Status -->
                <x-auth-session-status class="mb-4" :status="session('status')" />
            </div>

            <!-- Password -->
            <div class="mb-25px">
                <x-form.label for="password">Password</x-form.label>
                <x-form.input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Password"
                />
                <p id="password-error" class="text-red-500 text-sm mt-2 hidden">
                    Password is required.
                </p>
                <x-form.error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <div
                class="text-contentColor dark:text-contentColor-dark flex flex-col-reverse sm:flex-row sm:items-center gap-3">

                <!-- Remember Me -->
                <div class="flex items-center">
                    <input
                        type="checkbox"
                        id="remember"
                        name="remember"
                        class="w-18px h-18px mr-2 block box-content cursor-pointer bg-transparent"
                    >
                    <label for="remember"> {{ __('Remember me') }} </label>
                </div>

            </div>

            <div class="my-25px text-center">
                <x-button class="w-full" type="submit">
                    {{ __('Log in') }}
                </x-button>
            </div>
        </form>

    </div>

    {{-- Frontend validation + auto-scroll --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('login-form');
            const email = document.getElementById('email');
            const password = document.getElementById('password');

            const emailError = document.getElementById('email-error');
            const passwordError = document.getElementById('password-error');

            form.addEventListener('submit', function (e) {
                let hasError = false;

                // Reset frontend errors
                emailError.classList.add('hidden');
                passwordError.classList.add('hidden');

                if (!email.value.trim()) {
                    emailError.classList.remove('hidden');
                    hasError = true;
                }

                if (!password.value.trim()) {
                    passwordError.classList.remove('hidden');
                    hasError = true;
                }

                // Stop submit if frontend validation fails
                if (hasError) {
                    e.preventDefault();
                }
            });

            // If backend validation/auth failed → scroll to form
            @if ($errors->any() || session('status'))
                const wrapper = document.getElementById('login-form-wrapper');
                wrapper.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });

                // Optional: focus first input
                email.focus();
            @endif
        });
    </script>

</x-auth-layout>
