<?php

namespace App\Support;

class Geo
{
    /**
     * Perkiraan luas polygon (m²) memakai pendekatan equirectangular.
     *
     * @param  array<int, array{0: float|int, 1: float|int}>|null  $polygon
     *                                                                       Daftar titik [lat, lng].
     */
    public static function polygonArea(?array $polygon): float
    {
        if (! is_array($polygon) || count($polygon) < 3) {
            return 0.0;
        }

        $earthRadius = 6378137.0;

        $lat0 = 0.0;
        foreach ($polygon as $point) {
            $lat0 += deg2rad((float) $point[0]);
        }
        $lat0 /= count($polygon);

        $total = 0.0;
        $count = count($polygon);

        for ($i = 0; $i < $count; $i++) {
            $p1 = $polygon[$i];
            $p2 = $polygon[($i + 1) % $count];

            $x1 = deg2rad((float) $p1[1]) * cos($lat0) * $earthRadius;
            $y1 = deg2rad((float) $p1[0]) * $earthRadius;
            $x2 = deg2rad((float) $p2[1]) * cos($lat0) * $earthRadius;
            $y2 = deg2rad((float) $p2[0]) * $earthRadius;

            $total += ($x1 * $y2) - ($x2 * $y1);
        }

        return round(abs($total) / 2, 2);
    }

    /**
     * Titik tengah (centroid) sederhana dari polygon.
     *
     * @param  array<int, array{0: float|int, 1: float|int}>|null  $polygon
     * @return array{lat: float, lng: float}|null
     */
    public static function polygonCenter(?array $polygon): ?array
    {
        if (! is_array($polygon) || $polygon === []) {
            return null;
        }

        $latSum = 0.0;
        $lngSum = 0.0;
        $count = count($polygon);

        foreach ($polygon as $point) {
            $latSum += (float) $point[0];
            $lngSum += (float) $point[1];
        }

        return [
            'lat' => round($latSum / $count, 7),
            'lng' => round($lngSum / $count, 7),
        ];
    }
}
