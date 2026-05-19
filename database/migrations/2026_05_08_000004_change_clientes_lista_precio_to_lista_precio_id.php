<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // El campo viejo `lista_precio` (entero suelto sin FK) se reemplaza por
        // `lista_precio_id` (FK a listas_precios, nullable).
        // null => usar lista default del sistema.
        // No se migran datos: el cliente confirmo que arranca con listas nuevas.
        Schema::table('clientes', function (Blueprint $table) {
            $table->foreignId('lista_precio_id')
                ->nullable()
                ->after('empleado_id')
                ->constrained('listas_precios')
                ->nullOnDelete();
        });

        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn('lista_precio');
        });
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->integer('lista_precio')->default(1)->after('empleado_id');
        });

        Schema::table('clientes', function (Blueprint $table) {
            $table->dropForeign(['lista_precio_id']);
            $table->dropColumn('lista_precio_id');
        });
    }
};
