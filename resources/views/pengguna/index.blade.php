@extends('layouts.app')

@section('content')
    {{-- ================= DATATABLES CSS ================= --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">

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


    <div class="p-6 space-y-6">

        {{-- ================= HEADER ================= --}}
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-semibold text-gray-800">Pengguna</h1>

            <div class="flex gap-2">
                <a href="{{ url('pengguna/scan') }}" target="_blank"
                    class="inline-flex items-center gap-2 px-4 py-2 border border-blue-600 text-blue-600 rounded-lg hover:bg-blue-50">
                    <i class="bi bi-upc-scan"></i> Cek Pengguna
                </a>

                <div class="flex gap-2">
    <!-- Tombol Import -->
    <button type="button" class="btn btn-outline-primary" onclick="document.getElementById('modalImport').classList.remove('hidden')">
        <i class="bi bi-file-earmark-excel"></i> Import Siswa
    </button>
    <a href="{{ route('pengguna.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Tambah Pengguna
    </a>
</div>
            </div>
        </div>

        {{-- ================= TABLE ================= --}}
        <div class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-visible mb-12 p-6">
            <table id="penggunaTable" class="w-full text-sm border-collapse">
                <thead class="bg-gray-100 text-gray-600 uppercase text-xs">
                    <tr>
                        <th>Nomor Induk</th>
                        <th>Nama</th>
                        <th>Jabatan</th>
                        <th>Lokasi</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>

                <tbody class="divide-y">
                    @foreach ($pengguna as $data)
                        @continue($data->nomor_induk == 0)

                        <tr class="hover:bg-gray-50">
                            <td>{{ $data->nomor_induk }}</td>

                            <td>
                                <a href="{{ route('absensi.pengguna', $data->nomor_induk) }}"
                                    class="text-blue-600 hover:underline">
                                    {{ $data->nama }}
                                </a>
                            </td>

                            <td>{{ $data->jabatan_status ?? '-' }}</td>
                            <td>{{ $data->lokasi ?? '-' }}</td>

                            <td class="text-center">
                                <span
                                    class="px-3 py-1 text-xs rounded-full
                                {{ $data->aktif ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                    {{ $data->aktif ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>

                            <td class="text-center space-x-2">
                                <a href="{{ route('pengguna.edit', $data->nomor_induk) }}"
                                    class="px-3 py-1 border border-yellow-400 text-yellow-600 rounded hover:bg-yellow-50">
                                    <i class="bi bi-pencil"></i>
                                </a>

                                <form action="{{ route('pengguna.destroy', $data->nomor_induk) }}" method="POST"
                                    class="inline" onsubmit="return confirm('Yakin ingin menghapus?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="px-3 py-1 border border-red-400 text-red-600 rounded hover:bg-red-50">
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

    {{-- ================= JS ================= --}}
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.colVis.min.js"></script>

    <script>
        $(function() {
            $('#penggunaTable').DataTable({
                pageLength: 8,
                responsive: true,
                dom: 'Bfrtip',
                buttons: [{
                        extend: 'copy',
                        title: 'Data Pengguna'
                    },
                    {
                        extend: 'excel',
                        title: 'Data Pengguna',
                        filename: 'data_pengguna'
                    },
                    {
                        extend: 'pdf',
                        title: 'Data Pengguna',
                        filename: 'data_pengguna'
                    },
                    {
                        extend: 'print',
                        title: 'Data Pengguna'
                    },
                    'colvis'
                ],
                language: {
                    search: "Cari:",
                    lengthMenu: "Tampilkan _MENU_ data",
                    info: "Menampilkan _START_ - _END_ dari _TOTAL_ data",
                    paginate: {
                        previous: "‹",
                        next: "›"
                    }
                }
            });
        });

        <!-- Modal Import Excel -->
<div id="modalImport" class="fixed inset-0 z-[100] hidden">
    <!-- Backdrop -->
    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" onclick="document.getElementById('modalImport').classList.add('hidden')"></div>
    
    <!-- Modal Dialog -->
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-full max-w-md bg-white rounded-2xl shadow-2xl overflow-hidden p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-slate-800">Import Data Siswa</h3>
            <button type="button" onclick="document.getElementById('modalImport').classList.add('hidden')" class="text-slate-400 hover:text-rose-500">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <form action="{{ route('pengguna.import') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium text-slate-700 mb-2">Pilih File Excel/CSV</label>
                <input type="file" name="file_excel" accept=".xlsx, .xls, .csv" required
                    class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                <p class="text-xs text-slate-500 mt-2">
                    Gunakan header: <strong>nis, nama, kelas, tag_rfid, id_cabang</strong> pada baris pertama file Excel Anda.
                </p>
            </div>
            
            <div class="flex justify-end gap-2 mt-6">
                <button type="button" onclick="document.getElementById('modalImport').classList.add('hidden')" class="px-4 py-2 text-sm font-medium text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-lg">Batal</button>
                <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg">
                    <i class="bi bi-cloud-upload mr-1"></i> Upload & Import
                </button>
            </div>
        </form>
    </div>
</div>
    </script>
@endsection
