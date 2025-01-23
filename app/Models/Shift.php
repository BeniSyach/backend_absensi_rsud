<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    use HasFactory;

    protected $table = 'shifts';

    protected $fillable = ['nama_shift', 'opd_id'];

    public function opd()
    {
        return $this->belongsTo(Location::class, 'opd_id');
    }
}
