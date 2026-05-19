<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('det_produccion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('produccion_id')->constrained('producciones')->cascadeOnDelete();
            $table->foreignId('articulo_id')->constrained('articulos');
            $table->enum('tipo', ['E', 'I'])->comment('E=insumo/egreso, I=producto/ingreso');
            $table->double('cantidad')->default(0);
            $table->decimal('precio_costo', 12, 4)->default(0);
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('rendimiento', 6, 3)->nullable()->comment('% sobre total salida, solo tipo I');
            $table->smallInteger('orden')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('det_produccion');
    }
};
