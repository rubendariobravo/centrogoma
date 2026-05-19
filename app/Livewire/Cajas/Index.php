<?php

namespace App\Livewire\Cajas;

use App\Models\Caja;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Caja')]
class Index extends Component
{
    use WithPagination;

    public ?Caja $cajaAbierta = null;

    // Apertura
    public bool  $showApertura  = false;
    public float $saldo_inicial = 0;
    public string $obs_apertura = '';

    // Cierre
    public bool  $showCierre   = false;
    public float $saldo_final  = 0;
    public string $obs_cierre  = '';

    public function mount(): void
    {
        $this->cajaAbierta = Caja::where('estado', 'abierta')
            ->where('user_id', auth()->id())
            ->latest()
            ->first();
    }

    public function abrirCaja(): void
    {
        $this->validate([
            'saldo_inicial' => 'required|numeric|min:0',
        ], [
            'saldo_inicial.required' => 'Ingrese el saldo inicial.',
            'saldo_inicial.min'      => 'El saldo inicial no puede ser negativo.',
        ]);

        $caja = Caja::create([
            'user_id'        => auth()->id(),
            'fecha_apertura' => now()->toDateString(),
            'hora_apertura'  => now(),
            'saldo_inicial'  => $this->saldo_inicial,
            'total_ventas'   => 0,
            'total_cobros'   => 0,
            'total_gastos'   => 0,
            'saldo_final'    => 0,
            'estado'         => 'abierta',
            'observacion'    => $this->obs_apertura ?: null,
        ]);

        $this->cajaAbierta  = $caja;
        $this->showApertura = false;
        $this->saldo_inicial = 0;
        $this->obs_apertura = '';

        $this->dispatch('caja-updated');
        session()->flash('success', 'Caja abierta correctamente.');
    }

    public function cerrarCaja(): void
    {
        if (! $this->cajaAbierta) {
            return;
        }

        $this->validate([
            'saldo_final' => 'required|numeric|min:0',
        ], [
            'saldo_final.required' => 'Ingrese el saldo final.',
            'saldo_final.min'      => 'El saldo final no puede ser negativo.',
        ]);

        $this->cajaAbierta->update([
            'fecha_cierre' => now()->toDateString(),
            'hora_cierre'  => now(),
            'saldo_final'  => $this->saldo_final,
            'estado'       => 'cerrada',
            'observacion'  => $this->obs_cierre ?: $this->cajaAbierta->observacion,
        ]);

        $this->cajaAbierta = null;
        $this->showCierre  = false;
        $this->saldo_final = 0;
        $this->obs_cierre  = '';

        $this->dispatch('caja-updated');
        session()->flash('success', 'Caja cerrada correctamente.');
    }

    public function render()
    {
        $historial = Caja::with('user')
            ->where('user_id', auth()->id())
            ->orderByDesc('fecha_apertura')
            ->orderByDesc('id')
            ->paginate(15);

        return view('livewire.cajas.index', [
            'historial' => $historial,
        ]);
    }
}
