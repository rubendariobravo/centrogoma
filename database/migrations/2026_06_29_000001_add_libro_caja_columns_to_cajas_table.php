<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Amplía la tabla `cajas` para soportar el libro de movimientos portado desde avícola:
 * identidad (nombre/tipo) + contadores cacheados de anticipos, ajustes, transferencias
 * entre cajas y valores en cheques. user_id pasa a nullable (cajas generales/bancos no
 * pertenecen a un usuario).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cajas', function (Blueprint $table) {
            // Identidad: cajas con nombre (general, bancos, etc.)
            $table->string('nombre', 100)->nullable()->after('user_id');
            $table->enum('tipo', ['diaria', 'general', 'banco'])->default('diaria')->after('nombre');

            // Contadores cacheados adicionales
            $table->decimal('total_anticipos', 15, 2)->default(0)->after('total_gastos');
            $table->decimal('total_ajustes_ingreso', 15, 2)->default(0)->after('total_anticipos');
            $table->decimal('total_ajustes_egreso', 15, 2)->default(0)->after('total_ajustes_ingreso');
            $table->decimal('total_transferencias_entrada', 15, 2)->default(0)->after('total_ajustes_egreso');
            $table->decimal('total_transferencias_salida', 15, 2)->default(0)->after('total_transferencias_entrada');
            // Valores en cheques que ingresan (acreditación de terceros) / egresan (débito de
            // propios). Espejan a las transferencias: suman al saldo esperado pero NO al efectivo.
            $table->decimal('total_cheques_entrada', 15, 2)->default(0)->after('total_transferencias_salida');
            $table->decimal('total_cheques_salida', 15, 2)->default(0)->after('total_cheques_entrada');
        });

        // user_id pasa a nullable en una operación separada (change requiere doctrine/dbal en <L11,
        // pero L12 lo maneja nativo).
        Schema::table('cajas', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('cajas', function (Blueprint $table) {
            $table->dropColumn([
                'nombre',
                'tipo',
                'total_anticipos',
                'total_ajustes_ingreso',
                'total_ajustes_egreso',
                'total_transferencias_entrada',
                'total_transferencias_salida',
                'total_cheques_entrada',
                'total_cheques_salida',
            ]);
        });
    }
};
