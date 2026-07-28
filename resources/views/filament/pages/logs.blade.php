<div>
<x-filament::page>
    <div class="space-y-6">
        <div class="bg-white dark:bg-gray-900 shadow rounded p-4">
            <h3 class="font-semibold mb-3 dark:text-gray-100">Activity Logs</h3>

            <form method="GET" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                <div>
                    <label class="block text-sm text-gray-500 dark:text-gray-300">Action</label>
                    <select name="log_action" class="mt-1 block w-full border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded px-2 py-1">
                        <option value="">All</option>
                        @foreach($actionsList as $key => $label)
                            <option value="{{ $key }}" @if(request()->query('log_action') == $key) selected @endif>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm text-gray-500 dark:text-gray-300">Date From</label>
                    <input type="date" name="log_from" value="{{ request()->query('log_from') }}" class="mt-1 block w-full border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded px-2 py-1" />
                </div>

                <div>
                    <label class="block text-sm text-gray-500 dark:text-gray-300">Date To</label>
                    <input type="date" name="log_to" value="{{ request()->query('log_to') }}" class="mt-1 block w-full border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded px-2 py-1" />
                </div>

                <div>
                    <label class="block text-sm text-gray-500 dark:text-gray-300">Search</label>
                    <input type="search" name="log_search" placeholder="Search user or details" value="{{ request()->query('log_search') }}" class="mt-1 block w-full border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded px-2 py-1" />
                </div>

                <div class="md:col-span-2 lg:col-span-4 flex gap-2">
                    <button type="submit" class="inline-flex items-center px-3 py-1 bg-blue-600 hover:bg-blue-700 text-white rounded">Filter</button>
                    <a href="{{ request()->url() }}" class="inline-flex items-center px-3 py-1 bg-gray-200 dark:bg-gray-800 text-gray-700 dark:text-gray-200 rounded">Reset</a>
                </div>
            </form>

            <div class="divide-y divide-gray-200 dark:divide-gray-700 max-h-[60vh] overflow-y-auto">
                @forelse($logs as $log)
                    <div class="p-3">
                        <div class="flex items-center justify-between">
                            <div class="text-sm text-gray-600 dark:text-gray-300">{{ $actionsList[$log->action] ?? ucfirst($log->action) }} — <span class="font-medium text-gray-800 dark:text-gray-100">{{ optional($log->user)->tenant_name ?? optional($log->user)->name ?? 'System' }}</span></div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $log->created_at->format('d M Y H:i') }}</div>
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ $log->ip }}</div>
                        @if($log->details)
                            <div class="mt-2 text-sm text-gray-700 dark:text-gray-200">{{ $log->details }}</div>
                        @endif
                    </div>
                @empty
                    <div class="p-4 text-sm text-gray-500">No logs found.</div>
                @endforelse
            </div>
        </div>
    </div>
</x-filament::page>
</div>
