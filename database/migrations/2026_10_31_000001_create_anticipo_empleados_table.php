<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Anticipos de sueldo (portado de avícola). El cabezal guarda la línea de pago
     * principal (caja / forma / referencia) denormalizada; el detalle real de cómo
     * se pagó está en `anticipo_pagos`.
     */
    public function up(): void
    {
        Schema::create('anticipo_empleados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empleado_id')->constrained('empleados')->cascadeOnDelete();
            // Caja de la línea de pago principal (egreso real en el libro de movimientos).
            $table->foreignId('caja_id')->nullable()->constrained('cajas')->nullOnDelete();
            $table->date('fecha');
            $table->decimal('monto', 12, 2);
            $table->string('forma_pago', 30)->default('efectivo');
            $table->string('referencia', 100)->nullable()->comment('Nro. de cheque, transferencia, etc.');
            $table->text('observaciones')->nullable();
            $table->enum('estado', ['pendiente', 'aprobado', 'rechazado', 'liquidado'])->default('pendiente');
            $table->foreignId('aprobado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->date('fecha_aprobacion')->nullable();
            $table->text('motivo_rechazo')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('anticipo_empleados');
    }
};
