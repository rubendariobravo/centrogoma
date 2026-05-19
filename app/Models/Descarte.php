<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class Descarte extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    protected $fillable = [
        'empleado_id', 'user_id', 'articulo_id', 'fecha',
        'cantidad', 'motivo', 'observacion',
    ];

    protected $casts = [
        'fecha'    => 'date',
        'cantidad' => 'float',
    ];

    public function empleado()
    {
        return $this->belongsTo(Empleado::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function articulo()
    {
        return $this->belongsTo(Articulo::class);
    }
}
