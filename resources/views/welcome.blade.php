<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'MaintenX') }} — Platform Pemeliharaan & Manajemen Aset</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                        mono: ['"JetBrains Mono"', 'monospace'],
                    },
                    colors: {
                        sage: {
                            light: '#eef2e7',
                            soft: '#9CB080',
                            DEFAULT: '#618764',
                            dark: '#2B5748',
                            deep: '#1d3c32',
                        }
                    },
                    keyframes: {
                        floatSlow: {
                            '0%, 100%': { transform: 'translateY(0px)' },
                            '50%': { transform: 'translateY(-10px)' },
                        },
                        subtleGlow: {
                            '0%, 100%': { opacity: '0.45', transform: 'scale(1)' },
                            '50%': { opacity: '0.7', transform: 'scale(1.05)' },
                        }
                    },
                    animation: {
                        'float-slow': 'floatSlow 6s ease-in-out infinite',
                        'subtle-glow': 'subtleGlow 5s ease-in-out infinite',
                    }
                }
            }
        }
    </script>

    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #ffffff;
            color: #1e293b;
        }

        .hero-pattern {
            background-image: 
                radial-gradient(circle at 18% 20%, rgba(156, 176, 128, 0.22) 0%, transparent 45%),
                radial-gradient(circle at 82% 25%, rgba(97, 135, 100, 0.16) 0%, transparent 48%),
                radial-gradient(circle at 50% 90%, rgba(43, 87, 72, 0.08) 0%, transparent 55%);
        }

        .dot-grid {
            background-size: 28px 28px;
            background-image: radial-gradient(circle, rgba(97, 135, 100, 0.12) 1.2px, transparent 1.2px);
        }

        .glass-clean {
            background: rgba(255, 255, 255, 0.88);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            border: 1px solid rgba(156, 176, 128, 0.25);
        }

        .clean-card {
            background: #ffffff;
            border: 1px solid rgba(97, 135, 100, 0.18);
            transition: all 0.3s cubic-bezier(0.2, 0.8, 0.2, 1);
        }

        .clean-card:hover {
            transform: translateY(-4px);
            border-color: #618764;
            box-shadow: 0 16px 32px -12px rgba(43, 87, 72, 0.12);
        }

        /* Scroll reveal */
        .reveal {
            opacity: 0;
            transform: translateY(22px);
            transition: opacity 0.7s cubic-bezier(0.16, 1, 0.3, 1), transform 0.7s cubic-bezier(0.16, 1, 0.3, 1);
            will-change: opacity, transform;
        }

        .reveal.active {
            opacity: 1;
            transform: translateY(0);
        }

        /* Hilangkan transform/pergeseran saat tombol ditekan (active state) */
        a, button {
            -webkit-tap-highlight-color: transparent;
        }
        a:active, button:active {
            transform: none !important;
        }
    </style>
</head>
<body class="bg-white text-slate-800 antialiased selection:bg-[#618764] selection:text-white">

    <!-- Subtle Background Lighting -->
    <div class="fixed inset-0 hero-pattern pointer-events-none -z-10"></div>
    <div class="fixed inset-0 dot-grid pointer-events-none opacity-80 -z-10"></div>

    <!-- Header Navigation -->
    <header class="fixed top-0 left-0 right-0 z-50 transition-all duration-300" id="main-nav">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-4">
            <div class="glass-clean rounded-2xl px-5 py-3 flex items-center justify-between shadow-lg shadow-[#2B5748]/5">
                <!-- Logo & Brand -->
                <a href="#" class="flex items-center gap-3 group">
                    <div class="w-10 h-10 rounded-xl bg-white border border-[#9CB080]/40 p-1.5 shadow-sm group-hover:border-[#618764] transition-colors duration-300 flex items-center justify-center">
                        <img src="{{ asset('img/logo.svg') }}" alt="Logo" class="w-full h-full object-contain group-hover:scale-105 transition-transform duration-300" />
                    </div>
                    <div>
                        <span class="text-lg font-bold tracking-tight text-[#2B5748] flex items-center gap-1.5">
                            {{-- {{ config('app.name', 'MaintenX') }}
                            <span class="px-1.5 py-0.5 text-[10px] font-semibold text-[#2B5748] bg-[#9CB080]/20 border border-[#9CB080]/50 rounded">PRO</span> --}}
                        </span>
                        {{-- <h1 class="text-[10px] text-slate-500 font-medium -mt-1 hidden sm:block">Industrial Asset Management</h1> --}}
                    </div>
                </a>

                <!-- Desktop Menu -->
                <nav class="hidden md:flex items-center gap-8 text-sm font-semibold text-slate-600">
                    <a href="#features" class="hover:text-[#2B5748] transition-colors">Fitur Utama</a>
                    <a href="#workflow" class="hover:text-[#2B5748] transition-colors">Alur Perawatan</a>
                    <a href="#stats" class="hover:text-[#2B5748] transition-colors">Efisiensi</a>
                </nav>

                <!-- Action Button -->
                <div class="flex items-center gap-3">
                    <a href="/admin/login" class="text-sm font-semibold text-[#2B5748] hover:text-[#618764] px-3.5 py-2 rounded-xl transition-colors hidden sm:inline-block">
                        Login Petugas
                    </a>
                    <a href="/admin" class="relative group inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold text-white bg-[#2B5748] hover:bg-[#618764] shadow-md shadow-[#2B5748]/20 transition-colors duration-200">
                        <span>Buka Dashboard</span>
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="relative pt-36 pb-20 md:pt-44 md:pb-28 overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto reveal active">
                <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-[#9CB080]/15 border border-[#9CB080]/40 text-[#2B5748] text-xs font-semibold mb-6">
                    <span class="w-2 h-2 rounded-full bg-[#618764]"></span>
                    <span>Sistem Manajemen Pemeliharaan & Perawatan Terpadu</span>
                </div>

                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold tracking-tight text-[#2B5748] leading-[1.18] mb-6">
                    Kelola Siklus Hidup Aset <br class="hidden sm:inline" />
                    <span class="text-[#618764]">Lebih Tepat, Efisien & Terukur</span>
                </h1>

                <p class="text-base sm:text-lg text-slate-600 max-w-2xl mx-auto mb-10 leading-relaxed font-normal">
                    Otomatisasi jadwal preventif berkala, pemetaan area GIS, penugasan work order teknisi, dan pencatatan riwayat peralatan dalam satu sistem kerja bersih dan modern.
                </p>

                <div class="flex flex-col sm:flex-row items-center justify-center gap-4 text-sm font-semibold">
                    <a href="/admin" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-8 py-3.5 rounded-xl text-white bg-[#2B5748] hover:bg-[#618764] shadow-md shadow-[#2B5748]/20 transition-colors duration-200">
                        <svg class="w-4 h-4 text-[#9CB080]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        Masuk ke Dashboard Admin
                    </a>
                    <a href="#features" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-7 py-3.5 rounded-xl text-[#2B5748] bg-white border border-[#9CB080]/40 hover:bg-[#9CB080]/10 transition-colors duration-200">
                        Pelajari Fitur
                    </a>
                </div>

                <!-- Highlight metrics -->
                <div class="pt-12 grid grid-cols-2 md:grid-cols-4 gap-6 text-left max-w-4xl mx-auto border-t border-slate-200 mt-14">
                    <div class="p-2">
                        <div class="text-3xl font-extrabold text-[#2B5748]">99.8%</div>
                        <div class="text-xs font-medium text-slate-500 mt-1">Keandalan Operasional</div>
                    </div>
                    <div class="p-2">
                        <div class="text-3xl font-extrabold text-[#618764]">0%</div>
                        <div class="text-xs font-medium text-slate-500 mt-1">Downtime Tak Terencana</div>
                    </div>
                    <div class="p-2">
                        <div class="text-3xl font-extrabold text-[#2B5748]">Real-Time</div>
                        <div class="text-xs font-medium text-slate-500 mt-1">Pelacakan Work Order</div>
                    </div>
                    <div class="p-2">
                        <div class="text-3xl font-extrabold text-[#618764]">GIS Area</div>
                        <div class="text-xs font-medium text-slate-500 mt-1">Pemetaan Zonasi Aset</div>
                    </div>
                </div>
            </div>

            <!-- Preview UI Box (Clean Style) -->
            <div class="mt-14 max-w-5xl mx-auto relative reveal">
                <div class="clean-card rounded-2xl overflow-hidden shadow-xl border border-[#9CB080]/30 bg-white">
                    <!-- App bar -->
                    <div class="bg-[#2B5748] px-4 py-3 flex items-center justify-between text-white">
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-[#9CB080]/80"></span>
                            <span class="w-2.5 h-2.5 rounded-full bg-white/40"></span>
                            <span class="w-2.5 h-2.5 rounded-full bg-white/20"></span>
                            <span class="text-xs text-[#eef2e7] font-mono ml-2">portal-maintenance.internal/overview</span>
                        </div>
                        <span class="text-xs text-[#9CB080] font-mono font-medium flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-[#9CB080]"></span>
                            Sistem Siap Operasi
                        </span>
                    </div>

                    <!-- Inner Mock Dashboard -->
                    <div class="p-6 md:p-8 bg-[#fafcfa]">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-6">
                            <div class="p-4 rounded-xl bg-white border border-[#9CB080]/30 shadow-sm flex items-center justify-between">
                                <div>
                                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Peralatan Terdaftar</span>
                                    <div class="text-2xl font-bold text-[#2B5748] mt-1">128 Unit</div>
                                    <span class="text-xs font-semibold text-[#618764] mt-1 inline-block">124 Aktif Terawat</span>
                                </div>
                                <div class="w-11 h-11 rounded-xl bg-[#9CB080]/20 flex items-center justify-center text-[#2B5748]">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                    </svg>
                                </div>
                            </div>

                            <div class="p-4 rounded-xl bg-white border border-[#9CB080]/30 shadow-sm flex items-center justify-between">
                                <div>
                                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Work Order Aktif</span>
                                    <div class="text-2xl font-bold text-[#618764] mt-1">14 Tugas</div>
                                    <span class="text-xs font-semibold text-slate-600 mt-1 inline-block">8 Selesai Hari Ini</span>
                                </div>
                                <div class="w-11 h-11 rounded-xl bg-[#618764]/15 flex items-center justify-center text-[#618764]">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                                    </svg>
                                </div>
                            </div>

                            <div class="p-4 rounded-xl bg-white border border-[#9CB080]/30 shadow-sm flex items-center justify-between">
                                <div>
                                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Jadwal Preventif</span>
                                    <div class="text-2xl font-bold text-[#2B5748] mt-1">32 Agenda</div>
                                    <span class="text-xs font-semibold text-[#618764] mt-1 inline-block">Tersinkron Kalender</span>
                                </div>
                                <div class="w-11 h-11 rounded-xl bg-[#2B5748]/10 flex items-center justify-center text-[#2B5748]">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                            </div>
                        </div>

                        <!-- Mini activity list -->
                        <div class="rounded-xl border border-slate-200 bg-white p-4">
                            <div class="flex items-center justify-between pb-3 mb-3 border-b border-slate-100 text-xs font-semibold text-slate-500">
                                <span>Aktivitas Penanganan Lapangan Terbaru</span>
                                <span class="text-[#2B5748] font-mono">Terkoneksi</span>
                            </div>
                            <div class="space-y-2.5">
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between p-3 rounded-lg bg-slate-50 border border-slate-100 text-sm gap-2">
                                    <div class="flex items-center gap-3">
                                        <span class="w-2.5 h-2.5 rounded-full bg-[#618764]"></span>
                                        <div>
                                            <p class="font-semibold text-slate-800">Inspeksi Berkala Generator Turbin Hydro #3</p>
                                            <p class="text-xs text-slate-500">Area Distribusi Barat • WO-2026-0042</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3 text-xs">
                                        <span class="px-2.5 py-1 rounded bg-[#618764]/15 text-[#2B5748] font-semibold">Selesai</span>
                                        <span class="text-slate-500">Teknisi: Budi Santoso</span>
                                    </div>
                                </div>
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between p-3 rounded-lg bg-slate-50 border border-slate-100 text-sm gap-2">
                                    <div class="flex items-center gap-3">
                                        <span class="w-2.5 h-2.5 rounded-full bg-[#9CB080]"></span>
                                        <div>
                                            <p class="font-semibold text-slate-800">Penggantian Filter Pompa Sentrifugal B</p>
                                            <p class="text-xs text-slate-500">Area Pengolahan Air Limbah • WO-2026-0043</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3 text-xs">
                                        <span class="px-2.5 py-1 rounded bg-[#9CB080]/25 text-[#2B5748] font-semibold">Proses Pengerjaan</span>
                                        <span class="text-slate-500">Teknisi: Ridwan Kamil</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-20 relative bg-slate-50/70 border-t border-b border-slate-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-2xl mx-auto mb-16 reveal">
                <h2 class="text-xs font-bold uppercase tracking-wider text-[#618764] font-mono mb-2">Modul Komprehensif</h2>
                <h3 class="text-3xl sm:text-4xl font-extrabold text-[#2B5748] tracking-tight">
                    Fitur Praktis untuk Kelancaran Operasional
                </h3>
                <p class="text-slate-600 mt-3 text-sm sm:text-base">
                    Sistem dirancang terpadu memudahkan koordinasi antara manajemen, supervisor, dan teknisi lapangan.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Card 1 -->
                <div class="clean-card p-6 rounded-2xl reveal">
                    <div class="w-11 h-11 rounded-xl bg-[#9CB080]/20 flex items-center justify-center text-[#2B5748] mb-4">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L16 4m0 13V4m0 0L9 7" />
                        </svg>
                    </div>
                    <h4 class="text-base font-bold text-[#2B5748] mb-2">GIS Area & Geoman Maps</h4>
                    <p class="text-slate-600 text-sm leading-relaxed">
                        Pemetaan poligon zona kerja pabrik dan penempatan aset industri berbasis Leaflet dan Geoman secara akurat.
                    </p>
                </div>

                <!-- Card 2 -->
                <div class="clean-card p-6 rounded-2xl reveal">
                    <div class="w-11 h-11 rounded-xl bg-[#618764]/20 flex items-center justify-center text-[#2B5748] mb-4">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <h4 class="text-base font-bold text-[#2B5748] mb-2">Kalender Maintenance</h4>
                    <p class="text-slate-600 text-sm leading-relaxed">
                        Jadwal preventif berkala otomatis untuk mengantisipasi kerusakan serta mencegah bentrok jadwal teknisi.
                    </p>
                </div>

                <!-- Card 3 -->
                <div class="clean-card p-6 rounded-2xl reveal">
                    <div class="w-11 h-11 rounded-xl bg-[#2B5748]/15 flex items-center justify-center text-[#2B5748] mb-4">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <h4 class="text-base font-bold text-[#2B5748] mb-2">Work Order & Checklist</h4>
                    <p class="text-slate-600 text-sm leading-relaxed">
                        Pemberian tugas langsung ke teknisi lapangan lengkap dengan petunjuk langkah kerja, waktu pengerjaan dan bukti.
                    </p>
                </div>

                <!-- Card 4 -->
                <div class="clean-card p-6 rounded-2xl reveal">
                    <div class="w-11 h-11 rounded-xl bg-[#9CB080]/20 flex items-center justify-center text-[#2B5748] mb-4">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                        </svg>
                    </div>
                    <h4 class="text-base font-bold text-[#2B5748] mb-2">Pencatatan Meter Log</h4>
                    <p class="text-slate-600 text-sm leading-relaxed">
                        Monitor jam operasional mesin, indikator suhu, dan kapasitas kerja sebagai basis perawatan prediktif.
                    </p>
                </div>

                <!-- Card 5 -->
                <div class="clean-card p-6 rounded-2xl reveal">
                    <div class="w-11 h-11 rounded-xl bg-[#618764]/20 flex items-center justify-center text-[#2B5748] mb-4">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <h4 class="text-base font-bold text-[#2B5748] mb-2">Tiket Kerusakan Cepat</h4>
                    <p class="text-slate-600 text-sm leading-relaxed">
                        Operator dapat melaporkan anomali dan kendala mesin dengan cepat agar segera ditindaklanjuti supervisor.
                    </p>
                </div>

                <!-- Card 6 -->
                <div class="clean-card p-6 rounded-2xl reveal">
                    <div class="w-11 h-11 rounded-xl bg-[#2B5748]/15 flex items-center justify-center text-[#2B5748] mb-4">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                    </div>
                    <h4 class="text-base font-bold text-[#2B5748] mb-2">Export Histori ke PDF</h4>
                    <p class="text-slate-600 text-sm leading-relaxed">
                        Cetak ringkasan rekam jejak pemeliharaan secara formal untuk audit internal, laporan keselamatan kerja dan arsip.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Workflow Section -->
    <section id="workflow" class="py-20 relative">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-2xl mx-auto mb-16 reveal">
                <h2 class="text-xs font-bold uppercase tracking-wider text-[#618764] font-mono mb-2">Siklus Kerja</h2>
                <h3 class="text-3xl sm:text-4xl font-extrabold text-[#2B5748] tracking-tight">
                    Alur Terstruktur dari Pelaporan Hingga Evaluasi
                </h3>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div class="clean-card p-6 rounded-2xl reveal bg-white">
                    <div class="text-3xl font-extrabold text-[#9CB080] font-mono mb-3">01</div>
                    <h4 class="text-base font-bold text-[#2B5748] mb-2">Registrasi Aset</h4>
                    <p class="text-slate-600 text-xs leading-relaxed">
                        Pendataan identitas mesin, spesifikasi pabrikan, serta pemetaan zonasi wilayah pabrik.
                    </p>
                </div>

                <div class="clean-card p-6 rounded-2xl reveal bg-white">
                    <div class="text-3xl font-extrabold text-[#618764] font-mono mb-3">02</div>
                    <h4 class="text-base font-bold text-[#2B5748] mb-2">Rencana Perawatan</h4>
                    <p class="text-slate-600 text-xs leading-relaxed">
                        Penetapan jadwal berkala (harian, bulanan, tahunan) beserta checklist prosedur standar.
                    </p>
                </div>

                <div class="clean-card p-6 rounded-2xl reveal bg-white">
                    <div class="text-3xl font-extrabold text-[#2B5748] font-mono mb-3">03</div>
                    <h4 class="text-base font-bold text-[#2B5748] mb-2">Eksekusi Tugas</h4>
                    <p class="text-slate-600 text-xs leading-relaxed">
                        Penerbitan work order ke teknisi dan pelaporan berkala secara bertahap.
                    </p>
                </div>

                <div class="clean-card p-6 rounded-2xl reveal bg-white">
                    <div class="text-3xl font-extrabold text-[#2B5748] font-mono mb-3">04</div>
                    <h4 class="text-base font-bold text-[#2B5748] mb-2">Audit & Arsip</h4>
                    <p class="text-slate-600 text-xs leading-relaxed">
                        Verifikasi hasil pengerjaan, pencatatan otomatis ke kartu histori, dan analisis berkala.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats & Benefit -->
    <section id="stats" class="py-16 relative bg-[#2B5748] text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 items-center reveal">
                <div>
                    <span class="text-xs font-mono font-bold tracking-wider text-[#9CB080] uppercase">Kinerja Terbukti</span>
                    <h3 class="text-3xl font-extrabold text-white mt-2 leading-tight">
                        Mengurangi Biaya Perbaikan & Memperpanjang Usia Peralatan
                    </h3>
                    <p class="text-slate-200 text-sm mt-4 leading-relaxed">
                        Transisi ke strategi preventif terstruktur membantu menjaga kontinuitas proses produksi tanpa gangguan henti mesin yang mendadak.
                    </p>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="p-5 rounded-xl bg-white/10 border border-white/15 backdrop-blur-sm">
                        <div class="text-3xl font-extrabold text-[#9CB080]">-35%</div>
                        <div class="text-xs text-slate-200 mt-1">Biaya Perbaikan Tak Terencana</div>
                    </div>
                    <div class="p-5 rounded-xl bg-white/10 border border-white/15 backdrop-blur-sm">
                        <div class="text-3xl font-extrabold text-[#9CB080]">+40%</div>
                        <div class="text-xs text-slate-200 mt-1">Kecepatan Respon Teknisi</div>
                    </div>
                    <div class="p-5 rounded-xl bg-white/10 border border-white/15 backdrop-blur-sm">
                        <div class="text-3xl font-extrabold text-[#9CB080]">100%</div>
                        <div class="text-xs text-slate-200 mt-1">Ketertelusuran Riwayat Aset</div>
                    </div>
                    <div class="p-5 rounded-xl bg-white/10 border border-white/15 backdrop-blur-sm">
                        <div class="text-3xl font-extrabold text-[#9CB080]">0</div>
                        <div class="text-xs text-slate-200 mt-1">Formulir Kertas Manual</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Call to action -->
    <section class="py-20 relative bg-white">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center reveal">
            <div class="p-10 sm:p-14 rounded-3xl border border-[#9CB080]/40 bg-[#fafcfa] shadow-sm">
                <h3 class="text-2xl sm:text-3xl font-extrabold text-[#2B5748] mb-3">
                    Mulai Kelola Perawatan Aset Anda Sekarang
                </h3>
                <p class="text-slate-600 text-sm sm:text-base max-w-xl mx-auto mb-8">
                    Akses portal manajemen untuk mengatur jadwal berkala, memantau tugas teknisi, dan memetakan lokasi peralatan.
                </p>

                <a href="/admin" class="inline-flex items-center gap-2 px-8 py-3.5 rounded-xl text-white font-semibold bg-[#2B5748] hover:bg-[#618764] shadow-md shadow-[#2B5748]/20 transition-colors duration-200">
                    <span>Masuk ke Panel Administrasi</span>
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                    </svg>
                </a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-[#2B5748] border-t border-[#618764]/40 pt-14 pb-10 text-slate-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-10 pb-12 border-b border-white/10">
                <!-- Col 1: Brand Info -->
                <div class="md:col-span-5 space-y-4">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-white border border-[#9CB080]/60 p-1.5 shadow-sm flex items-center justify-center">
                            <img src="{{ asset('img/logo.svg') }}" alt="Logo" class="w-full h-full object-contain" />
                        </div>
                        <span class="text-xl font-bold tracking-tight text-white">RUMAT</span>
                    </div>
                    <p class="text-sm text-slate-300 leading-relaxed max-w-sm">
                        Platform Enterprise Manajemen Pemeliharaan & Aset Industri. Membantu penjadwalan preventif, pemetaan geo-area, serta penugasan work order teknisi secara akurat dan transparan.
                    </p>
                    <div class="flex items-center gap-2 text-xs text-[#9CB080] font-medium pt-1">
                        <span class="w-2 h-2 rounded-full bg-[#9CB080]"></span>
                        <span>Sistem Operasional Aktif & Siap Digunakan</span>
                    </div>
                </div>

                <!-- Col 2: Navigasi Cepat -->
                <div class="md:col-span-2 space-y-3">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-[#9CB080] font-mono">Navigasi</h4>
                    <ul class="space-y-2 text-sm text-slate-300">
                        <li><a href="#features" class="hover:text-white transition-colors">Fitur Utama</a></li>
                        <li><a href="#workflow" class="hover:text-white transition-colors">Alur Kerja</a></li>
                        <li><a href="#stats" class="hover:text-white transition-colors">Kinerja & Metrik</a></li>
                        <li><a href="/admin" class="hover:text-white transition-colors">Dashboard Admin</a></li>
                    </ul>
                </div>

                <!-- Col 3: Modul Sistem -->
                <div class="md:col-span-3 space-y-3">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-[#9CB080] font-mono">Modul Sistem</h4>
                    <ul class="space-y-2 text-sm text-slate-300">
                        <li><span>GIS & Geoman Area Mapping</span></li>
                        <li><span>Jadwal & Kalender Preventif</span></li>
                        <li><span>Work Order & Checklist Petugas</span></li>
                        <li><span>Pencatatan Sensor & Meter Log</span></li>
                        <li><span>Cetak Dokumen & PDF Histori</span></li>
                    </ul>
                </div>

                <!-- Col 4: Akses Cepat -->
                <div class="md:col-span-2 space-y-3">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-[#9CB080] font-mono">Akses Petugas</h4>
                    <p class="text-xs text-slate-300 leading-relaxed">
                        Masuk ke konsol untuk mengelola penugasan harian atau mengajukan tiket perawatan.
                    </p>
                    <div class="pt-1">
                        <a href="/admin/login" class="inline-flex items-center justify-center w-full px-4 py-2 rounded-lg text-xs font-semibold text-[#2B5748] bg-[#9CB080] hover:bg-white transition-colors duration-200">
                            Masuk Portal
                        </a>
                    </div>
                </div>
            </div>

            <!-- Bottom Copyright -->
            <div class="pt-8 flex flex-col sm:flex-row items-center justify-between text-xs text-slate-300 gap-3">
                <p>&copy; {{ date('Y') }} {{ config('app.name', 'MaintenX') }}. Seluruh hak cipta dilindungi undang-undang.</p>
                <div class="flex items-center gap-6">
                    <a href="/admin" class="hover:text-white transition-colors">Kebijakan Privasi</a>
                    <a href="/admin" class="hover:text-white transition-colors">Ketentuan Layanan</a>
                    <a href="/admin" class="hover:text-white transition-colors">Bantuan</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Interactive Smooth Scroll & Scroll-Reveal Observer -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('active');
                    }
                });
            }, {
                threshold: 0.1,
                rootMargin: '0px 0px -30px 0px'
            });

            document.querySelectorAll('.reveal').forEach((el) => {
                observer.observe(el);
            });

            const header = document.getElementById('main-nav');
            window.addEventListener('scroll', () => {
                if (window.scrollY > 30) {
                    header.classList.add('py-0.5');
                } else {
                    header.classList.remove('py-0.5');
                }
            });
        });
    </script>
</body>
</html>
