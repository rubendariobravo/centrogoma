<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class FormaPago extends Model
{
    protected $table = 'formas_pago';

    protected $fillable = [
        'codigo',
        'descripcion',
        'es_efectivo',
        'es_cuenta_corriente',
        'costo_financiero',
        'activo',
        'orden',
    ];

    protected $casts = [
        'es_efectivo'         => 'boolean',
        'es_cuenta_corriente' => 'boolean',
        'costo_financiero'    => 'float',
        'activo'              => 'boolean',
    ];

    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('activo', true)->orderBy('orden');
    }

    /** Devuelve colección [{codigo, descripcion}] de las formas activas. */
    public static function activosParaSelect(): \Illuminate\Support\Collection
    {
        return static::activos()->get(['codigo', 'descripcion']);
    }

    /** Códigos válidos de formas activas, para reglas de validación. */
    public static function codigosActivos(): array
    {
        return static::where('activo', true)->pluck('codigo')->toArray();
    }

    /** True si el codigo corresponde a una forma marcada como efectivo. */
    public static function esEfectivo(string $codigo): bool
    {
        return static::where('codigo', $codigo)->value('es_efectivo') ?? false;
    }

    /** True si el codigo corresponde a cuenta corriente. */
    public static function esCuentaCorriente(string $codigo): bool
    {
        return static::where('codigo', $codigo)->value('es_cuenta_corriente') ?? false;
    }

    /** Código del primer método activo marcado como cuenta corriente, o null. */
    public static function codigoCuentaCorriente(): ?string
    {
        return static::where('activo', true)->where('es_cuenta_corriente', true)->value('codigo');
    }

    /** Código del primer método activo marcado como efectivo, o 'efectivo' como fallback. */
    public static function codigoEfectivo(): string
    {
        return static::where('activo', true)->where('es_efectivo', true)->value('codigo') ?? 'efectivo';
    }
}
