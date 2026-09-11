<?php

namespace App\Services;

use App\Helpers\EmailHelper;
use App\Helpers\EmailTemplateHelper;
use App\Models\House;
use App\Models\HouseAlert;
use App\Models\HouseAlertNotification;
use App\Notifications\DatabaseNotification;

/**
 * Checks a house that just became (or already was, on creation) publicly
 * available against every open alert, and notifies a match once (both
 * in-app and by email) - called from House::booted() whenever a house is
 * created or a field that could flip its public visibility changes.
 */
class HouseAlertMatchService
{
    public function notifyIfNewlyAvailable(House $house): void
    {
        if (! $this->isPubliclyAvailable($house)) {
            return;
        }

        $specific = HouseAlert::where('house_id', $house->id)->with('user')->get();

        $general = HouseAlert::whereNull('house_id')
            ->with('user')
            ->get()
            ->filter(fn (HouseAlert $alert) => $this->matchesCriteria($alert, $house));

        foreach ($specific->merge($general) as $alert) {
            $this->notify($alert, $house);
        }
    }

    protected function isPubliclyAvailable(House $house): bool
    {
        $query = $house->isShortTerm() ? House::bnbVisible() : House::publiclyVisible();

        return $query->whereKey($house->id)->exists();
    }

    /** Public so InterestedUsers can also check a general alert against a landlord's own houses. */
    public function matchesCriteria(HouseAlert $alert, House $house): bool
    {
        if ($alert->listing_mode && $alert->listing_mode !== $house->listing_mode) {
            return false;
        }

        if (filled($alert->house_types) && ! in_array($house->house_type, $alert->house_types, true)) {
            return false;
        }

        if (filled($alert->areas)) {
            $houseArea = $house->location?->area?->name;

            if (! $houseArea || ! in_array($houseArea, $alert->areas, true)) {
                return false;
            }
        }

        if ($alert->max_rent) {
            $price = $this->priceFor($house);

            if (! $price || $price > $alert->max_rent) {
                return false;
            }
        }

        return true;
    }

    protected function priceFor(House $house): ?float
    {
        if ($house->isShortTerm()) {
            return optional($house->pricePackages->sortBy('price')->first())->price;
        }

        return $house->rent_amount;
    }

    protected function notify(HouseAlert $alert, House $house): void
    {
        if (HouseAlertNotification::where('house_alert_id', $alert->id)->where('house_id', $house->id)->exists()) {
            return;
        }

        $user = $alert->user;

        if (! $user) {
            return;
        }

        $url = $house->isShortTerm() ? route('stays.show', $house) : route('listings.show', $house);
        $price = $this->priceFor($house);

        try {
            $user->notify(new DatabaseNotification(
                'A listing matches your alert',
                $house->publicName() . ($house->location?->geo_id ? ' in ' . $house->location->geo_id : '') . ' is now available.',
                $url
            ));
        } catch (\Throwable $e) {
            // ignore - matches the rest of this codebase's booted() convention
        }

        try {
            $body = EmailTemplateHelper::render('house_alert_match', [
                'user_name' => $user->name,
                'house_name' => $house->publicName(),
                'area' => $house->location?->geo_id ?? '',
                'price' => $price ? number_format($price) : 'N/A',
                'url' => $url,
            ], $house->landlord_id);

            EmailHelper::send($user->email, 'A place matching your alert is available', $body, $house->landlord_id);
        } catch (\Throwable $e) {
            // ignore
        }

        HouseAlertNotification::create([
            'house_alert_id' => $alert->id,
            'house_id' => $house->id,
            'notified_at' => now(),
        ]);
    }
}
