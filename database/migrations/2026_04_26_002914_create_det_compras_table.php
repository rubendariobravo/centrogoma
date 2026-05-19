<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('det_compras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('compra_id')->constrained('compras')->cascadeOnDelete();
            $table->foreignId('articulo_id')->constrained('articulos');
            $table->double('cantidad')->default(0);
            $table->double('precio_unitario')->default(0);
            $table->decimal('alicuota_iva', 5, 2)->default(21);
            $table->double('subtotal')->default(0);
            $table->double('subtotal_iva')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('det_compras');
    }
};
