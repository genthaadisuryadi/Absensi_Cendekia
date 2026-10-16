@extends('layouts.app')

@section('title', 'Profil Pengguna')

@section('content')
<div class="container-fluid px-4 py-6 max-w-5xl mx-auto">
    
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-slate-800">Pengaturan Profil</h1>
        <p class="text-slate-500 mt-2">Kelola informasi data diri dan kata sandi Anda</p>
    </div>

    @include('partials.flash')
    @include('partials.form-errors')

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        {{-- Kolom Kiri: Kartu Profil --}}
        <div class="lg:col-span-1">
            <div class="bg-white rounded-3xl p-6 border border-slate-100 shadow-sm flex flex-col items-center text-center relative overflow-hidden group">
                <div class="absolute inset-0 bg-gradient-to-br from-indigo-50 to-blue-50 opacity-50 z-0 group-hover:scale-110 transition duration-500"></div>
                
                {{-- FOTO PROFIL BESAR --}}
                <div class="relative z-10 w-28 h-28 rounded-full bg-gradient-to-tr from-blue-600 to-indigo-500 text-white flex items-center justify-center text-4xl font-extrabold mb-5 shadow-xl ring-8 ring-white overflow-hidden">
                    @if($user->foto_profil)
                        <img src="{{ asset('storage/' . $user->foto_profil) }}" alt="Foto Profil" class="w-full h-full object-cover">
                    @else
                        {{ substr($user->nama, 0, 1) }}
                    @endif
                </div>
                
                <h2 class="relative z-10 text-xl font-extrabold text-slate-800">{{ $user->nama }}</h2>
                <p class="relative z-10 text-sm font-bold text-indigo-600 mt-2 bg-indigo-50 px-4 py-1.5 rounded-full inline-flex items-center gap-1.5">
                    <i class="bi bi-shield-fill-check"></i> {{ $user->tag === 'admin' ? 'Administrator' : 'Siswa/Guru' }}
                </p>
                
                <div class="relative z-10 w-full mt-6 pt-6 border-t border-slate-200/60 text-left">
                    <div class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mb-3">Info Akun</div>
                    
                    <div class="flex items-center gap-4 mb-3 p-3 bg-white rounded-2xl border border-slate-50 shadow-sm">
                        <div class="w-10 h-10 rounded-xl bg-cyan-50 flex items-center justify-center text-cyan-500">
                            <i class="bi bi-person-vcard text-lg"></i>
                        </div>
                        <div>
                            <div class="text-xs text-slate-400 font-medium">Nomor Induk</div>
                            <div class="text-sm font-bold text-slate-700">{{ $user->nomor_induk }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Kolom Kanan: Form Edit --}}
        <div class="lg:col-span-2">
            <div class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">
                <div class="px-7 py-5 border-b border-slate-100 bg-slate-50/50">
                    <h3 class="font-extrabold text-slate-800 flex items-center gap-2">
                        <i class="bi bi-pencil-square text-blue-500"></i> Perbarui Data Diri
                    </h3>
                </div>
                
                <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="p-7">
                    @csrf
                    @method('PATCH')

                    <div class="space-y-6">
                        
                        {{-- Input Foto --}}
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Upload Foto Profil Baru (Opsional)</label>
                            <input type="file" name="foto_profil" accept="image/jpeg,image/png,image/gif"
                                class="w-full text-sm text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-bold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 border border-slate-200 rounded-xl cursor-pointer bg-slate-50">
                            <p class="text-xs text-slate-400 mt-2">Maksimal ukuran file 2MB (JPG/PNG).</p>
                        </div>

                        {{-- Input Nama --}}
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Nama Lengkap</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <i class="bi bi-person text-slate-400"></i>
                                </div>
                                <input type="text" name="nama" value="{{ old('nama', $user->nama) }}" required
                                    class="w-full h-12 pl-11 pr-4 rounded-xl border border-slate-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all shadow-sm bg-slate-50 focus:bg-white text-slate-800 font-medium outline-none">
                            </div>
                        </div>

                        {{-- Divider --}}
                        <div class="pt-4 border-t border-slate-100">
                            <h4 class="text-sm font-bold text-slate-800 mb-1 flex items-center gap-2">
                                <i class="bi bi-key text-amber-500"></i> Keamanan Akun
                            </h4>
                            <p class="text-xs text-slate-500 mb-4">Abaikan kolom ini jika Anda tidak ingin mengubah kata sandi.</p>
                        </div>

                        {{-- Input Password --}}
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Kata Sandi Baru</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <i class="bi bi-lock text-slate-400"></i>
                                </div>
                                <input type="password" name="password" placeholder="Masukkan kata sandi baru (min. 8 karakter)"
                                    class="w-full h-12 pl-11 pr-4 rounded-xl border border-slate-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all shadow-sm bg-slate-50 focus:bg-white text-slate-800 font-medium outline-none">
                            </div>
                        </div>

                    </div>

                    <div class="mt-8 pt-6 border-t border-slate-100 flex flex-col-reverse sm:flex-row items-center justify-end gap-3">
                        <a href="{{ route('dashboard') }}" class="w-full sm:w-auto text-center px-6 py-3 rounded-xl font-bold text-slate-600 hover:bg-slate-100 hover:text-slate-800 transition">
                            Batal
                        </a>
                        <button type="submit" class="w-full sm:w-auto px-8 py-3 bg-blue-600 text-white rounded-xl font-bold shadow-md hover:bg-blue-700 hover:shadow-lg transition-all transform hover:-translate-y-0.5 flex items-center justify-center gap-2">
                            <i class="bi bi-check2-circle text-lg"></i> Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>
@endsection