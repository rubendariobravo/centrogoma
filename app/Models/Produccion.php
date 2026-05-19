<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class Produccion extends Model implements Auditable
{
    use SoftDeletes, \OwenIt\Auditing\Auditable;

    protected $table = 'producciones';

    protected $fillable = [
        'empleado_id', 'user_id', 'nro_produccion', 'fecha',
        'tipo_produccion', 'estado', 'observacion',
        'costo_total', 'costo_promedio', 'ganancia',
    ];

    protected $casts = [
        'fecha'          => 'date',
        'costo_total'    => 'float',
        'costo_promedio' => 'float',
        'ganancia'       => 'float',
    ];

    public function empleado()
    {
        return $this->belongsTo(Empleado::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function detalles()
    {
        return $this->hasMany(DetProduccion::class)->orderBy('orden')->orderBy('id');
    }

    public function insumos()
    {
        return $this->hasMany(DetProduccion::class)->where('tipo', 'E')->orderBy('orden');
    }

    public function productos()
    {
        return $this->hasMany(DetProduccion::class)->where('tipo', 'I')->orderBy('orden');
    }

    // Acceso conveniente al artículo de entrada (primer insumo) — compatible con la UI de un solo insumo
    public function getArticuloEntradaAttribute(): ?Articulo
    {
        return $this->insumos->first()?->articulo;
    }

    public function getTotalEntradaAttribute(): float
    {
        return $this->insumos->sum('cantidad');
    }

    public function getTotalSalidaAttribute(): float
    {
        return $this->productos->sum('cantidad');
    }

    public function getMermaAttribute(): float
    {
        return max(0, $this->total_entrada - $this->total_salida);
    }

    public function getMermaPorcentajeAttribute(): float
    {
        return $this->total_entrada > 0
            ? round(($this->merma / $this->total_entrada) * 100, 3)
            : 0;
    }
}
