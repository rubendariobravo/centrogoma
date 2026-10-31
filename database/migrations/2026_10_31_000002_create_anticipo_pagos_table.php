<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Líneas de pago de un anticipo: un anticipo puede pagarse con varios medios
     * (ej. mitad efectivo, mitad transferencia) y cada línea sale de SU propia caja
     * (el efectivo del cajón, la transferencia del banco/caja general), para que el
     * arqueo de efectivo de cada caja siga siendo real.
     *
     * Mismo patrón que `pago_ventas`.
     */
    public function up(): void
    {
        Schema::create('anticipo_pagos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('anticipo_empleado_id')->constrained('anticipo_empleados')->cascadeOnDelete();
            $table->foreignId('caja_id')->nullable()->constrained('cajas')->nullOnDelete();
            $table->string('forma_pago', 30)->default('efectivo');
            $table->decimal('monto', 12, 2);
            $table->string('referencia', 100)->nullable()->comment('Nro. de cheque, transferencia, etc.');
            $table->timestamps();

            $table->index('anticipo_empleado_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('anticipo_pagos');
    }
};
