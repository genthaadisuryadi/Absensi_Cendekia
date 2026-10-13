@extends('layouts.app')

@section('title', 'Pengaturan Jam Absensi')

@section('content')
<div class="form-page">

    <div class="form-head">
        <div>
            <h2 class="text-xl font-extrabold m-0">Pengaturan Jam Absensi</h2>
            <p class="text-sm text-slate-500 m-0">Atur jadwal jam masuk, istirahat, pulang, serta batas toleransi keterlambatan.</p>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success mb-4">
            <i class="bi bi-check-circle-fill me-1"></i> {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger mb-4">
            <ul class="mb-0 ps-4">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="form-card">
        <div class="profile-banner">
            <div class="avatar-lg"><i class="bi bi-clock-history"></i></div>
            <div class="min-w-0">
                <p class="banner-name">Jadwal Waktu Presensi</p>
                <p class="banner-sub">Berlaku untuk seluruh perhitungan status Tepat Waktu & Terlambat di sistem</p>
            </div>
        </div>

        <form action="{{ route('jam-absensi.update') }}" method="POST">
            @csrf
            <div class="form-body">

                <div class="field">
                    <label for="jam_masuk">Jam Masuk (Kategori 1)</label>
                    <div class="input-icon">
                        <i class="bi bi-box-arrow-in-right"></i>
                        <input type="time" id="jam_masuk" name="jam_masuk" class="form-control"
                            value="{{ substr($jamSetting['1'] ?? '08:00:00', 0, 5) }}" required>
                    </div>
                </div>

                <div class="field">
                    <label for="jam_pulang">Jam Pulang (Kategori 4)</label>
                    <div class="input-icon">
                        <i class="bi bi-box-arrow-right"></i>
                        <input type="time" id="jam_pulang" name="jam_pulang" class="form-control"
                            value="{{ substr($jamSetting['4'] ?? '17:00:00', 0, 5) }}" required>
                    </div>
                </div>

                <div class="field">
                    <label for="jam_mulai_istirahat">Jam Mulai Istirahat (Kategori 2)</label>
                    <div class="input-icon">
                        <i class="bi bi-cup-hot"></i>
                        <input type="time" id="jam_mulai_istirahat" name="jam_mulai_istirahat" class="form-control"
                            value="{{ substr($jamSetting['2'] ?? '12:00:00', 0, 5) }}" required>
                    </div>
                </div>

                <div class="field">
                    <label for="jam_selesai_istirahat">Jam Selesai Istirahat (Kategori 3)</label>
                    <div class="input-icon">
                        <i class="bi bi-alarm"></i>
                        <input type="time" id="jam_selesai_istirahat" name="jam_selesai_istirahat" class="form-control"
                            value="{{ substr($jamSetting['3'] ?? '13:00:00', 0, 5) }}" required>
                    </div>
                </div>

                <div class="field full">
                    <label for="toleransi">Toleransi Keterlambatan Masuk (Menit)</label>
                    <div class="input-icon">
                        <i class="bi bi-hourglass-split"></i>
                        <input type="number" id="toleransi" name="toleransi" min="0" max="180" class="form-control"
                            value="{{ $jamSetting['toleransi'] ?? 15 }}" required>
                    </div>
                    <small class="text-slate-400 text-xs mt-1 block">
                        Contoh: Jika Jam Masuk diatur 08:00 dan Toleransi 15 menit, maka absen hingga pukul 08:15 masih dihitung <strong>Tepat Waktu</strong>.
                    </small>
                </div>

            </div>

            <div class="form-actions">
                <button type="submit" class="btn-x btn-submit">
                    <i class="bi bi-check2-circle"></i> Simpan Pengaturan Jam
                </button>
            </div>
        </form>
    </div>
</div>
@endsection