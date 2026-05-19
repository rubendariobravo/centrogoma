<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetCompra extends Model
{
    protected $table = 'det_compras';

    protected $fillable = [
        'compra_id', 'articulo_id', 'cantidad', 'precio_unitario',
        'alicuota_iva', 'subtotal', 'subtotal_iva',
    ];

    protected $casts = [
        'cantidad'       => 'float',
        'precio_unitario'=> 'float',
        'alicuota_iva'   => 'decimal:2',
        'subtotal'       => 'float',
        'subtotal_iva'   => 'float',
    ];

    public function compra()
    {
        return $this->belongsTo(Compra::class);
    }

    public function articulo()
    {
        return $this->belongsTo(Articulo::class);
    }
}
