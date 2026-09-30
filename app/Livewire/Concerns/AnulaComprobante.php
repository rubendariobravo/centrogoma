<?php

namespace App\Livewire\Concerns;

use App\Services\Anulacion\AnulacionException;

/**
 * Flujo del modal de anulación en los listados (ventas, recibos, órdenes de pago).
 * El componente implementa ejecutarAnulacion(), que delega en AnulacionService y
 * devuelve el mensaje de éxito.
 */
trait AnulaComprobante
{
    public ?int   $anularId        = null;
    public string $motivoAnulacion = '';

    abstract protected function ejecutarAnulacion(int $id, string $motivo): string;

    /** Hook para cargar datos extra del modal (ej. cobros aplicados). */
    protected function alAbrirAnulacion(int $id): void
    {
    }

    public function confirmarAnular(int $id): void
    {
        $this->anularId        = $id;
        $this->motivoAnulacion = '';
        $this->resetErrorBag();
        $this->alAbrirAnulacion($id);
    }

    public function cancelarAnular(): void
    {
        $this->anularId        = null;
        $this->motivoAnulacion = '';
    }

    public function anular(): void
    {
        if (! $this->anularId) {
            return;
        }

        if (! auth()->user()->hasRole(['super-admin', 'admin'])) {
            $this->addError('motivoAnulacion', 'No tiene permisos para anular comprobantes.');
            return;
        }

        try {
            $mensaje = $this->ejecutarAnulacion($this->anularId, $this->motivoAnulacion);
        } catch (AnulacionException $e) {
            $this->addError('motivoAnulacion', $e->getMessage());
            return;
        }

        $this->cancelarAnular();
        session()->flash('success', $mensaje);
    }
}
