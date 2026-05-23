<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->aturTampilan();
        $this->aturBatasanLaju();
    }

    /**
     * Mengarahkan Fortify ke view login custom (Livewire/Blade Indonesia).
     */
    private function aturTampilan(): void
    {
        Fortify::loginView(fn () => view('livewire.auth.login'));
    }

    /**
     * Batasan laju untuk percobaan login agar tidak mudah brute-force.
     */
    private function aturBatasanLaju(): void
    {
        RateLimiter::for('login', function (Request $request) {
            $kunci = Str::transliterate(
                Str::lower($request->input(Fortify::username())).'|'.$request->ip()
            );

            return Limit::perMinute(5)->by($kunci);
        });
    }
}
