<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proveedores', function (Blueprint $table) {
            $table->id();
            $table->string('razon_social', 150);
            $table->string('nombre_fantasia', 150)->nullable();
            $table->string('cuit', 13)->nullable()->unique();
            $table->string('telefono', 30)->nullable();
            $table->string('celular', 30)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('domicilio', 200)->nullable();
            $table->string('localidad', 100)->nullable();
            $table->string('provincia', 100)->nullable();
            $table->string('codigo_postal', 10)->nullable();
            $table->string('contacto', 100)->nullable();
            $table->decimal('saldo', 15, 2)->default(0);
            $table->char('estado', 1)->default('A');
            $table->text('observacion')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proveedores');
    }
};
