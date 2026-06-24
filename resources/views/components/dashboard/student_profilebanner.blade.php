<div class="flex flex-row items-center gap-3 shadow-lg bg-[#17093E]  rounded-lg p-8 sm:p-4 md:p-6 dark:border dark:border-[#161c42]">

    {{-- Profile picture --}}
    <div class="flex-shrink-0">
        <img
    src="{{ Auth::user()->student?->profileImageUrl() }}"
    alt="Profile Picture"
    class="w-12 h-12 rounded-full border-2 border-primaryColor object-cover"
>

    </div>

    {{-- Name & ID --}}
    <div class="flex-1 min-w-0">
        <p class="font-bold text-white text-sm sm:text-xl md:text-2xl truncate">
            {{ Auth::user()->student->first_name }} {{ Auth::user()->student->last_name }}
        </p>
        <p class="text-white text-xs sm:text-sm">
            Student ID: {{ Auth::user()->id }}
        </p>
    </div>

    {{-- Edit button --}}
    <div class="flex-shrink-0">
        <a href="{{ route('student.profile') }}"
           class="bg-primaryColor text-white  font-semibold rounded px-3 py-1 sm:px-4 sm:py-2 hover:bg-[#4c24bb] whitespace-nowrap text-xs sm:text-base">
            Edit Profile
        </a>
    </div>

</div>
