@extends('layouts.app')

@section('title', 'Ubah Mesin')

@section('content')
<div class="form-page is-narrow">

    <div class="form-head">
        <a href="{{ route('mesin.index') }}" class="back-link"><i class="bi bi-arrow-left"></i> Kembali</a>
        <div>
            <h2 class="text-xl font-extrabold m-0">Ubah Data Mesin</h2>
            <p class="text-sm text-slate-500 m-0">Perbarui informasi mesin di bawah ini.</p>
        </div>
    </div>

    @include('partials.form-errors')

    <div class="form-card">
        <div class="profile-banner">
            <div class="avatar-lg"><i class="bi bi-cpu-fill"></i></div>
            <div class="min-w-0">
                <p class="banner-name truncate">{{ $mesin->idmesin }}</p>
                <p class="banner-sub truncate"><i class="bi bi-geo-alt"></i> {{ $mesin->cabangGedung->lokasi ?? 'Belum ada cabang' }}</p>
            </div>
        </div>

        @include('mesin._form', [
            'action'      => route('mesin.update', $mesin->idmesin),
            'method'      => 'PUT',
            'mesin'       => $mesin,
            'cabang'      => $cabangs,
            'submitLabel' => 'Simpan Perubahan',
        ])
    </div>
</div>
@endsection
