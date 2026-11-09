<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/** Marca de tarjeta (Visa, Naranja, Visa Débito…) de un cobro con forma de pago `tarjeta`. */
class Tarjeta extends Model
{
    protected $table = 'tarjetas';

    protected $fillable = ['nombre', 'tipo', 'activo', 'orden'];

    protected $casts = ['activo' => 'boolean'];

    /** [{id, nombre}] de las marcas activas, en el orden de uso. */
    public static function activasParaSelect(): Collection
    {
        return static::where('activo', true)->orderBy('orden')->orderBy('nombre')->get(['id', 'nombre']);
    }

    public static function idsActivos(): array
    {
        return static::where('activo', true)->pluck('id')->all();
    }
}
