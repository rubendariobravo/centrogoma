<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cotizacion extends Model
{
    protected $fillable = ['moneda', 'valor', 'activo', 'fecha'];

    protected $casts = [
        'valor'  => 'decimal:4',
        'activo' => 'boolean',
        'fecha'  => 'date',
    ];

    public static function getValorActivo(string $moneda = 'USD'): ?float
    {
        $cotizacion = static::where('moneda', $moneda)
            ->where('activo', true)
            ->orderByDesc('fecha')
            ->first();

        return $cotizacion?->valor;
    }
}
