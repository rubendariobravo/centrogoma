<?php

namespace App\Services\Pricing;

use Carbon\Carbon;

/**
 * Pedido de cotizacion para una linea de venta.
 *
 * El motor de precios responde con CotizacionResultado.
 * Inmutable por diseño: si necesitas modificar parametros, crea un nuevo request.
 */
final class CotizacionRequest
{
    public function __construct(
        public readonly int $articuloId,
        public readonly float $cantidad,
        public readonly ?int $clienteId = null,
        public readonly string $formaPago = 'efectivo',
        public readonly bool $esReparto = false,
        public readonly ?Carbon $fecha = null,
        /** Suma de cantidades de todos los artículos del mismo rubro+presentación en el carrito.
         *  Si se provee, se usa para determinar el tramo; $cantidad se usa para el subtotal. */
        public readonly ?float $cantidadTramo = null,
        /** Lista forzada por el operador desde el selector de venta.
         *  Si se provee, bypasea resolverLista() (override por rubro + lista del cliente + default).
         *  Las bonificaciones del cliente siguen aplicando normalmente. */
        public readonly ?int $forzarListaId = null,
    ) {
    }
}
