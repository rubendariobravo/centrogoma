<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class Cheque extends Model implements Auditable
{
    use SoftDeletes;
    use \OwenIt\Auditing\Auditable;

    protected $table = 'cheques';

    protected $fillable = [
        'tipo', 'estado', 'banco_id', 'serie', 'nro_cheque', 'sucursal',
        'es_diferido', 'es_echeq', 'librador_nombre', 'librador_cuit',
        'fecha_emision', 'fecha_recepcion', 'fecha_pago', 'monto',
        'cliente_id', 'proveedor_id', 'caja_id', 'caja_origen_id',
        'recibo_id', 'venta_id', 'orden_pago_id',
        'fecha_venta', 'monto_venta', 'comprador',
        'user_id', 'observacion',
    ];

    protected $casts = [
        'es_diferido'     => 'boolean',
        'es_echeq'        => 'boolean',
        'fecha_emision'   => 'date',
        'fecha_recepcion' => 'date',
        'fecha_pago'      => 'date',
        'fecha_venta'     => 'date',
        'monto'           => 'decimal:2',
        'monto_venta'     => 'decimal:2',
    ];

    /**
     * Transiciones de estado permitidas, por naturaleza del cheque.
     * Es la fuente de verdad que usa ChequeService para validar.
     */
    public const TRANSICIONES = [
        'tercero' => [
            'cartera'    => ['depositado', 'aplicado', 'vendido', 'anulado'],
            'depositado' => ['acreditado', 'rechazado'],
            'rechazado'  => ['cartera', 'anulado'], // recuperar un rechazado a cartera
        ],
        'propio' => [
            'emitido'   => ['debitado', 'rechazado', 'anulado'],
            'rechazado' => ['emitido', 'anulado'],
        ],
    ];

    public const ESTADO_LABELS = [
        'cartera'    => 'En cartera',
        'depositado' => 'Depositado',
        'acreditado' => 'Acreditado',
        'rechazado'  => 'Rechazado',
        'aplicado'   => 'Endosado',
        'vendido'    => 'Vendido',
        'anulado'    => 'Anulado',
        'emitido'    => 'Emitido',
        'debitado'   => 'Debitado',
    ];

    public const TIPO_LABELS = [
        'tercero' => 'De tercero',
        'propio'  => 'Propio',
    ];

    public function banco(): BelongsTo
    {
        return $this->belongsTo(Banco::class);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function caja(): BelongsTo
    {
        return $this->belongsTo(Caja::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(ChequeMovimiento::class)->orderByDesc('fecha')->orderByDesc('id');
    }

    public function scopeTerceros(Builder $q): Builder
    {
        return $q->where('tipo', 'tercero');
    }

    public function scopePropios(Builder $q): Builder
    {
        return $q->where('tipo', 'propio');
    }

    /** True si el cheque puede pasar al estado indicado. */
    public function puedeTransicionarA(string $nuevoEstado): bool
    {
        return in_array($nuevoEstado, self::TRANSICIONES[$this->tipo][$this->estado] ?? [], true);
    }

    /** Estado terminal: no admite más transiciones útiles. */
    public function isTerminal(): bool
    {
        return in_array($this->estado, ['acreditado', 'debitado', 'anulado', 'vendido', 'aplicado'], true);
    }

    public function getEstadoLabelAttribute(): string
    {
        return self::ESTADO_LABELS[$this->estado] ?? ucfirst($this->estado);
    }

    public function getTipoLabelAttribute(): string
    {
        return self::TIPO_LABELS[$this->tipo] ?? ucfirst($this->tipo);
    }

    /** Estado de vencimiento respecto de la fecha de pago: vencido | proximo | normal | null. */
    public function getEstadoVencimientoAttribute(): ?string
    {
        if (! $this->fecha_pago || in_array($this->estado, ['acreditado', 'debitado', 'anulado', 'vendido', 'aplicado'], true)) {
            return null;
        }

        $dias = now()->startOfDay()->diffInDays($this->fecha_pago, false);

        return match (true) {
            $dias < 0  => 'vencido',
            $dias <= 7 => 'proximo',
            default    => 'normal',
        };
    }
}
