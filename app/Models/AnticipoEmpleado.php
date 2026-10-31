<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class AnticipoEmpleado extends Model implements Auditable
{
    use SoftDeletes, \OwenIt\Auditing\Auditable;

    protected $table = 'anticipo_empleados';

    protected $fillable = [
        'empleado_id', 'caja_id', 'fecha', 'monto', 'forma_pago',
        'referencia', 'observaciones', 'estado',
        'aprobado_por', 'fecha_aprobacion', 'motivo_rechazo',
    ];

    protected $casts = [
        'fecha'            => 'date',
        'fecha_aprobacion' => 'date',
        'monto'            => 'decimal:2',
    ];

    public function empleado()
    {
        return $this->belongsTo(Empleado::class);
    }

    public function caja()
    {
        return $this->belongsTo(Caja::class);
    }

    /** Líneas de pago (un anticipo puede pagarse con varios medios / varias cajas). */
    public function pagos()
    {
        return $this->hasMany(AnticipoPago::class, 'anticipo_empleado_id');
    }

    public function aprobador()
    {
        return $this->belongsTo(User::class, 'aprobado_por');
    }

    /** True si el anticipo se pagó con más de un medio. */
    public function esMixto(): bool
    {
        return $this->pagos->count() > 1;
    }

    /**
     * Etiqueta de la forma de pago para listados: "Mixto (2)" cuando hay varias
     * líneas, la descripción del medio único en caso contrario.
     */
    public function formaPagoEtiqueta(): string
    {
        $pagos = $this->pagos;

        if ($pagos->count() > 1) {
            return 'Mixto (' . $pagos->count() . ')';
        }

        return ucfirst($pagos->first()->forma_pago ?? $this->forma_pago);
    }
}
