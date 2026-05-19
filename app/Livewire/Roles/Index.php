<?php

namespace App\Livewire\Roles;

use Livewire\Attributes\Title;
use Livewire\Component;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

#[Title('Roles y permisos')]
class Index extends Component
{
    public bool   $showForm = false;
    public ?int   $roleId   = null;
    public string $roleName = '';
    public array  $selectedPermissions = [];

    public ?int $confirmingDelete = null;

    protected $rules = [
        'roleName' => 'required|string|max:100',
    ];

    public function openCreate(): void
    {
        $this->roleId              = null;
        $this->roleName            = '';
        $this->selectedPermissions = [];
        $this->showForm            = true;
        $this->resetValidation();
    }

    public function openEdit(int $id): void
    {
        $role = Role::with('permissions')->findOrFail($id);
        $this->roleId              = $role->id;
        $this->roleName            = $role->name;
        $this->selectedPermissions = $role->permissions->pluck('name')->toArray();
        $this->showForm            = true;
        $this->resetValidation();
    }

    public function save(): void
    {
        $this->validate([
            'roleName' => 'required|string|max:100|unique:roles,name' . ($this->roleId ? ",{$this->roleId}" : ''),
        ]);

        if ($this->roleId) {
            $role = Role::findOrFail($this->roleId);
            $role->update(['name' => $this->roleName]);
        } else {
            $role = Role::create(['name' => $this->roleName, 'guard_name' => 'web']);
        }

        $role->syncPermissions($this->selectedPermissions);

        $this->showForm = false;
        $this->resetValidation();
        session()->flash('success', $this->roleId ? 'Rol actualizado.' : 'Rol creado correctamente.');
    }

    public function confirmDelete(int $id): void
    {
        $this->confirmingDelete = $id;
    }

    public function delete(): void
    {
        $role = Role::find($this->confirmingDelete);
        if ($role && in_array($role->name, ['admin', 'supervisor'])) {
            session()->flash('error', 'No se puede eliminar el rol '.$role->name.'.');
            $this->confirmingDelete = null;
            return;
        }
        $role?->delete();
        $this->confirmingDelete = null;
        session()->flash('success', 'Rol eliminado.');
    }

    public function render()
    {
        return view('livewire.roles.index', [
            'roles'      => Role::with('permissions')->withCount('users')->orderBy('name')->get(),
            'permissions'=> Permission::orderBy('name')->get(),
        ]);
    }
}
