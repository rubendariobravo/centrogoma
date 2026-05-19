<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sub_articulos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('articulo_padre_id')->constrained('articulos')->cascadeOnDelete();
            $table->foreignId('articulo_hijo_id')->constrained('articulos')->cascadeOnDelete();
            $table->decimal('rendimiento', 8, 4)->default(100);
            $table->string('descripcion', 200)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sub_articulos');
    }
};
