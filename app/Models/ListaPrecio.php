<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class ListaPrecio extends Model implements Auditable
{
    use SoftDeletes, \OwenIt\Auditing\Auditable;

    protected $table = 'listas_precios';

    protected $fillable = [
        'codigo', 'nombre', 'descripcion', 'tipo', 'es_default',
        'recargo_no_efectivo', 'descuento_efectivo', 'orden', 'activo',
    ];

    protected $casts = [
        'es_default'          => 'boolean',
        'activo'              => 'boolean',
        'recargo_no_efectivo' => 'decimal:2',
        'descuento_efectivo'  => 'decimal:2',
    ];

    public function articuloPrecios()
    {
        return $this->hasMany(ArticuloPrecio::class);
    }

    public function clientes()
    {
        return $this->hasMany(Cliente::class);
    }

    public function clientePreciosOverride()
    {
        return $this->hasMany(ClientePrecio::class);
    }

    /**
     * Devuelve la lista marcada como default. Util como fallback cuando un
     * cliente no tiene lista asignada.
     */
    public static function default(): ?self
    {
        return static::where('es_default', true)->where('activo', true)->first();
    }
}
