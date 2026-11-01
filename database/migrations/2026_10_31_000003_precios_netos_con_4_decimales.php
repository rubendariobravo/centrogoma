<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Los precios se cargan FINALES (con IVA) y se guardan NETOS. Con 2 decimales el neto no
 * reproduce el final: $15.000 → 12.396,69 → 14.999,99. Con 4 decimales vuelve exacto
 * (12.396,6942 × 1,21 = 15.000,00).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articulo_precios', function (Blueprint $table) {
            $table->decimal('precio', 15, 4)->change();
        });
        Schema::table('cliente_precios', function (Blueprint $table) {
            $table->decimal('precio_especial', 15, 4)->nullable()->change();
        });

        // Tramos de la lista default importados del Delphi: el importador ya calculó el neto con
        // 4 decimales en articulos.precio_unitario, pero la columna lo truncó. Se recupera solo
        // donde el tramo sigue igual a lo importado (no se pisan precios editados en la app).
        $default = DB::table('listas_precios')->where('es_default', true)->value('id');
        if ($default) {
            DB::table('articulo_precios as ap')
                ->join('articulos as a', 'a.id', '=', 'ap.articulo_id')
                ->where('ap.lista_precio_id', $default)
                ->whereNotNull('a.legacy_id')
                ->whereRaw('ap.precio = ROUND(a.precio_unitario, 2)')
                ->update(['ap.precio' => DB::raw('ROUND(a.precio_unitario, 4)')]);
        }
    }

    public function down(): void
    {
        Schema::table('articulo_precios', function (Blueprint $table) {
            $table->decimal('precio', 15, 2)->change();
        });
        Schema::table('cliente_precios', function (Blueprint $table) {
            $table->decimal('precio_especial', 15, 2)->nullable()->change();
        });
    }
};
