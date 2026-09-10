<x-filament-widgets::widget>
    <x-filament::section>
        @php $details = $this->getDetails(); @endphp
        <p class="text-sm font-semibold">How to pay</p>
        <div class="mt-2 text-sm text-gray-600 dark:text-gray-400 space-y-1">
            @if (!empty($details['bank_name']))
                <p>Bank: <span class="font-medium text-gray-800 dark:text-gray-200">{{ $details['bank_name'] }}</span></p>
            @endif
            @if (!empty($details['account_name']))
                <p>Account name: <span class="font-medium text-gray-800 dark:text-gray-200">{{ $details['account_name'] }}</span></p>
            @endif
            @if (!empty($details['account_number']))
                <p>Account number: <span class="font-medium text-gray-800 dark:text-gray-200">{{ $details['account_number'] }}</span></p>
            @endif
            @if (!empty($details['paybill_number']))
                <p>Paybill: <span class="font-medium text-gray-800 dark:text-gray-200">{{ $details['paybill_number'] }}</span></p>
            @endif
            @if (!empty($details['till_number']))
                <p>Till number: <span class="font-medium text-gray-800 dark:text-gray-200">{{ $details['till_number'] }}</span></p>
            @endif
            @if (!empty($details['instructions']))
                <p class="pt-1 text-gray-500 dark:text-gray-400">{{ $details['instructions'] }}</p>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
