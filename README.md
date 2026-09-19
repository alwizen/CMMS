# FT Tegal Maintenance Management System

## Panduan Penggunaan Lengkap

---

## Daftar Isi

1. [Akses & Login](#1-akses--login)
2. [Dashboard](#2-dashboard)
3. [Kalender Maintenance](#3-kalender-maintenance)
4. [Master Data](#4-master-data)
5. [Maintenance Request (Korektif)](#5-maintenance-request-korektif)
6. [Maintenance Plan (Preventif)](#6-maintenance-plan-preventif)
7. [Maintenance Schedule](#7-maintenance-schedule)
8. [Work Order](#8-work-order)
9. [Riwayat Maintenance](#9-riwayat-maintenance)
10. [Meter Log](#10-meter-log)
11. [Alur & Workflow](#11-alur--workflow)
12. [Akun Dummy](#12-akun-dummy)
13. [REST API](#13-rest-api)

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
├── 📅 Maintenance Plans
├── 📆 Maintenance Schedules
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
| 🟡 Kuning | Work Order (On Hold) | WO yang ditunda |
| 🔴 Merah | Work Order (Cancelled) | WO yang dibatalkan |
| 🟣 Ungu (Background) | Maintenance Plan | Rentang waktu rencana aktif |
| 🟠 Oranye | Schedule (Delayed) | Jadwal yang terlambat |
| 🟣 Ungu | Schedule (Rescheduled) | Jadwal yang dijadwalkan ulang |
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
- **Tipe**: Work Order / Maintenance Plan / Maintenance Schedule
- **Status**: Status saat ini
- **Equipment**: Nama dan tag number equipment
- **Tanggal**: Tanggal mulai dan selesai
- **Klasifikasi**: Preventive / Corrective (untuk WO)
- **Interval**: Interval maintenance (untuk Plan)
- **Keterangan**: Deskripsi tambahan (untuk Schedule)

### Data yang Ditampilkan

Kalender menampilkan 3 jenis data sekaligus:

**Work Orders** (event biasa):
- Semua work order dengan tanggal mulai
- Warna menunjukkan status

**Maintenance Plans** (background):
- Rentang waktu plan aktif
- Ditampilkan sebagai background ungu transparan

**Maintenance Schedules** (event):
- Jadwal maintenance yang sudah dijadwalkan
- Warna menunjukkan status schedule

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
Supervisor mereview & approve
    ↓
Dibuatkan Work Order
```

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

## 6. Maintenance Plan (Preventif)

Digunakan untuk membuat rencana maintenance berkala pada equipment.

### Flow

```
Equipment dipilih
    ↓
Maintenance Plan dibuat
    ↓
Aktivitas-aktivitas ditambahkan ke plan
    ↓
Maintenance Schedule di-generate per periode
    ↓
Work Order dibuat otomatis per jadwal
```

### Cara Membuat Maintenance Plan

1. Klik **Maintenance Plans** → **+ Create**
2. Isi form:
   - **Equipment** — Pilih equipment
   - **Maintenance Classification** — `Preventive`, `Predictive`, atau `Condition-Based`
   - **Interval** — `Daily`, `Weekly`, `Monthly`, `Quarterly`, `Bi-Annually`, `Yearly`
   - **Start Date** — Tanggal mulai berlaku
   - **End Date** — Tanggal berakhir (opsional)
   - **Created By** — Siapa yang membuat plan
   - **Technician Coordinator** — Koordinator teknisi (opsional)
   - **Description** — Deskripsi plan
   - **Status** — `Active`, `Inactive`, `Suspended`, `Completed`
3. Klik **Create**

### Contoh

```
Equipment               : Pump (P-101)
Maintenance Classification : Preventive
Interval                : Monthly
Start Date              : 2026-01-01
End Date                : 2026-12-31
Created By              : Supervisor
Description             : Plan maintenance bulanan untuk Pump
Status                  : Active
```

---

## 7. Maintenance Schedule

Jadwal aktual yang muncul di planner, di-generate dari Maintenance Plan.

### Cara Membuat

1. Klik **Maintenance Schedules** → **+ Create**
2. Isi form:
   - **Maintenance Plan** — Pilih plan terkait
   - **Equipment** — Equipment yang akan di-maintenance
   - **Scheduled Date** — Tanggal yang dijadwalkan
   - **Status** — `Scheduled`, `In Progress`, `Completed`, `Delayed`, `Rescheduled`, `Cancelled`
   - **Description** — Keterangan (opsional)
   - **Rescheduled From** — Tanggal asli jika di-reschedule (opsional)
3. Klik **Create**

### Status Schedule

| Status | Keterangan |
|---|---|
| Scheduled | Sudah dijadwalkan |
| In Progress | Sedang dikerjakan |
| Completed | Selesai dikerjakan |
| Delayed | Terlambat / tertunda |
| Rescheduled | Dijadwalkan ulang |
| Cancelled | Dibatalkan |

---

## 8. Work Order

Work Order (WO) adalah transaksi maintenance utama. WO bisa berasal dari 2 sumber:

### Sumber Work Order

```
Preventive:
Maintenance Plan → Schedule → Work Order

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
   - **Maintenance Plan** — Pilih plan jika preventive (opsional)
   - **Maintenance Schedule** — Pilih schedule jika ada (opsional)
   - **Maintenance Request** — Pilih request jika corrective (opsional)
   - **Issued By** — Siapa yang menerbitkan WO
   - **Technician Coordinator** — Koordinator (opsional)
   - **Classification** — `Preventive` atau `Corrective`
   - **Interval** — Interval maintenance (opsional)
   - **Start At** — Tanggal & waktu mulai
   - **Finish At** — Tanggal & waktu selesai (opsional)
   - **Note** — Catatan pekerjaan (opsional)
   - **Status** — `Open`, `In Progress`, `On Hold`, `Completed`, `Cancelled`
4. Klik **Create**

### Contoh Isian

```
Work Order Number       : WO-20260918-0001 (otomatis)
Equipment               : Pump (P-101)
Maintenance Plan        : Plan bulanan Pump
Issued By               : Supervisor
Classification          : Preventive
Interval                : Monthly
Start At                : 2026-09-18 08:00
Status                  : Open
```

### Status Work Order

| Status | Keterangan |
|---|---|
| Open | Belum mulai dikerjakan |
| In Progress | Sedang dikerjakan |
| On Hold | Ditunda sementara |
| Completed | Selesai dikerjakan |
| Cancelled | Dibatalkan |

---

## 9. Riwayat Maintenance

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

## 10. Meter Log

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

## 11. Alur & Workflow

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

2. BUAT RENCANA
   ├─ Maintenance Plan (rencana berkala)
   │   └─ Equipment + Interval + Classification
   ├─ Tambahkan Aktivitas ke Plan
   └─ Maintenance Schedule (jadwal per periode)

3. EKSEKUSI
   ├─ Schedule → Work Order (otomatis/manual)
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

2. Kalender menampilkan 3 jenis data:
   ├─ Work Orders (event dengan warna status)
   ├─ Maintenance Plans (background ungu)
   └─ Maintenance Schedules (event dengan warna status)

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
│          │                    │ └ Final Result │                  │
│          │                    └────────────────┘                  │
│          │                         │                             │
│          │                    ┌────┴─────┐                       │
│          │                    │ Work Order│                       │
│          │                    │ Workers   │                       │
│          │                    └──────────┘                       │
│          │                                                       │
│          ├───────→ Meter Logs                                    │
│          └───────→ Maintenance History                           │
│                                                                  │
└──────────────────────────────────────────────────────────────────┘
```

---

## 12. Akun Dummy

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

Akses: `http://localhost:8000/admin`

---

## Struktur File

```
maintenance/
├── app/
│   ├── Filament/
│   │   ├── Pages/
│   │   │   ├── Dashboard.php              ← Dashboard + Filter
│   │   │   └── MaintenanceCalendar.php    ← Kalender Maintenance
│   │   ├── Resources/                     ← Resource CRUD
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
│   │   └── Widgets/                       ← Widget Dashboard
│   │       ├── Concerns/
│   │       │   └── FiltersByDate.php
│   │       ├── StatsOverviewWidget.php
│   │       ├── WorkOrderClassificationChart.php
│   │       ├── EquipmentByLocationChart.php
│   │       ├── MaintenanceTrendChart.php
│   │       ├── RecentWorkOrdersWidget.php
│   │       └── EquipmentHealthWidget.php
│   ├── Models/                            ← Eloquent Model
│   ├── Observers/
│   │   └── WorkOrderObserver.php          ← Auto WO number
│   └── Providers/
│       └── Filament/
│           └── AdminPanelProvider.php
├── database/
│   ├── migrations/                        ← Migration
│   └── seeders/                           ← Seeders
├── resources/
│   └── views/
│       └── filament/
│           └── pages/
│               └── maintenance-calendar.blade.php ← View Kalender
└── USER-GUIDE.md                          ← Dokumen ini
```
