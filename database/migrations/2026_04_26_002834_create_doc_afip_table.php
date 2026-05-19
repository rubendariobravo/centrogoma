<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doc_afip', function (Blueprint $table) {
            $table->id();
            $table->string('descripcion', 50);
            $table->unsignedTinyInteger('tipo_cbte');
            $table->char('letra', 1);
            $table->char('estado', 1)->default('A');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doc_afip');
    }
};
