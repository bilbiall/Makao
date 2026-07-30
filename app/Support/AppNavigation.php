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
                ['label' => 'Properties', 'icon' => 'heroicon-o-building-office-2', 'route' => 'app.admin.properties', 'tab' => false],
                ['label' => 'Invoices', 'icon' => 'heroicon-o-credit-card', 'route' => 'app.admin.invoices', 'tab' => true],
                ['label' => 'Payments', 'icon' => 'heroicon-o-banknotes', 'route' => 'app.admin.payments', 'tab' => false],
                ['label' => 'Bills', 'icon' => 'heroicon-o-receipt-percent', 'route' => 'app.admin.bills', 'tab' => false],
                ['label' => 'Issues', 'icon' => 'heroicon-o-wrench-screwdriver', 'route' => 'app.admin.issues', 'tab' => false],
                ['label' => 'Notices', 'icon' => 'heroicon-o-flag', 'route' => 'app.admin.notices', 'tab' => false],
                ['label' => 'Reports', 'icon' => 'heroicon-o-chart-bar', 'route' => 'app.admin.reports', 'tab' => false],
                ['label' => 'Staff', 'icon' => 'heroicon-o-identification', 'route' => 'app.admin.users', 'tab' => false],
                ['label' => 'Chat', 'icon' => 'heroicon-o-chat-bubble-left-right', 'route' => 'app.admin.chat', 'tab' => true],
                ['label' => 'Settings', 'icon' => 'heroicon-o-cog-6-tooth', 'route' => 'app.admin.settings', 'tab' => false],
            ],
            'caretaker' => [
                ['label' => 'Dashboard', 'icon' => 'heroicon-o-home', 'route' => 'app.admin.dashboard', 'tab' => true],
                ['label' => 'Tenants', 'icon' => 'heroicon-o-users', 'route' => 'app.admin.tenants', 'tab' => true],
                ['label' => 'Properties', 'icon' => 'heroicon-o-building-office-2', 'route' => 'app.admin.properties', 'tab' => false],
                ['label' => 'Invoices', 'icon' => 'heroicon-o-credit-card', 'route' => 'app.admin.invoices', 'tab' => true],
                ['label' => 'Payments', 'icon' => 'heroicon-o-banknotes', 'route' => 'app.admin.payments', 'tab' => false],
                ['label' => 'Bills', 'icon' => 'heroicon-o-receipt-percent', 'route' => 'app.admin.bills', 'tab' => false],
                ['label' => 'Issues', 'icon' => 'heroicon-o-wrench-screwdriver', 'route' => 'app.admin.issues', 'tab' => false],
                ['label' => 'Notices', 'icon' => 'heroicon-o-flag', 'route' => 'app.admin.notices', 'tab' => false],
                ['label' => 'Chat', 'icon' => 'heroicon-o-chat-bubble-left-right', 'route' => 'app.admin.chat', 'tab' => true],
            ],
            'superadmin' => [
                ['label' => 'Dashboard', 'icon' => 'heroicon-o-home', 'route' => 'app.superadmin.dashboard', 'tab' => true],
                ['label' => 'Landlords', 'icon' => 'heroicon-o-building-office-2', 'route' => 'app.superadmin.landlords', 'tab' => true],
                ['label' => 'Packages', 'icon' => 'heroicon-o-cube', 'route' => 'app.superadmin.packages', 'tab' => false],
                ['label' => 'Subscriptions', 'icon' => 'heroicon-o-arrow-path', 'route' => 'app.superadmin.subscriptions', 'tab' => true],
                ['label' => 'Platform Settings', 'icon' => 'heroicon-o-cog-6-tooth', 'route' => 'app.superadmin.settings', 'tab' => false],
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
            default => 'app.admin.profile',
        };
    }
}
