<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'geofence',
        'center_lat',
        'center_lng',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'geofence' => 'array',
        'center_lat' => 'float',
        'center_lng' => 'float',
    ];

    public function areas(): HasMany
    {
        return $this->hasMany(Area::class);
    }
}
