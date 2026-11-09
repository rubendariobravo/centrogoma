<?php

namespace Tests\Feature\Ventas;

use App\Livewire\Cajas\Show as CajaShow;
use App\Livewire\Recibos\Form as ReciboForm;
use App\Livewire\Ventas\Form as VentaForm;
use App\Models\Articulo;
use App\Models\ArticuloPrecio;
use App\Models\Caja;
use App\Models\Cliente;
use App\Models\ListaPrecio;
use App\Models\MovimientoCaja;
use App\Models\PagoVenta;
use App\Models\Recibo;
use App\Models\Tarjeta;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Formas de pago del Delphi: tarjeta por marca (para conciliar las liquidaciones), Mercado Pago,
 * y la orden de compra del cliente en la factura.
 */
class FormasPagoTarjetaTest extends TestCase
{
    use RefreshDatabase;

    private Caja $caja;
    private Articulo $articulo;
    private Cliente $cliente;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create();
        $this->actingAs($user);
        $this->caja = Caja::create([
            'user_id' => $user->id, 'tipo' => 'diaria', 'estado' => 'abierta',
            'fecha_apertura' => now()->toDateString(), 'hora_apertura' => now(), 'saldo_inicial' => 0,
        ]);
        Cliente::create(['razon_social' => 'Consumidor final', 'estado' => 'A', 'saldo' => 0]);
        $this->cliente = Cliente::create(['razon_social' => 'Taller Norte', 'estado' => 'A', 'saldo' => 0]);

        $lista = ListaPrecio::firstOrCreate(['codigo' => 'MINORISTA'],
            ['nombre' => 'Minorista', 'tipo' => 'minorista', 'es_default' => true, 'activo' => true, 'orden' => 1]);
        $this->articulo = Articulo::create([
            'codigo' => '1820', 'descripcion' => 'Guante', 'tipo_venta' => 'CAJA', 'unidad_medida' => 'unidad',
            'precio_costo' => 8000, 'precio_unitario' => 12396.6942, 'precio_unitario_caja' => 0,
            'alicuota_iva' => 21, 'estado' => 'A', 'modifica_stock' => false, 'stock' => 0,
        ]);
        ArticuloPrecio::create(['articulo_id' => $this->articulo->id, 'lista_precio_id' => $lista->id,
            'cantidad_desde' => 1, 'cantidad_hasta' => null, 'precio' => 12396.6942, 'activo' => true, 'orden' => 0]);
    }

    private function tarjeta(string $nombre): Tarjeta
    {
        return Tarjeta::where('nombre', $nombre)->firstOrFail();
    }

    private function vender(array $pagos, array $sets = []): \Livewire\Features\SupportTesting\Testable
    {
        $lw = Livewire::test(VentaForm::class);
        foreach ($sets as $k => $v) {
            $lw->set($k, $v);
        }
        $lw->call('seleccionarArticulo', 0, $this->articulo->id, 1);

        return $lw->call('registrarVenta', [
            'items' => $lw->get('items'), 'clienteData' => $lw->get('clienteData'), 'pagos' => $pagos, 'total' => 15000,
        ]);
    }

    public function test_las_marcas_y_mercado_pago_vienen_sembradas(): void
    {
        $this->assertSame('Visa Débito', Tarjeta::orderBy('orden')->value('nombre'));
        $this->assertSame(16, Tarjeta::count());
        Livewire::test(VentaForm::class)
            ->assertSee('Mercado Pago')
            ->assertSee('Naranja');
    }

    public function test_venta_con_tarjeta_guarda_la_marca_en_el_pago_y_en_la_caja(): void
    {
        $visa = $this->tarjeta('Visa');

        $this->vender([
            ['forma_pago' => 'tarjeta', 'tarjeta_id' => $visa->id, 'monto' => 10000],
            ['forma_pago' => 'mercado_pago', 'monto' => 5000],
        ], ['orden_compra' => 'OC-4471'])->assertHasNoErrors();

        $venta = Venta::firstOrFail();
        $this->assertSame('OC-4471', $venta->orden_compra);
        $this->assertSame('C', $venta->estado);

        $pago = PagoVenta::where('forma_pago', 'tarjeta')->firstOrFail();
        $this->assertSame($visa->id, $pago->tarjeta_id);
        $this->assertSame('Tarjeta · Visa', $pago->etiqueta);
        $this->assertSame('Mercado Pago', PagoVenta::where('forma_pago', 'mercado_pago')->firstOrFail()->etiqueta);

        $this->assertSame($visa->id, MovimientoCaja::where('forma_pago', 'tarjeta')->value('tarjeta_id'));
        // Ni la tarjeta ni Mercado Pago entran al cajón.
        $this->assertEquals(0.0, $this->caja->fresh()->saldo_efectivo_esperado);
        $this->assertEquals(15000.0, $this->caja->fresh()->resumenMovimientos()['otros_medios']);

        // La factura muestra la OC y la marca.
        $html = $this->get(route('ventas.imprimir', $venta))->assertOk()->getContent();
        $this->assertStringContainsString('OC-4471', $html);
        $this->assertStringContainsString('Tarjeta · Visa', $html);
    }

    public function test_tarjeta_sin_marca_no_se_registra(): void
    {
        $this->vender([['forma_pago' => 'tarjeta', 'tarjeta_id' => '', 'monto' => 15000]])
            ->assertHasErrors('pagos');

        $this->assertSame(0, Venta::count());
    }

    public function test_forma_de_pago_inexistente_no_se_registra(): void
    {
        $this->vender([['forma_pago' => 'bitcoin', 'monto' => 15000]])->assertHasErrors('pagos');
        $this->assertSame(0, Venta::count());
    }

    public function test_recibo_con_tarjeta_pide_la_marca_y_la_guarda(): void
    {
        $this->vender([['forma_pago' => 'cuenta_corriente', 'monto' => 15000]], ['cliente_id' => $this->cliente->id])
            ->assertHasNoErrors();
        $venta = Venta::firstOrFail();
        $naranja = $this->tarjeta('Naranja');

        $lw = Livewire::test(ReciboForm::class)
            ->set('cliente_id', $this->cliente->id)
            ->set('forma_pago', 'tarjeta')
            ->call('save', [['venta_id' => $venta->id, 'monto_aplicar' => 15000]])
            ->assertHasErrors('tarjeta_id');

        $lw->set('tarjeta_id', $naranja->id)
            ->call('save', [['venta_id' => $venta->id, 'monto_aplicar' => 15000]])
            ->assertHasNoErrors();

        $recibo = Recibo::firstOrFail();
        $this->assertSame($naranja->id, $recibo->tarjeta_id);
        $this->assertSame($naranja->id, MovimientoCaja::where('concepto', 'cobro')->value('tarjeta_id'));

        // Un recibo no puede "cobrar" en cuenta corriente.
        Livewire::test(ReciboForm::class)->set('cliente_id', $this->cliente->id)->set('forma_pago', 'cuenta_corriente')
            ->call('save', [['venta_id' => $venta->id, 'monto_aplicar' => 1]])
            ->assertHasErrors('forma_pago');
    }

    public function test_la_caja_muestra_lo_cobrado_por_marca(): void
    {
        $this->vender([['forma_pago' => 'tarjeta', 'tarjeta_id' => $this->tarjeta('Visa')->id, 'monto' => 15000]]);
        $this->vender([['forma_pago' => 'tarjeta', 'tarjeta_id' => $this->tarjeta('Naranja')->id, 'monto' => 15000]]);
        $this->vender([['forma_pago' => 'tarjeta', 'tarjeta_id' => $this->tarjeta('Visa')->id, 'monto' => 15000]]);

        $porMedio = Livewire::test(CajaShow::class, ['caja' => $this->caja])
            ->assertSee('Cobrado por medio')
            ->viewData('cobrosPorMedio');

        $this->assertEquals(['cantidad' => 2, 'total' => 30000.0], $porMedio['Tarjeta · Visa']);
        $this->assertEquals(['cantidad' => 1, 'total' => 15000.0], $porMedio['Tarjeta · Naranja']);
    }
}
