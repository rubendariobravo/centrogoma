<?php

namespace Tests\Feature\Caja;

use App\Livewire\Cajas\Index as CajasIndex;
use App\Livewire\Cajas\Show as CajasShow;
use App\Models\Caja;
use App\Models\CategoriaGasto;
use App\Models\Gasto;
use App\Models\Recibo;
use App\Models\User;
use App\Services\Caja\CajaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Detalle de caja (/cajas/{caja}): libro de movimientos con saldo corrido en efectivo
 * y pestañas por tipo de documento.
 */
class CajaShowTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Caja $caja;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        $this->caja = Caja::create([
            'user_id' => $this->user->id, 'tipo' => 'diaria',
            'fecha_apertura' => now()->toDateString(), 'hora_apertura' => now(),
            'saldo_inicial' => 1000, 'estado' => 'abierta',
        ]);
    }

    public function test_detalle_muestra_movimientos_y_saldo_corrido_en_efectivo(): void
    {
        $svc = app(CajaService::class);
        $svc->registrarMovimiento($this->caja, 'ingreso', 'venta', 500, ['forma_pago' => 'efectivo', 'descripcion' => 'Venta efectivo']);
        $svc->registrarMovimiento($this->caja, 'ingreso', 'venta', 300, ['forma_pago' => 'transferencia', 'descripcion' => 'Venta transferencia']);
        $svc->registrarMovimiento($this->caja, 'egreso', 'pago_proveedor', 200, ['forma_pago' => 'efectivo', 'descripcion' => 'Pago contado']);

        $caja = $this->caja->fresh();
        $this->assertEquals(1600, $caja->saldo_esperado);
        $this->assertEquals(1300, $caja->saldo_efectivo_esperado); // la transferencia bancaria no entra al cajón

        Livewire::test(CajasShow::class, ['caja' => $caja])
            ->assertOk()
            ->assertSee('Venta efectivo')
            ->assertSee('Venta transferencia')
            ->assertSee('Pago contado')
            ->assertSee('1.300,00');
    }

    public function test_todas_las_pestanas_renderizan(): void
    {
        $general = Caja::create([
            'nombre' => 'Caja General', 'tipo' => 'general',
            'fecha_apertura' => now()->toDateString(), 'saldo_inicial' => 0, 'estado' => 'abierta',
        ]);
        app(CajaService::class)->transferir($this->caja, $general, 100, 'Retiro');
        app(CajaService::class)->registrarMovimiento($this->caja, 'ingreso', 'ajuste', 50, ['forma_pago' => 'efectivo', 'descripcion' => 'Sobrante']);

        $cat = CategoriaGasto::create(['nombre' => 'Limpieza', 'activo' => true]);
        Gasto::create([
            'caja_id' => $this->caja->id, 'categoria_gasto_id' => $cat->id, 'user_id' => $this->user->id,
            'fecha' => now()->toDateString(), 'monto' => 80, 'forma_pago' => 'efectivo',
            'descripcion' => 'Artículos de limpieza', 'estado' => 'A',
        ]);

        $test = Livewire::test(CajasShow::class, ['caja' => $this->caja->fresh()]);
        foreach (['movimientos', 'ventas', 'recibos', 'pagos', 'gastos', 'transferencias', 'ajustes'] as $tab) {
            $test->set('tab', $tab)->assertOk();
        }
        $test->set('tab', 'transferencias')->assertSee('Retiro');
        $test->set('tab', 'gastos')->assertSee('Artículos de limpieza');
        $test->set('tab', 'ajustes')->assertSee('Sobrante');
    }

    public function test_ruta_detalle_responde(): void
    {
        $this->get(route('cajas.show', $this->caja))->assertOk();
        $this->get(route('cajas'))->assertOk();
    }

    public function test_no_permite_abrir_dos_cajas_diarias(): void
    {
        Livewire::test(CajasIndex::class)
            ->set('saldo_inicial', 500)
            ->call('abrirCaja')
            ->assertHasErrors('saldo_inicial');

        $this->assertSame(1, Caja::where('tipo', 'diaria')->where('estado', 'abierta')->count());
    }
}
