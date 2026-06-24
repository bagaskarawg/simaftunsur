<?php

namespace App\Providers;

use App\Actions\Fortify\ResetUserPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\ResetsUserPasswords;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Reset kata sandi memakai kolom `kata_sandi` (lihat ResetUserPassword).
        $this->app->singleton(ResetsUserPasswords::class, ResetUserPassword::class);
    }

    public function boot(): void
    {
        $this->aturTampilan();
        $this->aturBatasanLaju();
    }

    /**
     * Mengarahkan Fortify ke view custom (Livewire/Blade Indonesia).
     */
    private function aturTampilan(): void
    {
        Fortify::loginView(fn () => view('livewire.auth.login'));
        Fortify::requestPasswordResetLinkView(fn () => view('auth.forgot-password'));
        Fortify::resetPasswordView(fn (Request $request) => view('auth.reset-password', ['request' => $request]));
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
