<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Laravel's auth middleware redirects an unauthenticated visitor to
        // route('login') by default - this app names that route 'generic.login'
        // instead, so without this every guest hitting a protected route (an
        // expired session, a bookmarked dashboard link, etc.) got a hard 500
        // (RouteNotFoundException) rather than a clean bounce to the login page.
        $middleware->redirectGuestsTo(fn () => route('generic.login'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
