<?php

namespace App\Models;

use App\Enums\BidangKriteria;
use App\Enums\OperatorKriteria;
use Database\Factories\ProgramSyaratFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu kriteria persyaratan program. Dievaluasi INDEPENDEN sebagai boolean
 * (lolos/tidak) — tanpa bobot, tanpa skor. Kelayakan program = AND seluruh
 * syarat wajib (lihat App\Services\EvaluatorKelayakan).
 */
class ProgramSyarat extends Model
{
    /** @use HasFactory<ProgramSyaratFactory> */
    use HasFactory;

    protected $table = 'program_syarat';

    protected $fillable = [
        'program_id',
        'bidang',
        'operator',
        'nilai',
        'wajib',
        'label',
    ];

    protected function casts(): array
    {
        return [
            'wajib' => 'boolean',
        ];
    }

    /**
     * Program induk.
     *
     * @return BelongsTo<Program, $this>
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    /** Enum bidang (field yang dievaluasi). Null bila nilai tak dikenal. */
    public function bidangEnum(): ?BidangKriteria
    {
        return BidangKriteria::tryFrom($this->bidang);
    }

    /** Enum operator perbandingan. Null bila nilai tak dikenal. */
    public function operatorEnum(): ?OperatorKriteria
    {
        return OperatorKriteria::tryFrom($this->operator);
    }

    /**
     * Ambang ter-decode: array untuk operator `in` & field khusus (JSON),
     * selain itu string apa adanya.
     */
    public function nilaiTerdecode(): mixed
    {
        $operator = $this->operatorEnum();
        $khusus = $this->bidangEnum() === BidangKriteria::JumlahPrestasiMinTingkat;

        if ($operator === OperatorKriteria::In || $khusus) {
            $terurai = json_decode((string) $this->nilai, true);

            return $terurai ?? $this->nilai;
        }

        return $this->nilai;
    }
}
