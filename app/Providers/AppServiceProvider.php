<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        // public/storage is a runtime artifact (gitignored, and reset on
        // every fresh container build on platforms like Railway) — recreate
        // it if missing rather than relying solely on a deploy script to
        // remember, since a missing link silently 404s every student photo
        // and signature image URL.
        if (! app()->runningInConsole() && ! is_link(public_path('storage'))) {
            Artisan::call('storage:link');
        }

        // Mat-no formats are sequential/guessable, so the public result
        // checker is rate-limited per IP against both brute-forcing a mat
        // number and guessing a student's access PIN.
        RateLimiter::for('result-check', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });
    }
}
