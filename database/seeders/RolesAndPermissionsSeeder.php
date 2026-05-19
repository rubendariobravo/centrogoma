<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            // Dashboard
            'ver_dashboard',

            // Artículos
            'ver_articulos', 'crear_articulos', 'editar_articulos', 'eliminar_articulos',

            // Clientes
            'ver_clientes', 'crear_clientes', 'editar_clientes', 'eliminar_clientes',

            // Proveedores
            'ver_proveedores', 'crear_proveedores', 'editar_proveedores', 'eliminar_proveedores',

            // Ventas
            'ver_ventas', 'crear_ventas', 'editar_ventas', 'eliminar_ventas', 'facturar_ventas',

            // Compras
            'ver_compras', 'crear_compras', 'editar_compras', 'eliminar_compras',

            // Cajas
            'ver_cajas', 'abrir_cajas', 'cerrar_cajas',

            // Recibos
            'ver_recibos', 'crear_recibos', 'eliminar_recibos',

            // Producción
            'ver_produccion', 'crear_produccion', 'cerrar_produccion',

            // Stock / Pesadas
            'ver_stock', 'ajustar_stock', 'ver_pesadas', 'crear_pesadas',

            // Informes
            'ver_informes', 'exportar_informes',

            // Usuarios
            'ver_usuarios', 'crear_usuarios', 'editar_usuarios', 'eliminar_usuarios',

            // Roles
            'ver_roles', 'crear_roles', 'editar_roles', 'eliminar_roles',

            // Config
            'ver_config', 'editar_config',

            // Auditorías
            'ver_auditorias',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        // Roles
        $admin      = Role::findOrCreate('admin', 'web');
        $supervisor = Role::findOrCreate('supervisor', 'web');
        $cajero     = Role::findOrCreate('cajero', 'web');
        $vendedor   = Role::findOrCreate('vendedor', 'web');
        $almacen    = Role::findOrCreate('almacen', 'web');

        // Admin: todos los permisos
        $admin->syncPermissions(Permission::all());

        // Supervisor: todo menos config y usuarios
        $supervisor->syncPermissions(Permission::whereNotIn('name', [
            'ver_usuarios', 'crear_usuarios', 'editar_usuarios', 'eliminar_usuarios',
            'ver_roles', 'crear_roles', 'editar_roles', 'eliminar_roles',
            'ver_config', 'editar_config',
        ])->get());

        // Cajero
        $cajero->syncPermissions([
            'ver_dashboard',
            'ver_clientes', 'crear_clientes', 'editar_clientes',
            'ver_ventas', 'crear_ventas',
            'facturar_ventas',
            'ver_recibos', 'crear_recibos',
            'ver_cajas', 'abrir_cajas', 'cerrar_cajas',
            'ver_informes',
        ]);

        // Vendedor
        $vendedor->syncPermissions([
            'ver_dashboard',
            'ver_clientes', 'crear_clientes',
            'ver_articulos',
            'ver_ventas', 'crear_ventas',
        ]);

        // Almacén
        $almacen->syncPermissions([
            'ver_dashboard',
            'ver_articulos',
            'ver_stock', 'ajustar_stock',
            'ver_pesadas', 'crear_pesadas',
            'ver_produccion', 'crear_produccion', 'cerrar_produccion',
            'ver_compras',
        ]);

        // Usuario admin por defecto
        $adminUser = User::firstOrNew(['email' => 'admin@avicola.test']);
        if (!$adminUser->exists) {
            $adminUser->name     = 'Administrador';
            $adminUser->password = bcrypt('password');
            $adminUser->saveQuietly();
        }
        $adminUser->syncRoles(['admin']);

        $this->command->info('✓ Roles y permisos creados.');
        $this->command->info('  admin@avicola.test / password');
    }
}
