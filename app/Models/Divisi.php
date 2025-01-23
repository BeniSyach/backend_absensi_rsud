<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Divisi extends Model
{
    use HasFactory;

    protected $table = 'divisi';

    protected $fillable = [
        'nama_divisi',
        'id_atasan',
        'id_jabatan',
        'id_opd'
    ];

    public function atasan()
    {
        return $this->belongsTo(User::class, 'id_atasan');
    }

    // Relasi ke pengguna lain yang ada di divisi ini
    public function users()
    {
        return $this->hasMany(User::class, 'id_divisi');
    }

    public function jabatan()
    {
        return $this->belongsTo(Jabatan::class, 'id_jabatan');
    }

    public function opd()
    {
        return $this->belongsTo(Location::class, 'opd_id');
    }
}
