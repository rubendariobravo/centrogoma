<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CcCliente extends Model
{
    protected $table = 'cc_clientes';

    protected $fillable = [
        'cliente_id', 'fecha', 'concepto', 'debe', 'haber', 'saldo',
        'referencia_type', 'referencia_id',
    ];

    protected $casts = [
        'fecha' => 'date',
        'debe'  => 'decimal:2',
        'haber' => 'decimal:2',
        'saldo' => 'decimal:2',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function referencia()
    {
        return $this->morphTo('referencia');
    }
}
