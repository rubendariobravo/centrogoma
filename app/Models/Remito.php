<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class Remito extends Model implements Auditable
{
    use SoftDeletes, \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'empleado_id', 'user_id', 'nro_remito', 'fecha',
        'estado', 'total_peso', 'total_cajas', 'observacion',
    ];

    protected $casts = [
        'fecha'       => 'date',
        'total_peso'  => 'decimal:3',
        'total_cajas' => 'decimal:2',
    ];

    public function empleado()
    {
        return $this->belongsTo(Empleado::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
