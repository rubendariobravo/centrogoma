<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetVenta extends Model
{
    protected $table = 'det_ventas';

    protected $fillable = [
        'venta_id', 'articulo_id', 'cantidad', 'precio_unitario',
        'alicuota_iva', 'descuento', 'subtotal', 'subtotal_iva',
        'tipo_venta', 'descripcion',
        // Trazabilidad del calculo de precio
        'lista_precio_id', 'tramo_aplicado_id', 'regla_aplicada',
        'precio_motor', 'precio_manual',
    ];

    protected $casts = [
        'cantidad'       => 'float',
        'precio_unitario'=> 'float',
        'alicuota_iva'   => 'decimal:2',
        'descuento'      => 'float',
        'subtotal'       => 'float',
        'subtotal_iva'   => 'float',
        'precio_motor'   => 'float',
        'precio_manual'  => 'boolean',
    ];

    public function listaPrecio()
    {
        return $this->belongsTo(ListaPrecio::class, 'lista_precio_id');
    }

    public function venta()
    {
        return $this->belongsTo(Venta::class);
    }

    public function articulo()
    {
        return $this->belongsTo(Articulo::class);
    }

    public function getSubtotalConIvaAttribute(): float
    {
        return round($this->subtotal * (1 + ($this->alicuota_iva / 100)), 2);
    }
}
