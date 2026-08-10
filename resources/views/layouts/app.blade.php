<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Sistem Informasi Dispensasi')</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600&family=Plus+Jakarta+Sans:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: { DEFAULT: '#163A5C', dark: '#0F2A44' },
                        accent: { DEFAULT: '#2BAFC7', soft: '#E1F5F8' },
                        secondary: { DEFAULT: '#A9BE2E', soft: '#F3F6D9' },
                        ink: { DEFAULT: '#1B2A3D', soft: '#5C6B78' },
                        canvas: '#F5F8FA',
                        line: '#E2E8EC',
                    },
                    fontFamily: {
                        display: ['Fraunces', 'serif'],
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                        mono: ['"IBM Plex Mono"', 'monospace'],
                    },
                    borderRadius: { xl2: '1rem' },
                }
            }
        }
    </script>

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #F5F8FA; color: #1B2A3D; }
        [x-cloak] { display: none !important; }

        .flow-bar { height: 3px; background: linear-gradient(90deg, #2BAFC7 0%, #A9BE2E 100%); }

        .card { background: #fff; border: 1px solid #E2E8EC; border-radius: 1rem; box-shadow: 0 1px 2px rgba(22,58,92,0.05); }

        .btn { display: inline-flex; align-items: center; justify-content: center; gap: .375rem; padding: .55rem 1.1rem; border-radius: .6rem; font-size: .875rem; font-weight: 600; transition: background-color .15s, border-color .15s, color .15s; }
        .btn-primary { background: #2BAFC7; color: #fff; }
        .btn-primary:hover { background: #24a0b6; }
        .btn-outline { background: #fff; color: #163A5C; border: 1px solid #E2E8EC; }
        .btn-outline:hover { background: #F5F8FA; }
        .btn-danger { background: #C1483A; color: #fff; }
        .btn-danger:hover { background: #a53c30; }
        .btn-sm { padding: .35rem .75rem; font-size: .75rem; }

        .field-label { display: block; font-size: .8rem; font-weight: 600; color: #1B2A3D; margin-bottom: .35rem; }
        .field-input { width: 100%; border: 1px solid #E2E8EC; border-radius: .6rem; padding: .6rem .8rem; font-size: .9rem; background: #fff; color: #1B2A3D; transition: border-color .15s, box-shadow .15s; }
        .field-input:focus { outline: none; border-color: #2BAFC7; box-shadow: 0 0 0 3px #E1F5F8; }
        .field-error { color: #C1483A; font-size: .8rem; margin-top: .3rem; }

        .badge { display: inline-flex; align-items: center; gap: .3rem; padding: .2rem .65rem; border-radius: 999px; font-size: .72rem; font-weight: 600; }
        .badge::before { content: ''; width: .4rem; height: .4rem; border-radius: 999px; background: currentColor; }
        .badge-diajukan { background: #FBF0DF; color: #C8862B; }
        .badge-disetujui { background: #E1F3EA; color: #2E9E6B; }
        .badge-ditolak { background: #FBE7E4; color: #C1483A; }
        .badge-neutral { background: #EEF2F1; color: #5C6B78; }

        .table-pro { width: 100%; border-collapse: separate; border-spacing: 0; font-size: .875rem; }
        .table-pro thead th { text-align: left; font-size: .72rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; color: #5C6B78; background: #F5F8FA; padding: .7rem 1.1rem; }
        .table-pro tbody td { padding: .8rem 1.1rem; border-top: 1px solid #E2E8EC; }
        .table-pro tbody tr:hover { background: #FAFCFD; }
        .mono-data { font-family: 'IBM Plex Mono', monospace; font-size: .82rem; }

        a:focus-visible, button:focus-visible, input:focus-visible, select:focus-visible, textarea:focus-visible {
            outline: 2px solid #2BAFC7; outline-offset: 2px;
        }

        @media (prefers-reduced-motion: reduce) {
            * { transition: none !important; }
        }
    </style>

    <script>
        document.addEventListener('submit', function (e) {
            const form = e.target;
            if (!(form instanceof HTMLFormElement)) return;

            if (form.dataset.submitted === 'true') {
                e.preventDefault();
                return;
            }
            form.dataset.submitted = 'true';

            form.querySelectorAll('button[type="submit"], button:not([type])').forEach(function (btn) {
                btn.disabled = true;
                btn.dataset.originalText = btn.innerHTML;
                btn.innerHTML = 'Memproses...';
            });
        }, true);
    </script>

</head>
<body class="min-h-screen">

    @auth
    <nav class="bg-white shadow-sm sticky top-0 z-30">
        <div class="max-w-6xl mx-auto px-6 py-4 flex justify-between items-center">
            <a href="{{ url('/') }}" class="flex items-center gap-3">
                @if (file_exists(public_path('images/logo1.png')))
                    <img src="{{ asset('images/logo1.png') }}" alt="Logo Tirta Mayang" class="h-9 w-auto">
                @else
                    <svg width="30" height="30" viewBox="0 0 24 24" fill="none">
                        <defs>
                            <linearGradient id="dropGrad" x1="4" y1="2" x2="20" y2="22" gradientUnits="userSpaceOnUse">
                                <stop offset="0" stop-color="#163A5C"/>
                                <stop offset="1" stop-color="#2BAFC7"/>
                            </linearGradient>
                        </defs>
                        <path d="M12 2C12 2 5 10.5 5 15C5 18.866 8.134 22 12 22C15.866 22 19 18.866 19 15C19 10.5 12 2 12 2Z" fill="url(#dropGrad)"/>
                    </svg>
                @endif
                <div class="leading-tight">
                    <p class="text-primary font-bold text-sm tracking-wide">TIRTA MAYANG</p>
                    <p class="text-accent text-xs font-medium">Sistem Informasi Dispensasi</p>
                </div>
            </a>

            <div class="flex items-center gap-5">
                {{-- Notifikasi --}}
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open; if (open) { fetch('{{ route('notifikasi.markAllRead') }}', { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } }) }"
                            class="relative text-ink-soft hover:text-primary">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M18 8a6 6 0 10-12 0c0 7-3 9-3 9h18s-3-2-3-9"/>
                            <path d="M13.73 21a2 2 0 01-3.46 0"/>
                        </svg>
                        @if (auth()->user()->unreadNotifications->count() > 0)
                        <span class="absolute -top-1.5 -right-1.5 bg-secondary text-primary-dark text-[10px] font-bold rounded-full px-1.5 py-0.5">
                            {{ auth()->user()->unreadNotifications->count() }}
                        </span>
                        @endif
                    </button>

                    <div x-show="open" @click.outside="open = false" x-cloak
                        class="absolute right-0 mt-3 w-80 bg-white border border-line rounded-xl2 shadow-lg z-20 max-h-96 overflow-y-auto">
                        @forelse (auth()->user()->notifications->take(6) as $notif)
                        <a href="{{ $notif->data['url'] ?? '#' }}"
                        class="block px-4 py-3 border-b border-line text-sm hover:bg-canvas {{ $notif->read_at ? 'text-ink-soft' : 'text-ink font-medium' }}">
                            {{ $notif->data['pesan'] ?? 'Notifikasi baru' }}
                            <div class="text-ink-soft text-xs mt-1 font-normal">{{ $notif->created_at->diffForHumans() }}</div>
                        </a>
                        @empty
                        <p class="px-4 py-4 text-sm text-ink-soft">Belum ada notifikasi.</p>
                        @endforelse
                    </div>
                </div>

                <div class="h-6 w-px bg-line"></div>

                <div class="text-right leading-tight hidden sm:block">
                    <p class="text-ink text-sm font-medium">{{ auth()->user()->name }}</p>
                    <p class="text-accent text-xs capitalize">{{ str_replace('_', ' ', auth()->user()->role) }}</p>
                </div>

                <form method="POST" action="{{ route('logout') }}" onsubmit="return confirm('Yakin ingin keluar dari sistem?')">
                    @csrf
                    <button class="text-ink-soft hover:text-primary" title="Logout">
                        <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/>
                            <path d="M16 17l5-5-5-5"/>
                            <path d="M21 12H9"/>
                        </svg>
                    </button>
                </form>
            </div>
        </div>
        <div class="flow-bar"></div>
    </nav>
    @endauth

    <main class="max-w-6xl mx-auto px-6 py-10">
        @if (session('success'))
            <div class="mb-6 bg-accent-soft border border-accent/30 text-primary px-4 py-3 rounded-xl2 text-sm font-medium">
                {{ session('success') }}
            </div>
        @endif
        @if (session('warning'))
            <div class="mb-6 bg-[#FBF0DF] border border-[#C8862B]/30 text-[#8a5f1e] px-4 py-3 rounded-xl2 text-sm">
                {{ session('warning') }}
            </div>
        @endif

        @yield('content')
    </main>

</body>
</html>