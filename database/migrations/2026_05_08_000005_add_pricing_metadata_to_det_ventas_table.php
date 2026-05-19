<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('det_ventas', function (Blueprint $table) {
            // Trazabilidad del calculo de precio para esta linea.
            // Permite auditar: que lista se aplico, que tramo gano, que regla
            // determino el precio, y si el usuario overrideo manualmente.
            $table->foreignId('lista_precio_id')->nullable()->after('articulo_id')
                ->constrained('listas_precios')->nullOnDelete();
            $table->unsignedBigInteger('tramo_aplicado_id')->nullable()->after('lista_precio_id');
            $table->string('regla_aplicada', 40)->nullable()->after('tramo_aplicado_id');
            $table->double('precio_motor')->nullable()->after('precio_unitario'); // precio que dio el motor (para comparar con overrides)
            $table->boolean('precio_manual')->default(false)->after('precio_motor'); // true => override del usuario
        });
    }

    public function down(): void
    {
        Schema::table('det_ventas', function (Blueprint $table) {
            $table->dropForeign(['lista_precio_id']);
            $table->dropColumn(['lista_precio_id', 'tramo_aplicado_id', 'regla_aplicada', 'precio_motor', 'precio_manual']);
        });
    }
};
