<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cajas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->date('fecha_apertura');
            $table->dateTime('hora_apertura')->nullable();
            $table->date('fecha_cierre')->nullable();
            $table->dateTime('hora_cierre')->nullable();
            $table->decimal('saldo_inicial', 15, 2)->default(0);
            $table->decimal('total_ventas', 15, 2)->default(0);
            $table->decimal('total_cobros', 15, 2)->default(0);
            $table->decimal('total_gastos', 15, 2)->default(0);
            $table->decimal('saldo_final', 15, 2)->default(0);
            $table->enum('estado', ['abierta', 'cerrada'])->default('abierta');
            $table->text('observacion')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cajas');
    }
};
