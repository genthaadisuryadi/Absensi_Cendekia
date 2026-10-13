<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class JabatanStatusSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('jabatan_status')->delete();

        $jabatanStatuses = [
            [
                'id' => 1,
                'jabatan_status' => 'SuperAdmin',
                'hak_akses' => 1, // superadmin
                'aktif' => 1,
            ],
            [
                'id' => 2,
                'jabatan_status' => 'Admin',
                'hak_akses' => 2, // admin
                'aktif' => 1,
            ],
            [
                'id' => 3,
                'jabatan_status' => 'Siswa',
                'hak_akses' => 3, // general (siswa)
                'aktif' => 1,
            ],
            [
                'id' => 4,
                'jabatan_status' => 'Orang Tua',
                'hak_akses' => 4, // orang_tua
                'aktif' => 1,
            ],
        ];

        foreach ($jabatanStatuses as $status) {
            DB::table('jabatan_status')->insert($status);
        }

        $this->command->info('Jabatan Status berhasil di-seed!');
    }
}