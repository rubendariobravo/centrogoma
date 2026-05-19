<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ventas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('empleado_id')->nullable()->constrained('empleados')->nullOnDelete();
            $table->foreignId('caja_id')->nullable()->constrained('cajas')->nullOnDelete();
            $table->foreignId('doc_afip_id')->nullable()->constrained('doc_afip')->nullOnDelete();
            $table->integer('nro_venta')->default(0);
            $table->date('fecha');
            $table->time('hora')->nullable();
            $table->double('neto')->default(0);
            $table->double('descuento')->default(0);
            $table->double('neto_21')->default(0);
            $table->double('neto_10_5')->default(0);
            $table->double('monto_iva21')->default(0);
            $table->double('monto_iva10_5')->default(0);
            $table->double('total')->default(0);
            $table->decimal('saldo', 15, 2)->default(0);
            $table->double('percepcion')->default(0);
            $table->double('percepiva21')->default(0);
            $table->double('percepiva10_5')->default(0);
            $table->double('porcpercep')->default(0);
            $table->integer('nro_factura')->nullable();
            $table->char('letra_comprobante', 1)->nullable()->default('B');
            $table->integer('tipo_cbte')->nullable();
            $table->integer('pto_vta')->nullable();
            $table->string('cae', 20)->nullable();
            $table->string('vto_cae', 20)->nullable();
            $table->enum('estado', ['P', 'C', 'A'])->default('P');
            $table->string('tipo', 20)->default('V');
            $table->string('moneda', 3)->default('ARS');
            $table->double('cotizacion')->nullable();
            $table->string('sucursal', 20)->nullable();
            $table->string('cliente_vtadiaria', 200)->nullable();
            $table->string('dni_vtadiaria', 20)->nullable();
            $table->string('domicilio_vtadiaria', 200)->nullable();
            $table->enum('tipo_comprobante', ['factura', 'nota_credito', 'nota_debito'])->default('factura');
            $table->foreignId('factura_original_id')->nullable()->constrained('ventas')->nullOnDelete();
            $table->text('observacion')->nullable();
            $table->double('totalanulado')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ventas');
    }
};
