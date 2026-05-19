<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('remitos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empleado_id')->nullable()->constrained('empleados')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->integer('nro_remito')->default(0);
            $table->date('fecha');
            $table->enum('estado', ['abierto', 'cerrado', 'anulado'])->default('abierto');
            $table->decimal('total_peso', 10, 3)->default(0);
            $table->decimal('total_cajas', 10, 2)->default(0);
            $table->text('observacion')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('remitos');
    }
};
