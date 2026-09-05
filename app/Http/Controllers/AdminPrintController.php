<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Tenant;
use App\Support\StaffScope;
use Illuminate\Http\Request;

/**
 * Plain, standalone printable pages for the app-shell's Invoices/Payments/
 * Tenants lists - opened in a new tab so the browser's native print/Save-as-
 * PDF dialog handles output. (The existing Reports::exportPdf() convention
 * downloads HTML content under a .pdf filename via streamDownload, but its
 * target view - filament.reports.pdf - doesn't exist in this codebase, so
 * that button already throws; not a pattern worth repeating here.)
 */
class AdminPrintController extends Controller
{
    public function invoices(Request $request)
    {
        $query = StaffScope::onTenantChild(Invoice::query())->with('tenant')->latest();

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($search = $request->query('search')) {
            $term = '%' . $search . '%';
            $query->where(function ($q) use ($term) {
                $q->where('invoice_number', 'like', $term)
                    ->orWhereHas('tenant', fn ($t) => $t->where('tenant_name', 'like', $term)->orWhere('phone_number', 'like', $term));
            });
        }

        return view('print.invoices', ['invoices' => $query->get()]);
    }

    public function payments(Request $request)
    {
        $query = StaffScope::onTenantChild(Payment::query())->with(['tenant', 'invoice'])->latest();

        if ($search = $request->query('search')) {
            $term = '%' . $search . '%';
            $query->where(function ($q) use ($term) {
                $q->where('payment_reference', 'like', $term)
                    ->orWhereHas('tenant', fn ($t) => $t->where('tenant_name', 'like', $term)->orWhere('phone_number', 'like', $term));
            });
        }

        return view('print.payments', ['payments' => $query->get()]);
    }

    public function tenants(Request $request)
    {
        $query = StaffScope::onTenant(Tenant::query())->with('house')->orderBy('tenant_name');

        if ($search = $request->query('search')) {
            $term = '%' . $search . '%';
            $query->where(function ($q) use ($term) {
                $q->where('tenant_name', 'like', $term)
                    ->orWhere('phone_number', 'like', $term)
                    ->orWhereHas('house', fn ($h) => $h->where('house_name', 'like', $term));
            });
        }

        return view('print.tenants', ['tenants' => $query->get()]);
    }
}
