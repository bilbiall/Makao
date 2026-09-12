<?php

namespace App\Support;

class AppNavigation
{
    /**
     * Full nav item list for a role, in display order. Each item:
     *  - label, icon (heroicon name), route (named route), tab (true = shown in the
     *    mobile bottom bar, false = only in the desktop sidebar and the mobile "More" sheet).
     * Bottom tab bars work best around 4-5 items, so only the most-used screens per
     * role are flagged `tab: true` - everything else still gets a sidebar link on
     * desktop and lives under the "More" tab on mobile.
     */
    public static function forRole(string $role): array
    {
        return match ($role) {
            'tenant' => [
                ['label' => 'Home', 'icon' => 'heroicon-o-home', 'route' => 'app.tenant.dashboard', 'tab' => true],
                ['label' => 'Invoices', 'icon' => 'heroicon-o-credit-card', 'route' => 'app.tenant.invoices', 'tab' => true],
                ['label' => 'Bills', 'icon' => 'heroicon-o-receipt-percent', 'route' => 'app.tenant.bills', 'tab' => false],
                ['label' => 'Payments', 'icon' => 'heroicon-o-banknotes', 'route' => 'app.tenant.payments', 'tab' => false],
                ['label' => 'Issues', 'icon' => 'heroicon-o-wrench-screwdriver', 'route' => 'app.tenant.issues', 'tab' => false],
                ['label' => 'Notice to Vacate', 'icon' => 'heroicon-o-flag', 'route' => 'app.tenant.notices', 'tab' => false],
                ['label' => 'Chat', 'icon' => 'heroicon-o-chat-bubble-left-right', 'route' => 'app.tenant.chat', 'tab' => true],
                ['label' => 'Profile', 'icon' => 'heroicon-o-user-circle', 'route' => 'app.tenant.profile', 'tab' => false],
            ],
            // Reports, Staff, and Settings are admin/landlord only - caretakers are
            // narrowed to a single location and don't manage other staff, cross-property
            // reporting, or landlord-wide settings, matching UserResource::canAccess(),
            // Reports::shouldRegisterNavigation(), and Settings::shouldRegisterNavigation()
            // in the existing Filament panel.
            'admin', 'landlord' => [
                ['label' => 'Dashboard', 'icon' => 'heroicon-o-home', 'route' => 'app.admin.dashboard', 'tab' => true],
                ['label' => 'Tenants', 'icon' => 'heroicon-o-users', 'route' => 'app.admin.tenants', 'tab' => true],
                ['label' => 'Properties', 'icon' => 'heroicon-o-building-office-2', 'route' => 'app.admin.properties', 'tab' => true],
                ['label' => 'Units', 'icon' => 'heroicon-o-home-modern', 'route' => 'app.admin.units', 'tab' => false],
                ['label' => 'Viewing Requests', 'icon' => 'heroicon-o-calendar-days', 'route' => 'app.admin.viewing-requests', 'tab' => false],
                ['label' => 'Users', 'icon' => 'heroicon-o-user-group', 'route' => 'app.admin.interested-users', 'tab' => false],
                ['label' => 'Invoices', 'icon' => 'heroicon-o-credit-card', 'route' => 'app.admin.invoices', 'tab' => true],
                ['label' => 'Payments', 'icon' => 'heroicon-o-banknotes', 'route' => 'app.admin.payments', 'tab' => false],
                ['label' => 'M-Pesa Review', 'icon' => 'heroicon-o-device-phone-mobile', 'route' => 'app.admin.mpesa-review', 'tab' => false],
                // No app-shell page exists for this yet (setup is rare, one-time) - links
                // straight into the Filament panel rather than leaving it unreachable from
                // the main nav, which is where a landlord actually lands by default.
                ['label' => 'M-Pesa Setup Guide', 'icon' => 'heroicon-o-book-open', 'route' => 'app.admin.mpesa-guide', 'tab' => false],
                ['label' => 'M-Pesa Channels', 'icon' => 'heroicon-o-key', 'route' => 'app.admin.mpesa-channels', 'tab' => false],
                ['label' => 'Bills', 'icon' => 'heroicon-o-receipt-percent', 'route' => 'app.admin.bills', 'tab' => false],
                ['label' => 'Bill Types', 'icon' => 'heroicon-o-adjustments-horizontal', 'route' => 'app.admin.bill-types', 'tab' => false],
                ['label' => 'Announcements', 'icon' => 'heroicon-o-megaphone', 'route' => 'app.admin.announcements', 'tab' => false],
                ['label' => 'Expenses', 'icon' => 'heroicon-o-banknotes', 'route' => 'app.admin.expenses', 'tab' => false],
                ['label' => 'Issues', 'icon' => 'heroicon-o-wrench-screwdriver', 'route' => 'app.admin.issues', 'tab' => false],
                ['label' => 'Notices', 'icon' => 'heroicon-o-flag', 'route' => 'app.admin.notices', 'tab' => false],
                ['label' => 'Bookings', 'icon' => 'heroicon-o-calendar-days', 'route' => 'app.admin.bookings', 'tab' => false],
                ['label' => 'Promotions', 'icon' => 'heroicon-o-tag', 'route' => 'app.admin.promotions', 'tab' => false],
                ['label' => 'Inquiries', 'icon' => 'heroicon-o-question-mark-circle', 'route' => 'app.admin.inquiries', 'tab' => false],
                ['label' => 'Analytics', 'icon' => 'heroicon-o-presentation-chart-line', 'route' => 'app.admin.analytics', 'tab' => false],
                ['label' => 'Reports', 'icon' => 'heroicon-o-chart-bar', 'route' => 'app.admin.reports', 'tab' => false],
                ['label' => 'Staff', 'icon' => 'heroicon-o-identification', 'route' => 'app.admin.users', 'tab' => false],
                ['label' => 'Staff Roles', 'icon' => 'heroicon-o-shield-check', 'route' => 'app.admin.staff-roles', 'tab' => false],
                ['label' => 'Logs', 'icon' => 'heroicon-o-clipboard-document-list', 'route' => 'app.admin.logs', 'tab' => false],
                ['label' => 'Import Data', 'icon' => 'heroicon-o-arrow-up-tray', 'route' => 'app.admin.import-data', 'tab' => false],
                ['label' => 'Chat', 'icon' => 'heroicon-o-chat-bubble-left-right', 'route' => 'app.admin.chat', 'tab' => false],
                ['label' => 'Settings', 'icon' => 'heroicon-o-cog-6-tooth', 'route' => 'app.admin.settings', 'tab' => false],
            ],
            // Manager and Caretaker share an identical, trimmed nav for now (see the
            // Phase 1 plan for why) - missing Reports/Staff/Settings, matching
            // UserResource::canAccess(), Reports::mount(), Settings::mount().
            // A landlord-defined custom role (App\Models\StaffRole) always gets this
            // same trimmed nav regardless of its checkbox permissions - the checkboxes
            // gate actions *within* these pages, not which pages are reachable at all.
            'caretaker', 'manager', 'staff' => [
                ['label' => 'Dashboard', 'icon' => 'heroicon-o-home', 'route' => 'app.admin.dashboard', 'tab' => true],
                ['label' => 'Tenants', 'icon' => 'heroicon-o-users', 'route' => 'app.admin.tenants', 'tab' => true],
                ['label' => 'Properties', 'icon' => 'heroicon-o-building-office-2', 'route' => 'app.admin.properties', 'tab' => true],
                ['label' => 'Units', 'icon' => 'heroicon-o-home-modern', 'route' => 'app.admin.units', 'tab' => false],
                ['label' => 'Viewing Requests', 'icon' => 'heroicon-o-calendar-days', 'route' => 'app.admin.viewing-requests', 'tab' => false],
                ['label' => 'Invoices', 'icon' => 'heroicon-o-credit-card', 'route' => 'app.admin.invoices', 'tab' => true],
                ['label' => 'Payments', 'icon' => 'heroicon-o-banknotes', 'route' => 'app.admin.payments', 'tab' => false],
                ['label' => 'M-Pesa Review', 'icon' => 'heroicon-o-device-phone-mobile', 'route' => 'app.admin.mpesa-review', 'tab' => false],
                ['label' => 'Bills', 'icon' => 'heroicon-o-receipt-percent', 'route' => 'app.admin.bills', 'tab' => false],
                ['label' => 'Bill Types', 'icon' => 'heroicon-o-adjustments-horizontal', 'route' => 'app.admin.bill-types', 'tab' => false],
                ['label' => 'Announcements', 'icon' => 'heroicon-o-megaphone', 'route' => 'app.admin.announcements', 'tab' => false],
                ['label' => 'Issues', 'icon' => 'heroicon-o-wrench-screwdriver', 'route' => 'app.admin.issues', 'tab' => false],
                ['label' => 'Notices', 'icon' => 'heroicon-o-flag', 'route' => 'app.admin.notices', 'tab' => false],
                ['label' => 'Bookings', 'icon' => 'heroicon-o-calendar-days', 'route' => 'app.admin.bookings', 'tab' => false],
                ['label' => 'Chat', 'icon' => 'heroicon-o-chat-bubble-left-right', 'route' => 'app.admin.chat', 'tab' => false],
            ],
            // Agent is scoped to specific short_term houses only (staff_assignments.house_id)
            // - it manages bookings and markets those units (discounts, its own public
            // profile via Profile), not long-term tenancy, so the nav is trimmed to just
            // Bookings/Promotions/Chat (matching StaffScope::onHouseOrAssignedHouse()).
            'agent' => [
                ['label' => 'Bookings', 'icon' => 'heroicon-o-calendar-days', 'route' => 'app.admin.bookings', 'tab' => true],
                ['label' => 'Promotions', 'icon' => 'heroicon-o-tag', 'route' => 'app.admin.promotions', 'tab' => true],
                ['label' => 'Inquiries', 'icon' => 'heroicon-o-question-mark-circle', 'route' => 'app.admin.inquiries', 'tab' => true],
                ['label' => 'Chat', 'icon' => 'heroicon-o-chat-bubble-left-right', 'route' => 'app.admin.chat', 'tab' => true],
                ['label' => 'Analytics', 'icon' => 'heroicon-o-presentation-chart-line', 'route' => 'app.admin.analytics', 'tab' => false],
            ],
            'user' => [
                ['label' => 'Home', 'icon' => 'heroicon-o-home', 'route' => 'app.user.dashboard', 'tab' => true],
                ['label' => 'Watchlist', 'icon' => 'heroicon-o-heart', 'route' => 'app.user.watchlist', 'tab' => true],
                ['label' => 'Applications', 'icon' => 'heroicon-o-clipboard-document-list', 'route' => 'app.user.applications', 'tab' => true],
                ['label' => 'Alerts', 'icon' => 'heroicon-o-bell-alert', 'route' => 'app.user.alerts', 'tab' => false],
                ['label' => 'Profile', 'icon' => 'heroicon-o-user-circle', 'route' => 'app.user.profile', 'tab' => false],
            ],
            'superadmin' => [
                ['label' => 'Dashboard', 'icon' => 'heroicon-o-home', 'route' => 'app.superadmin.dashboard', 'tab' => true],
                ['label' => 'Landlords', 'icon' => 'heroicon-o-building-office-2', 'route' => 'app.superadmin.landlords', 'tab' => true],
                ['label' => 'Users', 'icon' => 'heroicon-o-user-group', 'route' => 'app.superadmin.users', 'tab' => false],
                ['label' => 'Locations', 'icon' => 'heroicon-o-map-pin', 'route' => 'app.superadmin.locations', 'tab' => false],
                ['label' => 'Packages', 'icon' => 'heroicon-o-cube', 'route' => 'app.superadmin.packages', 'tab' => false],
                ['label' => 'Subscriptions', 'icon' => 'heroicon-o-arrow-path', 'route' => 'app.superadmin.subscriptions', 'tab' => true],
                ['label' => 'Platform Settings', 'icon' => 'heroicon-o-cog-6-tooth', 'route' => 'app.superadmin.settings', 'tab' => true],
                // Founder-only planning/pitch pages, published as private Artifacts - these
                // routes just redirect out to them (see routes/web.php).
                ['label' => 'Rollout Plan', 'icon' => 'heroicon-o-map', 'route' => 'app.superadmin.rollout-plan', 'tab' => false],
                ['label' => 'Pitch Deck', 'icon' => 'heroicon-o-presentation-chart-line', 'route' => 'app.superadmin.pitch-deck', 'tab' => false],
            ],
            default => [],
        };
    }

    public static function tabItems(string $role): array
    {
        $items = array_values(array_filter(static::forRole($role), fn ($item) => $item['tab']));

        // Bottom bar is always: up to 4 flagged tab items + a trailing "More" sheet
        // trigger for everything else - keeps touch targets comfortably sized on
        // small screens instead of cramming every section into the bar.
        return array_slice($items, 0, 4);
    }

    public static function moreItems(string $role): array
    {
        $tabRoutes = array_column(static::tabItems($role), 'route');

        return array_values(array_filter(
            static::forRole($role),
            fn ($item) => !in_array($item['route'], $tabRoutes, true)
        ));
    }

    /**
     * Only tenants have "Profile" as its own nav entry (the list is short enough to
     * fit). For the other roles the sidebar's own user card / top bar avatar link
     * straight here instead of spending a nav slot on it.
     */
    public static function profileRoute(string $role): string
    {
        return match ($role) {
            'tenant' => 'app.tenant.profile',
            'superadmin' => 'app.superadmin.profile',
            'user' => 'app.user.profile',
            default => 'app.admin.profile',
        };
    }

    /**
     * Human-facing display name for a role - the underlying `role` column value stays
     * 'landlord' everywhere in code (permission checks, role options, route names);
     * this only affects what's shown to a person, so "Landlord" reads as the
     * gender-neutral "Property Owner" without touching a single permission check.
     */
    public static function roleLabel(string $role): string
    {
        return match ($role) {
            'landlord' => 'Property Owner',
            default => ucfirst($role),
        };
    }

    /**
     * The Filament panel dashboard for this role's "Advanced view" - null for 'user',
     * which has no Filament panel at all (it's a self-registered, landlord-less
     * account). The app-shell stays the default landing spot for every role
     * (GenericLoginController), Filament is reached only via this deliberate link.
     */
    public static function filamentDashboardRoute(string $role): ?string
    {
        return match ($role) {
            'admin', 'landlord', 'manager', 'caretaker', 'agent', 'staff' => 'filament.admin.pages.dashboard',
            'tenant' => 'filament.tenant.pages.tenant-dashboard',
            'superadmin' => 'filament.superadmin.pages.superadmin-dashboard',
            default => null,
        };
    }
}
