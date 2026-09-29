<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimientos_cheque', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cheque_id')->constrained('cheques')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('tipo_mov', [
                'recepcion',  // cheque recibido ingresa a cartera
                'emision',    // cheque propio emitido a proveedor
                'deposito',   // cheque recibido depositado en banco
                'cobro',      // cheque efectivamente cobrado/acreditado
                'rechazo',    // cheque rechazado / sin fondos
                'endoso',     // cheque cedido a tercero (OP a proveedor)
                'anulacion',  // anulación manual
            ]);
            $table->date('fecha_mov');
            $table->double('monto')->default(0);
            $table->string('comprobante', 150)->nullable(); // ej: "Venta #42", "OP #7"
            $table->text('observacion')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos_cheque');
    }
};
