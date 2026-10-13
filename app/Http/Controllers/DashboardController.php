<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\Absensi;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        $month = $request->get('month', Carbon::now()->month);
        $year  = $request->get('year', Carbon::now()->year);

        // Ambil ID hak_akses dari tabel jabatan_status
        $hakAkses = (int) DB::table('jabatan_status')
            ->where('id', $user->jabatan_status)
            ->value('hak_akses');

        /**
         * ===============================
         * 1. ORANG TUA (hak_akses = 4)
         * ===============================
         */
        if ($hakAkses === 4) {
            return redirect()->route('dashboard.ortu');
        }

        /**
         * ===============================
         * 2. SUPERADMIN (1) & ADMIN (2)
         * ===============================
         */
        if (in_array($hakAkses, [1, 2], true)) {
            $cabangGedungId = $request->get('cabangGedung');
            $cabangGedungList = DB::table('cabang_gedung')->get();

            $lokasiCabang = $cabangGedungId
                ? DB::table('cabang_gedung')
                    ->where('id', $cabangGedungId)
                    ->value('lokasi')
                : 'Semua Cabang';

            $stats = $this->getMonthlyStats(
                $month,
                $year,
                $cabangGedungId,
                null
            );

            return view('dashboard.index', [
                'cabangGedungList' => $cabangGedungList,
                'labels'           => $stats['labels'],
                'tepatWaktu'       => $stats['tepat'],
                'terlambat'        => $stats['telat'],
                'lokasiCabang'     => $lokasiCabang,
                'cabangGedungId'   => $cabangGedungId ?? null,
                'hakAkses'         => 1, // Menampilkan filter cabang di view
            ]);
        }

        /**
         * ===============================
         * 3. SISWA / GENERAL (hak_akses = 3)
         * ===============================
         */
        $nomorInduk = $user->nomor_induk;

        $totalHariMasuk = Absensi::where('nomor_induk', $nomorInduk)
            ->whereMonth('absen', $month)
            ->whereYear('absen', $year)
            ->where('kategori', 1)
            ->count();

        $totalHariEfektif = max(Carbon::now()->day, 1);
        $persentaseKehadiran = ($totalHariMasuk / $totalHariEfektif) * 100;

        $riwayatTerbaru = Absensi::where('nomor_induk', $nomorInduk)
            ->orderBy('absen', 'desc')
            ->take(5)
            ->get();

        $defaultJam = [
            '1' => '08:00:00',
            '2' => '12:00:00',
            '3' => '13:00:00',
            '4' => '17:00:00',
        ];

        foreach ($riwayatTerbaru as $riwayat) {
            if (empty($riwayat->absen)) {
                continue;
            }

            $waktuAbsen = Carbon::parse($riwayat->absen);

            $kategoriMap = [
                1 => 'Presensi Masuk',
                2 => 'Mulai Istirahat',
                3 => 'Selesai Istirahat',
                4 => 'Presensi Pulang',
            ];
            $riwayat->kategori_label = $kategoriMap[$riwayat->kategori] ?? '-';

            $jamTarget = $defaultJam[$riwayat->kategori] ?? null;
            $riwayat->status_label = 'Tepat Waktu';

            if ($jamTarget) {
                $target = Carbon::parse($waktuAbsen->toDateString() . ' ' . $jamTarget);

                if ((int) $riwayat->kategori === 1 && $waktuAbsen->greaterThan($target->copy()->addMinutes(15))) {
                    $selisih = $waktuAbsen->diffInMinutes($target);
                    $riwayat->status_label = "Terlambat {$selisih} Menit";
                } elseif ((int) $riwayat->kategori === 4 && $waktuAbsen->lessThan($target)) {
                    $riwayat->status_label = 'Pulang Cepat';
                }
            }
        }

        return view('dashboard.siswa', [
            'totalHariMasuk'      => $totalHariMasuk,
            'totalHariEfektif'    => $totalHariEfektif,
            'persentaseKehadiran' => $persentaseKehadiran,
            'riwayatTerbaru'      => $riwayatTerbaru,
            'hakAkses'            => $hakAkses,
        ]);
    }