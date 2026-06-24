<?php

namespace App\Models;

use Database\Factories\SekolahFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Master sekolah target promosi/PMB.
 */
class Sekolah extends Model
{
    /** @use HasFactory<SekolahFactory> */
    use HasFactory;

    protected $table = 'sekolah';

    protected $fillable = ['nama', 'jenjang', 'kota', 'alamat', 'kontak'];
}
