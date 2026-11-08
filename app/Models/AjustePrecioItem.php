<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AjustePrecioItem extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'ajuste_precio_id', 'articulo_precio_id', 'articulo_id',
        'precio_anterior', 'precio_nuevo', 'costo_anterior', 'costo_nuevo',
    ];
}
