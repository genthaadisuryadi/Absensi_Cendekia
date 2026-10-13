<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HakAksesMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        $jabatanStatus = $user->jabatanStatus;
        if (!$jabatanStatus || !$jabatanStatus->hakAkses) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return response()->view('errors.access_denied', [
                'message' => 'Akun Anda belum memiliki Jabatan atau Hak Akses yang valid.',
                'action' => 'logout',
            ], 403);
        }

        // Normalisasi role user (ubah spasi menjadi underscore, huruf kecil)
        $rawHak = strtolower(trim($jabatanStatus->hakAkses->hak ?? ''));
        $normalizedUserRole = str_replace(' ', '_', $rawHak);

        // Dukungan backward-compatibility jika di DB masih tertulis 'nusabot' atau 'full'
        $roleAliasMap = [
            'nusabot' => 'superadmin',
            'full'    => 'admin',
            'siswa'   => 'general',
        ];
        $effectiveUserRole = $roleAliasMap[$normalizedUserRole] ?? $normalizedUserRole;

        // Normalisasi semua parameter role dari web.php
        $normalizedAllowedRoles = array_map(function ($role) use ($roleAliasMap) {
            $r = str_replace(' ', '_', strtolower(trim($role)));
            return $roleAliasMap[$r] ?? $r;
        }, $roles);

        if (!in_array($effectiveUserRole, $normalizedAllowedRoles, true)) {
            return response()->view('errors.access_denied', [
                'message' => "Akses ditolak. Role Anda ('{$jabatanStatus->jabatan_status}') tidak diizinkan mengakses halaman ini.",
                'action' => 'back',
                'debug' => [
                    'user_role' => $effectiveUserRole,
                    'required_roles' => $normalizedAllowedRoles,
                ]
            ], 403);
        }

        return $next($request);
    }
}