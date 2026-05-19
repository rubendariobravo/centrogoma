<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cheques', function (Blueprint $table) {
            $table->id();
            $table->enum('tipo', ['recibido', 'emitido'])->default('recibido');
            $table->string('banco', 100);
            $table->string('nro_cheque', 50);
            $table->string('titular', 150);
            $table->string('cuit_titular', 20)->nullable();
            $table->date('fecha_emision');
            $table->date('fecha_cobro');
            $table->double('monto')->default(0);
            $table->enum('estado', ['en_cartera', 'depositado', 'cobrado', 'rechazado', 'endosado', 'anulado'])
                  ->default('en_cartera');
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->nullOnDelete();
            $table->foreignId('proveedor_id')->nullable()->constrained('proveedores')->nullOnDelete();
            $table->string('referencia', 150)->nullable();
            $table->text('observacion')->nullable();
            $table->string('legacy_id')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cheques');
    }
};
