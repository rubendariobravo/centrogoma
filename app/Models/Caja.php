<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Contracts\Auditable;

class Caja extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;

    protected array $auditExclude = [
        'total_ventas', 'total_cobros', 'total_pagos', 'total_gastos', 'total_anticipos',
        'total_transferencias_entrada', 'total_transferencias_salida',
        'total_ajustes_ingreso', 'total_ajustes_egreso',
        'total_cheques_entrada', 'total_cheques_salida',
    ];

    protected $fillable = [
        'user_id', 'nombre', 'tipo', 'fecha_apertura', 'hora_apertura', 'fecha_cierre', 'hora_cierre',
        'saldo_inicial', 'total_ventas', 'total_cobros', 'total_pagos', 'total_gastos', 'total_anticipos',
        'total_ajustes_ingreso', 'total_ajustes_egreso',
        'total_transferencias_entrada', 'total_transferencias_salida',
        'total_cheques_entrada', 'total_cheques_salida', 'saldo_final',
        'estado', 'observacion',
    ];

    protected $casts = [
        'fecha_apertura' => 'date',
        'fecha_cierre'   => 'date',
        'hora_apertura'  => 'datetime',
        'hora_cierre'    => 'datetime',
        'saldo_inicial'  => 'decimal:2',
        'total_ventas'   => 'decimal:2',
        'total_cobros'   => 'decimal:2',
        'total_pagos'    => 'decimal:2',
        'total_gastos'   => 'decimal:2',
        'total_anticipos' => 'decimal:2',
        'total_ajustes_ingreso' => 'decimal:2',
        'total_ajustes_egreso'  => 'decimal:2',
        'total_transferencias_entrada' => 'decimal:2',
        'total_transferencias_salida'  => 'decimal:2',
        'total_cheques_entrada' => 'decimal:2',
        'total_cheques_salida'  => 'decimal:2',
        'saldo_final'    => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function ventas()
    {
        return $this->hasMany(Venta::class);
    }

    public function recibos()
    {
        return $this->hasMany(Recibo::class);
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(MovimientoCaja::class);
    }

    public function gastos(): HasMany
    {
        return $this->hasMany(Gasto::class);
    }

    public function estaAbierta(): bool
    {
        return $this->estado === 'abierta';
    }

    /**
     * Saldo esperado en la caja según los contadores cacheados.
     * Egresos (pagos a proveedores, gastos, transferencias salientes) restan;
     * ingresos (ventas, cobros, transferencias entrantes) suman.
     */
    public function getSaldoEsperadoAttribute(): float
    {
        return (float) $this->saldo_inicial
            + (float) $this->total_ventas
            + (float) $this->total_cobros
            + (float) $this->total_transferencias_entrada
            + (float) $this->total_ajustes_ingreso
            + (float) $this->total_cheques_entrada
            - (float) $this->total_pagos
            - (float) $this->total_gastos
            - (float) $this->total_anticipos
            - (float) $this->total_transferencias_salida
            - (float) $this->total_ajustes_egreso
            - (float) $this->total_cheques_salida;
    }

    /** Cache en memoria del resumen del libro de movimientos. */
    private ?array $resumenCache = null;

    /**
     * Resumen del libro de movimientos discriminado por forma de pago.
     * Una sola pasada sobre `movimiento_cajas`. Distingue el efectivo físico
     * (forma_pago='efectivo' + transferencias entre cajas) del resto de los
     * medios (transferencia bancaria, tarjeta, cheque, cuenta corriente).
     *
     * Nota: una venta/cobro con forma_pago='transferencia' es transferencia
     * BANCARIA (no es efectivo). Una transferencia ENTRE cajas tiene
     * concepto='transferencia' y mueve efectivo físico → cuenta como efectivo.
     *
     * @return array{ventas_por_medio: array, ingresos_por_medio: array, egresos_por_medio: array, cobrado_sin_cc: float, efectivo_ingreso: float, efectivo_egreso: float, saldo_efectivo: float, otros_medios: float}
     */
    public function resumenMovimientos(): array
    {
        if ($this->resumenCache !== null) {
            return $this->resumenCache;
        }

        $movs = $this->movimientos()->get(['tipo', 'concepto', 'monto', 'forma_pago']);

        $ventasPorMedio    = [];
        $ingresosPorMedio  = [];
        $egresosPorMedio   = [];
        $cobradoSinCC      = 0.0;
        $efectivoIngreso   = 0.0;
        $efectivoEgreso    = 0.0;

        foreach ($movs as $m) {
            $monto      = (float) $m->monto;
            $esTransfer = $m->concepto === 'transferencia';
            $esEfectivo = $m->forma_pago === 'efectivo' || $esTransfer;
            $medio      = $m->forma_pago ?: ($esTransfer ? 'transferencia_caja' : 'otro');

            if ($m->tipo === 'ingreso') {
                $ingresosPorMedio[$medio] = ($ingresosPorMedio[$medio] ?? 0) + $monto;
                if ($esEfectivo) {
                    $efectivoIngreso += $monto;
                }
                if ($m->concepto === 'venta') {
                    $k = $m->forma_pago ?: 'otro';
                    $ventasPorMedio[$k] = ($ventasPorMedio[$k] ?? 0) + $monto;
                }
                // Cobrado real del día: ventas y cobros por cualquier medio salvo cuenta corriente.
                if (in_array($m->concepto, ['venta', 'cobro'], true) && $m->forma_pago !== 'cuenta_corriente') {
                    $cobradoSinCC += $monto;
                }
            } else {
                $egresosPorMedio[$medio] = ($egresosPorMedio[$medio] ?? 0) + $monto;
                if ($esEfectivo) {
                    $efectivoEgreso += $monto;
                }
            }
        }

        $saldoEfectivo = (float) $this->saldo_inicial + $efectivoIngreso - $efectivoEgreso;

        return $this->resumenCache = [
            'ventas_por_medio'   => $ventasPorMedio,
            'ingresos_por_medio' => $ingresosPorMedio,
            'egresos_por_medio'  => $egresosPorMedio,
            'cobrado_sin_cc'     => round($cobradoSinCC, 2),
            'efectivo_ingreso'   => round($efectivoIngreso, 2),
            'efectivo_egreso'    => round($efectivoEgreso, 2),
            'saldo_efectivo'     => round($saldoEfectivo, 2),
            // Lo que entró por medios NO efectivos reales (banco/tarjeta/cheque),
            // informativo — excluye cuenta corriente (no es plata) y las
            // transferencias entre cajas (ya cuentan como efectivo).
            'otros_medios'       => round(
                (float) ($ingresosPorMedio['transferencia'] ?? 0)
                + (float) ($ingresosPorMedio['tarjeta'] ?? 0)
                + (float) ($ingresosPorMedio['cheque'] ?? 0),
                2
            ),
        ];
    }

    /**
     * Saldo que debería haber EN EFECTIVO en la caja (arqueo real).
     * No mezcla transferencias bancarias, tarjeta ni cuenta corriente.
     */
    public function getSaldoEfectivoEsperadoAttribute(): float
    {
        return $this->resumenMovimientos()['saldo_efectivo'];
    }

    /** Total realmente cobrado en el día (cualquier medio salvo cuenta corriente). */
    public function getTotalCobradoSinCcAttribute(): float
    {
        return $this->resumenMovimientos()['cobrado_sin_cc'];
    }

    /** Desglose de las ventas por forma de pago (efectivo, transferencia, cuenta_corriente, …). */
    public function ventasPorMedio(): array
    {
        return $this->resumenMovimientos()['ventas_por_medio'];
    }

    /**
     * Saldo disponible para transferir desde esta caja.
     * En una caja diaria solo se puede mover el efectivo físico; en cajas
     * generales/bancos todo el saldo es movible (no hay distinción de efectivo).
     */
    public function getSaldoTransferibleAttribute(): float
    {
        return $this->tipo === 'diaria'
            ? $this->saldo_efectivo_esperado
            : (float) $this->saldo_esperado;
    }

    public function nombreEtiqueta(): string
    {
        if ($this->nombre) {
            return $this->nombre;
        }

        return match ($this->tipo) {
            'general' => 'Caja General',
            'banco'   => 'Banco',
            default   => 'Caja ' . ($this->user?->name ?? '—'),
        };
    }
}
