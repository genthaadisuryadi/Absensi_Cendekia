@extends('layouts.app')

@section('title', 'Dashboard Siswa')

@section('content')
@php
    $namaDepan = explode(' ', trim(auth()->user()->nama ?? auth()->user()->name ?? 'Siswa'))[0];
@endphp
<div class="space-y-6">

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl relative flex items-start gap-3 shadow-sm mb-4" role="alert">
            <i class="bi bi-check-circle-fill text-lg mt-0.5"></i>
            <div>
                <span class="block sm:inline font-medium">{{ session('success') }}</span>
            </div>
        </div>
    @endif
    @if(session('error'))
        <div class="bg-rose-50 border border-rose-200 text-rose-700 px-4 py-3 rounded-xl relative flex items-start gap-3 shadow-sm mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill text-lg mt-0.5"></i>
            <div>
                <span class="block sm:inline font-medium">{{ session('error') }}</span>
            </div>
        </div>
    @endif

    {{-- A. Header Card (Pinterest Style) --}}
    <div class="bg-gradient-to-r from-blue-700 via-blue-600 to-indigo-600 rounded-[2rem] p-7 sm:p-8 text-white shadow-2xl relative overflow-hidden group">
        {{-- Background Pattern & Glow --}}
        <div class="absolute top-0 right-0 opacity-10 transform translate-x-1/3 -translate-y-1/3 transition-transform duration-700 group-hover:rotate-12">
            <svg width="300" height="300" viewBox="0 0 100 100"><circle cx="50" cy="50" r="40" fill="currentColor"/></svg>
        </div>
        <div class="absolute -bottom-20 -left-20 w-64 h-64 rounded-full bg-cyan-400/20 blur-3xl"></div>
        <div class="absolute top-10 right-10 w-32 h-32 rounded-full bg-emerald-400/20 blur-2xl"></div>

        <div class="relative z-10 flex flex-col lg:flex-row items-start lg:items-center justify-between gap-8">
            
            {{-- Avatar & Info --}}
            <div class="flex items-center gap-5 w-full lg:w-auto">
                <div class="w-20 h-20 sm:w-24 sm:h-24 rounded-full bg-white/10 backdrop-blur-xl flex items-center justify-center border border-white/20 text-3xl sm:text-4xl font-black shrink-0 shadow-[0_0_15px_rgba(255,255,255,0.1)] overflow-hidden">
                    @if(Auth::user()->foto_profil)
                        <img src="{{ asset('storage/' . Auth::user()->foto_profil) }}" alt="Foto" class="w-full h-full object-cover">
                    @else
                        {{ substr($namaDepan, 0, 1) }}
                    @endif
                </div>
                <div>
                    <h2 class="text-2xl sm:text-4xl font-black m-0 tracking-tight drop-shadow-sm">{{ Auth::user()->nama ?? Auth::user()->name }}</h2>
                    <div class="flex items-center gap-3 mt-2 sm:mt-3">
                        <span class="bg-white/15 px-3 py-1.5 rounded-lg text-xs sm:text-sm font-bold backdrop-blur-md border border-white/10 text-white shadow-sm flex items-center gap-1.5">
                            <i class="bi bi-mortarboard-fill"></i> Pengguna
                        </span>
                        <span class="text-blue-100 text-xs sm:text-sm font-semibold bg-black/10 px-3 py-1.5 rounded-lg border border-black/5">
                            ID: {{ Auth::user()->nomor_induk }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- Progress Card (Glassmorphism) --}}
            <div class="mt-2 lg:mt-0 w-full lg:max-w-md bg-white/10 backdrop-blur-md rounded-2xl p-5 border border-white/20 shadow-xl flex items-center gap-6 relative overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-br from-white/5 to-transparent"></div>
                
                <!-- SVG Circular Progress -->
                <div class="relative w-16 h-16 sm:w-20 sm:h-20 shrink-0 flex items-center justify-center z-10">
                    <svg class="w-full h-full transform -rotate-90" viewBox="0 0 36 36">
                        <path class="text-white/10" stroke-width="3.5" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                        <path class="text-emerald-400 drop-shadow-[0_0_6px_rgba(52,211,153,0.6)] transition-all duration-1000 ease-out" stroke-dasharray="{{ min($persentaseKehadiran ?? 0, 100) }}, 100" stroke-width="3.5" stroke-linecap="round" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                    </svg>
                    <div class="absolute inset-0 flex items-center justify-center flex-col">
                        <span class="text-base sm:text-lg font-black text-white leading-none">{{ round($persentaseKehadiran ?? 0) }}%</span>
                    </div>
                </div>

                <!-- Text Detail -->
                <div class="flex-1 z-10">
                    <h4 class="text-[10px] sm:text-xs font-black text-blue-200 uppercase tracking-widest mb-1.5 opacity-90">Kehadiran Bulan Ini</h4>
                    <div class="flex items-end gap-1.5 mb-1">
                        <span class="text-xl sm:text-2xl font-black text-white leading-none">{{ $totalHariMasuk ?? 0 }}</span>
                        <span class="text-xs sm:text-sm text-blue-100 font-semibold mb-0.5">dari {{ $totalHariEfektif ?? 0 }} Hari</span>
                    </div>
                    <div class="w-full bg-black/20 h-1.5 rounded-full mt-2 overflow-hidden">
                        <div class="bg-gradient-to-r from-emerald-400 to-cyan-300 h-full rounded-full" style="width: {{ min($persentaseKehadiran ?? 0, 100) }}%;"></div>
                    </div>
                </div>
            </div>
            
        </div>
    </div>

    {{-- B. Section "Menu Utama" (Grid 3 Kartu Aksi Modern) --}}
    @php
        // Safety for routes that might not exist yet
        $routeRiwayat = Route::has('absensi.pengguna.saya') ? route('absensi.pengguna.saya') : '#';
        $routeIzin = Route::has('siswa.izin.index') ? route('siswa.izin.index') : '#';
    @endphp
    
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 my-6">

        {{-- Card 2 (Riwayat Presensi) --}}
        <a href="{{ $routeRiwayat }}" class="block bg-white p-5 rounded-2xl border border-indigo-50 shadow-sm hover:shadow-md hover:border-indigo-200 transition-all transform hover:scale-105 cursor-pointer group">
            <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-500 flex items-center justify-center mb-3">
                <i class="bi bi-clock-history text-xl"></i>
            </div>
            <div class="font-bold text-slate-800 text-lg">Riwayat</div>
            <div class="text-xs text-slate-500 mt-1">Lihat data lengkap</div>
        </a>

        {{-- Card 3 (Izin / Sakit) --}}
        <a href="{{ $routeIzin }}" class="block bg-white p-5 rounded-2xl border border-rose-50 shadow-sm hover:shadow-md hover:border-rose-200 transition-all transform hover:scale-105 cursor-pointer group">
            <div class="w-10 h-10 rounded-xl bg-rose-50 text-rose-500 flex items-center justify-center mb-3">
                <i class="bi bi-file-earmark-medical text-xl"></i>
            </div>
            <div class="font-bold text-slate-800 text-lg">Izin / Sakit</div>
            <div class="text-xs text-slate-500 mt-1">Ajukan keterangan</div>
        </a>

        {{-- Card 4 (Unduh Rekap) --}}
        <button onclick="document.getElementById('modalUnduh').classList.remove('hidden')" class="w-full text-left bg-white p-5 rounded-2xl border border-cyan-50 shadow-sm hover:shadow-md hover:border-cyan-200 transition-all transform hover:scale-105 cursor-pointer group">
            <div class="w-10 h-10 rounded-xl bg-cyan-50 text-cyan-600 flex items-center justify-center mb-3">
                <i class="bi bi-download text-xl"></i>
            </div>
            <div class="font-bold text-slate-800 text-lg">Unduh Rekap</div>
            <div class="text-xs text-slate-500 mt-1">Cetak laporan bulanan</div>
        </button>
    </div>

    {{-- C. Section "Riwayat Kehadiran Terbaru" --}}
    <div class="bg-white rounded-2xl p-5 border border-slate-100 shadow-sm mt-6">
        <div class="flex justify-between items-center border-b border-slate-100 pb-4 mb-4">
            <h3 class="font-bold text-lg text-slate-800 flex items-center gap-2">
                <i class="bi bi-activity text-indigo-500"></i> Aktivitas Terakhir
            </h3>
            <a href="{{ $routeRiwayat }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-700 hover:underline">
                Lihat Semua Riwayat
            </a>
        </div>

        <div class="space-y-3">
            @forelse ($riwayatTerbaru as $riwayat)
                <div class="flex items-center justify-between p-3 rounded-xl border border-slate-50 hover:bg-slate-50 transition-colors">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-lg bg-indigo-50 flex flex-col items-center justify-center text-indigo-600 font-bold border border-indigo-100/50">
                            <span class="text-sm leading-none">{{ \Carbon\Carbon::parse($riwayat->absen)->format('d') }}</span>
                            <span class="text-[10px] uppercase leading-none mt-1">{{ \Carbon\Carbon::parse($riwayat->absen)->format('M') }}</span>
                        </div>
                        <div>
                            <div class="font-semibold text-slate-800">
                                {{ $riwayat->kategori_label ?? 'Presensi' }}
                            </div>
                            <div class="text-xs text-slate-500 flex items-center gap-1 mt-1">
                                <i class="bi bi-clock"></i> {{ \Carbon\Carbon::parse($riwayat->absen)->format('H:i') }} WIB
                            </div>
                        </div>
                    </div>
                    
                    @php
                        $status =$riwayat->status_label ?? 'Tepat Waktu';
                        if (stripos($status, 'Terlambat') !== false) {
                            $badgeClass = 'bg-amber-50 text-amber-700 border-amber-200';$iconClass  = 'bi-exclamation-triangle-fill';
                        } elseif (stripos($status, 'Pulang Cepat') !== false) {
                            $badgeClass = 'bg-blue-50 text-blue-700 border-blue-200';$iconClass  = 'bi-info-circle-fill';
                        } elseif (stripos($status, 'Izin') !== false || stripos($status, 'Sakit') !== false) {
                            $badgeClass = 'bg-sky-50 text-sky-700 border-sky-200';$iconClass  = 'bi-file-medical-fill';
                        } else {
                            $badgeClass = 'bg-emerald-50 text-emerald-700 border-emerald-200';$iconClass  = 'bi-check-circle-fill';
                        }
                    @endphp
                    
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold border {{ $badgeClass }}">
                        <i class="bi {{ $iconClass }}"></i> {{ $status }}
                    </span>
                </div>
            @empty
                <div class="text-center py-8">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-slate-50 border border-slate-100 mb-3 text-slate-300">
                        <i class="bi bi-card-list text-3xl"></i>
                    </div>
                    <div class="text-slate-500 font-medium">Belum ada aktivitas presensi terbaru.</div>
                    <div class="text-xs text-slate-400 mt-1">Presensi yang kamu lakukan akan muncul di sini.</div>
                </div>
            @endforelse
        </div>
    </div>

</div>

{{-- Modal Unduh Rekap --}}
<div id="modalUnduh" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/50 backdrop-blur-sm transition-opacity" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-slate-100">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-cyan-100 sm:mx-0 sm:h-10 sm:w-10">
                        <i class="bi bi-cloud-download text-cyan-600 text-xl"></i>
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                        <h3 class="text-lg leading-6 font-bold text-slate-800" id="modal-title">Unduh Rekap Bulanan</h3>
                        <div class="mt-4">
                            <form action="#" method="GET" id="formRekapBulan">
                            <div class="mb-4 grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-500 mb-1">Bulan</label>
                                    <select name="bulan" id="bulanRekap" class="w-full rounded-xl border border-slate-200 text-sm px-3 py-2 outline-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500">
                                        @foreach(range(1,12) as $m)
                                            <option value="{{ $m }}" {{ date('n') == $m ? 'selected' : '' }}>{{ date('F', mktime(0,0,0,$m,1)) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-500 mb-1">Tahun</label>
                                    <input type="number" name="tahun" id="tahunRekap" value="{{ date('Y') }}" class="w-full rounded-xl border border-slate-200 text-sm px-3 py-2 outline-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500" required>
                                </div>
                            </div>
                            <p class="text-sm text-slate-500 mb-3">Pilih format laporan untuk diunduh:</p>
                            <div class="grid grid-cols-2 gap-3">
                                <button type="button" onclick="downloadRekap('pdf')" class="w-full border-2 border-slate-100 rounded-xl p-3 flex flex-col items-center gap-2 hover:border-red-500 hover:bg-red-50 transition-colors text-slate-700 hover:text-red-600 group">
                                    <i class="bi bi-file-earmark-pdf text-3xl text-slate-400 group-hover:text-red-500 transition-colors"></i>
                                    <span class="font-bold text-sm">Cetak PDF</span>
                                </button>
                                <button type="button" onclick="downloadRekap('excel')" class="w-full border-2 border-slate-100 rounded-xl p-3 flex flex-col items-center gap-2 hover:border-green-500 hover:bg-green-50 transition-colors text-slate-700 hover:text-green-600 group">
                                    <i class="bi bi-file-earmark-excel text-3xl text-slate-400 group-hover:text-green-500 transition-colors"></i>
                                    <span class="font-bold text-sm">Export Excel</span>
                                </button>
                            </div>
                            </form>
                            <script>
                                function downloadRekap(type) {
                                    const bulan = document.getElementById('bulanRekap').value;
                                    const tahun = document.getElementById('tahunRekap').value;
                                    const urlPDF = '{{ route("siswa.rekap.pdf") }}';
                                    const urlExcel = '{{ route("siswa.rekap.excel") }}';
                                    
                                    if(type === 'pdf') {
                                        window.open(`${urlPDF}?bulan=${bulan}&tahun=${tahun}`, '_blank');
                                    } else {
                                        window.location.href = `${urlExcel}?bulan=${bulan}&tahun=${tahun}`;
                                    }
                                }
                            </script>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-slate-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="button" onclick="document.getElementById('modalUnduh').classList.add('hidden')" class="mt-3 w-full inline-flex justify-center rounded-xl border border-slate-300 shadow-sm px-4 py-2 bg-white text-base font-semibold text-slate-700 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm transition-colors">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>
@endsection