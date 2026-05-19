<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetalleOrdenPago extends Model
{
    protected $table = 'detalle_ordenes_pago';

    protected $fillable = ['orden_pago_id', 'compra_id', 'monto'];

    protected $casts = [
        'monto' => 'float',
    ];

    public function ordenPago()
    {
        return $this->belongsTo(OrdenPago::class);
    }

    public function compra()
    {
        return $this->belongsTo(Compra::class);
    }
}
