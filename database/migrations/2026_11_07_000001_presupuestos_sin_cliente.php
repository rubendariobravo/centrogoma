<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Presupuesto sin cliente registrado, como en el Delphi (41% de los presupuestos del último año):
 * cliente_id pasa a opcional y se puede escribir el nombre a mano.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('presupuestos', function (Blueprint $table) {
            $table->unsignedBigInteger('cliente_id')->nullable()->change();
            $table->string('cliente_nombre', 150)->nullable()->after('cliente_id');
        });
    }

    public function down(): void
    {
        Schema::table('presupuestos', function (Blueprint $table) {
            $table->dropColumn('cliente_nombre');
            $table->unsignedBigInteger('cliente_id')->nullable(false)->change();
        });
    }
};
