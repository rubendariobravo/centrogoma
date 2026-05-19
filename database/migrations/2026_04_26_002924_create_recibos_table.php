<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recibos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes');
            $table->foreignId('caja_id')->nullable()->constrained('cajas')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->integer('nro_recibo')->default(0);
            $table->date('fecha');
            $table->decimal('total', 15, 2)->default(0);
            $table->enum('forma_pago', ['efectivo', 'transferencia', 'cheque', 'tarjeta', 'otro'])->default('efectivo');
            $table->string('referencia', 100)->nullable();
            $table->text('observacion')->nullable();
            $table->enum('estado', ['P', 'C', 'A'])->default('P');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recibos');
    }
};
