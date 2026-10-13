<?php

namespace App\Imports;

use App\Models\Pengguna;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class PenggunaImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        // Skip jika baris NIS kosong
        if (!isset($row['nis']) || empty($row['nis'])) {
            return null;
        }

        // Cek apakah NIS sudah ada di database, jika sudah ada, lewati (skip)
        $exists = Pengguna::where('nomor_induk', $row['nis'])->first();
        if ($exists) {
            return null;
        }

        return new Pengguna([
            'nomor_induk'    => $row['nis'],
            'nama'           => $row['nama'],
            'kelas'          => $row['kelas'] ?? null,
            'tag'            => $row['tag_rfid'] ?? null,
            'jabatan_status' => 3, // 3 = Otomatis jadi Siswa
            'cabang_gedung'  => $row['id_cabang'] ?? 1, // ID cabang default
            'password'       => Hash::make('password'), // Password default
            'aktif'          => 1,
        ]);
    }
}