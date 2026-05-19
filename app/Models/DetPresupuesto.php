<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetPresupuesto extends Model
{
    protected $table = 'det_presupuestos';

    protected $fillable = [
        'presupuesto_id', 'articulo_id', 'descripcion',
        'cantidad', 'precio_unitario', 'alicuota_iva',
        'descuento', 'subtotal', 'subtotal_iva',
    ];

    protected $casts = [
        'cantidad'       => 'float',
        'precio_unitario'=> 'float',
        'alicuota_iva'   => 'decimal:2',
        'descuento'      => 'float',
        'subtotal'       => 'float',
        'subtotal_iva'   => 'float',
    ];

    public function presupuesto()
    {
        return $this->belongsTo(Presupuesto::class);
    }

    public function articulo()
    {
        return $this->belongsTo(Articulo::class);
    }
}
