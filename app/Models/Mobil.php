<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mobil extends Model
{
    use HasFactory;

    protected $table = 'mobil';
    public $timestamps = false;

    protected $fillable = [
        'id',
        'nomor_kk',
        'nomor_chassis',
        'nomor_polisi',
        'nopol',
        'model',
        'tahun_pembuatan',
        'warna',
        'nomor_mesin',
        'tanggal_pembelian',
        'tanggal_start_sewa',
        'km_start_sewa',
        'rental_reference',
        'kode_sup',
    ];

    public function getNomorChassisAttribute($value)
    {
        return trim((string)$value);
    }

    public function htransaksis()
    {
        return $this->hasMany(Htransaksi::class, 'nomor_chassis', 'nomor_chassis');
    }
}
