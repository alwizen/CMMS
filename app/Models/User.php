<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'employee_number', 'phone', 'fungsi', 'status'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    /**
     * Role yang boleh membuka panel admin (supervisor, pengawas, super admin).
     *
     * @var list<string>
     */
    public const ADMIN_PANEL_ROLES = [
        'super_admin',
        'spv_mos',
        'spv_gos',
        'spv_hsse',
        'pengawas',
    ];

    /**
     * Panel admin untuk supervisor/pengawas, panel app untuk petugas lapangan.
     * Super admin boleh membuka keduanya untuk keperluan support.
     *
     * Akses panel app mengikuti permission Shield (`View:FieldDashboard`),
     * sehingga role yang diberi izin tersebut otomatis bisa masuk.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        if (! $this->status) {
            return false;
        }

        return match ($panel->getId()) {
            'admin' => $this->hasAnyRole(self::ADMIN_PANEL_ROLES),
            'app' => $this->can('View:FieldDashboard'),
            default => false,
        };
    }

    /**
     * Daftar fungsi yang dipakai pada penugasan maintenance/housekeeping.
     *
     * @var array<string, string>
     */
    public const FUNGSI = [
        'MOS LTSA' => 'MOS LTSA',
        'MOS PM Reborn' => 'MOS PM Reborn',
        'MOS TAD' => 'MOS TAD',
        'HSSE' => 'HSSE',
    ];

    /**
     * Nilai fungsi yang valid.
     *
     * @return array<int, string>
     */
    public static function fungsiValues(): array
    {
        return array_keys(self::FUNGSI);
    }

    /**
     * Fungsi yang punya user aktif dengan role tertentu (default: role `mos`).
     *
     * Dipakai saat approval request: WO ditugaskan ke fungsi (shared pool),
     * bukan ke satu orang. Hanya fungsi yang punya petugas `mos` yang muncul.
     *
     * @return array<string, string>
     */
    public static function fungsiOptionsForRole(string $role = 'mos'): array
    {
        $fungsi = static::query()
            ->where('status', true)
            ->whereNotNull('fungsi')
            ->whereHas('roles', fn ($query) => $query->where('name', $role))
            ->distinct()
            ->pluck('fungsi')
            ->all();

        return array_intersect_key(self::FUNGSI, array_flip($fungsi));
    }

    /**
     * User aktif dengan fungsi tertentu (dipakai sebagai koordinator WO).
     *
     * @return array<int, string>
     */
    public static function optionsByFungsi(string $fungsi): array
    {
        return static::query()
            ->where('status', true)
            ->where('fungsi', $fungsi)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    /**
     * Role yang tidak eligible sebagai Technician Coordinator maintenance.
     *
     * @var list<string>
     */
    public const EXCLUDED_COORDINATOR_ROLES = [
        'patra-jasa',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'status' => 'boolean',
        ];
    }

    /**
     * Pilihan user untuk Technician Coordinator maintenance.
     * Hanya user aktif dan tidak memiliki role yang dikecualikan (mis. patra-jasa).
     *
     * @return array<int, string>
     */
    public static function technicianCoordinatorOptions(): array
    {
        return static::query()
            ->where('status', true)
            ->whereDoesntHave('roles', fn ($query) => $query->whereIn('name', static::EXCLUDED_COORDINATOR_ROLES))
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    public function maintenanceRequestsReported(): HasMany
    {
        return $this->hasMany(MaintenanceRequest::class, 'reported_by');
    }

    public function workOrdersIssued(): HasMany
    {
        return $this->hasMany(WorkOrder::class, 'issued_by');
    }

    public function workOrdersCoordinated(): HasMany
    {
        return $this->hasMany(WorkOrder::class, 'technician_coordinator_id');
    }

    public function workOrderWorkers(): HasMany
    {
        return $this->hasMany(WorkOrderWorker::class);
    }

    public function housekeepingPlansCreated(): HasMany
    {
        return $this->hasMany(HousekeepingPlan::class, 'created_by');
    }

    public function housekeepingAreasSupervised(): HasMany
    {
        return $this->hasMany(HousekeepingArea::class, 'supervisor_id');
    }
}
