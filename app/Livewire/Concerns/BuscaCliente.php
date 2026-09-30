<?php

namespace App\Livewire\Concerns;

use App\Models\Cliente;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Buscador de clientes server-side para componentes Livewire (reemplaza al <select> con los
 * ~6.6K clientes). Va de la mano con <x-buscador-cliente />.
 *
 * El componente define la propiedad donde queda el cliente elegido (default `cliente_id`,
 * se cambia sobreescribiendo clienteCampo()). Al seleccionar se dispara el hook
 * updated{Campo}() del componente, igual que lo hacía el wire:model.live del select.
 */
trait BuscaCliente
{
    public string $clienteBuscar = '';

    private const CLIENTE_RESULTADOS = 15;

    protected function clienteCampo(): string
    {
        return 'cliente_id';
    }

    /** @return Collection<int, Cliente> */
    public function clientesEncontrados(): Collection
    {
        $texto = trim($this->clienteBuscar);
        if (mb_strlen($texto) < 2) {
            return collect();
        }

        $digitos = preg_replace('/\D/', '', $texto);

        return Cliente::query()
            ->where('estado', 'A')
            ->where(function ($q) use ($texto, $digitos) {
                // Todas las palabras deben aparecer (en cualquier orden) en razón social o fantasía.
                $q->where(function ($w) use ($texto) {
                    foreach (preg_split('/\s+/', $texto) as $palabra) {
                        $w->where(fn ($p) => $p->where('razon_social', 'like', "%{$palabra}%")
                            ->orWhere('nombre_fantasia', 'like', "%{$palabra}%"));
                    }
                });

                if (strlen($digitos) >= 3 && $digitos === preg_replace('/[\s\-.]/', '', $texto)) {
                    $q->orWhereRaw("REPLACE(REPLACE(cuit, '-', ''), '.', '') LIKE ?", ["{$digitos}%"])
                      ->orWhere('dni', 'like', "{$digitos}%");
                }
            })
            ->orderBy('razon_social')
            ->limit(self::CLIENTE_RESULTADOS)
            ->get(['id', 'razon_social', 'nombre_fantasia', 'cuit', 'dni', 'localidad', 'saldo']);
    }

    public function clienteSeleccionado(): ?Cliente
    {
        $id = $this->{$this->clienteCampo()};

        return $id ? Cliente::find($id) : null;
    }

    public function seleccionarCliente(?int $id): void
    {
        $campo = $this->clienteCampo();
        $this->{$campo}      = $id ?: null;
        $this->clienteBuscar = '';

        // Mismo efecto que el wire:model.live del <select> anterior.
        $hook = 'updated' . Str::studly($campo);
        if (method_exists($this, $hook)) {
            $this->{$hook}($this->{$campo});
        }
    }
}
