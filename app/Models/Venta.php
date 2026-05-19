<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class Venta extends Model implements Auditable
{
    use SoftDeletes, \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'cliente_id', 'user_id', 'empleado_id', 'caja_id', 'doc_afip_id',
        'nro_venta', 'fecha', 'hora',
        'neto', 'descuento', 'neto_21', 'neto_10_5', 'monto_iva21', 'monto_iva10_5',
        'total', 'saldo', 'percepcion', 'percepiva21', 'percepiva10_5', 'porcpercep',
        'nro_factura', 'letra_comprobante', 'tipo_cbte', 'pto_vta', 'cae', 'vto_cae',
        'estado', 'tipo', 'moneda', 'cotizacion', 'sucursal',
        'cliente_vtadiaria', 'dni_vtadiaria', 'domicilio_vtadiaria',
        'tipo_comprobante', 'factura_original_id', 'observacion', 'totalanulado',
    ];

    protected $casts = [
        'fecha'    => 'date',
        'neto'     => 'float',
        'descuento'=> 'float',
        'neto_21'  => 'float',
        'neto_10_5'=> 'float',
        'monto_iva21'   => 'float',
        'monto_iva10_5' => 'float',
        'total'         => 'float',
        'saldo'         => 'decimal:2',
        'percepcion'    => 'float',
        'percepiva21'   => 'float',
        'percepiva10_5' => 'float',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function empleado()
    {
        return $this->belongsTo(Empleado::class);
    }

    public function caja()
    {
        return $this->belongsTo(Caja::class);
    }

    public function docAfip()
    {
        return $this->belongsTo(DocAfip::class);
    }

    public function detalles()
    {
        return $this->hasMany(DetVenta::class);
    }

    public function pagos()
    {
        return $this->hasMany(PagoVenta::class);
    }

    public function facturaOriginal()
    {
        return $this->belongsTo(Venta::class, 'factura_original_id');
    }

    public function notasCredito()
    {
        return $this->hasMany(Venta::class, 'factura_original_id');
    }

    public function estaFacturada(): bool
    {
        return !empty($this->cae);
    }

    public function estaAnulada(): bool
    {
        return $this->estado === 'A';
    }
}
