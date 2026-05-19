<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ordenes_pago', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proveedor_id')->constrained('proveedores');
            $table->foreignId('caja_id')->nullable()->constrained('cajas')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->integer('nro_orden')->default(0);
            $table->date('fecha');
            $table->decimal('total', 15, 2)->default(0);
            $table->enum('forma_pago', ['efectivo', 'transferencia', 'cheque', 'tarjeta', 'otro'])->default('efectivo');
            $table->string('referencia', 100)->nullable();
            $table->text('observacion')->nullable();
            $table->enum('estado', ['P', 'C', 'A'])->default('C');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('detalle_ordenes_pago', function (Blueprint $table) {
            $table->id();
            $table->foreignId('orden_pago_id')->constrained('ordenes_pago')->cascadeOnDelete();
            $table->foreignId('compra_id')->constrained('compras');
            $table->decimal('monto', 15, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detalle_ordenes_pago');
        Schema::dropIfExists('ordenes_pago');
    }
};
