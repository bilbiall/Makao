<?php

namespace App\Livewire\AdminApp;

use App\Models\House;
use App\Models\HouseAlert;
use App\Models\User;
use App\Services\HouseAlertMatchService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * "Users" area for a property manager - not staff, but the house-seeker ('user'
 * role) accounts who've actually shown interest in one of THIS landlord's
 * properties (watchlisted a unit, requested a viewing, or set an alert that
 * matches one). 'user' accounts aren't landlord-scoped - they browse listings
 * across every landlord on the platform - so this view (rather than a plain
 * "all users" list) is the only slice of them a landlord has any real
 * relationship with. See SuperadminApp\Users for the platform-wide list.
 */
class InterestedUsers extends Component
{
    public function mount(): void
    {
        // Matches the "Staff" page's own gate - manager/caretaker/agent don't
        // need this either.
        abort_unless(in_array(Auth::user()->role, ['admin', 'landlord']), 403);
    }

    public function render()
    {
        // House/ViewingRequest both auto-scope to the current landlord via
        // BelongsToLandlord - no explicit landlord_id filter needed here.
        $houses = House::with('location')->get();
        $houseIds = $houses->pluck('id');

        $rows = [];

        $addReason = function (?User $user, array $reason) use (&$rows) {
            if (!$user) {
                return;
            }

            $rows[$user->id] ??= ['user' => $user, 'reasons' => []];
            $rows[$user->id]['reasons'][] = $reason;
        };

        User::whereHas('watchlist', fn ($q) => $q->whereIn('houses.id', $houseIds))
            ->with(['watchlist' => fn ($q) => $q->whereIn('houses.id', $houseIds)->with('location')])
            ->get()
            ->each(function (User $user) use ($addReason) {
                foreach ($user->watchlist as $house) {
                    $addReason($user, ['type' => 'watchlist', 'house' => $house]);
                }
            });

        User::whereHas('viewingRequests', fn ($q) => $q->whereIn('house_id', $houseIds))
            ->with(['viewingRequests' => fn ($q) => $q->whereIn('house_id', $houseIds)->with('house.location')])
            ->get()
            ->each(function (User $user) use ($addReason) {
                foreach ($user->viewingRequests as $request) {
                    $addReason($user, ['type' => 'viewing_request', 'house' => $request->house, 'status' => $request->status]);
                }
            });

        User::whereHas('houseAlerts', fn ($q) => $q->whereIn('house_id', $houseIds))
            ->with(['houseAlerts' => fn ($q) => $q->whereIn('house_id', $houseIds)->with('house.location')])
            ->get()
            ->each(function (User $user) use ($addReason) {
                foreach ($user->houseAlerts as $alert) {
                    $addReason($user, ['type' => 'alert_specific', 'house' => $alert->house]);
                }
            });

        // General-criteria alerts aren't tied to a house at all, so the only way
        // to tell whether one is relevant to this landlord is to check it against
        // every one of this landlord's houses using the exact same match logic
        // HouseAlertMatchService itself uses to decide who to email.
        $matchService = app(HouseAlertMatchService::class);

        HouseAlert::whereNull('house_id')->with('user')->get()
            ->each(function (HouseAlert $alert) use ($houses, $matchService, $addReason) {
                $matched = $houses->filter(fn (House $house) => $matchService->matchesCriteria($alert, $house));

                if ($matched->isNotEmpty()) {
                    $addReason($alert->user, ['type' => 'alert_general', 'alert' => $alert, 'houses' => $matched]);
                }
            });

        $rows = collect($rows)->sortBy(fn ($row) => strtolower($row['user']->name))->values();

        return view('livewire.admin-app.interested-users', ['rows' => $rows])
            ->layout('components.layouts.app', ['title' => 'Users']);
    }
}
