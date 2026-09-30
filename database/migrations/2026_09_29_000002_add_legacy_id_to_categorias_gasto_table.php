<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * legacy_id en categorias_gasto para importar `plan_cuenta` (Delphi) de forma idempotente
 * con `centrogoma:importar plan_cuenta`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categorias_gasto', function (Blueprint $table) {
            $table->unsignedInteger('legacy_id')->nullable()->unique()->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('categorias_gasto', function (Blueprint $table) {
            $table->dropUnique(['legacy_id']);
            $table->dropColumn('legacy_id');
        });
    }
};
