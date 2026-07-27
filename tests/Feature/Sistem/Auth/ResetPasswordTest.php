<?php

use App\Models\Pengguna;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

uses(RefreshDatabase::class);

it('menampilkan halaman lupa kata sandi', function () {
    $this->withoutVite();
    $this->get(route('password.request'))->assertOk()->assertSee('Lupa kata sandi');
});

it('mengirim tautan reset ke email terdaftar', function () {
    $pengguna = Pengguna::factory()->create(['email' => 'reset@ft.unsur.ac.id']);

    $this->post(route('password.email'), ['email' => 'reset@ft.unsur.ac.id'])
        ->assertSessionHasNoErrors()
        ->assertSessionHas('status');
});

it('mereset kata sandi memakai token & memperbarui kolom kata_sandi', function () {
    $pengguna = Pengguna::factory()->create([
        'email' => 'reset@ft.unsur.ac.id', 'kata_sandi' => Hash::make('lamasandi123'),
    ]);

    $token = Password::broker()->createToken($pengguna);

    $this->post(route('password.update'), [
        'token'                 => $token,
        'email'                 => 'reset@ft.unsur.ac.id',
        'password'              => 'barusandi123',
        'password_confirmation' => 'barusandi123',
    ])->assertSessionHasNoErrors();

    expect(Hash::check('barusandi123', $pengguna->refresh()->kata_sandi))->toBeTrue();
});
