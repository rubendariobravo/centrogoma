<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Formas de pago configurables (antes hardcodeadas). Consolida las 3 migraciones de
 * avícola (create + change_enum + costo_financiero) en una sola tabla limpia.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('formas_pago', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 30)->unique();
            $table->string('descripcion', 100);
            $table->boolean('es_efectivo')->default(false);
            $table->boolean('es_cuenta_corriente')->default(false);
            // Recargo financiero (%) aplicable a esta forma de pago (ej. tarjeta).
            $table->decimal('costo_financiero', 8, 4)->default(0);
            $table->boolean('activo')->default(true);
            $table->unsignedSmallInteger('orden')->default(0);
            $table->timestamps();
        });

        DB::table('formas_pago')->insert([
            ['codigo' => 'efectivo',         'descripcion' => 'Efectivo',         'es_efectivo' => true,  'es_cuenta_corriente' => false, 'costo_financiero' => 0, 'activo' => true, 'orden' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['codigo' => 'transferencia',    'descripcion' => 'Transferencia',    'es_efectivo' => false, 'es_cuenta_corriente' => false, 'costo_financiero' => 0, 'activo' => true, 'orden' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['codigo' => 'tarjeta',          'descripcion' => 'Tarjeta',          'es_efectivo' => false, 'es_cuenta_corriente' => false, 'costo_financiero' => 0, 'activo' => true, 'orden' => 3, 'created_at' => now(), 'updated_at' => now()],
            ['codigo' => 'cheque',           'descripcion' => 'Cheque',           'es_efectivo' => false, 'es_cuenta_corriente' => false, 'costo_financiero' => 0, 'activo' => true, 'orden' => 4, 'created_at' => now(), 'updated_at' => now()],
            ['codigo' => 'cuenta_corriente', 'descripcion' => 'Cuenta Corriente', 'es_efectivo' => false, 'es_cuenta_corriente' => true,  'costo_financiero' => 0, 'activo' => true, 'orden' => 5, 'created_at' => now(), 'updated_at' => now()],
            ['codigo' => 'otro',             'descripcion' => 'Otro',             'es_efectivo' => false, 'es_cuenta_corriente' => false, 'costo_financiero' => 0, 'activo' => true, 'orden' => 6, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('formas_pago');
    }
};
