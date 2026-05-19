<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetProduccion extends Model
{
    protected $table = 'det_produccion';

    protected $fillable = [
        'produccion_id', 'articulo_id', 'tipo', 'cantidad',
        'precio_costo', 'subtotal', 'rendimiento', 'orden',
    ];

    protected $casts = [
        'cantidad'     => 'float',
        'precio_costo' => 'float',
        'subtotal'     => 'float',
        'rendimiento'  => 'float',
    ];

    public function produccion()
    {
        return $this->belongsTo(Produccion::class);
    }

    public function articulo()
    {
        return $this->belongsTo(Articulo::class);
    }
}
