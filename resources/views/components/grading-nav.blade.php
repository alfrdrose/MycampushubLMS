<div class="flex flex-row">
    {{-- Grade Report --}}
    <a
        href="{{ route('teacher.grade.report', [$section->id, $subject->id]) }}"
        class="px-4 py-2 text-sm rounded-t-xl mt-2 w-fit cursor-pointer transition-all
            {{ request()->routeIs('teacher.grade.report')
                ? 'bg-primaryColor text-white'
                : 'border text-[#373737] dark:text-white border-[#D4D4D8] dark:border-[#334155]' }}"
    >
        Grade Report
    </a>

    {{-- Grade Setup --}}
    <a
        href="{{ route('teacher.grade.setup', [$section->id, $subject->id]) }}"
        class="px-4 py-2 text-sm rounded-t-xl mt-2 w-fit cursor-pointer transition-all
            {{ request()->routeIs('teacher.grade.setup')
                ? 'bg-primaryColor text-white'
                : 'border text-[#373737] dark:text-white border-[#D4D4D8] dark:border-[#334155]' }}"
    >
        Grade Setup
    </a>
</div>
