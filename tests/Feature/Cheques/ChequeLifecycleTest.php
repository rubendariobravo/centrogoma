<?php

namespace Tests\Feature\Cheques;

use App\Livewire\Cheques\Index as ChequesIndex;
use App\Models\Banco;
use App\Models\Caja;
use App\Models\Cheque;
use App\Models\Cliente;
use App\Models\TipoIva;
use App\Models\User;
use App\Services\Cheque\ChequeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ChequeLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Caja $cajaDiaria;
    private Caja $cajaBanco;
    private Banco $banco;
    private Cliente $cliente;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        $this->cajaDiaria = Caja::create([
            'user_id' => $this->user->id, 'tipo' => 'diaria',
            'fecha_apertura' => now()->toDateString(), 'hora_apertura' => now(),
            'saldo_inicial' => 0, 'estado' => 'abierta',
        ]);

        $this->cajaBanco = Caja::create([
            'user_id' => null, 'nombre' => 'Banco Nación', 'tipo' => 'banco',
            'fecha_apertura' => now()->toDateString(), 'hora_apertura' => now(),
            'saldo_inicial' => 0, 'estado' => 'abierta',
        ]);

        $this->banco = Banco::create(['descripcion' => 'Banco Test', 'activo' => true]);

        $tipoIva = TipoIva::firstOrCreate(['codigo_afip' => '05'], ['descripcion' => 'Consumidor Final']);
        $this->cliente = Cliente::create([
            'razon_social' => 'Cliente Test', 'tipoiva_id' => $tipoIva->id,
            'estado' => 'A', 'saldo' => 0,
        ]);
    }

    private function nuevoCheque(string $estado = 'cartera'): Cheque
    {
        return Cheque::create([
            'tipo' => 'tercero', 'estado' => $estado, 'banco_id' => $this->banco->id,
            'nro_cheque' => '00012345', 'monto' => 5000,
            'cliente_id' => $this->cliente->id, 'caja_origen_id' => $this->cajaDiaria->id,
            'fecha_pago' => now()->addDays(30)->toDateString(),
            'user_id' => $this->user->id,
        ]);
    }

    public function test_acreditar_mueve_valor_de_origen_a_banco(): void
    {
        $cheque = $this->nuevoCheque();
        $service = app(ChequeService::class);

        $service->depositar($cheque, $this->cajaBanco->id);
        $this->assertEquals('depositado', $cheque->fresh()->estado);

        $service->acreditar($cheque->fresh(), $this->cajaBanco->id);

        $cheque->refresh();
        $this->assertEquals('acreditado', $cheque->estado);

        // Ingreso en banco, egreso en la caja de origen.
        $this->assertDatabaseHas('movimiento_cajas', [
            'caja_id' => $this->cajaBanco->id, 'tipo' => 'ingreso', 'concepto' => 'cheque', 'monto' => 5000,
        ]);
        $this->assertDatabaseHas('movimiento_cajas', [
            'caja_id' => $this->cajaDiaria->id, 'tipo' => 'egreso', 'concepto' => 'cheque', 'monto' => 5000,
        ]);

        $this->assertEquals(5000, (float) $this->cajaBanco->fresh()->total_cheques_entrada);
        $this->assertEquals(5000, $this->cajaBanco->fresh()->saldo_esperado);
        // El cheque no es efectivo físico: no afecta el arqueo de efectivo del banco.
        $this->assertEquals(0, $this->cajaBanco->fresh()->saldo_efectivo_esperado);
    }

    public function test_rechazar_reabre_deuda_del_cliente(): void
    {
        $cheque = $this->nuevoCheque('depositado');
        $cheque->update(['caja_id' => $this->cajaBanco->id]);

        app(ChequeService::class)->rechazar($cheque, 'Sin fondos');

        $this->assertEquals('rechazado', $cheque->fresh()->estado);
        $this->assertEquals(5000, (float) $this->cliente->fresh()->saldo);
        $this->assertDatabaseHas('cc_clientes', [
            'cliente_id' => $this->cliente->id, 'debe' => 5000, 'referencia_type' => Cheque::class,
        ]);
    }

    public function test_no_permite_transicion_invalida(): void
    {
        $cheque = $this->nuevoCheque(); // cartera
        $this->expectException(\RuntimeException::class);
        app(ChequeService::class)->acreditar($cheque); // cartera → acreditado no permitido
    }

    public function test_emitir_propio_no_egresa_y_debitar_si(): void
    {
        $service = app(ChequeService::class);

        $cheque = $service->registrarEmitido([
            'banco_id' => $this->banco->id, 'nro_cheque' => 'PROP-1', 'monto' => 8000,
            'caja_id' => $this->cajaBanco->id, 'fecha_pago' => now()->addDays(20)->toDateString(),
        ]);

        $this->assertEquals('emitido', $cheque->estado);
        // Al emitir NO sale plata de la caja.
        $this->assertEquals(0, (float) $this->cajaBanco->fresh()->total_cheques_salida);
        $this->assertDatabaseMissing('movimiento_cajas', ['concepto' => 'cheque', 'tipo' => 'egreso']);

        // Al debitarse, recién sale de la caja banco.
        $service->debitar($cheque->fresh(), $this->cajaBanco->id);

        $this->assertEquals('debitado', $cheque->fresh()->estado);
        $this->assertEquals(8000, (float) $this->cajaBanco->fresh()->total_cheques_salida);
        $this->assertEquals(-8000, $this->cajaBanco->fresh()->saldo_esperado);
        $this->assertDatabaseHas('movimiento_cajas', [
            'caja_id' => $this->cajaBanco->id, 'tipo' => 'egreso', 'concepto' => 'cheque', 'monto' => 8000,
        ]);
    }

    public function test_anular_propio_debitado_revierte_egreso(): void
    {
        $service = app(ChequeService::class);
        $cheque = $service->registrarEmitido([
            'banco_id' => $this->banco->id, 'nro_cheque' => 'PROP-2', 'monto' => 3000,
            'caja_id' => $this->cajaBanco->id, 'fecha_pago' => now()->toDateString(),
        ]);
        $service->debitar($cheque->fresh(), $this->cajaBanco->id);
        $this->assertEquals(3000, (float) $this->cajaBanco->fresh()->total_cheques_salida);

        $service->anular($cheque->fresh(), 'Error de carga');

        $this->assertEquals('anulado', $cheque->fresh()->estado);
        $this->assertEquals(0, (float) $this->cajaBanco->fresh()->total_cheques_salida);
    }

    public function test_alta_desde_componente_crea_cheque_en_cartera(): void
    {
        Livewire::test(ChequesIndex::class)
            ->call('openCreate')
            ->set('banco_id', $this->banco->id)
            ->set('nro_cheque', '99887766')
            ->set('monto', 12000)
            ->set('cliente_id', $this->cliente->id)
            ->set('fecha_pago', now()->addDays(15)->toDateString())
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('cheques', [
            'nro_cheque' => '99887766', 'monto' => 12000, 'tipo' => 'tercero', 'estado' => 'cartera',
        ]);
        $this->assertDatabaseHas('cheque_movimientos', ['tipo_mov' => 'recepcion']);
    }

    public function test_render_index(): void
    {
        $this->nuevoCheque();
        Livewire::test(ChequesIndex::class)->assertOk()->assertSee('00012345');
    }

    /** Crea un cheque propio para probar la vista mezclada. */
    private function nuevoPropio(string $nro = 'PROP-9', float $monto = 1000): Cheque
    {
        return Cheque::create([
            'tipo' => 'propio', 'estado' => 'emitido', 'banco_id' => $this->banco->id,
            'nro_cheque' => $nro, 'monto' => $monto,
            'fecha_pago' => now()->addDays(10)->toDateString(),
            'user_id' => $this->user->id,
        ]);
    }

    public function test_el_indice_arranca_mostrando_terceros_y_propios(): void
    {
        $this->nuevoCheque();       // tercero, 00012345
        $this->nuevoPropio();       // propio, PROP-9

        Livewire::test(ChequesIndex::class)
            ->assertSet('filterTipo', '')
            ->assertSee('00012345')
            ->assertSee('PROP-9');
    }

    public function test_filtrar_por_tipo_descarta_un_estado_incompatible(): void
    {
        Livewire::test(ChequesIndex::class)
            ->set('filterEstado', 'cartera')     // solo existe en cheques de tercero
            ->set('filterTipo', 'propio')
            ->assertSet('filterEstado', '');
    }

    public function test_ordenar_por_columna_alterna_asc_desc(): void
    {
        $this->nuevoCheque();                       // monto 5000
        $this->nuevoPropio('PROP-BARATO', 100);     // monto 100

        $comp = Livewire::test(ChequesIndex::class)
            ->call('sortBy', 'monto')
            ->assertSet('sortField', 'monto')
            ->assertSet('sortDir', 'asc');

        $ids = $comp->viewData('cheques')->pluck('monto')->map(fn ($m) => (float) $m)->all();
        $this->assertSame([100.0, 5000.0], $ids);

        $comp->call('sortBy', 'monto')->assertSet('sortDir', 'desc');
        $ids = $comp->viewData('cheques')->pluck('monto')->map(fn ($m) => (float) $m)->all();
        $this->assertSame([5000.0, 100.0], $ids);
    }

    public function test_ordenar_por_banco_usa_la_descripcion(): void
    {
        $otro = Banco::create(['descripcion' => 'AAA Primero', 'activo' => true]);
        $this->nuevoCheque();                                   // Banco Test
        $this->nuevoPropio('PROP-AAA')->update(['banco_id' => $otro->id]);

        $comp = Livewire::test(ChequesIndex::class)->call('sortBy', 'banco');

        $this->assertSame(
            ['AAA Primero', 'Banco Test'],
            $comp->viewData('cheques')->map(fn ($c) => $c->banco->descripcion)->all()
        );
    }

    public function test_un_campo_de_orden_invalido_no_se_aplica(): void
    {
        $this->nuevoCheque();

        Livewire::test(ChequesIndex::class)
            ->call('sortBy', 'monto; DROP TABLE cheques')
            ->assertSet('sortField', 'fecha_pago')
            ->assertOk();
    }
}
