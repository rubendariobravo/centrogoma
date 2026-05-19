<?php

namespace App\Livewire\Usuarios;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

#[Title('Usuarios')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    // Formulario
    public bool   $showForm = false;
    public ?int   $userId   = null;
    public string $name     = '';
    public string $email    = '';
    public string $password = '';
    public string $roleName = '';

    // Confirmación de eliminación
    public ?int $confirmingDelete = null;

    protected function rules(): array
    {
        $uniqueEmail = $this->userId
            ? "unique:users,email,{$this->userId}"
            : 'unique:users,email';

        return [
            'name'     => 'required|string|max:255',
            'email'    => "required|email|max:255|{$uniqueEmail}",
            'password' => $this->userId ? 'nullable|min:8' : 'required|min:8',
            'roleName' => 'required|exists:roles,name',
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
        $user = User::findOrFail($id);
        $this->userId   = $user->id;
        $this->name     = $user->name;
        $this->email    = $user->email;
        $this->password = '';
        $this->roleName = $user->roles->first()?->name ?? '';
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'name'  => $this->name,
            'email' => $this->email,
        ];

        if ($this->password) {
            $data['password'] = Hash::make($this->password);
        }

        if ($this->userId) {
            $user = User::findOrFail($this->userId);
            $user->update($data);
        } else {
            $user = User::create($data);
        }

        $user->syncRoles([$this->roleName]);

        $this->resetForm();
        session()->flash('success', $this->userId ? 'Usuario actualizado.' : 'Usuario creado correctamente.');
    }

    public function confirmDelete(int $id): void
    {
        $this->confirmingDelete = $id;
    }

    public function delete(): void
    {
        if ($this->confirmingDelete === auth()->id()) {
            session()->flash('error', 'No podés eliminar tu propio usuario.');
            $this->confirmingDelete = null;
            return;
        }

        User::findOrFail($this->confirmingDelete)->delete();
        $this->confirmingDelete = null;
        session()->flash('success', 'Usuario eliminado.');
    }

    public function resetForm(): void
    {
        $this->userId   = null;
        $this->name     = '';
        $this->email    = '';
        $this->password = '';
        $this->roleName = '';
        $this->showForm = false;
        $this->resetValidation();
    }

    public function render()
    {
        $usuarios = User::query()
            ->when($this->search, fn ($q) => $q
                ->where('name', 'like', "%{$this->search}%")
                ->orWhere('email', 'like', "%{$this->search}%")
            )
            ->with('roles')
            ->orderBy('name')
            ->paginate(20);

        return view('livewire.usuarios.index', [
            'usuarios' => $usuarios,
            'roles'    => Role::orderBy('name')->get(),
        ]);
    }
}
