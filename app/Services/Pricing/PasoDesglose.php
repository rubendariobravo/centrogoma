<?php

namespace App\Services\Pricing;

/**
 * Un paso del pipeline de calculo de precio. La secuencia de pasos
 * arma la traza auditable que se muestra al vendedor en el tooltip
 * y que se persiste en det_ventas para auditoria.
 */
final class PasoDesglose
{
    public function __construct(
        public readonly string $paso,        // identificador interno: lista_resuelta, precio_especial, tramo, fallback, bonificacion, recargo_pago, iva
        public readonly string $descripcion, // texto legible para humanos
        public readonly ?float $precioAntes = null,
        public readonly ?float $precioDespues = null,
        public readonly array $detalle = [], // datos extra (id_lista, id_tramo, motivo, etc.)
    ) {
    }

    public function toArray(): array
    {
        return [
            'paso'           => $this->paso,
            'descripcion'    => $this->descripcion,
            'precio_antes'   => $this->precioAntes,
            'precio_despues' => $this->precioDespues,
            'detalle'        => $this->detalle,
        ];
    }
}
