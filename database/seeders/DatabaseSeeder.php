<?php

namespace Database\Seeders;

use App\Models\Pengguna;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Mengisi data awal: tiga akun demo untuk uji peran berbeda.
     */
    public function run(): void
    {
        Pengguna::factory()->create([
            'nip'   => 'admin',
            'nama'  => 'Administrator Sistem',
            'email' => 'admin@ft.unsur.ac.id',
            'kata_sandi' => Hash::make('rahasia123'),
            'peran' => 'admin',
        ]);

        Pengguna::factory()->create([
            'nip'   => '197003051998031001',
            'nama'  => 'Dr. Ir. Budi Santoso, M.T.',
            'email' => 'budi.santoso@ft.unsur.ac.id',
            'kata_sandi' => Hash::make('rahasia123'),
            'peran' => 'wd3',
        ]);

        Pengguna::factory()->create([
            'nip'   => '198506152012121002',
            'nama'  => 'Siti Nurhaliza, S.Kom.',
            'email' => 'siti@ft.unsur.ac.id',
            'kata_sandi' => Hash::make('rahasia123'),
            'peran' => 'staf',
        ]);
    }
}
