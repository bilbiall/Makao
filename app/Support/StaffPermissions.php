<?php

namespace App\Support;

/**
 * The fixed catalog of checkbox permissions a landlord can grant to a custom
 * staff role - see StaffRoleResource / AdminApp\StaffRoles for the checkbox UI
 * built from catalog(). Deliberately NOT landlord-editable (no separate
 * permissions table) - this list is developer-defined, same as every other
 * enum-like list in this codebase (house types, amenities, etc.).
 *
 * Sensitive, never-delegable capabilities (staff/role management, Settings/
 * SMTP/SMS/payment-gateway credentials, Import Data, Activity Logs) are
 * deliberately NOT in this catalog - those stay hardcoded admin/landlord-only
 * everywhere they're already checked, untouched by this system.
 */
class StaffPermissions
{
    public const ADMIT_TENANTS = 'admit_tenants';
    public const EDIT_TENANTS = 'edit_tenants';
    public const VACATE_TENANTS = 'vacate_tenants';

    public const RECORD_PAYMENTS = 'record_payments';
    public const EDIT_PAYMENTS = 'edit_payments';
    public const DELETE_PAYMENTS = 'delete_payments';

    public const CREATE_INVOICES = 'create_invoices';
    public const EDIT_INVOICES = 'edit_invoices';
    public const DELETE_INVOICES = 'delete_invoices';
    public const SEND_MASS_INVOICES = 'send_mass_invoices';
    public const SEND_MASS_REMINDERS = 'send_mass_reminders';

    public const MANAGE_BILLS = 'manage_bills';
    public const MANAGE_PROPERTIES = 'manage_properties';

    public const RESOLVE_ISSUES = 'resolve_issues';
    public const DELETE_ISSUES = 'delete_issues';

    public const DECIDE_NOTICES = 'decide_notices';
    public const MANAGE_BOOKINGS = 'manage_bookings';
    public const ADMIT_VIEWING_REQUESTS = 'admit_viewing_requests';
    public const RESOLVE_MPESA_REVIEW = 'resolve_mpesa_review';

    public const VIEW_REPORTS = 'view_reports';
    public const MANAGE_EXPENSES = 'manage_expenses';

    /**
     * Grouped for the checkbox UI: ['Group label' => ['slug' => 'Checkbox label']].
     */
    public static function catalog(): array
    {
        return [
            'Tenants' => [
                self::ADMIT_TENANTS => 'Admit tenants',
                self::EDIT_TENANTS => 'Edit tenants',
                self::VACATE_TENANTS => 'Vacate / remove tenants',
            ],
            'Payments' => [
                self::RECORD_PAYMENTS => 'Record payments',
                self::EDIT_PAYMENTS => 'Edit payments',
                self::DELETE_PAYMENTS => 'Delete payments',
            ],
            'Invoices' => [
                self::CREATE_INVOICES => 'Create invoices',
                self::EDIT_INVOICES => 'Edit invoices',
                self::DELETE_INVOICES => 'Delete invoices',
                self::SEND_MASS_INVOICES => 'Send mass invoices',
                self::SEND_MASS_REMINDERS => 'Send mass reminders',
            ],
            'Bills' => [
                self::MANAGE_BILLS => 'Create / edit / delete bills',
            ],
            'Properties & Units' => [
                self::MANAGE_PROPERTIES => 'Create / edit / delete properties & units',
            ],
            'Issues' => [
                self::RESOLVE_ISSUES => 'Resolve issues',
                self::DELETE_ISSUES => 'Delete issues',
            ],
            'Notices to Vacate' => [
                self::DECIDE_NOTICES => 'Approve / deny notices to vacate',
            ],
            'Bookings' => [
                self::MANAGE_BOOKINGS => 'Confirm / check in / check out / cancel bookings',
            ],
            'Viewing Requests' => [
                self::ADMIT_VIEWING_REQUESTS => 'Admit viewing requests',
            ],
            'M-Pesa Review' => [
                self::RESOLVE_MPESA_REVIEW => 'Resolve unmatched M-Pesa payments',
            ],
            'Reports & Expenses' => [
                self::VIEW_REPORTS => 'View reports',
                self::MANAGE_EXPENSES => 'Manage expenses',
            ],
        ];
    }

    public static function all(): array
    {
        return array_keys(array_merge(...array_values(self::catalog())));
    }

    /**
     * What a legacy (no custom staff_role assigned) manager/caretaker/agent
     * account can already do today - the backward-compatibility baseline.
     * Manager and caretaker are already functionally identical everywhere in
     * this codebase (both just StaffScope::isScopedStaff()), so they share the
     * same default set here. Agent is denied everywhere except Bookings,
     * matching StaffScope::denyIfAgent()'s existing blanket-deny behavior.
     */
    public static function defaultFor(string $role, string $slug): bool
    {
        if (in_array($role, ['manager', 'caretaker'], true)) {
            return in_array($slug, self::all(), true);
        }

        if ($role === 'agent') {
            return $slug === self::MANAGE_BOOKINGS;
        }

        return false;
    }
}
