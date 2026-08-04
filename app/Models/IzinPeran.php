<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Baris pivot penugasan satu kode izin ke satu peran.
 */
class IzinPeran extends Model
{
    protected $table = 'izin_peran';

    protected $fillable = ['peran_id', 'izin_kode'];

    /**
     * @return BelongsTo<Peran, $this>
     */
    public function peran(): BelongsTo
    {
        return $this->belongsTo(Peran::class, 'peran_id');
    }
}
