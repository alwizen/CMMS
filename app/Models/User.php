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
     * Semua user aktif boleh membuka panel Filament.
     * Hak akses per menu/resource tetap diatur lewat permission Shield.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return (bool) $this->status;
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
