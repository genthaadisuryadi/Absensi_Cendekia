@extends('layouts.app')

@section('title', 'Dashboard Orang Tua')

@section('content')
@php
    $namaDepan = explode(' ', trim(auth()->user()->nama ?? auth()->user()->name ?? 'Orang Tua'))[0];
@endphp
<div class="p-6">
    <div class="mb-8 flex items-center gap-4">
        {{-- FOTO PROFIL ORTU --}}
        <div class="w-16 h-16 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold text-2xl shadow-sm border border-blue-200 overflow-hidden shrink-0">
            @if(Auth::user()->foto_profil)
                <img src="{{ asset('storage/' . Auth::user()->foto_profil) }}" alt="Foto" class="w-full h-full object-cover">
            @else
                {{ substr($namaDepan, 0, 1) }}
            @endif
        </div>
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Dashboard Orang Tua</h1>
            <p class="text-slate-500 mt-1">Selamat datang, {{ $user->nama ?? $user->name ?? 'Orang Tua' }}!</p>
        </div>
    </div>

    <!-- Data Anak -->
    <h2 class="text-lg font-semibold text-slate-700 mb-4">Informasi Absensi Anak Hari Ini</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 mb-8">
        @forelse($anakAnak as $anak)
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 flex flex-col">
            <div class="flex items-center gap-4 mb-4">
                <div class="w-12 h-12 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center font-bold text-xl overflow-hidden shrink-0">
                    @if($anak->foto_profil)
                        <img src="{{ asset('storage/' . $anak->foto_profil) }}" alt="Foto" class="w-full h-full object-cover">
                    @else
                        {{ strtoupper(substr($anak->nama, 0, 1)) }}
                    @endif
                </div>
                <div>
                    <h3 class="font-bold text-slate-800">{{ $anak->nama }}</h3>
                    <p class="text-xs text-slate-500">NIS: {{ $anak->nomor_induk }}</p>
                </div>
            </div>
            
            <div class="mt-auto p-4 rounded-xl {{ $anak->status_absen === 'Belum Absen' ? 'bg-slate-50' : ($anak->status_absen === 'Tepat Waktu' ? 'bg-emerald-50' : 'bg-rose-50') }}">
                <div class="flex justify-between items-center mb-1">
                    <span class="text-xs font-medium text-slate-500">Status Hari Ini</span>
                    <span class="text-xs font-bold {{ $anak->status_absen === 'Belum Absen' ? 'text-slate-600' : ($anak->status_absen === 'Tepat Waktu' ? 'text-emerald-600' : 'text-rose-600') }}">
                        {{ $anak->status_absen }}
                    </span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-xs font-medium text-slate-500">Jam Masuk</span>
                    <span class="text-sm font-bold text-slate-700">{{ $anak->jam_masuk }}</span>
                </div>
            </div>
        </div>
        @empty
        <div class="col-span-full bg-yellow-50 rounded-2xl p-6 border border-yellow-100 text-center">
            <p class="text-yellow-700 font-medium">Belum ada data siswa yang dihubungkan dengan akun Anda.</p>
            <p class="text-yellow-600 text-sm mt-1">Silakan hubungi administrator sekolah.</p>
        </div>
        @endforelse
    </div>

    <!-- Riwayat Absensi -->
    <h2 class="text-lg font-semibold text-slate-700 mb-4">Riwayat Absensi Terakhir</h2>
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="text-xs text-slate-500 uppercase bg-slate-50 border-b border-slate-100">
                    <tr>
                        <th class="px-6 py-4 font-semibold">Waktu</th>
                        <th class="px-6 py-4 font-semibold">Nama Anak</th>
                        <th class="px-6 py-4 font-semibold">Kategori</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($riwayatAbsensi as $riwayat)
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td class="px-6 py-4 font-medium text-slate-700">
                            {{ \Carbon\Carbon::parse($riwayat->absen)->format('d M Y - H:i') }}
                        </td>
                        <td class="px-6 py-4 text-slate-600">
                            {{ $riwayat->nama_anak }}
                        </td>
                        <td class="px-6 py-4">
                            @if($riwayat->kategori == 1)
                                <span class="inline-flex px-2.5 py-1 rounded-md bg-blue-50 text-blue-600 text-xs font-semibold">Masuk</span>
                            @else
                                <span class="inline-flex px-2.5 py-1 rounded-md bg-amber-50 text-amber-600 text-xs font-semibold">Pulang</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" class="px-6 py-8 text-center text-slate-500">
                            Belum ada riwayat absensi.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection