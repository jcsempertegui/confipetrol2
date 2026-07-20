<?php

namespace App\Support;

final class Quantity
{
    /**
     * Presenta cantidades sin ceros decimales innecesarios, conservando
     * hasta tres decimales cuando el dato realmente los utiliza.
     */
    public static function format(int|float|string|null $value, int $precision = 3): string
    {
        $number = is_numeric($value) ? (float) $value : 0.0;
        $rounded = round($number, $precision);

        if (abs($rounded) < 0.5 * (10 ** -$precision)) {
            $rounded = 0.0;
        }

        return rtrim(rtrim(number_format($rounded, $precision, '.', ','), '0'), '.');
    }
}
