<?php

namespace App\Http\Controllers;

use App\Models\CabangGedung;
use Illuminate\Http\Request;

class CabangGedungController extends Controller
{
    public function index()
    {
        // Ubah nama variabel agar sesuai dengan yang dibaca oleh view index.blade.php
        $cabangGedungs = CabangGedung::orderBy('lokasi')->get();
        return view('cabang_gedung.index', compact('cabangGedungs'));
    }

    public function create()
    {
        return view('cabang_gedung.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'lokasi' => 'required',
            'jam_masuk' => 'required',
            'jam_pulang' => 'required',
            'istirahat_mulai' => 'required',
            'istirahat_selesai' => 'required',
            'hari_libur' => 'nullable|array',
            'zona_waktu' => 'required',
        ]);

        CabangGedung::create([
            'lokasi' => $request->lokasi,
            'jam_masuk' => $request->jam_masuk,
            'jam_pulang' => $request->jam_pulang,
            'istirahat_mulai' => $request->istirahat_mulai,
            'istirahat_selesai' => $request->istirahat_selesai,
            'hari_libur' => implode(',', $request->hari_libur ?? []),
            'zona_waktu' => $request->zona_waktu,
            'aktif' => 1,
        ]);

        return redirect()->route('cabang-gedung.index')
            ->with('success', 'Cabang berhasil ditambahkan');
    }

    public function edit($id)
    {
        $cabang = CabangGedung::findOrFail($id);
        return view('cabang_gedung.edit', compact('cabang'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'lokasi' => 'required',
            'jam_masuk' => 'required',
            'jam_pulang' => 'required',
            'istirahat_mulai' => 'required',
            'istirahat_selesai' => 'required',
            'hari_libur' => 'nullable|array',
            'zona_waktu' => 'required',
        ]);

        $cabang = CabangGedung::findOrFail($id);

        $cabang->update([
            'lokasi' => $request->lokasi,
            'jam_masuk' => $request->jam_masuk,
            'jam_pulang' => $request->jam_pulang,
            'istirahat_mulai' => $request->istirahat_mulai,
            'istirahat_selesai' => $request->istirahat_selesai,
            'hari_libur' => implode(',', $request->hari_libur ?? []),
            'zona_waktu' => $request->zona_waktu,
        ]);

        return redirect()->route('cabang-gedung.index')
            ->with('success', 'Cabang berhasil diupdate');
    }

    // ====== FUNGSI TOGGLE (AKTIF/NON-AKTIF) ======
    public function toggle($id)
    {
        $cabang = CabangGedung::findOrFail($id);
        
        $cabang->aktif = $cabang->aktif == 1 ? 0 : 1;
        $cabang->save();

        $statusText = $cabang->aktif ? 'diaktifkan' : 'dinonaktifkan';
        return back()->with('success', "Status cabang berhasil $statusText");
    }

    // ====== FUNGSI HAPUS PERMANEN DARI DATABASE ======
    public function destroy($id)
    {
        try {
            $cabang = CabangGedung::findOrFail($id);
            $cabang->delete();

            return redirect()->route('cabang-gedung.index')
                ->with('success', 'Cabang berhasil dihapus secara permanen dari database.');
        } catch (\Exception $e) {
            return redirect()->route('cabang-gedung.index')
                ->with('error', 'Gagal menghapus cabang. Pastikan tidak ada data pengguna atau mesin yang masih terhubung ke cabang ini.');
        }
    }
}