<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WaktuKerja extends Model
{
    use HasFactory;

    protected $table = 'waktu_kerjas';

    protected $fillable = ['hari_id', 'shift_id', 'jam_mulai', 'jam_selesai', 'opd_id'];

    public function hari()
    {
        return $this->belongsTo(Hari::class);
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    public function opd()
    {
        return $this->belongsTo(Location::class, 'opd_id');
    }
}
