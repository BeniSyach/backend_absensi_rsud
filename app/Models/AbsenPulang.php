<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AbsenPulang extends Model
{
    use HasFactory;

    protected $table = 'absen_pulang'; 

    protected $fillable = [
        'absen_masuk_id',
        'user_id',
        'shift_id',
        'waktu_pulang',
        'waktu_kerja_id',
        'longitude',
        'latitude',
        'selish',
        'photo',
        'tpp_out',
        'keterangan',
    ];

    public $timestamps = true;

    public function absenmasuk()
    {
        return $this->belongsTo(AbsenMasuk::class, 'absen_masuk_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public static function getTotalPulang($tanggal_awal, $tanggal_akhir)
    {
        return self::whereBetween('waktu_pulang', [$tanggal_awal, $tanggal_akhir])
            ->selectRaw("
                SUM(CASE WHEN keterangan = 'Lebih Cepat Pulang' THEN 1 ELSE 0 END) as total_lebih_cepat_pulang,
                SUM(CASE WHEN keterangan = 'Tepat Waktu' THEN 1 ELSE 0 END) as total_tepat_waktu,
                SUM(CASE WHEN keterangan = 'Lebih Lambat Pulang' THEN 1 ELSE 0 END) as total_lebih_lambat_pulang
            ")
            ->first();
    }
}
