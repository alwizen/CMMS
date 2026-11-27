<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Aktivasi Akun — {{ config('app.name', 'Rumat') }}</title>
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

        .shell {
            display: grid;
            place-items: center;
            min-height: 100vh;
            padding: clamp(1.5rem, 5vw, 3.5rem);
        }

        .card {
            width: 100%;
            max-width: 25rem;
            padding: clamp(1.5rem, 4vw, 2.25rem);
            border: 1px solid var(--rule);
            border-radius: 1rem;
            background: #fff;
            box-shadow: 0 18px 40px -28px rgba(0, 66, 54, .45);
        }

        .card-logo {
            height: 2.5rem;
            width: auto;
            margin-bottom: 1.5rem;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: .45rem;
            padding: .3rem .7rem;
            border-radius: 999px;
            border: 1px solid var(--rule);
            background: var(--paper);
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

        h1 {
            margin: 1.1rem 0 .4rem;
            font-size: 1.5rem;
            letter-spacing: -.02em;
        }

        p.lead {
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

        form { margin-top: 1.6rem; }

        .field { margin-bottom: 1.05rem; }

        .field label {
            display: block;
            margin-bottom: .45rem;
            font-size: .78rem;
            font-weight: 600;
            color: var(--text);
        }

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

        .control input:focus {
            outline: none;
            border-color: var(--mid);
            box-shadow: 0 0 0 3px rgba(97, 135, 100, .18);
        }

        .control input[readonly] {
            background: var(--paper);
            color: var(--muted);
        }

        .control.has-error input { border-color: #e0a39c; }

        .error {
            margin: .4rem 0 0;
            font-size: .75rem;
            color: var(--danger);
        }

        .submit {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 2.95rem;
            margin-top: .5rem;
            border: 0;
            border-radius: .65rem;
            background: var(--ink);
            color: #fff;
            font-family: inherit;
            font-size: .88rem;
            font-weight: 600;
            cursor: pointer;
            transition: background .15s ease;
        }

        .submit:hover { background: #003128; }
    </style>
</head>
<body>
    <main class="shell">
        <div class="card">
            <img src="{{ asset('img/logo.png') }}" alt="{{ config('app.name', 'Rumat') }}" class="card-logo">

            <span class="badge">Aktivasi Akun</span>
            <h1>Buat password Anda</h1>
            <p class="lead">
                Anda diundang bergabung ke {{ config('app.name', 'Rumat') }}.
                Lengkapi data di bawah untuk mengaktifkan akun.
            </p>

            @if ($errors->any())
                <div class="alert" role="alert">
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <form method="POST" action="{{ $action }}">
                @csrf

                <div class="field">
                    <label for="email">Email</label>
                    <div class="control">
                        <input id="email" type="email" value="{{ $user->email }}" readonly>
                    </div>
                </div>

                <div class="field">
                    <label for="name">Nama Lengkap</label>
                    <div class="control @error('name') has-error @enderror">
                        <input id="name" type="text" name="name" value="{{ old('name', $user->name) }}" required autofocus>
                    </div>
                    @error('name')
                        <p class="error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="field">
                    <label for="password">Password</label>
                    <div class="control @error('password') has-error @enderror">
                        <input id="password" type="password" name="password" required autocomplete="new-password">
                    </div>
                    @error('password')
                        <p class="error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="field">
                    <label for="password_confirmation">Ulangi Password</label>
                    <div class="control">
                        <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password">
                    </div>
                </div>

                <button type="submit" class="submit">Aktifkan Akun</button>
            </form>
        </div>
    </main>
</body>
</html>
