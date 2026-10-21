<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Pengguna extends Authenticatable
{
    use Notifiable;

    protected $table = 'pengguna';
    public $timestamps = false;

    protected $primaryKey = 'nomor_induk';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'nomor_induk',
        'nama',
        'foto_profil',
        'kelas',
        'tag',
        'jabatan_status',
        'cabang_gedung',
        'password',
        'aktif',
        'foto_profil',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    // 🔹 Cabang / Gedung
    public function cabangGedung()
    {
        return $this->belongsTo(
            CabangGedung::class,
            'cabang_gedung',
            'id'
        );
    }

    // 🔹 Jabatan Status
    public function jabatanStatus()
    {
        return $this->belongsTo(
            JabatanStatus::class,
            'jabatan_status',
            'id'
        );
    }

    // 🔹 Absensi
    public function absensis()
    {
        return $this->hasMany(
            Absensi::class,
            'nomor_induk',
            'nomor_induk'
        );
    }

    // 🔹 Cuti
    public function cuti()
    {
        return $this->hasMany(
            Cuti::class,
            'nomor_induk',
            'nomor_induk'
        );
    }

    // 🔹 Relasi Orang Tua ke Siswa (Anak)
    public function anak()
    {
        return $this->belongsToMany(
            Pengguna::class, 
            'orang_tua_siswa', 
            'orang_tua_id', 
            'siswa_id'
        );
    }

    // 🔹 Relasi Siswa ke Orang Tua
    public function orangTua()
    {
        return $this->belongsToMany(
            Pengguna::class, 
            'orang_tua_siswa', 
            'siswa_id', 
            'orang_tua_id'
        );
    }
}
