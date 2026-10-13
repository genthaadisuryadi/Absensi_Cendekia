@extends('layouts.app')

@section('title', 'Libur Khusus')

@section('content')
    <div class="relative max-w-7xl mx-auto space-y-6">

        {{-- ================= HEADER ================= --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="avatar" style="width:3rem;height:3rem;border-radius:14px;font-size:1.3rem;box-shadow:0 10px 20px -8px rgba(99,102,241,.6)">
                    <i class="bi bi-calendar2-heart-fill"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-extrabold m-0">Libur Khusus</h1>
                    <p class="text-slate-500 text-sm m-0">Kelola tanggal libur khusus perusahaan</p>
                </div>
            </div>

            {{-- Tampilkan tombol tambah hanya untuk SuperAdmin dan Admin --}}
            @if(in_array(strtolower(auth()->user()->jabatanStatus?->hakAkses?->hak), ['superadmin', 'admin', 'full']))
            <button type="button" id="btnTambah"
                class="js-open-tambah inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl text-white font-bold text-sm cursor-pointer border-0"
                style="background:linear-gradient(135deg,#4f46e5,#7c3aed);box-shadow:0 10px 20px -8px rgba(79,70,229,.65)">
                <i class="bi bi-plus-lg"></i> Tambah Libur
            </button>
            @endif
        </div>

        {{-- ================= ALERT ================= --}}
        @php
            $alerts = [
                'successAdd'    => ['bi-check-circle-fill', '#f0fdf4', '#bbf7d0', '#15803d'],
                'successEdit'   => ['bi-pencil-square',     '#eff6ff', '#bfdbfe', '#1d4ed8'],
                'successDelete' => ['bi-trash3-fill',       '#fffbeb', '#fde68a', '#b45309'],
            ];
        @endphp
        @foreach ($alerts as $key => [$icon, $bg, $border, $text])
            @if (session($key))
                <div class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium"
                    style="background:{{ $bg }};border:1px solid {{ $border }};color:{{ $text }}">
                    <i class="bi {{ $icon }} text-lg"></i>
                    <span>{{ session($key) }}</span>
                </div>
            @endif
        @endforeach

        {{-- ================= TABLE ================= --}}
        <div class="bg-white rounded-2xl p-6 overflow-x-auto">

            <div class="flex items-center justify-between mb-2">
                <h2 class="text-base font-bold m-0">Daftar Tanggal Libur</h2>
                <span class="text-xs font-semibold px-3 py-1 rounded-full" style="background:#eef2ff;color:#4f46e5">
                    {{ count($data) }} hari libur
                </span>
            </div>

            <table id="datatableLibur" class="min-w-full text-sm">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Keterangan</th>
                        @if(in_array(strtolower(auth()->user()->jabatanStatus?->hakAkses?->hak), ['superadmin', 'admin', 'full']))
                        <th class="text-center">Aksi</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach ($data as $row)
                        @php $tgl = \Carbon\Carbon::parse($row->tanggal); @endphp
                        <tr>
                            <td data-order="{{ $tgl->format('Y-m-d') }}" class="whitespace-nowrap">
                                <span class="inline-flex items-center gap-2 font-semibold text-slate-800">
                                    <i class="bi bi-calendar-event" style="color:#6366f1"></i>
                                    {{ $tgl->locale('id')->translatedFormat('d F Y') }}
                                </span>
                            </td>
                            <td>{{ $row->keterangan ?: '-' }}</td>
                            @if(in_array(strtolower(auth()->user()->jabatanStatus?->hakAkses?->hak), ['superadmin', 'admin', 'full']))
                            <td class="text-center">
                                <div class="flex justify-center gap-2">

                                    {{-- EDIT --}}
                                    <button type="button"
                                        class="btn-edit inline-flex items-center gap-1 px-3 py-1.5 rounded-lg border border-blue-300 text-blue-600 hover:bg-blue-50 cursor-pointer bg-white"
                                        data-action="{{ route('libur_khusus.update', $row->id) }}"
                                        data-tanggal="{{ $tgl->format('Y-m-d') }}"
                                        data-keterangan="{{ $row->keterangan }}">
                                        <i class="bi bi-pencil"></i>
                                        <span class="hidden sm:inline">Edit</span>
                                    </button>

                                    {{-- DELETE --}}
                                    <form action="{{ route('libur_khusus.destroy', $row->id) }}" method="POST"
                                        onsubmit="return confirm('Hapus data ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg border border-red-300 text-red-600 hover:bg-red-50 cursor-pointer bg-white">
                                            <i class="bi bi-trash"></i>
                                            <span class="hidden sm:inline">Hapus</span>
                                        </button>
                                    </form>

                                </div>
                            </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- MODAL TAMBAH & EDIT --}}
        @if(in_array(strtolower(auth()->user()->jabatanStatus?->hakAkses?->hak), ['superadmin', 'admin', 'full']))
            @include('libur_khusus.create')
            @include('libur_khusus.edit')
        @endif

    </div>
@endsection

@push('scripts')
    <script>
        $(function() {
            var userRole = "{{ strtolower(auth()->user()->jabatanStatus?->hakAkses?->hak) }}";
            var isAdmin = ['superadmin', 'admin', 'full'].includes(userRole);
            
            var colDefs = [];
            if (isAdmin) {
                colDefs.push({ targets: 2, orderable: false });
            }

            $('#datatableLibur').DataTable({
                pageLength: 8,
                searching: true,
                destroy: true,
                order: [[0, 'desc']],
                dom: 'ftip',
                columnDefs: colDefs,
                language: {
                    search: 'Cari:',
                    emptyTable: 'Belum ada data libur khusus',
                    zeroRecords: 'Data tidak ditemukan',
                    info: 'Menampilkan _START_ - _END_ dari _TOTAL_ data',
                    infoEmpty: 'Tidak ada data',
                    paginate: { previous: '‹', next: '›' }
                }
            });
        });

        /* ===== BUKA MODAL TAMBAH ===== */
        $(document).on('click', '#btnTambah', function() {
            $('#modalTambah').removeClass('hidden').addClass('flex');
        });

        /* ===== BUKA MODAL EDIT ===== */
        $(document).on('click', '.btn-edit', function() {
            $('#formEdit').attr('action', $(this).data('action'));
            $('#editTanggal').val($(this).data('tanggal'));
            $('#editKeterangan').val($(this).data('keterangan'));

            $('#modalEdit').removeClass('hidden').addClass('flex');
        });

        /* ===== TUTUP MODAL ===== */
        $(document).on('click', '#closeModal, #closeModalTambah, .btn-batal-modal', function() {
            $('#modalEdit').addClass('hidden').removeClass('flex');
            $('#modalTambah').addClass('hidden').removeClass('flex');
        });
    </script>
@endpush