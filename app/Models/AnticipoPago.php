<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Línea de pago de un anticipo. Un anticipo puede pagarse con varios medios
 * (mitad efectivo, mitad transferencia) y cada línea tiene su propia caja.
 */
class AnticipoPago extends Model
{
    protected $table = 'anticipo_pagos';

    protected $fillable = [
        'anticipo_empleado_id', 'caja_id', 'forma_pago', 'monto', 'referencia',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
    ];

    public function anticipo()
    {
        return $this->belongsTo(AnticipoEmpleado::class, 'anticipo_empleado_id');
    }

    public function caja()
    {
        return $this->belongsTo(Caja::class);
    }
}
