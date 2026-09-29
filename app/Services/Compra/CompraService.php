<?php

namespace App\Services\Compra;

use App\Models\Caja;
use App\Models\CcProveedor;
use App\Models\Compra;
use App\Models\FormaPago;
use App\Models\MovimientoArticulo;
use App\Models\Proveedor;
use App\Models\VariableEntorno;
use App\Services\Caja\CajaService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Punto único de impacto de una compra sobre stock, costos, cuenta corriente y caja.
 *
 * procesar() aplica los efectos de una compra registrada; revertir() los deshace
 * (usado al anular). Portado de avícola sin percepciones/retenciones ni propagación
 * de costo a listas (CentroGoma no tiene ajuste_tipo en los tramos).
 */
class CompraService
{
    public function __construct(private CajaService $cajas)
    {
    }

    /**
     * Aplica stock + precio_costo + CC proveedor (o egreso de caja si es contado).
     * Idempotente: si ya está procesada o anulada no hace nada.
     */
    public function procesar(Compra $compra): void
    {
        if ($compra->procesada || $compra->estado === 'A') {
            return;
        }

        DB::transaction(function () use ($compra) {
            $compra->loadMissing('detalles.articulo');
            $actualizaCosto = (bool) VariableEntorno::get('ajuste_automatico', true);

            foreach ($compra->detalles as $det) {
                $art = $det->articulo;
                if (! $art) {
                    continue;
                }

                if ($art->modifica_stock) {
                    $stockAnterior = (float) $art->stock;
                    $art->increment('stock', $det->cantidad);

                    MovimientoArticulo::create([
                        'articulo_id'     => $art->id,
                        'user_id'         => Auth::id(),
                        'tipo'            => 'entrada',
                        'cantidad'        => $det->cantidad,
                        'stock_anterior'  => $stockAnterior,
                        'stock_posterior' => $stockAnterior + (float) $det->cantidad,
                        'fecha'           => $compra->fecha,
                        'referencia_type' => Compra::class,
                        'referencia_id'   => $compra->id,
                        'observacion'     => $compra->etiqueta(),
                    ]);
                }

                if ($actualizaCosto) {
                    $art->update(['precio_costo' => $det->precio_unitario]);
                }
            }

            $total = round((float) $compra->total, 2);

            if (FormaPago::esCuentaCorriente($compra->forma_pago)) {
                $this->asentarCc($compra, debe: $total, haber: 0, fecha: $compra->fecha, concepto: $compra->etiqueta());
                $compra->saldo  = $total;
                $compra->estado = 'P';
            } else {
                // Contado: egreso desde la caja elegida en la compra.
                if ($compra->caja_id && ($caja = Caja::find($compra->caja_id))) {
                    $this->cajas->registrarMovimiento($caja, 'egreso', 'pago_proveedor', $total, [
                        'forma_pago'      => $compra->forma_pago,
                        'referencia_type' => Compra::class,
                        'referencia_id'   => $compra->id,
                        'descripcion'     => 'Pago ' . $compra->etiqueta(),
                        'fecha'           => $compra->fecha,
                    ]);
                }
                $compra->saldo  = 0;
                $compra->estado = 'C';
            }

            $compra->procesada     = true;
            $compra->procesada_at  = now();
            $compra->procesada_por = Auth::id();
            $compra->save();
        });
    }

    /**
     * Deshace los efectos de una compra procesada (stock, CC / caja).
     * No revierte precio_costo (no es reversible de forma confiable).
     */
    public function revertir(Compra $compra): void
    {
        if (! $compra->procesada) {
            return;
        }

        DB::transaction(function () use ($compra) {
            $compra->loadMissing('detalles.articulo');

            foreach ($compra->detalles as $det) {
                $art = $det->articulo;
                if (! $art || ! $art->modifica_stock) {
                    continue;
                }

                $stockAnterior = (float) $art->stock;
                $art->decrement('stock', $det->cantidad);

                MovimientoArticulo::create([
                    'articulo_id'     => $art->id,
                    'user_id'         => Auth::id(),
                    'tipo'            => 'salida',
                    'cantidad'        => $det->cantidad,
                    'stock_anterior'  => $stockAnterior,
                    'stock_posterior' => $stockAnterior - (float) $det->cantidad,
                    'fecha'           => now()->toDateString(),
                    'referencia_type' => Compra::class,
                    'referencia_id'   => $compra->id,
                    'observacion'     => 'Reversión ' . $compra->etiqueta(),
                ]);
            }

            if (FormaPago::esCuentaCorriente($compra->forma_pago)) {
                $this->asentarCc($compra, debe: 0, haber: round((float) $compra->total, 2),
                    fecha: now()->toDateString(), concepto: 'Anulación ' . $compra->etiqueta());
            } else {
                $this->cajas->revertirMovimientosDe(Compra::class, $compra->id);
            }

            $compra->procesada = false;
            $compra->saldo     = 0;
            $compra->save();
        });
    }

    private function asentarCc(Compra $compra, float $debe, float $haber, $fecha, string $concepto): void
    {
        $proveedor = Proveedor::find($compra->proveedor_id);
        if (! $proveedor) {
            return;
        }

        $nuevoSaldo = round((float) $proveedor->saldo + $debe - $haber, 2);
        $proveedor->update(['saldo' => $nuevoSaldo]);

        CcProveedor::create([
            'proveedor_id'    => $compra->proveedor_id,
            'fecha'           => $fecha,
            'concepto'        => $concepto,
            'debe'            => $debe,
            'haber'           => $haber,
            'saldo'           => $nuevoSaldo,
            'referencia_type' => Compra::class,
            'referencia_id'   => $compra->id,
        ]);
    }
}
