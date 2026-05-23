<?php

namespace App\Providers;

use App\Models\Pengguna;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

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
        $this->configureDefaults();
        $this->daftarkanGateIzin();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }

    /**
     * Daftarkan satu Gate Laravel untuk tiap kode izin yang ada di
     * config/peran.php. Hasilnya:
     *
     *   - @can('mahasiswa.kelola') aktif di Blade.
     *   - $pengguna->can('mahasiswa.kelola') aktif di PHP/Livewire.
     *
     * Wildcard '*' di config tidak didaftarkan sebagai Gate sendiri;
     * resolusinya ditangani Pengguna::punyaIzin().
     */
    protected function daftarkanGateIzin(): void
    {
        $kodeIzin = collect((array) Config::get('peran.peta', []))
            ->flatten()
            ->reject(fn ($kode) => $kode === '*')
            ->unique()
            ->values();

        foreach ($kodeIzin as $kode) {
            Gate::define($kode, fn (Pengguna $pengguna) => $pengguna->punyaIzin($kode));
        }
    }
}
