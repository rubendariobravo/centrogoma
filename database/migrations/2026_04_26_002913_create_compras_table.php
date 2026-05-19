<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proveedor_id')->constrained('proveedores');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('caja_id')->nullable()->constrained('cajas')->nullOnDelete();
            $table->date('fecha');
            $table->string('nro_factura', 20)->nullable();
            $table->char('letra', 1)->nullable();
            $table->double('neto')->default(0);
            $table->double('neto_21')->default(0);
            $table->double('neto_10_5')->default(0);
            $table->double('monto_iva21')->default(0);
            $table->double('monto_iva10_5')->default(0);
            $table->double('total')->default(0);
            $table->decimal('saldo', 15, 2)->default(0);
            $table->enum('estado', ['P', 'C', 'A'])->default('P');
            $table->text('observacion')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compras');
    }
};
