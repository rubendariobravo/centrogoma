<?php

namespace Tests\Feature\Empleados;

use App\Livewire\Anticipos\Index;
use App\Models\AnticipoEmpleado;
use App\Models\Caja;
use App\Models\Empleado;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Anticipo pagado con varios medios (mitad efectivo / mitad transferencia),
 * cada línea desde su propia caja.
 */
class AnticipoMultiPagoTest extends TestCase
{
    use RefreshDatabase;

    private User     $user;
    private Empleado $empleado;
    private Caja     $cajaDiaria;
    private Caja     $cajaGeneral;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        $this->empleado = Empleado::create([
            'nombre'      => 'Noelia',
            'apellido'    => 'Samudio',
            'rol'         => 'administrativo',
            'comision'    => 0,
            'estado'      => 'A',
        ]);

        $this->cajaDiaria = Caja::create([
            'user_id'        => $this->user->id,
            'tipo'           => 'diaria',
            'fecha_apertura' => now()->toDateString(),
            'hora_apertura'  => now(),
            'saldo_inicial'  => 500000,
            'estado'         => 'abierta',
        ]);

        $this->cajaGeneral = Caja::create([
            'user_id'        => null,
            'tipo'           => 'general',
            'nombre'         => 'Caja General',
            'fecha_apertura' => now()->toDateString(),
            'hora_apertura'  => now(),
            'saldo_inicial'  => 500000,
            'estado'         => 'abierta',
        ]);
    }

    /** Payload de líneas de pago mitad y mitad. */
    private function pagosMitad(): array
    {
        return [
            ['forma_pago' => 'efectivo',      'caja_id' => $this->cajaDiaria->id,  'monto' => '101400', 'referencia' => ''],
            ['forma_pago' => 'transferencia', 'caja_id' => $this->cajaGeneral->id, 'monto' => '101400', 'referencia' => 'TRF-99'],
        ];
    }

    public function test_anticipo_mixto_crea_una_linea_y_un_egreso_por_medio(): void
    {
        Livewire::test(Index::class)
            ->call('openCreate')
            ->set('empleado_id', $this->empleado->id)
            ->set('fecha', now()->toDateString())
            ->set('monto', '202800')
            ->set('pagos', $this->pagosMitad())
            ->call('save')
            ->assertHasNoErrors();

        $anticipo = AnticipoEmpleado::first();
        $this->assertEquals(202800, (float) $anticipo->monto);
        $this->assertCount(2, $anticipo->pagos);
        $this->assertTrue($anticipo->esMixto());
        $this->assertEquals('Mixto (2)', $anticipo->formaPagoEtiqueta());

        // Un egreso en cada caja, por su propio monto
        $this->assertDatabaseHas('movimiento_cajas', [
            'caja_id'         => $this->cajaDiaria->id,
            'tipo'            => 'egreso',
            'concepto'        => 'anticipo',
            'forma_pago'      => 'efectivo',
            'monto'           => 101400,
            'referencia_type' => AnticipoEmpleado::class,
            'referencia_id'   => $anticipo->id,
        ]);
        $this->assertDatabaseHas('movimiento_cajas', [
            'caja_id'         => $this->cajaGeneral->id,
            'tipo'            => 'egreso',
            'concepto'        => 'anticipo',
            'forma_pago'      => 'transferencia',
            'monto'           => 101400,
            'referencia_type' => AnticipoEmpleado::class,
            'referencia_id'   => $anticipo->id,
        ]);

        $this->assertEquals(101400, (float) $this->cajaDiaria->fresh()->total_anticipos);
        $this->assertEquals(101400, (float) $this->cajaGeneral->fresh()->total_anticipos);
    }

    public function test_la_transferencia_no_baja_el_efectivo_de_la_caja_diaria(): void
    {
        Livewire::test(Index::class)
            ->call('openCreate')
            ->set('empleado_id', $this->empleado->id)
            ->set('fecha', now()->toDateString())
            ->set('monto', '202800')
            ->set('pagos', $this->pagosMitad())
            ->call('save')
            ->assertHasNoErrors();

        // Solo los $101.400 en efectivo salen del cajón de la caja diaria.
        $this->assertEquals(500000 - 101400, $this->cajaDiaria->fresh()->saldo_efectivo_esperado);

        // La transferencia salió de la general y NO tocó su efectivo (no es efectivo físico),
        // pero sí baja su saldo estimado con todos los medios.
        $general = $this->cajaGeneral->fresh();
        $this->assertEquals(500000, $general->saldo_efectivo_esperado);
        $this->assertEquals(500000 - 101400, $general->saldo_esperado);
    }

    public function test_el_cabezal_guarda_la_linea_de_mayor_monto_como_principal(): void
    {
        Livewire::test(Index::class)
            ->call('openCreate')
            ->set('empleado_id', $this->empleado->id)
            ->set('fecha', now()->toDateString())
            ->set('monto', '100000')
            ->set('pagos', [
                ['forma_pago' => 'efectivo',      'caja_id' => $this->cajaDiaria->id,  'monto' => '30000', 'referencia' => ''],
                ['forma_pago' => 'transferencia', 'caja_id' => $this->cajaGeneral->id, 'monto' => '70000', 'referencia' => 'TRF-1'],
            ])
            ->call('save')
            ->assertHasNoErrors();

        $anticipo = AnticipoEmpleado::first();
        $this->assertEquals('transferencia', $anticipo->forma_pago);
        $this->assertEquals($this->cajaGeneral->id, $anticipo->caja_id);
        $this->assertEquals('TRF-1', $anticipo->referencia);
    }

    public function test_no_guarda_si_las_lineas_no_cubren_el_monto(): void
    {
        Livewire::test(Index::class)
            ->call('openCreate')
            ->set('empleado_id', $this->empleado->id)
            ->set('fecha', now()->toDateString())
            ->set('monto', '202800')
            ->set('pagos', [
                ['forma_pago' => 'efectivo', 'caja_id' => $this->cajaDiaria->id, 'monto' => '100000', 'referencia' => ''],
            ])
            ->call('save')
            ->assertHasErrors('pagos');

        $this->assertEquals(0, AnticipoEmpleado::count());
        $this->assertEquals(0, (float) $this->cajaDiaria->fresh()->total_anticipos);
    }

    public function test_editar_revierte_todas_las_lineas_y_regraba(): void
    {
        $component = Livewire::test(Index::class)
            ->call('openCreate')
            ->set('empleado_id', $this->empleado->id)
            ->set('fecha', now()->toDateString())
            ->set('monto', '202800')
            ->set('pagos', $this->pagosMitad())
            ->call('save');

        $anticipo = AnticipoEmpleado::first();

        // Se reedita como un único pago en efectivo por otro importe
        $component->call('openEdit', $anticipo->id)
            ->set('monto', '50000')
            ->set('pagos', [
                ['forma_pago' => 'efectivo', 'caja_id' => $this->cajaDiaria->id, 'monto' => '50000', 'referencia' => ''],
            ])
            ->call('save')
            ->assertHasNoErrors();

        $anticipo->refresh();
        $this->assertCount(1, $anticipo->pagos()->get());
        $this->assertEquals(50000, (float) $anticipo->monto);

        // La caja general recuperó su monto; la diaria quedó con el nuevo egreso
        $this->assertEquals(0, (float) $this->cajaGeneral->fresh()->total_anticipos);
        $this->assertEquals(50000, (float) $this->cajaDiaria->fresh()->total_anticipos);
        $this->assertEquals(1, \App\Models\MovimientoCaja::where('referencia_type', AnticipoEmpleado::class)
            ->where('referencia_id', $anticipo->id)->count());
    }

    public function test_rechazar_devuelve_el_monto_a_todas_las_cajas(): void
    {
        Livewire::test(Index::class)
            ->call('openCreate')
            ->set('empleado_id', $this->empleado->id)
            ->set('fecha', now()->toDateString())
            ->set('monto', '202800')
            ->set('pagos', $this->pagosMitad())
            ->call('save');

        $anticipo = AnticipoEmpleado::first();

        Livewire::test(Index::class)
            ->call('openRechazar', $anticipo->id)
            ->set('motivoRechazo', 'Cargado por error')
            ->call('rechazar')
            ->assertHasNoErrors();

        $this->assertEquals('rechazado', $anticipo->fresh()->estado);
        $this->assertEquals(0, (float) $this->cajaDiaria->fresh()->total_anticipos);
        $this->assertEquals(0, (float) $this->cajaGeneral->fresh()->total_anticipos);
        $this->assertEquals(0, \App\Models\MovimientoCaja::where('referencia_type', AnticipoEmpleado::class)
            ->where('referencia_id', $anticipo->id)->count());
    }

    public function test_eliminar_devuelve_el_monto_y_borra_las_lineas(): void
    {
        Livewire::test(Index::class)
            ->call('openCreate')
            ->set('empleado_id', $this->empleado->id)
            ->set('fecha', now()->toDateString())
            ->set('monto', '202800')
            ->set('pagos', $this->pagosMitad())
            ->call('save');

        $anticipo = AnticipoEmpleado::first();

        Livewire::test(Index::class)
            ->call('confirmDelete', $anticipo->id)
            ->call('delete');

        $this->assertSoftDeleted('anticipo_empleados', ['id' => $anticipo->id]);
        $this->assertEquals(0, \App\Models\AnticipoPago::count());
        $this->assertEquals(0, (float) $this->cajaDiaria->fresh()->total_anticipos);
        $this->assertEquals(0, (float) $this->cajaGeneral->fresh()->total_anticipos);
    }

    public function test_aprobar_y_liquidar_no_mueven_la_caja(): void
    {
        Livewire::test(Index::class)
            ->call('openCreate')
            ->set('empleado_id', $this->empleado->id)
            ->set('fecha', now()->toDateString())
            ->set('monto', '202800')
            ->set('pagos', $this->pagosMitad())
            ->call('save');

        $anticipo = AnticipoEmpleado::first();

        Livewire::test(Index::class)
            ->call('aprobar', $anticipo->id)
            ->call('liquidar', $anticipo->id);

        $anticipo->refresh();
        $this->assertEquals('liquidado', $anticipo->estado);
        $this->assertEquals($this->user->id, $anticipo->aprobado_por);
        $this->assertEquals(101400, (float) $this->cajaDiaria->fresh()->total_anticipos);
        $this->assertEquals(101400, (float) $this->cajaGeneral->fresh()->total_anticipos);
        $this->assertEquals(2, \App\Models\MovimientoCaja::where('referencia_type', AnticipoEmpleado::class)
            ->where('referencia_id', $anticipo->id)->count());
    }

    public function test_no_permite_pagar_desde_una_caja_cerrada(): void
    {
        $this->cajaGeneral->update(['estado' => 'cerrada']);

        Livewire::test(Index::class)
            ->call('openCreate')
            ->set('empleado_id', $this->empleado->id)
            ->set('fecha', now()->toDateString())
            ->set('monto', '202800')
            ->set('pagos', $this->pagosMitad())
            ->call('save')
            ->assertHasErrors('pagos.1.caja_id');

        $this->assertEquals(0, AnticipoEmpleado::count());
    }

    public function test_completar_resto_asigna_lo_que_falta(): void
    {
        Livewire::test(Index::class)
            ->call('openCreate')
            ->set('monto', '202800')
            ->set('pagos', [
                ['forma_pago' => 'efectivo',      'caja_id' => $this->cajaDiaria->id,  'monto' => '100000', 'referencia' => ''],
                ['forma_pago' => 'transferencia', 'caja_id' => $this->cajaGeneral->id, 'monto' => '',       'referencia' => ''],
            ])
            ->call('completarResto', 1)
            ->assertSet('pagos.1.monto', '102800');
    }

    public function test_con_una_sola_linea_el_monto_la_arrastra(): void
    {
        Livewire::test(Index::class)
            ->call('openCreate')
            ->set('monto', '75000')
            ->assertSet('pagos.0.monto', '75000');
    }
}
