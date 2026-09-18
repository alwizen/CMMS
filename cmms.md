# CMMS — Database Design
## Pondasi #1: Company, Area, Equipment

Dokumen ini berisi desain database awal CMMS yang sudah disepakati.

---

## 1. Relasi Utama

Struktur hierarki:

```text
Company / Site
    │
    └── Area
          │
          └── Equipment
```

Relasi database:

```text
companies (1)
    │
    └── areas (N)
            │
            └── equipment (N)
```

### Contoh struktur FT Tegal

```text
FT Tegal
│
├── Loading
│   ├── P-101       Pump
│   ├── FM-101      Flow Meter
│   └── MOV-101     Motor Operated Valve
│
├── Unloading
│   ├── P-201       Unloading Pump
│   └── FM-201      Flow Meter
│
├── Storage
│   ├── TK-01       Storage Tank
│   ├── TK-02       Storage Tank
│   └── LT-01       Level Transmitter
│
└── MCC
    ├── MCC-01      Main MCC Panel
    └── MCC-02      Pump MCC Panel
```

---

# 2. Table: `companies`

Mewakili site atau terminal dalam CMMS.

Contoh: FT Tegal, FT Maos, FT Rewulu.

| Field | Type | Key | Nullable | Default | Keterangan |
|---|---|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK | No | Auto | Primary key |
| `code` | VARCHAR(20) | UNIQUE | No | - | Kode site/company, contoh `FT-TGL` |
| `name` | VARCHAR(100) | - | No | - | Nama site, contoh `FT Tegal` |
| `description` | TEXT | - | Yes | NULL | Keterangan |
| `is_active` | BOOLEAN | - | No | TRUE | Status aktif |
| `created_at` | TIMESTAMP | - | Yes | - | Laravel timestamp |
| `updated_at` | TIMESTAMP | - | Yes | - | Laravel timestamp |

### Constraint

```text
UNIQUE(code)
```

---

# 3. Table: `areas`

Mewakili area fisik/bagian di dalam suatu company/site.

Contoh:

- Loading
- Unloading
- Storage
- MCC
- Utilities

| Field | Type | Key | Nullable | Default | Keterangan |
|---|---|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK | No | Auto | Primary key |
| `company_id` | BIGINT UNSIGNED | FK | No | - | Relasi ke `companies.id` |
| `code` | VARCHAR(20) | - | No | - | Kode area |
| `name` | VARCHAR(100) | - | No | - | Nama area |
| `description` | TEXT | - | Yes | NULL | Keterangan |
| `is_active` | BOOLEAN | - | No | TRUE | Status aktif |
| `created_at` | TIMESTAMP | - | Yes | - | Laravel timestamp |
| `updated_at` | TIMESTAMP | - | Yes | - | Laravel timestamp |

### Foreign Key

```text
areas.company_id
        ↓
companies.id
```

### Constraint

```text
UNIQUE(company_id, code)
```

Artinya kode area harus unik di dalam satu company.

Contoh:

```text
FT-TGL → LOAD
FT-MAO → LOAD
```

Diperbolehkan.

Tetapi:

```text
FT-TGL → LOAD
FT-TGL → LOAD
```

Tidak diperbolehkan.

### Delete behavior

```text
companies → areas
```

Gunakan `RESTRICT ON DELETE`.

Company tidak boleh dihapus jika masih mempunyai area.

---

# 4. Table: `equipment`

Mewakili equipment/aset yang dikelola dan dirawat oleh CMMS.

Equipment selalu berada di dalam satu area.

| Field | Type | Key | Nullable | Default | Keterangan |
|---|---|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK | No | Auto | Primary key |
| `area_id` | BIGINT UNSIGNED | FK | No | - | Relasi ke `areas.id` |
| `tag_number` | VARCHAR(50) | UNIQUE | No | - | Tag equipment, contoh `P-101` |
| `name` | VARCHAR(150) | - | No | - | Nama equipment |
| `description` | TEXT | - | Yes | NULL | Deskripsi |
| `equipment_type` | VARCHAR(100) | - | Yes | NULL | Jenis equipment |
| `manufacturer` | VARCHAR(100) | - | Yes | NULL | Manufacturer |
| `model` | VARCHAR(100) | - | Yes | NULL | Model |
| `serial_number` | VARCHAR(100) | - | Yes | NULL | Serial number |
| `installation_date` | DATE | - | Yes | NULL | Tanggal instalasi |
| `operational_unit` | VARCHAR(20) | - | Yes | NULL | Satuan operasional |
| `photo` | VARCHAR(255) | - | Yes | NULL | Path foto equipment |
| `status` | VARCHAR(30) | - | No | `active` | Status equipment |
| `criticality` | VARCHAR(20) | - | No | `medium` | Tingkat kritikal equipment |
| `created_at` | TIMESTAMP | - | Yes | - | Laravel timestamp |
| `updated_at` | TIMESTAMP | - | Yes | - | Laravel timestamp |

### Foreign Key

```text
equipment.area_id
        ↓
areas.id
```

### Delete behavior

Gunakan:

```text
RESTRICT ON DELETE
```

Equipment tidak boleh ikut terhapus secara cascade ketika area dihapus.

---

# 5. Operational Unit

`operational_unit` digunakan untuk menentukan satuan operasi/meter equipment.

Contoh:

| Equipment | Operational Unit |
|---|---|
| Pompa | `hour` |
| Generator | `hour` |
| Kendaraan | `km` |
| Equipment dengan inspeksi harian | `daily` |

Contoh:

```text
P-101
operational_unit = hour
```

Kemudian nantinya bisa digunakan untuk Preventive Maintenance berbasis meter:

```text
PM P-101
Every 1,000 hour
```

atau:

```text
Vehicle
operational_unit = km

PM
Every 10,000 km
```

Catatan: `operational_unit` tidak otomatis berarti metode PM. Detail Preventive Maintenance akan dibuat pada modul berikutnya.

---

# 6. Equipment Photo

Field:

```text
photo VARCHAR(255)
```

Database hanya menyimpan path file.

Contoh:

```text
equipment/photos/P-101.jpg
```

File fisiknya dikelola oleh Laravel Storage.

---

# 7. Equipment Status

Untuk tahap awal menggunakan string:

```text
active
inactive
retired
```

Contoh:

```text
active
```

Equipment yang sudah memiliki histori maintenance sebaiknya tidak dihapus secara permanen. Jika sudah tidak digunakan, status dapat diubah menjadi:

```text
retired
```

Dengan demikian histori maintenance tetap tersedia.

---

# 8. Equipment Criticality

`criticality` menunjukkan seberapa besar dampak kegagalan equipment terhadap operasi.

Ini berbeda dengan priority Work Order.

### Level awal

```text
low
medium
high
critical
```

Contoh:

| Criticality | Gambaran |
|---|---|
| `low` | Kerusakan tidak banyak mengganggu operasi utama |
| `medium` | Kerusakan mengganggu sebagian aktivitas |
| `high` | Kerusakan berdampak signifikan terhadap operasi |
| `critical` | Kegagalan dapat menghentikan proses utama atau berdampak sangat besar |

Contoh:

```text
P-101
criticality = critical
```

bukan berarti semua WO untuk P-101 otomatis menjadi emergency.

Konsepnya dipisahkan:

```text
Equipment Criticality
    ↓
Seberapa kritis equipment tersebut

WO Priority
    ↓
Seberapa segera pekerjaan harus dilakukan
```

WO Priority akan dibahas pada modul Work Order.

---

# 9. Laravel Migration

## `create_companies_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();

            $table->string('code', 20)->unique();
            $table->string('name', 100);
            $table->text('description')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
```

---

## `create_areas_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('areas', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->constrained('companies')
                ->restrictOnDelete();

            $table->string('code', 20);
            $table->string('name', 100);
            $table->text('description')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique(['company_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('areas');
    }
};
```

---

## `create_equipment_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipment', function (Blueprint $table) {
            $table->id();

            $table->foreignId('area_id')
                ->constrained('areas')
                ->restrictOnDelete();

            $table->string('tag_number', 50)->unique();
            $table->string('name', 150);
            $table->text('description')->nullable();

            $table->string('equipment_type', 100)->nullable();

            $table->string('manufacturer', 100)->nullable();
            $table->string('model', 100)->nullable();
            $table->string('serial_number', 100)->nullable();

            $table->date('installation_date')->nullable();

            $table->string('operational_unit', 20)->nullable();

            $table->string('photo')->nullable();

            $table->string('status', 30)->default('active');
            $table->string('criticality', 20)->default('medium');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment');
    }
};
```

---

# 10. Database Summary

```text
┌─────────────────────┐
│      companies      │
├─────────────────────┤
│ id                  │
│ code                │
│ name                │
│ description         │
│ is_active           │
│ created_at          │
│ updated_at          │
└──────────┬──────────┘
           │ 1:N
           ▼
┌─────────────────────┐
│        areas        │
├─────────────────────┤
│ id                  │
│ company_id FK       │
│ code                │
│ name                │
│ description         │
│ is_active           │
│ created_at          │
│ updated_at          │
└──────────┬──────────┘
           │ 1:N
           ▼
┌─────────────────────┐
│      equipment      │
├─────────────────────┤
│ id                  │
│ area_id FK          │
│ tag_number          │
│ name                │
│ description         │
│ equipment_type      │
│ manufacturer        │
│ model               │
│ serial_number       │
│ installation_date   │
│ operational_unit    │
│ photo               │
│ status              │
│ criticality         │
│ created_at          │
│ updated_at          │
└─────────────────────┘
```

---

## Status Pondasi #1

**FINAL**

- [x] Company / Site
- [x] Area
- [x] Equipment
- [x] Equipment operational unit
- [x] Equipment photo
- [x] Equipment status
- [x] Equipment criticality
- [x] Foreign key & relationship
- [x] Delete behavior

> Modul berikutnya dapat dibangun di atas `equipment` tanpa mengubah struktur dasar ini.
