<?php

namespace App\Http\Controllers;

use App\Models\CcCliente;
use App\Models\Venta;
use App\Models\VariableEntorno;
use Barryvdh\DomPDF\Facade\Pdf;

class VentaController extends Controller
{
    /**
     * Renderiza el comprobante como HTML y dispara window.print() automáticamente.
     * El formato (ticket / pdf) se lee de la variable de entorno 'impresion_formato'.
     * Tras el diálogo de impresión, redirige al listado de ventas.
     */
    public function imprimir(Venta $venta)
    {
        $venta->load(['cliente.tipoIva', 'detalles.articulo', 'pagos']);
        $saldoAnterior = $this->calcularSaldoAnterior($venta);

        $formato   = VariableEntorno::get('impresion_formato', 'ticket');
        $viewName  = $formato === 'pdf' ? 'ventas.pdf' : 'ventas.ticket';
        $urlVolver = route('ventas');

        // Renderiza el template existente como HTML puro
        $html = view($viewName, compact('venta', 'saldoAnterior'))->render();

        // Inyecta el JS de auto-impresión y redirección post-impresión
        $script = <<<JS
<script>
    // En algunos navegadores window.print() bloquea hasta que cierra el diálogo;
    // en otros es asíncrono. Cubrimos ambos casos con onafterprint + setTimeout.
    function irAVentas() { window.location.href = '{$urlVolver}'; }
    window.onafterprint = irAVentas;
    window.onload = function () {
        window.print();
        // Fallback: si onafterprint no dispara (ej. PDF viewer en pestaña aparte)
        // damos 3 segundos y volvemos igual.
        setTimeout(irAVentas, 30000);
    };
</script>
JS;

        $html = str_replace('</body>', $script . '</body>', $html);

        return response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    public function generarPdf(Venta $venta)
    {
        $venta->load(['cliente.tipoIva', 'detalles.articulo', 'pagos']);
        $saldoAnterior = $this->calcularSaldoAnterior($venta);

        $pdf = Pdf::loadView('ventas.pdf', compact('venta', 'saldoAnterior'));
        $pdf->setPaper('A4');

        return $pdf->stream($this->nombreArchivo($venta));
    }

    public function generarTicket(Venta $venta)
    {
        $venta->load(['cliente.tipoIva', 'detalles.articulo', 'pagos']);
        $saldoAnterior = $this->calcularSaldoAnterior($venta);

        $altura = 520 + ($venta->detalles->count() * 30) + ($venta->pagos->count() * 15);
        if ($venta->cae) $altura += 100;
        if ($saldoAnterior > 0.01) $altura += 60;
        $altura = min($altura, 1400);

        $pdf = Pdf::loadView('ventas.ticket', compact('venta', 'saldoAnterior'));
        $pdf->setPaper([0, 0, 204, $altura], 'portrait');

        return $pdf->stream('Ticket-' . $this->nombreArchivo($venta));
    }

    private function calcularSaldoAnterior(Venta $venta): float
    {
        if (!$venta->cliente_id) {
            return 0.0;
        }

        // Buscar el movimiento de cc generado por esta venta
        $ccVenta = CcCliente::where('referencia_type', Venta::class)
            ->where('referencia_id', $venta->id)
            ->first();

        if ($ccVenta) {
            // saldo_anterior = saldo_despues_de_esta_venta - debe_de_esta_venta
            return (float) $ccVenta->saldo - (float) $ccVenta->debe;
        }

        // Venta cobrada en efectivo/otro: tomar el último saldo antes de esta fecha
        return (float) (CcCliente::where('cliente_id', $venta->cliente_id)
            ->where('fecha', '<', $venta->fecha->toDateString())
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->value('saldo') ?? 0);
    }

    private function nombreArchivo(Venta $venta): string
    {
        if ($venta->cae) {
            $pto = str_pad($venta->pto_vta, 4, '0', STR_PAD_LEFT);
            $nro = str_pad($venta->nro_factura, 8, '0', STR_PAD_LEFT);
            $tipo = match ($venta->tipo_comprobante) {
                'nota_credito' => 'NC-' . $venta->letra_comprobante,
                'nota_debito'  => 'ND-' . $venta->letra_comprobante,
                default        => 'Factura-' . $venta->letra_comprobante,
            };
            return "{$tipo}-{$pto}-{$nro}.pdf";
        }

        return 'Recibo-' . str_pad($venta->nro_venta ?? $venta->id, 8, '0', STR_PAD_LEFT) . '.pdf';
    }
}
