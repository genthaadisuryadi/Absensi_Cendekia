<?php

namespace App\Http\Controllers;

use App\Models\Cuti;
use App\Models\Pengguna;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CutiController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Cuti::with(['pengguna', 'penyetuju'])
            ->orderByRaw("CASE WHEN status_persetujuan = 'Pending' THEN 0 ELSE 1 END")
            ->orderBy('tanggal', 'desc');

        // Filter berdasarkan tanggal jika ada
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('tanggal', [
                $request->start_date,
                $request->end_date
            ]);
        }

        // Filter berdasarkan nomor induk
        if ($request->filled('nomor_induk')) {
            $query->where('nomor_induk', $request->nomor_induk);
        }

        // Filter berdasarkan status persetujuan
        $status = $request->get('status');
        if (in_array($status, [Cuti::PENDING, Cuti::DISETUJUI, Cuti::DITOLAK], true)) {
            $query->where('status_persetujuan', $status);
        } else {
            $status = null;
        }

        // DataTable di view yang mengurus paging, jadi semua baris dikirim
        $cuti = $query->get();

        $jumlah = [
            'semua'     => Cuti::count(),
            'pending'   => Cuti::pending()->count(),
            'disetujui' => Cuti::disetujui()->count(),
            'ditolak'   => Cuti::where('status_persetujuan', Cuti::DITOLAK)->count(),
        ];

        // Ambil semua pengguna aktif untuk dropdown
        $penggunaList = Pengguna::where('aktif', '1')
            ->orderBy('nama')
            ->get();

        return view('cuti.index', compact('cuti', 'penggunaList', 'jumlah', 'status'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $pengguna = Pengguna::where('aktif', '1')
            ->orderBy('nama')
            ->get();
        return view('cuti.create', compact('pengguna'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nomor_induk' => 'required',
            'tanggal'     => 'required|date|after_or_equal:today'
        ], [
            'nomor_induk.required'     => 'Nomor induk wajib diisi.',
            'nomor_induk.exists'       => 'Nomor induk tidak ditemukan dalam sistem.',
            'tanggal.required'         => 'Tanggal cuti wajib diisi.',
            'tanggal.date'             => 'Format tanggal tidak valid.',
            'tanggal.after_or_equal'   => 'Tanggal cuti tidak boleh kurang dari hari ini.'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput()
                ->with('error', 'Validasi gagal. Silakan periksa data yang dimasukkan.');
        }

        // Cek duplikasi cuti pada tanggal yang sama
        $existingCuti = Cuti::where('nomor_induk', $request->nomor_induk)
            ->where('tanggal', $request->tanggal)
            ->first();

        if ($existingCuti) {
            return redirect()->back()
                ->with('error', 'Karyawan sudah memiliki cuti pada tanggal tersebut.')
                ->withInput();
        }

        try {
            DB::beginTransaction();
            
            // Cuti yang diinput langsung oleh admin otomatis Disetujui
            $cuti = Cuti::create([
                'nomor_induk'        => $request->nomor_induk,
                'tanggal'            => $request->tanggal,
                'tanggal_mulai'      => $request->tanggal,
                'tanggal_selesai'    => $request->tanggal,
                'kategori'           => 'Cuti',
                'status_persetujuan' => Cuti::DISETUJUI,
                'disetujui_oleh'     => auth()->user()?->nomor_induk,
                'tanggal_keputusan'  => now(),
            ]);

            // Ambil nama pengguna untuk pesan sukses
            $namaPengguna = Pengguna::where('nomor_induk', $request->nomor_induk)
                ->value('nama');

            DB::commit();

            return redirect()->route('cuti.index')
                ->with('success', "Cuti untuk <b>$namaPengguna</b> berhasil ditambahkan.");
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()->back()
                ->with('error', 'Terjadi kesalahan sistem: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $cuti = Cuti::with('pengguna')->findOrFail($id);
        $pengguna = Pengguna::where('aktif', '1')
            ->orderBy('nama')
            ->get();
        
        return view('cuti.edit', compact('cuti', 'pengguna'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'nomor_induk' => 'required|exists:pengguna,nomor_induk',
            'tanggal'     => 'required|date'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $cuti = Cuti::findOrFail($id);
        $oldNomorInduk = $cuti->nomor_induk;
        $oldTanggal = $cuti->tanggal;

        // Cek duplikasi HANYA JIKA nomor_induk atau tanggal BERUBAH
        if ($request->nomor_induk != $oldNomorInduk || $request->tanggal != $oldTanggal) {
            $existingCuti = Cuti::where('nomor_induk', $request->nomor_induk)
                ->where('tanggal', $request->tanggal)
                ->where('id', '!=', $id)
                ->first();

            if ($existingCuti) {
                return redirect()->back()
                    ->with('error', 'Karyawan sudah memiliki cuti pada tanggal tersebut.')
                    ->withInput();
            }
        }

        try {
            DB::beginTransaction();
            
            $data = [
                'nomor_induk' => $request->nomor_induk,
                'tanggal'     => $request->tanggal
            ];

            // Cuti buatan admin (bukan pengajuan siswa): rentang ikut tanggal baru
            if (empty($cuti->kategori) || $cuti->kategori === 'Cuti') {
                $data['tanggal_mulai'] = $request->tanggal;
                $data['tanggal_selesai'] = $request->tanggal;
            }

            $cuti->update($data);

            // Ambil nama pengguna baru untuk pesan sukses
            $namaPenggunaBaru = Pengguna::where('nomor_induk', $request->nomor_induk)
                ->value('nama');
            
            $namaPenggunaLama = Pengguna::where('nomor_induk', $oldNomorInduk)
                ->value('nama');

            DB::commit();

            $message = "Cuti ";
            
            if ($oldNomorInduk != $request->nomor_induk) {
                $message .= "berhasil dipindahkan dari <b>$namaPenggunaLama</b> ke <b>$namaPenggunaBaru</b>";
            } else {
                $message .= "untuk <b>$namaPenggunaBaru</b> berhasil diubah";
            }
            
            if ($oldTanggal != $request->tanggal) {
                $message .= " (tanggal diubah)";
            }

            return redirect()->route('cuti.index')
                ->with('success', $message . ".");
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()->back()
                ->with('error', 'Terjadi kesalahan: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $cuti = Cuti::with('pengguna')->findOrFail($id);
        
        $nomorInduk = $cuti->nomor_induk;
        $tanggal = \Carbon\Carbon::parse($cuti->tanggal)->format('d-m-Y');
        $namaPengguna = $cuti->pengguna ? $cuti->pengguna->nama : 'Tidak Diketahui';
        
        $cuti->delete();

        return redirect()->route('cuti.index')
            ->with('warning', "Cuti untuk <b>$namaPengguna</b> tanggal <b>$tanggal</b> telah dibatalkan.");
    }

    /**
     * API untuk cek cuti berdasarkan tanggal (untuk integrasi absensi)
     */
    public function checkCuti(Request $request)
    {
        $request->validate([
            'nomor_induk' => 'required|string',
            'tanggal'     => 'required|date'
        ]);

        $cuti = in_array(
            Carbon::parse($request->tanggal)->format('Y-m-d'),
            Cuti::tanggalDisetujui($request->nomor_induk, $request->tanggal, $request->tanggal),
            true
        );

        return response()->json([
            'is_cuti' => $cuti,
            'message' => $cuti ? 'Karyawan sedang cuti' : 'Karyawan tidak cuti'
        ]);
    }

    // ============================================
    // PERSETUJUAN PENGAJUAN SISWA (ADMIN)
    // ============================================

    private function pastikanAdmin()
    {
        $user = auth()->user();
        
        // Cek langsung dari relasi hak akses atau hak akses teks di database
        $hak = $user?->jabatanStatus?->hakAkses?->hak;

        // Diperbarui agar mendeteksi superadmin dan admin
        abort_unless($user && in_array(strtolower($hak), ['superadmin', 'admin', 'nusabot', 'full'], true), 403, 'Hanya admin yang dapat memproses pengajuan.');

        return $user;
    }

    public function setujui(Request $request, string $id)
    {
        $admin = $this->pastikanAdmin();
        $cuti = Cuti::with('pengguna')->findOrFail($id);

        $cuti->update([
            'status_persetujuan' => Cuti::DISETUJUI,
            'disetujui_oleh'     => $admin->nomor_induk,
            'tanggal_keputusan'  => now(),
            'catatan_admin'      => null,
        ]);

        $nama = $cuti->pengguna->nama ?? $cuti->nomor_induk;

        return redirect()->route('cuti.index', $request->only('status'))
            ->with('success', "Pengajuan <b>{$nama}</b> telah disetujui.");
    }

    public function tolak(Request $request, string $id)
    {
        $admin = $this->pastikanAdmin();

        $validator = Validator::make($request->all(), [
            'catatan_admin' => 'required|string|max:500',
        ], [
            'catatan_admin.required' => 'Alasan penolakan wajib diisi.',
            'catatan_admin.max'      => 'Alasan penolakan maksimal 500 karakter.',
        ]);

        if ($validator->fails()) {
            return redirect()->route('cuti.index', $request->only('status'))
                ->with('error', $validator->errors()->first());
        }

        $cuti = Cuti::with('pengguna')->findOrFail($id);

        $cuti->update([
            'status_persetujuan' => Cuti::DITOLAK,
            'disetujui_oleh'     => $admin->nomor_induk,
            'tanggal_keputusan'  => now(),
            'catatan_admin'      => $request->catatan_admin,
        ]);

        $nama = $cuti->pengguna->nama ?? $cuti->nomor_induk;

        return redirect()->route('cuti.index', $request->only('status'))
            ->with('warning', "Pengajuan <b>{$nama}</b> telah ditolak.");
    }

    // ============================================
    // FUNGSI KHUSUS SISWA (DASHBOARD SISWA)
    // ============================================

    public function izinSiswaIndex(Request $request)
    {
        $user = auth()->user();
        
        $cuti = Cuti::where('nomor_induk', $user->nomor_induk)
            ->orderBy('tanggal', 'desc')
            ->paginate(10);
            
        return view('cuti.siswa_index', compact('cuti'));
    }

    public function izinSiswaCreate()
    {
        return view('cuti.siswa_create');
    }

    public function izinSiswaStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tanggal_mulai'   => 'required|date|after_or_equal:today',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'kategori'        => 'required|in:Izin,Sakit',
            'alasan'          => 'required|string|max:1000',
            'bukti_file'      => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048'
        ], [
            'tanggal_mulai.required'         => 'Tanggal mulai wajib diisi.',
            'tanggal_mulai.after_or_equal'   => 'Tanggal mulai tidak boleh sebelum hari ini.',
            'tanggal_selesai.after_or_equal' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
            'kategori.required'              => 'Kategori wajib dipilih.',
            'alasan.required'                => 'Alasan/Keterangan wajib diisi.',
            'bukti_file.mimes'               => 'Format file tidak didukung. Gunakan PDF, JPG, atau PNG.',
            'bukti_file.max'                 => 'Ukuran file maksimal 2MB.'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $user = auth()->user();

        // Cegah pengajuan yang bertabrakan dengan pengajuan lain yang masih aktif
        $bentrok = Cuti::byNomorInduk($user->nomor_induk)
            ->where('status_persetujuan', '!=', Cuti::DITOLAK)
            ->whereDate('tanggal_mulai', '<=', $request->tanggal_selesai)
            ->whereDate('tanggal_selesai', '>=', $request->tanggal_mulai)
            ->exists();

        if ($bentrok) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Sudah ada pengajuan izin/sakit pada rentang tanggal tersebut.');
        }

        // Handle File Upload
        $path = null;
        if ($request->hasFile('bukti_file')) {
            $path = $request->file('bukti_file')->store('bukti_izin', 'public');
        }

        // Simpan permohonan
        Cuti::create([
            'nomor_induk'        => $user->nomor_induk,
            'tanggal'            => $request->tanggal_mulai, // default legacy column
            'tanggal_mulai'      => $request->tanggal_mulai,
            'tanggal_selesai'    => $request->tanggal_selesai,
            'kategori'           => $request->kategori,
            'alasan'             => $request->alasan,
            'bukti_file'         => $path,
            'status_persetujuan' => Cuti::PENDING
        ]);

        return redirect()->route('siswa.izin.index')
            ->with('success', 'Pengajuan izin/sakit berhasil dikirim dan menunggu persetujuan.');
    }
}