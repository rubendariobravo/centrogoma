<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Compras: persiste la forma de pago y agrega el flag `procesada` que usa CompraService
 * para saber si los efectos (stock, costo, CC proveedor / egreso de caja) ya se aplicaron.
 * Las compras preexistentes ya tuvieron sus efectos aplicados inline → se marcan procesadas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('compras', function (Blueprint $table) {
            $table->string('forma_pago', 30)->default('cuenta_corriente')->after('letra');
            $table->boolean('procesada')->default(false)->after('estado');
            $table->timestamp('procesada_at')->nullable()->after('procesada');
            $table->foreignId('procesada_por')->nullable()->after('procesada_at')
                ->constrained('users')->nullOnDelete();
        });

        DB::table('compras')->update(['procesada' => true, 'procesada_at' => DB::raw('created_at')]);
    }

    public function down(): void
    {
        Schema::table('compras', function (Blueprint $table) {
            $table->dropConstrainedForeignId('procesada_por');
            $table->dropColumn(['forma_pago', 'procesada', 'procesada_at']);
        });
    }
};
