<x-filament::page>
    <x-filament::section>
        <x-slot name="heading">Migrations</x-slot>
        <x-slot name="description">
            Runs `php artisan migrate --force` - applies any migration file already deployed to this server that hasn't run yet. Never drops or resets anything (that's deliberately not offered here) - back up your database first regardless, the same as you would before running this from a terminal.
        </x-slot>

        <div class="flex items-center justify-between gap-4">
            <div>
                @if ($pendingCount === 0)
                    <span class="inline-flex items-center gap-1.5 text-sm font-medium text-success-600 dark:text-success-400">
                        @svg('heroicon-o-check-circle', 'w-5 h-5')
                        Up to date - no pending migrations.
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 text-sm font-medium text-warning-600 dark:text-warning-400">
                        @svg('heroicon-o-exclamation-triangle', 'w-5 h-5')
                        {{ $pendingCount }} pending {{ \Illuminate\Support\Str::plural('migration', $pendingCount) }}
                    </span>
                    <ul class="mt-2 ml-1 list-disc list-inside text-xs text-gray-500 dark:text-gray-400 space-y-0.5">
                        @foreach ($pendingNames as $name)
                            <li>{{ $name }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <x-filament::button
                wire:click="runMigrations"
                wire:confirm="Run pending migrations now? Make sure the database is backed up first."
                wire:loading.attr="disabled"
                wire:target="runMigrations"
                :disabled="$pendingCount === 0"
                color="warning"
            >
                Run migrations
            </x-filament::button>
        </div>
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">Seed data</x-slot>
        <x-slot name="description">
            Each of these is safe to re-run - idempotent, and scoped to only its own data (never a real landlord's account).
        </x-slot>

        <div class="space-y-4">
            @foreach (\App\Filament\Superadmin\Pages\SystemMaintenance::SEEDERS as $class => $meta)
                <div class="flex items-center justify-between gap-4 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $meta['label'] }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $meta['description'] }}</p>
                    </div>
                    @php $jsClass = addslashes($class); @endphp
                    <x-filament::button
                        wire:click="runSeeder('{{ $jsClass }}')"
                        wire:confirm="Run the '{{ $meta['label'] }}' seeder now?"
                        wire:loading.attr="disabled"
                        wire:target="runSeeder('{{ $jsClass }}')"
                        color="gray"
                        class="shrink-0"
                    >
                        Run
                    </x-filament::button>
                </div>
            @endforeach
        </div>
    </x-filament::section>

    @if ($lastOutput !== null)
        <x-filament::section>
            <x-slot name="heading">Last run: {{ $lastAction }}</x-slot>

            <pre class="max-h-96 overflow-y-auto whitespace-pre-wrap rounded-lg bg-gray-950 p-4 text-xs text-gray-100">{{ $lastOutput }}</pre>
        </x-filament::section>
    @endif
</x-filament::page>
