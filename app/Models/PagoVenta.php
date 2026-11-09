<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PagoVenta extends Model
{
    protected $table = 'pago_ventas';

    protected $fillable = [
        'venta_id', 'caja_id', 'fecha', 'forma_pago', 'tarjeta_id', 'monto', 'referencia', 'observacion',
    ];

    protected $casts = [
        'fecha' => 'date',
        'monto' => 'decimal:2',
    ];

    /** "Tarjeta · Visa", "Mercado Pago"… con la descripción de `formas_pago`. */
    public function getEtiquetaAttribute(): string
    {
        static $labels = null;
        $labels ??= FormaPago::pluck('descripcion', 'codigo')->all();
        $label = $labels[$this->forma_pago] ?? ucfirst(str_replace('_', ' ', $this->forma_pago));

        return $this->tarjeta_id && $this->tarjeta ? "{$label} · {$this->tarjeta->nombre}" : $label;
    }

    public function venta()
    {
        return $this->belongsTo(Venta::class);
    }

    public function tarjeta()
    {
        return $this->belongsTo(Tarjeta::class);
    }

    public function caja()
    {
        return $this->belongsTo(Caja::class);
    }
}
