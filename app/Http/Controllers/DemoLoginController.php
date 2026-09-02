<?php

namespace App\Http\Controllers;

use App\Models\Landlord;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Powers the homepage's one-click "try the demo" buttons - logs the visitor
 * straight into a real seeded account for a given role, no password needed.
 * Every role resolves against the single flagship landlord in config('demo.
 * landlord_email') (see DemoNairobiSeeder's manifest), never an
 * attacker-supplied email/id, so this can only ever sign someone into that
 * one coherent demo portfolio - not an arbitrary account.
 */
class DemoLoginController extends Controller
{
    public function __invoke(Request $request, string $role)
    {
        abort_unless(config('demo.enabled'), 404);

        $landlord = Landlord::where('contact_email', config('demo.landlord_email'))->firstOrFail();

        $user = match ($role) {
            'owner' => User::where('email', config('demo.landlord_email'))->first(),
            'admin', 'manager', 'agent' => User::where('landlord_id', $landlord->id)->where('role', $role)->first(),
            // Kilimani Skyline Suites is the flagship landlord's BnB-mixed property -
            // more interesting to demo than a plain long-term-only caretaker.
            'caretaker' => User::where('landlord_id', $landlord->id)->where('role', 'caretaker')
                ->whereHas('staffAssignments.location', fn ($q) => $q->where('location_name', 'Kilimani Skyline Suites'))
                ->first(),
            'tenant' => Tenant::whereHas('house', fn ($q) => $q->where('landlord_id', $landlord->id))
                ->orderBy('id')
                ->first()
                ?->user,
            default => null,
        };

        abort_unless($user, 404);

        Auth::login($user);
        $request->session()->regenerate();

        return GenericLoginController::redirectForRole($user);
    }
}
