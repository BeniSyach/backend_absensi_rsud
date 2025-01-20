<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SPT extends Model
{
    use HasFactory;

    protected $table = 'spt';

    protected $fillable = [
        'id_user',
        'tanggal_spt',
        'waktu_spt',
        'lama_acara',
        'lokasi_spt',
        'file_spt',
        'status'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }
}
