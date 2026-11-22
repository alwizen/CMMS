# Rumat | Reliable · Utility · Maintenance · Asset · Tracking

## Panduan Penggunaan Lengkap

---

## Daftar Isi

1. [Akses & Login](#1-akses--login)
2. [Dashboard](#2-dashboard)
3. [Kalender Maintenance](#3-kalender-maintenance)
4. [Master Data](#4-master-data)
5. [Maintenance Request (Korektif)](#5-maintenance-request-korektif)
6. [Generate Work Order (Preventif)](#6-generate-work-order-preventif)
7. [Work Order](#7-work-order)
8. [Riwayat Maintenance](#8-riwayat-maintenance)
9. [Meter Log](#9-meter-log)
10. [Alur & Workflow](#10-alur--workflow)
11. [Akun Dummy](#11-akun-dummy)
12. [REST API](#12-rest-api)

---

## 1. Akses & Login

### URL Akses

```
http://localhost:8000/admin
```

### Halaman Login

Buka browser → akses URL di atas → halaman login akan muncul.

Masukkan email dan password, lalu klik **Log in**.

### Menu Navigasi (Sidebar)

Setelah login, sidebar di sebelah kiri berisi:

```
📊 Dashboard

Master Data
├── 🏢 Companies
├── 📍 Areas
├── ⚙️ Equipment Types
├── 🔧 Equipment
└── ✅ Activities

Maintenance
├── ⚠️ Maintenance Requests
├── 📋 Work Orders
└── 📆 Kalender Maintenance

Riwayat & Monitoring
├── 🕐 Maintenance Histories
└── 📊 Meter Logs
```

---

## 2. Dashboard

Dashboard adalah halaman utama setelah login.

### Stats Overview (Atas)

| Widget | Keterangan |
|---|---|
| Total Equipment | Jumlah seluruh equipment yang terdaftar |
| Work Orders | Total work order yang dibuat |
| WO Selesai | Work order dengan status Completed |
| Request Masuk | Jumlah maintenance request |
| Riwayat | Total catatan riwayat maintenance |

### Global Filter

Klik tombol **Filter** (ikon corong) di pojok kanan atas dashboard.

Pilihan periode:
- **Semua Waktu** — Tampilkan semua data
- **Hari Ini** — Data hari ini saja
- **Kemarin** — Data kemarin
- **7 Hari Terakhir** — Data 7 hari ke belakang
- **1 Bulan Terakhir** — Data 30 hari ke belakang
- **Pilih Range** — Tentukan tanggal awal & akhir sendiri

> Filter ini mempengaruhi semua widget di dashboard: stats, chart, dan tabel.

### Chart

| Widget | Tipe | Keterangan |
|---|---|---|
| Work Order per Klasifikasi | Pie Chart | Perbandingan WO Preventive vs Corrective |
| Equipment per Area | Bar Chart | Jumlah equipment di setiap area |
| Trend Maintenance (6 Bulan) | Line Chart | Grafik trend jumlah maintenance per bulan |

### Tabel

| Widget | Keterangan |
|---|---|
| Work Order Terbaru | 10 work order terakhir dengan status badge |
| Kondisi Equipment | 10 equipment terakhir dengan status kesehatan & kritikal |

---

## 3. Kalender Maintenance

Halaman kalender menampilkan semua jadwal maintenance dalam format kalender interaktif menggunakan FullCalendar.io.

### Akses

Klik menu **Kalender Maintenance** di sidebar, atau akses langsung:

```
http://localhost:8000/admin/maintenance-calendar
```

### Jenis Event di Kalender

| Warna | Tipe | Keterangan |
|---|---|---|
| 🔵 Biru | Work Order (In Progress) | WO yang sedang dikerjakan |
| 🟢 Hijau | Work Order (Completed) | WO yang sudah selesai |
| 🔴 Merah | Work Order (Cancelled) | WO yang dibatalkan |
| ⚪ Abu-abu | Work Order (Open/Default) | WO default |

### View Tersedia

| View | Fungsi |
|---|---|
| **Bulan** | Tampilan bulanan — melihat semua event dalam 1 bulan |
| **Minggu** | Tampilan mingguan — detail event per minggu |
| **Hari** | Tampilan harian — detail event per hari |
| **Daftar** | Tampilan list — semua event dalam format daftar |

### Cara Menggunakan

1. **Navigasi Bulan**: Klik tombol `<` (sebelumnya) atau `>` (selanjutnya) di pojok kiri atas
2. **Kembali ke Hari Ini**: Klik tombol **Hari Ini**
3. **Ganti View**: Klik tombol **Bulan**, **Minggu**, **Hari**, atau **Daftar** di pojok kanan atas
4. **Lihat Detail Event**: Klik pada event di kalender, modal detail akan muncul
5. **Tutup Modal**: Klik tombol X atau klik di luar modal

### Detail Event (Modal)

Saat mengklik event, modal akan menampilkan:
- **Tipe**: Work Order
- **Status**: Status saat ini
- **Equipment**: Nama dan tag number equipment
- **Tanggal**: Tanggal mulai dan selesai
- **Klasifikasi**: Preventive / Corrective (untuk WO)
- **Catatan**: Deskripsi tambahan work order

### Data yang Ditampilkan

Kalender menampilkan **Work Order**:
- Semua work order dengan tanggal mulai
- Warna menunjukkan status

---

## 4. Master Data

### 4.1 Companies (Perusahaan/Site)

Mengelola data perusahaan atau site/facility.

**Field Utama:**

| Field | Keterangan | Contoh |
|---|---|---|
| Code | Kode unik perusahaan | FT-TGL |
| Name | Nama perusahaan | FT Tegal |
| Description | Deskripsi | Fasilitas Teknik Tegal |
| Active | Status aktif | Ya / Tidak |

**Cara Membuat:**

1. Klik **Companies** → **+ Create**
2. Isi **Code** (unik, contoh: `FT-TGL`)
3. Isi **Name** (contoh: `FT Tegal`)
4. Isi **Description** (opsional)
5. Aktifkan toggle **Active**
6. Klik **Create**

---

### 4.2 Areas (Area/Lokasi)

Mengelola area di dalam suatu company. Area merupakan bagian fungsional dari facility.

**Contoh Struktur:**

```
FT Tegal (Company)
├── Loading (LOAD)
├── Unloading (UNLOAD)
├── Storage (STORAGE)
└── MCC (MCC)
```

**Field Utama:**

| Field | Keterangan | Contoh |
|---|---|---|
| Company | Perusahaan induk | FT Tegal |
| Code | Kode area (unik per company) | LOAD |
| Name | Nama area | Loading |
| Description | Deskripsi | Loading area untuk distribusi |
| Active | Status aktif | Ya / Tidak |

**Cara Membuat:**

1. Klik **Areas** → **+ Create**
2. Pilih **Company**
3. Isi **Code** (contoh: `LOAD`)
4. Isi **Name** (contoh: `Loading`)
5. Isi **Description** (opsional)
6. Aktifkan toggle **Active**
7. Klik **Create**

---

### 4.3 Equipment Types (Jenis Equipment)

Mengelola tipe/jenis equipment.

**Contoh data:**

| Code | Name | Description |
|---|---|---|
| PUMP | Pump | Pompa untuk distribusi |
| TANK | Storage Tank | Tangki penyimpanan |
| COMPRESSOR | Compressor | Kompresor udara |
| GENSET | Genset | Generator Set |
| PANEL | Panel | Panel listrik |
| FIRE-PUMP | Fire Pump | Pompa pemadam kebakaran |
| MOTOR | Motor | Motor penggerak |
| VALVE | Valve | Katup/klep |

**Cara Membuat:**

1. Klik **Equipment Types** → **+ Create**
2. Isi **Code**, **Name**, **Description**
3. Aktifkan toggle **Active**
4. Klik **Create**

---

### 4.4 Equipment

Mengelola data equipment / aset.

**Field Utama:**

| Field | Keterangan | Contoh |
|---|---|---|
| Area | Area lokasi | Loading |
| Tag Number | Nomor tag unik | P-101 |
| Name | Nama equipment | Pump |
| Equipment Type | Jenis equipment | Pump |
| Description | Deskripsi | Main loading pump |
| Manufacturer | Pabrikan | GRUNDFOS |
| Model | Nomor model | CR32 |
| Serial Number | Nomor seri | SN-P-101 |
| Installation Date | Tanggal instalasi | 2025-06-15 |
| Operational Unit | Satuan operasi | hour / day / km |
| Photo | Foto equipment | (upload file) |
| Status | Status | active / inactive / retired |
| Criticality | Tingkat kritikal | low / medium / high / critical |

**Cara Membuat:**

1. Klik **Equipment** → **+ Create**
2. Pilih **Area**
3. Isi **Tag Number** (unik, contoh: `P-101`)
4. Isi **Name** (contoh: `Pump`)
5. Isi **Equipment Type** (contoh: `Pump`)
6. Isi field opsional lainnya
7. Pilih **Status** dan **Criticality**
8. Klik **Create**

---

### 4.5 Activities (Aktivitas Maintenance)

Mengelola master aktivitas maintenance yang terkait dengan jenis equipment.

**Field Utama:**

| Field | Keterangan | Contoh |
|---|---|---|
| Equipment Type | Jenis equipment terkait | Pump |
| Name | Nama aktivitas | Pump Oil Change |
| Type | Tipe aktivitas | Maintenance, Inspection, Testing, Cleaning, Replacement |
| Maintenance Classification | Klasifikasi | Preventive / Corrective |
| Interval | Interval pelaksanaan | Daily, Monthly, Quarterly, Yearly |
| Answer Type | Tipe jawaban | Qualitative / Quantitative |
| Optimum | Nilai optimum | 50.0 |
| Minimum | Nilai minimum | 40.0 |
| Maximum | Nilai maksimum | 80.0 |
| Unit | Satuan | °C, mm/s, V |

**Cara Membuat:**

1. Klik **Activities** → **+ Create**
2. Pilih **Equipment Type**
3. Isi nama aktivitas dan tipe
4. Atur batasan nilai jika Answer Type = Quantitative
5. Klik **Create**

---

## 5. Maintenance Request (Korektif)

Digunakan untuk melaporkan kerusakan / gangguan pada equipment (unscheduled/corrective maintenance).

### Flow

```
Equipment mengalami gangguan
    ↓
User membuat Maintenance Request
    ↓
Supervisor mereview & approve (pilih fungsi MOS penanggung jawab)
    ↓
Dibuatkan Work Order untuk fungsi tersebut (shared pool)
```

Saat approve, **Performed By** diisi dengan **fungsi** (wajib), bukan satu orang. Pilihan fungsi dibatasi pada fungsi yang punya petugas role `mos`. WO menjadi milik fungsi tersebut; petugas MOS pertama yang mulai mengerjakan menjadi `performed by`, sedangkan petugas lain pada fungsi yang sama hanya dapat melihat sampai WO selesai.

### Cara Membuat Maintenance Request

1. Klik **Maintenance Requests** → **+ Create**
2. Isi form:
   - **Equipment** — Pilih equipment yang bermasalah
   - **Request Number** — Nomor request (contoh: `MR-20260918-001`)
   - **Reported By** — Siapa yang melaporkan
   - **Operation Status** — Status operasi saat ini (`Running`, `Stopped`, dll)
   - **Description** — Deskripsi masalah/kerusakan
   - **Damage Date** — Tanggal kerusakan
   - **Damage Time** — Waktu kerusakan (opsional)
   - **Equipment Condition** — Kondisi equipment saat ini (opsional)
   - **Impact** — Dampak dari kerusakan (opsional)
   - **Early Action** — Tindakan awal yang sudah dilakukan (opsional)
   - **Status** — `Open`, `Assigned`, `In Progress`, `Completed`, `Rejected`
3. Klik **Create**

### Contoh Isian

```
Equipment        : Pump (P-101)
Request Number   : MR-20260918-001
Reported By      : Technician 1
Operation Status : Stopped
Description      : Pompa mengalami kebocoran pada seal, tidak bisa menghisap air
Damage Date      : 2026-09-18
Damage Time      : 14:30
Equipment Condition : Kondisi darurat, pompa tidak beroperasi
Impact           : Gangguan proses pengiriman air ke storage tank
Early Action     : Pompa dimatikan untuk mencegah kerusakan lebih lanjut
Status           : Open
```

---

## 6. Generate Work Order (Preventif)

Membuat Work Order preventif langsung dari Dashboard tanpa membuat Plan manual.

### Flow

```
Equipment Type punya Penanggung Jawab + checklist (Activity per interval)
    ↓
Dashboard → Generate WO
    ↓
Pilih Equipment Type, Equipment (multi), Interval, jumlah WO
    ↓
Work Order dibuat (status Pending), checklist tersalin otomatis
```

### Cara Generate WO

1. Buka **Dashboard** → klik **Generate WO**
2. Isi form:
   - **Equipment Type** — hanya tipe yang punya Penanggung Jawab & checklist yang muncul
   - **Equipment** — pilih satu/lebih equipment dari tipe tersebut
   - **Interval** — Daily, Weekly, Monthly, Quarterly, Semi-Annual, Yearly
   - **Berapa WO per equipment** — jumlah WO yang dibuat untuk tiap equipment
   - **Jam Mulai** dan **Pemberi Tugas**
   - **Lewati Sabtu & Minggu**
3. Klik **Generate**

> Checklist WO diambil dari activity tipe equipment yang interval-nya sama dengan interval pilihan. Penanggung jawab WO = Penanggung Jawab Equipment Type. Equipment tanpa penanggung jawab atau tanpa checklist yang cocok akan dilewati.

> Catatan: menu **Maintenance Plan** dan **Maintenance Schedule** sudah dihapus.

---

## 7. Work Order

Work Order (WO) adalah transaksi maintenance utama. WO bisa berasal dari 2 sumber:

### Sumber Work Order

```
Preventive:
Generate WO → Work Order

Corrective:
Maintenance Request → Work Order
```

### Nomor Work Order (Otomatis)

WO Number di-generate otomatis dengan format:

```
WO-YYYYMMDD-XXXX

Contoh: WO-20260918-0001
        │    │       │
        │    │       └── Urutan ke-1 hari ini
        │    └────────── Tanggal: 18 September 2026
        └─────────────── Tahun 2026
```

### Cara Membuat Work Order

1. Klik **Work Orders** → **+ Create**
2. **Work Order Number** — Otomatis terisi (tidak perlu diisi)
3. Isi form:
   - **Equipment** — Pilih equipment
   - **Maintenance Request** — Pilih request jika corrective (opsional)
   - **Issued By** — Siapa yang menerbitkan WO
   - **Technician Coordinator** — Koordinator (opsional)
   - **Classification** — `Preventive` atau `Corrective`
   - **Interval** — Interval maintenance (opsional)
   - **Start At** — Tanggal & waktu mulai
   - **Finish At** — Tanggal & waktu selesai (opsional)
   - **Note** — Catatan pekerjaan (opsional)
   - **Status** — `Pending`, `In Progress`, `Completed`, `Cancelled`
4. Klik **Create**

### Contoh Isian

```
Work Order Number       : WO-20260918-0001 (otomatis)
Equipment               : Pump (P-101)
Issued By               : Supervisor
Classification          : Preventive
Interval                : Monthly
Start At                : 2026-09-18 08:00
Status                  : Open
```

### Status Work Order

| Status | Keterangan |
|---|---|
| Pending | Belum mulai dikerjakan |
| In Progress | Sedang dikerjakan |
| Completed | Selesai dikerjakan, menunggu review SPV MOS |
| Cancelled | Dibatalkan |

### Review SPV MOS

WO yang ditandai **Completed** oleh teknisi belum langsung tuntas. WO masuk antrean review SPV MOS:

1. Teknisi menekan **Tandai Selesai** (panel `/app` atau Android). Status WO menjadi `Completed`, `approval_status` menjadi `Pending`.
2. SPV MOS membuka **Work Order** di `/admin`. Badge navigasi dan filter **Perlu Review SPV MOS** menunjukkan WO yang menunggu.
3. **Approve** → `approval_status = Approved`, maintenance history dibuat, dan maintenance request terkait ikut `Completed` bila semua WO-nya sudah tuntas.
4. **Reject** (wajib catatan) → WO dibuka kembali ke `In Progress`, `finish_at` dikosongkan, dan teknisi menerima notifikasi untuk memperbaiki pekerjaan.
5. Selama menunggu review, checklist dan foto tidak dapat diubah (web & API) agar hasil yang direview tidak berubah.

### Work Order Otomatis (Scheduler)

**Pengaturan → Site Setting → Work Order Otomatis** (default **nonaktif**):

| Setting | Keterangan |
|---|---|
| Buat Work Order Otomatis | Toggle utama. Nonaktif = WO hanya dibuat manual dari tombol Generate WO di dashboard |
| Buat WO untuk N Hari ke Depan | Rentang tanggal yang dibuatkan WO (hari ini s.d. +N) |
| Jam Mulai WO | `start_at` WO yang dibuat |
| Lewati Sabtu & Minggu | Hari kerja yang dipakai |
| Batas Backfill Jadwal Terlewat | Berapa hari ke belakang boleh dikejar (0 = tanpa backfill) |

Saat toggle aktif, scheduler harian pukul **00:05** menjalankan:

```bash
php artisan work-orders:generate-scheduled
```

Command ini membuat WO preventif dari **Equipment Type + interval checklist** untuk setiap equipment, satu WO per periode (harian/mingguan/bulanan/…). Periode yang sudah punya WO dilewati, jadi aman dijalankan berulang. WO dibuat berstatus **Pending**, dimiliki **fungsi**, dengan checklist tersalin otomatis.

Syarat: cron `php artisan schedule:run` harus aktif di server. Tombol **Generate WO Sekarang** di Site Setting bisa dipakai untuk menjalankan generator tanpa menunggu scheduler. Untuk mencoba manual:

```bash
php artisan work-orders:generate-scheduled --force --days=3 --backfill=0
```

---

## 8. Riwayat Maintenance

Mencatat semua aktivitas maintenance yang sudah dilakukan.

### Field Utama

| Field | Keterangan | Contoh |
|---|---|---|
| Equipment | Equipment yang di-maintenance | Pump (P-101) |
| Work Order | WO terkait (opsional) | WO-20260918-0001 |
| Maintenance Type | Tipe maintenance | Preventive Maintenance |
| Classification | Klasifikasi | Preventive / Corrective |
| Description | Deskripsi pekerjaan | Pengecekan oli dan pelumasan |
| Maintenance Date | Tanggal pelaksanaan | 2026-09-18 10:00 |
| Performed By | Teknisi pelaksana | Technician 1 |
| Findings | Temuan | Bearing aus, perlu diganti |
| Actions Taken | Tindakan yang dilakukan | Bearing diganti dengan yang baru |
| Status | Status | Completed / In Progress |
| Duration (minutes) | Durasi (menit) | 120 |
| Notes | Catatan tambahan | — |

### Cara Membuat

1. Klik **Maintenance Histories** → **+ Create**
2. Pilih **Equipment** dan **Work Order** (jika ada)
3. Isi **Maintenance Type**, **Classification**, **Description**
4. Atur tanggal dan teknisi pelaksana
5. Isi temuan dan tindakan
6. Klik **Create**

---

## 9. Meter Log

Mencatat pembacaan meter pada equipment (operating hours, flow meter, dll).

### Contoh

```
Equipment    : Genset Backup (GEN-001)
Reading Date : 2026-09-18
Value        : 1250.5
Recorded By  : Technician 1
```

### Cara Membuat

1. Klik **Meter Logs** → **+ Create**
2. Pilih **Equipment**
3. Isi **Reading Date** dan **Value**
4. Pilih **Recorded By**
5. Klik **Create**

---

## 10. Alur & Workflow

### 11.0 Urutan Penggunaan Aplikasi

Pertama kali menggunakan aplikasi, ikuti urutan ini:

**Langkah 1 — Setup Master Data**

| No | Menu | Keterangan |
|---|---|---|
| 1 | Companies | Buat data perusahaan/site terlebih dahulu |
| 2 | Areas | Buat area di dalam company (pilih company) |
| 3 | Equipment Types | Buat jenis equipment (Pump, Tank, Valve, dll) |
| 4 | Equipment | Buat data equipment (pilih area + equipment type) |

**Langkah 2 — Setup Aktivitas**

| No | Menu | Keterangan |
|---|---|---|
| 5 | Activities | Buat master aktivitas maintenance per equipment type |

**Langkah 3 — Operasional Harian**

| No | Menu | Keterangan |
|---|---|---|
| 6 | Maintenance Request | Laporkan kerusakan equipment (jika ada) |
| 7 | Generate WO | Buat Work Order preventif dari Dashboard |
| 9 | Work Order | Terbitkan perintah kerja |
| 10 | Maintenance History | Catat hasil pengerjaan |
| 11 | Meter Log | Catat pembacaan meter berkala |

**Diagram Alur:**

```
Company → Area → Equipment Type → Equipment → Activity
                                                ↓
                              Maintenance Request / Generate WO → Work Order → History
```

> **Tips:** Master data (langkah 1-2) hanya perlu dibuat sekali. Operasional (langkah 3) dilakukan setiap hari sesuai kebutuhan.

### 11.1 Preventive Maintenance (Pencegahan)

```
┌─────────────────────────────────────────────────────────┐
│                   ALUR PREVENTIVE                       │
└─────────────────────────────────────────────────────────┘

1. SETUP MASTER DATA
   ├─ Buat Companies (perusahaan/site)
   ├─ Buat Areas (area di dalam company)
   ├─ Buat Equipment Types (jenis equipment)
   ├─ Buat Equipment (data equipment di area)
   └─ Buat Activities (master aktivitas per jenis)

2. BUAT WORK ORDER (PREVENTIF)
   ├─ Generate WO dari Dashboard
   │   └─ Equipment Type + Equipment + Interval + jumlah
   └─ Checklist tersalin otomatis dari Activity tipe equipment

3. EKSEKUSI
   ├─ Work Order (manual/generate)
   ├─ Assign Worker / Technician ke WO
   ├─ Technician mengerjakan
   │   ├─ Pre Inspection (sebelum kerja)
   │   ├─ Activity Execution (eksekusi aktivitas)
   │   ├─ Follow Up (tindak lanjut)
   │   └─ Final Result (hasil akhir)
   └─ WO Status → Completed

4. DOKUMENTASI
   └─ Catat di Maintenance History
```

### 11.2 Corrective Maintenance (Perbaikan)

```
┌─────────────────────────────────────────────────────────┐
│                   ALUR CORRECTIVE                       │
└─────────────────────────────────────────────────────────┘

1. PELAPORAN
   └─ User/Operator melaporkan kerusakan
       └─ Maintenance Request
           ├─ Equipment yang rusak
           ├─ Deskripsi masalah
           ├─ Tanggal & waktu kerusakan
           └─ Status: Open

2. REVIEW & APPROVAL
   └─ Supervisor mereview request
       ├─ Status: Assigned → Lanjut buat WO
       └─ Status: Rejected → Ditolak dengan catatan

3. BUAT WORK ORDER
   ├─ Maintenance Request → Work Order
   ├─ Classification: Corrective
   ├─ Assign Technician
   └─ Status: Open → In Progress

4. EKSEKUSI
   ├─ Technician mengerjakan perbaikan
   ├─ Update status WO: In Progress → Completed
   └─ Catat di Maintenance History

5. VERIFIKASI
   ├─ Equipment kembali normal
   └─ Status Maintenance Request: Completed
```

### 11.3 Kalender Maintenance

```
┌─────────────────────────────────────────────────────────┐
│                KALENDER MAINTENANCE                     │
└─────────────────────────────────────────────────────────┘

1. Akses kalender dari sidebar → Kalender Maintenance

2. Kalender menampilkan Work Orders (event dengan warna status)

3. Navigasi:
   ├─ Bulan/Minggu/Hari/Daftar (tombol di pojok kanan)
   ├─ Prev/Next (navigasi waktu)
   └─ Hari Ini (kembali ke hari ini)

4. Klik event untuk melihat detail:
   ├─ Tipe (WO/Plan/Schedule)
   ├─ Status
   ├─ Equipment
   ├─ Tanggal
   └─ Info tambahan
```

### 11.4 Diagram Alur Utama

```
┌──────────────────────────────────────────────────────────────────┐
│                     SISTEM MAINTENANCE FT TEGAL                  │
├──────────────────────────────────────────────────────────────────┤
│                                                                  │
│   ┌─────────────┐         ┌──────────────┐                      │
│   │  Equipment   │────────→│ Maintenance  │──── Schedule ────┐  │
│   │  (Pusat)     │         │   Plan       │                   │  │
│   └──────┬──────┘         └──────────────┘                   │  │
│          │                                                     │  │
│          │                                                    ↓  │
│          │                      ┌──────────────┐    ┌──────────┐ │
│          ├─────────────────────→│ Maintenance  │───→│Work Order│ │
│          │                      │  Request     │    │(WO-XXXX) │ │
│          │                      └──────────────┘    └────┬─────┘ │
│          │                                                │       │
│          │                    ┌────────────────┐          │       │
│          │                    │ WO Activities  │←─────────┘       │
│          │                    │ ├ Pre Inspect  │                  │
│          │                    │ ├ Follow Up    │                  │
│          │                    │ └ Report      
             │                  │   
│          │                    └────────────────┘                  │
│          │                         │                             │
│          │                    ┌────┴─────┐                       │
│          │                    │ Aproval  |                       │
│          │                    │          │                       │
│          │                    └──────────┘                       │
│          │                                                       │
│          ├───────→ Meter Logs                                    │
│          └───────→ Maintenance History                           │
│                                                                  │
└──────────────────────────────────────────────────────────────────┘
```

---

## 11. Akun Dummy

Setelah menjalankan seeders, berikut akun yang tersedia:

| Role | Email | Password | Employee No |
|---|---|---|---|
| Admin | admin@admin.com | password | ADM-001 |
| Technician 1 | tech1@ft-tegal.com | password | TECH-001 |
| Technician 2 | tech2@ft-tegal.com | password | TECH-002 |
| Supervisor | supervisor@ft-tegal.com | password | SUP-001 |

### Cara Reset Database & Seed Ulang

```bash
php artisan migrate:fresh --seed
```

### Cara Jalankan Server

```bash
php artisan serve
```

Login terpadu di `http://localhost:8000/login`. Setelah login, user diarahkan otomatis sesuai role:

- **Admin** (supervisor, pengawas, super admin): `http://localhost:8000/admin`
- **App** (petugas lapangan: `mos`, `patra-jasa`; `super_admin` untuk support): `http://localhost:8000/app`

Logout dari panel mana pun kembali ke `/login`.

Role `ltsa_tad` sudah diganti menjadi `mos`. Petugas HSSE tetap memakai role `mos` dan dibedakan lewat kolom `fungsi = HSSE`.

Menu dan aksi panel `/app` sepenuhnya mengikuti permission Shield, diatur dari **/admin → User & Permission → Peran**. Akses masuk panel `/app` memakai `View:FieldDashboard`; menu mengikuti `ViewAny:<Model>`; aksi mengikuti `Create/Update/Delete:<Model>`.

Contoh: agar role `mos` bisa melihat **Dokumen** tanpa bisa membuat/mengubah/menghapus, cukup berikan `ViewAny:Document` dan `View:Document` (tanpa `Create/Update/Delete:Document`). Panel `/app` menampilkan dokumen sebagai arsip read-only. Role default `mos` dan `patra-jasa` sudah diberi permission awal lewat `FieldPermissionSeeder` / migrasi `sync_field_panel_permissions`.

---

---

## 12. REST API

API untuk integrasi dengan perangkat mobile (Android/iOS). Menggunakan **Laravel Sanctum** untuk autentikasi token.

### Base URL

```
http://localhost:8000/api
```

### Header Standar

Semua request yang membutuhkan autentikasi harus menyertakan header:

```
Authorization: Bearer <token>
Content-Type: application/json
Accept: application/json
```

### Format Response

```json
{
    "success": true,
    "message": "Pesan sukses",
    "data": { ... },
    "meta": {
        "current_page": 1,
        "last_page": 5,
        "per_page": 15,
        "total": 75
    }
}
```

---

### 13.1 Autentikasi

#### `POST /api/login`

Login dan mendapatkan token.

**Request Body:**

| Field | Tipe | Wajib | Keterangan |
|---|---|---|---|
| email | string | Ya | Email user |
| password | string | Ya | Password user |

**Contoh Request (cURL):**

```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"tech1@ft-tegal.com","password":"password"}'
```

**Contoh Response (200):**

```json
{
    "success": true,
    "message": "Login berhasil.",
    "data": {
        "user": {
            "id": 2,
            "name": "Technician 1",
            "email": "tech1@ft-tegal.com",
            "employee_number": "TECH-001",
            "phone": null,
            "roles": ["technician"]
        },
        "token": "1|abc123def456..."
    }
}
```

**Error Response (422):**

```json
{
    "message": "Email atau password salah.",
    "errors": {
        "email": ["Email atau password salah."]
    }
}
```

---

#### `POST /api/logout`

Logout dan revoke token saat ini.

```bash
curl -X POST http://localhost:8000/api/logout \
  -H "Authorization: Bearer <token>"
```

**Response (200):**

```json
{
    "success": true,
    "message": "Logout berhasil."
}
```

---

#### `GET /api/me`

Mendapatkan profil user yang sedang login.

```bash
curl http://localhost:8000/api/me \
  -H "Authorization: Bearer <token>"
```

**Response (200):**

```json
{
    "success": true,
    "data": {
        "id": 2,
        "name": "Technician 1",
        "email": "tech1@ft-tegal.com",
        "employee_number": "TECH-001",
        "phone": null,
        "status": true,
        "roles": ["technician"]
    }
}
```

---

### 13.2 Work Orders

#### `GET /api/work-orders`

Daftar work order.

| Parameter | Tipe | Keterangan |
|---|---|---|
| per_page | integer | Jumlah data per halaman (default: 15) |
| status | string | Filter: `Pending`, `In Progress`, `Completed`, `Cancelled` |
| classification | string | Filter: `Preventive`, `Corrective` |
| search | string | Cari berdasarkan WO number, note, atau nama/tag equipment |

```bash
curl "http://localhost:8000/api/work-orders?status=In+Progress" \
  -H "Authorization: Bearer <token>"
```

---

#### `GET /api/work-orders/{id}`

Detail work order lengkap (activities, workers, request).

Field klaim pengerjaan:

| Field | Keterangan |
|---|---|
| is_unclaimed | `true` bila WO belum diambil siapa pun |
| is_locked | `true` bila WO sedang dikerjakan user lain |
| locked_by | `{id, name}` user yang mengerjakan, atau `null` |
| lock_message | Pesan siap tampil, mis. "Work order sedang dikerjakan oleh Budi. Anda hanya dapat melihat." |
| can_execute | `false` bila tanggal belum tiba, WO dikunci user lain, atau WO sudah selesai |
| approval_status | `Pending`, `Approved`, atau `Rejected` |
| is_awaiting_review | `true` bila WO selesai dikerjakan dan menunggu review SPV MOS |
| approval_notes | Catatan review SPV MOS |
| approved_at | Waktu review disetujui/ditolak |
| approved_by | `{id, name}` SPV MOS yang mereview, atau `null` |

```bash
curl http://localhost:8000/api/work-orders/1 \
  -H "Authorization: Bearer <token>"
```

---

#### `PUT /api/work-orders/{id}`

Update work order (status, catatan, tanggal).

| Field | Tipe | Keterangan |
|---|---|---|
| status | string | `Pending`, `In Progress`, `Completed`, `Cancelled`. `On Hold` ditolak `422`. |
| note | string | Catatan pekerjaan |
| start_at | datetime | Waktu mulai (ISO 8601) |
| finish_at | datetime | Waktu selesai (ISO 8601) |

```bash
curl -X PUT http://localhost:8000/api/work-orders/1 \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{"status":"Completed","note":"Selesai dikerjakan"}'
```

**Visibilitas request.** Petugas MOS melihat maintenance request miliknya, request untuk equipment dengan fungsi yang sama, serta request dari WO yang ia pegang. Supervisor/pengawas/super admin melihat semua.

**Hasil checklist kualitatif.** Item dengan `answer_type = qualitative` memakai pilihan kondisi untuk `pre_inspection` dan `final_result`:

| Nilai | Keterangan |
|---|---|
| `Good` | Kondisi baik dan berfungsi normal |
| `Fair` | Kondisi cukup baik, ada sedikit kerusakan atau penurunan fungsi |
| `Poor` | Kondisi buruk, perlu perbaikan |
| `Not Working` | Tidak berfungsi atau rusak total |
| `Under Maintenance` | Sedang dalam perawatan atau perbaikan |

`GET /api/work-orders/{id}` mengembalikan `answer_type`, `is_qualitative`, dan `result_options` (`[{value,label}]`) per aktivitas. `PUT .../activities/{id}` menolak nilai di luar daftar dengan `422`. Item manual (tanpa master checklist) dianggap kualitatif; item kuantitatif tetap memakai teks bebas.

**Review SPV MOS.** WO berstatus `Completed` menyertakan `approval_status` (`Pending`/`Approved`/`Rejected`), `approval_notes`, `is_awaiting_review`, `approved_at`, dan `approved_by`. Perubahan checklist/foto setelah WO selesai ditolak `403` dengan pesan `Work order sudah diselesaikan dan menunggu review SPV MOS.` Maintenance history dan status request `Completed` baru terjadi setelah review **Approve**.

**Klaim WO per fungsi.** User pertama yang memulai/menyelesaikan WO menjadi `performed by`. User lain pada fungsi yang sama hanya bisa melihat; perubahan status/checklist/foto ditolak `403` dengan pesan `Work order sedang dikerjakan oleh <nama>. Anda hanya dapat melihat.` Role `super_admin`, `pengawas`, dan `spv_mos` tetap bisa menindaklanjuti.

---

#### `PUT /api/work-orders/{woId}/activities/{actId}`

Update aktivitas work order (hasil inspeksi, status eksekusi).

| Field | Tipe | Keterangan |
|---|---|---|
| pre_inspection | string | Hasil inspeksi awal |
| follow_up | string | Tindak lanjut |
| final_result | string | Hasil akhir |
| executed | boolean | Sudah dieksekusi atau belum |
| note | string | Catatan |

```bash
curl -X PUT http://localhost:8000/api/work-orders/1/activities/1 \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{"executed":true,"final_result":"Normal"}'
```

---

### 13.3 Equipment

#### `GET /api/equipment`

Daftar equipment.

| Parameter | Tipe | Keterangan |
|---|---|---|
| per_page | integer | Jumlah data per halaman (default: 15) |
| area_id | integer | Filter berdasarkan area |
| status | string | Filter: `active`, `inactive`, `retired` |
| search | string | Cari berdasarkan nama, tag number, atau tipe |

```bash
curl "http://localhost:8000/api/equipment?status=active" \
  -H "Authorization: Bearer <token>"
```

---

#### `GET /api/equipment/{id}`

Detail equipment (+ tipe & penanggung jawab).

```bash
curl http://localhost:8000/api/equipment/1 \
  -H "Authorization: Bearer <token>"
```

---

### 13.4 Ringkasan Endpoint

| Method | Endpoint | Auth | Keterangan |
|---|---|---|---|
| `POST` | `/api/login` | Tidak | Login, dapatkan token |
| `POST` | `/api/logout` | Ya | Logout, revoke token |
| `GET` | `/api/me` | Ya | Profil user login |
| `GET` | `/api/work-orders` | Ya | Daftar work order |
| `GET` | `/api/work-orders/{id}` | Ya | Detail work order |
| `PUT` | `/api/work-orders/{id}` | Ya | Update work order |
| `PUT` | `/api/work-orders/{woId}/activities/{actId}` | Ya | Update aktivitas WO |
| `GET` | `/api/equipment` | Ya | Daftar equipment |
| `GET` | `/api/equipment/{id}` | Ya | Detail equipment |
| `GET` | `/api/maintenance-requests` | Ya | Daftar maintenance request |
| `POST` | `/api/maintenance-requests` | Ya | Buat maintenance request |
| `PUT` | `/api/maintenance-requests/{id}` | Ya | Update maintenance request |
| `GET` | `/api/downtime` | Ya | Laporan downtime |
| `GET` | `/api/dashboard/housekeeping` | Ya | Ringkasan housekeeping (plan aktif, progres, SLA). Khusus `patra-jasa` & akses penuh |
| `GET` | `/api/housekeeping-areas` | Ya | Daftar area housekeeping |
| `GET` | `/api/housekeeping-areas/{id}/manpowers` | Ya | Manpower aktif area |
| `GET` | `/api/housekeeping-areas/{id}/facilities` | Ya | Fasilitas + master checklist |
| `GET` | `/api/reports/daily-housekeeping?date=YYYY-MM-DD` | Ya | Laporan harian housekeeping (PDF). Khusus role `patra-jasa` & akses penuh |
| `GET` | `/api/housekeeping-plans` | Ya | Daftar plan harian |
| `POST` | `/api/housekeeping-plans` | Ya | Buat plan kosong |
| `POST` | `/api/housekeeping-plans/generate` | Ya | Generate plan + checklist dari area |
| `GET` | `/api/housekeeping-plans/{id}` | Ya | Detail plan + checklist |
| `PUT` | `/api/housekeeping-plans/{id}` | Ya | Update tanggal/catatan plan |
| `DELETE` | `/api/housekeeping-plans/{id}` | Ya | Hapus plan |
| `POST` | `/api/housekeeping-plans/{planId}/tasks` | Ya | Tambah item checklist |
| `DELETE` | `/api/housekeeping-plans/{planId}/tasks/{taskId}` | Ya | Hapus item checklist |
| `PUT` | `/api/housekeeping-plans/{planId}/tasks/{taskId}` | Ya | Update status/catatan item |
| `POST` | `/api/housekeeping-plans/{planId}/facilities/{planFacilityId}/photos` | Ya | Upload foto before/after fasilitas |
| `DELETE` | `/api/housekeeping-plans/{planId}/facilities/{planFacilityId}/photos` | Ya | Hapus foto fasilitas |
| `POST` | `/api/housekeeping-plans/{planId}/manpowers` | Ya | Tugaskan manpower |
| `DELETE` | `/api/housekeeping-plans/{planId}/manpowers/{manpowerId}` | Ya | Lepas manpower |
| `GET` | `/api/housekeeping-requests` | Ya | Daftar housekeeping request (`?mine=1` untuk milik sendiri) |
| `POST` | `/api/housekeeping-requests` | Ya | Buat request (multipart `photos[]`) |
| `GET` | `/api/housekeeping-requests/{id}` | Ya | Detail request |
| `PUT` | `/api/housekeeping-requests/{id}` | Ya | Ubah request (status Open) |
| `DELETE` | `/api/housekeeping-requests/{id}` | Ya | Hapus request (status Open) |
| `POST` | `/api/housekeeping-requests/{id}/photos` | Ya | Upload foto request |
| `DELETE` | `/api/housekeeping-requests/{id}/photos` | Ya | Hapus foto request |
| `POST` | `/api/housekeeping-requests/{id}/approve` | Ya | Setujui request + buat plan |
| `POST` | `/api/housekeeping-requests/{id}/reject` | Ya | Tolak request |

---

### 13.5 Alur Housekeeping untuk Android

**A. Supervisor membuat request dari lapangan**

```bash
# 1) Ambil area & fasilitas + master checklist
curl http://localhost:8000/api/housekeeping-areas -H "Authorization: Bearer <token>"
curl http://localhost:8000/api/housekeeping-areas/1/facilities -H "Authorization: Bearer <token>"

# 2) Kirim request beserta foto (multipart)
curl -X POST http://localhost:8000/api/housekeeping-requests   -H "Authorization: Bearer <token>"   -H "Accept: application/json"   -F "housekeeping_area_id=1"   -F "housekeeping_facility_id=2"   -F "request_date=2026-11-20"   -F "description=Gazebo kotor setelah acara"   -F "priority=High"   -F "photos[]=@foto1.jpg"

# 3) Pantau status request milik sendiri
curl "http://localhost:8000/api/housekeeping-requests?mine=1" -H "Authorization: Bearer <token>"
```

**B. Approver menyetujui request (otomatis membuat Plan Harian)**

```bash
curl -X POST http://localhost:8000/api/housekeeping-requests/1/approve   -H "Authorization: Bearer <token>"   -H "Content-Type: application/json"   -d '{"plan_date":"2026-11-20","manpower_ids":[1,2],"approval_notes":"Disetujui"}'
```

**C. Generate Plan Harian langsung dari master checklist area**

```bash
curl -X POST http://localhost:8000/api/housekeeping-plans/generate   -H "Authorization: Bearer <token>"   -H "Content-Type: application/json"   -d '{"area_ids":[1],"plan_date":"2026-11-20","manpower_ids":[1]}'
```

Response `201` berisi `data` (plan yang dibuat) dan `skipped` (alasan area dilewati). Jika tidak ada plan yang dibuat, response `422` dengan `created: 0`.

**D. Eksekusi checklist & dokumentasi fasilitas (mobile)**

Foto dokumentasi dikelola **per fasilitas** pada plan, bukan per item checklist.
`GET /api/housekeeping-plans/{id}` mengembalikan `facilities[]` berisi `id` (plan facility id) dan `photo_before`/`photo_after`.

```bash
# 1) WAJIB upload foto before fasilitas dulu
curl -X POST http://localhost:8000/api/housekeeping-plans/1/facilities/1/photos   -H "Authorization: Bearer <token>" -F "type=before" -F "photos[]=@before.jpg"

# 2) Baru boleh mulai kerja (tanpa foto before -> 422)
curl -X PUT http://localhost:8000/api/housekeeping-plans/1/tasks/1   -H "Authorization: Bearer <token>" -H "Content-Type: application/json"   -d '{"status":"In Progress"}'

# 3) WAJIB upload foto after fasilitas dulu
curl -X POST http://localhost:8000/api/housekeeping-plans/1/facilities/1/photos   -H "Authorization: Bearer <token>" -F "type=after" -F "photos[]=@after.jpg"

# 4) Baru boleh selesaikan (tanpa foto after -> 422)
curl -X PUT http://localhost:8000/api/housekeeping-plans/1/tasks/1   -H "Authorization: Bearer <token>" -H "Content-Type: application/json"   -d '{"status":"Done","note":"Selesai"}'
```

Urutan ini juga divalidasi di web (Filament): tombol "Mulai Kerja" dan "Selesaikan" menolak aksi bila foto fasilitas belum ada.

**Visibilitas plan per penanggung jawab area.** Patra-jasa hanya melihat plan pada area yang ia menjadi penanggung jawab (`housekeeping_areas.supervisor_id`) atau plan yang secara eksplisit menugaskannya (`housekeeping_plan_users`). Aturan ini berlaku di `GET /api/housekeeping-plans`, `GET /api/housekeeping-plans/{id}` (403 bila bukan haknya), serta seluruh angka pada `GET /api/dashboard/housekeeping` (plan aktif, progres checklist, pekerjaan selesai hari ini). SPV GOS, `pengawas`, dan `super_admin` tetap melihat semua area.

**Dashboard Android per role.** User `patra-jasa` mendarat di **dashboard housekeeping** (bukan dashboard maintenance): kartu progres pekerjaan aktif, jumlah plan aktif/lewat SLA/selesai hari ini, pintasan Daftar Plan, Laporan, dan Profil, serta daftar plan berjalan. Datanya berasal dari `GET /api/dashboard/housekeeping`. Role akses penuh tetap memakai dashboard maintenance.

**UX generate laporan di Android.** Saat laporan dibuat, aplikasi menampilkan dialog progres tanpa tombol ("Membuat laporan… sedang diproses") dan ikon di app bar berubah menjadi spinner, sehingga user tahu prosesnya berjalan dan tidak menekan ulang. Setelah selesai, muncul dialog hasil: **"Laporan siap"** bila berhasil (PDF langsung dibuka), atau **"Laporan gagal dibuat"** dengan alasan dari server apa adanya (mis. plan belum direview/disetujui SPV GOS, tidak punya hak akses 403, atau file tidak valid). Error tidak lagi hanya masuk ke state daftar plan — tersimpan terpisah di `reportError`/`reportMessage` agar selalu terlihat oleh user yang login.

**Laporan harian dari Android.** User housekeeping (`patra-jasa`) melihat ikon dokumen di app bar daftar plan.

Catatan Android: file laporan disimpan dengan nama unik (`laporan-housekeeping-YYYY-MM-DD-<timestamp>.pdf`) dan laporan lama di cache dihapus. Ini penting karena penampil PDF (Chrome/MuPDF/Drive) meng-cache berdasarkan nama file — dengan nama tetap, laporan baru bisa menampilkan versi lama yang belum ada fotonya. Aplikasi juga memvalidasi header `%PDF` sebelum membuka file. Menekan ikon itu membuka date picker, lalu PDF laporan harian untuk tanggal tersebut diunduh dari `GET /api/reports/daily-housekeeping?date=YYYY-MM-DD` dan langsung dibuka dengan aplikasi PDF. Endpoint ini juga boleh diakses role akses penuh (`super_admin`, `spv_gos`, `pengawas`).

Di aplikasi Android, alur per fasilitas di UI:

1. Upload **foto before** fasilitas.
2. Tekan **Mulai Kerjakan** → seluruh checklist fasilitas itu menjadi `In Progress` sekaligus.
3. **Centang** checklist yang sudah dikerjakan (belum dikirim ke server).
4. Upload **foto after** fasilitas.
5. Tekan **Selesaikan** → checklist yang dicentang dikirim sebagai `Done`. Checklist `Done` dipindahkan ke bawah daftar.

> Catatan: foto dikembalikan sebagai path relatif pada disk `public` (mis. `housekeeping/plan-facilities/before/xxx.jpg`). Android perlu menambahkan prefix `{base_url}/storage/` untuk menampilkan gambar.

**E. Laporan harian housekeeping**

Sekali generate laporan untuk tanggal tertentu, PDF memuat:

1. Ringkasan: plan berjalan, plan dimulai hari ini, plan selesai hari ini, pekerjaan selesai, plan melewati SLA.
2. Detail **plan yang dimulai hari ini**.
3. Detail **plan yang masih berjalan** (termasuk yang sudah lewat SLA).
4. Detail **plan yang selesai** pada tanggal itu.

Pada setiap detail plan, checklist dan dokumentasi foto disajikan dalam **satu tabel**: kolom `Pekerjaan | Fasilitas | Status | Foto Before | Foto After`. Foto before/after ditampilkan sekali per fasilitas (sel digabung dengan `rowspan`), sehingga tidak ada tabel foto terpisah dan tidak ada foto yang berulang di tiap baris pekerjaan.

Tabel rekap "Seluruh pekerjaan hari ini" dihapus karena isinya sudah tercakup pada detail plan di poin 2–4.

**F. Progress & SLA plan (otomatis)**

Progress **tidak diisi manual** — server menghitung `progress_percent` = item checklist `Done` ÷ total item. SLA dihitung dari `plan_date + sla_days` area (mis. kantor 1 hari, area luar 14 hari). Setiap plan mengembalikan `progress_percent`, `sla_days`, `due_date`, `sla_status` (`On Track` / `At Risk` / `Overdue` / `Completed`), dan `is_overdue`.

Aplikasi Android cukup menampilkan nilai tersebut; perubahan status checklist (`PUT .../tasks/{taskId}`) otomatis memperbarui progress plan.

**G. Pembagian peran**

- **Admin web**: membuat/meng-generate Plan Harian, menambah item checklist, menugaskan manpower.
- **Patra-jasa (Android)**: hanya melihat plan yang sudah dibuat, mengubah status checklist (Pending → In Progress → Done), serta mengunggah foto before/after **per fasilitas**.

**G2. Approval Plan Harian oleh SPV GOS**

Role supervisor dibagi per bidang: `spv_mos` (maintenance), **`spv_gos` (housekeeping/General Office Services)**, dan `spv_hsse` (HSSE).

**Pemisahan peran housekeeping:**

| Aksi | SPV GOS (`spv_gos`) | Patra-jasa (`patra-jasa`) |
|---|---|---|
| Buat / generate / ubah / hapus Plan Harian | ✅ | ❌ (403 di API, tombol disembunyikan di web & Android) |
| Tambah/hapus checklist & fasilitas plan | ✅ | ❌ |
| Tugaskan manpower | ✅ | ❌ |
| Mulai/selesaikan checklist | ❌ | ✅ |
| Upload/hapus foto before-after fasilitas | ❌ | ✅ |
| Approve / Reject laporan harian | ✅ | ❌ |
| Lihat plan & laporan | ✅ | ✅ |

Alur housekeeping:

1. **SPV GOS membuat Plan Harian** (generate / tambah checklist, tugaskan manpower) lalu **langsung diteruskan ke patra-jasa** — tidak ada approval di awal. Penanggung jawab area tetap petugas `patra-jasa`.
2. **Patra-jasa mengerjakan** checklist + upload foto before/after dari Android sampai semua checklist `Done` (plan menjadi `Completed`). Android menyembunyikan tombol eksekusi untuk SPV GOS dan sebaliknya.
3. **SPV GOS me-review pekerjaan** — bisa saat masih berjalan maupun setelah selesai — lalu **Approve** (atau **Reject** dengan catatan) dari halaman Plan Harian (tabel maupun halaman detail). Tombol review muncul begitu pekerjaan dimulai, dan notifikasi dikirim saat pekerjaan selesai.
4. **Approval SPV GOS adalah trigger laporan**: setelah di-approve, patra-jasa bisa **mengunduh laporan harian hari itu** dari Android (menu Housekeeping → ikon laporan).

Laporan ditolak (422) bila ada plan yang **belum direview/disetujui** SPV GOS atau **ditolak**. Bila patra-jasa mengubah checklist setelah approval, status kembali `Pending` sehingga SPV GOS perlu review ulang (mencegah laporan berisi pekerjaan yang belum diperiksa). Notifikasi maintenance tetap ke `spv_mos`.

Approval ini menjadi **gerbang generate laporan harian housekeeping**: laporan tanggal tertentu hanya bisa digenerate bila semua plan pada tanggal itu **dan** semua plan lain yang masih berjalan sudah `Approved`. Bila masih ada yang `Pending`/`Rejected`, web dan Android menerima pesan penolakan (422) berisi nomor plan yang menahan. Plan lama yang sudah ada sebelum fitur ini otomatis dianggap `Approved`.

Endpoint terkait:
- `GET /api/housekeeping-plans/{id}` kini menyertakan `approval_status`, `approval_notes`, `approved_at`, `approved_by`.
- `GET /api/dashboard/housekeeping` menyertakan `approval_status` pada setiap plan berjalan.
- `GET /api/reports/daily-housekeeping` mengembalikan `422` + `data.pending_plans`/`data.rejected_plans` bila belum boleh digenerate.

**H. Penguncian pembuatan plan**

Plan baru untuk satu area **ditolak** bila masih ada plan yang belum `Completed` dan batas SLA-nya belum lewat. Aturan ini berlaku di generate Plan Harian, approve request (web + API), dan `POST /api/housekeeping-plans`. Setelah SLA lewat (overdue) atau plan selesai, area boleh digenerate lagi.

**I. Klasifikasi KPI housekeeping**

Area housekeeping punya `kpi_class` (`indoor` / `outdoor`) yang menentukan set metrik KPI:

| Indoor (plan harian) | Bobot | Outdoor (plan per window SLA) | Bobot |
|---|---|---|---|
| `task_completion_rate` | 25 | `task_completion_rate` | 25 |
| `sla_compliance` | 25 | `sla_compliance` | 25 |
| `plan_consistency` (hari ber-plan ÷ hari periode) | 20 | `sla_efficiency` (SLA ÷ hari selesai, maks 100%) | 20 |
| `documentation_compliance` | 15 | `window_coverage` (hari tercakup plan ÷ hari periode) | 15 |
| `backlog` (maks backlog yang ditoleransi) | 15 | `documentation_compliance` | 15 |

Target & bobot bisa diubah lewat menu **KPI > KPI Metric Settings**.

---

### 13.6 Testing dengan Bruno/Postman

1. **Login** - `POST /api/login` dengan email + password → copy token
2. **Set Header** - `Authorization: Bearer <token>` di semua request
3. **Get Data** - Akses endpoint lainnya dengan token


---

## Struktur File

```
maintenance/
├── app/
│   ├── Filament/
│   │   ├── Pages/
│   │   │   ├── Dashboard.php
│   │   │   └── MaintenanceCalendar.php
│   │   ├── Resources/
│   │   │   ├── Activities/
│   │   │   ├── Companies/
│   │   │   ├── Areas/
│   │   │   ├── Equipment/
│   │   │   ├── EquipmentTypes/
│   │   │   ├── MaintenanceHistories/
│   │   │   ├── MaintenancePlans/
│   │   │   ├── MaintenanceRequests/
│   │   │   ├── MaintenanceSchedules/
│   │   │   ├── MeterLogs/
│   │   │   └── WorkOrders/
│   │   └── Widgets/
│   ├── Http/Controllers/Api/
│   │   ├── AuthController.php
│   │   ├── EquipmentController.php
│   │   ├── MaintenancePlanController.php
│   │   ├── MaintenanceScheduleController.php
│   │   └── WorkOrderController.php
│   ├── Http/Resources/
│   │   ├── EquipmentResource.php
│   │   ├── MaintenancePlanResource.php
│   │   ├── MaintenancePlanActivityResource.php
│   │   ├── MaintenanceScheduleResource.php
│   │   ├── WorkOrderResource.php
│   │   ├── WorkOrderActivityResource.php
│   │   └── WorkOrderWorkerResource.php
│   ├── Models/
│   ├── Observers/
│   │   └── WorkOrderObserver.php
│   └── Providers/Filament/
│       └── AdminPanelProvider.php
├── routes/
│   ├── api.php
│   └── web.php
├── database/
│   ├── migrations/
│   └── seeders/
└── resources/views/filament/pages/
    └── maintenance-calendar.blade.php
```
