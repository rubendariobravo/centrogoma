<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Mejoras del Delphi:
 *  - Tarjeta por marca: en el Delphi cada tarjeta era una forma de pago ("TARJETA VISA", "TARJETA NARANJA"…) y así
 *    se concilian las liquidaciones. Acá la forma sigue siendo `tarjeta` (arqueo y estadísticas no cambian) y la marca
 *    va en `tarjeta_id` del pago, del recibo y del movimiento de caja.
 *  - Mercado Pago como forma de pago (201 cobros por año en el Delphi).
 *  - Orden de compra del cliente en la venta (206 por año).
 *
 * `forma_pago` de pago_ventas y recibos pasa de enum a string: los códigos salen de `formas_pago`.
 */
return new class extends Migration
{
    /** Marcas del Delphi (`pago_aceptado`), ordenadas por uso de los últimos 12 meses. */
    private const TARJETAS = [
        ['Visa Débito', 'debito'], ['Visa', 'credito'], ['Naranja', 'credito'], ['Mastercard', 'credito'],
        ['Su Crédito', 'credito'], ['Sol', 'credito'], ['Maestro Débito', 'debito'], ['Credimas', 'credito'],
        ['Nevada', 'credito'], ['Credicash', 'credito'], ['Shopping', 'credito'], ['Cabal', 'credito'],
        ['American Express', 'credito'], ['Nativa', 'credito'], ['Ital Cred', 'credito'], ['Carta Automática', 'credito'],
    ];

    public function up(): void
    {
        Schema::create('tarjetas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 60)->unique();
            $table->enum('tipo', ['credito', 'debito'])->default('credito');
            $table->boolean('activo')->default(true);
            $table->unsignedSmallInteger('orden')->default(0);
            $table->timestamps();
        });

        foreach (self::TARJETAS as $i => [$nombre, $tipo]) {
            DB::table('tarjetas')->insert([
                'nombre' => $nombre, 'tipo' => $tipo, 'activo' => true, 'orden' => $i + 1,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        DB::statement("ALTER TABLE pago_ventas MODIFY forma_pago VARCHAR(30) NOT NULL DEFAULT 'efectivo'");
        DB::statement("ALTER TABLE recibos MODIFY forma_pago VARCHAR(30) NOT NULL DEFAULT 'efectivo'");

        foreach (['pago_ventas', 'recibos', 'movimiento_cajas'] as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->foreignId('tarjeta_id')->nullable()->after('forma_pago')->constrained('tarjetas')->nullOnDelete();
            });
        }

        Schema::table('ventas', function (Blueprint $table) {
            $table->string('orden_compra', 100)->nullable()->after('observacion');
        });

        if (! DB::table('formas_pago')->where('codigo', 'mercado_pago')->exists()) {
            // Después de tarjeta: efectivo 1, transferencia 2, tarjeta 3, mercado pago 4, cheque 5…
            DB::table('formas_pago')->where('orden', '>=', 4)->increment('orden');
            DB::table('formas_pago')->insert([
                'codigo' => 'mercado_pago', 'descripcion' => 'Mercado Pago', 'es_efectivo' => false,
                'es_cuenta_corriente' => false, 'costo_financiero' => 0, 'activo' => true, 'orden' => 4,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('formas_pago')->where('codigo', 'mercado_pago')->delete();
        DB::table('formas_pago')->where('orden', '>', 4)->decrement('orden');

        Schema::table('ventas', fn (Blueprint $table) => $table->dropColumn('orden_compra'));

        foreach (['pago_ventas', 'recibos', 'movimiento_cajas'] as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->dropConstrainedForeignId('tarjeta_id');
            });
        }

        DB::statement("UPDATE pago_ventas SET forma_pago = 'otro' WHERE forma_pago NOT IN ('efectivo','transferencia','cheque','tarjeta','cuenta_corriente','otro')");
        DB::statement("UPDATE recibos SET forma_pago = 'otro' WHERE forma_pago NOT IN ('efectivo','transferencia','cheque','tarjeta','otro')");
        DB::statement("ALTER TABLE pago_ventas MODIFY forma_pago ENUM('efectivo','transferencia','cheque','tarjeta','cuenta_corriente','otro') NOT NULL DEFAULT 'efectivo'");
        DB::statement("ALTER TABLE recibos MODIFY forma_pago ENUM('efectivo','transferencia','cheque','tarjeta','otro') NOT NULL DEFAULT 'efectivo'");

        Schema::dropIfExists('tarjetas');
    }
};
