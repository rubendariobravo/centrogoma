<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('producciones', function (Blueprint $table) {
            // Eliminar columna de artículo único (pasa a det_produccion)
            $table->dropForeign(['articulo_id']);
            $table->dropColumn(['articulo_id', 'cantidad_entrada', 'cantidad_salida', 'merma', 'merma_porcentaje']);

            // Nuevos campos en cabecera
            $table->enum('tipo_produccion', ['troceo', 'milanesas', 'otras'])->default('troceo')->after('fecha');
            $table->decimal('costo_total', 12, 2)->default(0)->after('tipo_produccion');
            $table->decimal('costo_promedio', 12, 4)->default(0)->after('costo_total');
            $table->decimal('ganancia', 12, 2)->default(0)->after('costo_promedio');
        });
    }

    public function down(): void
    {
        Schema::table('producciones', function (Blueprint $table) {
            $table->dropColumn(['tipo_produccion', 'costo_total', 'costo_promedio', 'ganancia']);
            $table->foreignId('articulo_id')->constrained('articulos');
            $table->double('cantidad_entrada')->default(0);
            $table->double('cantidad_salida')->default(0);
            $table->double('merma')->default(0);
            $table->decimal('merma_porcentaje', 6, 3)->default(0);
        });
    }
};
