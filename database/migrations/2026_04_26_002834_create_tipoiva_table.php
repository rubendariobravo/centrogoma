<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tipoiva', function (Blueprint $table) {
            $table->id();
            $table->string('descripcion', 50);
            $table->char('codigo_afip', 2)->nullable();
            $table->char('estado', 1)->default('A');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tipoiva');
    }
};
