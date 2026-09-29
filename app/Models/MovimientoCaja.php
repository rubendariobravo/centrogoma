<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class MovimientoCaja extends Model
{
    protected $table = 'movimiento_cajas';

    protected $fillable = [
        'caja_id', 'fecha', 'tipo', 'concepto', 'monto', 'forma_pago',
        'referencia_type', 'referencia_id', 'user_id',
        'categoria_gasto_id', 'cuenta_contable_id', 'descripcion',
    ];

    protected $casts = [
        'fecha' => 'date',
        'monto' => 'decimal:2',
    ];

    public function caja(): BelongsTo
    {
        return $this->belongsTo(Caja::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function categoriaGasto(): BelongsTo
    {
        return $this->belongsTo(CategoriaGasto::class);
    }

    public function referencia(): MorphTo
    {
        return $this->morphTo();
    }

    public function esIngreso(): bool
    {
        return $this->tipo === 'ingreso';
    }
}
