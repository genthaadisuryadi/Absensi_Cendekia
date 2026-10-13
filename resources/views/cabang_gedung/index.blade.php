@extends('layouts.app')

@section('title', 'Cabang / Gedung')

@section('content')
<div class="form-page">
    <div class="form-head">
        <div>
            <h2 class="text-xl font-extrabold m-0">Cabang / Gedung</h2>
            <p class="text-sm text-slate-500 m-0">Kelola lokasi, ID cabang, jam kerja, dan hari libur</p>
        </div>
        <a href="{{ route('cabang-gedung.create') }}" class="btn-x btn-submit" style="text-decoration:none; display:inline-flex; width:auto; padding:0.5rem 1rem;">
            <i class="bi bi-plus-lg"></i> Tambah Cabang
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success mb-4"><i class="bi bi-check-circle-fill me-1"></i> {{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger mb-4"><i class="bi bi-exclamation-triangle-fill me-1"></i> {{ session('error') }}</div>
    @endif

    <div class="form-card p-6">
        <table id="cabangTable" class="w-full text-sm border-collapse">
            <thead class="bg-gray-50 text-gray-600 uppercase text-[11px] font-bold tracking-wider">
                <tr>
                    <th class="py-3 px-4 text-left">ID Cabang</th>
                    <th class="py-3 px-4 text-left">Lokasi</th>
                    <th class="py-3 px-4 text-left">Jam Kerja</th>
                    <th class="py-3 px-4 text-left">Istirahat</th>
                    <th class="py-3 px-4 text-center">Hari Libur</th>
                    <th class="py-3 px-4 text-center">Zona</th>
                    <th class="py-3 px-4 text-center">Status</th>
                    <th class="py-3 px-4 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($cabangGedungs as $cabang)
                    <tr class="hover:bg-indigo-50/30 transition">
                        <td class="py-3 px-4 font-bold text-indigo-600 text-center w-24">
                            <span class="bg-indigo-50 border border-indigo-100 px-2 py-1 rounded text-xs">ID: {{ $cabang->id }}</span>
                        </td>
                        <td class="py-3 px-4 font-semibold text-slate-700">{{ $cabang->lokasi }}</td>
                        <td class="py-3 px-4 text-slate-600">
                            {{ substr($cabang->jam_masuk, 0, 5) }} – {{ substr($cabang->jam_pulang, 0, 5) }}
                        </td>
                        <td class="py-3 px-4 text-slate-600">
                            {{ substr($cabang->jam_mulai_istirahat, 0, 5) }} – {{ substr($cabang->jam_selesai_istirahat, 0, 5) }}
                        </td>
                        <td class="py-3 px-4 text-center">
                            @php $libur = json_decode($cabang->hari_libur, true) ?? []; @endphp
                            @if(count($libur) > 0)
                                <div class="flex flex-wrap gap-1 justify-center">
                                    @foreach($libur as $l)
                                        <span class="px-2 py-0.5 bg-slate-100 text-slate-500 rounded text-[10px] uppercase font-bold border border-slate-200">{{ substr($l, 0, 3) }}</span>
                                    @endforeach
                                </div>
                            @else
                                <span class="text-xs text-slate-400">Tidak ada</span>
                            @endif
                        </td>
                        <td class="py-3 px-4 text-center">
                            <span class="px-2 py-0.5 bg-blue-50 text-blue-600 rounded text-[10px] font-bold border border-blue-100">
                                {{ $cabang->zona_waktu == 1 ? 'WIB' : ($cabang->zona_waktu == 2 ? 'WITA' : 'WIT') }}
                            </span>
                        </td>
                        <td class="py-3 px-4 text-center">
                            <span class="px-3 py-1 text-[11px] font-bold rounded-full {{ $cabang->aktif ? 'bg-emerald-100 text-emerald-700 border border-emerald-200' : 'bg-rose-100 text-rose-700 border border-rose-200' }}">
                                {{ $cabang->aktif ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                        <td class="py-3 px-4 text-center space-x-2 whitespace-nowrap">
                            <a href="{{ route('cabang-gedung.edit', $cabang->id) }}" class="inline-flex px-3 py-1.5 border border-amber-400 text-amber-600 rounded-md hover:bg-amber-50 transition" title="Edit">
                                <i class="bi bi-pencil-square"></i>
                            </a>
                            <form action="{{ route('cabang-gedung.toggle', $cabang->id) }}" method="POST" class="inline">
                                @csrf @method('PATCH')
                                <button type="submit" class="inline-flex px-3 py-1.5 border {{ $cabang->aktif ? 'border-rose-400 text-rose-600 hover:bg-rose-50' : 'border-emerald-400 text-emerald-600 hover:bg-emerald-50' }} rounded-md transition" title="{{ $cabang->aktif ? 'Nonaktifkan' : 'Aktifkan' }}">
                                    <i class="bi {{ $cabang->aktif ? 'bi-power' : 'bi-check-circle' }}"></i>
                                </button>
                            </form>
                            {{-- TOMBOL HAPUS CABANG --}}
                            <form action="{{ route('cabang-gedung.destroy', $cabang->id) }}" method="POST" class="inline" onsubmit="return confirm('YAKIN INGIN MENGHAPUS CABANG INI? Peringatan: Semua data yang terkait dengan cabang ini (seperti pengguna dan mesin) mungkin akan ikut terhapus atau kehilangan relasi.')">
                                @csrf @method('DELETE')
                                <button type="submit" class="inline-flex px-3 py-1.5 border border-red-400 text-red-600 rounded-md hover:bg-red-50 transition" title="Hapus Cabang">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@push('scripts')
<script>
    $(document).ready(function() {
        $('#cabangTable').DataTable({
            responsive: true,
            language: { search: "Cari data:", lengthMenu: "Tampilkan _MENU_ data", info: "Menampilkan _START_ - _END_ dari _TOTAL_ data" }
        });
    });
</script>
@endpush
@endsection