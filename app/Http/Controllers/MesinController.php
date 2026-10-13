<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Mesin;
use App\Models\CabangGedung;

class MesinController extends Controller
{
    public function index()
    {
        $mesins = Mesin::with('cabangGedung')->get();
        return view('mesin.index', compact('mesins'));
    }

    public function create()
    {
        $cabangs = CabangGedung::all();
        return view('mesin.create', compact('cabangs'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'idmesin'       => 'required|unique:mesin,idmesin',
            'cabang_gedung' => 'required|exists:cabang_gedung,id',
            'keterangan'    => 'nullable|string'
        ]);

        Mesin::create($request->all());
        return redirect()->route('mesin.index')->with('success', 'Mesin berhasil ditambahkan.');
    }

    public function edit($idmesin)
    {
        $mesin = Mesin::where('idmesin', $idmesin)->firstOrFail();
        $cabangs = CabangGedung::all();
        return view('mesin.edit', compact('mesin', 'cabangs'));
    }

    public function update(Request $request, $idmesin)
    {
        $mesin = Mesin::where('idmesin', $idmesin)->firstOrFail();

        $request->validate([
            'cabang_gedung' => 'required|exists:cabang_gedung,id',
            'keterangan'    => 'nullable|string'
        ]);

        $mesin->update($request->only(['cabang_gedung', 'keterangan']));
        return redirect()->route('mesin.index')->with('success', 'Data mesin berhasil diperbarui.');
    }

    // ====== FUNGSI HAPUS MESIN ======
    public function destroy($idmesin)
    {
        try {
            $mesin = Mesin::where('idmesin', $idmesin)->firstOrFail();
            $mesin->delete();
            
            return redirect()->route('mesin.index')->with('success', 'Mesin berhasil dihapus dari sistem.');
        } catch (\Exception $e) {
            return redirect()->route('mesin.index')->with('error', 'Gagal menghapus mesin. Pastikan tidak ada data yang masih terhubung.');
        }
    }
}