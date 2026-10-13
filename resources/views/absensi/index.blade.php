@extends('layouts.app')

@section('content')

{{-- ================= DATA TABLES CSS ================= --}}
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">

{{-- ================= DATATABLES FIX STYLE ================= --}}
@push('styles')
<style>
.dataTables_wrapper {
    padding: 1rem;
}

.dataTables_filter,
.dt-buttons {
    margin-bottom: 1rem;
}

.dataTables_info,
.dataTables_paginate {
    margin-top: 1rem;
}

table.dataTable {
    margin-top: 0.75rem !important;
    margin-bottom: 0.75rem !important;
}
</style>
@endpush


<div class="container-fluid px-3 sm:px-5 py-6 text-[15px] sm:text-[16px]">

    {{-- HEADER --}}
    <div class="mb-6 flex flex-col sm:flex-row items-start sm:items-center gap-4">
        <div class="bg-blue-600 text-white p-3 rounded-xl shadow">
            <i class="bi bi-clipboard-data text-2xl"></i>
        </div>
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">
                Laporan Absensi
            </h1>
            <p class="text-gray-600 text-sm sm:text-base">
                Rekap data absensi (DataTables)
            </p>
        </div>
    </div>

    {{-- TABLE --}}
    <div class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-visible mb-12 p-6">
        <div class="overflow-x-auto">
            <table id="absensiTable" class="w-full text-sm border-collapse">
                   {{-- class="min-w-[900px] w-full text-sm sm:text-base border-collapse"> --}}

                <thead class="bg-slate-800 text-slate-100">
                    <tr>
                        <th class="px-4 py-3 border">Waktu</th>
                        <th class="px-4 py-3 border">Nama</th>
                        <th class="px-4 py-3 border">NIP</th>
                        <th class="px-4 py-3 border">Cabang</th>
                        <th class="px-4 py-3 border">Kategori</th>
                        <th class="px-4 py-3 border">Status</th>
                        <th class="px-4 py-3 border">Mesin</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach($absensis as $a)
                        <tr class="hover:bg-blue-50 transition text-gray-800">
                            <td class="px-4 py-3 border whitespace-nowrap">
                                {{ $a->display_absen ?? ($a->absen_at ?? '-') }}
                            </td>

                            <td class="px-4 py-3 border font-semibold">
                                <a href="{{ route('absensi.pengguna', $a->nomor_induk) }}"
                                   class="text-blue-600 hover:underline">
                                    {{ $a->pengguna->nama ?? '-' }}
                                </a>
                            </td>

                            <td class="px-4 py-3 border">
                                {{ $a->nomor_induk }}
                            </td>

                            <td class="px-4 py-3 border">
                                {{ $a->pengguna->cabangGedung->lokasi ?? '-' }}
                            </td>

                            <td class="px-4 py-3 border">
                                @switch($a->kategori)
                                    @case(1)
                                        <span class="bg-green-600 text-white px-3 py-1 rounded-full">Masuk</span>
                                        @break
                                    @case(2)
                                        <span class="bg-yellow-500 text-black px-3 py-1 rounded-full">Istirahat</span>
                                        @break
                                    @case(3)
                                        <span class="bg-blue-500 text-white px-3 py-1 rounded-full">Selesai</span>
                                        @break
                                    @case(4)
                                        <span class="bg-gray-600 text-white px-3 py-1 rounded-full">Pulang</span>
                                        @break
                                @endswitch
                            </td>

                           <td class="px-6 py-4 border text-center">
                                    @if($a->status === 'tepat_waktu')
                                        <span class="px-3 py-1 rounded-full bg-gray-200 text-gray-700 font-semibold">-</span>
                                     @elseif($a->status === 'telat')
                                        <span class="px-3 py-1 rounded-full bg-green-600 text-white font-semibold">{{ $absensi->status_label ?? 'tepat_waktu' }}</span>
                                    @else
                                        <span class="px-3 py-1 rounded-full bg-red-600 text-white font-semibold">{{ $absensi->status_label ?? 'telat' }}</span>
                                    @endif
                                </td>

                            <td class="px-4 py-3 border">
                                {{ $a->idmesin }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>

            </table>
        </div>
    </div>

</div>

{{-- ================= JS ================= --}}
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.colVis.min.js"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>

<script>
$(document).ready(function () {
    $('#absensiTable').DataTable({
        responsive: true,
        pageLength: 10,
        lengthMenu: [10, 25, 50, 100],
        order: [[0, 'desc']],

        dom:
            "<'flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-2'Bf>" +
            "<'overflow-x-auto't>" +
            "<'flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mt-2'lip>",

        buttons: [
            { extend: 'excel', title: 'Laporan Absensi' },
            {
                extend: 'pdf',
                title: 'Laporan Absensi',
                orientation: 'landscape',
                pageSize: 'A4'
            },
            { extend: 'print', title: 'Laporan Absensi' },
            'colvis'
        ],

        language: {
            search: "Cari:",
            lengthMenu: "Tampilkan _MENU_ data",
            info: "Menampilkan _START_ - _END_ dari _TOTAL_ data",
            paginate: {
                previous: "‹",
                next: "›"
            },
            zeroRecords: "Data tidak ditemukan"
        }
    });
});
</script>

@endsection
