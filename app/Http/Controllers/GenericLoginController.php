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
            'admin', 'landlord', 'caretaker' => redirect()->intended(route('filament.admin.pages.dashboard')),
            'tenant' => redirect()->intended(route('filament.tenant.pages.tenant-dashboard')),
            'superadmin' => redirect()->intended(route('filament.superadmin.pages.superadmin-dashboard')),
            default  => abort(403, 'Role not allowed'),
        };
    }
}
