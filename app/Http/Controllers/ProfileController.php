<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Menampilkan halaman profil.
     */
    public function index()
    {
        return view('profil.index', [
            'user' => auth()->user()
        ]);
    }

    /**
     * Memperbarui informasi profil pengguna (Nama, Password, dan Foto Profil).
     */
    public function update(Request $request)
    {
        $request->validate([
            'nama'        => 'required|string|max:255',
            'password'    => 'nullable|min:8', // Disesuaikan menjadi 8 karakter
            'foto_profil' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Validasi foto profil
        ], [
            'foto_profil.image' => 'File harus berupa gambar.',
            'foto_profil.mimes' => 'Format gambar harus jpeg, png, jpg, atau gif.',
            'foto_profil.max'   => 'Ukuran gambar maksimal 2MB.',
            'password.min'      => 'Kata sandi minimal 8 karakter.',
        ]);

        $user = auth()->user();
        $user->nama = $request->nama;

        // Update password jika diisi
        if ($request->filled('password')) {
            $user->password = bcrypt($request->password);
        }

        // Handle upload foto profil
        if ($request->hasFile('foto_profil')) {
            // Hapus foto lama di storage jika ada
            if ($user->foto_profil && Storage::disk('public')->exists($user->foto_profil)) {
                Storage::disk('public')->delete($user->foto_profil);
            }
            
            // Simpan foto baru ke folder 'foto_profil' di disk public
            $fotoPath = $request->file('foto_profil')->store('foto_profil', 'public');
            $user->foto_profil = $fotoPath;
        }

        $user->save();

        return back()->with('success', 'Profil berhasil diperbarui!');
    }

    /**
     * Menghapus akun pengguna.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        // Opsional: Hapus foto profil dari server sebelum akun dihapus
        if ($user->foto_profil && Storage::disk('public')->exists($user->foto_profil)) {
            Storage::disk('public')->delete($user->foto_profil);
        }

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}