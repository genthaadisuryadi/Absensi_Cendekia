<?php

namespace App\Http\Controllers;

use App\Models\Absensi;
use App\Models\CabangGedung;
use App\Models\Pengguna;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class AbsensiController extends Controller
{
    private function getJamConfig(): array
    {
        return DashboardController::getJamSetting();
    }

    /**
     * HALAMAN PENGATURAN JAM ABSENSI
     */
    public function editJam()
    {
        $jamSetting = $this->getJamConfig();
        return view('absensi.jam_setting', compact('jamSetting'));
    }

    /**
     * SIMPAN PENGATURAN JAM ABSENSI (UPDATE: HARI AKTIF)
     */
    public function updateJam(Request $request)
    {
        $request->validate([
            'jam_masuk'             => 'required',
            'jam_mulai_istirahat'   => 'required',
            'jam_selesai_istirahat' => 'required',
            'jam_pulang'            => 'required',
            'toleransi'             => 'required|integer|min:0|max:180',
            'hari_aktif'            => 'required|array|min:1',
        ], [
            'hari_aktif.required'   => 'Silakan centang minimal satu hari aktif absensi.',
        ]);

        $formatTime = function ($t) {
            return strlen($t) === 5 ? $t . ':00' : $t;
        };

        // PASTIKAN URUTAN INI SESUAI DENGAN KATEGORI DI DATABASE
        // 1 = Masuk, 2 = Mulai Istirahat, 3 = Selesai Istirahat, 4 = Pulang
        $data = [
            '1'          => $formatTime($request->jam_masuk),
            '2'          => $formatTime($request->jam_mulai_istirahat),
            '3'          => $formatTime($request->jam_selesai_istirahat),
            '4'          => $formatTime($request->jam_pulang),
            'toleransi'  => (int) $request->toleransi,
            'hari_aktif' => $request->hari_aktif,
        ];

        Storage::disk('local')->put('jam_absensi.json', json_encode($data, JSON_PRETTY_PRINT));

        return back()->with('success', 'Pengaturan waktu dan hari absensi berhasil diperbarui!');
    }

    /**
     * FUNGSI BANTUAN CEK HARI LIBUR
     */
    private function isHariLibur(): bool
    {
        $jamConfig = $this->getJamConfig();
        $hariAktif = $jamConfig['hari_aktif'] ?? ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];
        
        $namaHariInggrisKeIndo = [
            'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu', 'Sunday' => 'Minggu'
        ];
        
        $hariIni = $namaHariInggrisKeIndo[Carbon::now()->format('l')];

        return !in_array($hariIni, $hariAktif);
    }

    /**
     * =====================================
     * LIST + FILTER ABSENSI (KHUSUS SISWA)
     * =====================================
     */
    public function index(Request $request)
    {
        $cabangGedungs = CabangGedung::where('aktif', 1)->get();
        $jamConfig     = $this->getJamConfig();
        $toleransi     = (int) ($jamConfig['toleransi'] ?? 15);

        // Ambil SEMUA data absensi terlebih dahulu (beserta relasinya)
        $query = Absensi::with(['mesin', 'pengguna.cabangGedung']);

        if ($request->filled('awal') && $request->filled('akhir')) {
            $query->whereBetween('absen', [
                $request->awal . ' 00:00:00',
                $request->akhir . ' 23:59:59',
            ]);
        }

        if ($request->filled('kategori')) {
            $query->where('kategori', $request->kategori);
        }

        if ($request->filled('cabang_gedung')) {
            $query->whereHas('pengguna', function ($q) use ($request) {
                $q->where('cabang_gedung', $request->cabang_gedung);
            });
        }

        $allAbsensi = $query->orderBy('absen', 'desc')->get();
        $absensis = collect(); // Koleksi khusus untuk menampung Siswa

        foreach ($allAbsensi as $absensi) {
            if (empty($absensi->absen) || empty($absensi->pengguna)) continue;

            // Dapatkan nama hak akses (huruf kecil)
            $hakAksesUser = strtolower($absensi->pengguna->jabatanStatus->hakAkses->hak ?? '');

            // FILTER MUTLAK: Hanya masukkan ke tabel jika hak aksesnya "siswa" atau "general"
            if (!in_array($hakAksesUser, ['siswa', 'general'])) {
                continue; // Tendang data Admin, Superadmin, Internal, Pegawai, dll.
            }

            $absensi->status = null;
            $absensi->status_label = null;
            $absensi->warna = 'text-gray-700';
            $absensi->display_absen = null;
            $absensi->display_batas = null;
            $absensi->selisih_menit = null;

            $waktuAbsen = Carbon::parse($absensi->absen);
            $cabangModel = $absensi->pengguna->cabangGedung ?? CabangGedung::find($absensi->pengguna->cabang_gedung ?? null);
            $zona = $cabangModel->zona_waktu ?? '1';
            $zonaWaktu = $zona == '1' ? 'WIB' : ($zona == '2' ? 'WITA' : 'WIT');
            $seconds = $zona == '1' ? 25200 : ($zona == '2' ? 28800 : 32400);

            $displayWaktu = $waktuAbsen->copy()->addSeconds($seconds);

            switch ((string) $absensi->kategori) {
                case '1': $absensi->kategori_label = 'Masuk'; break;
                case '2': $absensi->kategori_label = 'Mulai Istirahat'; break;
                case '3': $absensi->kategori_label = 'Selesai Istirahat'; break;
                case '4': $absensi->kategori_label = 'Pulang'; break;
                default:  $absensi->kategori_label = '-'; break;
            }

            $tgl = $displayWaktu->toDateString();
            $jam = $jamConfig[(string) $absensi->kategori] ?? null;
            $isTepat = false;
            $targetLokal = null;

            if ($jam) {
                $targetLokal = Carbon::parse($tgl . ' ' . $jam);
                switch ((string) $absensi->kategori) {
                    case '1': 
                    case '2': 
                    case '3': 
                        $isTepat = $displayWaktu->lessThanOrEqualTo($targetLokal->copy()->addMinutes($toleransi));
                        break;
                    case '4': 
                        $isTepat = $displayWaktu->greaterThanOrEqualTo($targetLokal);
                        break;
                }
            }

            $absensi->status = $isTepat ? 'tepat_waktu' : 'telat';
            $absensi->status_label = $isTepat ? 'Tepat Waktu' : 'Terlambat';
            $absensi->warna = $isTepat ? 'text-green-600' : 'text-red-600';

            $absensi->display_absen = $displayWaktu->format('Y-m-d H:i:s') . ' ' . $zonaWaktu;
            $absensi->display_batas = $targetLokal ? $targetLokal->format('Y-m-d H:i:s') : null;
            $absensi->selisih_menit = $targetLokal ? round(abs($displayWaktu->diffInSeconds($targetLokal)) / 60) : null;

            $absensis->push($absensi);
        }

        if ($request->wantsJson()) {
            return response()->json($absensis);
        }

        return view('absensi.index', compact('absensis', 'cabangGedungs'));
    }

    public function show($id)
    {
        $absensi = Absensi::with(['mesin', 'pengguna.cabangGedung'])->findOrFail($id);
        return response()->json($absensi);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nomor_induk' => 'required|exists:pengguna,nomor_induk',
            'absen'       => 'required|date',
            'kategori'    => 'required|in:1,2,3,4',
            'idmesin'     => 'nullable',
        ]);

        $absensi = Absensi::create($data);

        return response()->json($absensi, 201);
    }

    public function dashboard(Request $request)
    {
        // 1. CEK SIAPA YANG SEDANG LOGIN
        $user = Auth::user();
        $hakAksesObj = DB::table('jabatan_status')
            ->join('hak_akses', 'jabatan_status.hak_akses', '=', 'hak_akses.id')
            ->where('jabatan_status.id', $user->jabatan_status)
            ->select('hak_akses.id', 'hak_akses.hak')
            ->first();

        // Tentukan apakah user ini Superadmin (ID 1 atau namanya superadmin)
        $isSuperAdmin = ($hakAksesObj && ($hakAksesObj->id == 1 || strtolower($hakAksesObj->hak) === 'superadmin' || strtolower($hakAksesObj->hak) === 'super admin'));

        $today = now()->format('Y-m-d');
        $monthStart = now()->startOfMonth()->format('Y-m-d');
        $monthEnd = now()->endOfMonth()->format('Y-m-d');

        // 2. BUAT ATURAN FILTER BERDASARKAN ROLE
        $filterPengguna = function ($q) use ($isSuperAdmin) {
            $q->whereHas('jabatanStatus', function ($q2) use ($isSuperAdmin) {
                $q2->whereHas('hakAkses', function ($q3) use ($isSuperAdmin) {
                    if ($isSuperAdmin) {
                        // Superadmin: Lihat SEMUA data kecuali Orang Tua
                        $q3->whereNotIn('hak', ['orang_tua', 'orang tua', 'ortu']);
                    } else {
                        // Admin Biasa: HANYA lihat data Siswa
                        $q3->whereIn('hak', ['siswa', 'general']);
                    }
                });
            });
        };

        // 3. TERAPKAN FILTER KE QUERY HARI INI
        $todayStats = Absensi::whereDate('absen', $today)
            ->whereHas('pengguna', $filterPengguna)
            ->selectRaw('kategori, count(*) as total')
            ->groupBy('kategori')
            ->get()
            ->pluck('total', 'kategori');

        // 4. TERAPKAN FILTER KE QUERY GRAFIK BULANAN
        $monthlyStats = Absensi::whereBetween('absen', [$monthStart, $monthEnd])
            ->whereHas('pengguna', $filterPengguna)
            ->selectRaw('DATE(absen) as tanggal, count(*) as total')
            ->groupBy('tanggal')
            ->orderBy('tanggal')
            ->get();

        return response()->json([
            'today' => [
                'masuk'             => $todayStats[1] ?? 0,
                'istirahat_mulai'   => $todayStats[2] ?? 0,
                'istirahat_selesai' => $todayStats[3] ?? 0,
                'pulang'            => $todayStats[4] ?? 0,
                'total'             => array_sum($todayStats->toArray()),
            ],
            'monthly' => $monthlyStats,
        ]);
    }

    /**
     * SIMPAN ABSENSI DARI MESIN ESP8266 RFID
     */
    public function storeFromMachine(Request $request)
    {
        if ($this->isHariLibur()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Sekolah Libur. Mesin Absensi dinonaktifkan hari ini.'
            ], 403);
        }

        $tag = trim($request->query('tag', $request->input('tag', '')));
        $idmesin = $request->query('idmesin', $request->input('idmesin'));
        $kategori = $request->query('kategori', $request->input('kategori'));

        if (!$tag || !$kategori) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Parameter kurang',
            ], 400);
        }

        $pengguna = Pengguna::where('tag', $tag)
            ->where('aktif', 1)
            ->where('jabatan_status', '!=', 4)
            ->first();

        if (!$pengguna) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Kartu tidak terdaftar',
            ], 404);
        }

        $now = now();
        $jamConfig = $this->getJamConfig();
        $jamTarget = $jamConfig[(string) $kategori] ?? null;

        $zona = DB::table('cabang_gedung')->where('id', $pengguna->cabang_gedung)->value('zona_waktu') ?? '1';
        $seconds = $zona == '1' ? 25200 : ($zona == '2' ? 28800 : 32400);
        $nowLokal = $now->copy()->addSeconds($seconds);

        $absenMaks = null;
        if ($jamTarget) {
            $absenMaks = Carbon::parse($nowLokal->toDateString() . ' ' . $jamTarget)->subSeconds($seconds);
        }

        $absensi = Absensi::create([
            'nomor_induk' => $pengguna->nomor_induk,
            'absen'       => $now,
            'absen_maks'  => $absenMaks,
            'kategori'    => $kategori,
            'idmesin'     => $idmesin,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Absensi tersimpan',
            'nama'    => $pengguna->nama,
            'data'    => $absensi,
        ]);
    }

    /**
     * SIMPAN ABSENSI MANUAL (WEB)
     */
    public function storeSiswa(Request $request)
    {
        if ($this->isHariLibur()) {
            return back()->with('error', 'Hari ini diatur sebagai hari Libur. Anda tidak dapat melakukan presensi.');
        }

        $user = Auth::user();
        $kategori = $request->input('kategori');

        if (!in_array($kategori, [1, 4])) {
            return back()->with('error', 'Kategori presensi tidak valid.');
        }

        $now = now();
        $today = $now->toDateString();

        $sudahAbsen = Absensi::where('nomor_induk', $user->nomor_induk)
            ->whereDate('absen', $today)
            ->where('kategori', $kategori)
            ->exists();

        if ($sudahAbsen) {
            $namaAksi = $kategori == 1 ? 'Masuk' : 'Pulang';
            return back()->with('error', "Anda sudah melakukan Absen $namaAksi hari ini.");
        }

        $jamConfig = $this->getJamConfig();
        $jamTarget = $jamConfig[(string) $kategori] ?? null;
        $absenMaks = $jamTarget ? Carbon::parse($today . ' ' . $jamTarget) : null;

        Absensi::create([
            'nomor_induk' => $user->nomor_induk,
            'absen'       => $now,
            'absen_maks'  => $absenMaks,
            'kategori'    => $kategori,
            'idmesin'     => null,
        ]);

        $namaAksi = $kategori == 1 ? 'Masuk' : 'Pulang';
        return back()->with('success', "Absen $namaAksi berhasil disimpan!");
    }

    /**
     * =====================================
     * HALAMAN ABSENSI GURU & STAFF (KHUSUS SUPERADMIN)
     * =====================================
     */
    public function absensiGuru(Request $request)
    {
        // Pastikan hanya Superadmin yang bisa mengakses
        $user = Auth::user();
        $hakAkses = DB::table('jabatan_status')
            ->where('id', $user->jabatan_status)
            ->value('hak_akses');

        if ($hakAkses !== 1) {
            abort(403, 'Akses Ditolak. Halaman ini khusus Superadmin.');
        }

        $cabangGedungs = CabangGedung::where('aktif', 1)->get();
        
        // Ambil SEMUA data absensi terlebih dahulu (beserta relasinya)
        $query = Absensi::with(['mesin', 'pengguna.cabangGedung']);

        if ($request->filled('awal') && $request->filled('akhir')) {
            $query->whereBetween('absen', [
                $request->awal . ' 00:00:00',
                $request->akhir . ' 23:59:59',
            ]);
        }

        if ($request->filled('cabang_gedung')) {
            $query->whereHas('pengguna', function ($q) use ($request) {
                $q->where('cabang_gedung', $request->cabang_gedung);
            });
        }

        $allAbsensi = $query->orderBy('absen', 'desc')->get();
        $absensiGuru = collect(); // Koleksi khusus untuk menampung Guru/Staff/Internal/Admin

        $jamConfig = $this->getJamConfig();
        $toleransi = (int) ($jamConfig['toleransi'] ?? 5);

        foreach ($allAbsensi as $absensi) {
            if (empty($absensi->absen) || empty($absensi->pengguna)) continue;

            // Dapatkan nama hak akses (huruf kecil)
            $hakAksesUser = strtolower($absensi->pengguna->jabatanStatus->hakAkses->hak ?? '');

            // FILTER MUTLAK: Tendang Siswa dan Orang Tua
            if (in_array($hakAksesUser, ['siswa', 'general', 'orang_tua', 'orang tua', 'ortu'])) {
                continue; // Jika statusnya siswa/orang_tua, lewati.
            }

            $waktuAbsen = Carbon::parse($absensi->absen);
            $cabangModel = $absensi->pengguna->cabangGedung ?? null;
            $zona = $cabangModel->zona_waktu ?? '1';
            $zonaWaktu = $zona == '1' ? 'WIB' : ($zona == '2' ? 'WITA' : 'WIT');
            $seconds = $zona == '1' ? 25200 : ($zona == '2' ? 28800 : 32400);

            $displayWaktu = $waktuAbsen->copy()->addSeconds($seconds);

            $kategoriMap = [
                1 => 'Masuk', 2 => 'Mulai Istirahat',
                3 => 'Selesai Istirahat', 4 => 'Pulang'
            ];
            $absensi->kategori_label = $kategoriMap[$absensi->kategori] ?? '-';
            
            $tgl = $displayWaktu->toDateString();
            $jam = $jamConfig[(string) $absensi->kategori] ?? null;
            $isTepat = false;

            if ($jam) {
                $targetLokal = Carbon::parse($tgl . ' ' . $jam);
                if (in_array($absensi->kategori, [1, 2, 3])) {
                    $isTepat = $displayWaktu->lessThanOrEqualTo($targetLokal->copy()->addMinutes($toleransi));
                } elseif ($absensi->kategori == 4) {
                    $isTepat = $displayWaktu->greaterThanOrEqualTo($targetLokal);
                }
            }

            $absensi->status_label = $isTepat ? 'Tepat Waktu' : 'Terlambat';
            $absensi->warna = $isTepat ? 'text-green-600' : 'text-red-600';
            $absensi->display_absen = $displayWaktu->format('Y-m-d H:i:s') . ' ' . $zonaWaktu;

            $absensiGuru->push($absensi);
        }

        return view('absensi.guru', compact('absensiGuru', 'cabangGedungs'));
    }
}