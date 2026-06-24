<?php

namespace App\Actions\Fortify;

use App\Models\Pengguna;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\ResetsUserPasswords;

/**
 * Aksi reset kata sandi via Fortify. Menyetel kolom `kata_sandi` (bukan
 * `password` bawaan) karena SIMAFTUNSUR memakai nama kolom Indonesia.
 */
class ResetUserPassword implements ResetsUserPasswords
{
    /**
     * @param  array<string, string>  $input
     */
    public function reset($user, array $input): void
    {
        Validator::make($input, [
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
            'password.min'       => 'Kata sandi minimal 8 karakter.',
        ])->validate();

        /** @var Pengguna $user */
        $user->forceFill([
            'kata_sandi' => Hash::make($input['password']),
        ])->save();
    }
}
