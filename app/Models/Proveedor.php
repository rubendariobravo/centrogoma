<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class Proveedor extends Model implements Auditable
{
    use SoftDeletes, \OwenIt\Auditing\Auditable;

    protected array $auditExclude = ['saldo'];

    protected $table = 'proveedores';

    protected $fillable = [
        'legacy_id', 'razon_social', 'nombre_fantasia', 'cuit', 'telefono', 'celular',
        'email', 'domicilio', 'localidad', 'provincia', 'codigo_postal',
        'contacto', 'saldo', 'estado', 'observacion',
    ];

    protected $casts = [
        'saldo' => 'decimal:2',
    ];

    public function marcas()
    {
        return $this->hasMany(Marca::class);
    }

    public function compras()
    {
        return $this->hasMany(Compra::class);
    }

    public function cuentaCorriente()
    {
        return $this->hasMany(CcProveedor::class);
    }
}
