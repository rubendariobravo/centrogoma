<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MovimientoArticulo extends Model
{
    protected $table = 'movimientos_articulos';

    protected $fillable = [
        'articulo_id', 'user_id', 'fecha', 'tipo', 'cantidad',
        'stock_anterior', 'stock_posterior', 'referencia_type', 'referencia_id',
        'es_minorista', 'observacion',
    ];

    protected $casts = [
        'fecha'           => 'date',
        'cantidad'        => 'float',
        'stock_anterior'  => 'float',
        'stock_posterior' => 'float',
        'es_minorista'    => 'boolean',
    ];

    public function articulo()
    {
        return $this->belongsTo(Articulo::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function referencia()
    {
        return $this->morphTo('referencia');
    }
}
