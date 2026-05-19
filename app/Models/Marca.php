<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class Marca extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    protected $fillable = ['descripcion', 'comision', 'proveedor_id', 'estado'];

    protected $casts = [
        'comision' => 'decimal:4',
    ];

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function articulos()
    {
        return $this->hasMany(Articulo::class);
    }
}
