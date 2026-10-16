@extends('layouts.app')

@section('title', 'Tambah Pengguna')

@section('content')
<div class="form-page">

    {{-- HEADER --}}
    <div class="form-head">
        <a href="{{ route('pengguna.index') }}" class="back-link"><i class="bi bi-arrow-left"></i> Kembali</a>
        <div>
            <h2 class="text-xl font-extrabold m-0">Tambah Pengguna Baru</h2>
            <p class="text-sm text-slate-500 m-0">Lengkapi data di bawah untuk mendaftarkan pengguna.</p>
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

    <div class="form-card">
        <div class="profile-banner">
            <div class="avatar-lg"><i class="bi bi-person-plus-fill"></i></div>
            <div class="min-w-0">
                <p class="banner-name">Pengguna Baru</p>
                <p class="banner-sub">Data identitas, penempatan, dan keamanan akun</p>
            </div>
        </div>

        <form action="{{ route('pengguna.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="form-body">
                {{-- ===== UPLOAD FOTO ===== --}}
                <div class="field full flex flex-col items-center justify-center mb-6">
                    <label class="font-semibold text-slate-700 mb-2 block">Foto Profil (Opsional)</label>
                    <div class="relative group cursor-pointer" onclick="document.getElementById('foto_profil').click()">
                        <div id="foto-preview-container" class="w-32 h-32 rounded-full overflow-hidden border-4 border-slate-100 bg-slate-50 flex items-center justify-center shadow-sm relative group-hover:border-indigo-200 transition">
                            <i class="bi bi-camera text-4xl text-slate-300" id="foto-icon"></i>
                            <img id="foto-preview" class="w-full h-full object-cover hidden" alt="Preview Foto">
                            
                            {{-- Overlay Hover --}}
                            <div class="absolute inset-0 bg-black/40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition">
                                <span class="text-white text-xs font-semibold">Ubah Foto</span>
                            </div>
                        </div>
                    </div>
                    <p class="text-xs text-slate-400 mt-2 text-center">Format: JPG, PNG, GIF (Maks. 2MB)</p>
                    <input type="file" id="foto_profil" name="foto_profil" class="hidden" accept="image/jpeg,image/png,image/gif" onchange="previewImage(this)">
                    @error('foto_profil') <div class="field-error mt-1 text-center">{{ $message }}</div> @enderror
                </div>

                <div class="field full" style="font-size:.7rem;font-weight:800;letter-spacing:.12em;text-transform:uppercase;color:#6366f1;">
                    <i class="bi bi-person-lines-fill"></i> Data Utama
                </div>

                <div class="field">
                    <label for="jabatan_status">Status Jabatan</label>
                    <div class="input-icon">
                        <i class="bi bi-briefcase"></i>
                        <select id="jabatan_status" name="jabatan_status" class="form-select @error('jabatan_status') is-invalid @enderror" required>
                            <option value="">-- Pilih Jabatan --</option>
                            @foreach ($jabatans as $jab)
                                @php
                                    $roleKey = str_replace(' ', '_', strtolower($jab->hak ?? $jab->jabatan_status));
                                @endphp
                                <option value="{{ $jab->id }}" data-role="{{ $roleKey }}" {{ old('jabatan_status') == $jab->id ? 'selected' : '' }}>
                                    {{ $jab->jabatan_status }}
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
                        <input type="text" id="nomor_induk" name="nomor_induk" class="form-control @error('nomor_induk') is-invalid @enderror" value="{{ old('nomor_induk') }}" placeholder="Contoh: 12300000" required>
                    </div>
                    @error('nomor_induk') <div class="field-error">{{ $message }}</div> @enderror
                </div>

                <div class="field">
                    <label for="nama">Nama Lengkap</label>
                    <div class="input-icon">
                        <i class="bi bi-person"></i>
                        <input type="text" id="nama" name="nama" class="form-control @error('nama') is-invalid @enderror" value="{{ old('nama') }}" placeholder="Nama lengkap" required>
                    </div>
                    @error('nama') <div class="field-error">{{ $message }}</div> @enderror
                </div>

                <div class="field">
                    <label for="cabang_gedung">Cabang Gedung</label>
                    <div class="input-icon">
                        <i class="bi bi-geo-alt"></i>
                        <select id="cabang_gedung" name="cabang_gedung" class="form-select @error('cabang_gedung') is-invalid @enderror" required>
                            <option value="">-- Pilih Lokasi --</option>
                            @foreach ($lokasis as $lok)
                                <option value="{{ $lok->id }}" {{ old('cabang_gedung') == $lok->id ? 'selected' : '' }}>{{ $lok->lokasi }}</option>
                            @endforeach
                        </select>
                    </div>
                    @error('cabang_gedung') <div class="field-error">{{ $message }}</div> @enderror
                </div>

                {{-- FIELD KHUSUS SISWA: KELAS & TAG RFID --}}
                <div class="field full grid grid-cols-1 md:grid-cols-2 gap-x-5" id="field-siswa-wrapper" style="display:none; padding:0; border:none; margin:0;">
                    <div class="field" style="margin-top:0;">
                        <label for="kelas">Kelas <span class="opt">(Khusus Siswa)</span></label>
                        <div class="input-icon">
                            <i class="bi bi-mortarboard"></i>
                            <input type="text" id="kelas" name="kelas" class="form-control @error('kelas') is-invalid @enderror" value="{{ old('kelas') }}" placeholder="Contoh: 10 RPL 1">
                        </div>
                        @error('kelas') <div class="field-error">{{ $message }}</div> @enderror
                    </div>

                    <div class="field" style="margin-top:0;">
                        <label for="tag">Tag Kartu RFID <span class="opt">(Khusus Siswa)</span></label>
                        <div class="input-icon">
                            <i class="bi bi-upc-scan"></i>
                            <input type="text" id="tag" name="tag" class="form-control @error('tag') is-invalid @enderror" value="{{ old('tag') }}" placeholder="Scan kartu / isi UID manual">
                        </div>
                        @error('tag') <div class="field-error">{{ $message }}</div> @enderror
                    </div>
                </div>

                {{-- FIELD KHUSUS ORANG TUA: PILIH ANAK (SISWA) DENGAN SEARCH --}}
                <div class="field full" id="field-anak-wrapper" style="display:none;">
                    <label class="font-semibold text-slate-700 mb-1 block"><i class="bi bi-people-fill text-indigo-600"></i> Pilih Siswa (Anak) yang Dipantau</label>
                    <p class="text-xs text-slate-500 mb-2">Cari dan centang satu atau lebih siswa yang merupakan anak dari Orang Tua ini:</p>
                    
                    {{-- Kotak Pencarian Siswa --}}
                    <div class="relative mb-2">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text" id="searchSiswaInput" onkeyup="filterSiswaList()" placeholder="Ketik nama atau NIS siswa untuk mencari..." class="w-full pl-9 pr-4 py-2 text-sm bg-white border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>

                    <div id="siswaListContainer" class="border border-slate-200 rounded-xl p-3 bg-slate-50 max-h-52 overflow-y-auto space-y-2">
                        @forelse ($siswaList as $siswa)
                            <label class="siswa-item flex items-center gap-3 p-2 rounded-lg bg-white border border-slate-200/80 hover:border-indigo-400 cursor-pointer transition" data-name="{{ strtolower($siswa->nama) }}" data-nis="{{ strtolower($siswa->nomor_induk) }}">
                                <input type="checkbox" name="siswa_ids[]" value="{{ $siswa->nomor_induk }}" class="w-4 h-4 text-indigo-600 rounded" {{ in_array($siswa->nomor_induk, old('siswa_ids', [])) ? 'checked' : '' }}>
                                <span class="text-sm font-semibold text-slate-700">{{ $siswa->nama }}</span>
                                <span class="text-xs text-slate-400 ml-auto">NIS: {{ $siswa->nomor_induk }}</span>
                            </label>
                        @empty
                            <div class="text-sm text-amber-600 py-2"><i class="bi bi-info-circle"></i> Belum ada data pengguna dengan jabatan <strong>Siswa</strong>.</div>
                        @endforelse
                    </div>
                    @error('siswa_ids') <div class="field-error">{{ $message }}</div> @enderror
                </div>

                {{-- ===== KEAMANAN AKUN ===== --}}
                <div class="field full" style="margin-top:.5rem;padding-top:1.25rem;border-top:1px dashed #e2e8f0;font-size:.7rem;font-weight:800;letter-spacing:.12em;text-transform:uppercase;color:#d97706;">
                    <i class="bi bi-shield-lock-fill"></i> Keamanan Akun
                </div>

                <div class="field">
                    <label for="password">Password</label>
                    <div class="input-icon">
                        <i class="bi bi-lock"></i>
                        <input type="password" id="password" name="password" style="padding-right:2.8rem" class="form-control @error('password') is-invalid @enderror" placeholder="Minimal 8 karakter" required>
                        <button type="button" class="pw-toggle" data-target="password" tabindex="-1" style="position:absolute;right:.5rem;top:50%;transform:translateY(-50%);width:2rem;height:2rem;border:0;background:none;color:#94a3b8;cursor:pointer;"><i class="bi bi-eye"></i></button>
                    </div>
                    @error('password') <div class="field-error">{{ $message }}</div> @enderror
                </div>

                <div class="field">
                    <label for="password_confirmation">Konfirmasi Password</label>
                    <div class="input-icon">
                        <i class="bi bi-shield-check"></i>
                        <input type="password" id="password_confirmation" name="password_confirmation" style="padding-right:2.8rem" class="form-control" placeholder="Ulangi password" required>
                        <button type="button" class="pw-toggle" data-target="password_confirmation" tabindex="-1" style="position:absolute;right:.5rem;top:50%;transform:translateY(-50%);width:2rem;height:2rem;border:0;background:none;color:#94a3b8;cursor:pointer;"><i class="bi bi-eye"></i></button>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <a href="{{ route('pengguna.index') }}" class="btn-x btn-cancel"><i class="bi bi-x-lg"></i> Batal</a>
                <button type="submit" class="btn-x btn-submit"><i class="bi bi-check2-circle"></i> Simpan Pengguna</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Fitur Live Search Siswa
    function filterSiswaList() {
        const input = document.getElementById('searchSiswaInput');
        const filter = input.value.toLowerCase();
        const items = document.querySelectorAll('.siswa-item');

        items.forEach(item => {
            const name = item.getAttribute('data-name');
            const nis = item.getAttribute('data-nis');
            if (name.includes(filter) || nis.includes(filter)) {
                item.style.display = 'flex';
            } else {
                item.style.display = 'none';
            }
        });
    }

    // Preview Gambar
    function previewImage(input) {
        const icon = document.getElementById('foto-icon');
        const preview = document.getElementById('foto-preview');
        
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.classList.remove('hidden');
                icon.classList.add('hidden');
            }
            
            reader.readAsDataURL(input.files[0]);
        } else {
            preview.src = "";
            preview.classList.add('hidden');
            icon.classList.remove('hidden');
        }
    }

    document.querySelectorAll('.pw-toggle').forEach(btn => {
        btn.addEventListener('click', () => {
            const input = document.getElementById(btn.dataset.target);
            const icon = btn.querySelector('i');
            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            icon.className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
        });
    });

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
            nomorIndukInput.value = '';

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