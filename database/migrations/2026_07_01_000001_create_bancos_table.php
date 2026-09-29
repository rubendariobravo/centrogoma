<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bancos', function (Blueprint $table) {
            $table->id();
            $table->string('descripcion', 100);
            $table->string('cuit', 13)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        $bancos = [
            'Banco de la Nación Argentina',
            'Banco de la Provincia de Buenos Aires',
            'Banco de la Ciudad de Buenos Aires',
            'Banco Santander Argentina',
            'Banco Galicia',
            'BBVA Argentina',
            'Banco Macro',
            'Banco Credicoop',
            'Banco Patagonia',
            'Banco Supervielle',
            'Banco Hipotecario',
            'Banco ICBC',
            'Banco Comafi',
            'Banco Itaú',
            'Brubank',
            'Otro',
        ];

        foreach ($bancos as $descripcion) {
            DB::table('bancos')->insert([
                'descripcion' => $descripcion,
                'activo'      => true,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bancos');
    }
};
