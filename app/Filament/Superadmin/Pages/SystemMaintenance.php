<?php

namespace App\Filament\Superadmin\Pages;

use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * Lets the platform operator run pending migrations and known seeders from the
 * browser - built for a shared-hosting deploy with no easy SSH/artisan access,
 * not as a replacement for one where a real terminal is available.
 *
 * Deliberately narrow: only `migrate` (never migrate:fresh/rollback/reset -
 * those are actually destructive, this isn't a place for that) and only a
 * fixed whitelist of seeder classes (never an arbitrary class name typed in -
 * that would let this page instantiate anything on the filesystem).
 */
class SystemMaintenance extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';
    protected static ?string $navigationLabel = 'System Maintenance';
    protected static ?string $slug = 'system-maintenance';
    protected static ?string $title = 'System Maintenance';

    protected static string $view = 'filament.superadmin.pages.system-maintenance';

    public const SEEDERS = [
        'Database\\Seeders\\KenyaLocationsSeeder' => [
            'label' => 'Kenya locations (cities & areas)',
            'description' => 'Reference data for the location picker/search. Idempotent - safe to run anytime, never touches a landlord\'s own data.',
        ],
        'Database\\Seeders\\DemoNairobiSeeder' => [
            'label' => 'Demo Nairobi dataset',
            'description' => 'Sample landlords, tenants, listings and bookings for sales demos. Idempotent and scoped to @rentydemo.co.ke / @example.test accounts only - never touches a real landlord\'s account.',
        ],
    ];

    public int $pendingCount = 0;

    /** @var array<int, string> */
    public array $pendingNames = [];

    public ?string $lastAction = null;
    public ?string $lastOutput = null;

    public function mount(): void
    {
        $this->refreshPendingMigrations();
    }

    protected function refreshPendingMigrations(): void
    {
        $ran = DB::table('migrations')->pluck('migration')->all();

        $files = collect(File::files(database_path('migrations')))
            ->map(fn ($file) => basename($file->getFilename(), '.php'))
            ->sort()
            ->values();

        $pending = $files->diff($ran)->values();

        $this->pendingCount = $pending->count();
        $this->pendingNames = $pending->all();
    }

    public function runMigrations(): void
    {
        Artisan::call('migrate', ['--force' => true]);

        $this->lastAction = 'Migrations';
        $this->lastOutput = Artisan::output();
        $this->refreshPendingMigrations();

        Notification::make()
            ->success()
            ->title($this->pendingCount === 0 ? 'Migrations complete - fully up to date' : 'Migrations ran, but some are still pending - check the output below')
            ->send();
    }

    public function runSeeder(string $class): void
    {
        abort_unless(array_key_exists($class, self::SEEDERS), 403);

        Artisan::call('db:seed', ['--class' => $class, '--force' => true]);

        $this->lastAction = self::SEEDERS[$class]['label'];
        $this->lastOutput = Artisan::output();

        Notification::make()
            ->success()
            ->title(self::SEEDERS[$class]['label'] . ' finished')
            ->send();
    }
}
