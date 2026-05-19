<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class Compra extends Model implements Auditable
{
    use SoftDeletes, \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'proveedor_id', 'user_id', 'caja_id', 'fecha', 'nro_factura', 'letra',
        'neto', 'neto_21', 'neto_10_5', 'monto_iva21', 'monto_iva10_5',
        'total', 'saldo', 'estado', 'observacion',
    ];

    protected $casts = [
        'fecha'        => 'date',
        'neto'         => 'float',
        'neto_21'      => 'float',
        'neto_10_5'    => 'float',
        'monto_iva21'  => 'float',
        'monto_iva10_5'=> 'float',
        'total'        => 'float',
        'saldo'        => 'decimal:2',
    ];

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function caja()
    {
        return $this->belongsTo(Caja::class);
    }

    public function detalles()
    {
        return $this->hasMany(DetCompra::class);
    }
}
