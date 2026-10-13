<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $pageTitle = trim($__env->yieldContent('title')) ?: ucwords(str_replace(['-', '_'], ' ', request()->segment(1) ?? 'Dashboard'));
    @endphp
    <title>{{ $pageTitle }} · Absensi Cendekia</title>

    {{-- Font --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    {{-- Font Awesome --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    {{-- Bootstrap Icons --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">

    {{-- Bootstrap --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    {{-- Tailwind CDN --}}
    <script src="https://cdn.tailwindcss.com"></script>

    {{-- Tailwind via Vite --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- DataTables CSS --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">

    {{-- Tema modern (harus paling akhir agar menimpa library lain) --}}
    <link rel="stylesheet" href="{{ asset('css/modern-ui.css') }}?v={{ @filemtime(public_path('css/modern-ui.css')) }}">

    @stack('styles')
</head>

@php
    $user = auth()->user();
    $jabatanStatus = $user?->jabatanStatus;
    $hakAkses = $jabatanStatus?->hakAkses;

    // Normalisasi nama role (huruf kecil & ubah spasi jadi underscore)
    $userRole = str_replace(' ', '_', strtolower(trim($hakAkses?->hak ?? '')));

    // Role SuperAdmin & Admin mendapatkan akses penuh ke menu Manajemen & Pengaturan
    $isAdmin = in_array($userRole, ['superadmin', 'admin', 'nusabot', 'full'], true);
    $isGeneral = in_array($userRole, ['general', 'siswa'], true);
    $isOrtu = in_array($userRole, ['orang_tua'], true);

    $cutiPending = 0;
    if ($isAdmin) {
        try {
            $cutiPending = \App\Models\Cuti::pending()->count();
        } catch (\Throwable $e) {
            // kolom status_persetujuan belum ada (migrasi belum dijalankan)
        }
    }

    $namaUser = $user?->nama ?? ($user?->name ?? 'User');
    $inisial = strtoupper(mb_substr($namaUser, 0, 2));

    $dashboardUrl = $isOrtu ? route('dashboard.ortu') : url('dashboard');

    $menuUtama = [
        [
            'url'    => $dashboardUrl,
            'icon'   => 'bi-grid-1x2-fill',
            'label'  => 'Dashboard',
            'active' => request()->is('dashboard*') || request()->is('/'),
        ],
    ];

    $menuAdmin = [
        ['url' => url('pengguna'), 'icon' => 'bi-people-fill', 'label' => 'Pengguna', 'active' => request()->is('pengguna*')],
        ['url' => url('absensi'), 'icon' => 'bi-card-checklist', 'label' => 'Absensi', 'active' => request()->is('absensi*') && !request()->is('absensi/pengguna*')],
        ['url' => url('cuti'), 'icon' => 'bi-calendar-event-fill', 'label' => 'Cuti', 'active' => request()->is('cuti*'), 'badge' => $cutiPending],
        ['url' => url('libur_khusus'), 'icon' => 'bi-calendar2-heart-fill', 'label' => 'Tanggal Libur', 'active' => request()->is('libur_khusus*')],
        ['url' => url('mesin'), 'icon' => 'bi-cpu-fill', 'label' => 'Mesin', 'active' => request()->is('mesin*')],
    ];

    $menuSetting = [
        ['url' => url('jabatan'), 'label' => 'Jabatan', 'active' => request()->is('jabatan*')],
        ['url' => route('cabang-gedung.index'), 'label' => 'Cabang', 'active' => request()->is('cabang*')],
        ['url' => route('denda.index'), 'label' => 'Denda', 'active' => request()->is('pengaturan/denda*') || request()->is('denda*')],
    ];
    $settingOpen = collect($menuSetting)->contains('active', true);
@endphp

<body class="antialiased">

    <div class="app-shell flex min-h-screen">

        {{-- ================= SIDEBAR ================= --}}
        <aside id="sidebar" class="text-slate-300 flex flex-col justify-between shrink-0">

            <div class="p-3">
                {{-- BRAND --}}
                <div class="flex items-center gap-3 px-2 h-14 mb-1">
                    <div class="sidebar-text overflow-hidden">
                        <div class="font-extrabold text-white text-[15px] tracking-wide leading-tight whitespace-nowrap">ABSENSI</div>
                        <div class="text-[10px] text-indigo-300 font-semibold tracking-[.18em] uppercase whitespace-nowrap">Cendekia System</div>
                    </div>
                </div>

                {{-- USER --}}
                <div class="user-card">
                    <div class="avatar">{{ $inisial }}</div>
                    <div class="sidebar-text min-w-0 flex-1">
                        <p class="text-[10px] text-slate-400 font-medium leading-none m-0">Login sebagai</p>
                        <p class="text-[13px] font-semibold text-white truncate mt-1 mb-0">{{ $namaUser }}</p>
                    </div>
                </div>

                <nav class="mt-1">
                    <div class="nav-label sidebar-text">Menu Utama</div>
                    @foreach ($menuUtama as $m)
                        <a href="{{ $m['url'] }}" title="{{ $m['label'] }}" class="nav-item {{ $m['active'] ? 'active' : '' }}">
                            <i class="bi {{ $m['icon'] }}"></i><span class="sidebar-text truncate">{{ $m['label'] }}</span>
                        </a>
                    @endforeach

                    {{-- SUPERADMIN & ADMIN --}}
                    @if ($isAdmin)
                        <div class="nav-label sidebar-text">Manajemen</div>
                        <div class="space-y-1">
                            @foreach ($menuAdmin as $m)
                                <a href="{{ $m['url'] }}" title="{{ $m['label'] }}" class="nav-item {{ $m['active'] ? 'active' : '' }}">
                                    <i class="bi {{ $m['icon'] }}"></i><span class="sidebar-text truncate">{{ $m['label'] }}</span>
                                    @if (!empty($m['badge']))
                                        <span class="sidebar-text" style="margin-left:auto;background:#f59e0b;color:#fff;border-radius:999px;font-size:.65rem;font-weight:700;padding:.1rem .45rem">{{ $m['badge'] }}</span>
                                    @endif
                                </a>
                            @endforeach

                            <details class="sidebar-details" {{ $settingOpen ? 'open' : '' }}>
                                <summary class="nav-item justify-between" title="Pengaturan">
                                    <span class="flex items-center gap-3">
                                        <i class="bi bi-gear-fill"></i><span class="sidebar-text truncate">Pengaturan</span>
                                    </span>
                                    <i class="bi bi-chevron-down chev sidebar-text"></i>
                                </summary>
                                <div class="sidebar-text mt-1 space-y-0.5">
                                    @foreach ($menuSetting as $s)
                                        <a href="{{ $s['url'] }}" class="nav-sub {{ $s['active'] ? 'active' : '' }}">{{ $s['label'] }}</a>
                                    @endforeach
                                </div>
                            </details>
                        </div>

                    {{-- SISWA / GENERAL USER --}}
                    @elseif ($isGeneral)
                        <div class="nav-label sidebar-text">Absensi Saya</div>
                        <a href="{{ url('/absensi/pengguna') }}" title="Rekap Absensi"
                            class="nav-item {{ request()->is('absensi/pengguna*') ? 'active' : '' }}">
                            <i class="bi bi-clipboard-data-fill"></i><span class="sidebar-text truncate">Rekap Absensi</span>
                        </a>
                        <a href="{{ url('/libur_khusus') }}" title="Libur"
                            class="nav-item {{ request()->is('libur_khusus*') ? 'active' : '' }}">
                            <i class="bi bi-calendar2-heart-fill"></i><span class="sidebar-text truncate">Libur</span>
                        </a>
                        <a href="{{ url('/izin/siswa') }}" title="Izin / Sakit"
                            class="nav-item {{ request()->is('izin/siswa*') ? 'active' : '' }}">
                            <i class="bi bi-file-earmark-medical-fill"></i><span class="sidebar-text truncate">Izin / Sakit</span>
                        </a>

                    {{-- ORANG TUA --}}
                    @elseif ($isOrtu)
                        <div class="nav-label sidebar-text">Informasi Sekolah</div>
                        <a href="{{ url('/libur_khusus') }}" title="Jadwal Libur"
                            class="nav-item {{ request()->is('libur_khusus*') ? 'active' : '' }}">
                            <i class="bi bi-calendar2-heart-fill"></i><span class="sidebar-text truncate">Jadwal Libur</span>
                        </a>
                    @endif
                </nav>
            </div>

            {{-- LOGOUT --}}
            <div class="p-3" style="border-top:1px solid rgba(255,255,255,.06)">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="logout-btn" title="Logout">
                        <i class="bi bi-box-arrow-right"></i><span class="sidebar-text">Keluar</span>
                    </button>
                </form>
            </div>
        </aside>

        <div id="overlay" onclick="toggleSidebar()" class="fixed inset-0 bg-slate-900/50 hidden z-40 lg:hidden" style="backdrop-filter:blur(2px)"></div>

        {{-- ================= KONTEN ================= --}}
        <div class="flex-1 flex flex-col min-w-0">

            <header class="topbar">
                <div class="flex items-center gap-3 min-w-0">
                    <button onclick="toggleSidebar()" class="icon-btn" title="Toggle Sidebar" aria-label="Toggle sidebar">
                        <i class="bi bi-layout-sidebar-inset"></i>
                    </button>
                    <div class="min-w-0">
                        <h1 class="page-title truncate">{{ $pageTitle }}</h1>
                        <p class="page-sub hide-sm" id="today-label">&nbsp;</p>
                    </div>
                </div>

                <div class="flex items-center gap-2">

                    <div class="relative">
                        <div class="user-chip" id="userChip" role="button" tabindex="0">
                            <div class="avatar" style="width:2.1rem;height:2.1rem;font-size:.72rem;border-radius:50%">{{ $inisial }}</div>
                            <span class="hide-sm text-[13px] font-semibold text-slate-700 max-w-[9rem] truncate">{{ $namaUser }}</span>
                            <i class="bi bi-chevron-down text-[10px] text-slate-400 hide-sm"></i>
                        </div>
                        <div class="menu-pop" id="userMenu">
                            <div class="px-3 py-2" style="border-bottom:1px solid var(--line);margin-bottom:.3rem">
                                <div class="text-[13px] font-bold text-slate-800 truncate">{{ $namaUser }}</div>
                                <div class="text-[11px] text-slate-500">{{ $jabatanStatus?->jabatan_status ?? ucfirst($userRole ?? 'Pengguna') }}</div>
                            </div>
                            <a href="{{ route('profile.index') }}"><i class="bi bi-person-circle"></i> Profil Saya</a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" style="color:#e11d48"><i class="bi bi-box-arrow-right"></i> Keluar</button>
                            </form>
                        </div>
                    </div>
                </div>
            </header>

            <main class="page-main flex-1">
                @yield('content')
            </main>

            <footer class="footer-bar">
                © {{ date('Y') }} Absensi Cendekia · Sistem Absensi Online
            </footer>

            <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
            <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
            <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
            <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
            <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
            <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.colVis.min.js"></script>
            <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
            <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
            <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>

            @stack('scripts')
        </div>
    </div>

    <script>
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('overlay');

        function toggleSidebar() {
            if (window.innerWidth <= 1024) {
                sidebar.classList.toggle('show');
                overlay.classList.toggle('hidden');
                return;
            }
            const collapsed = sidebar.classList.toggle('is-collapsed');
            if (collapsed) document.querySelectorAll('.sidebar-details').forEach(d => d.removeAttribute('open'));
            try { localStorage.setItem('sidebarCollapsed', collapsed ? '1' : '0'); } catch (e) {}
        }

        // pulihkan status sidebar (desktop)
        try {
            if (window.innerWidth > 1024 && localStorage.getItem('sidebarCollapsed') === '1') {
                sidebar.classList.add('is-collapsed');
                document.querySelectorAll('.sidebar-details').forEach(d => d.removeAttribute('open'));
            }
        } catch (e) {}

        // menu pengguna
        const chip = document.getElementById('userChip');
        const menu = document.getElementById('userMenu');
        chip.addEventListener('click', e => { e.stopPropagation(); menu.classList.toggle('open'); });
        document.addEventListener('click', () => menu.classList.remove('open'));

        // tanggal
        const tgl = document.getElementById('today-label');
        function setTodayLabel() {
            const n = new Date();
            tgl.textContent = n.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
        }
        setTodayLabel();
    </script>
</body>

</html>