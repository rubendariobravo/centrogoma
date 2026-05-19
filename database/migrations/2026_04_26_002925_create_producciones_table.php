<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('producciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empleado_id')->nullable()->constrained('empleados')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('articulo_id')->constrained('articulos');
            $table->integer('nro_produccion')->default(0);
            $table->date('fecha');
            $table->double('cantidad_entrada')->default(0);
            $table->double('cantidad_salida')->default(0);
            $table->double('merma')->default(0);
            $table->decimal('merma_porcentaje', 6, 3)->default(0);
            $table->enum('estado', ['abierta', 'cerrada', 'anulada'])->default('abierta');
            $table->text('observacion')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('producciones');
    }
};
