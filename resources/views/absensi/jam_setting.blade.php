@extends('layouts.app')

@section('title', 'Pengaturan Jam Absensi')

@section('content')
<div class="form-page">

    <div class="form-head">
        <div>
            <h2 class="text-xl font-extrabold m-0">Pengaturan Jam Absensi</h2>
            <p class="text-sm text-slate-500 m-0">Atur jadwal masuk, istirahat, pulang, hari aktif, serta toleransi keterlambatan.</p>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success mb-4 flex items-center bg-green-50 text-green-800 p-4 rounded-xl border border-green-200">
            <i class="bi bi-check-circle-fill me-2 text-xl"></i> {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger mb-4 flex flex-col bg-red-50 text-red-800 p-4 rounded-xl border border-red-200">
            <ul class="mb-0 ps-4 list-disc">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="form-card bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
        <div class="profile-banner flex items-center mb-6 pb-6 border-b border-slate-100">
            <div class="avatar-lg bg-indigo-50 text-indigo-600 rounded-xl w-12 h-12 flex items-center justify-center mr-4">
                <i class="bi bi-clock-history text-2xl"></i>
            </div>
            <div class="min-w-0">
                <p class="banner-name font-bold text-lg m-0">Jadwal Waktu & Hari Presensi</p>
                <p class="banner-sub text-slate-500 text-sm m-0">Berlaku untuk seluruh perhitungan absensi mesin RFID & sistem</p>
            </div>
        </div>

        <form action="{{ route('jam-absensi.update') }}" method="POST">
            @csrf
            
            {{-- AREA PENGATURAN HARI AKTIF (Fitur Baru) --}}
            <div class="mb-8">
                <h3 class="font-bold text-slate-800 mb-3"><i class="bi bi-calendar-check text-indigo-500 mr-2"></i>Hari Masuk / Aktif</h3>
                <p class="text-xs text-slate-500 mb-4">Centang hari di bawah ini untuk mengizinkan absensi. Jika tidak dicentang, mesin scanner dan absen manual akan otomatis ditolak karena dianggap Libur.</p>
                
                @php
                    // Ambil data hari aktif (default: Senin-Jumat jika kosong)
                    $hariAktif = $jamSetting['hari_aktif'] ?? ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];
                    $daftarHari = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
                @endphp

                <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-7 gap-3">
                    @foreach($daftarHari as $hari)
                    <label class="relative flex cursor-pointer items-center justify-center p-3 rounded-xl border-2 transition-all {{ in_array($hari, $hariAktif) ? 'border-indigo-500 bg-indigo-50' : 'border-slate-200 bg-white hover:border-slate-300' }}">
                        <input type="checkbox" name="hari_aktif[]" value="{{ $hari }}" class="peer sr-only" {{ in_array($hari, $hariAktif) ? 'checked' : '' }}>
                        <span class="text-sm font-semibold {{ in_array($hari, $hariAktif) ? 'text-indigo-700' : 'text-slate-600' }} peer-checked:text-indigo-700">
                            {{ $hari }}
                        </span>
                        {{-- Indikator centang kecil --}}
                        <i class="bi bi-check-circle-fill absolute top-1 right-1 text-indigo-500 text-[10px] {{ in_array($hari, $hariAktif) ? 'block' : 'hidden' }} peer-checked:block"></i>
                    </label>
                    @endforeach
                </div>
            </div>

            <hr class="border-slate-100 mb-6">

            {{-- AREA PENGATURAN WAKTU --}}
            <div class="form-body grid grid-cols-1 md:grid-cols-2 gap-6">

                <div class="field">
                    <label for="jam_masuk" class="block font-semibold text-sm mb-2">Jam Masuk (Kategori 1)</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400"><i class="bi bi-box-arrow-in-right"></i></div>
                        <input type="time" id="jam_masuk" name="jam_masuk" class="form-control pl-10 w-full rounded-lg border-slate-200 focus:border-indigo-500 focus:ring-indigo-500"
                            value="{{ substr($jamSetting['1'] ?? '08:00:00', 0, 5) }}" required>
                    </div>
                </div>

                <div class="field">
                    <label for="jam_pulang" class="block font-semibold text-sm mb-2">Jam Pulang (Kategori 4)</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400"><i class="bi bi-box-arrow-right"></i></div>
                        <input type="time" id="jam_pulang" name="jam_pulang" class="form-control pl-10 w-full rounded-lg border-slate-200 focus:border-indigo-500 focus:ring-indigo-500"
                            value="{{ substr($jamSetting['4'] ?? '17:00:00', 0, 5) }}" required>
                    </div>
                </div>

                <div class="field">
                    <label for="jam_mulai_istirahat" class="block font-semibold text-sm mb-2">Jam Mulai Istirahat (Kategori 2)</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400"><i class="bi bi-cup-hot"></i></div>
                        <input type="time" id="jam_mulai_istirahat" name="jam_mulai_istirahat" class="form-control pl-10 w-full rounded-lg border-slate-200 focus:border-indigo-500 focus:ring-indigo-500"
                            value="{{ substr($jamSetting['2'] ?? '12:00:00', 0, 5) }}" required>
                    </div>
                </div>

                <div class="field">
                    <label for="jam_selesai_istirahat" class="block font-semibold text-sm mb-2">Jam Selesai Istirahat (Kategori 3)</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400"><i class="bi bi-alarm"></i></div>
                        <input type="time" id="jam_selesai_istirahat" name="jam_selesai_istirahat" class="form-control pl-10 w-full rounded-lg border-slate-200 focus:border-indigo-500 focus:ring-indigo-500"
                            value="{{ substr($jamSetting['3'] ?? '13:00:00', 0, 5) }}" required>
                    </div>
                </div>

                <div class="field col-span-1 md:col-span-2">
                    <label for="toleransi" class="block font-semibold text-sm mb-2">Toleransi Keterlambatan Masuk (Menit)</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400"><i class="bi bi-hourglass-split"></i></div>
                        <input type="number" id="toleransi" name="toleransi" min="0" max="180" class="form-control pl-10 w-full rounded-lg border-slate-200 focus:border-indigo-500 focus:ring-indigo-500"
                            value="{{ $jamSetting['toleransi'] ?? 15 }}" required>
                    </div>
                    <small class="text-slate-400 text-xs mt-2 block">
                        Contoh: Jika Jam Masuk diatur 08:00 dan Toleransi 15 menit, maka absen hingga pukul 08:15 masih dihitung <strong class="text-green-600">Tepat Waktu</strong>.
                    </small>
                </div>

            </div>

            <div class="form-actions mt-8 flex justify-end">
                <button type="submit" class="btn-submit inline-flex items-center gap-2 px-6 py-3 bg-indigo-600 text-white font-bold rounded-xl hover:bg-indigo-700 shadow-md transition">
                    <i class="bi bi-check2-circle"></i> Simpan Pengaturan
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Skrip ringan untuk mengubah warna border checkbox saat di-klik --}}
<script>
    document.querySelectorAll('input[name="hari_aktif[]"]').forEach(box => {
        box.addEventListener('change', function() {
            const label = this.parentElement;
            const text = label.querySelector('span');
            const icon = label.querySelector('i');
            
            if(this.checked) {
                label.classList.replace('border-slate-200', 'border-indigo-500');
                label.classList.replace('bg-white', 'bg-indigo-50');
                text.classList.replace('text-slate-600', 'text-indigo-700');
                icon.classList.replace('hidden', 'block');
            } else {
                label.classList.replace('border-indigo-500', 'border-slate-200');
                label.classList.replace('bg-indigo-50', 'bg-white');
                text.classList.replace('text-indigo-700', 'text-slate-600');
                icon.classList.replace('block', 'hidden');
            }
        });
    });
</script>
@endsection