<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ChequeMovimiento extends Model
{
    protected $table = 'cheque_movimientos';

    protected $fillable = [
        'cheque_id', 'fecha', 'tipo_mov', 'estado_resultante', 'monto',
        'referencia_type', 'referencia_id', 'user_id', 'observacion',
    ];

    protected $casts = [
        'fecha' => 'date',
        'monto' => 'decimal:2',
    ];

    public function cheque(): BelongsTo
    {
        return $this->belongsTo(Cheque::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function referencia(): MorphTo
    {
        return $this->morphTo();
    }

    /** Etiqueta legible del tipo de movimiento. */
    public function getLabelTipoMovAttribute(): string
    {
        return match ($this->tipo_mov) {
            'recepcion'    => 'Recepción',
            'emision'      => 'Emisión',
            'deposito'     => 'Depósito',
            'acreditacion' => 'Acreditación',
            'debito'       => 'Débito',
            'rechazo'      => 'Rechazo',
            'endoso'       => 'Endoso',
            'venta'        => 'Venta',
            'anulacion'    => 'Anulación',
            'edicion'      => 'Edición',
            default        => ucfirst($this->tipo_mov),
        };
    }
}
