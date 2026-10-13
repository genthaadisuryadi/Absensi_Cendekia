<?php

namespace App\Http\Controllers;

use App\Models\Absensi;
use App\Models\CabangGedung;
use App\Models\Pengguna;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AbsensiController extends Controller
{
    private $defaultJam = [
        '1' => '08:00:00', // Masuk
        '2' => '12:00:00', // Mulai Istirahat
        '3' => '13:00:00', // Selesai Istirahat
        '4' => '17:00:00', // Pulang
    ];

    /**
     * LIST + FILTER ABSENSI
     */
    public function index(Request $request)
    {
        $cabangGedungs = CabangGedung::where('aktif', 1)->get();

        // Hanya tampilkan absensi milik pengguna yang bukan Orang Tua (jabatan_status != 4)
        $query = Absensi::with(['mesin', 'pengguna.cabangGedung'])
            ->whereHas('pengguna', function ($q) {
                $q->where('jabatan_status', '!=', 4);
            });

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

        $absensis = $query->orderBy('absen', 'desc')->get();

        foreach ($absensis as $absensi) {
            $absensi->status = null;
            $absensi->status_label = null;
            $absensi->warna = 'text-gray-700';
            $absensi->display_absen = null;
            $absensi->display_batas = null;
            $absensi->selisih_menit = null;

            if (empty($absensi->absen)) {
                continue;
            }

            $waktuAbsen = Carbon::parse($absensi->absen);

            switch ((string) $absensi->kategori) {
                case '1': $absensi->kategori_label = 'Masuk'; break;
                case '2': $absensi->kategori_label = 'Mulai Istirahat'; break;
                case '3': $absensi->kategori_label = 'Selesai Istirahat'; break;
                case '4': $absensi->kategori_label = 'Pulang'; break;
                default:  $absensi->kategori_label = '-'; break;
            }

            $tgl = $waktuAbsen->toDateString();
            $jam = $this->defaultJam[(string) $absensi->kategori] ?? null;
            $isTepat = false;
            $target = null;

            if ($jam) {
                $target = Carbon::parse($tgl . ' ' . $jam);

                switch ((string) $absensi->kategori) {
                    case '1':
                        $isTepat = $waktuAbsen->lessThanOrEqualTo($target->copy()->addMinutes(15));
                        break;
                    case '2':
                    case '3':
                        $isTepat = $waktuAbsen->lessThanOrEqualTo($target->copy()->addMinutes(15));
                        break;
                    case '4':
                        $isTepat = $waktuAbsen->greaterThanOrEqualTo($target);
                        break;
                }
            }

            $absensi->status = $isTepat ? 'tepat_waktu' : 'telat';
            $absensi->status_label = $isTepat ? 'Tepat Waktu' : 'Terlambat';
            $absensi->warna = $isTepat ? 'text-green-600' : 'text-red-600';

            $cabangModel = $absensi->pengguna->cabangGedung ?? CabangGedung::find($absensi->pengguna->cabang_gedung);
            $zona = $cabangModel->zona_waktu ?? '1';
            $zonaWaktu = $zona == '1' ? 'WIB' : ($zona == '2' ? 'WITA' : 'WIT');
            $seconds = $zona == '1' ? 25200 : ($zona == '2' ? 28800 : 32400);

            $displayWaktu = $waktuAbsen->copy()->addSeconds($seconds);
            $displayTarget = $target ? $target->copy()->addSeconds($seconds) : null;
            $absensi->display_absen = $displayWaktu->format('Y-m-d H:i:s') . ' ' . $zonaWaktu;
            $absensi->display_batas = $displayTarget ? $displayTarget->format('Y-m-d H:i:s') : null;
            $absensi->selisih_menit = $target ? round(abs($waktuAbsen->diffInSeconds($target)) / 60) : null;
        }

        if ($request->wantsJson()) {
            return response()->json($absensis);
        }

        return view('absensi.index', compact('absensis', 'cabangGedungs'));
    }

    /**
     * DETAIL ABSENSI
     */
    public function show($id)
    {
        $absensi = Absensi::with(['mesin', 'pengguna.cabangGedung'])->findOrFail($id);
        return response()->json($absensi);
    }

    /**
     * SIMPAN ABSENSI MANUAL
     */
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

    /**
     * API DASHBOARD (Statistik JSON)
     */
    public function dashboard(Request $request)
    {
        $today = now()->format('Y-m-d');

        $todayStats = Absensi::whereDate('absen', $today)
            ->selectRaw('kategori, count(*) as total')
            ->groupBy('kategori')
            ->get()
            ->pluck('total', 'kategori');

        $monthStart = now()->startOfMonth()->format('Y-m-d');
        $monthEnd = now()->endOfMonth()->format('Y-m-d');

        $monthlyStats = Absensi::whereBetween('absen', [$monthStart, $monthEnd])
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
        $tag = trim($request->query('tag', $request->input('tag', '')));
        $idmesin = $request->query('idmesin', $request->input('idmesin'));
        $kategori = $request->query('kategori', $request->input('kategori'));

        if (!$tag || !$kategori) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Parameter kurang',
            ], 400);
        }

        // Cari pengguna aktif yang bukan Orang Tua (jabatan_status != 4)
        $pengguna = Pengguna::where('tag', $tag)
            ->where('aktif', 1)
            ->where('jabatan_status', '!=', 4)
            ->first();

        if (!$pengguna) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Kartu tidak terdaftar untuk Siswa',
            ], 404);
        }

        $now = now();
        $jamTarget = $this->defaultJam[(string) $kategori] ?? null;
        $absenMaks = $jamTarget ? Carbon::parse($now->toDateString() . ' ' . $jamTarget) : null;

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

    public function storeSiswa(Request $request)
    {
        $user = \Illuminate\Support\Facades\Auth::user();
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

        $jamTarget = $this->defaultJam[(string) $kategori] ?? null;
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
}