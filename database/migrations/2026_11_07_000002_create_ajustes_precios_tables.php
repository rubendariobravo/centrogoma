<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Historial de ajustes masivos de precios: quién, cuándo, cómo y el precio anterior de cada
 * tramo, para poder revertirlo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ajustes_precios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lista_precio_id')->constrained('listas_precios');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('modo', 20);                 // porcentaje | costo
            $table->string('tipo', 10);                 // subir | bajar
            $table->decimal('porcentaje', 8, 3);
            $table->decimal('redondeo', 10, 2)->default(0);
            $table->json('filtros')->nullable();
            $table->string('descripcion', 255);
            $table->unsignedInteger('cantidad')->default(0);
            $table->timestamp('revertido_at')->nullable();
            $table->foreignId('revertido_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('ajuste_precio_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ajuste_precio_id')->constrained('ajustes_precios')->cascadeOnDelete();
            $table->unsignedBigInteger('articulo_precio_id')->index();
            $table->unsignedBigInteger('articulo_id')->index();
            $table->decimal('precio_anterior', 15, 4);
            $table->decimal('precio_nuevo', 15, 4);
            $table->decimal('costo_anterior', 15, 4)->nullable();   // solo modo "costo"
            $table->decimal('costo_nuevo', 15, 4)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ajuste_precio_items');
        Schema::dropIfExists('ajustes_precios');
    }
};
