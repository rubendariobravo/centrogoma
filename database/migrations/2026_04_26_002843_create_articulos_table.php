<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('articulos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marca_id')->nullable()->constrained('marcas')->nullOnDelete();
            $table->foreignId('rubro_id')->nullable()->constrained('rubros')->nullOnDelete();
            $table->string('codigo', 20)->default('SINCODIGO');
            $table->string('descripcion', 250)->nullable();
            $table->string('imagen_url')->nullable();
            $table->string('unidad_medida', 20)->default('unidad');
            $table->string('presentacion', 200)->nullable();
            $table->string('presentacion_caja', 200)->nullable();
            $table->decimal('cantidad_caja', 10, 2)->nullable();
            $table->double('precio_costo')->default(0);
            $table->double('precio_unitario')->default(0);
            $table->double('precio_unitario_caja')->default(0);
            $table->double('stock')->default(0);
            $table->double('stock_minimo')->default(0);
            $table->double('stock_inicial')->default(0);
            $table->decimal('alicuota_iva', 5, 2)->default(21);
            $table->double('margen')->default(0);
            $table->double('bonificacion')->default(0);
            $table->boolean('modifica_stock')->default(true);
            $table->boolean('art_mayorista')->default(false);
            $table->char('estado', 1)->default('A');
            $table->date('fecha_modif')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articulos');
    }
};
