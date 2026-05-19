<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('det_ventas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venta_id')->constrained('ventas')->cascadeOnDelete();
            $table->foreignId('articulo_id')->constrained('articulos');
            $table->double('cantidad')->default(0);
            $table->double('precio_unitario')->default(0);
            $table->decimal('alicuota_iva', 5, 2)->default(21);
            $table->double('descuento')->default(0);
            $table->double('subtotal')->default(0);
            $table->double('subtotal_iva')->default(0);
            $table->enum('tipo_venta', ['CAJA', 'PESO'])->default('CAJA');
            $table->string('descripcion', 250)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('det_ventas');
    }
};
