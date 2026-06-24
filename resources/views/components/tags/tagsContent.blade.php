<div class="flex-1 flex flex-col p-4 sm:p-10 h-auto bg-whiteColor shadow-lg dark:bg-darkblack rounded-2xl transition-all duration-300 dark:ring-2 dark:ring-blue-500/20 dark:ring-offset-0">

    <!-- Page Header -->
    <div id="page-header" class="mb-4">
        <h1 class="text-2xl sm:text-3xl font-bold text-[#5E5F60] dark:text-white">Tags Management</h1>
        <p class="text-sm sm:text-base text-[#ABB7C2]">Manage and organize tags</p>
    </div>

    <!-- Search Bar -->
    <div class="flex flex-col sm:flex-row w-full justify-between items-center pb-4 gap-4 sm:gap-0">
        <div class="flex-1"></div>
        <div class="relative w-full sm:w-[30%]">
            <form method="GET" action="{{ route('admin.tags') }}">
                <div class="relative w-full">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                        <i class="icofont-search-1"></i>
                    </span>
                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Search by name or category..."
                        class="w-full pl-10 pr-3 py-2 rounded-xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-[#7F6994] text-sm
                        dark:bg-bodyBg-dark dark:text-white dark:focus:ring-blue"
                    />
                </div>
            </form>
        </div>
    </div>

    <!-- Table for Desktop -->
    <div class="overflow-visible dark:bg-transparent scrollbar-thin border-b-[1px] hidden sm:block">
        <table class="min-w-full max-w-full text-sm">
            <thead class="border-b-[1px] sticky top-0">
                <tr class="bg-whiteColor dark:bg-whiteColor-dark">
                @foreach ($columns as $column)
                    <th class="py-4 text-left font-semibold text-[#5E5F60] dark:text-gray-300 {{ $column === 'id' ? 'px-8' : '' }}">
                        {{ ucfirst(str_replace('_', ' ', $column)) }}
                    </th>
                @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse ($tags as $tag)
                    <tr data-tag-id="{{ $tag->id }}" class="border-b text-[#373737] dark:text-white">
                        <td class="py-2 px-8">{{ $tag->id }}</td>
                        <td class="py-2">{{ $tag->name }}</td>
                        <td class="py-2">{{ $tag->category }}</td>
                        <td class="py-2 px-2">
                            <div class="relative inline-block text-left">
                                <button class="actionMenuBtn p-2 rounded-full hover:bg-gray-200 dark:hover:bg-gray-700">
                                    <i class="icofont-gear text-lg text-gray-600 dark:text-gray-300"></i>
                                </button>
                                <div class="actionMenu hidden absolute right-8 bottom-6 w-32 bg-white dark:bg-gray-800 rounded-md shadow-lg ring-1 ring-black ring-opacity-5 z-50">
                                    <div class="py-1">
                                        <button class="btnupdatetag w-full px-4 py-2 text-sm text-left text-[#5F2DED] hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center gap-2">
                                            <i class="icofont-ui-edit text-sm"></i> Update
                                        </button>
                                        <button class="btndeleteTag w-full px-4 py-2 text-sm text-left text-[#C10007] hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center gap-2">
                                            <i class="icofont-ui-delete text-sm"></i> Delete
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center py-4 text-gray-500">No tags found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Cards for Mobile -->
    <div class="sm:hidden flex flex-col gap-4">
        @forelse ($tags as $tag)
            <div data-tag-id="{{ $tag->id }}" class="bg-whiteColor dark:bg-whiteColor-dark rounded-lg shadow p-4 flex flex-col gap-2 border border-gray-200 dark:border-gray-700">
                <div class="flex justify-between items-center">
                    <h2 class="font-semibold text-[#5E5F60] dark:text-white">Tag: {{ $tag->name }}</h2>
                    <div class="relative inline-block text-left">
                        <button class="actionMenuBtn p-2 rounded-full hover:bg-gray-200 dark:hover:bg-gray-700">
                            <i class="icofont-gear text-lg text-gray-600 dark:text-gray-300"></i>
                        </button>
                        <div class="actionMenu hidden absolute right-0 top-full mt-1 w-32 bg-white dark:bg-gray-800 rounded-md shadow-lg ring-1 ring-black ring-opacity-5 z-50">
                            <div class="py-1">
                                <button class="btnupdatetag w-full px-4 py-2 text-sm text-left text-[#5F2DED] hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center gap-2">
                                    <i class="icofont-ui-edit text-sm"></i> Update
                                </button>
                                <button class="btndeleteTag w-full px-4 py-2 text-sm text-left text-[#C10007] hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center gap-2">
                                    <i class="icofont-ui-delete text-sm"></i> Delete
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-300">Category: {{ $tag->category }}</p>
                <p class="text-sm text-gray-600 dark:text-gray-300">ID: {{ $tag->id }}</p>
            </div>
        @empty
            <p class="text-center text-gray-500 py-4">No tags found.</p>
        @endforelse
    </div>

    <!-- Add Tag & Pagination -->
    <div class="flex flex-col sm:flex-row justify-between items-center mt-4 gap-4">
        <a href="{{ route('admin.addtag') }}"
            class="p-3 text-sm gap-2 px-10 flex flex-row justify-center items-center text-[#5F2DED] font-[450] border border-[#5F2DED] rounded-xl
            dark:text-[#244890] dark:border-[#244890] hover:bg-[#5F2DED] hover:text-white hover:dark:bg-[#244890] hover:dark:text-white">
            <i class="icofont-ui-add text-sm"></i>
            <p>Add Tag</p>
        </a>
        <div class="flex justify-end w-full sm:w-auto">
            {{ $tags->links() }}
        </div>
    </div>

    <!-- Modals -->
    <x-lmsmodal/>
    <div id="confirmDeleteTagModal" class="hidden">
        <div class="flex flex-col justify-center items-center w-full gap-4">
            <p class="dark:text-white">Are you sure you want to delete this tag?</p>
            <div class="flex flex-col sm:flex-row gap-4 w-full">
                <button data-close="lmsmodal" class="px-4 py-2 flex-1 border dark:text-white dark:hover:text-black border-[#ABB7C2] rounded">Cancel</button>
                <button id="confirmDeleteTag" class="px-4 py-2 flex-1 bg-red-500 hover:bg-red-300 text-white rounded">Confirm</button>
            </div>
        </div>
    </div>
</div>
