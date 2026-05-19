<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArticuloPrecio extends Model
{
    protected $table = 'articulo_precios';

    protected $fillable = [
        'articulo_id', 'lista_precio_id', 'cantidad_desde', 'cantidad_hasta',
        'precio', 'unidad_tramo', 'activo', 'orden',
    ];

    protected $casts = [
        'cantidad_desde' => 'decimal:3',
        'cantidad_hasta' => 'decimal:3',
        'precio'         => 'decimal:2',
        'activo'         => 'boolean',
    ];

    public function articulo()
    {
        return $this->belongsTo(Articulo::class);
    }

    public function listaPrecio()
    {
        return $this->belongsTo(ListaPrecio::class, 'lista_precio_id');
    }
}
