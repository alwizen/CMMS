<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentSequence extends Model
{
    protected $fillable = [
        'prefix',
        'last_number',
    ];

    protected $casts = [
        'last_number' => 'integer',
    ];
}
