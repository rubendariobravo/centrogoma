<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocAfip extends Model
{
    protected $table = 'doc_afip';

    protected $fillable = ['descripcion', 'tipo_cbte', 'letra', 'estado'];

    public function ventas()
    {
        return $this->hasMany(Venta::class);
    }
}
