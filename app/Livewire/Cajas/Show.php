<?php

namespace App\Livewire\Cajas;

use App\Models\Caja;
use App\Models\FormaPago;
use App\Models\Gasto;
use App\Models\MovimientoCaja;
use App\Models\PagoVenta;
use App\Models\TransferenciaCaja;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Detalle de Caja')]
class Show extends Component
{
    public Caja $caja;
    public string $tab = 'movimientos';

    public function mount(Caja $caja): void
    {
        $this->caja->load('user');
    }

    public function render()
    {
        $ventas = $this->caja->ventas()
            ->with('cliente')
            ->orderByDesc('id')
            ->get();

        $recibos = $this->caja->recibos()
            ->with('cliente')
            ->orderByDesc('id')
            ->get();

        $pagos = PagoVenta::where('caja_id', $this->caja->id)
            ->with(['venta.cliente', 'tarjeta'])
            ->orderByDesc('id')
            ->get();

        $gastos = Gasto::where('caja_id', $this->caja->id)
            ->with(['categoria', 'proveedor'])
            ->orderByDesc('id')
            ->get();

        $transferencias = TransferenciaCaja::with(['cajaOrigen.user', 'cajaDestino.user'])
            ->where('caja_origen_id', $this->caja->id)
            ->orWhere('caja_destino_id', $this->caja->id)
            ->orderByDesc('id')
            ->get();

        // Se traen en orden cronológico para poder calcular el saldo corrido y recién
        // después se invierten para mostrar los últimos arriba.
        $movimientos = MovimientoCaja::where('caja_id', $this->caja->id)
            ->with(['user', 'tarjeta'])
            ->orderBy('id')
            ->get();

        // Saldo en efectivo después de cada movimiento. Sin esto la pantalla muestra
        // un único número final y un operador que la mira dos veces en la mañana no
        // tiene forma de saber qué lo movió: el salto parece inventado.
        $corrido       = (float) $this->caja->saldo_inicial;
        $saldoCorrido  = [];
        $esEfectivoMov = [];

        foreach ($movimientos as $mov) {
            $esEfectivo = Caja::movimientoEsEfectivo($mov->forma_pago, $mov->concepto);
            $esEfectivoMov[$mov->id] = $esEfectivo;

            if ($esEfectivo) {
                $corrido += ($mov->tipo === 'ingreso' ? 1 : -1) * (float) $mov->monto;
            }

            $saldoCorrido[$mov->id] = round($corrido, 2);
        }

        $movimientos = $movimientos->reverse()->values();

        // Cobrado por medio (ventas y recibos, sin cuenta corriente), con la tarjeta abierta por
        // marca: es lo que se compara contra las liquidaciones de cada tarjeta y de Mercado Pago.
        $labels = FormaPago::pluck('descripcion', 'codigo');
        $cobrosPorMedio = $movimientos
            ->filter(fn ($m) => $m->tipo === 'ingreso' && in_array($m->concepto, ['venta', 'cobro'], true)
                && $m->forma_pago && $m->forma_pago !== 'cuenta_corriente')
            ->groupBy(fn ($m) => ($labels[$m->forma_pago] ?? $m->forma_pago) . ($m->forma_pago === 'tarjeta' ? ' · ' . ($m->tarjeta?->nombre ?? 'sin marca') : ''))
            ->map(fn ($g) => ['cantidad' => $g->count(), 'total' => round($g->sum('monto'), 2)])
            ->sortByDesc('total');

        // Movimientos manuales (ingresos/egresos registrados desde "Registrar movimiento")
        $ajustes = $movimientos->where('concepto', 'ajuste')->values();

        return view('livewire.cajas.show', [
            'ventas'         => $ventas,
            'recibos'        => $recibos,
            'pagos'          => $pagos,
            'gastos'         => $gastos,
            'transferencias' => $transferencias,
            'movimientos'    => $movimientos,
            'ajustes'        => $ajustes,
            'saldoCorrido'   => $saldoCorrido,
            'esEfectivoMov'  => $esEfectivoMov,
            'cobrosPorMedio' => $cobrosPorMedio,
            'labelsFormas'   => $labels,
        ]);
    }
}
