<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PagoVenta extends Model
{
    protected $table = 'pago_ventas';

    protected $fillable = [
        'venta_id', 'caja_id', 'fecha', 'forma_pago', 'monto', 'referencia', 'observacion',
    ];

    protected $casts = [
        'fecha' => 'date',
        'monto' => 'decimal:2',
    ];

    public function venta()
    {
        return $this->belongsTo(Venta::class);
    }

    public function caja()
    {
        return $this->belongsTo(Caja::class);
    }
}
