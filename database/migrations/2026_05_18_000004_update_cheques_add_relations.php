<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cheques', function (Blueprint $table) {
            // Serie del cheque (talonario)
            $table->string('serie', 10)->nullable()->after('banco');

            // Estado para cheques emitidos: arranca en pendiente (no en_cartera)
            // Modificamos el enum para agregar 'pendiente'
            $table->enum('estado', [
                'en_cartera', 'pendiente', 'depositado',
                'cobrado', 'rechazado', 'endosado', 'anulado',
            ])->default('en_cartera')->change();

            // Origen del cheque recibido: pago de venta
            $table->foreignId('pago_venta_id')
                  ->nullable()
                  ->after('proveedor_id')
                  ->constrained('pago_ventas')
                  ->nullOnDelete();

            // Origen del cheque recibido: cobro de recibo (CC)
            $table->foreignId('recibo_id')
                  ->nullable()
                  ->after('pago_venta_id')
                  ->constrained('recibos')
                  ->nullOnDelete();

            // Origen del cheque emitido: orden de pago a proveedor
            $table->foreignId('orden_pago_id')
                  ->nullable()
                  ->after('recibo_id')
                  ->constrained('ordenes_pago')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('cheques', function (Blueprint $table) {
            $table->dropForeign(['pago_venta_id']);
            $table->dropForeign(['recibo_id']);
            $table->dropForeign(['orden_pago_id']);
            $table->dropColumn(['serie', 'pago_venta_id', 'recibo_id', 'orden_pago_id']);
            $table->enum('estado', [
                'en_cartera', 'depositado', 'cobrado', 'rechazado', 'endosado', 'anulado',
            ])->default('en_cartera')->change();
        });
    }
};
