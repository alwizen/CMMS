<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EquipmentType extends Model
{
    protected $fillable = [
        'code',
        'name',
        'fungsi',
        'responsible_user_id',
        'description',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    /**
     * Fungsi penanggung jawab tipe equipment (MOS LTSA, MOS PM Reborn, MOS TAD, HSSE).
     * Menggantikan penanggung jawab per user.
     */
    public function fungsiUsers(): HasMany
    {
        return $this->hasMany(User::class, 'fungsi', 'fungsi');
    }

    public function responsibleUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    /**
     * Seluruh penanggung jawab equipment type (bisa lebih dari satu).
     */
    public function responsibleUsers(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'equipment_type_responsible_users',
            'equipment_type_id',
            'user_id',
        )->withTimestamps();
    }

    /**
     * Samakan kolom lama `responsible_user_id` dengan penanggung jawab pertama
     * pada pivot, agar generator WO & integrasi lama tetap berjalan.
     */
    public function syncPrimaryResponsibleUser(): void
    {
        $primaryId = $this->responsibleUsers()->orderBy('users.id')->value('users.id');

        if ($this->responsible_user_id !== $primaryId) {
            $this->forceFill(['responsible_user_id' => $primaryId])->saveQuietly();
        }
    }

    /**
     * Pindahkan penanggung jawab tunggal lama ke pivot (bila pivot masih kosong).
     */
    public function syncResponsibleUsersFromPrimary(): void
    {
        if ($this->responsible_user_id && $this->responsibleUsers()->doesntExist()) {
            $this->responsibleUsers()->syncWithoutDetaching([$this->responsible_user_id]);
        }

        $this->syncPrimaryResponsibleUser();
    }

    /**
     * Penanggung jawab yang dipakai sebagai koordinator WO.
     */
    public function primaryResponsibleUserId(): ?int
    {
        return $this->responsible_user_id
            ?? $this->responsibleUsers()->orderBy('users.id')->value('users.id');
    }

    public function equipment(): HasMany
    {
        return $this->hasMany(Equipment::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }
}
