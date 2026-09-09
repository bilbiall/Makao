<?php

namespace App\Support;

/**
 * Flag models check to skip side effects that make sense for a real-time event
 * (SMS, in-app notifications) but not for bulk-loading a property manager's
 * pre-existing historical data - importing five years of past tenants/invoices
 * shouldn't SMS every one of them a "welcome" or "payment received" text, and an
 * imported invoice's amount should be trusted as-is rather than recomputed from
 * Bill rows that don't exist for that historical period. See Invoice/Tenant/
 * Payment/House/Location booted() hooks for the guarded side effects.
 */
class ImportContext
{
    protected static bool $active = false;

    public static function run(callable $callback): mixed
    {
        $previous = self::$active;
        self::$active = true;

        try {
            return $callback();
        } finally {
            self::$active = $previous;
        }
    }

    public static function active(): bool
    {
        return self::$active;
    }
}
