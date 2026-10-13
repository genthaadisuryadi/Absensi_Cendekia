@extends('layouts.app')

@section('title', 'Mesin Absensi')

@section('content')
<div class="form-page">
    <div class="form-head">
        <div>
            <h2 class="text-xl font-extrabold m-0">Daftar Mesin</h2>
            <p class="text-sm text-slate-500 m-0">Kelola mesin absensi dan cabang penempatannya</p>
        </div>
        <a href="{{ route('mesin.create') }}" class="btn-x btn-submit" style="text-decoration:none; display:inline-flex; width:auto; padding:0.5rem 1rem;">
            <i class="bi bi-plus-lg"></i> Tambah Mesin
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success mb-4"><i class="bi bi-check-circle-fill me-1"></i> {{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger mb-4"><i class="bi bi-exclamation-triangle-fill me-1"></i> {{ session('error') }}</div>
    @endif

    <div class="form-card p-6">
        <table id="mesinTable" class="w-full text-sm border-collapse">
            <thead class="bg-gray-50 text-gray-600 uppercase text-[11px] font-bold tracking-wider">
                <tr>
                    <th class="py-3 px-4 text-left">Kode Mesin (ID)</th>
                    <th class="py-3 px-4 text-left">Cabang Penempatan</th>
                    <th class="py-3 px-4 text-left">Keterangan</th>
                    <th class="py-3 px-4 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($mesins as $m)
                    <tr class="hover:bg-indigo-50/30 transition">
                        <td class="py-3 px-4 font-bold text-indigo-600">{{ $m->idmesin }}</td>
                        <td class="py-3 px-4 font-medium text-slate-700">{{ $m->cabangGedung->lokasi ?? '-' }}</td>
                        <td class="py-3 px-4 text-slate-600">{{ $m->keterangan ?? '-' }}</td>
                        <td class="py-3 px-4 text-center space-x-2 whitespace-nowrap">
                            <a href="{{ route('mesin.edit', $m->idmesin) }}" class="inline-flex px-3 py-1.5 border border-amber-400 text-amber-600 rounded-md hover:bg-amber-50 transition" title="Edit">
                                <i class="bi bi-pencil-square mr-1"></i> Edit
                            </a>
                            {{-- TOMBOL HAPUS MESIN --}}
                            <form action="{{ route('mesin.destroy', $m->idmesin) }}" method="POST" class="inline" onsubmit="return confirm('Yakin ingin menghapus mesin {{ $m->idmesin }} ini dari sistem?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="inline-flex px-3 py-1.5 border border-red-400 text-red-600 rounded-md hover:bg-red-50 transition" title="Hapus Mesin">
                                    <i class="bi bi-trash mr-1"></i> Hapus
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
        $('#mesinTable').DataTable({
            responsive: true,
            language: { search: "Cari data:", lengthMenu: "Tampilkan _MENU_ data", info: "Menampilkan _START_ - _END_ dari _TOTAL_ data" }
        });
    });
</script>
@endpush
@endsection