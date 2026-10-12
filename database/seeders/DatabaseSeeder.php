<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Urutan WAJIB: HakAkses & CabangGedung dulu, baru JabatanStatus, lalu Pengguna
        $this->call([
            CabangGedungSeeder::class,
            HakAksesSeeder::class,        // HakAkses dijalankan sebelum JabatanStatus
            JabatanStatusSeeder::class,   // JabatanStatus butuh id dari HakAkses
            PenggunaSeeder::class,        // Pengguna butuh JabatanStatus & CabangGedung
        ]);  
    }
}