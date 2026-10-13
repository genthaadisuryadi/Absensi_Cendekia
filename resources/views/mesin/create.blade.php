@extends('layouts.app')

@section('title', 'Tambah Mesin')

@section('content')
<div class="form-page is-narrow">

    <div class="form-head">
        <a href="{{ route('mesin.index') }}" class="back-link"><i class="bi bi-arrow-left"></i> Kembali</a>
        <div>
            <h2 class="text-xl font-extrabold m-0">Tambah Mesin Baru</h2>
            <p class="text-sm text-slate-500 m-0">Daftarkan mesin absensi dan tentukan cabangnya.</p>
        </div>
    </div>

    @include('partials.form-errors')

    <div class="form-card">
        <div class="profile-banner">
            <div class="avatar-lg"><i class="bi bi-cpu-fill"></i></div>
            <div class="min-w-0">
                <p class="banner-name">Mesin Baru</p>
                <p class="banner-sub">Kode, cabang, dan keterangan mesin</p>
            </div>
        </div>

        @include('mesin._form', [
            'action'      => route('mesin.store'),
            'method'      => 'POST',
            'mesin'       => null,
            'cabang'      => $cabangs,
            'submitLabel' => 'Simpan Mesin',
        ])
    </div>
</div>
@endsection
