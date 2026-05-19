<?php

namespace App\Livewire\Proveedores;

use App\Models\Proveedor;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Proveedor')]
class Form extends Component
{
    public ?int $proveedorId = null;

    public string $razon_social    = '';
    public string $nombre_fantasia = '';
    public string $cuit            = '';
    public string $telefono        = '';
    public string $celular         = '';
    public string $email           = '';
    public string $domicilio       = '';
    public string $localidad       = '';
    public string $provincia       = '';
    public string $codigo_postal   = '';
    public string $contacto        = '';
    public string $estado          = 'A';
    public string $observacion     = '';

    protected function rules(): array
    {
        return [
            'razon_social'    => 'required|string|max:200',
            'nombre_fantasia' => 'nullable|string|max:200',
            'cuit'            => 'nullable|string|max:20',
            'telefono'        => 'nullable|string|max:30',
            'celular'         => 'nullable|string|max:30',
            'email'           => 'nullable|email|max:100',
            'domicilio'       => 'nullable|string|max:200',
            'localidad'       => 'nullable|string|max:100',
            'provincia'       => 'nullable|string|max:100',
            'codigo_postal'   => 'nullable|string|max:10',
            'contacto'        => 'nullable|string|max:100',
            'estado'          => 'required|in:A,I',
            'observacion'     => 'nullable|string|max:500',
        ];
    }

    public function mount(?int $proveedor = null): void
    {
        if ($proveedor) {
            $p = Proveedor::findOrFail($proveedor);
            $this->proveedorId    = $p->id;
            $this->razon_social   = $p->razon_social;
            $this->nombre_fantasia = $p->nombre_fantasia ?? '';
            $this->cuit           = $p->cuit ?? '';
            $this->telefono       = $p->telefono ?? '';
            $this->celular        = $p->celular ?? '';
            $this->email          = $p->email ?? '';
            $this->domicilio      = $p->domicilio ?? '';
            $this->localidad      = $p->localidad ?? '';
            $this->provincia      = $p->provincia ?? '';
            $this->codigo_postal  = $p->codigo_postal ?? '';
            $this->contacto       = $p->contacto ?? '';
            $this->estado         = $p->estado;
            $this->observacion    = $p->observacion ?? '';
        }
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'razon_social'    => $this->razon_social,
            'nombre_fantasia' => $this->nombre_fantasia ?: null,
            'cuit'            => $this->cuit ?: null,
            'telefono'        => $this->telefono ?: null,
            'celular'         => $this->celular ?: null,
            'email'           => $this->email ?: null,
            'domicilio'       => $this->domicilio ?: null,
            'localidad'       => $this->localidad ?: null,
            'provincia'       => $this->provincia ?: null,
            'codigo_postal'   => $this->codigo_postal ?: null,
            'contacto'        => $this->contacto ?: null,
            'estado'          => $this->estado,
            'observacion'     => $this->observacion ?: null,
        ];

        if ($this->proveedorId) {
            Proveedor::findOrFail($this->proveedorId)->update($data);
            session()->flash('success', 'Proveedor actualizado.');
        } else {
            Proveedor::create($data);
            session()->flash('success', 'Proveedor creado.');
        }

        $this->redirect(route('proveedores'), navigate: true);
    }

    public function render()
    {
        return view('livewire.proveedores.form');
    }
}
