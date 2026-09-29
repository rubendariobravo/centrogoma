<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Comprobante de transferencia de dinero entre dos cajas. CajaService::transferir()
 * crea este registro + dos MovimientoCaja (egreso en origen, ingreso en destino).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transferencias_caja', function (Blueprint $table) {
            $table->id();
            $table->foreignId('caja_origen_id')->constrained('cajas');
            $table->foreignId('caja_destino_id')->constrained('cajas');
            $table->date('fecha');
            $table->decimal('monto', 15, 2);
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('descripcion', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transferencias_caja');
    }
};
