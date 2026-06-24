@extends('pages.teacher.layout')

@section('content')
<div class="w-full">
    <div class="bg-[#FFFFFF] dark:bg-whiteColor-dark dark:border shadow-lg dark:border-[#5F2DED] p-4 sm:p-6 md:p-8 rounded-lg">

        {{-- Header --}}
        <div class="border-b border-[#D4D4D8] dark:border-white mb-4">
            <h1 class="text-2xl sm:text-3xl font-bold text-[#5E5F60] dark:text-white">
                Dashboard
            </h1>
        </div>

        {{-- Main Body --}}
        <div class="flex flex-col lg:flex-row gap-4">

            {{-- Announcements Panel --}}
            <div
                class="w-full lg:w-[40%] p-4 sm:p-5 border-[#5F2DED] border rounded"
                x-data="{
                    open: false,
                    selected: null,
                    openModal(announcement) {
                        this.selected = announcement;
                        this.open = true;
                    }
                }"
            >
                <div class="flex flex-row gap-2 items-center">
                    <i class="icofont-notification text-xl dark:text-white"></i>
                    <p class="text-lg sm:text-xl text-[#373737] dark:text-white font-semibold">
                        Announcements
                    </p>
                </div>

                <p class="text-sm text-[#757575] dark:text-white">
                    Latest updates and notifications
                </p>

                {{-- AJAX container --}}
                <div id="announcement-container" class="mt-4">
                    @include('pages.teacher.pages.announcement-list', ['announcements' => $announcements])
                </div>

                {{-- Modal --}}
<div
    x-show="open"
    x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center p-4"
>
    {{-- Overlay --}}
    <div
        class="absolute inset-0 bg-black/50"
        @click="open = false"
    ></div>

    {{-- Modal Box --}}
    <div
        class="relative w-full max-w-3xl bg-white dark:bg-whiteColor-dark
               rounded-xl shadow-lg flex flex-col
               max-h-[90vh]"
        x-transition
        @keydown.escape.window="open = false"
    >
        {{-- Header --}}
        <div class="flex justify-between items-start p-4 sm:p-6 border-b border-[#D4D4D8] dark:border-[#5F2DED]">
            <div>
                <h2
                    class="text-xl sm:text-2xl font-bold text-[#5E5F60] dark:text-white"
                    x-text="selected?.title"
                ></h2>

                <p
                    class="text-sm text-[#757575] dark:text-white mt-1"
                    x-text="selected?.date"
                ></p>
            </div>

            <button
                type="button"
                @click="open = false"
                class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 text-xl"
            >
                ✕
            </button>
        </div>

        {{-- Scrollable Content --}}
        <div class="p-4 sm:p-6 overflow-y-auto">

            <template x-if="selected?.image">
                <img
                    :src="selected.image"
                    class="w-full h-48 sm:h-64 object-cover rounded-lg mb-4
                           border border-[#D4D4D8] dark:border-[#5F2DED]"
                    alt="Announcement Image"
                >
            </template>

            <div
                class="text-[#373737] dark:text-white text-sm leading-relaxed
                       prose dark:prose-invert max-w-none"
                x-html="selected?.description"
            ></div>

        </div>

        {{-- Footer --}}
        <div class="p-4 sm:p-6 border-t border-[#D4D4D8] dark:border-[#5F2DED] flex justify-end">
            <button
                type="button"
                @click="open = false"
                class="bg-[#5F2DED] text-white px-4 py-2 rounded-lg
                       hover:scale-105 transition"
            >
                Close
            </button>
        </div>
    </div>
</div>

            </div>

            {{-- Right Panel --}}
            <div class="w-full lg:w-[60%] flex flex-col gap-4">

                {{-- Stats --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="border-[#5F2DED] border rounded flex items-center">
                        <div class="flex flex-col w-3/4 px-6 py-4">
                            <p class="text-[#343434] dark:text-white font-semibold">Total Students</p>
                            <p class="text-3xl sm:text-4xl font-bold text-[#5E5F60] dark:text-white mt-2">{{ $totalStudents }}</p>
                            <p class="text-[#757575] dark:text-white text-sm mt-4">Currently Enrolled</p>
                        </div>
                        <div class="w-1/4 pr-2 flex justify-center">
                            <div class="w-11 h-11 rounded-full bg-[#E1D6FF] flex justify-center items-center">
                                <i class="icofont-group-students text-xl" style="color:#5F2DED;"></i>
                            </div>
                        </div>
                    </div>

                    <div class="border-[#5F2DED] border rounded flex items-center">
                        <div class="flex flex-col w-3/4 px-6 py-4">
                            <p class="text-[#343434] dark:text-white font-semibold">Total Classes</p>
                            <p class="text-3xl sm:text-4xl font-bold text-[#5E5F60] dark:text-white mt-2">{{ $totalClasses }}</p>
                            <p class="text-[#757575] dark:text-white text-sm mt-4">Assigned Classes</p>
                        </div>
                        <div class="w-1/4 pr-2 flex justify-center">
                            <div class="w-11 h-11 rounded-full bg-[#E1D6FF] flex justify-center items-center">
                                <i class="icofont-book text-xl" style="color:#5F2DED;"></i>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Tasks --}}
                <div class="border-[#5F2DED] border rounded p-4 sm:p-5">
                    <div class="flex flex-col sm:flex-row gap-2 sm:gap-0">
                        <div class="flex items-center gap-2 sm:w-1/2">
                            <i class="icofont-tasks-alt text-xl text-primaryColor"></i>
                            <p class="text-[#343434] dark:text-white font-semibold">
                                Tasks Assigned by you
                            </p>
                        </div>
                        <div class="sm:w-1/2 sm:text-right">
                            <p class="text-[#343434] dark:text-white font-semibold">
                                Students Completed
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-col gap-6 mt-6">
                        @forelse ($tasks as $index => $task)
                            <div class="flex w-full">
                                <div class="flex items-center justify-center mr-4">
                                    <div class="w-10 h-10 rounded-full bg-[#5F2DED] flex items-center justify-center">
                                        <span class="text-white font-bold">
                                            {{ ($tasks->currentPage() - 1) * $tasks->perPage() + $index + 1 }}
                                        </span>
                                    </div>
                                </div>

                                <div class="flex-1">
                                    <p class="font-semibold text-[#373737] dark:text-white">
                                        {{ $task['section'] }}
                                    </p>
                                    <p class="text-[#343434] dark:text-white text-sm">
                                        {{ $task['title'] }}
                                    </p>
                                </div>

                                <div class="flex items-center ">
                                    @php
                                        $statusColor = $task['completed'] == $task['total']
                                            ? 'green'
                                            : ($task['completed'] == 0 ? 'red' : 'yellow');
                                    @endphp

                                    <div class="flex items-center gap-2 px-4 py-1.5 rounded-2xl text-sm
                                        {{ $statusColor == 'green' ? 'bg-[#DCFCE7] text-[#00A63E]' : '' }}
                                        {{ $statusColor == 'yellow' ? 'bg-[#FEFCE8] text-[#D08700]' : '' }}
                                        {{ $statusColor == 'red' ? 'bg-[#FFE2E2] text-[#E7000B]' : '' }}
                                    ">
                                        {{-- Status Dot --}}
                                        <span class="w-1.5 h-1.5 rounded-full
                                            {{ $statusColor == 'green' ? 'bg-[#16A34A]' : '' }}
                                            {{ $statusColor == 'yellow' ? 'bg-[#D08700]' : '' }}
                                            {{ $statusColor == 'red' ? 'bg-[#DC2626]' : '' }}
                                        "></span>

                                        {{-- Text --}}
                                        <span>
                                            {{ $task['completed'] }} of {{ $task['total'] }}
                                        </span>
                                    </div>
                                </div>

                            </div>
                        @empty
                            <p class="text-sm text-gray-500">No tasks assigned yet.</p>
                        @endforelse
                        </div>
                        @if ($tasks->hasPages())
                            <div class="mt-6 justify-center items-center flex">
                                {{ $tasks->links() }}
                            </div>
                        @endif


                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener("click", function(e) {
    const link = e.target.closest("#announcement-container .pagination a");
    if (!link) return;

    e.preventDefault();

    fetch(link.href, {
        headers: {
            "X-Requested-With": "XMLHttpRequest"
        }
    })
    .then(res => res.text())
    .then(html => {
        document.getElementById("announcement-container").innerHTML = html;
    })
    .catch(err => console.error(err));
});
</script>
@endpush
