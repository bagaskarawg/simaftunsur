<?php

use App\Models\Mahasiswa;
use App\Models\NilaiIpkSemester;
use App\Models\Pengguna;
use App\Models\ProgramStudi;
use App\Services\KlasterisasiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->prodi = ProgramStudi::create(['kode' => 'TIF', 'nama' => 'Teknik Informatika', 'jenjang' => 'S1']);
});

it('D-01: IPK di luar rentang 0–4 ditolak (aturan validasi impor)', function () {
    $aturan = ['ipk' => ['required', 'numeric', 'between:0,4']];

    expect(Validator::make(['ipk' => 4.5], $aturan)->fails())->toBeTrue()
        ->and(Validator::make(['ipk' => -0.1], $aturan)->fails())->toBeTrue()
        ->and(Validator::make(['ipk' => 3.5], $aturan)->fails())->toBeFalse();
});

it('D-02: NPM ganda ditolak', function () {
    Mahasiswa::factory()->untukProdi($this->prodi)->create(['npm' => '55201191240']);
    $this->actingAs(Pengguna::factory()->admin()->create());

    Volt::test('mahasiswa.baru')
        ->set('npm', '55201191240')  // duplikat
        ->set('nama', 'Uji Duplikat')
        ->set('program_studi_id', $this->prodi->id)
        ->set('angkatan', 2022)
        ->set('semester_aktif', 4)
        ->set('jenis_kelamin', 'L')
        ->set('status', 'aktif')
        ->call('simpan')
        ->assertHasErrors('npm');
});

it('D-03: hapus mahasiswa membersihkan riwayat IPK terkait (integritas relasi)', function () {
    $m = Mahasiswa::factory()->untukProdi($this->prodi)->create();
    NilaiIpkSemester::factory()->create([
        'mahasiswa_id' => $m->id, 'semester' => 1, 'semester_ganjil_genap' => 'ganjil',
    ]);

    $m->delete();

    expect(NilaiIpkSemester::where('mahasiswa_id', $m->id)->exists())->toBeFalse();
});

it('D-04: eksekusi klasterisasi ditolak bila mahasiswa layak < 3', function () {
    // Hanya 2 mahasiswa layak (masing-masing 3 catatan IPK).
    foreach (range(1, 2) as $i) {
        $m = Mahasiswa::factory()->untukProdi($this->prodi)->create(['status' => 'aktif']);
        for ($s = 1; $s <= 3; $s++) {
            NilaiIpkSemester::factory()->create([
                'mahasiswa_id' => $m->id, 'semester' => $s,
                'semester_ganjil_genap' => $s % 2 === 1 ? 'ganjil' : 'genap',
            ]);
        }
    }

    app(KlasterisasiService::class)->jalankan();
})->throws(RuntimeException::class);
