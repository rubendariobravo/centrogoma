<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class Pesada extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    protected $fillable = [
        'user_id', 'articulo_id', 'nro_pesada', 'fecha',
        'stock_sistema', 'stock_real', 'diferencia', 'merma_porcentaje', 'estado', 'observacion',
    ];

    protected $casts = [
        'fecha'            => 'date',
        'stock_sistema'    => 'float',
        'stock_real'       => 'float',
        'diferencia'       => 'float',
        'merma_porcentaje' => 'decimal:3',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function articulo()
    {
        return $this->belongsTo(Articulo::class);
    }
}
