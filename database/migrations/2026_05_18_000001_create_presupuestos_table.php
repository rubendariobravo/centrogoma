<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('presupuestos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('lista_precio_id')->nullable()->constrained('listas_precios')->nullOnDelete();
            $table->foreignId('venta_id')->nullable()->constrained('ventas')->nullOnDelete();
            $table->integer('nro_presupuesto')->default(0);
            $table->date('fecha');
            $table->date('fecha_vencimiento')->nullable();
            $table->integer('dias_validez')->default(7);
            $table->double('neto')->default(0);
            $table->double('neto_21')->default(0);
            $table->double('neto_10_5')->default(0);
            $table->double('monto_iva21')->default(0);
            $table->double('monto_iva10_5')->default(0);
            $table->double('descuento')->default(0);
            $table->double('total')->default(0);
            $table->enum('estado', ['A', 'V', 'F', 'N'])->default('A'); // A=Activo, V=Vencido, F=Facturado, N=Anulado
            $table->string('condicion_pago', 100)->nullable();
            $table->string('plazo_entrega', 100)->nullable();
            $table->text('observacion')->nullable();
            $table->string('legacy_id')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('presupuestos');
    }
};
