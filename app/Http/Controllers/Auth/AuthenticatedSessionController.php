<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $loginInput = trim($request->nomor_induk);
        $password = $request->password;

        // Bisa login menggunakan Nomor Induk ATAU Nama Lengkap (memudahkan Orang Tua)
        $user = \App\Models\User::where('nomor_induk', $loginInput)
            ->orWhereRaw('LOWER(nama) = ?', [strtolower($loginInput)])
            ->first();

        if ($user && Hash::check($password, $user->password)) {
            Auth::login($user);
            $request->session()->regenerate();

            $hakAkses = DB::table('jabatan_status')
                ->where('id', $user->jabatan_status)
                ->value('hak_akses');

            if ((int) $user->jabatan_status === 4 || (int) $hakAkses === 4) {
                return redirect()->route('dashboard.ortu');
            }

            return redirect()->route('dashboard.index');
        }

        return back()->withErrors([
            'nomor_induk' => 'Nomor Induk / Nama atau Password salah.',
        ])->onlyInput('nomor_induk');
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}