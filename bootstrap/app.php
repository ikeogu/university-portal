<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Railway (and most PaaS hosts) terminate TLS at their own edge and
        // forward requests to this container over plain HTTP, signaling the
        // original scheme via X-Forwarded-Proto. Without trusting that,
        // Laravel believes every request is HTTP — so @vite's generated
        // asset URLs come out as http:// on a page actually loaded over
        // https://, and browsers silently block that as mixed content,
        // producing a blank page (no console error dialog, just nothing
        // rendering) rather than an obvious failure. '*' is safe here
        // specifically because Railway's networking means traffic can only
        // reach this container through Railway's own proxy in the first
        // place — there's no untrusted path for a spoofed header to arrive.
        $middleware->trustProxies(at: '*');

        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureUserHasRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
