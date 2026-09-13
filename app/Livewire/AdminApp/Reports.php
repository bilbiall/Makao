<?php

namespace App\Livewire\AdminApp;

use App\Livewire\Concerns\ExportsCsv;
use App\Models\Location;
use App\Services\Reports\ArrearsReport;
use App\Services\Reports\BnbPerformanceReport;
use App\Services\Reports\FinancialSummaryReport;
use App\Services\Reports\IncomeStatementReport;
use App\Services\Reports\RentRollReport;
use App\Support\StaffPermissions;
use App\Support\StaffScope;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Five real reports behind one page (a landlord/PM only needed one nav entry,
 * not five): Overview (invoice/payment rollup - the only report that used to
 * exist), Rent Roll, Arrears aging, BnB Performance, and an Income Statement.
 * Each report's aggregation lives in App\Services\Reports so the same query
 * logic backs the on-screen view, the PDF export, and the CSV export - no
 * separate "what the screen shows" vs "what the export contains" drift.
 */
class Reports extends Component
{
    use ExportsCsv;

    #[Url]
    public string $tab = 'overview';

    public $from;
    public $to;
    public $location_id = '';
    public $tenant_search = '';
    public $invoice_status = '';

    public const TABS = [
        'overview' => 'Overview',
        'rent_roll' => 'Rent Roll',
        'arrears' => 'Arrears',
        'bnb' => 'BnB Performance',
        'income_statement' => 'Income Statement',
    ];

    /** Which StaffPermissions slug unlocks each tab for a non-admin/landlord user - see StaffRoles/StaffRoleResource, both driven by StaffPermissions::catalog(). */
    public const TAB_PERMISSIONS = [
        'overview' => StaffPermissions::VIEW_REPORT_OVERVIEW,
        'rent_roll' => StaffPermissions::VIEW_REPORT_RENT_ROLL,
        'arrears' => StaffPermissions::VIEW_REPORT_ARREARS,
        'bnb' => StaffPermissions::VIEW_REPORT_BNB_PERFORMANCE,
        'income_statement' => StaffPermissions::VIEW_REPORT_INCOME_STATEMENT,
    ];

    public function mount(): void
    {
        $allowedTabs = $this->allowedTabs();

        abort_unless(count($allowedTabs) > 0, 403);

        if (! in_array($this->tab, $allowedTabs, true)) {
            $this->tab = $allowedTabs[0];
        }

        $this->to = Carbon::now()->toDateString();
        $this->from = Carbon::now()->subMonths(5)->startOfMonth()->toDateString();
    }

    public function selectTab(string $tab): void
    {
        if (in_array($tab, $this->allowedTabs(), true)) {
            $this->tab = $tab;
        }
    }

    /** admin/landlord always see every tab; anyone else needs that specific tab's permission - granted directly, or via the legacy manager/caretaker/agent default (see StaffPermissions::defaultFor()). */
    protected function isTabAllowed(string $tab): bool
    {
        if (in_array(Auth::user()->role, ['admin', 'landlord'], true)) {
            return true;
        }

        $permission = self::TAB_PERMISSIONS[$tab] ?? null;

        return $permission && Auth::user()->hasPermission($permission);
    }

    protected function allowedTabs(): array
    {
        return array_values(array_filter(array_keys(self::TABS), fn ($tab) => $this->isTabAllowed($tab)));
    }

    protected function locationOptions()
    {
        $query = Location::query()->orderBy('location_name');

        if (StaffScope::isScopedStaff()) {
            $query->whereIn('id', StaffScope::locationIds());
        }

        return $query->get(['id', 'location_name']);
    }

    protected function buildReportData(): array
    {
        abort_unless($this->isTabAllowed($this->tab), 403);

        $locationId = $this->location_id ?: null;

        return match ($this->tab) {
            'rent_roll' => RentRollReport::build($locationId),
            'arrears' => ArrearsReport::build($locationId),
            'bnb' => BnbPerformanceReport::build($this->from, $this->to, $locationId),
            'income_statement' => IncomeStatementReport::build($this->from, $this->to),
            default => FinancialSummaryReport::build($this->from, $this->to, $locationId, $this->tenant_search ?: null, $this->invoice_status ?: null),
        };
    }

    public function exportPdf()
    {
        $data = $this->buildReportData();
        $filename = $this->tab . '-report-' . now()->format('Y-m-d') . '.pdf';

        $view = match ($this->tab) {
            'rent_roll' => 'reports.pdf.rent-roll',
            'arrears' => 'reports.pdf.arrears',
            'bnb' => 'reports.pdf.bnb-performance',
            'income_statement' => 'reports.pdf.income-statement',
            default => 'reports.pdf.overview',
        };

        return Pdf::loadView($view, [
            'data' => $data,
            'landlord' => Auth::user()->landlord?->name ?? Auth::user()->name,
            'generated_at' => now(),
        ])->download($filename);
    }

    public function exportCsv()
    {
        $data = $this->buildReportData();
        $filename = $this->tab . '-report-' . now()->format('Y-m-d') . '.csv';

        return match ($this->tab) {
            'rent_roll' => $this->streamCsv($filename, ['Property', 'Unit', 'Tenant', 'Phone', 'Rent Amount', 'Balance', 'Date Admitted'], $this->rentRollRows($data)),
            'arrears' => $this->streamCsv($filename, ['Tenant', 'Phone', 'Property', 'Unit', 'Invoice #', 'Due Date', 'Days Overdue', 'Bucket', 'Balance'], $this->arrearsRows($data)),
            'bnb' => $this->streamCsv($filename, ['Property', 'Unit', 'Views', 'Inquiries', 'Bookings', 'Revenue'], $this->bnbRows($data)),
            'income_statement' => $this->streamCsv($filename, ['Month', 'Rent Revenue', 'BnB Revenue', 'Total Revenue', 'Electricity', 'Water', 'Internet', 'Maintenance', 'Other', 'Total Expense', 'Net'], $this->incomeStatementRows($data)),
            default => $this->streamCsv($filename, ['Tenant', 'Phone', 'Property', 'Unit', 'Invoice #', 'Invoice Date', 'Amount', 'Balance', 'Status'], $this->overviewRows($data)),
        };
    }

    protected function overviewRows(array $data): array
    {
        return $data['invoices']->map(fn ($invoice) => [
            $invoice->tenant?->tenant_name ?? 'Unknown tenant',
            $invoice->tenant?->phone_number,
            $invoice->tenant?->house?->location?->location_name ?? 'Unassigned',
            $invoice->tenant?->house?->display_name ?: $invoice->tenant?->house?->house_name,
            $invoice->invoice_number,
            optional($invoice->invoice_date)?->format('Y-m-d') ?? $invoice->invoice_date,
            $invoice->amount,
            $invoice->balance,
            ucfirst($invoice->status),
        ])->all();
    }

    protected function rentRollRows(array $data): array
    {
        $rows = [];
        foreach ($data['properties'] as $property) {
            foreach ($property['tenants'] as $tenant) {
                $rows[] = [
                    $property['location_name'],
                    $tenant['unit'],
                    $tenant['tenant_name'],
                    $tenant['phone_number'],
                    $tenant['rent_amount'],
                    $tenant['balance'],
                    $tenant['date_admitted'],
                ];
            }
        }

        return $rows;
    }

    protected function arrearsRows(array $data): array
    {
        return $data['rows']->map(fn ($row) => [
            $row['tenant_name'],
            $row['phone_number'],
            $row['location_name'],
            $row['unit'],
            $row['invoice_number'],
            $row['due_date'],
            $row['days_overdue'],
            $row['bucket_label'],
            $row['balance'],
        ])->all();
    }

    protected function bnbRows(array $data): array
    {
        return $data['properties']->map(fn ($row) => [
            $row['location_name'],
            $row['house_name'],
            $row['views'],
            $row['inquiries'],
            $row['bookings'],
            $row['revenue'],
        ])->all();
    }

    protected function incomeStatementRows(array $data): array
    {
        return array_map(fn ($row) => [
            $row['month'],
            $row['rent_revenue'],
            $row['bnb_revenue'],
            $row['total_revenue'],
            $row['electricity'],
            $row['water'],
            $row['internet'],
            $row['maintenance'],
            $row['other'],
            $row['total_expense'],
            $row['net'],
        ], $data['rows']);
    }

    public function render()
    {
        return view('livewire.admin-app.reports', [
            'data' => $this->buildReportData(),
            'locations' => $this->locationOptions(),
            'allowedTabs' => $this->allowedTabs(),
        ])->layout('components.layouts.app', ['title' => 'Reports']);
    }
}
