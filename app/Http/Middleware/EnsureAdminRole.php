<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class EnsureAdminRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && in_array(Auth::user()->role, ['admin', 'manager', 'caretaker', 'agent', 'landlord'])) {
            return $next($request);
        }

        // A tenant landing on an admin app-shell route (e.g. a stale bookmark) goes to
        // their own app-shell dashboard, not straight into Filament - the app-shell is
        // the default landing spot for every role; Filament is only reached via the
        // deliberate "Advanced view" link.
        if (Auth::check() && Auth::user()->role === 'tenant') {
            return redirect()->route('app.tenant.dashboard');
        }

        abort(403, 'Unauthorized');
    }
}
