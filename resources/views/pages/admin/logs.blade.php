@extends('layouts.dashboard')

@section('content')
<div class="w-full h-full min-h-0 p-4 sm:p-6 md:p-10 bg-whiteColor dark:bg-whiteColor-dark shadow-accordion dark:shadow-accordion-dark rounded-lg flex flex-col overflow-hidden">
    <div id="page-header" class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 id="page-title" class="text-2xl sm:text-3xl font-bold text-[#5E5F60] dark:text-white">
                Logs
            </h1>
        </div>
    </div>

    <!-- Filters & Search -->
    <div class="flex flex-col sm:flex-row w-full gap-4 sm:gap-0 sm:justify-between items-start sm:items-center pb-4">
        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4 sm:gap-6 w-full sm:w-auto">
            <form method="GET" action="{{ route('admin.logs') }}" class="flex flex-col sm:flex-row items-start sm:items-center gap-3 w-full sm:w-auto">
                <div class="flex items-center gap-2 w-full sm:w-auto">
                    <label class="font-medium text-[#5E5F60] dark:text-white text-sm">Module</label>
                    <select name="module" onchange="this.form.submit()"
                        class="w-full sm:w-auto border border-gray-300 dark:border-gray-50 cursor-pointer text-sm px-4 pr-8 py-2 dark:bg-bodyBg-dark dark:text-white rounded">
                        <option value="">All Modules</option>
                        @foreach ($moduleLabels as $moduleKey => $label)
                            <option value="{{ $moduleKey }}" {{ request('module') === $moduleKey ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-center gap-2 w-full sm:w-auto">
                    <label class="font-medium text-[#5E5F60] dark:text-white text-sm">Action</label>
                    <select name="action" onchange="this.form.submit()"
                        class="w-full sm:w-auto border border-gray-300 dark:border-gray-50 cursor-pointer text-sm px-4 pr-8 py-2 dark:bg-bodyBg-dark dark:text-white rounded">
                        <option value="">All Actions</option>
                        <option value="created" {{ request('action') === 'created' ? 'selected' : '' }}>Created</option>
                        <option value="updated" {{ request('action') === 'updated' ? 'selected' : '' }}>Updated</option>
                        <option value="deleted" {{ request('action') === 'deleted' ? 'selected' : '' }}>Deleted</option>
                    </select>
                </div>
                @if (request('search'))
                    <input type="hidden" name="search" value="{{ request('search') }}">
                @endif
            </form>
        </div>

        <div class="relative w-full sm:w-[30%] mt-2 sm:mt-0">
            <form method="GET" action="{{ route('admin.logs') }}">
                @if (request('module'))
                    <input type="hidden" name="module" value="{{ request('module') }}">
                @endif
                @if (request('action'))
                    <input type="hidden" name="action" value="{{ request('action') }}">
                @endif
                <div class="relative w-full">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                        <i class="icofont-search-1"></i>
                    </span>
                    <input type="text" name="search" value="{{ request('search') }}"
                        placeholder="Search by action, module, or ID..."
                        class="w-full pl-10 pr-3 py-2 rounded-xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-[#7F6994] text-sm dark:bg-bodyBg-dark dark:text-white dark:focus:ring-blue">
                </div>
            </form>
        </div>
    </div>

    <div class="flex-1 min-h-0 overflow-x-auto overflow-y-auto border border-gray-200 dark:border-gray-700 rounded-lg">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 dark:bg-whiteColor-dark border-b border-gray-200 dark:border-gray-700">
                <tr>
                    <th class="text-left py-3 px-4 font-semibold text-[#5E5F60] dark:text-gray-300">ID</th>
                    <th class="text-left py-3 px-4 font-semibold text-[#5E5F60] dark:text-gray-300">Module</th>
                    <th class="text-left py-3 px-4 font-semibold text-[#5E5F60] dark:text-gray-300">Action</th>
                    <th class="text-left py-3 px-4 font-semibold text-[#5E5F60] dark:text-gray-300">Done By</th>
                    <th class="text-left py-3 px-4 font-semibold text-[#5E5F60] dark:text-gray-300">Date</th>
                    <th class="text-left py-3 px-4 font-semibold text-[#5E5F60] dark:text-gray-300">Time</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($activities as $activity)
                    @php
                        $actor = $activity->causer?->name
                            ?? data_get($activity->properties, 'username', 'System');
                        $event = $activity->event ?? $activity->description;
                        $moduleKey = $activity->subject_type ? class_basename($activity->subject_type) : null;
                        $moduleOverride = data_get($activity->properties, 'module');
                        $module = $moduleOverride ?: ($moduleKey ? ($moduleLabels[$moduleKey] ?? $moduleKey) : 'System');
                        $targetId = $activity->subject_id ?? '-';
                    @endphp
                    <tr class="border-t border-gray-200 dark:border-gray-700 text-[#373737] dark:text-white">
                        <td class="py-3 px-4">{{ $targetId }}</td>
                        <td class="py-3 px-4">{{ $module }}</td>
                        <td class="py-3 px-4 capitalize">{{ $event }}</td>
                        <td class="py-3 px-4">{{ $actor }}</td>
                        <td class="py-3 px-4">{{ optional($activity->created_at)->format('M d, Y') }}</td>
                        <td class="py-3 px-4">{{ optional($activity->created_at)->format('h:i A') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-6 px-4 text-center text-[#5E5F60] dark:text-gray-300">
                            No admin activity recorded yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6 flex justify-end">
        {{ $activities->links('pagination.logs') }}
    </div>
</div>
@endsection
