<?php

namespace App\Services\Pricing;

use App\Models\Articulo;
use App\Models\ArticuloPrecio;
use App\Models\Cliente;
use App\Models\ClientePrecio;
use App\Models\ListaPrecio;
use InvalidArgumentException;
use RuntimeException;

/**
 * Motor de calculo de precios.
 *
 * Pipeline (en orden):
 *   1) Resolver cliente y articulo.
 *   2) Buscar precio fijo del cliente para ese articulo (cliente_precios.precio_especial).
 *      Si existe, salta directo al paso de bonificaciones.
 *   3) Resolver la lista a usar:
 *        a) override por rubro: cliente_precios con (cliente, rubro, lista_precio_id no nulo)
 *        b) lista del cliente: cliente.lista_precio_id
 *        c) fallback: lista default del sistema (es_default = true)
 *   4) Buscar tramo en lista_articulo_precios filtrando por lista, articulo y cantidad
 *      (mayor cantidad_desde que <= cantidad).
 *   5) Si no hay tramo aplicable -> fallback a articulo.precio_unitario.
 *   6) Aplicar bonificaciones (cliente general + cliente_precios sobre articulo o rubro).
 *   7) Aplicar recargo por forma de pago si la lista lo define.
 *   8) Calcular IVA y total bruto.
 *
 * Convencion: todos los precios en la base son NETO (sin IVA). El IVA se calcula
 * sobre el subtotal y se devuelve discriminado.
 */
class PriceEngine
{
    public function cotizar(CotizacionRequest $request): CotizacionResultado
    {
        if ($request->cantidad <= 0) {
            throw new InvalidArgumentException('La cantidad debe ser mayor a 0.');
        }

        $articulo = Articulo::find($request->articuloId);
        if (! $articulo) {
            throw new InvalidArgumentException("Articulo {$request->articuloId} no encontrado.");
        }

        $cliente = $request->clienteId ? Cliente::find($request->clienteId) : null;

        $desglose = [];

        // Cargamos overrides del cliente una sola vez
        $overrides = $this->cargarOverridesCliente($cliente);

        // ── 1) Precio especial del cliente para este articulo ─────────────
        $precioEspecial = $overrides['articulo'][$articulo->id]['precio_especial'] ?? null;

        if ($precioEspecial !== null) {
            $desglose[] = new PasoDesglose(
                paso: 'precio_especial',
                descripcion: "Precio especial negociado para este cliente: \${$precioEspecial}",
                precioAntes: null,
                precioDespues: (float) $precioEspecial,
                detalle: ['cliente_precio_id' => $overrides['articulo'][$articulo->id]['id']],
            );

            return $this->finalizar(
                request: $request,
                articulo: $articulo,
                cliente: $cliente,
                overrides: $overrides,
                listaId: null,
                tramoId: null,
                regla: 'precio_especial',
                precioBase: (float) $precioEspecial,
                desglose: $desglose,
            );
        }

        // ── 2) Resolver lista efectiva ─────────────────────────────────────
        // Si el operador eligió una lista explícita en el formulario de venta,
        // la usamos directamente (bypass de override por rubro + lista del cliente + default).
        // Las bonificaciones del cliente siguen aplicando en el paso finalizar().
        if ($request->forzarListaId) {
            $listaForzada = ListaPrecio::find($request->forzarListaId);
            if ($listaForzada) {
                $desglose[] = new PasoDesglose(
                    paso: 'lista_resuelta',
                    descripcion: "Lista \"{$listaForzada->nombre}\" seleccionada manualmente por el operador.",
                    detalle: ['lista_id' => $listaForzada->id, 'origen' => 'operador'],
                );
                $lista = $listaForzada;
            } else {
                $lista = $this->resolverLista($cliente, $articulo, $overrides, $desglose);
            }
        } else {
            $lista = $this->resolverLista($cliente, $articulo, $overrides, $desglose);
        }

        // ── 3) Buscar tramo en la lista resuelta ───────────────────────────
        // Si hay una cantidad grupal (mismo rubro+presentación), se usa para
        // determinar el tramo; la cantidad propia se usa solo para el subtotal.
        $cantidadParaTramo = $request->cantidadTramo ?? $request->cantidad;

        if ($request->cantidadTramo !== null && $request->cantidadTramo > $request->cantidad) {
            $desglose[] = new PasoDesglose(
                paso: 'tramo_grupo',
                descripcion: sprintf(
                    'Cantidad propia: %s — Total del grupo (mismo rubro/presentación): %s → se usa el tramo del grupo.',
                    $request->cantidad,
                    $request->cantidadTramo,
                ),
                detalle: [
                    'cantidad_propia' => $request->cantidad,
                    'cantidad_grupo'  => $request->cantidadTramo,
                ],
            );
        }

        $tramo = ArticuloPrecio::where('articulo_id', $articulo->id)
            ->where('lista_precio_id', $lista->id)
            ->where('activo', true)
            ->where('cantidad_desde', '<=', $cantidadParaTramo)
            ->where(function ($q) use ($cantidadParaTramo) {
                $q->whereNull('cantidad_hasta')
                  ->orWhere('cantidad_hasta', '>=', $cantidadParaTramo);
            })
            ->orderByDesc('cantidad_desde')
            ->first();

        if ($tramo) {
            $desglose[] = new PasoDesglose(
                paso: 'tramo',
                descripcion: sprintf(
                    'Tramo "%s..%s" en lista %s: $%s',
                    rtrim(rtrim((string) $tramo->cantidad_desde, '0'), '.'),
                    $tramo->cantidad_hasta !== null ? rtrim(rtrim((string) $tramo->cantidad_hasta, '0'), '.') : '∞',
                    $lista->nombre,
                    $tramo->precio
                ),
                precioAntes: null,
                precioDespues: (float) $tramo->precio,
                detalle: [
                    'tramo_id'        => $tramo->id,
                    'lista_id'        => $lista->id,
                    'cantidad_desde'  => (float) $tramo->cantidad_desde,
                    'cantidad_hasta'  => $tramo->cantidad_hasta !== null ? (float) $tramo->cantidad_hasta : null,
                ],
            );

            return $this->finalizar(
                request: $request,
                articulo: $articulo,
                cliente: $cliente,
                overrides: $overrides,
                listaId: $lista->id,
                tramoId: $tramo->id,
                regla: 'tramo_lista',
                precioBase: (float) $tramo->precio,
                desglose: $desglose,
            );
        }

        // ── 4) Fallback: precio_unitario del articulo ──────────────────────
        $desglose[] = new PasoDesglose(
            paso: 'fallback',
            descripcion: "Sin tramo aplicable en lista \"{$lista->nombre}\" para cantidad {$cantidadParaTramo}. Se usa precio_unitario del articulo.",
            precioAntes: null,
            precioDespues: (float) $articulo->precio_unitario,
            detalle: ['lista_id' => $lista->id],
        );

        return $this->finalizar(
            request: $request,
            articulo: $articulo,
            cliente: $cliente,
            overrides: $overrides,
            listaId: $lista->id,
            tramoId: null,
            regla: 'fallback_articulo',
            precioBase: (float) $articulo->precio_unitario,
            desglose: $desglose,
        );
    }

    /**
     * Indexa los cliente_precios activos para acceso O(1) por articulo y por rubro.
     * Estructura:
     *   ['articulo' => [articulo_id => row], 'rubro' => [rubro_id => row]]
     */
    private function cargarOverridesCliente(?Cliente $cliente): array
    {
        $resultado = ['articulo' => [], 'rubro' => []];
        if (! $cliente) {
            return $resultado;
        }

        $rows = ClientePrecio::where('cliente_id', $cliente->id)
            ->where('activo', true)
            ->get();

        foreach ($rows as $row) {
            $data = [
                'id'              => $row->id,
                'precio_especial' => $row->precio_especial !== null ? (float) $row->precio_especial : null,
                'lista_precio_id' => $row->lista_precio_id,
                'bonificacion'    => (float) $row->bonificacion,
            ];
            if ($row->articulo_id) {
                $resultado['articulo'][$row->articulo_id] = $data;
            } elseif ($row->rubro_id) {
                $resultado['rubro'][$row->rubro_id] = $data;
            }
        }

        return $resultado;
    }

    /**
     * Resuelve que lista usar: override por rubro -> cliente -> default.
     * Agrega un paso al desglose explicando la decision.
     */
    private function resolverLista(?Cliente $cliente, Articulo $articulo, array $overrides, array &$desglose): ListaPrecio
    {
        // a) override por rubro del articulo
        $rubroId = $articulo->rubro_id;
        if ($rubroId && isset($overrides['rubro'][$rubroId]['lista_precio_id'])) {
            $listaId = $overrides['rubro'][$rubroId]['lista_precio_id'];
            $lista = ListaPrecio::find($listaId);
            if ($lista) {
                $desglose[] = new PasoDesglose(
                    paso: 'lista_resuelta',
                    descripcion: "Lista \"{$lista->nombre}\" por override del cliente para rubro {$rubroId}.",
                    detalle: ['lista_id' => $lista->id, 'origen' => 'override_rubro'],
                );
                return $lista;
            }
        }

        // b) lista del cliente
        if ($cliente && $cliente->lista_precio_id) {
            $lista = ListaPrecio::find($cliente->lista_precio_id);
            if ($lista) {
                $desglose[] = new PasoDesglose(
                    paso: 'lista_resuelta',
                    descripcion: "Lista \"{$lista->nombre}\" asignada al cliente.",
                    detalle: ['lista_id' => $lista->id, 'origen' => 'cliente'],
                );
                return $lista;
            }
        }

        // c) lista default del sistema
        $lista = ListaPrecio::default();
        if (! $lista) {
            throw new RuntimeException(
                'No hay lista de precios marcada como default. Marque una lista con es_default=true.'
            );
        }

        $desglose[] = new PasoDesglose(
            paso: 'lista_resuelta',
            descripcion: "Lista default del sistema: \"{$lista->nombre}\".",
            detalle: ['lista_id' => $lista->id, 'origen' => 'default'],
        );
        return $lista;
    }

    /**
     * Aplica bonificaciones, recargo por forma de pago, calcula IVA y arma el resultado.
     */
    private function finalizar(
        CotizacionRequest $request,
        Articulo $articulo,
        ?Cliente $cliente,
        array $overrides,
        ?int $listaId,
        ?int $tramoId,
        string $regla,
        float $precioBase,
        array $desglose,
    ): CotizacionResultado {
        $precio = $precioBase;

        // Bonificaciones aditivas: cliente general + override por articulo + override por rubro
        $bonifTotal = 0.0;
        if ($cliente) {
            $bonifTotal += (float) $cliente->bonificacion;
        }
        if (isset($overrides['articulo'][$articulo->id]['bonificacion'])) {
            $bonifTotal += $overrides['articulo'][$articulo->id]['bonificacion'];
        }
        if ($articulo->rubro_id && isset($overrides['rubro'][$articulo->rubro_id]['bonificacion'])) {
            $bonifTotal += $overrides['rubro'][$articulo->rubro_id]['bonificacion'];
        }

        if ($bonifTotal > 0) {
            $precioAntes = $precio;
            $precio = round($precio * (1 - $bonifTotal / 100), 4);
            $desglose[] = new PasoDesglose(
                paso: 'bonificacion',
                descripcion: "Bonificacion {$bonifTotal}% (cliente + overrides).",
                precioAntes: $precioAntes,
                precioDespues: $precio,
                detalle: ['porcentaje' => $bonifTotal],
            );
        }

        // Recargo por forma de pago segun la lista resuelta
        $recargo = 0.0;
        if ($listaId) {
            $lista = ListaPrecio::find($listaId);
            if ($lista) {
                if ($request->formaPago !== 'efectivo' && (float) $lista->recargo_no_efectivo > 0) {
                    $recargo = (float) $lista->recargo_no_efectivo;
                    $precioAntes = $precio;
                    $precio = round($precio * (1 + $recargo / 100), 4);
                    $desglose[] = new PasoDesglose(
                        paso: 'recargo_pago',
                        descripcion: "Recargo {$recargo}% por forma de pago no efectivo.",
                        precioAntes: $precioAntes,
                        precioDespues: $precio,
                        detalle: ['forma_pago' => $request->formaPago, 'porcentaje' => $recargo],
                    );
                } elseif ($request->formaPago === 'efectivo' && (float) $lista->descuento_efectivo > 0) {
                    $descuento = (float) $lista->descuento_efectivo;
                    $precioAntes = $precio;
                    $precio = round($precio * (1 - $descuento / 100), 4);
                    $recargo = -$descuento;
                    $desglose[] = new PasoDesglose(
                        paso: 'recargo_pago',
                        descripcion: "Descuento {$descuento}% por pago en efectivo.",
                        precioAntes: $precioAntes,
                        precioDespues: $precio,
                        detalle: ['forma_pago' => 'efectivo', 'porcentaje' => -$descuento],
                    );
                }
            }
        }

        // Redondeo final del precio unitario a 2 decimales
        $precioUnitario = round($precio, 2);
        $subtotal = round($precioUnitario * $request->cantidad, 2);
        $alicuotaIva = (float) $articulo->alicuota_iva;
        $montoIva = round($subtotal * $alicuotaIva / 100, 2);
        $total = round($subtotal + $montoIva, 2);

        $desglose[] = new PasoDesglose(
            paso: 'iva',
            descripcion: "IVA {$alicuotaIva}% sobre subtotal de \${$subtotal}: \${$montoIva}.",
            detalle: [
                'subtotal_neto' => $subtotal,
                'alicuota'      => $alicuotaIva,
                'monto_iva'     => $montoIva,
                'total_bruto'   => $total,
            ],
        );

        return new CotizacionResultado(
            precioUnitario: $precioUnitario,
            subtotal: $subtotal,
            alicuotaIva: $alicuotaIva,
            netoIva: $subtotal,
            montoIva: $montoIva,
            total: $total,
            listaPrecioId: $listaId,
            tramoAplicadoId: $tramoId,
            reglaAplicada: $regla,
            bonificacionAplicada: $bonifTotal,
            recargoAplicado: $recargo,
            desglose: $desglose,
        );
    }
}
