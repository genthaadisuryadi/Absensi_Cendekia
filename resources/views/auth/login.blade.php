<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem Absensi Online</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <!-- Fallback CDN Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <style>
        body { font-family: 'Plus Jakarta Sans', system-ui, sans-serif; }
        .bg-mesh {
            background:
                radial-gradient(60rem 30rem at 10% -10%, rgba(99,102,241,.25), transparent 60%),
                radial-gradient(50rem 30rem at 100% 110%, rgba(168,85,247,.22), transparent 60%),
                #f1f4fb;
        }
        @keyframes rise { from { opacity:0; transform: translateY(14px) } to { opacity:1; transform:none } }
        .rise { animation: rise .5s ease both }
        @keyframes floaty { 0%,100% { transform: translateY(0) } 50% { transform: translateY(-10px) } }
        .floaty { animation: floaty 6s ease-in-out infinite }
    </style>
</head>
<body class="bg-mesh min-h-screen flex items-center justify-center p-4 font-sans antialiased">

    <!-- Card Container Utama -->
    <div class="max-w-4xl w-full bg-white rounded-3xl shadow-2xl shadow-indigo-900/10 overflow-hidden grid grid-cols-1 md:grid-cols-2 border border-white rise">
        
        <!-- Sisi Kiri: Banner Gradien Biru dengan Dekorasi -->
        <div class="relative bg-gradient-to-br from-indigo-600 via-indigo-600 to-violet-700 p-8 md:p-12 text-white flex flex-col justify-between overflow-hidden min-h-[320px] md:min-h-[480px]">
            
            <!-- Ornamen Dekorasi Background -->
            <div class="absolute -top-12 -left-12 w-48 h-48 rounded-full bg-white/10 blur-2xl"></div>
            <div class="absolute -bottom-16 -right-16 w-64 h-64 rounded-full bg-blue-400/20 blur-3xl"></div>
            <div class="absolute inset-0 bg-[radial-gradient(#fff_1px,transparent_1px)] [background-size:16px_16px] opacity-10"></div>

            <!-- Header Banner -->
            <div class="relative z-10">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-2xl bg-white/15 backdrop-blur-md border border-white/20 grid place-items-center text-2xl floaty">
                        <i class="bi bi-fingerprint"></i>
                    </div>
                    <span class="px-3 py-1 bg-white/10 backdrop-blur-md text-[11px] font-bold tracking-widest rounded-full uppercase border border-white/20">
                        Absensi Online
                    </span>
                </div>
            </div>

            <!-- Teks Utama -->
            <div class="relative z-10 my-auto py-6">
                <p class="text-blue-200 text-sm font-medium tracking-wide">Nice to see you again</p>
                <h1 class="text-3xl md:text-4xl font-extrabold tracking-tight mt-1 leading-tight">
                    Selamat Datang
                </h1>
                <div class="w-12 h-1 bg-white/80 rounded-full my-4"></div>
                <p class="text-blue-100 text-xs md:text-sm leading-relaxed max-w-sm">
                    Silakan masuk ke dalam sistem untuk mencatat kehadiran dan mengelola data absensi harian Anda.
                </p>
                <ul class="mt-6 space-y-2.5 text-sm text-indigo-50">
                    <li class="flex items-center gap-2.5"><i class="bi bi-check2-circle text-emerald-300"></i> Rekap kehadiran real-time</li>
                    <li class="flex items-center gap-2.5"><i class="bi bi-check2-circle text-emerald-300"></i> Laporan siap ekspor</li>
                    <li class="flex items-center gap-2.5"><i class="bi bi-check2-circle text-emerald-300"></i> Aman &amp; terintegrasi mesin</li>
                </ul>
            </div>

            <!-- Footer Banner -->
            <div class="relative z-10 text-xs text-blue-200/80">
                &copy; {{ date('Y') }} Sistem Absensi Cendekia
            </div>
        </div>

        <!-- Sisi Kanan: Form Login -->
        <div class="p-8 md:p-12 flex flex-col justify-center bg-white">
            
            <!-- Judul Form -->
            <div class="mb-8">
                <h2 class="text-2xl font-extrabold text-slate-900 tracking-tight">Masuk ke akun</h2>
                <p class="text-xs text-slate-400 mt-1">Masukkan NIS/NIP atau Nama Lengkap beserta password Anda untuk masuk.</p>
            </div>

            <!-- Status Sesi (jika ada) -->
            @if (session('aktif') || session('status'))
                <div class="mb-4 p-3 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl text-xs font-medium">
                    {{ session('aktif') ?? session('status') }}
                </div>
            @endif

            <!-- Pesan Alert Error Validation -->
            @if ($errors->any())
                <div class="mb-5 p-3.5 bg-rose-50 border border-rose-200 text-rose-600 rounded-xl text-xs font-medium space-y-1">
                    @foreach ($errors->all() as $error)
                         <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <!-- Form Transaksi Login -->
            <form method="POST" action="{{ route('login') }}" class="space-y-5">
                @csrf

                <!-- Input Nomor Induk / Nama Lengkap -->
                <div>
                    <label for="nomor_induk" class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-2">
                        ID Login / Nama Lengkap
                    </label>
                    <div class="relative">
                        <i class="bi bi-person absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input 
                            type="text" 
                            name="nomor_induk" 
                            id="nomor_induk" 
                            value="{{ old('nomor_induk') }}"
                            placeholder="Masukkan NIS/NIP atau Nama" 
                            required 
                            autofocus
                            class="w-full pl-11 pr-11 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-4 focus:ring-indigo-500/15 focus:border-indigo-500 focus:bg-white transition-all duration-200"
                        >
                    </div>
                </div>

                <!-- Input Password -->
                <div>
                    <label for="password" class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-2">
                        Password
                    </label>
                    <div class="relative">
                        <i class="bi bi-lock absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <button type="button" id="togglePw" tabindex="-1" aria-label="Tampilkan password"
                            class="absolute right-3 top-1/2 -translate-y-1/2 w-8 h-8 grid place-items-center rounded-lg text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 transition cursor-pointer">
                            <i class="bi bi-eye" id="togglePwIcon"></i>
                        </button>
                        <input 
                            type="password" 
                            name="password" 
                            id="password" 
                            placeholder="••••••••" 
                            required 
                            class="w-full pl-11 pr-11 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-4 focus:ring-indigo-500/15 focus:border-indigo-500 focus:bg-white transition-all duration-200"
                        >
                    </div>
                </div>

                <!-- Tombol Submit -->
                <button 
                    type="submit" 
                    class="w-full py-3.5 px-4 bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-700 hover:to-violet-700 text-white font-bold rounded-xl text-sm shadow-lg shadow-indigo-500/30 hover:-translate-y-0.5 active:scale-[0.99] transition-all duration-200 cursor-pointer"
                >
                    Masuk <i class="bi bi-arrow-right ml-1"></i>
                </button>
            </form>

        </div>

    </div>

<script>
    const pw = document.getElementById('password');
    const ic = document.getElementById('togglePwIcon');
    document.getElementById('togglePw').addEventListener('click', () => {
        const show = pw.type === 'password';
        pw.type = show ? 'text' : 'password';
        ic.className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
    });
</script>
</body>
</html>