<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Comprobante de saldo inicial: venta/compra sin renglones que representa la deuda de
 * cuenta corriente arrastrada del sistema Delphi (`centrogoma:importar-saldos`). Se cobra con
 * recibo o se paga con orden de pago como cualquier comprobante, pero no es una venta ni
 * una compra real: queda fuera de totales y estadísticas (scope `comerciales()`).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->boolean('saldo_inicial')->default(false)->after('tipo_comprobante');
        });
        Schema::table('compras', function (Blueprint $table) {
            $table->boolean('saldo_inicial')->default(false)->after('forma_pago');
        });
    }

    public function down(): void
    {
        Schema::table('ventas', fn (Blueprint $table) => $table->dropColumn('saldo_inicial'));
        Schema::table('compras', fn (Blueprint $table) => $table->dropColumn('saldo_inicial'));
    }
};
