<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubArticulo extends Model
{
    protected $table = 'sub_articulos';

    protected $fillable = [
        'articulo_padre_id', 'articulo_hijo_id', 'rendimiento', 'descripcion', 'activo',
    ];

    protected $casts = [
        'rendimiento' => 'decimal:4',
        'activo'      => 'boolean',
    ];

    public function articuloPadre()
    {
        return $this->belongsTo(Articulo::class, 'articulo_padre_id');
    }

    public function articuloHijo()
    {
        return $this->belongsTo(Articulo::class, 'articulo_hijo_id');
    }
}
