<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class Cheque extends Model implements Auditable
{
    use SoftDeletes, \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'tipo', 'banco', 'nro_cheque', 'titular', 'cuit_titular',
        'fecha_emision', 'fecha_cobro', 'monto', 'estado',
        'cliente_id', 'proveedor_id', 'referencia', 'observacion', 'legacy_id',
    ];

    protected $casts = [
        'fecha_emision' => 'date',
        'fecha_cobro'   => 'date',
        'monto'         => 'float',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function getLabelEstadoAttribute(): string
    {
        return match ($this->estado) {
            'en_cartera' => 'En cartera',
            'depositado' => 'Depositado',
            'cobrado'    => 'Cobrado',
            'rechazado'  => 'Rechazado',
            'endosado'   => 'Endosado',
            'anulado'    => 'Anulado',
            default      => $this->estado,
        };
    }

    public function getLabelTipoAttribute(): string
    {
        return $this->tipo === 'recibido' ? 'Recibido' : 'Emitido';
    }
}
