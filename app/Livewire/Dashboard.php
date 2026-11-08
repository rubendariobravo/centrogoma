<?php

namespace App\Livewire;

use App\Models\Caja;
use App\Models\Cheque;
use App\Models\Cliente;
use App\Models\Proveedor;
use App\Models\Venta;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Tablero de inicio. Sin métricas de stock: el negocio no lo lleva.
 * Los saldos de CC salen de la ficha (clientes.saldo / proveedores.saldo), que incluye los
 * saldos iniciales importados del Delphi; las ventas excluyen esos comprobantes (comerciales()).
 */
class Dashboard extends Component
{
    /** Ventana de "cheques por vencer", en días. */
    private const DIAS_CHEQUES = 7;

    public int $ventasHoy = 0;
    public float $totalHoy = 0;
    public int $ventasSemana = 0;
    public float $totalMes = 0;
    public ?float $variacionMes = null;   // % contra el mismo tramo del mes anterior
    public float $aCobrar = 0;
    public int $deudores = 0;
    public float $aPagar = 0;
    public float $chequesPropiosPendientes = 0;
    public ?Caja $cajaAbierta = null;
    public $ultimasVentas;
    public $chequesPorVencer;
    public $mayoresDeudores;

    public function mount(): void
    {
        $hoy = today();
        $vigentes = fn () => Venta::comerciales()->where('estado', '!=', 'A');

        $this->ventasHoy    = $vigentes()->whereDate('fecha', $hoy)->count();
        $this->totalHoy     = (float) $vigentes()->whereDate('fecha', $hoy)->sum('total');
        $this->ventasSemana = $vigentes()->whereBetween('fecha', [now()->startOfWeek()->toDateString(), $hoy->toDateString()])->count();

        // Mes en curso hasta hoy contra los mismos días del mes anterior.
        $this->totalMes = (float) $vigentes()->whereBetween('fecha', [$hoy->copy()->startOfMonth()->toDateString(), $hoy->toDateString()])->sum('total');
        $mesAnterior = (float) $vigentes()->whereBetween('fecha', [
            $hoy->copy()->subMonthNoOverflow()->startOfMonth()->toDateString(),
            $hoy->copy()->subMonthNoOverflow()->toDateString(),
        ])->sum('total');
        $this->variacionMes = $mesAnterior > 0 ? round(($this->totalMes - $mesAnterior) / $mesAnterior * 100, 1) : null;

        $this->aCobrar  = (float) Cliente::where('saldo', '>', 0)->sum('saldo');
        $this->deudores = Cliente::where('saldo', '>', 0)->count();
        $this->aPagar   = (float) Proveedor::where('saldo', '>', 0)->sum('saldo');
        $this->chequesPropiosPendientes = (float) Cheque::propios()->where('estado', 'emitido')->sum('monto');

        $this->cajaAbierta = Caja::where('estado', 'abierta')->where('tipo', 'diaria')->where('user_id', auth()->id())->latest()->first();

        $this->ultimasVentas = $vigentes()->with('cliente')
            ->latest()
            ->limit(8)
            ->get();

        // Pendientes de cobro/débito con fecha de pago vencida o dentro de la ventana.
        $this->chequesPorVencer = Cheque::with(['cliente:id,razon_social', 'proveedor:id,razon_social'])
            ->whereIn('estado', ['cartera', 'depositado', 'emitido'])
            ->whereDate('fecha_pago', '<=', $hoy->copy()->addDays(self::DIAS_CHEQUES))
            ->orderBy('fecha_pago')
            ->limit(6)
            ->get();

        $this->mayoresDeudores = Cliente::where('saldo', '>', 0)
            ->orderByDesc('saldo')
            ->limit(5)
            ->get(['id', 'razon_social', 'saldo']);
    }

    public function render(): View
    {
        return view('livewire.dashboard', ['diasCheques' => self::DIAS_CHEQUES]);
    }
}
