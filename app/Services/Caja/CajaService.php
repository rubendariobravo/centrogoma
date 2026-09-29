<?php

namespace App\Services\Caja;

use App\Models\Caja;
use App\Models\MovimientoCaja;
use App\Models\TransferenciaCaja;
use Illuminate\Support\Facades\DB;

/**
 * Punto único de escritura del libro de movimientos de caja.
 *
 * Cada hecho económico que afecta una caja se registra como un MovimientoCaja
 * (ingreso/egreso) y, en paralelo, actualiza el contador cacheado correspondiente
 * en la tabla `cajas` para mantener compatibilidad con las vistas existentes.
 *
 * El campo `cuenta_contable_id` queda preparado para un futuro plan de cuentas
 * contable: cuando exista, cada movimiento podrá mapearse a su cuenta y derivar
 * el libro diario por partida doble.
 */
class CajaService
{
    /**
     * Registra un movimiento en una caja y actualiza su contador cacheado.
     *
     * @param  string  $tipo      'ingreso' | 'egreso'
     * @param  string  $concepto  venta|cobro|pago_proveedor|gasto|transferencia|apertura|ajuste
     * @param  array   $opts      forma_pago, referencia_type, referencia_id, user_id,
     *                            categoria_gasto_id, cuenta_contable_id, descripcion, fecha
     */
    public function registrarMovimiento(Caja $caja, string $tipo, string $concepto, float $monto, array $opts = []): MovimientoCaja
    {
        $monto = round($monto, 2);

        $mov = MovimientoCaja::create([
            'caja_id'            => $caja->id,
            'fecha'              => $opts['fecha'] ?? now()->toDateString(),
            'tipo'               => $tipo,
            'concepto'           => $concepto,
            'monto'              => $monto,
            'forma_pago'         => $opts['forma_pago'] ?? null,
            'referencia_type'    => $opts['referencia_type'] ?? null,
            'referencia_id'      => $opts['referencia_id'] ?? null,
            'user_id'            => $opts['user_id'] ?? auth()->id(),
            'categoria_gasto_id' => $opts['categoria_gasto_id'] ?? null,
            'cuenta_contable_id' => $opts['cuenta_contable_id'] ?? null,
            'descripcion'        => $opts['descripcion'] ?? null,
        ]);

        if ($columna = $this->columnaContador($concepto, $tipo)) {
            $caja->increment($columna, $monto);
        }

        return $mov;
    }

    /**
     * Revierte todos los movimientos asociados a una referencia (ej. al anular
     * una venta o eliminar un gasto): decrementa los contadores y borra los
     * movimientos. Devuelve la cantidad de movimientos revertidos.
     */
    public function revertirMovimientosDe(string $referenciaType, int $referenciaId): int
    {
        $movs = MovimientoCaja::where('referencia_type', $referenciaType)
            ->where('referencia_id', $referenciaId)
            ->get();

        foreach ($movs as $mov) {
            if (($caja = Caja::find($mov->caja_id)) && ($columna = $this->columnaContador($mov->concepto, $mov->tipo))) {
                $caja->decrement($columna, (float) $mov->monto);
            }
            $mov->delete();
        }

        return $movs->count();
    }

    /**
     * Transferencia atómica entre dos cajas: registra el comprobante y dos
     * movimientos (egreso en origen + ingreso en destino).
     */
    public function transferir(Caja $origen, Caja $destino, float $monto, ?string $descripcion = null, ?string $fecha = null): TransferenciaCaja
    {
        $monto = round($monto, 2);
        $fecha = $fecha ?? now()->toDateString();

        return DB::transaction(function () use ($origen, $destino, $monto, $descripcion, $fecha) {
            $transferencia = TransferenciaCaja::create([
                'caja_origen_id'  => $origen->id,
                'caja_destino_id' => $destino->id,
                'fecha'           => $fecha,
                'monto'           => $monto,
                'user_id'         => auth()->id(),
                'descripcion'     => $descripcion,
            ]);

            $opts = [
                'referencia_type' => TransferenciaCaja::class,
                'referencia_id'   => $transferencia->id,
                'fecha'           => $fecha,
            ];

            $this->registrarMovimiento($origen, 'egreso', 'transferencia', $monto, [
                ...$opts,
                'descripcion' => $descripcion ?: ('Transferencia a ' . $destino->nombreEtiqueta()),
            ]);

            $this->registrarMovimiento($destino, 'ingreso', 'transferencia', $monto, [
                ...$opts,
                'descripcion' => $descripcion ?: ('Transferencia desde ' . $origen->nombreEtiqueta()),
            ]);

            return $transferencia;
        });
    }

    /** Columna de contador cacheado en `cajas` para un concepto/tipo dado. */
    private function columnaContador(string $concepto, string $tipo): ?string
    {
        return match ($concepto) {
            'venta'          => 'total_ventas',
            'cobro'          => 'total_cobros',
            'pago_proveedor' => 'total_pagos',
            'gasto'          => 'total_gastos',
            'anticipo'       => 'total_anticipos',
            'ajuste'         => $tipo === 'ingreso' ? 'total_ajustes_ingreso' : 'total_ajustes_egreso',
            'transferencia'  => $tipo === 'ingreso' ? 'total_transferencias_entrada' : 'total_transferencias_salida',
            'cheque'         => $tipo === 'ingreso' ? 'total_cheques_entrada' : 'total_cheques_salida',
            default          => null,
        };
    }
}
