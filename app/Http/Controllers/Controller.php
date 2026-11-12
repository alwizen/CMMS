<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

abstract class Controller
{
    /**
     * Batasi parameter per_page agar tidak membebani server.
     */
    protected function perPage(Request $request, int $default = 15, int $max = 100): int
    {
        $value = (int) $request->get('per_page', $default);

        return max(1, min($value, $max));
    }
}
