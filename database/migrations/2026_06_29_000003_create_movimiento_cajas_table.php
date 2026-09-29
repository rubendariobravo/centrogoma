<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Libro de movimientos de caja: cada hecho económico que afecta una caja se asienta
 * como un MovimientoCaja (ingreso/egreso). Los contadores cacheados en `cajas` son
 * una vista derivada que mantiene CajaService.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimiento_cajas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('caja_id')->constrained('cajas')->cascadeOnDelete();
            $table->date('fecha');

            // Sentido del movimiento sobre la caja
            $table->enum('tipo', ['ingreso', 'egreso']);

            // Naturaleza económica:
            // venta | cobro | pago_proveedor | gasto | anticipo | transferencia | apertura | ajuste | cheque
            $table->string('concepto', 30);

            $table->decimal('monto', 15, 2);
            $table->string('forma_pago', 30)->nullable();

            // Origen polimórfico (Venta, Recibo, OrdenPago, Gasto, TransferenciaCaja, Cheque, ...)
            $table->nullableMorphs('referencia');

            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            // Clasificación para reportes de gastos
            $table->foreignId('categoria_gasto_id')->nullable();

            // Puente hacia un futuro plan de cuentas contable (no usado todavía)
            $table->unsignedBigInteger('cuenta_contable_id')->nullable();

            $table->string('descripcion', 255)->nullable();
            $table->timestamps();

            $table->index(['caja_id', 'fecha']);
            $table->index(['concepto', 'tipo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimiento_cajas');
    }
};
