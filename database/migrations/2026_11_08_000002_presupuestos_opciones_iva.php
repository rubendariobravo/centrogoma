<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Opciones de impresión del presupuesto, como en el Delphi (`ivasino`, casilla "sin leyenda"):
 * cotizar con precios finales o netos (276 de 1.779 en el último año fueron sin IVA) y ocultar la leyenda.
 * Lo grabado no cambia: los renglones siguen en neto con 4 decimales; solo cambia cómo se muestran.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('presupuestos', function (Blueprint $table) {
            $table->boolean('precios_con_iva')->default(true)->after('plazo_entrega');
            $table->boolean('leyenda_iva')->default(true)->after('precios_con_iva');
        });
    }

    public function down(): void
    {
        Schema::table('presupuestos', function (Blueprint $table) {
            $table->dropColumn(['precios_con_iva', 'leyenda_iva']);
        });
    }
};
