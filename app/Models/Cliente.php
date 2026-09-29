<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class Cliente extends Model implements Auditable
{
    use SoftDeletes, \OwenIt\Auditing\Auditable;

    protected array $auditExclude = ['saldo'];

    protected $fillable = [
        'legacy_id', 'razon_social', 'nombre_fantasia', 'cuit', 'dni', 'tipoiva_id', 'empleado_id',
        'telefono', 'celular', 'email', 'domicilio', 'localidad', 'provincia',
        'codigo_postal', 'lista_precio_id', 'bonificacion', 'limite_credito', 'saldo',
        'porcentaje_iibb', 'percepcion_iva', 'es_mayorista', 'estado', 'observacion',
    ];

    protected $casts = [
        'bonificacion'    => 'decimal:2',
        'limite_credito'  => 'decimal:2',
        'saldo'           => 'decimal:2',
        'porcentaje_iibb' => 'decimal:4',
        'percepcion_iva'  => 'boolean',
        'es_mayorista'    => 'boolean',
    ];

    public function tipoIva()
    {
        return $this->belongsTo(TipoIva::class, 'tipoiva_id');
    }

    public function listaPrecio()
    {
        return $this->belongsTo(ListaPrecio::class, 'lista_precio_id');
    }

    /**
     * Devuelve la lista efectiva del cliente: la suya si tiene asignada,
     * sino la lista default del sistema.
     */
    public function listaPrecioEfectiva(): ?ListaPrecio
    {
        return $this->listaPrecio ?? ListaPrecio::default();
    }

    public function vendedor()
    {
        return $this->belongsTo(Empleado::class, 'empleado_id');
    }

    public function ventas()
    {
        return $this->hasMany(Venta::class);
    }

    public function recibos()
    {
        return $this->hasMany(Recibo::class);
    }

    public function cuentaCorriente()
    {
        return $this->hasMany(CcCliente::class);
    }

    public function precios()
    {
        return $this->hasMany(ClientePrecio::class);
    }

    public function esResponsableInscripto(): bool
    {
        return $this->tipoIva?->codigo_afip === '01';
    }
}
