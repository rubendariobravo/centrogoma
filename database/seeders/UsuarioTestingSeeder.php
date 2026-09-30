<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

/**
 * Usuario para pruebas manuales / automatizadas en entornos locales (rol admin = todos
 * los permisos). No se ejecuta en producción.
 *
 *   php artisan db:seed --class=UsuarioTestingSeeder
 *
 * Credenciales: testing@centrogoma.test / Test-561de9bcd11e!
 */
class UsuarioTestingSeeder extends Seeder
{
    public const EMAIL    = 'testing@centrogoma.test';
    public const PASSWORD = 'Test-561de9bcd11e!';

    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command?->warn('UsuarioTestingSeeder: omitido en producción.');
            return;
        }

        $user = User::updateOrCreate(
            ['email' => self::EMAIL],
            [
                'name'              => 'Usuario Testing',
                'password'          => Hash::make(self::PASSWORD),
                'email_verified_at' => now(),
            ]
        );

        $rol = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $user->syncRoles([$rol]);
    }
}
