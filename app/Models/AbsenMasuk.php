<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AbsenMasuk extends Model
{
    use HasFactory;

    protected $table = 'absen_masuk'; 

    protected $fillable = [
        'user_id',
        'shift_id',
        'waktu_masuk',
        'waktu_kerja_id',
        'longitude',
        'latitude',
        'selish',
        'photo',
        'tpp_in',
        'keterangan',
    ];

    public $timestamps = true;

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function absenPulang()
    {
        return $this->hasMany(AbsenPulang::class, 'absen_masuk_id');
    }

    public function waktuKerja()
    {
        return $this->belongsTo(WaktuKerja::class, 'waktu_kerja_id');
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class, 'shift_id');
    }
}
