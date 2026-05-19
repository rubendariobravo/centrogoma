<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Agregamos como nullable para poder backfillear, despues lo dejamos NOT NULL.
        Schema::table('articulo_precios', function (Blueprint $table) {
            $table->foreignId('lista_precio_id')
                ->nullable()
                ->after('articulo_id')
                ->constrained('listas_precios')
                ->cascadeOnDelete();
            $table->string('unidad_tramo', 20)->nullable()->after('precio');
        });

        // Backfill: todos los tramos existentes pasan a la lista default.
        $defaultId = DB::table('listas_precios')->where('es_default', true)->value('id');
        if ($defaultId) {
            DB::table('articulo_precios')
                ->whereNull('lista_precio_id')
                ->update(['lista_precio_id' => $defaultId]);
        }

        // Cambiamos a NOT NULL una vez backfilled.
        // En SQLite Laravel 12 maneja esto recreando la tabla.
        Schema::table('articulo_precios', function (Blueprint $table) {
            $table->foreignId('lista_precio_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('articulo_precios', function (Blueprint $table) {
            $table->dropForeign(['lista_precio_id']);
            $table->dropColumn(['lista_precio_id', 'unidad_tramo']);
        });
    }
};
