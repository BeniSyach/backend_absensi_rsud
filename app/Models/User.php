<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'nik',
        'nomor_hp',
        'alamat',
        'id_divisi',
        'id_level_akses',
        'id_gender',
        'id_status',
        'device_token',
        'opd_id'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'device_token'
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function divisi()
    {
        return $this->belongsTo(Divisi::class, 'id_divisi');
    }

    public function levelAkses()
    {
        return $this->belongsTo(LevelAkses::class, 'id_level_akses');
    }

    public function gender()
    {
        return $this->belongsTo(Gender::class, 'id_gender');
    }

    public function statusPegawai()
    {
        return $this->belongsTo(StatusPegawai::class, 'id_status');
    }

    public function opd()
    {
        return $this->belongsTo(Location::class, 'opd_id');
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();  // Biasanya ini adalah ID pengguna
    }

    public function getJWTCustomClaims()
    {
        return [];  // Anda bisa menambahkan klaim khusus jika perlu
    }
}
