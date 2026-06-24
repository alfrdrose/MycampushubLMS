<x-auth2-layout title="Veify Email" :breadcrumbs="[
        'Home',
        ['title' => 'Veify Email'],
    ]">

    <div>
        <div class="text-center my-8">
            <span class="rounded-xl border-2 p-3 text-6xl text-slate-800">
                <i class="icofont-ui-password"></i>
            </span>
        </div>

        <p class="text-contentColor dark:text-contentColor-dark mb-15px">
            {{ __('Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you? If you didn\'t receive the email, we will gladly send you another.') }}
        </p>
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 font-medium text-sm text-green-600">
            {{ __('A new verification link has been sent to the email address you provided during registration.') }}
        </div>
    @endif

    <div class="mt-4 flex items-center justify-between">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf

            <div class="mt-25px text-center">
                <x-button class="w-full"> {{ __('Resend Verification Email') }} </x-button>
            </div>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <div class="mt-5 mb-25px text-center">
                <x-button.link> {{ __('Log Out') }} </x-button>
            </div>
        </form>
    </div>

</x-auth2-layout>
