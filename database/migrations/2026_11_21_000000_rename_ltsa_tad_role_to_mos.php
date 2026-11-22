<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;

/**
 * Role lapangan `ltsa_tad` diganti menjadi `mos`.
 *
 * HSSE tetap memakai role yang sama dan dibedakan lewat kolom `users.fungsi`.
 */
return new class extends Migration
{
    public function up(): void
    {
        $legacy = Role::query()->where('name', 'ltsa_tad')->first();
        $target = Role::query()->where('name', 'mos')->first();

        if ($legacy === null) {
            if ($target === null) {
                Role::findOrCreate('mos', 'web');
            }

            return;
        }

        if ($target === null) {
            $legacy->forceFill(['name' => 'mos'])->save();

            return;
        }

        // Kedua role sudah ada: pindahkan assignment lalu hapus role lama.
        foreach ($legacy->users as $user) {
            $user->assignRole($target);
        }

        $legacy->delete();
    }

    public function down(): void
    {
        $mos = Role::query()->where('name', 'mos')->first();
        $legacy = Role::query()->where('name', 'ltsa_tad')->first();

        if ($mos === null) {
            return;
        }

        if ($legacy === null) {
            $mos->forceFill(['name' => 'ltsa_tad'])->save();

            return;
        }

        foreach ($mos->users as $user) {
            $user->assignRole($legacy);
        }

        $mos->delete();
    }
};
