<?php

namespace Tests\Feature\Auth;

use App\Models\Pengguna;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_login_dapat_dirender(): void
    {
        $respons = $this->get(route('login'));

        $respons->assertOk();
    }

    public function test_pengguna_dapat_masuk_dengan_nip_dan_kata_sandi(): void
    {
        $pengguna = Pengguna::factory()->create([
            'nip' => '199001012020121001',
            'kata_sandi' => Hash::make('rahasia123'),
        ]);

        $respons = $this->post(route('login.store'), [
            'nip' => $pengguna->nip,
            'password' => 'rahasia123',
        ]);

        $respons
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('beranda', absolute: false));

        $this->assertAuthenticatedAs($pengguna);
    }

    public function test_pengguna_tidak_dapat_masuk_dengan_kata_sandi_salah(): void
    {
        $pengguna = Pengguna::factory()->create([
            'kata_sandi' => Hash::make('rahasia123'),
        ]);

        $respons = $this->post(route('login.store'), [
            'nip' => $pengguna->nip,
            'password' => 'kata-sandi-salah',
        ]);

        $respons->assertSessionHasErrors('nip');
        $this->assertGuest();
    }

    public function test_pengguna_dapat_keluar(): void
    {
        $pengguna = Pengguna::factory()->create();

        $respons = $this->actingAs($pengguna)->post(route('logout'));

        $respons->assertRedirect('/');
        $this->assertGuest();
    }
}
