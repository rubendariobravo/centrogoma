<?php

namespace Tests\Feature\Estadisticas;

use App\Livewire\Anticipos\Index as AnticiposIndex;
use App\Livewire\Dashboard;
use App\Livewire\Estadisticas\Clientes as EstadisticasClientes;
use App\Livewire\Estadisticas\Index as EstadisticasIndex;
use App\Livewire\Ventas\Form as VentaForm;
use App\Models\Articulo;
use App\Models\ArticuloPrecio;
use App\Models\Caja;
use App\Models\Cheque;
use App\Models\Cliente;
use App\Models\Empleado;
use App\Models\ListaPrecio;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Estadísticas (portadas de avícola) y tablero de inicio: los comprobantes de saldo inicial
 * importados del Delphi no son ventas del período, los sueldos son solo anticipos y no hay
 * métricas de stock (el negocio no lo lleva).
 */
class EstadisticasTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Caja $caja;
    private Articulo $articulo;
    private Cliente $cliente;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->user->givePermissionTo(Permission::findOrCreate('ver_informes', 'web'));
        $this->actingAs($this->user);

        $this->caja = Caja::create([
            'user_id' => $this->user->id, 'tipo' => 'diaria', 'estado' => 'abierta',
            'fecha_apertura' => now()->toDateString(), 'hora_apertura' => now(), 'saldo_inicial' => 0,
        ]);
        Cliente::create(['razon_social' => 'Consumidor final', 'estado' => 'A', 'saldo' => 0]);
        $this->cliente = Cliente::create(['razon_social' => 'Gomería del Norte', 'estado' => 'A', 'saldo' => 0]);

        $lista = ListaPrecio::firstOrCreate(
            ['codigo' => 'MINORISTA'],
            ['nombre' => 'Minorista', 'tipo' => 'minorista', 'es_default' => true, 'activo' => true, 'orden' => 1]
        );
        $this->articulo = Articulo::create([
            'codigo' => '1820', 'descripcion' => 'Guante', 'tipo_venta' => 'CAJA', 'unidad_medida' => 'unidad',
            'precio_costo' => 8000, 'precio_unitario' => 12396.6942, 'precio_unitario_caja' => 0,
            'alicuota_iva' => 21, 'estado' => 'A', 'modifica_stock' => true, 'stock' => 0,
        ]);
        ArticuloPrecio::create([
            'articulo_id' => $this->articulo->id, 'lista_precio_id' => $lista->id,
            'cantidad_desde' => 1, 'cantidad_hasta' => null, 'precio' => 12396.6942, 'activo' => true, 'orden' => 0,
        ]);

        // Contado $15.000 a consumidor final + $30.000 en CC + la deuda importada del Delphi.
        $this->vender(1, 'efectivo');
        $this->vender(2, 'cuenta_corriente', $this->cliente);
        $this->saldoInicial($this->cliente, 1000000);
    }

    private function vender(float $cantidad, string $formaPago, ?Cliente $cliente = null): void
    {
        $lw = Livewire::test(VentaForm::class);
        if ($cliente) {
            $lw->set('cliente_id', $cliente->id);
        }
        $lw->call('seleccionarArticulo', 0, $this->articulo->id, $cantidad);
        $lw->call('registrarVenta', [
            'items'       => $lw->get('items'),
            'clienteData' => $lw->get('clienteData'),
            'pagos'       => [['forma_pago' => $formaPago, 'monto' => 15000 * $cantidad]],
            'total'       => 15000 * $cantidad,
        ])->assertHasNoErrors();
    }

    /** Comprobante de saldo inicial como lo deja centrogoma:importar-saldos (fecha de hoy = corte). */
    private function saldoInicial(Cliente $cliente, float $monto): void
    {
        Venta::create([
            'cliente_id' => $cliente->id, 'nro_venta' => (int) Venta::max('nro_venta') + 1,
            'fecha' => now()->toDateString(), 'tipo_comprobante' => 'factura', 'saldo_inicial' => true,
            'neto' => 0, 'neto_21' => 0, 'neto_10_5' => 0, 'monto_iva21' => 0, 'monto_iva10_5' => 0,
            'total' => $monto, 'saldo' => $monto, 'estado' => 'P',
        ]);
        $cliente->update(['saldo' => (float) $cliente->fresh()->saldo + $monto]);
    }

    public function test_el_resumen_no_cuenta_los_saldos_iniciales_como_ventas(): void
    {
        $lw = Livewire::test(EstadisticasIndex::class);

        $lw->assertViewHas('totalVentas', 45000.0)
           ->assertViewHas('cantVentas', 2)
           ->assertViewHas('cobranzaResumen', fn ($c) => $c['en_la_venta'] == 15000 && $c['pendiente'] == 30000)
           ->assertViewHas('ventasPorProducto', fn ($p) => $p->count() === 1 && (float) $p->first()->total_civa == 45000);
    }

    public function test_los_sueldos_son_los_anticipos_y_el_resultado_no_usa_stock(): void
    {
        $empleado = Empleado::create(['nombre' => 'Noelia', 'apellido' => 'Samudio', 'rol' => 'administrativo', 'comision' => 0, 'estado' => 'A']);
        Livewire::test(AnticiposIndex::class)
            ->call('openCreate')
            ->set('empleado_id', $empleado->id)
            ->set('fecha', now()->toDateString())
            ->set('monto', '5000')
            ->set('pagos', [['forma_pago' => 'efectivo', 'caja_id' => $this->caja->id, 'monto' => '5000', 'referencia' => '']])
            ->call('save')
            ->assertHasNoErrors();

        Livewire::test(EstadisticasIndex::class)
            ->call('setTab', 'sueldos')
            ->assertSee('Noelia Samudio')
            ->assertViewHas('totalSueldos', 5000.0)
            ->assertViewHas('resumen', fn ($r) => $r['resultado'] == 40000 && ! array_key_exists('stock_valorizado', $r));
    }

    public function test_clientes_top_filtra_con_el_buscador(): void
    {
        Livewire::test(EstadisticasClientes::class)
            ->assertViewHas('ranking', fn ($r) => $r->count() === 2 && $r->first()->cliente === 'Gomería del Norte')
            ->set('clienteBuscar', 'gomer')
            ->assertSee('Gomería del Norte')
            ->call('seleccionarCliente', $this->cliente->id)
            ->assertSet('clienteId', $this->cliente->id)
            ->assertViewHas('ranking', fn ($r) => $r->count() === 1 && (float) $r->first()->monto == 30000);
    }

    public function test_estadisticas_requieren_permiso_de_informes(): void
    {
        $this->get(route('estadisticas'))->assertOk();

        $this->actingAs(User::factory()->create());
        $this->get(route('estadisticas'))->assertForbidden();
    }

    public function test_dashboard_muestra_ventas_saldos_y_cheques_sin_stock(): void
    {
        Cheque::create([
            'tipo' => 'tercero', 'estado' => 'cartera', 'nro_cheque' => '445566', 'monto' => 80000,
            'librador_nombre' => 'Transportes Salta', 'fecha_recepcion' => now()->toDateString(),
            'fecha_pago' => now()->addDays(3)->toDateString(), 'user_id' => $this->user->id,
        ]);
        Cheque::create([
            'tipo' => 'tercero', 'estado' => 'cartera', 'nro_cheque' => '778899', 'monto' => 10000,
            'librador_nombre' => 'Lejano SRL', 'fecha_recepcion' => now()->toDateString(),
            'fecha_pago' => now()->addDays(40)->toDateString(), 'user_id' => $this->user->id,
        ]);

        Livewire::test(Dashboard::class)
            ->assertSet('ventasHoy', 2)
            ->assertSet('totalHoy', 45000.0)
            ->assertSet('aCobrar', 1030000.0)
            ->assertSet('deudores', 1)
            ->assertSee('Transportes Salta')
            ->assertDontSee('Lejano SRL')
            ->assertSee('Gomería del Norte')
            ->assertDontSee('Stock');
    }
}
