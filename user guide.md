# User Guide — Rumat

Panduan penggunaan aplikasi dari awal (membuat perusahaan) sampai akhir (riwayat perawatan).

Login di `/admin`.

---

## Alur Singkat

```
Company → Area → Equipment Type → Equipment
  → Maintenance Checklist (Activity)
  → Maintenance Plan (+ checklist) → Schedule → WO otomatis
  → Maintenance Request (temuan) → Approve → WO
  → Kerjakan WO → Completed → Maintenance History
```

---

## Master Data

### 1. Company
**Fungsi:** induk seluruh lokasi. Satu company berisi banyak area.
**Menu:** Master Data → Company

- **Code** (unik), **Name**, **Description**, **Active**.
- **Geofence Location:** klik peta untuk titik pusat. Isi Center Latitude/Longitude bila perlu.
- Tombol **Lihat Area** membuka daftar area company tersebut.

### 2. Company Area
**Fungsi:** lokasi fisik di dalam company (mis. Loading, Pump Area).
**Menu:** Master Data → Company Area

- **Company**, **Name** (Code terisi otomatis, bisa diubah), **Description**, **Active**.
- **Location:** klik peta untuk menandai titik area.
- Code unik per company.

### 3. Equipment Type
**Fungsi:** jenis aset (Pompa, Valve, Tank). Dipakai oleh Equipment dan Checklist.
**Menu:** Master Data → Equipment Type

- **Name** (Code terisi otomatis), **Description**, **Active**.

### 4. Equipment
**Fungsi:** aset nyata yang dirawat, identifikasi dengan Tag Number.
**Menu:** Master Data → Equipment

- **General Information:** Area, Equipment Type, **Tag Number** (unik), Description.
- **Technical Details:** Manufacturer, Model, Serial Number, Installation Date, Operational Unit.
- **Status & Media:** Status, **Criticality**, Photo.
- Di detail equipment, tab **Maintenance History** menampilkan riwayat aset tersebut.

### 5. Maintenance Checklist (Activity)
**Fungsi:** master item pekerjaan per tipe equipment. Dipakai sebagai isi checklist plan dan WO.
**Menu:** Maintenance Checklist → Activities

- **Equipment Type**, **Name**, **Type** (Maintenance, Inspection, Testing, Visual Check, Measurement, Lubrication, Cleaning, Calibration, Replacement).
- **Maintenance Classification** (Preventive, Corrective, Predictive, Condition-Based), **Interval** (opsional).
- **Answer Type:** Qualitative (teks/ya-tidak) atau Quantitative (angka).
- Untuk Quantitative: **Optimum**, **Minimum**, **Maximum**, **Unit**.
- **Reference** dan **Active**. Activity nonaktif tidak muncul saat menyusun checklist.

---

## Perawatan Berencana (Preventif)

### 6. Maintenance Plan
**Fungsi:** aturan perawatan berulang untuk satu equipment (mis. bulanan).
**Menu:** Maintenance → Maintenance Plans (Preventive)

- **Equipment Type** lalu **Equipment (Tag Number)**.
- **Classification**, **Interval** (Daily, Weekly, Monthly, Quarterly, Yearly), **Status** (Active untuk berjalan).
- **Start Date**, **End Date** (kosongkan bila tanpa batas).
- **Technician Coordinator**, **Description**.
- Nomor plan terbentuk otomatis (`MP-YYYYMM-####`).

**Tab Checklist Activities** (di dalam plan):
- **Create** → pilih Activity → Order (berurutan, bisa di-drag) → Save.
- Checklist ini disalin ke setiap WO dari plan.

### 7. Maintenance Schedule
**Fungsi:** tanggal-tanggal konkret hasil dari plan.
**Menu:** Maintenance → Maintenance Schedule

- Dibuat otomatis tiap malam 01:00 dari interval plan. Sabtu dan Minggu dilewati.
- Bisa juga dibuat manual: pilih **Maintenance Plan**, isi **Scheduled Date** dan **Status**.
- Status: Scheduled, In Progress, Completed, Delayed, Rescheduled, Cancelled.

### 8. Work Order Otomatis
**Fungsi:** membuat WO dari jadwal tanpa input manual.
**Diatur di:** Pengaturan → Site Setting → *Work Order Otomatis*

| Pengaturan | Fungsi | Default |
|---|---|---|
| Buat Work Order Otomatis | Nyalakan/matikan | Aktif |
| Buat WO untuk N Hari ke Depan | 1–5 hari kerja ke depan | 3 |
| Jam Mulai WO | Jam mulai di WO | 08:00 |
| Lewati Sabtu & Minggu | WO digeser ke Senin | Aktif |
| Batas Backfill (hari) | Jadwal terlewat dalam batas ini tetap dibuat WO | 14 |

- WO otomatis berstatus **In Progress**, membawa checklist dari plan.
- Jadwal terlewat dalam batas backfill dijadwal ulang dan berstatus **Delayed**.
- Satu jadwal = satu WO.

---

## Perawatan Temuan (Korektif)

### 9. Maintenance Request
**Fungsi:** tiket temuan kerusakan dari lapangan (mis. pompa bocor).
**Menu:** Maintenance → Maintenance Requests

**Membuat request:**
- **Equipment Type** lalu **Equipment (Tag Number)**.
- **Operation Status** (Running, Stopped, Standby, Faulty).
- **Damage Date**, **Damage Time**, **Description**, **Equipment Condition**.
- **Impact**, **Early Action**.
- Nomor terbentuk otomatis (`MR-YYYYMMDD-####`).

**Approve (SPV):** pada request berstatus Open, klik **Approve**:
- **Start Date**, **Penanggung Jawab** (wajib, 1 orang), **Classification** (default Corrective), **Catatan Approval**.
- **Aktivitas Pemeriksaan** — checklist yang akan dikerjakan teknisi. Otomatis terisi semua aktivitas aktif untuk tipe equipment tersebut. Bisa dikurangi, ditambah, atau dikosongkan.
- Hasil: request menjadi **Approved**, WO dibuat berstatus **Pending**, terhubung ke request, dan checklist tersalin ke WO.

**Reject:** klik **Reject**, isi alasan (wajib). Request menjadi **Rejected**, tidak ada WO.

**Status request berjalan otomatis** mengikuti Work Order — tidak perlu diubah manual:

| Kejadian | Status request |
|---|---|
| WO dibuat dari request yang masih Open | `Assigned` |
| WO mulai dikerjakan / ditunda | `In Progress` |
| Semua WO dari request selesai (minimal satu Completed) | `Completed` |
| Semua WO dibatalkan | tetap `Approved` (perlu keputusan manual) |

Request yang sudah `Rejected` atau `Completed` tidak akan berubah lagi.

### 10. Work Order
**Fungsi:** surat perintah kerja untuk dikerjakan di lapangan.
**Menu:** Maintenance → Work Order

- Nomor terbentuk otomatis (`WO-YYYYMMDD-####`).
- Isi **Equipment**, **Classification**, **Status**, **Start At**, **Finish At**, **Note**.
- **Checklist & Hasil Eksekusi:** per item isi Pre Inspection, Final Result, Follow Up, centang **Executed**, dan unggah **Foto Dokumentasi** (opsional, maks. 3).
- **Teknisi Pelaksana:** tambahkan teknisi dan perannya.
- Status: Pending → In Progress → Completed (atau On Hold / Cancelled).
- Saat **Completed**, sistem membuat Maintenance History otomatis.

---

## Riwayat & Monitoring

### 11. Maintenance History
**Fungsi:** arsip hasil perawatan yang sudah selesai, dasar audit.
**Menu:** Riwayat & Monitoring → Maintenance History

- Dibuat otomatis dari WO yang Completed. Tidak diisi manual.
- Kolom: WO Number, Tag Number, Equipment, Type, Classification, Maintenance Date, Performed By, Status.
- Tombol **Cetak PDF** di halaman ini mencetak riwayat. Bisa difilter dulu berdasarkan tanggal, equipment, dan area.

### 12. Meter Log
**Fungsi:** catat pembacaan meter per equipment (jam kerja, nilai operasi). Dasar perawatan berbasis kondisi.
**Menu:** Riwayat & Monitoring → Meter Log

- **Equipment**, **Reading Date**, **Value**, **Recorded By**.

---

## Monitoring

### 13. Dashboard
**Fungsi:** ringkasan kondisi hari ini.
**Menu:** Dashboard

- Statistik (total equipment, WO, WO selesai, request aktif, jadwal terlambat).
- Peta area, jadwal jatuh tempo (7 hari), WO per klasifikasi dan status, tren 6 bulan, WO terbaru.
- Tombol **Filter** untuk periode.

### 14. Kalender Maintenance
**Fungsi:** melihat WO, plan, dan jadwal dalam kalender.
**Menu:** Kalender Maintenance

- Warna menunjukkan status. Klik item untuk detail.

---

## Pengaturan & Akses

### 15. Site Setting
**Fungsi:** atur format nomor dan aturan WO otomatis.
**Menu:** Pengaturan → Site Setting

- **Format Penomoran:** WO, Plan, Schedule, Request. Token: `{YYYY}`, `{YY}`, `{MM}`, `{MONTH}`, `{DD}`, `{DATE}`, `{SEQ}` (mis. `{SEQ:6}`).
- **Work Order Otomatis:** lihat bagian 8.

### 16. Pengguna & Peran
**Fungsi:** akun pengguna dan hak akses.
**Menu:** User & Permission → Pengguna / Peran

- Hanya admin yang mengatur bagian ini.

---

## Urutan Input Pertama Kali

1. Company
2. Company Area
3. Equipment Type
4. Equipment
5. Maintenance Checklist
6. Maintenance Plan + checklist
7. Tunggu WO otomatis (atau cek `php artisan maintenance:generate-work-orders --dry-run`)
8. Maintenance Request bila ada temuan
9. Kerjakan WO sampai Completed
10. Cek di Maintenance History

---

## Aplikasi Android (CM-MS)

Aplikasi Android memakai API yang sama, dan **data otomatis terfilter sesuai akun** yang login:

| Role | Cakupan data |
|---|---|
| super_admin, pengawas, spv_mos | Semua jadwal dan work order |
| ltsa_tad | Hanya yang terkait dirinya (koordinator, pemberi tugas, atau anggota tim) |

Navigasi Android memakai **menu samping (drawer)**. Ketuk ikon ☰ di kiri atas layar untuk membuka menu.

Menu yang tersedia:

1. **Dashboard** — terdiri dari:
   - **Ringkasan** (kartu hijau): total work order Anda, progress penyelesaian, dan persentase selesai.
   - **Statistik ringkas**: Berjalan, Pending, dan Terlambat (WO yang tanggal mulainya sudah lewat dan belum selesai).
   - **Agenda Hari Ini**: WO yang dijadwalkan hari ini, lengkap dengan jam mulai.
   - **Work Order Terbaru**: 5 WO terakhir milik Anda.

   Semua kartu WO bisa diketuk untuk membuka detail. Data hanya mencakup WO yang terkait akun Anda (koordinator, pemberi tugas, atau pelaksana).
2. **Riwayat** — daftar work order.
3. **Riwayat** — daftar work order. Ketuk salah satu untuk membuka detail dan mengerjakannya.
4. **Kalender** — kalender maintenance bulanan:
   - Titik kuning = jadwal, titik biru = work order.
   - Ketuk tanggal untuk melihat agenda harinya.
   - Tombol panah untuk pindah bulan, "Hari ini" untuk kembali ke bulan berjalan.
5. **Equipment** — daftar seluruh aset:
   - Cari berdasarkan tag number, deskripsi, atau tipe equipment.
   - Ketuk salah satu untuk melihat detail (tipe, area, manufacturer, model, serial, tanggal instalasi, status, criticality).
   - Di halaman detail, lihat riwayat **Meter Log** aset tersebut dan catat pembacaan baru dengan tombol **Catat**.
6. **Meter Log** — daftar seluruh pembacaan meter:
   - Cari berdasarkan tag atau deskripsi equipment.
   - Ketuk **Catat Meter** (tombol bawah), pilih equipment, isi tanggal (format `YYYY-MM-DD`), dan nilai meter (gunakan titik untuk desimal, mis. `1250.5`).
7. **Request** — daftar, detail, dan pembuatan maintenance request.
8. **Saya** — profil dan logout.

Tombol **Logout** juga tersedia di bagian bawah menu samping.

### Mengerjakan Work Order dari Android

1. Buka **Riwayat** (atau ketuk kartu WO di Dashboard).
2. Di halaman detail, gunakan **Aksi Pekerjaan**:
   - **Mulai Pekerjaan** — status berubah menjadi In Progress dan waktu mulai tercatat.
   - **Tandai Selesai** — status menjadi Completed dan waktu selesai tercatat.
   - **Tunda Pekerjaan** / **Lanjutkan Pekerjaan** — untuk On Hold.
3. Pada tiap **Aktivitas**, ketuk **Isi Hasil Inspeksi** (atau **Edit Hasil** bila sudah diisi), lalu isi:
   - **Pre Inspection** — kondisi sebelum dikerjakan (maks. 100 karakter).
   - **Final Result** — hasil akhir (maks. 100 karakter).
   - **Follow Up** — tindak lanjut (maks. 100 karakter).
   - **Catatan** — keterangan tambahan.
   - Aktifkan **Tandai sudah dieksekusi** bila item ini selesai.
4. **Foto Dokumentasi (opsional)** — maksimal 3 foto per item:
   - Ketuk **Tambah Foto**, lalu pilih **Kamera** atau **Galeri**.
   - Ketuk thumbnail untuk melihat foto ukuran penuh.
   - Ketuk tanda **×** pada thumbnail untuk menghapus foto.
   - Sisa slot ditampilkan pada tombol, mis. "Tambah Foto (2 tersisa)".
   - Foto tersimpan langsung ke server saat diunggah; foto yang sudah ada bisa dihapus kapan saja.
5. Ketuk **Simpan**. Data langsung tersimpan di server.

Hanya user yang terkait WO tersebut (koordinator, pemberi tugas, atau anggota tim) yang dapat mengubah status dan mengisi hasil. Selain itu akan muncul pesan "Anda tidak berhak mengubah aktivitas ini".

Alamat server diatur di halaman login (kolom Base URL). Gunakan `http://IP-SERVER:8000/api/`.
