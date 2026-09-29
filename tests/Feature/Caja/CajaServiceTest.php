<?php

namespace Tests\Feature\Caja;

use App\Models\Caja;
use App\Models\MovimientoCaja;
use App\Models\User;
use App\Services\Caja\CajaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CajaServiceTest extends TestCase
{
    use RefreshDatabase;

    private CajaService $service;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
        $this->service = app(CajaService::class);
    }

    private function caja(array $attrs = []): Caja
    {
        return Caja::create(array_merge([
            'user_id'        => $this->user->id,
            'tipo'           => 'diaria',
            'fecha_apertura' => now()->toDateString(),
            'hora_apertura'  => now(),
            'saldo_inicial'  => 1000,
            'estado'         => 'abierta',
        ], $attrs));
    }

    public function test_registrar_ingreso_crea_movimiento_y_actualiza_contador(): void
    {
        $caja = $this->caja();

        $this->service->registrarMovimiento($caja, 'ingreso', 'venta', 250.50);

        $this->assertDatabaseHas('movimiento_cajas', [
            'caja_id'  => $caja->id,
            'tipo'     => 'ingreso',
            'concepto' => 'venta',
            'monto'    => 250.50,
        ]);
        $this->assertEquals(250.50, (float) $caja->fresh()->total_ventas);
    }

    public function test_registrar_egreso_gasto_actualiza_total_gastos(): void
    {
        $caja = $this->caja();

        $this->service->registrarMovimiento($caja, 'egreso', 'gasto', 400);

        $this->assertEquals(400, (float) $caja->fresh()->total_gastos);
    }

    public function test_saldo_esperado_suma_ingresos_y_resta_egresos(): void
    {
        $caja = $this->caja(['saldo_inicial' => 1000]);

        $this->service->registrarMovimiento($caja, 'ingreso', 'venta', 500);
        $this->service->registrarMovimiento($caja, 'ingreso', 'cobro', 200);
        $this->service->registrarMovimiento($caja, 'egreso', 'gasto', 100);
        $this->service->registrarMovimiento($caja, 'egreso', 'pago_proveedor', 300);

        // 1000 + 500 + 200 - 100 - 300 = 1300
        $this->assertEquals(1300, $caja->fresh()->saldo_esperado);
    }

    public function test_transferir_crea_dos_movimientos_y_actualiza_ambas_cajas(): void
    {
        $origen  = $this->caja(['saldo_inicial' => 1000]);
        $destino = $this->caja(['user_id' => null, 'tipo' => 'general', 'nombre' => 'Caja General', 'saldo_inicial' => 0]);

        $transferencia = $this->service->transferir($origen, $destino, 600, 'Test transfer');

        $this->assertDatabaseHas('transferencias_caja', [
            'id'              => $transferencia->id,
            'caja_origen_id'  => $origen->id,
            'caja_destino_id' => $destino->id,
            'monto'           => 600,
        ]);

        $this->assertEquals(600, (float) $origen->fresh()->total_transferencias_salida);
        $this->assertEquals(600, (float) $destino->fresh()->total_transferencias_entrada);

        // Origen: 1000 - 600 = 400 ; Destino: 0 + 600 = 600
        $this->assertEquals(400, $origen->fresh()->saldo_esperado);
        $this->assertEquals(600, $destino->fresh()->saldo_esperado);

        $this->assertEquals(2, MovimientoCaja::where('referencia_type', \App\Models\TransferenciaCaja::class)
            ->where('referencia_id', $transferencia->id)->count());
    }

    public function test_revertir_movimientos_deshace_contador_y_borra_movimientos(): void
    {
        $caja = $this->caja();
        $gasto = \App\Models\Gasto::create([
            'caja_id'            => $caja->id,
            'categoria_gasto_id' => \App\Models\CategoriaGasto::create(['nombre' => 'Test'])->id,
            'user_id'            => $this->user->id,
            'fecha'              => now()->toDateString(),
            'monto'              => 150,
            'forma_pago'         => 'efectivo',
            'estado'             => 'A',
        ]);

        $this->service->registrarMovimiento($caja, 'egreso', 'gasto', 150, [
            'referencia_type' => \App\Models\Gasto::class,
            'referencia_id'   => $gasto->id,
        ]);
        $this->assertEquals(150, (float) $caja->fresh()->total_gastos);

        $revertidos = $this->service->revertirMovimientosDe(\App\Models\Gasto::class, $gasto->id);

        $this->assertEquals(1, $revertidos);
        $this->assertEquals(0, (float) $caja->fresh()->total_gastos);
        $this->assertDatabaseMissing('movimiento_cajas', [
            'referencia_type' => \App\Models\Gasto::class,
            'referencia_id'   => $gasto->id,
        ]);
    }
}
