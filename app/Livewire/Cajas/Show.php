<?php

namespace App\Livewire\Cajas;

use App\Models\Caja;
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
            ->with('venta.cliente')
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
            ->with('user')
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
        ]);
    }
}
