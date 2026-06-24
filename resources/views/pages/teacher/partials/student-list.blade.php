<div class="flex flex-col mt-3">
    @forelse($students as $student)
        <div class="w-full flex flex-row items-center
            {{ $loop->odd ? 'bg-[#F0F9FF] dark:bg-[#0B1220]' : 'bg-white dark:bg-[#0F1724]' }}
            px-4 py-2">

            <div class="w-1/2 flex flex-row items-center gap-3">
                <div class="w-8 h-8 rounded-full overflow-hidden border bg-gray-200 dark:bg-gray-700">
                    @if($student->hasMedia('profile_image'))
                        <img src="{{ $student->profileImageUrl() }}"
                             class="w-full h-full object-cover rounded-full">
                    @else
                        <div class="w-full h-full flex items-center justify-center text-xs font-semibold text-gray-500">
                            {{ strtoupper(substr($student->first_name, 0, 1)) }}
                        </div>
                    @endif
                </div>

                <p class="text-[#373737] dark:text-gray-100">
                    {{ $student->last_name }}, {{ $student->first_name }}
                </p>
            </div>
        </div>
    @empty
        <div class="w-full text-center py-4 text-gray-500">
            No students found
        </div>
    @endforelse
</div>

<div class="mt-4">
    {{ $students->links() }}
</div>
