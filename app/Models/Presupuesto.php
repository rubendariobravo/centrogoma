<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class Presupuesto extends Model implements Auditable
{
    use SoftDeletes, \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'cliente_id', 'user_id', 'lista_precio_id', 'venta_id',
        'nro_presupuesto', 'fecha', 'fecha_vencimiento', 'dias_validez',
        'neto', 'neto_21', 'neto_10_5', 'monto_iva21', 'monto_iva10_5',
        'descuento', 'total', 'estado',
        'condicion_pago', 'plazo_entrega', 'observacion', 'legacy_id',
    ];

    protected $casts = [
        'fecha'            => 'date',
        'fecha_vencimiento'=> 'date',
        'neto'             => 'float',
        'neto_21'          => 'float',
        'neto_10_5'        => 'float',
        'monto_iva21'      => 'float',
        'monto_iva10_5'    => 'float',
        'descuento'        => 'float',
        'total'            => 'float',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function listaPrecio()
    {
        return $this->belongsTo(ListaPrecio::class, 'lista_precio_id');
    }

    public function venta()
    {
        return $this->belongsTo(Venta::class);
    }

    public function detalles()
    {
        return $this->hasMany(DetPresupuesto::class);
    }

    public function estaActivo(): bool
    {
        return $this->estado === 'A';
    }

    public function estaVencido(): bool
    {
        return $this->estado === 'V' ||
            ($this->estado === 'A' && $this->fecha_vencimiento && $this->fecha_vencimiento->isPast());
    }

    public function getLabelEstadoAttribute(): string
    {
        return match ($this->estado) {
            'A' => 'Activo',
            'V' => 'Vencido',
            'F' => 'Facturado',
            'N' => 'Anulado',
            default => $this->estado,
        };
    }
}
