<x-filament::page>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="dark:bg-gray-900 bg-white p-4 shadow rounded">
            <h2 class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Landlords</h2>
            <p class="text-2xl font-semibold dark:text-gray-100">{{ $totalLandlords }}</p>
        </div>

        <div class="dark:bg-gray-900 bg-white p-4 shadow rounded">
            <h2 class="text-sm font-medium text-gray-500 dark:text-gray-400">Trialing</h2>
            <p class="text-2xl font-semibold text-blue-600">{{ $trialingCount }}</p>
        </div>

        <div class="dark:bg-gray-900 bg-white p-4 shadow rounded">
            <h2 class="text-sm font-medium text-gray-500 dark:text-gray-400">Active Subscriptions</h2>
            <p class="text-2xl font-semibold text-green-600">{{ $activeCount }}</p>
        </div>

        <div class="dark:bg-gray-900 bg-white p-4 shadow rounded">
            <h2 class="text-sm font-medium text-gray-500 dark:text-gray-400">Expiring in 7 Days</h2>
            <p class="text-2xl font-semibold text-amber-600">{{ $expiringSoonCount }}</p>
        </div>
    </div>

    <div class="mt-4 dark:bg-gray-900 bg-white p-4 shadow rounded">
        <h2 class="text-sm font-medium text-gray-500 dark:text-gray-400">Estimated MRR</h2>
        <p class="text-2xl font-semibold dark:text-gray-100">KES {{ number_format($roughMrr, 2) }}</p>
        <p class="text-xs text-gray-400 mt-1">Sum of active subscriptions' package price, normalized to monthly.</p>
    </div>
</x-filament::page>
