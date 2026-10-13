@extends('layouts.app')

@section('title', 'Ubah Pengguna')

@section('content')
@php
    $inisial = strtoupper(mb_substr($user->nama ?? 'U', 0, 2));
@endphp

<div class="form-page">
    <div class="form-head">
        <a href="{{ route('pengguna.index') }}" class="back-link"><i class="bi bi-arrow-left"></i> Kembali</a>
        <div>
            <h2 class="text-xl font-extrabold m-0">Ubah Data Pengguna</h2>
            <p class="text-sm text-slate-500 m-0">Perbarui informasi pengguna di bawah ini.</p>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <div class="font-bold mb-1"><i class="bi bi-exclamation-triangle-fill"></i> Periksa kembali isian Anda:</div>
            <ul class="mb-0 ps-4">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card" style="overflow:hidden">
        <div class="profile-banner">
            <div class="avatar-lg">{{ $inisial }}</div>
            <div class="min-w-0">
                <div class="text-lg font-extrabold truncate">{{ $user->nama }}</div>
                <div class="text-sm" style="color:#e0e7ff"><i class="bi bi-person-check"></i> Data Profil Pengguna</div>
            </div>
        </div>

        <form method="POST" action="{{ route('pengguna.update', $user->nomor_induk) }}">
            @csrf
            @method('PUT')

            <div class="card-body" style="padding:1.5rem">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-5">

                    <div class="field">
                        <label for="jabatan_status">Status Jabatan</label>
                        <div class="input-icon">
                            <i class="bi bi-briefcase"></i>
                            <select id="jabatan_status" name="jabatan_status" class="form-select @error('jabatan_status') is-invalid @enderror">
                                @foreach ($jabatans as $j)
                                    @php
                                        $roleKey = str_replace(' ', '_', strtolower($j->hak ?? $j->jabatan_status));
                                    @endphp
                                    <option value="{{ $j->id }}" data-role="{{ $roleKey }}" {{ old('jabatan_status', $user->jabatan_status) == $j->id ? 'selected' : '' }}>
                                        {{ $j->jabatan_status }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        @error('jabatan_status') <div class="field-error">{{ $message }}</div> @enderror
                    </div>

                    <div class="field" id="field-nomor-induk-wrapper">
                        <label for="nomor_induk">Nomor Induk (NIS / NIP)</label>
                        <div class="input-icon">
                            <i class="bi bi-person-badge"></i>
                            <input type="text" id="nomor_induk" name="nomor_induk" class="form-control @error('nomor_induk') is-invalid @enderror" value="{{ old('nomor_induk', $user->nomor_induk) }}" required>
                        </div>
                        @error('nomor_induk') <div class="field-error">{{ $message }}</div> @enderror
                    </div>

                    <div class="field">
                        <label for="nama">Nama Lengkap</label>
                        <div class="input-icon">
                            <i class="bi bi-person"></i>
                            <input type="text" id="nama" name="nama" class="form-control @error('nama') is-invalid @enderror" value="{{ old('nama', $user->nama) }}" required>
                        </div>
                        @error('nama') <div class="field-error">{{ $message }}</div> @enderror
                    </div>

                    <div class="field">
                        <label for="cabang_gedung">Lokasi / Cabang</label>
                        <div class="input-icon">
                            <i class="bi bi-geo-alt"></i>
                            <select id="cabang_gedung" name="cabang_gedung" class="form-select @error('cabang_gedung') is-invalid @enderror">
                                @foreach ($lokasis as $l)
                                    <option value="{{ $l->id }}" {{ old('cabang_gedung', $user->cabang_gedung) == $l->id ? 'selected' : '' }}>{{ $l->lokasi }}</option>
                                @endforeach
                            </select>
                        </div>
                        @error('cabang_gedung') <div class="field-error">{{ $message }}</div> @enderror
                    </div>

                    {{-- FIELD KHUSUS SISWA: KELAS & TAG RFID --}}
                    <div class="field md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-x-5" id="field-siswa-wrapper" style="display:none; padding:0; border:none; margin:0;">
                        <div class="field" style="margin-top:0;">
                            <label for="kelas">Kelas <span class="opt">(Khusus Siswa)</span></label>
                            <div class="input-icon">
                                <i class="bi bi-mortarboard"></i>
                                <input type="text" id="kelas" name="kelas" class="form-control @error('kelas') is-invalid @enderror" value="{{ old('kelas', $user->kelas) }}" placeholder="Contoh: 10 RPL 1">
                            </div>
                            @error('kelas') <div class="field-error">{{ $message }}</div> @enderror
                        </div>

                        <div class="field" style="margin-top:0;">
                            <label for="tag">Tag Kartu RFID <span class="opt">(Khusus Siswa)</span></label>
                            <div class="input-icon">
                                <i class="bi bi-upc-scan"></i>
                                <input type="text" id="tag" name="tag" class="form-control @error('tag') is-invalid @enderror" value="{{ old('tag', $user->tag) }}" placeholder="Kode kartu / tag pengguna">
                            </div>
                            @error('tag') <div class="field-error">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    {{-- FIELD KHUSUS ORANG TUA: PILIH ANAK (SISWA) --}}
                    <div class="field md:col-span-2" id="field-anak-wrapper" style="display:none;">
                        <label class="font-semibold text-slate-700 mb-1 block"><i class="bi bi-people-fill text-indigo-600"></i> Pilih Siswa (Anak) yang Dipantau</label>
                        <p class="text-xs text-slate-500 mb-2">Centang satu atau lebih siswa yang merupakan anak dari Orang Tua ini:</p>

                        @php $checkedIds = old('siswa_ids', $selectedSiswaIds ?? []); @endphp

                        <div class="border border-slate-200 rounded-xl p-3 bg-slate-50 max-h-52 overflow-y-auto space-y-2">
                            @forelse ($siswaList as $siswa)
                                <label class="flex items-center gap-3 p-2 rounded-lg bg-white border border-slate-200/80 hover:border-indigo-400 cursor-pointer transition">
                                    <input type="checkbox" name="siswa_ids[]" value="{{ $siswa->nomor_induk }}" class="w-4 h-4 text-indigo-600 rounded" {{ in_array($siswa->nomor_induk, $checkedIds) ? 'checked' : '' }}>
                                    <span class="text-sm font-semibold text-slate-700">{{ $siswa->nama }}</span>
                                    <span class="text-xs text-slate-400 ml-auto">NIS: {{ $siswa->nomor_induk }}</span>
                                </label>
                            @empty
                                <div class="text-sm text-amber-600 py-2"><i class="bi bi-info-circle"></i> Belum ada data pengguna dengan jabatan <strong>Siswa</strong>.</div>
                            @endforelse
                        </div>
                        @error('siswa_ids') <div class="field-error">{{ $message }}</div> @enderror
                    </div>

                    {{-- OPSIONAL UBAH PASSWORD --}}
                    <div class="field">
                        <label for="password">Password Baru <span class="opt">(Kosongkan jika tidak diubah)</span></label>
                        <div class="input-icon">
                            <i class="bi bi-lock"></i>
                            <input type="password" id="password" name="password" class="form-control @error('password') is-invalid @enderror" placeholder="Minimal 8 karakter">
                        </div>
                        @error('password') <div class="field-error">{{ $message }}</div> @enderror
                    </div>

                    <div class="field">
                        <label for="password_confirmation">Konfirmasi Password Baru</label>
                        <div class="input-icon">
                            <i class="bi bi-shield-check"></i>
                            <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" placeholder="Ulangi password baru">
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <a href="{{ route('pengguna.index') }}" class="btn btn-ghost"><i class="bi bi-x-lg"></i> Batal</a>
                <button type="submit" class="btn btn-save"><i class="bi bi-check2-circle"></i> Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<script>
    const jabatanSelect = document.getElementById('jabatan_status');
    const nomorIndukWrapper = document.getElementById('field-nomor-induk-wrapper');
    const nomorIndukInput = document.getElementById('nomor_induk');
    const siswaWrapper = document.getElementById('field-siswa-wrapper');
    const kelasInput = document.getElementById('kelas');
    const tagInput = document.getElementById('tag');
    const anakWrapper = document.getElementById('field-anak-wrapper');

    function handleJabatanChange() {
        const selectedOption = jabatanSelect.options[jabatanSelect.selectedIndex];
        const role = selectedOption ? (selectedOption.getAttribute('data-role') || '') : '';
        const val = jabatanSelect.value;

        const isOrtu = role.includes('orang_tua') || role.includes('orang tua') || val === '4';
        const isSiswa = role.includes('general') || role.includes('siswa') || val === '3';

        if (isOrtu) {
            nomorIndukWrapper.style.display = 'none';
            nomorIndukInput.removeAttribute('required');

            siswaWrapper.style.display = 'none';
            kelasInput.value = '';
            tagInput.value = '';

            anakWrapper.style.display = 'block';
        } else if (isSiswa) {
            nomorIndukWrapper.style.display = 'block';
            nomorIndukInput.setAttribute('required', 'required');

            siswaWrapper.style.display = 'grid';
            anakWrapper.style.display = 'none';
        } else {
            nomorIndukWrapper.style.display = 'block';
            nomorIndukInput.setAttribute('required', 'required');

            siswaWrapper.style.display = 'none';
            kelasInput.value = '';
            tagInput.value = '';
            anakWrapper.style.display = 'none';
        }
    }

    jabatanSelect.addEventListener('change', handleJabatanChange);
    handleJabatanChange();
</script>
@endsection