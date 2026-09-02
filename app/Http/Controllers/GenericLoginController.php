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
        $user = Auth::user();

        return match ($user->role) {
            // Land in the new mobile-first app shell by default, not Filament's admin-
            // panel chrome. The Filament panels are untouched and still fully reachable
            // directly at /admin, /tenant, /superadmin for anyone who navigates there.
            'admin', 'landlord', 'manager', 'caretaker' => redirect()->intended(route('app.admin.dashboard')),
            // Agent has no tenant/invoice/payment data to see (Dashboard's stats don't
            // apply to it - see AdminApp\Dashboard::mount()), so it lands on Bookings directly.
            'agent' => redirect()->intended(route('app.admin.bookings')),
            'tenant' => redirect()->intended(route('app.tenant.dashboard')),
            'superadmin' => redirect()->intended(route('app.superadmin.dashboard')),
            'user' => redirect()->intended(route('app.user.dashboard')),
            default  => abort(403, 'Role not allowed'),
        };
    }
}
