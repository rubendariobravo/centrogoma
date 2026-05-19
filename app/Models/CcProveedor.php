<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CcProveedor extends Model
{
    protected $table = 'cc_proveedores';

    protected $fillable = [
        'proveedor_id', 'fecha', 'concepto', 'debe', 'haber', 'saldo',
        'referencia_type', 'referencia_id',
    ];

    protected $casts = [
        'fecha' => 'date',
        'debe'  => 'decimal:2',
        'haber' => 'decimal:2',
        'saldo' => 'decimal:2',
    ];

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function referencia()
    {
        return $this->morphTo('referencia');
    }
}
