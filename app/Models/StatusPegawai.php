<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StatusPegawai extends Model
{
    use HasFactory;

    protected $table = 'status_pegawai';

    protected $fillable = [
        'nama_status',
    ];


    public function users()
    {
        return $this->hasMany(User::class, 'id_status');
    }
}
