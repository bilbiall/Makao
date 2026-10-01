<x-filament-widgets::widget>
    <x-filament::section heading="Unmet demand (last 30 days)" description="Searches where the assistant had no homes to show. Landlords adding stock here would meet real demand.">
        @php($rows = $this->getRows())

        @if (empty($rows))
            <p class="text-sm text-gray-500 dark:text-gray-400">No unmet searches yet.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="text-xs uppercase text-gray-500 dark:text-gray-400">
                        <tr>
                            <th class="py-2 pr-4">Unit type</th>
                            <th class="py-2 pr-4">Where</th>
                            <th class="py-2 pr-4">Searches</th>
                            <th class="py-2">Typical budget</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/10">
                        @foreach ($rows as $row)
                            <tr>
                                <td class="py-2 pr-4 font-medium">{{ $row['type'] }}</td>
                                <td class="py-2 pr-4">{{ $row['place'] }}</td>
                                <td class="py-2 pr-4">{{ $row['searches'] }}</td>
                                <td class="py-2">{{ $row['typical_budget'] ? 'KES '.number_format($row['typical_budget']) : '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
