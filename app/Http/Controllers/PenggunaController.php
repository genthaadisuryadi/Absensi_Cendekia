<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use App\Models\Pengguna;
use Illuminate\Support\Facades\DB;

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

    // ====== CREATE ======
    public function create()
    {
        $jabatans = DB::table('jabatan_status')
            ->leftJoin('hak_akses', 'jabatan_status.hak_akses', '=', 'hak_akses.id')
            ->select('jabatan_status.*', 'hak_akses.hak')
            ->get();

        $lokasis = DB::table('cabang_gedung')->get();

        // Ambil daftar pengguna dengan role Siswa untuk dipilih oleh Orang Tua
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
        $isSiswa = in_array($roleNormalized, ['general', 'siswa'], true) || (int) $request->jabatan_status === 3;
        $isOrtu  = in_array($roleNormalized, ['orang_tua'], true) || (int) $request->jabatan_status === 4;

        // Jika Orang Tua, buat nomor_induk otomatis di belakang layar karena tidak diinputkan
        if ($isOrtu && !$request->filled('nomor_induk')) {
            do {
                $generatedId = (string) random_int(800000, 899999);
            } while (DB::table('pengguna')->where('nomor_induk', $generatedId)->exists());

            $request->merge(['nomor_induk' => $generatedId]);
        }

        // Kolom 'tag' di MySQL bersifat NOT NULL, isi '' jika bukan Siswa atau kosong
        $tagValue = ($isSiswa && $request->filled('tag')) ? trim($request->tag) : '';

        $request->validate([
            'nomor_induk'    => 'required|unique:pengguna,nomor_induk',
            'nama'           => 'required|string|max:255',
            'tag'            => $tagValue !== '' ? ['nullable', Rule::unique('pengguna', 'tag')] : ['nullable'],
            'jabatan_status' => 'required|exists:jabatan_status,id',
            'cabang_gedung'  => 'required|exists:cabang_gedung,id',
            'password'       => 'required|min:8|confirmed',
            'siswa_ids'      => $isOrtu ? 'required|array|min:1' : 'nullable|array',
            'siswa_ids.*'    => 'exists:pengguna,nomor_induk',
        ], [
            'tag.unique'         => 'Kartu RFID (Tag) ini sudah terdaftar pada pengguna lain.',
            'siswa_ids.required' => 'Silakan pilih minimal 1 Siswa (Anak) untuk akun Orang Tua ini.',
        ]);

        $pengguna = Pengguna::create([
            'nomor_induk'    => $request->nomor_induk,
            'nama'           => $request->nama,
            'tag'            => $tagValue,
            'jabatan_status' => $request->jabatan_status,
            'cabang_gedung'  => $request->cabang_gedung,
            'password'       => Hash::make($request->password),
            'aktif'          => 1,
        ]);

        if ($isOrtu && $request->filled('siswa_ids')) {
            $pengguna->anak()->sync($request->siswa_ids);
        }

        return redirect()->route('pengguna.index')->with([
            'successAdd' => 1,
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
        $isSiswa = in_array($roleNormalized, ['general', 'siswa'], true) || (int) $request->jabatan_status === 3;
        $isOrtu  = in_array($roleNormalized, ['orang_tua'], true) || (int) $request->jabatan_status === 4;

        // Jika Orang Tua, pertahankan nomor_induk yang sudah ada tanpa perlu input dari form
        if ($isOrtu || !$request->filled('nomor_induk')) {
            $request->merge(['nomor_induk' => $user->nomor_induk]);
        }

        // Kolom 'tag' di MySQL bersifat NOT NULL, isi '' jika bukan Siswa atau kosong
        $tagValue = ($isSiswa && $request->filled('tag')) ? trim($request->tag) : '';

        $request->validate([
            'nomor_induk'    => ['required', Rule::unique('pengguna', 'nomor_induk')->ignore($user->nomor_induk, 'nomor_induk')],
            'nama'           => 'required|string|max:255',
            'tag'            => $tagValue !== '' ? ['nullable', Rule::unique('pengguna', 'tag')->ignore($user->nomor_induk, 'nomor_induk')] : ['nullable'],
            'jabatan_status' => 'required|exists:jabatan_status,id',
            'cabang_gedung'  => 'required|exists:cabang_gedung,id',
            'password'       => 'nullable|min:8|confirmed',
            'siswa_ids'      => $isOrtu ? 'required|array|min:1' : 'nullable|array',
            'siswa_ids.*'    => 'exists:pengguna,nomor_induk',
        ], [
            'tag.unique'         => 'Kartu RFID (Tag) ini sudah terdaftar pada pengguna lain.',
            'siswa_ids.required' => 'Silakan pilih minimal 1 Siswa (Anak) untuk akun Orang Tua ini.',
        ]);

        $dataUpdate = [
            'nomor_induk'    => $request->nomor_induk,
            'nama'           => $request->nama,
            'tag'            => $tagValue,
            'jabatan_status' => $request->jabatan_status,
            'cabang_gedung'  => $request->cabang_gedung,
        ];

        if ($request->filled('password')) {
            $dataUpdate['password'] = Hash::make($request->password);
        }

        $user->update($dataUpdate);

        // Sinkronisasi relasi anak jika role adalah Orang Tua
        if ($isOrtu) {
            $user->anak()->sync($request->input('siswa_ids', []));
        } else {
            $user->anak()->detach();
        }

        return redirect()->route('pengguna.index')->with([
            'successEdit' => 1,
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
            'successDelete' => 1,
            'nama'          => $user->nama,
            'aktifText'     => ($newStatus == 1 ? 'aktif' : 'non-aktif'),
        ]);
    }

    public function destroy($id)
    {
        Pengguna::where('nomor_induk', $id)->delete();

        return redirect()
            ->route('pengguna.index')
            ->with('successDelete', true);
    }
}