<?php

namespace Tests\Feature\Legacy;

use App\Livewire\Recibos\Form as ReciboForm;
use App\Livewire\Ventas\Index as VentasIndex;
use App\Models\Caja;
use App\Models\CcCliente;
use App\Models\CcProveedor;
use App\Models\Cliente;
use App\Models\Compra;
use App\Models\Proveedor;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * centrogoma:importar-saldos contra una BD legacy de prueba (centrogoma_legacy_test,
 * definida en phpunit.xml) con las tablas Delphi armadas por el test.
 */
class ImportarSaldosLegacyTest extends TestCase
{
    use RefreshDatabase;

    private Cliente $deudor;
    private Cliente $aFavor;
    private Proveedor $proveedor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
        $this->armarLegacy();

        $this->deudor    = Cliente::create(['razon_social' => 'Deudor SA', 'estado' => 'A', 'saldo' => 0, 'legacy_id' => 10]);
        $this->aFavor    = Cliente::create(['razon_social' => 'A Favor SRL', 'estado' => 'A', 'saldo' => 0, 'legacy_id' => 20]);
        Cliente::create(['razon_social' => 'Consumidor Final', 'estado' => 'A', 'saldo' => 0, 'legacy_id' => 1]);
        $this->proveedor = Proveedor::create(['razon_social' => 'Proveedor SA', 'estado' => 'A', 'saldo' => 0, 'legacy_id' => 5]);
    }

    private function armarLegacy(): void
    {
        $schema = Schema::connection('centro_goma_legacy');
        foreach (['cccliente', 'ccproveedor', 'cliente', 'proveedor'] as $t) {
            $schema->dropIfExists($t);
        }
        foreach (['cccliente' => 'id_cliente', 'ccproveedor' => 'id_proveedor'] as $tabla => $fk) {
            $schema->create($tabla, function (Blueprint $t) use ($fk) {
                $t->increments('id_movimiento');
                $t->integer($fk);
                $t->string('comprobante', 100)->nullable();
                $t->date('fecha');
                $t->char('tipo', 1)->nullable();
                $t->decimal('ingreso', 11, 2)->nullable();
                $t->decimal('egreso', 11, 2)->nullable();
                $t->decimal('saldo', 11, 2)->nullable();
                $t->char('estado', 1)->nullable();
            });
        }
        $schema->create('cliente', fn (Blueprint $t) => [$t->integer('id_cliente'), $t->string('razon_social')]);
        $schema->create('proveedor', fn (Blueprint $t) => [$t->integer('id_proveedor'), $t->string('razon_social')]);

        $l = DB::connection('centro_goma_legacy');
        $l->table('cliente')->insert([
            ['id_cliente' => 1, 'razon_social' => 'CONSUMIDOR FINAL'],
            ['id_cliente' => 10, 'razon_social' => 'DEUDOR SA'],
            ['id_cliente' => 20, 'razon_social' => 'A FAVOR SRL'],
            ['id_cliente' => 99, 'razon_social' => 'NO IMPORTADO'],
        ]);
        // `saldo` y `estado` del legacy se ignoran a propósito: el saldo es SUM(egreso) − SUM(ingreso).
        $l->table('cccliente')->insert([
            ['id_cliente' => 10, 'fecha' => '2026-01-10', 'tipo' => 'F', 'egreso' => 1000, 'ingreso' => null, 'saldo' => -999999, 'estado' => 'P'],
            ['id_cliente' => 10, 'fecha' => '2026-02-10', 'tipo' => 'F', 'egreso' => 500, 'ingreso' => null, 'saldo' => 0, 'estado' => 'A'],
            ['id_cliente' => 10, 'fecha' => '2026-03-01', 'tipo' => 'E', 'egreso' => null, 'ingreso' => 300, 'saldo' => 0, 'estado' => 'P'],
            ['id_cliente' => 10, 'fecha' => '2026-12-31', 'tipo' => 'F', 'egreso' => 7777, 'ingreso' => null, 'saldo' => 0, 'estado' => 'A'], // posterior al corte
            ['id_cliente' => 20, 'fecha' => '2026-01-05', 'tipo' => 'T', 'egreso' => null, 'ingreso' => 250, 'saldo' => 0, 'estado' => 'P'],
            ['id_cliente' => 1,  'fecha' => '2026-01-05', 'tipo' => 'E', 'egreso' => null, 'ingreso' => 10000, 'saldo' => 0, 'estado' => 'P'],
            ['id_cliente' => 99, 'fecha' => '2026-01-05', 'tipo' => 'F', 'egreso' => 400, 'ingreso' => null, 'saldo' => 0, 'estado' => 'P'],
        ]);
        $l->table('proveedor')->insert([['id_proveedor' => 5, 'razon_social' => 'PROVEEDOR SA']]);
        $l->table('ccproveedor')->insert([
            ['id_proveedor' => 5, 'fecha' => '2026-01-10', 'tipo' => 'O', 'egreso' => 2000, 'ingreso' => null, 'saldo' => 0, 'estado' => 'P'],
            ['id_proveedor' => 5, 'fecha' => '2026-02-10', 'tipo' => 'C', 'egreso' => null, 'ingreso' => 800, 'saldo' => 0, 'estado' => 'P'],
        ]);
    }

    private function importar(array $opts = []): void
    {
        $this->artisan('centrogoma:importar-saldos', ['--corte' => '2026-06-30'] + $opts)->assertSuccessful();
    }

    public function test_carga_deuda_como_comprobante_de_saldo_inicial(): void
    {
        $this->importar();

        $venta = Venta::where('cliente_id', $this->deudor->id)->sole();
        $this->assertTrue($venta->saldo_inicial);
        $this->assertEquals(1200, (float) $venta->total);   // 1000 + 500 − 300; el 7777 es posterior al corte
        $this->assertEquals(1200, (float) $venta->saldo);
        $this->assertSame('P', $venta->estado);
        $this->assertSame('2026-06-30', $venta->fecha->toDateString());
        $this->assertEquals(1200, (float) $this->deudor->fresh()->saldo);
        $this->assertEquals(1200, (float) CcCliente::where('cliente_id', $this->deudor->id)->sole()->debe);

        $compra = Compra::where('proveedor_id', $this->proveedor->id)->sole();
        $this->assertTrue($compra->saldo_inicial);
        $this->assertEquals(1200, (float) $compra->saldo);
        $this->assertEquals(1200, (float) $this->proveedor->fresh()->saldo);
        $this->assertSame(1, CcProveedor::count());
    }

    public function test_saldo_a_favor_va_solo_como_asiento_y_mostrador_se_excluye(): void
    {
        $this->importar();

        $this->assertSame(0, Venta::where('cliente_id', $this->aFavor->id)->count());
        $this->assertEquals(-250, (float) $this->aFavor->fresh()->saldo);
        $this->assertEquals(250, (float) CcCliente::where('cliente_id', $this->aFavor->id)->sole()->haber);

        $mostrador = Cliente::where('legacy_id', 1)->first();
        $this->assertEquals(0, (float) $mostrador->saldo);
        $this->assertSame(0, CcCliente::where('cliente_id', $mostrador->id)->count());
    }

    public function test_dry_run_no_guarda_nada(): void
    {
        $this->importar(['--dry-run' => true]);

        $this->assertSame(0, Venta::count());
        $this->assertSame(0, CcCliente::count());
        $this->assertEquals(0, (float) $this->deudor->fresh()->saldo);
    }

    public function test_segunda_corrida_reemplaza_con_el_legacy_actualizado(): void
    {
        $this->importar();

        // El legacy sigue operando hasta la salida a producción: entra un pago nuevo.
        DB::connection('centro_goma_legacy')->table('cccliente')->insert(
            ['id_cliente' => 10, 'fecha' => '2026-06-15', 'tipo' => 'E', 'egreso' => null, 'ingreso' => 200, 'saldo' => 0, 'estado' => 'P']
        );
        $this->importar();

        $venta = Venta::where('cliente_id', $this->deudor->id)->sole();
        $this->assertEquals(1000, (float) $venta->total);
        $this->assertEquals(1000, (float) $this->deudor->fresh()->saldo);
        $this->assertSame(1, CcCliente::where('cliente_id', $this->deudor->id)->count());
        $this->assertEquals(-250, (float) $this->aFavor->fresh()->saldo);
        $this->assertSame(1, CcCliente::where('cliente_id', $this->aFavor->id)->count());
        $this->assertSame(1, Compra::count());
    }

    public function test_saldo_ya_cobrado_en_la_app_no_se_recalcula(): void
    {
        $this->importar();
        $venta = Venta::where('cliente_id', $this->deudor->id)->sole();

        Caja::create(['user_id' => auth()->id(), 'tipo' => 'diaria', 'estado' => 'abierta',
            'fecha_apertura' => now()->toDateString(), 'saldo_inicial' => 0]);
        Livewire::test(ReciboForm::class)
            ->set('cliente_id', $this->deudor->id)
            ->assertSee('Saldo inicial')
            ->set('forma_pago', 'efectivo')
            ->call('save', [['venta_id' => $venta->id, 'monto_aplicar' => 500]])
            ->assertHasNoErrors();

        $this->importar();

        $this->assertSame($venta->id, Venta::where('cliente_id', $this->deudor->id)->sole()->id);
        $this->assertEquals(700, (float) $venta->fresh()->saldo);
        $this->assertEquals(700, (float) $this->deudor->fresh()->saldo);
    }

    public function test_saldo_inicial_no_suma_en_el_total_de_ventas(): void
    {
        $this->importar();

        Livewire::test(VentasIndex::class)
            ->set('filterDesde', '2026-06-01')
            ->set('filterHasta', '2026-06-30')
            ->assertSee('Saldo inicial')
            ->assertViewHas('totalPeriodo', fn ($t) => (float) $t === 0.0);
    }
}
