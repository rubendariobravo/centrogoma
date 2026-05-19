<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('listas_precios', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 30)->unique();
            $table->string('nombre', 100);
            $table->string('descripcion', 250)->nullable();
            $table->enum('tipo', ['minorista', 'mayorista', 'especial'])->default('minorista');
            $table->boolean('es_default')->default(false);
            $table->decimal('recargo_no_efectivo', 5, 2)->default(0);
            $table->decimal('descuento_efectivo', 5, 2)->default(0);
            $table->unsignedInteger('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // Insertamos la lista default en la propia migracion para que las migraciones
        // siguientes (que agregan FK a articulo_precios y cliente_precios) puedan
        // backfillear filas existentes sin depender del orden de los seeders.
        DB::table('listas_precios')->insert([
            'codigo'              => 'MINORISTA',
            'nombre'              => 'Minorista',
            'descripcion'         => 'Lista de precios minorista (consumidor final). Lista default del sistema.',
            'tipo'                => 'minorista',
            'es_default'          => true,
            'recargo_no_efectivo' => 0,
            'descuento_efectivo'  => 0,
            'orden'               => 1,
            'activo'              => true,
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('listas_precios');
    }
};
