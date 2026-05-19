<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->string('razon_social', 150);
            $table->string('nombre_fantasia', 150)->nullable();
            $table->string('cuit', 13)->nullable();
            $table->string('dni', 15)->nullable();
            $table->foreignId('tipoiva_id')->nullable()->constrained('tipoiva')->nullOnDelete();
            $table->foreignId('empleado_id')->nullable()->constrained('empleados')->nullOnDelete();
            $table->string('telefono', 30)->nullable();
            $table->string('celular', 30)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('domicilio', 200)->nullable();
            $table->string('localidad', 100)->nullable();
            $table->string('provincia', 100)->nullable();
            $table->string('codigo_postal', 10)->nullable();
            $table->integer('lista_precio')->default(1);
            $table->decimal('bonificacion', 5, 2)->default(0);
            $table->decimal('limite_credito', 15, 2)->default(0);
            $table->decimal('saldo', 15, 2)->default(0);
            $table->decimal('porcentaje_iibb', 5, 4)->default(0);
            $table->boolean('percepcion_iva')->default(false);
            $table->boolean('es_mayorista')->default(false);
            $table->char('estado', 1)->default('A');
            $table->text('observacion')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
