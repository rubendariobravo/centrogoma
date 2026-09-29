<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reconcilia el módulo de cheques al modelo de avícola (tercero/propio con impacto en
 * caja). Las tablas viejas (`cheques` recibido/emitido y `movimientos_cheque`) estaban
 * vacías, por lo que se descartan y se recrean con el esquema nuevo.
 *
 * También se elimina `pago_ventas.cheque_id`: en el modelo nuevo el cheque enlaza a la
 * venta directamente por `venta_id` (igual que avícola), no al pago puntual.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Soltar FK + columna cheque_id de pago_ventas (el vínculo pasa a cheques.venta_id)
        if (Schema::hasColumn('pago_ventas', 'cheque_id')) {
            Schema::table('pago_ventas', function (Blueprint $table) {
                $table->dropForeign('pago_ventas_cheque_id_foreign');
                $table->dropColumn('cheque_id');
            });
        }

        // 2. Descartar tablas viejas de cheques (estaban vacías)
        Schema::dropIfExists('movimientos_cheque');
        Schema::dropIfExists('cheques');

        // 3. Crear cheques con el esquema de avícola
        Schema::create('cheques', function (Blueprint $table) {
            $table->id();

            // Naturaleza del cheque, independiente de su estado.
            // 'tercero' = cheque recibido de un cliente (cartera de valores)
            // 'propio'  = cheque emitido contra nuestra cuenta para pagar
            $table->enum('tipo', ['tercero', 'propio'])->default('tercero');

            // Estado / ciclo de vida.
            // Terceros: cartera → depositado → acreditado | rechazado | aplicado (endosado) | vendido | anulado
            // Propios:  emitido → debitado | rechazado | anulado
            $table->enum('estado', [
                'cartera', 'depositado', 'acreditado', 'rechazado',
                'aplicado', 'vendido', 'anulado', 'emitido', 'debitado',
            ])->default('cartera');

            $table->foreignId('banco_id')->nullable()->constrained('bancos')->nullOnDelete();
            $table->string('serie', 10)->nullable();
            $table->string('nro_cheque', 30)->nullable();
            $table->string('sucursal', 50)->nullable();

            $table->boolean('es_diferido')->default(true);
            $table->boolean('es_echeq')->default(false);

            // Quién libró el cheque (titular de la cuenta bancaria).
            $table->string('librador_nombre', 150)->nullable();
            $table->string('librador_cuit', 13)->nullable();

            $table->date('fecha_emision')->nullable();
            $table->date('fecha_recepcion')->nullable();
            $table->date('fecha_pago')->nullable(); // vencimiento / fecha de cobro del diferido

            $table->decimal('monto', 15, 2);

            // Relaciones de trazabilidad.
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->nullOnDelete();      // de quién lo recibí
            $table->foreignId('proveedor_id')->nullable()->constrained('proveedores')->nullOnDelete(); // a quién lo endosé / emití
            $table->foreignId('caja_id')->nullable()->constrained('cajas')->nullOnDelete();            // caja banco donde se acredita/debita
            $table->foreignId('caja_origen_id')->nullable()->constrained('cajas')->nullOnDelete();     // caja donde se registró el cobro con cheque

            // Origen / aplicación documental (sin FK fuerte para no acoplar).
            $table->unsignedBigInteger('recibo_id')->nullable();
            $table->unsignedBigInteger('venta_id')->nullable();
            $table->unsignedBigInteger('orden_pago_id')->nullable();

            // Datos de venta/descuento del cheque (financiera).
            $table->date('fecha_venta')->nullable();
            $table->decimal('monto_venta', 15, 2)->nullable();
            $table->string('comprador', 150)->nullable();

            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('observacion')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['tipo', 'estado']);
            $table->index('fecha_pago');
        });

        // 4. Crear cheque_movimientos (audit trail)
        Schema::create('cheque_movimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cheque_id')->constrained('cheques')->cascadeOnDelete();
            $table->date('fecha');
            // recepcion | emision | deposito | acreditacion | rechazo | endoso | venta | anulacion | edicion
            $table->string('tipo_mov', 30);
            $table->string('estado_resultante', 20)->nullable();
            $table->decimal('monto', 15, 2)->nullable();
            $table->nullableMorphs('referencia');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('observacion', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cheque_movimientos');
        Schema::dropIfExists('cheques');

        // Recrear cheques mínimo modelo viejo no es necesario para rollback de desarrollo;
        // se recrea la columna en pago_ventas para no dejar el esquema inconsistente.
        if (! Schema::hasColumn('pago_ventas', 'cheque_id')) {
            Schema::table('pago_ventas', function (Blueprint $table) {
                $table->unsignedBigInteger('cheque_id')->nullable()->after('forma_pago');
            });
        }
    }
};
