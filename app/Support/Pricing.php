<?php

namespace App\Support;

/**
 * Conversion entre precios neto (sin IVA) y precio final (con IVA).
 *
 * Convencion del sistema: la base de datos almacena SIEMPRE neto. La capa de
 * presentacion convierte a final para mostrar/editar en pantallas de tramos
 * (Listas\Precios, Articulos\Form) y overrides (Clientes\Form). El motor
 * de precios sigue trabajando en neto y agrega IVA al cierre del pipeline.
 */
class Pricing
{
    /** Convierte un precio neto (sin IVA) a precio final con IVA. */
    public static function final(float $neto, float $iva): float
    {
        return round($neto * (1 + $iva / 100), 2);
    }

    /**
     * Convierte un precio final (con IVA) a precio neto sin IVA. Con 4 decimales, para que
     * final(neto(x)) devuelva x: con 2, $15.000 quedaba en 12.396,69 → $14.999,99.
     */
    public static function neto(float $final, float $iva): float
    {
        if ($iva <= 0) {
            return round($final, 2);
        }
        return round($final / (1 + $iva / 100), 4);
    }
}
