<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk — {{ config('app.name', 'Rumat') }}</title>
    <meta name="robots" content="noindex">
    <link rel="icon" href="{{ asset('img/logo.svg') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">

    <style>
        :root {
            --ink: #004236;
            --mid: #618764;
            --paper: #f1f3f5;
            --rule: #d5dae0;
            --text: #1f3a24;
            --muted: #5c6b60;
            --danger: #b42318;
        }

        * { box-sizing: border-box; }

        html, body { height: 100%; }

        body {
            margin: 0;
            font-family: 'Inter', system-ui, sans-serif;
            color: var(--text);
            background: var(--paper);
            -webkit-font-smoothing: antialiased;
        }

        a { color: inherit; }

        .shell {
            display: grid;
            grid-template-columns: minmax(0, 1.05fr) minmax(0, .95fr);
            min-height: 100vh;
        }

        /* ---------- Brand panel ---------- */
        .brand {
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: clamp(2rem, 5vw, 4rem);
            color: #fff;
            overflow: hidden;
            background:
                linear-gradient(150deg, rgba(0, 66, 54, .94), rgba(0, 33, 27, .88)),
                url('{{ asset('img/ft.webp') }}') center / cover no-repeat;
        }

        .brand::after {
            content: '';
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(to right, rgba(255,255,255,.06) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(255,255,255,.06) 1px, transparent 1px);
            background-size: 56px 56px;
            pointer-events: none;
            mask-image: linear-gradient(to bottom, #000 35%, transparent 100%);
        }

        .brand > * { position: relative; z-index: 1; }

        .brand-logo {
            width: auto;
            height: 3rem;
            object-fit: contain;
        }

        .brand-copy { max-width: 30rem; margin-top: 3rem; }

        .eyebrow {
            margin: 0 0 .9rem;
            font-family: 'JetBrains Mono', monospace;
            font-size: .68rem;
            letter-spacing: .22em;
            text-transform: uppercase;
            color: rgba(255,255,255,.6);
        }

        .brand-copy h1 {
            margin: 0;
            font-size: clamp(1.8rem, 3.4vw, 2.75rem);
            line-height: 1.1;
            letter-spacing: -.02em;
            font-weight: 700;
        }

        .brand-copy p {
            margin: 1.1rem 0 0;
            font-size: .95rem;
            line-height: 1.7;
            color: rgba(255,255,255,.72);
        }

        .brand-points {
            display: grid;
            gap: .75rem;
            margin: 2.5rem 0 0;
            padding: 0;
            list-style: none;
        }

        .brand-points li {
            display: flex;
            align-items: center;
            gap: .65rem;
            font-size: .85rem;
            color: rgba(255,255,255,.8);
        }

        .brand-points span {
            display: grid;
            place-items: center;
            width: 1.4rem;
            height: 1.4rem;
            flex-shrink: 0;
            border-radius: .4rem;
            background: rgba(255,255,255,.12);
            border: 1px solid rgba(255,255,255,.16);
        }

        .brand-points svg { width: .8rem; height: .8rem; }

        .brand-foot {
            margin: 3rem 0 0;
            font-family: 'JetBrains Mono', monospace;
            font-size: .66rem;
            letter-spacing: .16em;
            text-transform: uppercase;
            color: rgba(255,255,255,.45);
        }

        /* ---------- Form panel ---------- */
        .panel {
            display: grid;
            place-items: center;
            padding: clamp(1.5rem, 5vw, 3.5rem);
        }

        .card {
            width: 100%;
            max-width: 25rem;
        }

        .card-logo {
            display: none;
            height: 2.75rem;
            width: auto;
            margin-bottom: 1.75rem;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: .45rem;
            padding: .3rem .7rem;
            border-radius: 999px;
            border: 1px solid var(--rule);
            background: #fff;
            font-family: 'JetBrains Mono', monospace;
            font-size: .62rem;
            letter-spacing: .16em;
            text-transform: uppercase;
            color: var(--mid);
        }

        .badge::before {
            content: '';
            width: .4rem;
            height: .4rem;
            border-radius: 999px;
            background: var(--mid);
        }

        .card-head h2 {
            margin: 1.1rem 0 .4rem;
            font-size: 1.55rem;
            letter-spacing: -.02em;
            color: var(--text);
        }

        .card-head p {
            margin: 0;
            font-size: .875rem;
            line-height: 1.6;
            color: var(--muted);
        }

        .alert {
            display: flex;
            gap: .6rem;
            align-items: flex-start;
            margin-top: 1.4rem;
            padding: .8rem .95rem;
            border-radius: .65rem;
            border: 1px solid #f2c4bf;
            background: #fdf3f2;
            color: var(--danger);
            font-size: .82rem;
            line-height: 1.5;
        }

        .alert svg { width: 1rem; height: 1rem; flex-shrink: 0; margin-top: .1rem; }

        form { margin-top: 1.6rem; }

        .field { margin-bottom: 1.05rem; }

        .field label {
            display: block;
            margin-bottom: .45rem;
            font-size: .78rem;
            font-weight: 600;
            letter-spacing: .01em;
            color: var(--text);
        }

        .control { position: relative; }

        .control input {
            width: 100%;
            height: 2.9rem;
            padding: 0 .95rem;
            border: 1px solid var(--rule);
            border-radius: .65rem;
            background: #fff;
            color: var(--text);
            font-size: .9rem;
            font-family: inherit;
            transition: border-color .15s ease, box-shadow .15s ease;
        }

        .control input::placeholder { color: #9aa7a0; }

        .control input:focus {
            outline: none;
            border-color: var(--mid);
            box-shadow: 0 0 0 3px rgba(97, 135, 100, .18);
        }

        .control.has-error input { border-color: #e0a39c; }

        .control.has-toggle input { padding-right: 3rem; }

        .toggle {
            position: absolute;
            top: 50%;
            right: .45rem;
            transform: translateY(-50%);
            display: grid;
            place-items: center;
            width: 2rem;
            height: 2rem;
            border: 0;
            border-radius: .45rem;
            background: transparent;
            color: var(--muted);
            cursor: pointer;
        }

        .toggle:hover { background: var(--paper); color: var(--text); }
        .toggle svg { width: 1.05rem; height: 1.05rem; }

        .error {
            margin: .4rem 0 0;
            font-size: .75rem;
            color: var(--danger);
        }

        .row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            margin: .35rem 0 1.5rem;
        }

        .check {
            display: inline-flex;
            align-items: center;
            gap: .55rem;
            font-size: .8rem;
            color: var(--muted);
            cursor: pointer;
            user-select: none;
        }

        .check input {
            width: 1rem;
            height: 1rem;
            accent-color: var(--ink);
            cursor: pointer;
        }

        .submit {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .55rem;
            width: 100%;
            height: 2.95rem;
            border: 0;
            border-radius: .65rem;
            background: var(--ink);
            color: #fff;
            font-family: inherit;
            font-size: .88rem;
            font-weight: 600;
            letter-spacing: .01em;
            cursor: pointer;
            transition: background .15s ease, transform .05s ease;
        }

        .submit:hover { background: #003128; }
        .submit:active { transform: translateY(1px); }
        .submit svg { width: 1rem; height: 1rem; }

        .hint {
            margin: 1.6rem 0 0;
            padding-top: 1.25rem;
            border-top: 1px solid var(--rule);
            font-size: .76rem;
            line-height: 1.6;
            color: var(--muted);
        }

        .hint strong { color: var(--text); font-weight: 600; }

        @media (max-width: 900px) {
            .shell { grid-template-columns: 1fr; }
            .brand { display: none; }
            .card-logo { display: block; }
            .panel { min-height: 100vh; }
        }
    </style>
</head>
<body>
    <div class="shell">
        <aside class="brand">
            <img src="{{ asset('img/dk.png') }}" alt="{{ config('app.name', 'Rumat') }}" class="brand-logo">

            <div>
                <div class="brand-copy">
                    <p class="eyebrow">Sistem Manajemen Pemeliharaan Aset</p>
                    <h1>Satu pintu untuk operasional lapangan.</h1>
                    <p>
                        Work order, checklist, dokumentasi foto, dan pekerjaan housekeeping
                        dalam satu alur yang rapi — dari supervisor sampai petugas.
                    </p>
                </div>

                <ul class="brand-points">
                    <li>
                        <span>
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                            </svg>
                        </span>
                        Work order &amp; checklist lapangan
                    </li>
                    <li>
                        <span>
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                            </svg>
                        </span>
                        Housekeeping plan &amp; laporan harian
                    </li>
                    <li>
                        <span>
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                            </svg>
                        </span>
                        Riwayat aset &amp; downtime
                    </li>
                </ul>
            </div>

            <p class="brand-foot">&copy; {{ date('Y') }} {{ config('app.name', 'Rumat') }}</p>
        </aside>

        <main class="panel">
            <div class="card">
                <img src="{{ asset('img/logo.png') }}" alt="{{ config('app.name', 'Rumat') }}" class="card-logo">

                <div class="card-head">
                    <span class="badge">Masuk</span>
                    <h2>Selamat datang kembali</h2>
                    <p>Gunakan akun yang sudah terdaftar. Sistem akan mengarahkan Anda ke panel yang sesuai.</p>
                </div>

                @if (session('status'))
                    <div class="alert" role="status" style="border-color:#bbf7d0;background:#f0fdf4;color:#166534;">
                        <span>{{ session('status') }}</span>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="alert" role="alert">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                        </svg>
                        <span>{{ $errors->first() }}</span>
                    </div>
                @endif

                <form method="POST" action="{{ route('login.store') }}">
                    @csrf

                    <div class="field">
                        <label for="email">Email</label>
                        <div class="control @error('email') has-error @enderror">
                            <input
                                id="email"
                                type="email"
                                name="email"
                                value="{{ old('email') }}"
                                placeholder="nama@perusahaan.com"
                                autocomplete="username"
                                autofocus
                                required
                            >
                        </div>
                        @error('email')
                            <p class="error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="field">
                        <label for="password">Password</label>
                        <div class="control has-toggle @error('password') has-error @enderror">
                            <input
                                id="password"
                                type="password"
                                name="password"
                                placeholder="Masukkan password"
                                autocomplete="current-password"
                                required
                            >
                            <button type="button" class="toggle" id="password-toggle" aria-label="Tampilkan password">
                                <svg id="eye-open" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                <svg id="eye-closed" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" style="display:none">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.243 4.243L9.88 9.88"/>
                                </svg>
                            </button>
                        </div>
                        @error('password')
                            <p class="error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="row">
                        <label class="check">
                            <input type="checkbox" name="remember" value="1" @checked(old('remember'))>
                            Ingat saya
                        </label>
                    </div>

                    <button type="submit" class="submit">
                        Masuk
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m0 0l-6-6m6 6l-6 6"/>
                        </svg>
                    </button>
                </form>

                {{-- <p class="hint">
                    <strong>Supervisor &amp; admin</strong> diarahkan ke panel admin.
                    <strong>Petugas lapangan</strong> (MOS &amp; patra-jasa) diarahkan ke panel aplikasi.
                </p> --}}
            </div>
        </main>
    </div>

    <script>
        (function () {
            const toggle = document.getElementById('password-toggle');
            const input = document.getElementById('password');
            const open = document.getElementById('eye-open');
            const closed = document.getElementById('eye-closed');

            if (!toggle || !input) return;

            toggle.addEventListener('click', function () {
                const isHidden = input.type === 'password';
                input.type = isHidden ? 'text' : 'password';
                open.style.display = isHidden ? 'none' : 'block';
                closed.style.display = isHidden ? 'block' : 'none';
                toggle.setAttribute('aria-label', isHidden ? 'Sembunyikan password' : 'Tampilkan password');
            });
        })();
    </script>
</body>
</html>
