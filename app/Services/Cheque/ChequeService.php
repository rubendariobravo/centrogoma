<?php

namespace App\Services\Cheque;

use App\Models\Caja;
use App\Models\CcCliente;
use App\Models\Cheque;
use App\Models\ChequeMovimiento;
use App\Models\Cliente;
use App\Services\Caja\CajaService;
use Illuminate\Support\Facades\DB;

/**
 * Punto único de cambios de estado y efectos colaterales de un cheque.
 *
 * Para los cheques de TERCEROS la plata real recién aparece al ACREDITAR:
 * el valor se mueve de la caja donde se cobró (caja_origen) hacia la caja
 * banco de destino. El concepto 'cheque' del libro de caja NO es efectivo
 * físico (espeja a las transferencias entre cajas), por lo que afecta el
 * saldo esperado de la caja pero no su arqueo de efectivo.
 */
class ChequeService
{
    public function __construct(private CajaService $cajas)
    {
    }

    /**
     * Registra una entrada en el historial del cheque.
     */
    public function log(Cheque $cheque, string $tipoMov, ?string $observacion = null, array $opts = []): ChequeMovimiento
    {
        return ChequeMovimiento::create([
            'cheque_id'         => $cheque->id,
            'fecha'             => $opts['fecha'] ?? now()->toDateString(),
            'tipo_mov'          => $tipoMov,
            'estado_resultante' => $cheque->estado,
            'monto'             => $opts['monto'] ?? $cheque->monto,
            'referencia_type'   => $opts['referencia_type'] ?? null,
            'referencia_id'     => $opts['referencia_id'] ?? null,
            'user_id'           => auth()->id(),
            'observacion'       => $observacion,
        ]);
    }

    /**
     * Crea un cheque de TERCERO recibido (estado cartera) y registra su recepción.
     * Usado al cobrar con cheque en Ventas / Recibos.
     *
     * @param array $datos banco_id, nro_cheque, monto, fecha_pago, cliente_id,
     *                     caja_origen_id, venta_id|recibo_id, es_echeq, es_diferido,
     *                     fecha_recepcion, librador_nombre, librador_cuit, observacion
     */
    public function registrarRecibido(array $datos): Cheque
    {
        $cheque = Cheque::create([
            'tipo'            => 'tercero',
            'estado'          => 'cartera',
            'banco_id'        => $datos['banco_id'] ?? null,
            'serie'           => $datos['serie'] ?? null,
            'nro_cheque'      => $datos['nro_cheque'] ?? null,
            'monto'           => round((float) ($datos['monto'] ?? 0), 2),
            'cliente_id'      => $datos['cliente_id'] ?? null,
            'caja_origen_id'  => $datos['caja_origen_id'] ?? null,
            'recibo_id'       => $datos['recibo_id'] ?? null,
            'venta_id'        => $datos['venta_id'] ?? null,
            'es_echeq'        => (bool) ($datos['es_echeq'] ?? false),
            'es_diferido'     => (bool) ($datos['es_diferido'] ?? true),
            'fecha_recepcion' => $datos['fecha_recepcion'] ?? now()->toDateString(),
            'fecha_pago'      => $datos['fecha_pago'] ?? null,
            'librador_nombre' => $datos['librador_nombre'] ?? null,
            'librador_cuit'   => $datos['librador_cuit'] ?? null,
            'user_id'         => auth()->id(),
            'observacion'     => $datos['observacion'] ?? null,
        ]);

        $this->log($cheque, 'recepcion', $datos['observacion'] ?? 'Cheque recibido', [
            'referencia_type' => $datos['referencia_type'] ?? null,
            'referencia_id'   => $datos['referencia_id'] ?? null,
        ]);

        return $cheque;
    }

    /**
     * Crea un cheque PROPIO emitido (estado emitido) y registra su emisión.
     * Usado al pagar con cheque en Órdenes de Pago. La plata NO sale de la caja
     * todavía: el egreso se registra al debitarse (cheque diferido).
     *
     * @param array $datos banco_id, nro_cheque, monto, fecha_pago, proveedor_id,
     *                     caja_id (banco librador), orden_pago_id, es_echeq, observacion
     */
    public function registrarEmitido(array $datos): Cheque
    {
        $cheque = Cheque::create([
            'tipo'            => 'propio',
            'estado'          => 'emitido',
            'banco_id'        => $datos['banco_id'] ?? null,
            'serie'           => $datos['serie'] ?? null,
            'nro_cheque'      => $datos['nro_cheque'] ?? null,
            'monto'           => round((float) ($datos['monto'] ?? 0), 2),
            'proveedor_id'    => $datos['proveedor_id'] ?? null,
            'caja_id'         => $datos['caja_id'] ?? null,
            'orden_pago_id'   => $datos['orden_pago_id'] ?? null,
            'es_echeq'        => (bool) ($datos['es_echeq'] ?? false),
            'es_diferido'     => (bool) ($datos['es_diferido'] ?? true),
            'fecha_emision'   => $datos['fecha_emision'] ?? now()->toDateString(),
            'fecha_pago'      => $datos['fecha_pago'] ?? null,
            'user_id'         => auth()->id(),
            'observacion'     => $datos['observacion'] ?? null,
        ]);

        $this->log($cheque, 'emision', $datos['observacion'] ?? 'Cheque emitido', [
            'referencia_type' => $datos['referencia_type'] ?? null,
            'referencia_id'   => $datos['referencia_id'] ?? null,
        ]);

        return $cheque;
    }

    /**
     * Debita un cheque propio (emitido → debitado): la plata sale de la caja banco.
     */
    public function debitar(Cheque $cheque, ?int $cajaBancoId = null, ?string $observacion = null): void
    {
        $this->assertTransicion($cheque, 'debitado');

        DB::transaction(function () use ($cheque, $cajaBancoId, $observacion) {
            $cajaId = $cajaBancoId ?? $cheque->caja_id;
            $caja   = $cajaId ? Caja::find($cajaId) : null;

            $cheque->update(['estado' => 'debitado', 'caja_id' => $cajaId]);

            if ($caja) {
                $this->cajas->registrarMovimiento($caja, 'egreso', 'cheque', (float) $cheque->monto, [
                    'forma_pago'      => 'cheque',
                    'referencia_type' => Cheque::class,
                    'referencia_id'   => $cheque->id,
                    'descripcion'     => 'Débito cheque propio ' . ($cheque->nro_cheque ?: '#' . $cheque->id),
                    'fecha'           => now()->toDateString(),
                ]);
            }

            $this->log($cheque, 'debito', $observacion ?? 'Cheque propio debitado del banco');
        });
    }

    /**
     * Deposita un cheque de tercero (cartera → depositado) en una caja banco.
     * No mueve plata todavía; la acreditación es la que impacta.
     */
    public function depositar(Cheque $cheque, ?int $cajaBancoId, ?string $observacion = null): void
    {
        $this->assertTransicion($cheque, 'depositado');

        $cheque->update([
            'estado'  => 'depositado',
            'caja_id' => $cajaBancoId,
        ]);

        $this->log($cheque, 'deposito', $observacion ?? 'Depósito del cheque para su acreditación');
    }

    /**
     * Acredita un cheque depositado: la plata entra a la caja banco.
     * Mueve el valor desde la caja de origen (egreso) hacia la caja banco (ingreso).
     */
    public function acreditar(Cheque $cheque, ?int $cajaBancoId = null, ?string $observacion = null): void
    {
        $this->assertTransicion($cheque, 'acreditado');

        DB::transaction(function () use ($cheque, $cajaBancoId, $observacion) {
            $cajaDestinoId = $cajaBancoId ?? $cheque->caja_id;
            $cajaDestino   = $cajaDestinoId ? Caja::find($cajaDestinoId) : null;

            $cheque->update([
                'estado'  => 'acreditado',
                'caja_id' => $cajaDestinoId,
            ]);

            $opts = [
                'forma_pago'      => 'cheque',
                'referencia_type' => Cheque::class,
                'referencia_id'   => $cheque->id,
                'descripcion'     => 'Acreditación cheque ' . ($cheque->nro_cheque ?: '#' . $cheque->id),
                'fecha'           => now()->toDateString(),
            ];

            // Sale de la caja donde se había cobrado el cheque (si existe).
            if ($cheque->caja_origen_id && ($origen = Caja::find($cheque->caja_origen_id))) {
                $this->cajas->registrarMovimiento($origen, 'egreso', 'cheque', (float) $cheque->monto, $opts);
            }

            // Entra a la caja banco.
            if ($cajaDestino) {
                $this->cajas->registrarMovimiento($cajaDestino, 'ingreso', 'cheque', (float) $cheque->monto, $opts);
            }

            $this->log($cheque, 'acreditacion', $observacion ?? 'Cheque acreditado en banco');
        });
    }

    /**
     * Rechaza un cheque (sin fondos). Revierte el impacto en caja si estaba
     * acreditado y reabre la deuda del cliente que lo entregó.
     */
    public function rechazar(Cheque $cheque, ?string $observacion = null): void
    {
        $this->assertTransicion($cheque, 'rechazado');

        DB::transaction(function () use ($cheque, $observacion) {
            // Si ya estaba acreditado, deshacer los movimientos de caja.
            if ($cheque->estado === 'acreditado') {
                $this->cajas->revertirMovimientosDe(Cheque::class, $cheque->id);
            }

            $cheque->update(['estado' => 'rechazado']);

            // Reabrir deuda del cliente: el pago con este cheque no se concretó.
            if ($cheque->tipo === 'tercero' && $cheque->cliente_id) {
                $this->reabrirDeudaCliente($cheque, $observacion);
            }

            $this->log($cheque, 'rechazo', $observacion ?? 'Cheque rechazado por el banco');
        });
    }

    /**
     * Anula un cheque. Revierte impacto en caja si estaba acreditado/debitado.
     */
    public function anular(Cheque $cheque, ?string $observacion = null): void
    {
        // La anulación se permite desde cualquier estado salvo si ya está anulado.
        if ($cheque->estado === 'anulado') {
            return;
        }

        DB::transaction(function () use ($cheque, $observacion) {
            if (in_array($cheque->estado, ['acreditado', 'debitado'], true)) {
                $this->cajas->revertirMovimientosDe(Cheque::class, $cheque->id);
            }

            $cheque->update(['estado' => 'anulado']);
            $this->log($cheque, 'anulacion', $observacion ?? 'Cheque anulado');
        });
    }

    private function reabrirDeudaCliente(Cheque $cheque, ?string $observacion): void
    {
        $cliente = Cliente::find($cheque->cliente_id);
        if (! $cliente) {
            return;
        }

        $nuevoSaldo = round((float) ($cliente->saldo ?? 0) + (float) $cheque->monto, 2);
        $cliente->update(['saldo' => $nuevoSaldo]);

        CcCliente::create([
            'cliente_id'      => $cliente->id,
            'fecha'           => now()->toDateString(),
            'concepto'        => 'Rechazo cheque ' . ($cheque->nro_cheque ?: '#' . $cheque->id)
                                 . ($observacion ? " — {$observacion}" : ''),
            'debe'            => (float) $cheque->monto,
            'haber'           => 0,
            'saldo'           => $nuevoSaldo,
            'referencia_type' => Cheque::class,
            'referencia_id'   => $cheque->id,
        ]);
    }

    private function assertTransicion(Cheque $cheque, string $nuevoEstado): void
    {
        if (! $cheque->puedeTransicionarA($nuevoEstado)) {
            throw new \RuntimeException(
                "Transición no permitida: {$cheque->estado} → {$nuevoEstado} para cheque {$cheque->tipo}."
            );
        }
    }
}
