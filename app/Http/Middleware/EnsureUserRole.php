<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class EnsureUserRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && Auth::user()->role === 'user') {
            return $next($request);
        }

        // A promoted-to-tenant account landing here (e.g. a stale bookmark) should
        // simply go to their tenant dashboard instead of a hard 403.
        if (Auth::check() && Auth::user()->role === 'tenant') {
            return redirect()->route('app.tenant.dashboard');
        }

        abort(403, 'Unauthorized');
    }
}
