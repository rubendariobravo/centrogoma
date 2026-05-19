<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pesadas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('articulo_id')->constrained('articulos');
            $table->integer('nro_pesada')->default(0);
            $table->date('fecha');
            $table->double('stock_sistema')->default(0);
            $table->double('stock_real')->default(0);
            $table->double('diferencia')->default(0);
            $table->decimal('merma_porcentaje', 6, 3)->default(0);
            $table->enum('estado', ['pendiente', 'aplicada', 'anulada'])->default('pendiente');
            $table->text('observacion')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pesadas');
    }
};
