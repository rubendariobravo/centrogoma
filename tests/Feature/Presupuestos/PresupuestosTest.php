<?php

namespace Tests\Feature\Presupuestos;

use App\Livewire\Presupuestos\Form as PresupuestoForm;
use App\Livewire\Ventas\Form as VentaForm;
use App\Models\Articulo;
use App\Models\ArticuloPrecio;
use App\Models\Caja;
use App\Models\Cliente;
use App\Models\DetVenta;
use App\Models\ListaPrecio;
use App\Models\Presupuesto;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Presupuestos como se usan en el Delphi: precio pactado por renglón (distinto del de lista),
 * sin cliente registrado, "modificar" que en realidad duplica, y pasaje a venta respetando
 * cantidades y precios del presupuesto, con ajustes.
 */
class PresupuestosTest extends TestCase
{
    use RefreshDatabase;

    private Cliente $cliente;
    private Articulo $guante;   // lista: $15.000 final
    private Articulo $botas;    // lista: $37.000 final

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create();
        $this->actingAs($user);
        Caja::create([
            'user_id' => $user->id, 'tipo' => 'diaria', 'estado' => 'abierta',
            'fecha_apertura' => now()->toDateString(), 'hora_apertura' => now(), 'saldo_inicial' => 0,
        ]);
        Cliente::create(['razon_social' => 'Consumidor final', 'estado' => 'A', 'saldo' => 0]);
        $this->cliente = Cliente::create(['razon_social' => 'Escuela Zorrilla', 'estado' => 'A', 'saldo' => 0]);

        $lista = ListaPrecio::firstOrCreate(
            ['codigo' => 'MINORISTA'],
            ['nombre' => 'Minorista', 'tipo' => 'minorista', 'es_default' => true, 'activo' => true, 'orden' => 1]
        );
        $this->guante = $this->articulo('1820', 'Guante', 12396.6942, $lista);
        $this->botas  = $this->articulo('4574', 'Botas', 30578.5124, $lista);
    }

    private function articulo(string $codigo, string $descripcion, float $neto, ListaPrecio $lista): Articulo
    {
        $art = Articulo::create([
            'codigo' => $codigo, 'descripcion' => $descripcion, 'tipo_venta' => 'CAJA', 'unidad_medida' => 'unidad',
            'precio_costo' => 1000, 'precio_unitario' => $neto, 'precio_unitario_caja' => 0,
            'alicuota_iva' => 21, 'estado' => 'A', 'modifica_stock' => true, 'stock' => 0,
        ]);
        ArticuloPrecio::create([
            'articulo_id' => $art->id, 'lista_precio_id' => $lista->id,
            'cantidad_desde' => 1, 'cantidad_hasta' => null, 'precio' => $neto, 'activo' => true, 'orden' => 0,
        ]);

        return $art;
    }

    /** Presupuesto armado con el form: 2 guantes a $14.000 (pactado; lista $15.000). */
    private function presupuestoPactado(?Cliente $cliente = null, array $extra = []): Presupuesto
    {
        $lw = Livewire::test(PresupuestoForm::class);
        if ($cliente) {
            $lw->call('seleccionarCliente', $cliente->id);
        }
        $lw->call('seleccionarArticulo', 0, $this->guante->id)
           ->set('items.0.cantidad', 2)
           ->set('items.0.precio', 14000);
        foreach ($extra as $prop => $valor) {
            $lw->set($prop, $valor);
        }
        $lw->call('save')->assertHasNoErrors();

        return Presupuesto::latest('id')->firstOrFail();
    }

    public function test_el_articulo_entra_con_el_precio_final_de_lista(): void
    {
        Livewire::test(PresupuestoForm::class)
            ->call('seleccionarArticulo', 0, $this->guante->id)
            ->assertSet('items.0.precio', 15000.0)
            ->assertSet('total', 15000.0);
    }

    public function test_reabrir_y_guardar_no_cambia_los_precios(): void
    {
        $p = $this->presupuestoPactado($this->cliente);

        $this->assertEquals(28000.0, $p->total);
        $this->assertEquals(11570.2479, (float) $p->detalles->first()->precio_unitario);

        // Antes, cada edición le sacaba otro 21% al precio.
        Livewire::test(PresupuestoForm::class, ['presupuesto' => $p->id])
            ->assertSet('items.0.precio', 14000.0)
            ->assertSet('total', 28000.0)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertEquals(28000.0, $p->fresh()->total);
        $this->assertEquals(11570.2479, (float) $p->fresh()->detalles->first()->precio_unitario);
    }

    public function test_presupuesto_sin_cliente_registrado(): void
    {
        $p = $this->presupuestoPactado(null, ['cliente_nombre' => 'Obra calle Belgrano']);

        $this->assertNull($p->cliente_id);
        $this->assertSame('Obra calle Belgrano', $p->nombre_cliente);
        $this->get(route('presupuestos.pdf', $p))->assertOk();
    }

    public function test_duplicar_copia_y_permite_ajustar_antes_de_guardar(): void
    {
        $original = $this->presupuestoPactado($this->cliente, ['condicion_pago' => 'CTA.CTE.', 'plazo_entrega' => 'INMEDIATO']);
        $original->update(['estado' => 'F']);   // se puede duplicar en cualquier estado

        Livewire::withQueryParams(['duplicar' => $original->id])
            ->test(PresupuestoForm::class)
            ->assertSet('presupuestoId', null)
            ->assertSet('duplicadoDe', $original->nro_presupuesto)
            ->assertSet('cliente_id', $this->cliente->id)
            ->assertSet('condicion_pago', 'CTA.CTE.')
            ->assertSet('fecha', now()->toDateString())
            ->assertSet('items.0.precio', 14000.0)
            // ajustes antes de registrar
            ->set('items.0.cantidad', 3)
            ->call('addItem')
            ->call('seleccionarArticulo', 1, $this->botas->id)
            ->call('save')
            ->assertHasNoErrors();

        $copia = Presupuesto::latest('id')->firstOrFail();
        $this->assertNotSame($original->id, $copia->id);
        $this->assertSame($original->nro_presupuesto + 1, $copia->nro_presupuesto);
        $this->assertSame('A', $copia->estado);
        $this->assertEquals(3 * 14000 + 37000, $copia->total);

        // El original no cambió.
        $this->assertSame('F', $original->fresh()->estado);
        $this->assertEquals(28000.0, $original->fresh()->total);
        $this->assertCount(1, $original->fresh()->detalles);
    }

    public function test_duplicar_puede_actualizar_los_precios_a_la_lista_de_hoy(): void
    {
        $original = $this->presupuestoPactado($this->cliente);

        Livewire::withQueryParams(['duplicar' => $original->id])
            ->test(PresupuestoForm::class)
            ->call('actualizarPreciosALista')
            ->assertSet('items.0.precio', 15000.0)
            ->assertSet('total', 30000.0);
    }

    public function test_pasar_a_venta_respeta_cantidades_y_precios_del_presupuesto(): void
    {
        $p = $this->presupuestoPactado($this->cliente);

        $lw = Livewire::withQueryParams(['presupuesto_id' => $p->id])->test(VentaForm::class)
            ->assertSet('cliente_id', $this->cliente->id)
            ->assertSet('items.0.cantidad', 2.0)
            ->assertSet('items.0.precio', 11570.2479)
            ->assertSet('items.0.precio_manual', true)
            ->assertSet('items.0.precio_lista', 12396.6942)   // referencia "Lista hoy"
            ->assertSee('Lista hoy');

        $lw->call('registrarVenta', [
            'items'       => $lw->get('items'),
            'clienteData' => $lw->get('clienteData'),
            'pagos'       => [['forma_pago' => 'cuenta_corriente', 'monto' => 28000]],
            'total'       => 28000,
        ])->assertHasNoErrors();

        // Mismo total que el presupuesto, al centavo; el motor no reemplazó el precio pactado.
        $venta = Venta::firstOrFail();
        $this->assertEquals(28000.0, (float) $venta->total);
        $this->assertEquals($p->total, (float) $venta->total);
        $det = DetVenta::firstOrFail();
        $this->assertEquals(11570.2479, (float) $det->precio_unitario);
        $this->assertSame('presupuesto', $det->regla_aplicada);

        $p->refresh();
        $this->assertSame('F', $p->estado);
        $this->assertSame($venta->id, $p->venta_id);
    }

    public function test_en_la_venta_se_ajusta_lo_que_vino_del_presupuesto(): void
    {
        $p = $this->presupuestoPactado($this->cliente);

        $lw = Livewire::withQueryParams(['presupuesto_id' => $p->id])->test(VentaForm::class)
            ->call('actualizarCantidad', 0, 3)              // más cantidad: mantiene el precio pactado
            ->assertSet('items.0.precio', 11570.2479)
            ->call('addItem')
            ->call('seleccionarArticulo', 1, $this->botas->id, 1);   // renglón nuevo: precio de lista

        $lw->call('registrarVenta', [
            'items'       => $lw->get('items'),
            'clienteData' => $lw->get('clienteData'),
            'pagos'       => [['forma_pago' => 'efectivo', 'monto' => 79000]],
            'total'       => 79000,
        ])->assertHasNoErrors();

        $this->assertEquals(3 * 14000 + 37000, (float) Venta::firstOrFail()->total);
    }

    public function test_en_la_venta_se_puede_pasar_un_renglon_al_precio_de_lista(): void
    {
        $p = $this->presupuestoPactado($this->cliente);

        Livewire::withQueryParams(['presupuesto_id' => $p->id])->test(VentaForm::class)
            ->call('restaurarPrecio', 0)
            ->assertSet('items.0.precio', 12396.6942)
            ->assertSet('items.0.precio_manual', false);
    }

    public function test_presupuesto_vencido_y_sin_cliente_pasa_a_venta_con_aviso(): void
    {
        $p = $this->presupuestoPactado(null, [
            'cliente_nombre' => 'Obra calle Belgrano',
            'fecha'          => now()->subDays(30)->toDateString(),
        ]);

        Livewire::withQueryParams(['presupuesto_id' => $p->id])->test(VentaForm::class)
            ->assertSet('presupuestoVencido', true)
            ->assertSee('vencido')
            ->assertSet('cliente_id', null)
            ->assertSet('observacion', "Presupuesto #{$p->nro_presupuesto} — Obra calle Belgrano")
            ->assertSet('items.0.precio', 11570.2479);
    }

    public function test_el_listado_ofrece_duplicar_y_muestra_el_nombre_sin_cliente(): void
    {
        $p = $this->presupuestoPactado(null, ['cliente_nombre' => 'Obra calle Belgrano']);

        Livewire::test(\App\Livewire\Presupuestos\Index::class)
            ->assertSee('Obra calle Belgrano')
            ->assertSee(route('presupuestos.create', ['duplicar' => $p->id]), false)
            ->set('search', 'Belgrano')
            ->assertSee('Obra calle Belgrano');
    }
    public function test_presupuesto_sin_iva_cotiza_netos_y_se_imprime_asi(): void
    {
        $lw = Livewire::test(PresupuestoForm::class)
            ->call('seleccionarArticulo', 0, $this->guante->id)
            ->assertSet('items.0.precio', 15000.0)
            ->call('cambiarModoIva', false)
            ->assertSet('items.0.precio', 12396.69)
            ->call('cambiarModoIva', false)                 // mismo modo: no vuelve a convertir
            ->assertSet('items.0.precio', 12396.69)
            ->set('items.0.cantidad', 2)
            ->set('items.0.precio', 10000)                  // pactado: $10.000 + IVA
            ->set('leyenda_iva', true)
            ->call('save')
            ->assertHasNoErrors();

        $p = Presupuesto::latest('id')->firstOrFail();
        $this->assertFalse($p->precios_con_iva);
        $this->assertEquals(10000.0, (float) $p->detalles->first()->precio_unitario);
        $this->assertEquals(24200.0, $p->total);

        // Al reabrir sigue en netos; al pasar a "con IVA" muestra el final.
        Livewire::test(PresupuestoForm::class, ['presupuesto' => $p->id])
            ->assertSet('precios_con_iva', false)
            ->assertSet('items.0.precio', 10000.0)
            ->call('cambiarModoIva', true)
            ->assertSet('items.0.precio', 12100.0)
            ->assertSet('total', 24200.0);

        $html = view('presupuestos.pdf', ['presupuesto' => $p->load('detalles.articulo'), 'empresa' => ['nombre' => 'CG']])->render();
        $this->assertStringContainsString('$10.000,00', $html);
        $this->assertStringContainsString('TOTAL s/IVA', $html);
        $this->assertStringContainsString('$20.000,00', $html);
        $this->assertStringContainsString('Los precios no incluyen IVA.', $html);

        $p->update(['leyenda_iva' => false]);
        $html = view('presupuestos.pdf', ['presupuesto' => $p->fresh()->load('detalles.articulo'), 'empresa' => ['nombre' => 'CG']])->render();
        $this->assertStringNotContainsString('incluyen IVA', $html);
    }

    public function test_presupuesto_con_iva_imprime_el_final_y_la_venta_no_cambia(): void
    {
        $p = $this->presupuestoPactado($this->cliente);
        $html = view('presupuestos.pdf', ['presupuesto' => $p->load('detalles.articulo'), 'empresa' => ['nombre' => 'CG']])->render();

        $this->assertStringContainsString('$14.000,00', $html);
        $this->assertStringContainsString('$28.000,00', $html);
        $this->assertStringContainsString('Los precios incluyen IVA.', $html);
    }
}
