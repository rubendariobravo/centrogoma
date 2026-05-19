<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Override "lista distinta por rubro para este cliente":
        // - rubro_id + lista_precio_id (sin precio_especial) => para ese rubro, usar otra lista
        // - articulo_id + precio_especial (sin lista) => precio fijo (caso ya existente)
        // - rubro_id + bonificacion (sin lista, sin precio) => bonif por rubro (caso ya existente)
        Schema::table('cliente_precios', function (Blueprint $table) {
            $table->foreignId('lista_precio_id')
                ->nullable()
                ->after('rubro_id')
                ->constrained('listas_precios')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('cliente_precios', function (Blueprint $table) {
            $table->dropForeign(['lista_precio_id']);
            $table->dropColumn('lista_precio_id');
        });
    }
};
