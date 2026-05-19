<?php

namespace App\Services\Pricing;

/**
 * Resultado de cotizar una linea.
 *
 * Convencion: precios en NETO (sin IVA), igual que en la tabla articulos.
 * El IVA se devuelve por separado para que la UI lo pueda mostrar discriminado.
 */
final class CotizacionResultado
{
    /** @param PasoDesglose[] $desglose */
    public function __construct(
        public readonly float $precioUnitario,        // neto, despues de bonificaciones y recargos
        public readonly float $subtotal,              // cantidad * precioUnitario
        public readonly float $alicuotaIva,           // 0, 10.5, 21
        public readonly float $netoIva,               // = subtotal (alias semantico)
        public readonly float $montoIva,              // subtotal * alicuotaIva / 100
        public readonly float $total,                 // subtotal + montoIva
        public readonly ?int $listaPrecioId,          // lista resuelta para esta linea
        public readonly ?int $tramoAplicadoId,        // null si no aplico tramo (precio especial o fallback)
        public readonly string $reglaAplicada,        // 'precio_especial' | 'tramo_lista' | 'fallback_articulo'
        public readonly float $bonificacionAplicada,  // % final aplicado
        public readonly float $recargoAplicado,       // % por forma de pago
        public readonly array $desglose,              // PasoDesglose[]
    ) {
    }

    public function toArray(): array
    {
        return [
            'precio_unitario'        => $this->precioUnitario,
            'subtotal'               => $this->subtotal,
            'alicuota_iva'           => $this->alicuotaIva,
            'neto_iva'               => $this->netoIva,
            'monto_iva'              => $this->montoIva,
            'total'                  => $this->total,
            'lista_precio_id'        => $this->listaPrecioId,
            'tramo_aplicado_id'      => $this->tramoAplicadoId,
            'regla_aplicada'         => $this->reglaAplicada,
            'bonificacion_aplicada'  => $this->bonificacionAplicada,
            'recargo_aplicado'       => $this->recargoAplicado,
            'desglose'               => array_map(fn (PasoDesglose $p) => $p->toArray(), $this->desglose),
        ];
    }
}
