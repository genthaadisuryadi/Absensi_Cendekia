<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class PenggunaSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('pengguna')->delete();

        // Nomor Induk 0: Orang Tua
        DB::table('pengguna')->insert([
            'nomor_induk' => 0,
            'nama' => 'Orang Tua Siswa',
            'tag' => 'ortu001',
            'jabatan_status' => 4, // 4 = Orang Tua
            'cabang_gedung' => 1,
            'password' => Hash::make('password'),
            'aktif' => 1,
        ]);

        // Nomor Induk 1: SuperAdmin
        DB::table('pengguna')->insert([
            'nomor_induk' => 1,
            'nama' => 'SuperAdmin',
            'tag' => 'superadmin',
            'jabatan_status' => 1, // 1 = SuperAdmin
            'cabang_gedung' => 1,
            'password' => Hash::make('password'),
            'aktif' => 1,
        ]);

        // Nomor Induk 2: Admin
        DB::table('pengguna')->insert([
            'nomor_induk' => 2,
            'nama' => 'Admin',
            'tag' => 'admin123',
            'jabatan_status' => 2, // 2 = Admin
            'cabang_gedung' => 1,
            'password' => Hash::make('password'),
            'aktif' => 1,
        ]);

        // Nomor Induk 3: Siswa
        DB::table('pengguna')->insert([
            'nomor_induk' => 3,
            'nama' => 'Siswa Cendekia',
            'tag' => 'siswa001',
            'jabatan_status' => 3, // 3 = Siswa (general)
            'cabang_gedung' => 1,
            'password' => Hash::make('password'),
            'aktif' => 1,
        ]);
    }
}