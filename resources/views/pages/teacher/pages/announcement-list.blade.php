<div id="announcement-list" class="mt-6 space-y-6">
    @forelse($announcements as $announcement)
        <button
            type="button"
            @click="openModal({
                title: @js($announcement->title),
                description: @js($announcement->description),
                date: @js(\Carbon\Carbon::parse($announcement->date_posted)->format('F d, Y h:i A')),
                image: @js($announcement->image ? asset('storage/' . $announcement->image) : null)
            })"
            class="w-full text-left border-[#5F2DED] border-l-4 px-4 py-2 hover:bg-[#E1D6FF] dark:hover:bg-[#5F2DED]/20 transition rounded"
        >
            <p class="text-lg text-[#373737] dark:text-white font-semibold">
                {{ $announcement->title }}
            </p>

            <p class="mt-2 text-[#757575] dark:text-white text-sm">
                {{ \Illuminate\Support\Str::limit(strip_tags($announcement->description), 90, '...') }}
            </p>

            <p class="text-xs text-[#757575] dark:text-white mt-2">
                {{ \Carbon\Carbon::parse($announcement->date_posted)->diffForHumans() }}
            </p>
        </button>
    @empty
        <p class="text-sm text-[#757575] dark:text-white mt-4">
            No announcements yet.
        </p>
    @endforelse
</div>

{{-- Pagination --}}
@if($announcements->hasPages())
    <div class="mt-4 flex justify-center text-sm">
        {!! $announcements->links() !!}
    </div>
@endif
