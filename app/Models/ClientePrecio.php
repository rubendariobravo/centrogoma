<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientePrecio extends Model
{
    protected $table = 'cliente_precios';

    protected $fillable = [
        'cliente_id', 'articulo_id', 'rubro_id', 'lista_precio_id',
        'precio_especial', 'bonificacion', 'activo',
    ];

    protected $casts = [
        'precio_especial' => 'decimal:2',
        'bonificacion'    => 'decimal:2',
        'activo'          => 'boolean',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function articulo()
    {
        return $this->belongsTo(Articulo::class);
    }

    public function rubro()
    {
        return $this->belongsTo(Rubro::class);
    }

    public function listaPrecio()
    {
        return $this->belongsTo(ListaPrecio::class, 'lista_precio_id');
    }
}
