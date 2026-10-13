<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */

    public $timestamps = false;
    protected $table = 'pengguna'; // Nama tabel kustom Anda
    protected $primaryKey = 'nomor_induk';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'nomor_induk',
        'nama',
        'kelas',
        'tag',
        'jabatan_status',
        'cabang_gedung',
        'password',
        'aktif',
    ];

    // Beritahu Laravel untuk menggunakan nomor_induk sebagai pengganti email
    public function getAuthIdentifierName()
    {
        return 'nomor_induk';
    }

    /**
     * Get the attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    // 🔹 Jabatan Status
    public function jabatanStatus()
    {
        return $this->belongsTo(
            JabatanStatus::class,
            'jabatan_status',
            'id'
        );
    }

    // 🔹 Cabang Gedung
    public function cabangGedung()
    {
        return $this->belongsTo(
            CabangGedung::class,
            'cabang_gedung',
            'id'
        );
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    // protected function casts(): array
    // {
    //     return [
    //         'email_verified_at' => 'datetime',
    //         'password' => 'hashed',
    //     ];
    // }

    // 🔹 Relasi Orang Tua ke Siswa (Anak)
    public function anak()
    {
        return $this->belongsToMany(
            User::class, 
            'orang_tua_siswa', 
            'orang_tua_id', 
            'siswa_id'
        );
    }

    // 🔹 Relasi Siswa ke Orang Tua
    public function orangTua()
    {
        return $this->belongsToMany(
            User::class, 
            'orang_tua_siswa', 
            'siswa_id', 
            'orang_tua_id'
        );
    }
}
