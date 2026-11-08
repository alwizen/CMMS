<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Rumat') }} — Sistem Manajemen Pemeliharaan Aset</title>
    <meta name="description" content="Rumat mengelola jadwal perawatan preventif, work order, dokumen teknis, dan riwayat aset dalam satu sistem terpusat.">
    <link rel="icon" href="{{ asset('img/logo.svg') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'system-ui', 'sans-serif'],
                        mono: ['"JetBrains Mono"', 'ui-monospace', 'monospace'],
                    },
                    colors: {
                        ink: '#618764',
                        steel: '#547a58',
                        graphite: '#4a6b4d',
                        paper: '#f1f3f5',
                        rule: '#d5dae0',
                        amber: '#004236',
                        signal: '#004236',
                    }
                }
            }
        }
    </script>

    <style>
        :root { color-scheme: light; }

        body {
            font-family: 'Inter', system-ui, sans-serif;
            background-color: #f1f3f5;
            color: #1f3a24;
            -webkit-font-smoothing: antialiased;
        }

        .num {
            font-variant-numeric: tabular-nums;
            font-feature-settings: "tnum";
        }

        .grid-lines {
            background-image:
                linear-gradient(to right, rgba(255,255,255,.045) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(255,255,255,.045) 1px, transparent 1px);
            background-size: 56px 56px;
        }

        .grid-lines-light {
            background-image:
                linear-gradient(to right, rgba(97,135,100,.12) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(97,135,100,.12) 1px, transparent 1px);
            background-size: 56px 56px;
        }

        .reveal {
            opacity: 0;
            transform: translateY(10px);
            transition: opacity .55s ease, transform .55s ease;
        }

        .reveal.active { opacity: 1; transform: translateY(0); }

        a, button { -webkit-tap-highlight-color: transparent; }

        @media (prefers-reduced-motion: reduce) {
            .reveal { opacity: 1; transform: none; transition: none; }
        }
    </style>
</head>
<body class="bg-paper text-ink">

    <header class="absolute inset-x-0 top-0 z-50 bg-transparent text-white">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-5 py-3 sm:px-8">
            <a href="/" class="flex items-center">
                <img
                    src="{{ asset('img/dark.png') }}"
                    alt="Rumat"
                    class="h-11 w-auto object-contain"
                >
            </a>

            <nav class="hidden items-center gap-8 font-mono text-[11px] uppercase tracking-[0.14em] text-white/50 lg:flex">
                <a href="#kapabilitas" class="transition-colors hover:text-white">Kapabilitas</a>
                <a href="#alur" class="transition-colors hover:text-white">Alur kerja</a>
                <a href="#modul" class="transition-colors hover:text-white">Modul</a>
                <a href="#spesifikasi" class="transition-colors hover:text-white">Spesifikasi</a>
            </nav>

            <div class="flex items-center gap-2">
                <a href="/admin/login" class="hidden border border-white/15 px-3.5 py-2 font-mono text-[11px] uppercase tracking-[0.12em] text-white/70 transition-colors hover:border-white/30 hover:text-white sm:inline-flex">
                    Masuk
                </a>
                <a href="/admin" class="inline-flex items-center gap-2 bg-white px-3.5 py-2 font-mono text-[11px] font-semibold uppercase tracking-[0.12em] text-ink transition-colors hover:bg-white/90">
                    Panel admin
                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m0 0l-6-6m6 6l-6 6"/>
                    </svg>
                </a>
            </div>
        </div>
    </header>

    <!-- Hero -->
    <section class="relative flex min-h-[100svh] flex-col justify-center overflow-hidden bg-ink text-white pt-20">
        {{-- Foto hero tanpa blur: opacity naik dari 30% (kiri) ke 70% (kanan) --}}
        <div
            class="absolute inset-0 bg-cover bg-center bg-no-repeat"
            style="
                background-image: url('{{ asset('img/hero.webp') }}');
                opacity: 0.7;
                -webkit-mask-image: linear-gradient(to right, rgba(0,0,0,0.43) 0%, rgba(0,0,0,1) 100%);
                mask-image: linear-gradient(to right, rgba(0,0,0,0.43) 0%, rgba(0,0,0,1) 100%);
            "
            aria-hidden="true"
        ></div>

        {{-- Scrim agar teks di sisi kiri tetap terbaca di atas foto --}}
        <div
            class="absolute inset-0"
            style="background-image: linear-gradient(to right, rgba(46,70,48,.88) 0%, rgba(46,70,48,.72) 32%, rgba(46,70,48,.35) 52%, rgba(46,70,48,0) 72%);"
            aria-hidden="true"
        ></div>

        <div class="absolute inset-0 grid-lines opacity-40"></div>

        <div class="relative mx-auto w-full max-w-7xl px-5 py-14 sm:px-8 sm:py-16 lg:py-20">
            <div class="grid gap-12">
                <div class="reveal active">
                    <div class="flex items-center gap-3 font-mono text-[10px] uppercase tracking-[0.2em] text-white">
                        <span class="h-px w-8 bg-amber"></span>
                        <span>Sistem manajemen pemeliharaan aset</span>
                    </div>

                    <h1 class="mt-6 max-w-xl text-[2.1rem] font-semibold leading-[1.14] tracking-[-0.02em] sm:text-[2.7rem] lg:text-[3rem]">
                        Kendali penuh atas jadwal, pekerjaan, dan riwayat perawatan aset.
                    </h1>

                    <p class="mt-6 max-w-xl text-[15px] leading-relaxed text-white/60">
                        Rumat mencatat siklus perawatan sejak penjadwalan preventif, penerbitan work order,
                        eksekusi di lapangan, hingga penutupan pekerjaan. Setiap aktivitas tersimpan sebagai
                        data yang dapat ditelusuri untuk audit dan evaluasi.
                    </p>

                    <div class="mt-9 flex flex-wrap items-center gap-3">
                        <a href="/admin" class="inline-flex items-center gap-2 bg-white px-5 py-2.5 text-[13px] font-semibold text-ink transition-colors hover:bg-white/90">
                            Buka panel administrasi
                        </a>
                        <a href="#kapabilitas" class="inline-flex items-center gap-2 border border-white/20 px-5 py-2.5 text-[13px] font-medium text-white/75 transition-colors hover:border-white/40 hover:text-white">
                            Lihat kapabilitas
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Kapabilitas -->
    <section id="kapabilitas" class="border-b border-rule">
        <div class="mx-auto max-w-7xl px-5 py-14 sm:px-8 sm:py-16">
            <div class="reveal grid gap-10 lg:grid-cols-[0.75fr_1.25fr] lg:gap-16">
                <div>
                    <p class="font-mono text-[10px] uppercase tracking-[0.2em] text-amber">01 — Kapabilitas</p>
                    <h2 class="mt-4 text-[1.7rem] font-semibold leading-tight tracking-[-0.01em] sm:text-[2rem]">
                        Cakupan fungsi sistem
                    </h2>
                    <p class="mt-3 max-w-md text-[14px] leading-relaxed text-ink/60">
                        Seluruh fungsi berikut tersedia dalam satu basis data terpusat, dengan hak akses
                        yang dibedakan menurut peran pengguna.
                    </p>
                </div>

                <div class="reveal divide-y divide-rule border-y border-rule">
                    <div class="grid gap-2 py-4 sm:grid-cols-[180px_1fr] sm:gap-6">
                        <h3 class="font-mono text-[11px] uppercase tracking-[0.1em] text-ink/45">Aset &amp; lokasi</h3>
                        <p class="text-[13px] leading-relaxed text-ink/70">
                            Struktur company, area, dan aset dengan nomor tag unik. Batas wilayah area digambar
                            pada peta untuk mempermudah identifikasi lokasi.
                        </p>
                    </div>

                    <div class="grid gap-2 py-4 sm:grid-cols-[180px_1fr] sm:gap-6">
                        <h3 class="font-mono text-[11px] uppercase tracking-[0.1em] text-ink/45">Perawatan preventif</h3>
                        <p class="text-[13px] leading-relaxed text-ink/70">
                            Rencana perawatan per aset dengan interval harian hingga tahunan, dilengkapi daftar
                            aktivitas dan nilai ambang untuk aktivitas pengukuran.
                        </p>
                    </div>

                    <div class="grid gap-2 py-4 sm:grid-cols-[180px_1fr] sm:gap-6">
                        <h3 class="font-mono text-[11px] uppercase tracking-[0.1em] text-ink/45">Work order</h3>
                        <p class="text-[13px] leading-relaxed text-ink/70">
                            Penerbitan work order otomatis dari jadwal maupun manual, penugasan koordinator dan
                            pelaksana, serta pencatatan hasil per item pekerjaan.
                        </p>
                    </div>

                    <div class="grid gap-2 py-4 sm:grid-cols-[180px_1fr] sm:gap-6">
                        <h3 class="font-mono text-[11px] uppercase tracking-[0.1em] text-ink/45">Pelaporan kerusakan</h3>
                        <p class="text-[13px] leading-relaxed text-ink/70">
                            Pelaporan gangguan oleh pengawas, proses persetujuan oleh supervisor, dan konversi
                            menjadi work order dengan checklist yang ditentukan saat persetujuan.
                        </p>
                    </div>

                    <div class="grid gap-2 py-4 sm:grid-cols-[180px_1fr] sm:gap-6">
                        <h3 class="font-mono text-[11px] uppercase tracking-[0.1em] text-ink/45">Dokumen teknis</h3>
                        <p class="text-[13px] leading-relaxed text-ink/70">
                            Penyimpanan sertifikat, P&amp;ID, manual, drawing, dan prosedur dengan penomoran otomatis
                            serta pemantauan masa berlaku dokumen.
                        </p>
                    </div>

                    <div class="grid gap-2 py-4 sm:grid-cols-[180px_1fr] sm:gap-6">
                        <h3 class="font-mono text-[11px] uppercase tracking-[0.1em] text-ink/45">Downtime &amp; meter</h3>
                        <p class="text-[13px] leading-relaxed text-ink/70">
                            Perhitungan downtime tidak terencana dari waktu laporan hingga penutupan work order,
                            disertai pencatatan pembacaan meter per aset.
                        </p>
                    </div>

                    <div class="grid gap-2 py-4 sm:grid-cols-[180px_1fr] sm:gap-6">
                        <h3 class="font-mono text-[11px] uppercase tracking-[0.1em] text-ink/45">Pelaporan &amp; audit</h3>
                        <p class="text-[13px] leading-relaxed text-ink/70">
                            Riwayat perawatan tersimpan permanen dan dapat diekspor ke PDF untuk keperluan audit
                            internal maupun pelaporan manajemen.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Alur -->
    <section id="alur" class="border-b border-rule bg-white">
        <div class="mx-auto max-w-7xl px-5 py-14 sm:px-8 sm:py-16">
            <div class="reveal max-w-2xl">
                <p class="font-mono text-[10px] uppercase tracking-[0.2em] text-amber">02 — Alur kerja</p>
                <h2 class="mt-4 text-[1.7rem] font-semibold leading-tight tracking-[-0.01em] sm:text-[2rem]">
                    Empat tahap yang saling terhubung
                </h2>
                <p class="mt-3 text-[14px] leading-relaxed text-ink/60">
                    Hasil setiap tahap menjadi masukan bagi tahap berikutnya, sehingga tidak ada pekerjaan
                    yang terputus tanpa catatan.
                </p>
            </div>

            <div class="reveal mt-10 grid gap-px bg-rule sm:grid-cols-2 lg:grid-cols-4">
                <div class="bg-white p-5">
                    <div class="flex items-center justify-between">
                        <span class="font-mono text-[10px] uppercase tracking-[0.14em] text-ink/35">Tahap 01</span>
                        <span class="h-1.5 w-1.5 bg-amber"></span>
                    </div>
                    <h3 class="mt-3 text-[14px] font-semibold">Penjadwalan</h3>
                    <p class="mt-1.5 text-[13px] leading-relaxed text-ink/60">
                        Rencana perawatan disusun per aset. Jadwal dibuat untuk 12 bulan ke depan dengan
                        penyesuaian hari kerja.
                    </p>
                </div>

                <div class="bg-white p-5">
                    <div class="flex items-center justify-between">
                        <span class="font-mono text-[10px] uppercase tracking-[0.14em] text-ink/35">Tahap 02</span>
                        <span class="h-1.5 w-1.5 bg-ink/20"></span>
                    </div>
                    <h3 class="mt-3 text-[14px] font-semibold">Penerbitan work order</h3>
                    <p class="mt-1.5 text-[13px] leading-relaxed text-ink/60">
                        Jadwal diterjemahkan menjadi work order dengan checklist per aktivitas, koordinator,
                        dan pelaksana yang ditetapkan.
                    </p>
                </div>

                <div class="bg-white p-5">
                    <div class="flex items-center justify-between">
                        <span class="font-mono text-[10px] uppercase tracking-[0.14em] text-ink/35">Tahap 03</span>
                        <span class="h-1.5 w-1.5 bg-ink/20"></span>
                    </div>
                    <h3 class="mt-3 text-[14px] font-semibold">Eksekusi lapangan</h3>
                    <p class="mt-1.5 text-[13px] leading-relaxed text-ink/60">
                        Teknisi memperbarui status pekerjaan, mengisi hasil pemeriksaan, dan melampirkan
                        dokumentasi foto melalui aplikasi Android.
                    </p>
                </div>

                <div class="bg-white p-5">
                    <div class="flex items-center justify-between">
                        <span class="font-mono text-[10px] uppercase tracking-[0.14em] text-ink/35">Tahap 04</span>
                        <span class="h-1.5 w-1.5 bg-ink/20"></span>
                    </div>
                    <h3 class="mt-3 text-[14px] font-semibold">Arsip &amp; evaluasi</h3>
                    <p class="mt-1.5 text-[13px] leading-relaxed text-ink/60">
                        Work order yang ditutup menjadi riwayat tetap berisi durasi, temuan, tindakan, dan
                        pelaksana untuk keperluan evaluasi.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Modul -->
    <section id="modul" class="border-b border-rule">
        <div class="mx-auto max-w-7xl px-5 py-14 sm:px-8 sm:py-16">
            <div class="reveal flex flex-wrap items-end justify-between gap-6">
                <div class="max-w-2xl">
                    <p class="font-mono text-[10px] uppercase tracking-[0.2em] text-amber">03 — Modul</p>
                    <h2 class="mt-4 text-[1.7rem] font-semibold leading-tight tracking-[-0.01em] sm:text-[2rem]">
                        Modul yang tersedia
                    </h2>
                </div>
                <p class="max-w-sm text-[13px] leading-relaxed text-ink/55">
                    Daftar modul operasional yang digunakan harian oleh pengawas, supervisor, dan teknisi.
                </p>
            </div>

            <div class="reveal mt-10 grid gap-px border border-rule bg-rule sm:grid-cols-2 lg:grid-cols-3">
                <div class="bg-paper p-5">
                    <div class="flex items-baseline justify-between">
                        <h3 class="text-[13px] font-semibold">Company &amp; Area</h3>
                        <span class="font-mono text-[10px] text-ink/30">M-01</span>
                    </div>
                    <p class="mt-1.5 text-[12px] leading-relaxed text-ink/60">Data perusahaan, area kerja, dan batas wilayah pada peta.</p>
                </div>

                <div class="bg-paper p-5">
                    <div class="flex items-baseline justify-between">
                        <h3 class="text-[13px] font-semibold">Equipment</h3>
                        <span class="font-mono text-[10px] text-ink/30">M-02</span>
                    </div>
                    <p class="mt-1.5 text-[12px] leading-relaxed text-ink/60">Registrasi aset, tipe, status operasi, dan tingkat kekritisan.</p>
                </div>

                <div class="bg-paper p-5">
                    <div class="flex items-baseline justify-between">
                        <h3 class="text-[13px] font-semibold">Activities</h3>
                        <span class="font-mono text-[10px] text-ink/30">M-03</span>
                    </div>
                    <p class="mt-1.5 text-[12px] leading-relaxed text-ink/60">Master aktivitas per tipe aset beserta satuan dan ambang nilai.</p>
                </div>

                <div class="bg-paper p-5">
                    <div class="flex items-baseline justify-between">
                        <h3 class="text-[13px] font-semibold">Maintenance Plan</h3>
                        <span class="font-mono text-[10px] text-ink/30">M-04</span>
                    </div>
                    <p class="mt-1.5 text-[12px] leading-relaxed text-ink/60">Rencana preventif dengan interval dan daftar aktivitas.</p>
                </div>

                <div class="bg-paper p-5">
                    <div class="flex items-baseline justify-between">
                        <h3 class="text-[13px] font-semibold">Schedule</h3>
                        <span class="font-mono text-[10px] text-ink/30">M-05</span>
                    </div>
                    <p class="mt-1.5 text-[12px] leading-relaxed text-ink/60">Jadwal terjadwal otomatis dan penyesuaian hari kerja.</p>
                </div>

                <div class="bg-paper p-5">
                    <div class="flex items-baseline justify-between">
                        <h3 class="text-[13px] font-semibold">Work Order</h3>
                        <span class="font-mono text-[10px] text-ink/30">M-06</span>
                    </div>
                    <p class="mt-1.5 text-[12px] leading-relaxed text-ink/60">Penugasan, checklist, status pekerjaan, dan dokumentasi.</p>
                </div>

                <div class="bg-paper p-5">
                    <div class="flex items-baseline justify-between">
                        <h3 class="text-[13px] font-semibold">Maintenance Request</h3>
                        <span class="font-mono text-[10px] text-ink/30">M-07</span>
                    </div>
                    <p class="mt-1.5 text-[12px] leading-relaxed text-ink/60">Pelaporan kerusakan dan proses persetujuan supervisor.</p>
                </div>

                <div class="bg-paper p-5">
                    <div class="flex items-baseline justify-between">
                        <h3 class="text-[13px] font-semibold">Maintenance History</h3>
                        <span class="font-mono text-[10px] text-ink/30">M-08</span>
                    </div>
                    <p class="mt-1.5 text-[12px] leading-relaxed text-ink/60">Riwayat pekerjaan selesai dan ekspor laporan PDF.</p>
                </div>

                <div class="bg-paper p-5">
                    <div class="flex items-baseline justify-between">
                        <h3 class="text-[13px] font-semibold">Meter Log</h3>
                        <span class="font-mono text-[10px] text-ink/30">M-09</span>
                    </div>
                    <p class="mt-1.5 text-[12px] leading-relaxed text-ink/60">Pembacaan meter per aset sebagai dasar perawatan kondisi.</p>
                </div>

                <div class="bg-paper p-5">
                    <div class="flex items-baseline justify-between">
                        <h3 class="text-[13px] font-semibold">Documents</h3>
                        <span class="font-mono text-[10px] text-ink/30">M-10</span>
                    </div>
                    <p class="mt-1.5 text-[12px] leading-relaxed text-ink/60">Dokumen teknis dengan kategori dan masa berlaku.</p>
                </div>

                <div class="bg-paper p-5">
                    <div class="flex items-baseline justify-between">
                        <h3 class="text-[13px] font-semibold">Downtime</h3>
                        <span class="font-mono text-[10px] text-ink/30">M-11</span>
                    </div>
                    <p class="mt-1.5 text-[12px] leading-relaxed text-ink/60">Laporan downtime tidak terencana per aset dan periode.</p>
                </div>

                <div class="bg-paper p-5">
                    <div class="flex items-baseline justify-between">
                        <h3 class="text-[13px] font-semibold">Users &amp; Roles</h3>
                        <span class="font-mono text-[10px] text-ink/30">M-12</span>
                    </div>
                    <p class="mt-1.5 text-[12px] leading-relaxed text-ink/60">Pengelolaan pengguna dan hak akses per peran.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Spesifikasi -->
    <section id="spesifikasi" class="border-b border-rule bg-ink text-white">
        <div class="mx-auto max-w-7xl px-5 py-14 sm:px-8 sm:py-16">
            <div class="reveal grid gap-10 lg:grid-cols-[0.7fr_1.3fr] lg:gap-16">
                <div>
                    <p class="font-mono text-[10px] uppercase tracking-[0.2em] text-amber">04 — Spesifikasi</p>
                    <h2 class="mt-4 text-[1.7rem] font-semibold leading-tight tracking-[-0.01em] sm:text-[2rem]">
                        Ketentuan teknis
                    </h2>
                </div>

                <dl class="divide-y divide-white/10 border-y border-white/10">
                    <div class="grid gap-1 py-3.5 sm:grid-cols-[200px_1fr] sm:gap-6">
                        <dt class="font-mono text-[11px] uppercase tracking-[0.1em] text-white/40">Akses</dt>
                        <dd class="text-[13px] text-white/75">Panel web untuk pengawas, supervisor, dan administrator. Aplikasi Android untuk teknisi lapangan.</dd>
                    </div>
                    <div class="grid gap-1 py-3.5 sm:grid-cols-[200px_1fr] sm:gap-6">
                        <dt class="font-mono text-[11px] uppercase tracking-[0.1em] text-white/40">Autentikasi</dt>
                        <dd class="text-[13px] text-white/75">Token API untuk aplikasi mobile, sesi web untuk panel administrasi.</dd>
                    </div>
                    <div class="grid gap-1 py-3.5 sm:grid-cols-[200px_1fr] sm:gap-6">
                        <dt class="font-mono text-[11px] uppercase tracking-[0.1em] text-white/40">Penomoran</dt>
                        <dd class="text-[13px] text-white/75">Format nomor work order, plan, schedule, request, dan dokumen dapat dikonfigurasi.</dd>
                    </div>
                    <div class="grid gap-1 py-3.5 sm:grid-cols-[200px_1fr] sm:gap-6">
                        <dt class="font-mono text-[11px] uppercase tracking-[0.1em] text-white/40">Penjadwalan</dt>
                        <dd class="text-[13px] text-white/75">Pembuatan work order otomatis untuk rentang hari kerja yang dapat diatur.</dd>
                    </div>
                    <div class="grid gap-1 py-3.5 sm:grid-cols-[200px_1fr] sm:gap-6">
                        <dt class="font-mono text-[11px] uppercase tracking-[0.1em] text-white/40">Notifikasi</dt>
                        <dd class="text-[13px] text-white/75">Notifikasi dalam aplikasi dan email untuk persetujuan, jadwal mendatang, dan work order terlambat.</dd>
                    </div>
                    <div class="grid gap-1 py-3.5 sm:grid-cols-[200px_1fr] sm:gap-6">
                        <dt class="font-mono text-[11px] uppercase tracking-[0.1em] text-white/40">Impor data</dt>
                        <dd class="text-[13px] text-white/75">Impor CSV untuk master data aset, area, tipe, dan aktivitas.</dd>
                    </div>
                    <div class="grid gap-1 py-3.5 sm:grid-cols-[200px_1fr] sm:gap-6">
                        <dt class="font-mono text-[11px] uppercase tracking-[0.1em] text-white/40">Keluaran</dt>
                        <dd class="text-[13px] text-white/75">Ekspor riwayat perawatan ke PDF sesuai filter periode, aset, dan area.</dd>
                    </div>
                </dl>
            </div>
        </div>
    </section>

    <!-- Penutup -->
    <section class="border-b border-rule bg-white">
        <div class="mx-auto max-w-7xl px-5 py-12 sm:px-8 sm:py-14">
            <div class="reveal flex flex-col gap-6 md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="text-[1.4rem] font-semibold tracking-[-0.01em] sm:text-[1.6rem]">
                        Implementasi dapat dimulai dari satu area.
                    </h2>
                    <p class="mt-2 max-w-xl text-[13px] leading-relaxed text-ink/60">
                        Data master dapat diimpor bertahap, dan modul lain diaktifkan sesuai kebutuhan operasional.
                    </p>
                </div>

                <div class="flex flex-col gap-2.5 sm:flex-row">
                    <a href="/admin" class="inline-flex items-center justify-center bg-ink px-5 py-2.5 text-[13px] font-semibold text-white transition-colors hover:bg-ink/90">
                        Buka panel administrasi
                    </a>
                    <a href="/admin/login" class="inline-flex items-center justify-center border border-rule px-5 py-2.5 text-[13px] font-medium text-ink/70 transition-colors hover:bg-paper">
                        Masuk sebagai petugas
                    </a>
                </div>
            </div>
        </div>
    </section>

    <footer class="bg-ink text-white">
        <div class="mx-auto max-w-7xl px-5 py-10 sm:px-8">
            <div class="flex flex-col gap-8 border-b border-white/10 pb-8 md:flex-row md:items-start md:justify-between">
                <div class="flex items-start gap-3">
                    {{-- <span class="flex h-8 w-8 items-center justify-center bg-white">
                        <img src="{{ asset('img/logo.svg') }}" alt="Rumat" class="h-5 w-5 object-contain">
                    </span> --}}
                   
                </div>

                <nav class="flex flex-wrap gap-x-8 gap-y-2 font-mono text-[11px] uppercase tracking-[0.12em] text-white/45">
                    <a href="#kapabilitas" class="transition-colors hover:text-white">Kapabilitas</a>
                    <a href="#alur" class="transition-colors hover:text-white">Alur kerja</a>
                    <a href="#modul" class="transition-colors hover:text-white">Modul</a>
                    <a href="#spesifikasi" class="transition-colors hover:text-white">Spesifikasi</a>
                    <a href="/admin" class="transition-colors hover:text-white">Panel</a>
                </nav>
            </div>

            <div class="grid gap-8 border-b border-white/10 py-8 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <p class="font-mono text-[10px] uppercase tracking-[0.14em] text-white/35">Produk</p>
                    <ul class="mt-3 space-y-2 text-[12px] text-white/60">
                        <li><a href="#kapabilitas" class="transition-colors hover:text-white">Kapabilitas</a></li>
                        <li><a href="#alur" class="transition-colors hover:text-white">Alur kerja</a></li>
                        <li><a href="#modul" class="transition-colors hover:text-white">Modul</a></li>
                        <li><a href="#spesifikasi" class="transition-colors hover:text-white">Spesifikasi</a></li>
                    </ul>
                </div>

                <div>
                    <p class="font-mono text-[10px] uppercase tracking-[0.14em] text-white/35">Modul inti</p>
                    <ul class="mt-3 space-y-2 text-[12px] text-white/60">
                        <li>Perawatan preventif</li>
                        <li>Work order &amp; checklist</li>
                        <li>Dokumen teknis</li>
                        <li>Downtime &amp; meter log</li>
                    </ul>
                </div>

                <div>
                    <p class="font-mono text-[10px] uppercase tracking-[0.14em] text-white/35">Akses</p>
                    <ul class="mt-3 space-y-2 text-[12px] text-white/60">
                        <li><a href="/admin" class="transition-colors hover:text-white">Panel administrasi</a></li>
                        <li><a href="/admin/login" class="transition-colors hover:text-white">Masuk petugas</a></li>
                        <li>Aplikasi Android</li>
                    </ul>
                </div>

                <div>
                    <p class="font-mono text-[10px] uppercase tracking-[0.14em] text-white/35">R U M A T</p>
                    <p class="mt-3 max-w-xs text-[12px] leading-relaxed text-white/50">
                        Seluruh data operasional tersimpan terpusat dan hanya dapat diakses pengguna
                        yang memiliki peran sesuai kewenangannya.
                    </p>
                </div>
            </div>

            <div class="flex flex-col gap-3 pt-6 font-mono text-[10px] uppercase tracking-[0.12em] text-white/30 sm:flex-row sm:items-center sm:justify-between">
                <p>&copy; {{ date('Y') }} Rumat. Seluruh hak dilindungi.</p>
                <div class="flex flex-wrap items-center gap-x-5 gap-y-2">
                    <span>Reliable · Utility · Maintenance · Asset · Tracking</span>
                    <a href="/" class="transition-colors hover:text-white">Kembali ke atas</a>
                </div>
            </div>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const items = document.querySelectorAll('.reveal');

            if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                items.forEach((el) => el.classList.add('active'));
                return;
            }

            const observer = new IntersectionObserver((entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('active');
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });

            items.forEach((el) => observer.observe(el));
        });
    </script>
</body>
</html>
