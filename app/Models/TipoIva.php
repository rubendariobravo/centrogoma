<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoIva extends Model
{
    protected $table = 'tipoiva';

    protected $fillable = ['descripcion', 'codigo_afip', 'estado'];

    public function clientes()
    {
        return $this->hasMany(Cliente::class);
    }
}
