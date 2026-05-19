<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class Caja extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;

    protected array $auditExclude = ['total_ventas', 'total_cobros', 'total_gastos'];
    protected $fillable = [
        'user_id', 'fecha_apertura', 'hora_apertura', 'fecha_cierre', 'hora_cierre',
        'saldo_inicial', 'total_ventas', 'total_cobros', 'total_gastos', 'saldo_final',
        'estado', 'observacion',
    ];

    protected $casts = [
        'fecha_apertura' => 'date',
        'fecha_cierre'   => 'date',
        'hora_apertura'  => 'datetime',
        'hora_cierre'    => 'datetime',
        'saldo_inicial'  => 'decimal:2',
        'total_ventas'   => 'decimal:2',
        'total_cobros'   => 'decimal:2',
        'total_gastos'   => 'decimal:2',
        'saldo_final'    => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function ventas()
    {
        return $this->hasMany(Venta::class);
    }

    public function recibos()
    {
        return $this->hasMany(Recibo::class);
    }

    public function estaAbierta(): bool
    {
        return $this->estado === 'abierta';
    }
}
