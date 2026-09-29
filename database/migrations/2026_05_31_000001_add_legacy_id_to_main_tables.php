<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rubros', function (Blueprint $table) {
            $table->unsignedInteger('legacy_id')->nullable()->unique()->after('id')
                  ->comment('ID original en BD Delphi (categoria.id_categoria)');
        });

        Schema::table('articulos', function (Blueprint $table) {
            $table->unsignedInteger('legacy_id')->nullable()->unique()->after('id')
                  ->comment('ID original en BD Delphi (producto.id_producto)');
        });

        Schema::table('clientes', function (Blueprint $table) {
            $table->unsignedInteger('legacy_id')->nullable()->unique()->after('id')
                  ->comment('ID original en BD Delphi (cliente.id_cliente)');
        });

        Schema::table('proveedores', function (Blueprint $table) {
            $table->unsignedInteger('legacy_id')->nullable()->unique()->after('id')
                  ->comment('ID original en BD Delphi (proveedor.id_proveedor)');
        });
    }

    public function down(): void
    {
        Schema::table('rubros',      fn ($t) => $t->dropColumn('legacy_id'));
        Schema::table('articulos',   fn ($t) => $t->dropColumn('legacy_id'));
        Schema::table('clientes',    fn ($t) => $t->dropColumn('legacy_id'));
        Schema::table('proveedores', fn ($t) => $t->dropColumn('legacy_id'));
    }
};
