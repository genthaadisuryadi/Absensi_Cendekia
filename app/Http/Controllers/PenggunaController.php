<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use App\Models\Pengguna;
use Illuminate\Support\Facades\DB;
use App\Imports\PenggunaImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;

class PenggunaController extends Controller
{
    // ====== INDEX ======
    public function index()
    {
        $pengguna = DB::table('pengguna')
            ->leftJoin('jabatan_status', 'pengguna.jabatan_status', '=', 'jabatan_status.id')
            ->leftJoin('cabang_gedung', 'pengguna.cabang_gedung', '=', 'cabang_gedung.id')
            ->select(
                'pengguna.*',
                'jabatan_status.jabatan_status',
                'cabang_gedung.lokasi'
            )
            ->get();

        return view('pengguna.index', compact('pengguna'));
    }

    // ====== IMPORT EXCEL / CSV ======
    public function import(Request $request)
    {
        $request->validate([
            'file_excel' => 'required|mimes:xlsx,csv,xls',
            'tipe_import' => 'required|in:siswa,guru', 
        ], [
            'file_excel.required' => 'Silakan pilih file Excel terlebih dahulu.',
            'file_excel.mimes'    => 'Format file harus berupa .xlsx atau .csv',
        ]);

        try {
            Excel::import(new PenggunaImport, $request->file('file_excel'));
            
            $tipeTeks = $request->tipe_import == 'guru' ? 'Guru/Staff' : 'Siswa';
            
            return redirect()->route('pengguna.index')->with([
                'successImport' => 1,
                'pesanNotif'    => "Data $tipeTeks berhasil diimpor dari Excel.",
                'nama'          => "Massal dari Excel ($tipeTeks)"
            ]);
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['file_excel' => 'Gagal mengimpor data: ' . $e->getMessage()]);
        }
    }

    // ====== CREATE ======
    public function create()
    {
        $jabatans = DB::table('jabatan_status')
            ->leftJoin('hak_akses', 'jabatan_status.hak_akses', '=', 'hak_akses.id')
            ->select('jabatan_status.*', 'hak_akses.hak')
            ->get();

        $lokasis = DB::table('cabang_gedung')->get();

        $siswaList = DB::table('pengguna')
            ->join('jabatan_status', 'pengguna.jabatan_status', '=', 'jabatan_status.id')
            ->join('hak_akses', 'jabatan_status.hak_akses', '=', 'hak_akses.id')
            ->whereIn('hak_akses.hak', ['general', 'siswa'])
            ->orWhere('pengguna.jabatan_status', 3)
            ->select('pengguna.nomor_induk', 'pengguna.nama')
            ->orderBy('pengguna.nama')
            ->get();

        return view('pengguna.create', compact('jabatans', 'lokasis', 'siswaList'));
    }

    // ====== STORE ======
    public function store(Request $request)
    {
        $hakAkses = DB::table('jabatan_status')
            ->join('hak_akses', 'jabatan_status.hak_akses', '=', 'hak_akses.id')
            ->where('jabatan_status.id', $request->jabatan_status)
            ->value('hak_akses.hak');

        $roleNormalized = str_replace(' ', '_', strtolower(trim($hakAkses ?? '')));
        $isOrtu  = in_array($roleNormalized, ['orang_tua', 'ortu'], true) || (int) $request->jabatan_status === 4;
        $isSiswa = in_array($roleNormalized, ['general', 'siswa'], true) || (int) $request->jabatan_status === 3;

        if ($isOrtu && !$request->filled('nomor_induk')) {
            do {
                $generatedId = (string) random_int(800000, 899999);
            } while (DB::table('pengguna')->where('nomor_induk', $generatedId)->exists());

            $request->merge(['nomor_induk' => $generatedId]);
        }

        // PERBAIKAN: Semua peran KECUALI Orang Tua boleh memiliki Tag RFID
        $tagValue   = (!$isOrtu && $request->filled('tag')) ? trim($request->tag) : '';
        // Kelas tetap khusus Siswa
        $kelasValue = ($isSiswa && $request->filled('kelas')) ? trim($request->kelas) : null;

        $request->validate([
            'nomor_induk'    => 'required|unique:pengguna,nomor_induk',
            'nama'           => 'required|string|max:255',
            'kelas'          => 'nullable|string|max:100',
            'tag'            => $tagValue !== '' ? ['nullable', Rule::unique('pengguna', 'tag')] : ['nullable'],
            'jabatan_status' => 'required|exists:jabatan_status,id',
            'cabang_gedung'  => 'required|exists:cabang_gedung,id',
            'password'       => 'required|min:8|confirmed',
            'siswa_ids'      => $isOrtu ? 'required|array|min:1' : 'nullable|array',
            'siswa_ids.*'    => 'exists:pengguna,nomor_induk',
            'foto_profil'    => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', 
        ], [
            'tag.unique'         => 'Kartu RFID (Tag) ini sudah terdaftar pada pengguna lain.',
            'siswa_ids.required' => 'Silakan pilih minimal 1 Siswa (Anak) untuk akun Orang Tua ini.',
            'foto_profil.image'  => 'File foto profil harus berupa gambar.',
            'foto_profil.mimes'  => 'Format gambar harus jpeg, png, jpg, atau gif.',
            'foto_profil.max'    => 'Ukuran gambar maksimal 2MB.',
        ]);

        $fotoPath = null;
        if ($request->hasFile('foto_profil')) {
            $fotoPath = $request->file('foto_profil')->store('foto_profil', 'public');
        }

        $pengguna = Pengguna::create([
            'nomor_induk'    => $request->nomor_induk,
            'nama'           => $request->nama,
            'kelas'          => $kelasValue,
            'tag'            => $tagValue,
            'jabatan_status' => $request->jabatan_status,
            'cabang_gedung'  => $request->cabang_gedung,
            'password'       => Hash::make($request->password),
            'aktif'          => 1,
            'foto_profil'    => $fotoPath,
        ]);

        if ($isOrtu && $request->filled('siswa_ids')) {
            $pengguna->anak()->sync($request->siswa_ids);
        }

        return redirect()->route('pengguna.index')->with([
            'successAdd' => 1,
            'pesanNotif' => 'Data pengguna berhasil ditambahkan.',
            'nama'       => $request->nama,
        ]);
    }

    // ====== EDIT: Menampilkan Form Edit ======
    public function edit($id)
    {
        $user = Pengguna::where('nomor_induk', $id)->firstOrFail();

        $jabatans = DB::table('jabatan_status')
            ->leftJoin('hak_akses', 'jabatan_status.hak_akses', '=', 'hak_akses.id')
            ->select('jabatan_status.*', 'hak_akses.hak')
            ->get();

        $lokasis = DB::table('cabang_gedung')->get();

        $siswaList = DB::table('pengguna')
            ->join('jabatan_status', 'pengguna.jabatan_status', '=', 'jabatan_status.id')
            ->join('hak_akses', 'jabatan_status.hak_akses', '=', 'hak_akses.id')
            ->where(function ($q) {
                $q->whereIn('hak_akses.hak', ['general', 'siswa'])
                  ->orWhere('pengguna.jabatan_status', 3);
            })
            ->where('pengguna.nomor_induk', '!=', $user->nomor_induk)
            ->select('pengguna.nomor_induk', 'pengguna.nama')
            ->orderBy('pengguna.nama')
            ->get();

        $selectedSiswaIds = DB::table('orang_tua_siswa')
            ->where('orang_tua_id', $user->nomor_induk)
            ->pluck('siswa_id')
            ->toArray();

        return view('pengguna.edit', compact('user', 'jabatans', 'lokasis', 'siswaList', 'selectedSiswaIds'));
    }

    // ====== UPDATE: Memperbarui Data ======
    public function update(Request $request, $id)
    {
        $user = Pengguna::where('nomor_induk', $id)->firstOrFail();

        $hakAkses = DB::table('jabatan_status')
            ->join('hak_akses', 'jabatan_status.hak_akses', '=', 'hak_akses.id')
            ->where('jabatan_status.id', $request->jabatan_status)
            ->value('hak_akses.hak');

        $roleNormalized = str_replace(' ', '_', strtolower(trim($hakAkses ?? '')));
        $isOrtu  = in_array($roleNormalized, ['orang_tua', 'ortu'], true) || (int) $request->jabatan_status === 4;
        $isSiswa = in_array($roleNormalized, ['general', 'siswa'], true) || (int) $request->jabatan_status === 3;

        if ($isOrtu || !$request->filled('nomor_induk')) {
            $request->merge(['nomor_induk' => $user->nomor_induk]);
        }

        // PERBAIKAN: Semua peran KECUALI Orang Tua boleh memiliki Tag RFID
        $tagValue   = (!$isOrtu && $request->filled('tag')) ? trim($request->tag) : '';
        // Kelas tetap khusus Siswa
        $kelasValue = ($isSiswa && $request->filled('kelas')) ? trim($request->kelas) : null;

        $request->validate([
            'nomor_induk'    => ['required', Rule::unique('pengguna', 'nomor_induk')->ignore($user->nomor_induk, 'nomor_induk')],
            'nama'           => 'required|string|max:255',
            'kelas'          => 'nullable|string|max:100',
            'tag'            => $tagValue !== '' ? ['nullable', Rule::unique('pengguna', 'tag')->ignore($user->nomor_induk, 'nomor_induk')] : ['nullable'],
            'jabatan_status' => 'required|exists:jabatan_status,id',
            'cabang_gedung'  => 'required|exists:cabang_gedung,id',
            'password'       => 'nullable|min:8|confirmed',
            'siswa_ids'      => $isOrtu ? 'required|array|min:1' : 'nullable|array',
            'siswa_ids.*'    => 'exists:pengguna,nomor_induk',
            'foto_profil'    => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', 
        ], [
            'tag.unique'         => 'Kartu RFID (Tag) ini sudah terdaftar pada pengguna lain.',
            'siswa_ids.required' => 'Silakan pilih minimal 1 Siswa (Anak) untuk akun Orang Tua ini.',
            'foto_profil.image'  => 'File foto profil harus berupa gambar.',
            'foto_profil.mimes'  => 'Format gambar harus jpeg, png, jpg, atau gif.',
            'foto_profil.max'    => 'Ukuran gambar maksimal 2MB.',
        ]);

        $dataUpdate = [
            'nomor_induk'    => $request->nomor_induk,
            'nama'           => $request->nama,
            'kelas'          => $kelasValue,
            'tag'            => $tagValue,
            'jabatan_status' => $request->jabatan_status,
            'cabang_gedung'  => $request->cabang_gedung,
        ];

        if ($request->hasFile('foto_profil')) {
            if ($user->foto_profil && Storage::disk('public')->exists($user->foto_profil)) {
                Storage::disk('public')->delete($user->foto_profil);
            }
            $dataUpdate['foto_profil'] = $request->file('foto_profil')->store('foto_profil', 'public');
        } elseif ($request->filled('remove_foto') && $request->remove_foto == '1') {
             if ($user->foto_profil && Storage::disk('public')->exists($user->foto_profil)) {
                Storage::disk('public')->delete($user->foto_profil);
            }
            $dataUpdate['foto_profil'] = null;
        }

        if ($request->filled('password')) {
            $dataUpdate['password'] = Hash::make($request->password);
        }

        $user->update($dataUpdate);

        if ($isOrtu) {
            $user->anak()->sync($request->input('siswa_ids', []));
        } else {
            $user->anak()->detach();
        }

        return redirect()->route('pengguna.index')->with([
            'successEdit' => 1,
            'pesanNotif'  => 'Data pengguna berhasil diperbarui.',
            'nama'        => $request->nama,
        ]);
    }

    // ====== TOGGLE STATUS: Aktif/Non-Aktif ======
    public function toggleStatus($id)
    {
        $user = Pengguna::where('nomor_induk', $id)->firstOrFail();
        $newStatus = ($user->aktif == 1) ? 0 : 1;

        $user->update(['aktif' => $newStatus]);

        return redirect()->back()->with([
            'successEdit'   => 1,
            'pesanNotif'    => 'Status pengguna berhasil diubah.',
            'nama'          => $user->nama,
            'aktifText'     => ($newStatus == 1 ? 'aktif' : 'non-aktif'),
        ]);
    }

    // ====== DESTROY: Hapus Satu Data ======
    public function destroy($id)
    {
        $user = Pengguna::where('nomor_induk', $id)->firstOrFail();

        if ($user->foto_profil && Storage::disk('public')->exists($user->foto_profil)) {
            Storage::disk('public')->delete($user->foto_profil);
        }

        $user->delete();

        return redirect()
            ->route('pengguna.index')
            ->with([
                'successDelete' => true,
                'pesanNotif'    => 'Data pengguna berhasil dihapus.',
                'nama'          => $user->nama
            ]);
    }

    // ============================================
    // CEK KARTU RFID (VIA AJAX)
    // ============================================
    public function cekTag(Request $request)
    {
        $tag = $request->query('tag');
        
        if (!$tag) {
            return response()->json(['success' => false, 'message' => 'Tag RFID kosong']);
        }

        $pengguna = Pengguna::where('tag', $tag)->first();

        if ($pengguna) {
            return response()->json([
                'success'     => true,
                'nama'        => $pengguna->nama,
                'kelas'       => $pengguna->kelas ?? 'Tidak ada kelas',
                'nomor_induk' => $pengguna->nomor_induk
            ]);
        } else {
            return response()->json(['success' => false, 'message' => 'Kartu RFID ini belum terdaftar.']);
        }
    }

    // ============================================
    // HAPUS MULTI DATA (BULK DELETE)
    // ============================================
    public function bulkDestroy(Request $request)
    {
        $ids = $request->input('ids');

        if (empty($ids)) {
            return redirect()->back()->withErrors(['message' => 'Tidak ada data pengguna yang dipilih untuk dihapus.']);
        }

        try {
            $users = Pengguna::whereIn('nomor_induk', $ids)->get();

            foreach ($users as $user) {
                if ($user->foto_profil && Storage::disk('public')->exists($user->foto_profil)) {
                    Storage::disk('public')->delete($user->foto_profil);
                }
                $user->delete();
            }

            return redirect()->route('pengguna.index')->with([
                'successDelete' => true,
                'pesanNotif'    => 'Data massal pengguna berhasil dihapus.',
                'nama'          => count($ids) . ' Pengguna Terpilih'
            ]);
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['message' => 'Gagal menghapus data massal: ' . $e->getMessage()]);
        }
    }
}