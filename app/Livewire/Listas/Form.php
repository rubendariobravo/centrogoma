<?php

namespace App\Livewire\Listas;

use App\Models\ListaPrecio;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Lista de precios')]
class Form extends Component
{
    public ?int $listaId = null;

    public string $codigo              = '';
    public string $nombre              = '';
    public string $descripcion         = '';
    public string $tipo                = 'minorista';
    public bool   $es_default          = false;
    public float  $recargo_no_efectivo = 0;
    public float  $descuento_efectivo  = 0;
    public int    $orden               = 0;
    public bool   $activo              = true;

    protected function rules(): array
    {
        return [
            'codigo'              => ['required', 'string', 'max:30', Rule::unique('listas_precios', 'codigo')->ignore($this->listaId)],
            'nombre'              => 'required|string|max:100',
            'descripcion'         => 'nullable|string|max:250',
            'tipo'                => 'required|in:minorista,mayorista,especial',
            'es_default'          => 'boolean',
            'recargo_no_efectivo' => 'required|numeric|min:0|max:100',
            'descuento_efectivo'  => 'required|numeric|min:0|max:100',
            'orden'               => 'required|integer|min:0',
            'activo'              => 'boolean',
        ];
    }

    protected function messages(): array
    {
        return [
            'codigo.unique'   => 'Ya existe una lista con ese código.',
            'codigo.required' => 'El código es obligatorio.',
            'nombre.required' => 'El nombre es obligatorio.',
        ];
    }

    public function mount(?int $lista = null): void
    {
        if ($lista) {
            $l = ListaPrecio::findOrFail($lista);
            $this->listaId             = $l->id;
            $this->codigo              = $l->codigo;
            $this->nombre              = $l->nombre;
            $this->descripcion         = $l->descripcion ?? '';
            $this->tipo                = $l->tipo;
            $this->es_default          = (bool) $l->es_default;
            $this->recargo_no_efectivo = (float) $l->recargo_no_efectivo;
            $this->descuento_efectivo  = (float) $l->descuento_efectivo;
            $this->orden               = (int) $l->orden;
            $this->activo              = (bool) $l->activo;
        } else {
            // Sugerir orden = max + 1 al crear
            $this->orden = ((int) ListaPrecio::max('orden')) + 1;
        }
    }

    public function save(): void
    {
        $this->validate();

        // Si la marcamos como default, desmarcamos cualquier otra default existente.
        if ($this->es_default) {
            ListaPrecio::where('es_default', true)
                ->when($this->listaId, fn ($q) => $q->where('id', '!=', $this->listaId))
                ->update(['es_default' => false]);
        }

        $data = [
            'codigo'              => strtoupper(trim($this->codigo)),
            'nombre'              => trim($this->nombre),
            'descripcion'         => $this->descripcion ?: null,
            'tipo'                => $this->tipo,
            'es_default'          => $this->es_default,
            'recargo_no_efectivo' => $this->recargo_no_efectivo,
            'descuento_efectivo'  => $this->descuento_efectivo,
            'orden'               => $this->orden,
            'activo'              => $this->activo,
        ];

        if ($this->listaId) {
            ListaPrecio::findOrFail($this->listaId)->update($data);
            session()->flash('success', 'Lista actualizada.');
        } else {
            ListaPrecio::create($data);
            session()->flash('success', 'Lista creada.');
        }

        $this->redirect(route('listas-precios'), navigate: true);
    }

    public function render()
    {
        return view('livewire.listas.form');
    }
}
