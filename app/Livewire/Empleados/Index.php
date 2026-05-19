<?php

namespace App\Livewire\Empleados;

use App\Models\Empleado;
use App\Models\User;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Empleados')]
class Index extends Component
{
    use WithPagination;

    public string $search       = '';
    public string $filterEstado = 'A';
    public string $filterRol    = '';

    // Form state
    public bool    $showForm   = false;
    public ?int    $empleadoId = null;
    public string  $nombre     = '';
    public string  $apellido   = '';
    public string  $dni        = '';
    public string  $cuil       = '';
    public string  $telefono   = '';
    public string  $celular    = '';
    public string  $email      = '';
    public string  $domicilio  = '';
    public string  $rol        = 'vendedor';
    public float   $comision   = 0;
    public ?int    $user_id    = null;
    public string  $estado     = 'A';

    public ?int $confirmingDelete = null;

    protected function rules(): array
    {
        return [
            'nombre'   => 'required|string|max:100',
            'apellido' => 'required|string|max:100',
            'dni'      => 'nullable|string|max:15',
            'cuil'     => 'nullable|string|max:20',
            'telefono' => 'nullable|string|max:30',
            'celular'  => 'nullable|string|max:30',
            'email'    => 'nullable|email|max:100',
            'domicilio' => 'nullable|string|max:200',
            'rol'      => 'required|in:vendedor,repartidor,produccion,administrativo,otro',
            'comision' => 'required|numeric|min:0|max:100',
            'user_id'  => 'nullable|exists:users,id',
            'estado'   => 'required|in:A,I',
        ];
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        $emp = Empleado::findOrFail($id);
        $this->empleadoId = $emp->id;
        $this->nombre     = $emp->nombre;
        $this->apellido   = $emp->apellido;
        $this->dni        = $emp->dni ?? '';
        $this->cuil       = $emp->cuil ?? '';
        $this->telefono   = $emp->telefono ?? '';
        $this->celular    = $emp->celular ?? '';
        $this->email      = $emp->email ?? '';
        $this->domicilio  = $emp->domicilio ?? '';
        $this->rol        = $emp->rol;
        $this->comision   = (float) $emp->comision;
        $this->user_id    = $emp->user_id;
        $this->estado     = $emp->estado;
        $this->showForm   = true;
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'nombre'    => $this->nombre,
            'apellido'  => $this->apellido,
            'dni'       => $this->dni ?: null,
            'cuil'      => $this->cuil ?: null,
            'telefono'  => $this->telefono ?: null,
            'celular'   => $this->celular ?: null,
            'email'     => $this->email ?: null,
            'domicilio' => $this->domicilio ?: null,
            'rol'       => $this->rol,
            'comision'  => $this->comision,
            'user_id'   => $this->user_id ?: null,
            'estado'    => $this->estado,
        ];

        if ($this->empleadoId) {
            Empleado::findOrFail($this->empleadoId)->update($data);
            session()->flash('success', 'Empleado actualizado.');
        } else {
            Empleado::create($data);
            session()->flash('success', 'Empleado creado.');
        }

        $this->showForm = false;
        $this->resetForm();
    }

    public function confirmDelete(int $id): void
    {
        $this->confirmingDelete = $id;
    }

    public function delete(): void
    {
        if ($this->confirmingDelete) {
            Empleado::findOrFail($this->confirmingDelete)->delete();
            $this->confirmingDelete = null;
            session()->flash('success', 'Empleado eliminado.');
        }
    }

    public function cancelDelete(): void
    {
        $this->confirmingDelete = null;
    }

    private function resetForm(): void
    {
        $this->empleadoId = null;
        $this->nombre     = '';
        $this->apellido   = '';
        $this->dni        = '';
        $this->cuil       = '';
        $this->telefono   = '';
        $this->celular    = '';
        $this->email      = '';
        $this->domicilio  = '';
        $this->rol        = 'vendedor';
        $this->comision   = 0;
        $this->user_id    = null;
        $this->estado     = 'A';
        $this->resetErrorBag();
    }

    public function render()
    {
        $empleados = Empleado::query()
            ->with('user')
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->where('nombre', 'like', "%{$this->search}%")
                  ->orWhere('apellido', 'like', "%{$this->search}%")
                  ->orWhere('dni', 'like', "%{$this->search}%");
            }))
            ->when($this->filterEstado, fn ($q) => $q->where('estado', $this->filterEstado))
            ->when($this->filterRol, fn ($q) => $q->where('rol', $this->filterRol))
            ->orderBy('apellido')->orderBy('nombre')
            ->paginate(20);

        return view('livewire.empleados.index', [
            'empleados' => $empleados,
            'usuarios'  => User::orderBy('name')->get(),
        ]);
    }
}
