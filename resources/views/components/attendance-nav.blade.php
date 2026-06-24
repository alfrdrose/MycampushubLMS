<div class="flex flex-row">
    {{-- Grade Report --}}
    <a
        href="{{ route('teacher.attendance.view', [$section->id, $subject->id]) }}"
        class="px-4 py-2 text-sm rounded-t-xl mt-2 w-fit cursor-pointer transition-all
            {{ request()->routeIs('teacher.attendance.view')
                ? 'bg-primaryColor text-white'
                : 'border text-[#373737] dark:text-white border-[#D4D4D8] dark:border-[#334155]' }}"
    >
        Attendance
    </a>

    {{-- Grade Setup --}}
    <a
        href="{{ route('teacher.attendance.score', [$section->id, $subject->id]) }}"
        class="px-4 py-2 text-sm rounded-t-xl mt-2 w-fit cursor-pointer transition-all
            {{ request()->routeIs('teacher.attendance.score')
                ? 'bg-primaryColor text-white'
                : 'border text-[#373737] dark:text-white border-[#D4D4D8] dark:border-[#334155]' }}"
    >
        Scores
    </a>
</div>
