<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReciboDetalle extends Model
{
    protected $table = 'recibo_detalles';

    protected $fillable = ['recibo_id', 'venta_id', 'monto_aplicado'];

    protected $casts = [
        'monto_aplicado' => 'float',
    ];

    public function recibo()
    {
        return $this->belongsTo(Recibo::class);
    }

    public function venta()
    {
        return $this->belongsTo(Venta::class);
    }
}
