@extends('layouts.app')

@section('title', 'Absensi Guru & Staff')

@section('content')
<div class="p-6">
    <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-extrabold text-slate-800 flex items-center gap-2">
                <i class="bi bi-person-badge text-indigo-600"></i> Data Absensi Guru & Staff
            </h2>
            <p class="text-sm text-slate-500 mt-1">Halaman eksklusif Superadmin untuk memantau kehadiran pegawai.</p>
        </div>
    </div>

    <!-- Filter Form -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5 mb-6">
        <form action="{{ route('absensi.guru') }}" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Tanggal Awal</label>
                <input type="date" name="awal" value="{{ request('awal') }}" class="form-control w-full rounded-xl border-slate-200 text-sm focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Tanggal Akhir</label>
                <input type="date" name="akhir" value="{{ request('akhir') }}" class="form-control w-full rounded-xl border-slate-200 text-sm focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Lokasi Gedung</label>
                <select name="cabang_gedung" class="form-select w-full rounded-xl border-slate-200 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">Semua Lokasi</option>
                    @foreach($cabangGedungs as $cabang)
                        <option value="{{ $cabang->id }}" {{ request('cabang_gedung') == $cabang->id ? 'selected' : '' }}>
                            {{ $cabang->lokasi }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-xl transition text-sm flex items-center justify-center gap-2">
                    <i class="bi bi-search"></i> Filter
                </button>
                @if(request()->has('awal') || request()->has('cabang_gedung'))
                    <a href="{{ route('absensi.guru') }}" class="w-full bg-slate-100 hover:bg-slate-200 text-slate-600 font-semibold py-2 px-4 rounded-xl transition text-sm flex items-center justify-center gap-2 text-center">
                        <i class="bi bi-x-circle"></i> Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Tabel Data -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left whitespace-nowrap">
                <thead class="text-xs text-slate-500 uppercase bg-slate-50 border-b border-slate-100">
                    <tr>
                        <th class="px-6 py-4 font-semibold">Nama / NIP</th>
                        <th class="px-6 py-4 font-semibold">Waktu Absen</th>
                        <th class="px-6 py-4 font-semibold">Kategori</th>
                        <th class="px-6 py-4 font-semibold">Status</th>
                        <th class="px-6 py-4 font-semibold">Lokasi Gedung</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($absensiGuru as $absen)
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td class="px-6 py-4">
                            <div class="font-bold text-slate-800">{{ $absen->pengguna->nama ?? '-' }}</div>
                            <div class="text-xs text-slate-500">NIP: {{ $absen->pengguna->nomor_induk ?? '-' }}</div>
                        </td>
                        <td class="px-6 py-4 font-medium text-slate-700">
                            {{ $absen->display_absen }}
                        </td>
                        <td class="px-6 py-4">
                            <span class="inline-flex px-2.5 py-1 rounded-md text-xs font-semibold 
                                {{ $absen->kategori == 1 ? 'bg-indigo-50 text-indigo-600' : 
                                  ($absen->kategori == 4 ? 'bg-amber-50 text-amber-600' : 'bg-slate-100 text-slate-600') }}">
                                {{ $absen->kategori_label }}
                            </span>
                        </td>
                        <td class="px-6 py-4 font-bold {{ $absen->warna }}">
                            {{ $absen->status_label }}
                        </td>
                        <td class="px-6 py-4 text-slate-600">
                            {{ $absen->pengguna->cabangGedung->lokasi ?? '-' }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-10 text-center text-slate-500">
                            <i class="bi bi-inbox text-3xl block mb-2 text-slate-300"></i>
                            Belum ada data absensi guru & staff.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection