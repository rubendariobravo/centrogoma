<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class OrdenPago extends Model implements Auditable
{
    use SoftDeletes, \OwenIt\Auditing\Auditable;

    protected $table = 'ordenes_pago';

    protected $fillable = [
        'proveedor_id', 'caja_id', 'user_id', 'nro_orden', 'fecha',
        'total', 'forma_pago', 'referencia', 'observacion', 'estado',
    ];

    protected $casts = [
        'fecha' => 'date',
        'total' => 'float',
    ];

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function caja()
    {
        return $this->belongsTo(Caja::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function detalles()
    {
        return $this->hasMany(DetalleOrdenPago::class);
    }
}
