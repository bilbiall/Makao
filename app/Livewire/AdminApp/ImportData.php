<?php

namespace App\Livewire\AdminApp;

use App\Filament\Imports\ExpenseImporter;
use App\Filament\Imports\HouseImporter;
use App\Filament\Imports\InvoiceImporter;
use App\Filament\Imports\LocationImporter;
use App\Filament\Imports\PaymentImporter;
use App\Filament\Imports\TenantImporter;
use Filament\Actions\Imports\Exceptions\RowImportFailedException;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use League\Csv\Reader;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * App-shell counterpart to the Filament "Import Data" page (App\Filament\Pages\ImportData) -
 * same six Importer classes underneath (so a template downloaded from either UI is
 * interchangeable and both UIs write identical data), just a plain upload form
 * instead of Filament's modal/queue machinery, since this layout doesn't load
 * Filament's own assets. Runs inline (no queue worker in this deployment) and each
 * row through the same ImportContext suppression the Filament page relies on.
 */
class ImportData extends Component
{
    use WithFileUploads;

    public string $activeKey = 'locations';

    public $file = null;

    /** @var array{label: string, total: int, successful: int, failures: array<string>, failuresCount: int}|null */
    public ?array $lastResult = null;

    public function canManageImports(): bool
    {
        return in_array(Auth::user()->role, ['admin', 'landlord']);
    }

    /** @return array<string, array{label: string, class: class-string}> */
    protected function importers(): array
    {
        return [
            'locations' => ['label' => 'Properties', 'class' => LocationImporter::class],
            'houses' => ['label' => 'Units', 'class' => HouseImporter::class],
            'tenants' => ['label' => 'Tenants', 'class' => TenantImporter::class],
            'invoices' => ['label' => 'Invoices', 'class' => InvoiceImporter::class],
            'payments' => ['label' => 'Payments', 'class' => PaymentImporter::class],
            'expenses' => ['label' => 'Expenses', 'class' => ExpenseImporter::class],
        ];
    }

    public function selectImporter(string $key): void
    {
        abort_unless(array_key_exists($key, $this->importers()), 404);

        $this->activeKey = $key;
        $this->lastResult = null;
        $this->file = null;
        $this->resetErrorBag();
    }

    public function downloadTemplate(string $key)
    {
        abort_unless($this->canManageImports(), 403);
        $importer = $this->importers()[$key]['class'] ?? null;
        abort_unless($importer, 404);

        // Same column set the Filament page's "Download example CSV" produces
        // (ImportColumn::getExampleHeader() defaults to the raw column name), so a
        // template from either UI has identical headers.
        $headers = array_map(
            fn (ImportColumn $column): string => $column->getExampleHeader(),
            $importer::getColumns(),
        );

        $csv = implode(',', $headers) . "\r\n";

        return response()->streamDownload(
            fn () => print($csv),
            "{$key}-import-template.csv",
            ['Content-Type' => 'text/csv'],
        );
    }

    public function import(string $key): void
    {
        abort_unless($this->canManageImports(), 403);
        $importerClass = $this->importers()[$key]['class'] ?? null;
        abort_unless($importerClass, 404);

        $this->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'max:10240']]);

        $columnNames = array_map(fn (ImportColumn $column) => $column->getName(), $importerClass::getColumns());

        $csv = Reader::createFromPath($this->file->getRealPath());
        $csv->setHeaderOffset(0);
        $csvHeader = $csv->getHeader();

        // Identity-map our own column names onto whatever the uploaded file's header
        // row actually says (case/whitespace-insensitive) - works for a template
        // downloaded from either UI, or a hand-edited CSV that kept our column names.
        $columnMap = [];
        foreach ($columnNames as $name) {
            $match = collect($csvHeader)->first(fn ($header) => strtolower(trim($header)) === strtolower($name));

            if ($match !== null) {
                $columnMap[$name] = $match;
            }
        }

        $import = Import::create([
            'file_name' => $this->file->getClientOriginalName(),
            'file_path' => $this->file->getClientOriginalName(),
            'importer' => $importerClass,
            'total_rows' => 0,
            'user_id' => Auth::id(),
        ]);

        $total = 0;
        $successful = 0;
        $failures = [];

        foreach ($csv->getRecords() as $row) {
            $total++;
            $importer = new $importerClass($import, $columnMap, []);

            try {
                DB::transaction(fn () => $importer($row));
                $successful++;
            } catch (RowImportFailedException $e) {
                $failures[] = "Row {$total}: {$e->getMessage()}";
            } catch (ValidationException $e) {
                $failures[] = "Row {$total}: " . collect($e->errors())->flatten()->implode(' ');
            } catch (\Throwable $e) {
                $failures[] = "Row {$total}: " . $e->getMessage();
            }
        }

        $import->update([
            'total_rows' => $total,
            'processed_rows' => $total,
            'successful_rows' => $successful,
            'completed_at' => now(),
        ]);

        $this->lastResult = [
            'label' => $this->importers()[$key]['label'],
            'total' => $total,
            'successful' => $successful,
            'failures' => array_slice($failures, 0, 20),
            'failuresCount' => count($failures),
        ];

        $this->file = null;
    }

    public function render()
    {
        abort_unless($this->canManageImports(), 403);

        return view('livewire.admin-app.import-data', ['importers' => $this->importers()])
            ->layout('components.layouts.app', ['title' => 'Import Data']);
    }
}
