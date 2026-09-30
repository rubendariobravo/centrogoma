<?php

namespace App\Livewire;

use App\Models\Articulo;
use App\Models\Caja;
use App\Models\Cliente;
use App\Models\Venta;
use Illuminate\View\View;
use Livewire\Component;

class Dashboard extends Component
{
    public int $ventasHoy = 0;
    public float $totalHoy = 0;
    public int $ventasSemana = 0;
    public float $totalSemana = 0;
    public int $clientesActivos = 0;
    public int $articulosActivos = 0;
    public int $stockCritico = 0;
    public ?Caja $cajaAbierta = null;
    public $ultimasVentas;
    public $alertasStock;

    public function mount(): void
    {
        $hoy    = today();
        $inicio = now()->startOfWeek();

        $this->ventasHoy    = Venta::comerciales()->whereDate('fecha', $hoy)->where('estado', '!=', 'A')->count();
        $this->totalHoy     = (float) Venta::comerciales()->whereDate('fecha', $hoy)->where('estado', '!=', 'A')->sum('total');
        $this->ventasSemana = Venta::comerciales()->whereBetween('fecha', [$inicio, $hoy])->where('estado', '!=', 'A')->count();
        $this->totalSemana  = (float) Venta::comerciales()->whereBetween('fecha', [$inicio, $hoy])->where('estado', '!=', 'A')->sum('total');

        $this->clientesActivos  = Cliente::where('estado', 'A')->count();
        $this->articulosActivos = Articulo::where('estado', 'A')->count();
        $this->stockCritico     = Articulo::where('estado', 'A')
            ->where('stock_minimo', '>', 0)
            ->whereColumn('stock', '<=', 'stock_minimo')
            ->count();

        $this->cajaAbierta = Caja::where('estado', 'abierta')->where('tipo', 'diaria')->where('user_id', auth()->id())->latest()->first();

        $this->ultimasVentas = Venta::comerciales()->with('cliente')
            ->where('estado', '!=', 'A')
            ->latest()
            ->limit(8)
            ->get();

        $this->alertasStock = Articulo::where('estado', 'A')
            ->where('stock_minimo', '>', 0)
            ->whereColumn('stock', '<=', 'stock_minimo')
            ->orderBy('stock')
            ->limit(6)
            ->get(['id', 'descripcion', 'codigo', 'stock', 'stock_minimo', 'unidad_medida']);
    }

    public function render(): View
    {
        return view('livewire.dashboard');
    }
}
