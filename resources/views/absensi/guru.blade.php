@extends('layouts.app')

@section('title', 'Absensi Guru dan Staff')

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

    <!-- Tabel Data (Sudah Diintegrasikan dengan DataTables) -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-visible p-6">
        <table id="tableAbsensiGuru" class="w-full text-sm border-collapse">
            <thead class="bg-gray-50 text-gray-600 uppercase text-[11px] font-bold tracking-wider">
                <tr>
                    <th class="py-3 px-4">Nama</th>
                    <th class="py-3 px-4">NIP</th>
                    <th class="py-3 px-4">Waktu Absen</th>
                    <th class="py-3 px-4">Kategori</th>
                    <th class="py-3 px-4">Status</th>
                    <th class="py-3 px-4">Lokasi Gedung</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($absensiGuru as $absen)
                <tr class="hover:bg-indigo-50/30 transition">
                    <td class="py-3 px-4 font-bold text-slate-800">
                        {{ $absen->pengguna->nama ?? '-' }}
                    </td>
                    <td class="py-3 px-4 text-slate-600">
                        {{ $absen->pengguna->nomor_induk ?? '-' }}
                    </td>
                    <td class="py-3 px-4 font-medium text-slate-700 whitespace-nowrap">
                        {{ $absen->display_absen }}
                    </td>
                    <td class="py-3 px-4">
                        <span class="inline-flex px-2.5 py-1 rounded-md text-xs font-semibold 
                            {{ $absen->kategori == 1 ? 'bg-indigo-50 text-indigo-600' : 
                              ($absen->kategori == 4 ? 'bg-amber-50 text-amber-600' : 'bg-slate-100 text-slate-600') }}">
                            {{ $absen->kategori_label }}
                        </span>
                    </td>
                    <td class="py-3 px-4 font-bold whitespace-nowrap {{ $absen->warna }}">
                        {{ $absen->status_label }}
                    </td>
                    <td class="py-3 px-4 text-slate-600">
                        {{ $absen->pengguna->cabangGedung->lokasi ?? '-' }}
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
        if ($.fn.DataTable.isDataTable('#tableAbsensiGuru')) {
            $('#tableAbsensiGuru').DataTable().destroy();
        }

        $('#tableAbsensiGuru').DataTable({
            pageLength: 10,
            responsive: true,
            dom: 'Bfrtip',
            buttons: [
                {
                    extend: 'excel',
                    text: 'Excel',
                    title: 'Laporan Absensi Guru & Staff',
                    filename: 'Laporan_Absensi_Guru',
                    exportOptions: { columns: ':visible' }
                },
                {
                    extend: 'pdf',
                    text: 'PDF',
                    title: 'Laporan Absensi Guru & Staff',
                    filename: 'Laporan_Absensi_Guru',
                    exportOptions: { columns: ':visible' }
                },
                {
                    extend: 'print',
                    text: 'Print',
                    title: 'Laporan Absensi Guru & Staff',
                    exportOptions: { columns: ':visible' }
                },
                'colvis'
            ],
            language: {
                search: "Cari data:",
                lengthMenu: "Tampilkan _MENU_ data",
                info: "Menampilkan _START_ - _END_ dari _TOTAL_ data",
                emptyTable: "Belum ada data absensi guru & staff yang ditemukan.",
                paginate: {
                    previous: "Sebelumnya",
                    next: "Selanjutnya"
                },
                buttons: {
                    colvis: 'Column visibility'
                }
            }
        });
    });
</script>
@endpush
@endsection