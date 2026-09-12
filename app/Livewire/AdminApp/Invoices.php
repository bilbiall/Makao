<?php

namespace App\Livewire\AdminApp;

use App\Livewire\Concerns\ExportsCsv;
use App\Models\Bill;
use App\Models\Invoice;
use App\Models\Location;
use App\Models\Tenant;
use App\Support\StaffPermissions;
use App\Support\StaffScope;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class Invoices extends Component
{
    use WithPagination;
    use ExportsCsv;

    public string $statusFilter = '';

    public string $search = '';

    public string $monthFilter = '';

    public string $locationFilter = '';

    // Which invoice's detail popup is open, if any.
    public ?int $selectedInvoiceId = null;

    // Create/edit form state.
    public bool $showForm = false;
    public ?int $editingId = null;
    public $tenant_id = '';
    public string $invoice_date = '';
    public string $due_date = '';
    public $amount = '';
    public string $comment = '';

    // Read-only, auto-computed helper figures shown on the create form.
    public $rent_only = 0;
    public $bill_only = 0;
    public $previous_balance = 0;

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingMonthFilter(): void
    {
        $this->resetPage();
    }

    public function updatingLocationFilter(): void
    {
        $this->resetPage();
    }

    public function viewInvoice(int $invoiceId): void
    {
        $this->selectedInvoiceId = $invoiceId;
    }

    public function closeInvoiceModal(): void
    {
        $this->selectedInvoiceId = null;
    }

    public function getSelectedInvoiceProperty(): ?Invoice
    {
        if (!$this->selectedInvoiceId) {
            return null;
        }

        // Re-scoped the same way as the list below - a crafted selectedInvoiceId
        // must not leak an invoice outside this staff member's assigned properties.
        return StaffScope::onTenantChild(Invoice::query())
            ->with([
                'tenant',
                'payments' => fn ($q) => $q->latest('payment_date'),
            ])
            ->find($this->selectedInvoiceId);
    }

    protected function rules(): array
    {
        return [
            'tenant_id' => 'required|exists:tenants,id',
            'invoice_date' => 'required|date',
            'due_date' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'comment' => 'nullable|string',
        ];
    }

    public function startCreate(): void
    {
        $this->reset(['editingId', 'tenant_id', 'amount', 'comment', 'rent_only', 'bill_only', 'previous_balance']);
        $this->invoice_date = now()->format('Y-m-d');
        $this->due_date = now()->addDays(10)->format('Y-m-d');
        $this->showForm = true;
    }

    public function startEdit(int $invoiceId): void
    {
        $invoice = StaffScope::onTenantChild(Invoice::query())->findOrFail($invoiceId);

        $this->editingId = $invoice->id;
        $this->tenant_id = $invoice->tenant_id;
        $this->invoice_date = optional($invoice->invoice_date)->format('Y-m-d') ?? now()->format('Y-m-d');
        $this->due_date = optional($invoice->due_date)->format('Y-m-d') ?? now()->format('Y-m-d');
        $this->amount = $invoice->amount;
        $this->comment = $invoice->comment ?? '';
        $this->showForm = true;
    }

    public function cancelForm(): void
    {
        $this->reset(['showForm', 'editingId', 'tenant_id', 'amount', 'comment', 'rent_only', 'bill_only', 'previous_balance']);
    }

    /**
     * Livewire's equivalent of Filament's afterStateUpdated on the tenant
     * select: auto-fills the expected amount from the tenant's rent + this
     * month's bills, less whatever balance they're already carrying.
     */
    public function updatedTenantId(): void
    {
        if ($this->editingId) {
            // Editing an existing invoice never re-derives the amount from the
            // tenant - only a brand-new invoice auto-computes it.
            return;
        }

        $this->recomputeAmounts();
    }

    /**
     * Bills are month-specific (see recomputeAmounts()), so changing the
     * invoice date after a tenant is already picked - backdating it, or
     * moving it into next month - must re-pull that month's bill total too,
     * not leave the breakdown silently describing whatever month it was
     * originally computed for.
     */
    public function updatedInvoiceDate(): void
    {
        if ($this->editingId || !$this->tenant_id) {
            return;
        }

        $this->recomputeAmounts();
    }

    protected function recomputeAmounts(): void
    {
        $tenant = StaffScope::onTenant(Tenant::query())->with('house')->find($this->tenant_id);

        if (!$tenant) {
            $this->rent_only = 0;
            $this->bill_only = 0;
            $this->previous_balance = 0;
            $this->amount = '';

            return;
        }

        $this->rent_only = $tenant->house->rent_amount ?? 0;

        $period = $this->invoice_date ? \Carbon\Carbon::parse($this->invoice_date) : now();
        $this->bill_only = $tenant->bills()
            ->whereMonth('bill_month', $period->month)
            ->whereYear('bill_month', $period->year)
            ->get()
            ->sum(fn (Bill $bill) => $bill->water + $bill->electricity + $bill->trash + $bill->internet);

        // Informational only, shown next to the amount below - NOT subtracted from
        // it. Folding a carried balance into this invoice's own amount would double
        // count it against TenantObserver's sum(invoices)-sum(payments) math (that
        // old balance already lives on whichever invoice it came from). Staff can
        // see this figure and judge for themselves whether to hand-adjust the amount.
        $this->previous_balance = $tenant->accountBalance();

        $this->amount = $this->rent_only + $this->bill_only;
    }

    /**
     * "September 2026" style label shown next to the Rent/Bills breakdown, so
     * it's obvious at a glance which month this invoice (and its auto-filled
     * bill total) actually covers - especially important when backdating or
     * postdating an invoice, where that's easy to lose track of otherwise.
     */
    public function getInvoicePeriodLabelProperty(): string
    {
        try {
            return \Carbon\Carbon::parse($this->invoice_date ?: now())->format('F Y');
        } catch (\Throwable $e) {
            return now()->format('F Y');
        }
    }

    public function save(): void
    {
        $slug = $this->editingId ? StaffPermissions::EDIT_INVOICES : StaffPermissions::CREATE_INVOICES;
        abort_unless(Auth::user()->hasPermission($slug), 403);

        $this->validate();

        if ($this->editingId) {
            StaffScope::onTenantChild(Invoice::query())->findOrFail($this->editingId)->update([
                'invoice_date' => $this->invoice_date,
                'due_date' => $this->due_date,
                'amount' => $this->amount,
                'comment' => $this->comment ?: null,
            ]);
            session()->flash('invoice-saved', 'Invoice updated.');
        } else {
            $period = \Carbon\Carbon::parse($this->invoice_date);

            if (Invoice::existsForTenantInMonth((int) $this->tenant_id, $period)) {
                $this->addError('tenant_id', 'This tenant already has an invoice dated in ' . $period->format('F Y') . '.');

                return;
            }

            // Invoice::booted()'s `created` hook sends the SMS/notifications/
            // activity log automatically - don't duplicate that here.
            Invoice::create([
                'tenant_id' => $this->tenant_id,
                'invoice_date' => $this->invoice_date,
                'due_date' => $this->due_date,
                'amount' => $this->amount,
                'comment' => $this->comment ?: null,
                'status' => 'unpaid',
            ]);
            session()->flash('invoice-saved', 'Invoice created.');
        }

        $this->cancelForm();
    }

    public function delete(int $invoiceId): void
    {
        abort_unless(Auth::user()->hasPermission(StaffPermissions::DELETE_INVOICES), 403);

        StaffScope::onTenantChild(Invoice::query())->findOrFail($invoiceId)->delete();
        session()->flash('invoice-saved', 'Invoice deleted.');
    }

    /**
     * Mirrors ListInvoices' "Send Mass Invoices" Filament action, but stays
     * StaffScope-scoped - the Filament version loops Tenant::all() unscoped,
     * which would leak every landlord's tenants to a caretaker/manager here.
     */
    public function sendMassInvoices(): void
    {
        abort_unless(Auth::user()->hasPermission(StaffPermissions::SEND_MASS_INVOICES), 403);

        $tenants = StaffScope::onTenant(Tenant::query())->with('house')->get();
        $today = now();
        $count = 0;

        foreach ($tenants as $tenant) {
            if (!$tenant->house) {
                continue;
            }

            if (Invoice::existsForTenantInMonth($tenant->id, $today)) {
                continue;
            }

            $rent = $tenant->house->rent_amount ?? 0;
            $billTotal = $tenant->bills()
                ->whereMonth('bill_month', $today->month)
                ->whereYear('bill_month', $today->year)
                ->get()
                ->sum(fn (Bill $bill) => $bill->water + $bill->electricity + $bill->trash + $bill->internet);

            // Matches Filament's own "Send Mass Invoices" formula exactly - unlike
            // the single-invoice create form above, mass generation does NOT net
            // off the tenant's carried balance (that's tracked separately via each
            // invoice's own running balance, not folded into a new invoice's amount).
            $total = $rent + $billTotal;

            Invoice::create([
                'tenant_id' => $tenant->id,
                'invoice_date' => $today,
                'due_date' => $today->copy()->addDays(10),
                'amount' => $total,
                'comment' => 'Mass-generated invoice',
                'status' => 'unpaid',
            ]);

            $count++;
        }

        session()->flash('invoice-saved', "Mass invoice sent to {$count} tenant" . ($count === 1 ? '' : 's') . '.');
    }

    /**
     * Mirrors ListInvoices' "Send Mass Reminders" Filament action, scoped the
     * same way (the Filament version loops Invoice::where('balance', '>', 0)
     * unscoped).
     */
    public function sendMassReminders(): void
    {
        abort_unless(Auth::user()->hasPermission(StaffPermissions::SEND_MASS_REMINDERS), 403);

        $today = now();
        $invoices = StaffScope::onTenantChild(Invoice::where('balance', '>', 0))->with('tenant.house.location')->get();
        $count = 0;

        foreach ($invoices as $invoice) {
            $tenant = $invoice->tenant;
            if (!$tenant) {
                continue;
            }

            // Already reminded this calendar month - don't re-send just because
            // the button was clicked again.
            if ($invoice->last_reminded_at && $invoice->last_reminded_at->isSameMonth($today)) {
                continue;
            }

            $message = \App\Helpers\SmsTemplateHelper::render('template_mass_reminder', [
                'tenant_name' => $tenant->tenant_name,
                'invoice_number' => $invoice->invoice_number,
                'amount' => number_format($invoice->balance),
                'due_date' => optional($invoice->due_date)->format('d/m/Y'),
                'property_name' => $tenant->house?->location?->location_name ?? '',
            ], $invoice->landlord_id);

            try {
                \App\Helpers\SmsHelper::sendSms($tenant->phone_number, $message, $invoice->landlord_id);
                $invoice->update(['last_reminded_at' => $today]);
                $count++;
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Mass reminder SMS failed', [
                    'invoice_id' => $invoice->id, 'landlord_id' => $invoice->landlord_id, 'error' => $e->getMessage(),
                ]);
            }

            if ($tenant->email) {
                try {
                    $emailBody = \App\Helpers\EmailTemplateHelper::render('mass_reminder', [
                        'tenant_name' => $tenant->tenant_name,
                        'invoice_number' => $invoice->invoice_number,
                        'amount' => number_format($invoice->balance),
                        'due_date' => optional($invoice->due_date)->format('d M Y'),
                        'property_name' => $tenant->house?->location?->location_name ?? '',
                    ], $invoice->landlord_id);

                    \App\Helpers\EmailHelper::send($tenant->email, "Payment reminder - Invoice {$invoice->invoice_number}", $emailBody, $invoice->landlord_id);
                    $invoice->update(['last_reminded_at' => $today]);
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('Mass reminder email failed', [
                        'invoice_id' => $invoice->id, 'landlord_id' => $invoice->landlord_id, 'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        session()->flash('invoice-saved', "Reminder sent to {$count} tenant" . ($count === 1 ? '' : 's') . '.');
    }

    protected function filteredQuery()
    {
        $query = StaffScope::onTenantChild(Invoice::query())->with('tenant')->latest();

        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        if ($this->monthFilter) {
            $month = \Carbon\Carbon::parse($this->monthFilter);
            $query->whereMonth('invoice_date', $month->month)->whereYear('invoice_date', $month->year);
        }

        if ($this->locationFilter) {
            $locationId = $this->locationFilter;
            $query->whereHas('tenant.house.location', fn ($q) => $q->where('id', $locationId));
        }

        if ($this->search) {
            $term = '%' . $this->search . '%';
            $query->where(function ($q) use ($term) {
                $q->where('invoice_number', 'like', $term)
                    ->orWhereHas('tenant', fn ($t) => $t->where('tenant_name', 'like', $term)->orWhere('phone_number', 'like', $term));
            });
        }

        return $query;
    }

    protected function visibleTenants()
    {
        return StaffScope::onTenant(Tenant::query())->orderBy('tenant_name')->get();
    }

    protected function visibleLocations()
    {
        $query = Location::query()->orderBy('location_name');

        if (StaffScope::isScopedStaff()) {
            $query->whereIn('id', StaffScope::locationIds());
        }

        return $query->get();
    }

    public function export()
    {
        $invoices = $this->filteredQuery()->get();

        return $this->streamCsv(
            'invoices.csv',
            ['Invoice #', 'Tenant', 'Phone', 'Amount', 'Balance', 'Status', 'Invoice Date', 'Due Date'],
            $invoices->map(fn (Invoice $invoice) => [
                $invoice->invoice_number,
                $invoice->tenant?->tenant_name,
                $invoice->tenant?->phone_number,
                $invoice->amount,
                $invoice->balance,
                $invoice->status,
                optional($invoice->invoice_date)->format('Y-m-d'),
                optional($invoice->due_date)->format('Y-m-d'),
            ])
        );
    }

    public function render()
    {
        $invoices = $this->filteredQuery()->paginate(10);

        // Stats reflect the full scoped invoice set, not just the current
        // status filter/search/page - the "big picture" summary at the top.
        $statsQuery = StaffScope::onTenantChild(Invoice::query());
        $totalInvoiced = (float) (clone $statsQuery)->sum('amount');
        $totalOutstanding = (float) (clone $statsQuery)->sum('balance');
        $totalPaid = $totalInvoiced - $totalOutstanding;

        $statusCounts = (clone $statsQuery)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $trendLabels = [];
        $trendInvoiced = [];
        $trendPaid = [];

        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $trendLabels[] = $month->format('M');

            $monthAmount = (float) (clone $statsQuery)
                ->whereMonth('invoice_date', $month->month)
                ->whereYear('invoice_date', $month->year)
                ->sum('amount');
            $monthBalance = (float) (clone $statsQuery)
                ->whereMonth('invoice_date', $month->month)
                ->whereYear('invoice_date', $month->year)
                ->sum('balance');

            $trendInvoiced[] = $monthAmount;
            $trendPaid[] = $monthAmount - $monthBalance;
        }

        return view('livewire.admin-app.invoices', [
            'invoices' => $invoices,
            'totalInvoiced' => $totalInvoiced,
            'totalPaid' => $totalPaid,
            'totalOutstanding' => $totalOutstanding,
            'paidCount' => (int) ($statusCounts['paid'] ?? 0),
            'partialCount' => (int) ($statusCounts['partial'] ?? 0),
            'unpaidCount' => (int) ($statusCounts['unpaid'] ?? 0),
            'trendLabels' => $trendLabels,
            'trendInvoiced' => $trendInvoiced,
            'trendPaid' => $trendPaid,
            'tenants' => $this->visibleTenants(),
            'locations' => $this->visibleLocations(),
        ])->layout('components.layouts.app', ['title' => 'Invoices']);
    }
}
