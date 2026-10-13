<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\Absensi;
use App\Models\CabangGedung;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Ambil pengaturan jam absensi (dari storage JSON atau default)
     */
    public static function getJamSetting(): array
    {
        $default = [
            '1'         => '08:00:00', // Masuk
            '2'         => '12:00:00', // Mulai Istirahat
            '3'         => '13:00:00', // Selesai Istirahat
            '4'         => '17:00:00', // Pulang
            'toleransi' => 15,         // Toleransi keterlambatan (menit)
        ];

        if (Storage::disk('local')->exists('jam_absensi.json')) {
            $saved = json_decode(Storage::disk('local')->get('jam_absensi.json'), true);
            if (is_array($saved)) {
                return array_merge($default, $saved);
            }
        }

        return $default;
    }

    /**
     * Konversi waktu UTC database ke waktu lokal cabang (WIB/WITA/WIT)
     */
    public static function toWaktuCabang($waktuRaw, $cabangGedungId = null): Carbon
    {
        $waktu = Carbon::parse($waktuRaw);
        $zona = '1'; // Default WIB (+7 jam)

        if ($cabangGedungId) {
            $zona = DB::table('cabang_gedung')->where('id', $cabangGedungId)->value('zona_waktu') ?? '1';
        }

        $seconds = $zona == '1' ? 25200 : ($zona == '2' ? 28800 : 32400);
        return $waktu->copy()->addSeconds($seconds);
    }

    public function index(Request $request)
    {
        $user = Auth::user();

        $month = $request->get('month', Carbon::now()->month);
        $year  = $request->get('year', Carbon::now()->year);

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

        $jamSetting = self::getJamSetting();
        $toleransi  = (int) ($jamSetting['toleransi'] ?? 15);

        /**
         * ===============================
         * 2. SUPERADMIN (1) & ADMIN (2)
         * ===============================
         */
        if (in_array($hakAkses, [1, 2], true) || str_contains(strtolower($user->tag ?? ''), 'admin')) {
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
                'hakAkses'         => 1,
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

        foreach ($riwayatTerbaru as $riwayat) {
            if (empty($riwayat->absen)) {
                continue;
            }

            $waktuLokal = self::toWaktuCabang($riwayat->absen, $user->cabang_gedung);
            $riwayat->absen = $waktuLokal->format('Y-m-d H:i:s');

            $kategoriMap = [
                1 => 'Presensi Masuk',
                2 => 'Mulai Istirahat',
                3 => 'Selesai Istirahat',
                4 => 'Presensi Pulang',
            ];
            $riwayat->kategori_label = $kategoriMap[$riwayat->kategori] ?? '-';

            $jamTarget = $jamSetting[(string) $riwayat->kategori] ?? null;
            $riwayat->status_label = 'Tepat Waktu';

            if ($jamTarget) {
                $target = Carbon::parse($waktuLokal->toDateString() . ' ' . $jamTarget);
                $batasTepat = $target->copy()->addMinutes($toleransi);

                if ((int) $riwayat->kategori === 1 && $waktuLokal->greaterThan($batasTepat)) {
                    $selisih = (int) round($target->diffInMinutes($waktuLokal));
                    $riwayat->status_label = "Terlambat {$selisih} Menit";
                } elseif ((int) $riwayat->kategori === 4 && $waktuLokal->lessThan($target)) {
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

    /**
     * =====================================
     * GRAFIK ABSEN MASUK BULANAN
     * =====================================
     */
    private function getMonthlyStats(
        $month,
        $year,
        $cabangGedungId = null,
        $userNomorInduk = null
    ) {
        $daysInMonth = Carbon::create($year, $month)->daysInMonth;

        $labels    = range(1, $daysInMonth);
        $dataTepat = array_fill(0, $daysInMonth, 0);
        $dataTelat = array_fill(0, $daysInMonth, 0);

        $jamSetting = self::getJamSetting();
        $jamMasukStr = $jamSetting['1'] ?? '08:00:00';
        $toleransi = (int) ($jamSetting['toleransi'] ?? 15);

        $query = Absensi::with('pengguna')
            ->where('kategori', 1)
            ->whereMonth('absen', $month)
            ->whereYear('absen', $year)
            ->whereNotNull('absen')
            ->whereHas('pengguna', function ($q) {
                // Jangan hitung akun Orang Tua
                $q->where('jabatan_status', '!=', 4);
            });

        if ($userNomorInduk) {
            $query->where('nomor_induk', $userNomorInduk);
        }

        if ($cabangGedungId) {
            $query->whereHas('pengguna', function ($q) use ($cabangGedungId) {
                $q->where('cabang_gedung', $cabangGedungId);
            });
        }

        foreach ($query->get() as $row) {
            $cabangId = $row->pengguna->cabang_gedung ?? null;
            $waktuLokal = self::toWaktuCabang($row->absen, $cabangId);
            $hariIndex  = (int) $waktuLokal->format('d') - 1;

            if ($hariIndex < 0 || $hariIndex >= $daysInMonth) {
                continue;
            }

            $batasMasuk = Carbon::parse($waktuLokal->toDateString() . ' ' . $jamMasukStr)
                ->addMinutes($toleransi);

            if ($waktuLokal->lte($batasMasuk)) {
                $dataTepat[$hariIndex]++;
            } else {
                $dataTelat[$hariIndex]++;
            }
        }

        return [
            'labels' => $labels,
            'tepat'  => $dataTepat,
            'telat'  => $dataTelat,
        ];
    }

    /**
     * =====================================
     * DASHBOARD KHUSUS ORANG TUA
     * =====================================
     */
    public function ortu(Request $request)
    {
        $user = Auth::user();
        $anakAnak = $user->anak()->get();

        $jamSetting = self::getJamSetting();
        $jamMasukStr = $jamSetting['1'] ?? '08:00:00';
        $toleransi = (int) ($jamSetting['toleransi'] ?? 15);

        // Gunakan tanggal hari ini sesuai WIB (+7 jam)
        $todayWib = Carbon::now()->addHours(7)->toDateString();

        $riwayatAbsensi = collect();

        foreach ($anakAnak as $anak) {
            // Ambil semua absensi masuk milik anak, lalu cek yang tanggal lokalnya hari ini
            $absenHariIni = DB::table('absensi')
                ->where('nomor_induk', $anak->nomor_induk)
                ->where('kategori', 1)
                ->orderBy('absen', 'desc')
                ->get()
                ->first(function ($item) use ($anak, $todayWib) {
                    if (empty($item->absen)) return false;
                    $lokal = self::toWaktuCabang($item->absen, $anak->cabang_gedung);
                    return $lokal->toDateString() === $todayWib;
                });

            $anak->absen_hari_ini = $absenHariIni;

            if ($absenHariIni && $absenHariIni->absen) {
                $waktuLokal = self::toWaktuCabang($absenHariIni->absen, $anak->cabang_gedung);
                $batasMasuk = Carbon::parse($waktuLokal->toDateString() . ' ' . $jamMasukStr)
                    ->addMinutes($toleransi);

                $anak->jam_masuk = $waktuLokal->format('H:i') . ' WIB';
                $anak->status_absen = $waktuLokal->lte($batasMasuk) ? 'Tepat Waktu' : 'Terlambat';
            } else {
                $anak->jam_masuk = '-';
                $anak->status_absen = 'Belum Absen';
            }

            // Ambil riwayat absensi 5 terakhir untuk anak ini & konversi ke jam WIB
            $riwayat = DB::table('absensi')
                ->where('nomor_induk', $anak->nomor_induk)
                ->orderBy('absen', 'desc')
                ->limit(5)
                ->get()
                ->map(function ($item) use ($anak) {
                    $item->nama_anak = $anak->nama;
                    if (!empty($item->absen)) {
                        $item->absen = self::toWaktuCabang($item->absen, $anak->cabang_gedung)->format('Y-m-d H:i:s');
                    }
                    return $item;
                });

            $riwayatAbsensi = $riwayatAbsensi->concat($riwayat);
        }

        $riwayatAbsensi = $riwayatAbsensi->sortByDesc('absen')->take(10);

        return view('dashboard.ortu', [
            'user'           => $user,
            'anakAnak'       => $anakAnak,
            'riwayatAbsensi' => $riwayatAbsensi,
        ]);
    }
}