<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GenericLoginController extends Controller
{
    //generic login and redirect
     /**
     * Handle the incoming POST /login request.
     */
    public function __invoke(Request $request)
    {
        // Validate credentials
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        // Attempt login
        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withErrors(['email' => 'Invalid credentials'])
                ->onlyInput('email');
        }

        // Regenerate session & redirect by role
        $request->session()->regenerate();

        return static::redirectForRole(Auth::user());
    }

    /**
     * Shared by the real login form above and DemoLoginController's one-click
     * "try the demo" buttons - both need the exact same "which dashboard does
     * this role land on" logic, just reached via a different auth step. Uses
     * intended() - correct here, since both callers run right after an actual
     * login and want to bounce back to whatever protected page the visitor
     * originally tried to reach. EmailVerificationController deliberately does
     * NOT use this - see dashboardRouteForRole() below.
     */
    public static function redirectForRole(\App\Models\User $user)
    {
        return redirect()->intended(route(static::dashboardRouteForRole($user->role)));
    }

    /**
     * Just the route name, no redirect - for a caller that's already past login
     * and must NOT use intended(). EmailVerificationController needs this: the
     * `verified` middleware's redirect to verification.notice runs through
     * Laravel's Redirect::guest(), which captures the referring page (the one
     * with the gated action) as the "intended" URL - reusing redirectForRole()
     * there would silently bounce the visitor right back to that same page
     * with no visible explanation, instead of their own dashboard where the
     * unverified-email banner and flashed status actually render.
     */
    public static function dashboardRouteForRole(string $role): string
    {
        return match ($role) {
            // Land in the new mobile-first app shell by default, not Filament's admin-
            // panel chrome. The Filament panels are untouched and still fully reachable
            // directly at /admin, /tenant, /superadmin for anyone who navigates there.
            'admin', 'landlord', 'manager', 'caretaker' => 'app.admin.dashboard',
            // Agent has no tenant/invoice/payment data to see (Dashboard's stats don't
            // apply to it - see AdminApp\Dashboard::mount()), so it lands on Bookings directly.
            'agent' => 'app.admin.bookings',
            'tenant' => 'app.tenant.dashboard',
            'superadmin' => 'app.superadmin.dashboard',
            'user' => 'app.user.dashboard',
            default => abort(403, 'Role not allowed'),
        };
    }
}
