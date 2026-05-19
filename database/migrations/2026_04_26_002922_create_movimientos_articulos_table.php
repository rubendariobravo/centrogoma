<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimientos_articulos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('articulo_id')->constrained('articulos');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('fecha');
            $table->enum('tipo', ['entrada', 'salida', 'ajuste', 'produccion', 'descarte', 'pesada'])->default('entrada');
            $table->double('cantidad')->default(0);
            $table->double('stock_anterior')->default(0);
            $table->double('stock_posterior')->default(0);
            $table->string('referencia_type', 100)->nullable();
            $table->unsignedBigInteger('referencia_id')->nullable();
            $table->boolean('es_minorista')->default(false);
            $table->text('observacion')->nullable();
            $table->timestamps();

            $table->index(['referencia_type', 'referencia_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos_articulos');
    }
};
