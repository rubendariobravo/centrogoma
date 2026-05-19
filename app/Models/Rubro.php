<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class Rubro extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    protected $fillable = ['descripcion', 'alicuota_iva', 'es_mayorista', 'estado'];

    protected $casts = [
        'alicuota_iva' => 'decimal:2',
        'es_mayorista'  => 'boolean',
    ];

    public function articulos()
    {
        return $this->hasMany(Articulo::class);
    }
}
