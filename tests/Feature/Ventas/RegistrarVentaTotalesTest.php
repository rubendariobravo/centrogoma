<?php

namespace Tests\Feature\Ventas;

use App\Livewire\Ventas\Form as VentaForm;
use App\Models\Articulo;
use App\Models\ArticuloPrecio;
use App\Models\Caja;
use App\Models\CcCliente;
use App\Models\Cliente;
use App\Models\DetVenta;
use App\Models\ListaPrecio;
use App\Models\MovimientoCaja;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Totales al registrar una venta: precio neto con 4 decimales (un final de $15.000 se cobra
 * $15.000, no $14.999,99), totales recalculados en el servidor y registro todo-o-nada.
 * Casos tomados de la simulación con las ventas del Delphi del 31/10/2026.
 */
class RegistrarVentaTotalesTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Caja $caja;
    private Articulo $articulo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        $this->caja = Caja::create([
            'user_id' => $this->user->id, 'tipo' => 'diaria', 'estado' => 'abierta',
            'fecha_apertura' => now()->toDateString(), 'hora_apertura' => now(), 'saldo_inicial' => 0,
        ]);
        Cliente::create(['razon_social' => 'Consumidor final', 'estado' => 'A', 'saldo' => 0]);

        $lista = ListaPrecio::firstOrCreate(
            ['codigo' => 'MINORISTA'],
            ['nombre' => 'Minorista', 'tipo' => 'minorista', 'es_default' => true, 'activo' => true, 'orden' => 1]
        );
        // Precio de lista $15.000 final, guardado neto como lo deja el importador.
        $this->articulo = Articulo::create([
            'codigo' => '1820', 'descripcion' => 'Guante', 'tipo_venta' => 'CAJA', 'unidad_medida' => 'unidad',
            'precio_costo' => 8000, 'precio_unitario' => 12396.6942, 'precio_unitario_caja' => 0,
            'alicuota_iva' => 21, 'estado' => 'A', 'modifica_stock' => true, 'stock' => 10,
        ]);
        ArticuloPrecio::create([
            'articulo_id' => $this->articulo->id, 'lista_precio_id' => $lista->id,
            'cantidad_desde' => 1, 'cantidad_hasta' => null, 'precio' => 12396.6942, 'activo' => true, 'orden' => 0,
        ]);
    }

    /** Carga el artículo como el operador y registra con el payload de Alpine (con el total indicado). */
    private function vender(float $cantidad, array $pagos, ?Cliente $cliente = null, array $extraPayload = []): \Livewire\Features\SupportTesting\Testable
    {
        $lw = Livewire::test(VentaForm::class);
        if ($cliente) {
            $lw->set('cliente_id', $cliente->id);
        }
        $lw->call('seleccionarArticulo', 0, $this->articulo->id, $cantidad);

        return $lw->call('registrarVenta', array_merge([
            'items'       => $lw->get('items'),
            'clienteData' => $lw->get('clienteData'),
            'pagos'       => $pagos,
            'total'       => 15000 * $cantidad,
        ], $extraPayload));
    }

    public function test_precio_de_lista_redondo_se_cobra_exacto(): void
    {
        $lw = Livewire::test(VentaForm::class)->call('seleccionarArticulo', 0, $this->articulo->id, 1);
        $this->assertEquals(12396.6942, $lw->get('items.0.precio'));

        $this->vender(1, [['forma_pago' => 'efectivo', 'monto' => 15000]])->assertHasNoErrors();

        $venta = Venta::firstOrFail();
        $this->assertEquals(15000.00, (float) $venta->total);
        $this->assertEquals(12396.69, (float) $venta->neto_21);
        $this->assertEquals(2603.31, (float) $venta->monto_iva21);
        // El renglón guarda el mismo IVA que el cabezal (final − neto), no neto × 21%: 2.603,30.
        $this->assertEquals(2603.31, (float) DetVenta::firstOrFail()->subtotal_iva);
        $this->assertEquals('C', $venta->estado);
        $this->assertEquals(0, (float) $venta->saldo);
    }

    public function test_calcular_totales_reproduce_el_ticket_del_delphi(): void
    {
        // Venta 329128 del Delphi: 40 u. a $150 final = $6.000 (neto 4.958,68 + IVA 1.041,32).
        $t = VentaForm::calcularTotales([
            ['precio' => 123.9669, 'cantidad' => 40, 'alicuota_iva' => 21, 'descuento' => 0],
        ], []);

        $this->assertEquals(6000.00, $t['total']);
        $this->assertEquals(4958.68, $t['neto_21']);
        $this->assertEquals(1041.32, $t['iva_21']);
    }

    public function test_el_total_que_manda_el_navegador_no_se_graba(): void
    {
        $cliente = Cliente::create(['razon_social' => 'Cliente CC', 'estado' => 'A', 'saldo' => 0]);

        $this->vender(2, [['forma_pago' => 'cuenta_corriente', 'monto' => 1]], $cliente, [
            'total' => 1, 'neto_21' => 1, 'iva_21' => 0,
        ])->assertHasNoErrors();

        $venta = Venta::firstOrFail();
        $this->assertEquals(30000.00, (float) $venta->total);
        $this->assertEquals(30000.00, (float) $venta->saldo);
        $this->assertEquals(30000.00, (float) $cliente->fresh()->saldo);
        $this->assertEquals(30000.00, (float) CcCliente::firstOrFail()->debe);
        $this->assertEquals(30000.00, (float) MovimientoCaja::sum('monto'));
    }

    public function test_las_percepciones_salen_del_cliente_en_la_bd(): void
    {
        $cliente = Cliente::create([
            'razon_social' => 'Agente IIBB', 'estado' => 'A', 'saldo' => 0, 'porcentaje_iibb' => 0.03,
        ]);

        // El navegador manda el cliente sin percepción: igual se cobra el 3% sobre el neto.
        $this->vender(1, [['forma_pago' => 'efectivo', 'monto' => 15371.90]], $cliente, [
            'clienteData' => ['porcentaje_iibb' => 0, 'percepcion_iva' => false],
        ])->assertHasNoErrors();

        $venta = Venta::firstOrFail();
        $this->assertEquals(371.90, (float) $venta->percepcion);
        $this->assertEquals(15371.90, (float) $venta->total);
    }

    public function test_si_algo_falla_no_queda_nada_grabado(): void
    {
        DetVenta::creating(fn () => throw new \RuntimeException('falla simulada'));

        try {
            $this->vender(1, [['forma_pago' => 'efectivo', 'monto' => 15000]]);
            $this->fail('Se esperaba la excepción');
        } catch (\RuntimeException $e) {
            $this->assertSame('falla simulada', $e->getMessage());
        }

        $this->assertEquals(0, Venta::withTrashed()->count());
        $this->assertEquals(0, MovimientoCaja::count());
        $this->assertEquals(10, (float) $this->articulo->fresh()->stock);
    }
}
