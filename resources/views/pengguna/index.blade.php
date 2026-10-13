@extends('layouts.app')

@section('content')
    {{-- ================= DATATABLES CSS ================= --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">

    @push('styles')
        <style>
            .dataTables_wrapper { padding: 1rem; }
            .dataTables_filter, .dt-buttons { margin-bottom: 1rem; }
            .dataTables_info, .dataTables_paginate { margin-top: 1rem; }
            table.dataTable { margin-top: 0.75rem !important; margin-bottom: 0.75rem !important; }
        </style>
    @endpush

    <div class="p-6 space-y-6">

        {{-- ================= ALERT PESAN ================= --}}
        @if(session('successAdd') || session('successEdit') || session('successDelete'))
            <div class="p-4 mb-4 text-sm text-green-800 rounded-lg bg-green-50 border border-green-200">
                <span class="font-medium">Berhasil!</span> Aksi pada data pengguna sukses dilakukan.
            </div>
        @endif
        @if($errors->any())
            <div class="p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50 border border-red-200">
                <span class="font-medium">Gagal!</span> {{ $errors->first() }}
            </div>
        @endif

        {{-- ================= HEADER ================= --}}
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <h1 class="text-2xl font-semibold text-gray-800">Kelola Pengguna</h1>

            <div class="flex flex-wrap items-center gap-2">
                <!-- Tombol Import Excel -->
                <button type="button" onclick="document.getElementById('modalImport').classList.remove('hidden')"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-500 text-white text-sm font-medium rounded-lg hover:bg-emerald-600 shadow-sm transition">
                    <i class="bi bi-file-earmark-excel"></i> Import Siswa
                </button>
                
                <!-- Tombol Cek Pengguna (Mesin RFID Modal) -->
                <button type="button" id="btnCekKartu"
                    class="inline-flex items-center gap-2 px-4 py-2 border border-blue-600 text-blue-600 text-sm font-medium rounded-lg hover:bg-blue-50 transition shadow-sm">
                    <i class="bi bi-upc-scan"></i> Cek Kartu
                </button>

                <!-- Tombol Tambah Manual -->
                <a href="{{ route('pengguna.create') }}" 
                    class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 shadow-sm transition">
                    <i class="bi bi-plus-lg"></i> Tambah Pengguna
                </a>
            </div>
        </div>

        {{-- ================= TABLE ================= --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-visible mb-12 p-6">
            <table id="penggunaTable" class="w-full text-sm border-collapse">
                <thead class="bg-gray-50 text-gray-600 uppercase text-[11px] font-bold tracking-wider">
                    <tr>
                        <th class="py-3">NIS / NIP</th>
                        <th class="py-3">Nama</th>
                        <th class="py-3">Kelas</th>
                        <th class="py-3">Jabatan</th>
                        <th class="py-3">Lokasi</th>
                        <th class="py-3 text-center">Status</th>
                        <th class="py-3 text-center">Aksi</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100">
                    @foreach ($pengguna as$data)
                        @continue($data->nomor_induk == 0)

                        <tr class="hover:bg-indigo-50/30 transition">
                            <td class="py-3">{{ $data->nomor_induk }}</td>

                            <td class="py-3 font-semibold text-slate-700">
                                <a href="{{ route('absensi.pengguna', $data->nomor_induk) }}"
                                    class="text-indigo-600 hover:text-indigo-800 hover:underline">
                                    {{ $data->nama }}
                                </a>
                            </td>

                            <td class="py-3">{{ $data->kelas ?? '-' }}</td>
                            <td class="py-3">
                                <span class="px-2.5 py-1 bg-slate-100 text-slate-600 rounded-md text-xs font-medium border border-slate-200">
                                    {{ $data->jabatan_status ?? '-' }}
                                </span>
                            </td>
                            <td class="py-3 text-slate-600">{{ $data->lokasi ?? '-' }}</td>

                            <td class="py-3 text-center">
                                <span class="px-3 py-1 text-[11px] font-bold rounded-full {{ $data->aktif ? 'bg-emerald-100 text-emerald-700 border border-emerald-200' : 'bg-rose-100 text-rose-700 border border-rose-200' }}">
                                    {{ $data->aktif ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>

                            <td class="py-3 text-center space-x-2 whitespace-nowrap">
                                <a href="{{ route('pengguna.edit', $data->nomor_induk) }}"
                                    class="inline-flex px-3 py-1.5 border border-amber-400 text-amber-600 rounded-md hover:bg-amber-50 transition" title="Edit">
                                    <i class="bi bi-pencil-square"></i>
                                </a>

                                <form action="{{ route('pengguna.destroy', $data->nomor_induk) }}" method="POST"
                                    class="inline" onsubmit="return confirm('Yakin ingin menghapus pengguna ini beserta seluruh data absensinya?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="inline-flex px-3 py-1.5 border border-rose-400 text-rose-600 rounded-md hover:bg-rose-50 transition" title="Hapus">
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

    {{-- ================= MODAL IMPORT EXCEL ================= --}}
    <div id="modalImport" class="fixed inset-0 z-[100] hidden">
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" onclick="document.getElementById('modalImport').classList.add('hidden')"></div>
        
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-full max-w-md bg-white rounded-2xl shadow-2xl overflow-hidden p-6 transform transition-all">
            <div class="flex items-center justify-between mb-4 border-b border-slate-100 pb-3">
                <h3 class="text-lg font-bold text-slate-800"><i class="bi bi-cloud-arrow-up-fill text-indigo-500 mr-2"></i> Import Data Siswa</h3>
                <button type="button" onclick="document.getElementById('modalImport').classList.add('hidden')" class="text-slate-400 hover:text-rose-500 transition">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <form action="{{ route('pengguna.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Pilih File Excel / CSV</label>
                    <input type="file" name="file_excel" accept=".xlsx, .xls, .csv" required
                        class="block w-full text-sm text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 border border-slate-200 rounded-xl cursor-pointer">
                    
                    <div class="mt-4 p-3 bg-slate-50 border border-slate-200 rounded-xl">
                        <p class="text-[11px] text-slate-600 leading-relaxed">
                            Gunakan header berikut pada baris pertama file Excel Anda (huruf kecil semua):
                            <br><code class="text-indigo-600 font-bold tracking-wider mt-1 block">nis, nama, kelas, tag_rfid, id_cabang</code>
                        </p>
                    </div>
                </div>
                
                <div class="flex justify-end gap-2 mt-6 pt-4 border-t border-slate-100">
                    <button type="button" onclick="document.getElementById('modalImport').classList.add('hidden')" class="px-5 py-2 text-sm font-medium text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl transition">Batal</button>
                    <button type="submit" class="px-5 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl shadow-md shadow-indigo-200 transition">
                        <i class="bi bi-upload mr-1"></i> Upload
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ================= MODAL CEK KARTU RFID ================= --}}
    <div id="modalCekKartu" class="fixed inset-0 z-[100] hidden items-center justify-center bg-black/50 backdrop-blur-sm transition-opacity">
        <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md overflow-hidden transform scale-100">
            {{-- Header Modal --}}
            <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                <h3 class="text-lg font-bold text-slate-800 m-0"><i class="bi bi-search text-blue-500 mr-2"></i> Cek Identitas Kartu</h3>
                <button type="button" id="closeModalCek" class="text-slate-400 hover:text-red-500 transition text-xl">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            {{-- Body Modal --}}
            <div class="p-6 text-center">
                <p class="text-sm text-slate-500 mb-4">Silakan <b>tap kartu RFID</b> ke mesin <i>scanner</i> Anda.</p>
                
                <div class="mb-5">
                    <input type="text" id="inputScanKartu" placeholder="Menunggu scan kartu..." 
                        class="w-full text-center font-mono text-xl py-3 px-4 border-2 border-blue-200 rounded-xl focus:border-blue-500 focus:ring-0 transition" autocomplete="off">
                </div>

                {{-- Area Hasil --}}
                <div id="hasilScan" class="min-h-[100px] flex items-center justify-center">
                    <span class="text-slate-400 italic">Hasil identitas akan muncul di sini...</span>
                </div>
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

    <script>
        $(function() {$('#penggunaTable').DataTable({
                pageLength: 10,
                responsive: true,
                dom: 'Bfrtip',
                buttons: [
                    { extend: 'copy', title: 'Data Pengguna' },
                    { extend: 'excel', title: 'Data Pengguna', filename: 'data_pengguna' },
                    { extend: 'pdf', title: 'Data Pengguna', filename: 'data_pengguna' },
                    { extend: 'print', title: 'Data Pengguna' },
                    'colvis'
                ],
                language: {
                    search: "Cari data:",
                    lengthMenu: "Tampilkan _MENU_ data",
                    info: "Menampilkan _START_ - _END_ dari _TOTAL_ data",
                    paginate: {
                        previous: "Sebelumnya",
                        next: "Selanjutnya"
                    }
                }
            });

            /* ===== LOGIKA MODAL CEK KARTU RFID ===== */
            $('#btnCekKartu').on('click', function() {
                $('#modalCekKartu').removeClass('hidden').addClass('flex');
                $('#hasilScan').html('<span class="text-slate-400 italic">Hasil identitas akan muncul di sini...</span>');
                // Auto focus ke input agar langsung siap menampung hasil scan scanner USB
                setTimeout(() => { $('#inputScanKartu').val('').focus(); }, 100);
            });

            $('#closeModalCek').on('click', function() {
                $('#modalCekKartu').addClass('hidden').removeClass('flex');
                $('#inputScanKartu').val('');
            });

            // Deteksi input dari scanner (tekan Enter/Barcode scanner submit otomatis)
            $('#inputScanKartu').on('keypress', function(e) {
                if(e.which === 13) { 
                    e.preventDefault();
                    let tag = $(this).val();
                    
                    if(tag.trim() === '') return;

                    $('#hasilScan').html('<span class="text-blue-500 font-bold"><i class="bi bi-arrow-repeat animate-spin inline-block"></i> Mengecek data...</span>');

                    $.ajax({
                        url: "{{ route('pengguna.cek_tag') }}",
                        type: "GET",
                        data: { tag: tag },
                        success: function(res) {
                            if(res.success) {
                                $('#hasilScan').html(`
                                    <div class="w-full bg-green-50 text-green-800 p-4 rounded-xl border border-green-200 text-left shadow-sm">
                                        <p class="font-extrabold text-lg mb-1"><i class="bi bi-person-check-fill text-green-600"></i> ${res.nama}</p>
                                        <p class="text-sm m-0 text-green-700">Kelas: <b>${res.kelas}</b></p>
                                        <p class="text-sm m-0 text-green-700">Nomor Induk: <b>${res.nomor_induk}</b></p>
                                    </div>
                                `);
                            } else {
                                $('#hasilScan').html(`
                                    <div class="w-full bg-red-50 text-red-800 p-4 rounded-xl border border-red-200 shadow-sm">
                                        <p class="font-bold mb-0"><i class="bi bi-x-circle-fill"></i> ${res.message}</p>
                                    </div>
                                `);
                            }
                            // Kosongkan input agar langsung siap untuk scan kartu berikutnya tanpa harus di-klik
                            $('#inputScanKartu').val('').focus();
                        },
                        error: function() {
                            $('#hasilScan').html('<div class="text-red-500 font-bold">Terjadi kesalahan koneksi ke server.</div>');
                            $('#inputScanKartu').val('').focus();
                        }
                    });
                }
            });
        });
    </script>
@endsection