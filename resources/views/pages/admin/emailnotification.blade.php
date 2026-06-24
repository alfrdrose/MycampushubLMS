@extends('layouts.dashboard')

@section('content')
@php
    $days = [
        'Sunday','Monday','Tuesday','Wednesday',
        'Thursday','Friday','Saturday'
    ];
@endphp

<div
    x-data="emailSettings()"
    x-init="init"
    class="w-full p-6 md:p-10 bg-whiteColor dark:bg-whiteColor-dark
           shadow-accordion dark:shadow-accordion-dark rounded-lg"
>
    {{-- Header --}}
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-[#5E5F60] dark:text-white">
            Email Notification Settings
        </h1>
        <p class="text-[#ABB7C2]">
            Configure Notification Settings
        </p>
    </div>

    {{-- Content --}}
    <div class="border-t border-[#D4D4D8] dark:border-gray-600 py-7">

        {{-- Toggle --}}
        <div class="flex items-center justify-between gap-4">
            <div>
                <p class="text-base font-medium text-[#5E5F60] dark:text-white">
                    Turn on email notifications
                </p>
                <p class="text-sm text-gray-500 dark:text-gray-300">
                    Sends attendance notifications to guardian emails.
                </p>
            </div>

            <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" class="sr-only peer" x-model="enabled">
                <div
                    class="w-11 h-6 bg-gray-300 dark:bg-gray-600 rounded-full
                           peer-checked:bg-primaryColor
                           after:content-[''] after:absolute after:top-[2px] after:left-[2px]
                           after:bg-white after:rounded-full after:h-5 after:w-5
                           after:transition-all peer-checked:after:translate-x-full">
                </div>
            </label>
        </div>

       

        {{-- Schedule --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-8">

            {{-- Start Day --}}
            <div>
                <label class="block text-sm font-medium mb-2 dark:text-white">
                    Start Day
                </label>
                <select
                    x-model="startDay"
                    :disabled="!enabled"
                    @change="validate"
                    class="w-full rounded-lg px-3 py-2
                           bg-white dark:bg-[#1E293B]
                           border dark:border-gray-600
                           text-[#5E5F60] dark:text-white
                           disabled:opacity-50 disabled:cursor-not-allowed
                           focus:outline-none focus:ring-2 focus:ring-primaryColor">
                    <option value="">Select day</option>
                    @foreach ($days as $day)
                        <option value="{{ $day }}">{{ $day }}</option>
                    @endforeach
                </select>
                <p x-show="errors.startDay" class="text-sm text-red-500 mt-1" x-text="errors.startDay"></p>
            </div>

            {{-- End Day --}}
            <div>
                <label class="block text-sm font-medium mb-2 dark:text-white">
                    End Day
                </label>
                <select
                    x-model="endDay"
                    :disabled="!enabled"
                    @change="validate"
                    class="w-full rounded-lg px-3 py-2
                           bg-white dark:bg-[#1E293B]
                           border dark:border-gray-600
                           text-[#5E5F60] dark:text-white
                           disabled:opacity-50 disabled:cursor-not-allowed
                           focus:outline-none focus:ring-2 focus:ring-primaryColor">
                    <option value="">Select day</option>
                    @foreach ($days as $day)
                        <option value="{{ $day }}">{{ $day }}</option>
                    @endforeach
                </select>
                <p x-show="errors.endDay" class="text-sm text-red-500 mt-1" x-text="errors.endDay"></p>
            </div>

            {{-- Send Time --}}
            <div>
                <label class="block text-sm font-medium mb-2 dark:text-white">
                    Send Out Time
                </label>
                <input
                    type="text"
                    id="send_time"
                    x-model="sendTime"
                    :disabled="!enabled"
                    placeholder="Select time"
                    class="w-full rounded-lg px-3 py-2
                           bg-white dark:bg-[#1E293B]
                           border dark:border-gray-600
                           text-[#5E5F60] dark:text-white
                           disabled:opacity-50 disabled:cursor-not-allowed
                           focus:outline-none focus:ring-2 focus:ring-primaryColor">
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    This time applies to the selected <strong>End Day</strong>.
                </p>
                <p x-show="errors.sendTime" class="text-sm text-red-500 mt-1" x-text="errors.sendTime"></p>
            </div>

        </div>

        {{-- Save Button --}}
        <div class="mt-10 flex justify-end">
            <button
                @click="openConfirm"
                :disabled="hasErrors || loading"
                class="px-6 py-2 rounded-lg text-white
                       bg-primaryColor
                       disabled:bg-gray-400 disabled:cursor-not-allowed
                       transition">
                <span x-show="!loading">Save Settings</span>
                <span x-show="loading">Saving...</span>
            </button>
        </div>
         {{-- Instructions --}}
        <div class="mt-4 p-4 rounded-lg bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
            <p class="text-sm text-gray-600 dark:text-gray-300 leading-relaxed">
    <strong>How this works:</strong><br>
    Attendance, activities, and quiz data are recorded from the selected
    <strong>Start Day</strong> through <strong>End Day</strong> (for example, Monday–Friday).
    A <strong>single summary email</strong> is then sent on the <strong>End Day</strong> at the
    selected <strong>Send Out Time</strong>.
</p>

        </div>

        {{-- Test Button --}}
        <div class="mt-4 flex justify-end">
            <button
                @click="forceSend"
                :disabled="loading"
                class="px-6 py-2 rounded-lg text-white
                    bg-red-500 hover:bg-red-600
                    disabled:bg-gray-400 disabled:cursor-not-allowed
                    transition">
                <span x-show="!loading">Force Send Test Email</span>
                <span x-show="loading">Sending...</span>
            </button>
        </div>

    </div>

    {{-- Confirmation Modal --}}
    <div
        x-show="showConfirm"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
    >
        <div class="bg-white dark:bg-[#1E293B] rounded-lg p-6 w-full max-w-md">
            <h2 class="text-lg font-semibold mb-4 dark:text-white">
                Confirm Save
            </h2>

            <p class="text-sm text-gray-600 dark:text-gray-300 mb-6">
                Are you sure you want to save these notification settings?
            </p>

            <div class="flex justify-end gap-3">
                <button
                    @click="showConfirm = false"
                    class="px-4 py-2 rounded bg-gray-200 dark:bg-gray-700 dark:text-white"
                >
                    Cancel
                </button>

                <button
                    @click="save"
                    class="px-4 py-2 rounded bg-primaryColor text-white"
                >
                    Yes, Save
                </button>
            </div>
        </div>
    </div>

</div>

{{-- Alpine Logic --}}
<script>
function emailSettings() {
    return {
        enabled: false,
        startDay: '',
        endDay: '',
        sendTime: '',
        errors: {},
        showConfirm: false,
        loading: false,

        init() {
            this.fetchSettings();

            this.$watch('enabled', value => {
                if (!value) {
                    this.startDay = '';
                    this.endDay = '';
                    this.sendTime = '';
                    this.errors = {};
                } else {
                    this.validate();
                }
            });

            this.$watch('sendTime', value => {
                this.validate();

                if (!value) return;

                const input = document.getElementById('send_time');

                const trySet = () => {
                    if (input && input._flatpickr) {
                        input._flatpickr.setDate(value, false);
                        clearInterval(timer);
                    }
                };

                const timer = setInterval(trySet, 50);
                trySet();
            });
        },

        async fetchSettings() {
            const res = await fetch('{{ route('admin.notification-settings.show') }}');
            const data = await res.json();

            this.enabled = data.enabled;
            this.startDay = data.start_day ?? '';
            this.endDay = data.end_day ?? '';
            this.sendTime = data.send_out_time ?? '';

            this.validate();
        },

        validate() {
            this.errors = {};
            if (!this.enabled) return;

            if (!this.startDay) this.errors.startDay = 'Start day is required.';
            if (!this.endDay) this.errors.endDay = 'End day is required.';
            if (!this.sendTime) this.errors.sendTime = 'Send time is required.';

            if (this.startDay && this.endDay) {
                const days = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
                if (days.indexOf(this.endDay) <= days.indexOf(this.startDay)) {
                    this.errors.endDay = 'End day must be after start day.';
                }
            }
        },

        openConfirm() {
            this.validate();
            if (!this.hasErrors) this.showConfirm = true;
        },

        async save() {
            this.loading = true;
            this.showConfirm = false;

            await fetch('{{ route('admin.notification-settings.store') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({
                    enabled: this.enabled,
                    start_day: this.enabled ? this.startDay : null,
                    end_day: this.enabled ? this.endDay : null,
                    send_out_time: this.enabled ? this.sendTime : null,
                }),
            });

            this.loading = false;
            window.location.reload();
        },

        get hasErrors() {
            return Object.keys(this.errors).length > 0;
        },

        async forceSend() {
            this.loading = true;

            try {
                const res = await fetch(
                    '{{ route('admin.notification-settings.force-send') }}',
                    {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                    }
                );

                const data = await res.json();

                if (!res.ok) {
                    throw new Error(data.message ?? 'Failed');
                }

                alert('✅ Weekly attendance email sent (forced). Check Gmail.');
            } catch (e) {
                alert('❌ Force send failed. Check Laravel logs.');
            } finally {
                this.loading = false;
            }
        }
    }
}
</script>
@endsection
