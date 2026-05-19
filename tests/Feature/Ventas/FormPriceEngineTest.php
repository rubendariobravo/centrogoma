<?php

namespace Tests\Feature\Ventas;

use App\Livewire\Ventas\Form;
use App\Models\Articulo;
use App\Models\ArticuloPrecio;
use App\Models\Cliente;
use App\Models\ListaPrecio;
use App\Models\Rubro;
use App\Models\TipoIva;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Tests de integracion del Livewire Ventas\Form con el PriceEngine.
 *
 * Cubre:
 * - seleccionarArticulo carga datos y cotiza.
 * - actualizarCantidad re-cotiza.
 * - Articulo duplicado suma cantidades en una sola fila (regla del cliente).
 * - marcarPrecioManual evita re-cotizar.
 * - Cambiar de cliente re-cotiza items con la lista nueva.
 */
class FormPriceEngineTest extends TestCase
{
    use RefreshDatabase;

    private ListaPrecio $minorista;
    private ListaPrecio $mayorista;
    private Articulo $pollo;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        $this->minorista = ListaPrecio::firstOrCreate(
            ['codigo' => 'MINORISTA'],
            ['nombre' => 'Minorista', 'tipo' => 'minorista', 'es_default' => true, 'activo' => true, 'orden' => 1]
        );
        $this->mayorista = ListaPrecio::firstOrCreate(
            ['codigo' => 'MAYORISTA'],
            ['nombre' => 'Mayorista', 'tipo' => 'mayorista', 'es_default' => false, 'activo' => true, 'orden' => 2]
        );

        $rubro = Rubro::create(['descripcion' => 'Pollos', 'estado' => 'A']);

        $this->pollo = Articulo::create([
            'codigo' => 'POL001', 'descripcion' => 'Pollo entero',
            'rubro_id' => $rubro->id,
            'tipo_venta' => 'PESO', 'unidad_medida' => 'kg',
            'precio_costo' => 2000, 'precio_unitario' => 3000, 'precio_unitario_caja' => 0,
            'alicuota_iva' => 10.5, 'estado' => 'A',
        ]);

        // MINORISTA: 1-4=$3000, 5+=$2800
        ArticuloPrecio::create([
            'articulo_id' => $this->pollo->id, 'lista_precio_id' => $this->minorista->id,
            'cantidad_desde' => 1, 'cantidad_hasta' => 4, 'precio' => 3000, 'activo' => true, 'orden' => 0,
        ]);
        ArticuloPrecio::create([
            'articulo_id' => $this->pollo->id, 'lista_precio_id' => $this->minorista->id,
            'cantidad_desde' => 5, 'cantidad_hasta' => null, 'precio' => 2800, 'activo' => true, 'orden' => 1,
        ]);
        // MAYORISTA: 1+=$2400
        ArticuloPrecio::create([
            'articulo_id' => $this->pollo->id, 'lista_precio_id' => $this->mayorista->id,
            'cantidad_desde' => 1, 'cantidad_hasta' => null, 'precio' => 2400, 'activo' => true, 'orden' => 0,
        ]);
    }

    private function clienteCon(?ListaPrecio $lista): Cliente
    {
        $tiva = TipoIva::firstOrCreate(['codigo_afip' => '05'], ['descripcion' => 'CF']);
        return Cliente::create([
            'razon_social'    => 'Cli',
            'tipoiva_id'      => $tiva->id,
            'lista_precio_id' => $lista?->id,
            'estado'          => 'A',
        ]);
    }

    public function test_seleccionar_articulo_carga_datos_y_cotiza(): void
    {
        Livewire::test(Form::class)
            ->call('seleccionarArticulo', 0, $this->pollo->id)
            ->assertSet('items.0.articulo_id', $this->pollo->id)
            ->assertSet('items.0.descripcion', 'Pollo entero')
            ->assertSet('items.0.cantidad', 1)
            ->assertSet('items.0.precio', 3000.0)
            ->assertSet('items.0.subtotal', 3000.0)
            ->assertSet('items.0.alicuota_iva', 10.5)
            ->assertSet('items.0.lista_precio_id', $this->minorista->id);
    }

    public function test_actualizar_cantidad_recotiza_y_baja_al_tramo_mayor(): void
    {
        Livewire::test(Form::class)
            ->call('seleccionarArticulo', 0, $this->pollo->id)
            ->set('items.0.cantidad', 10)
            ->call('actualizarCantidad', 0)
            ->assertSet('items.0.precio', 2800.0)
            ->assertSet('items.0.subtotal', 28000.0);
    }

    public function test_articulo_duplicado_suma_cantidades_en_una_fila(): void
    {
        $component = Livewire::test(Form::class)
            ->call('seleccionarArticulo', 0, $this->pollo->id)  // fila 0 con cantidad 1
            ->set('items.0.cantidad', 3)
            ->call('actualizarCantidad', 0)
            ->call('addItem')                                    // fila 1 vacia
            ->set('items.1.cantidad', 4)
            ->call('seleccionarArticulo', 1, $this->pollo->id);   // mismo articulo en fila 1

        // Debe quedar una sola fila con 3+4 = 7 kilos a $2800 (tramo 5+)
        $items = $component->get('items');
        $this->assertCount(1, $items);
        $this->assertEquals($this->pollo->id, $items[0]['articulo_id']);
        $this->assertEquals(7.0, (float) $items[0]['cantidad']);
        $this->assertEquals(2800.0, (float) $items[0]['precio']);
        $this->assertEquals(19600.0, (float) $items[0]['subtotal']);
    }

    public function test_cliente_mayorista_recibe_precio_de_lista_mayorista(): void
    {
        $cliente = $this->clienteCon($this->mayorista);

        Livewire::test(Form::class)
            ->set('cliente_id', $cliente->id)
            ->call('seleccionarArticulo', 0, $this->pollo->id)
            ->set('items.0.cantidad', 2)
            ->call('actualizarCantidad', 0)
            ->assertSet('items.0.precio', 2400.0)
            ->assertSet('items.0.lista_precio_id', $this->mayorista->id);
    }

    public function test_cambiar_de_cliente_recotiza_items(): void
    {
        $cliMin = $this->clienteCon($this->minorista);
        $cliMay = $this->clienteCon($this->mayorista);

        $c = Livewire::test(Form::class)
            ->set('cliente_id', $cliMin->id)
            ->call('seleccionarArticulo', 0, $this->pollo->id)
            ->set('items.0.cantidad', 2)
            ->call('actualizarCantidad', 0);

        $c->assertSet('items.0.precio', 3000.0);

        // Cambiamos cliente: el motor re-cotiza
        $c->set('cliente_id', $cliMay->id)
          ->assertSet('items.0.precio', 2400.0)
          ->assertSet('items.0.lista_precio_id', $this->mayorista->id);
    }

    public function test_marcar_precio_manual_no_se_recotiza_al_cambiar_cantidad(): void
    {
        $c = Livewire::test(Form::class)
            ->call('seleccionarArticulo', 0, $this->pollo->id)
            ->call('marcarPrecioManual', 0, 9999.0)
            ->assertSet('items.0.precio', 9999.0)
            ->assertSet('items.0.precio_manual', true);

        // Cambiar cantidad mantiene el precio manual
        $c->set('items.0.cantidad', 10)
          ->call('actualizarCantidad', 0)
          ->assertSet('items.0.precio', 9999.0)
          ->assertSet('items.0.subtotal', 99990.0);
    }

    public function test_restaurar_precio_vuelve_al_motor(): void
    {
        Livewire::test(Form::class)
            ->call('seleccionarArticulo', 0, $this->pollo->id)
            ->set('items.0.cantidad', 10)
            ->call('actualizarCantidad', 0)
            ->call('marcarPrecioManual', 0, 1.0)
            ->assertSet('items.0.precio', 1.0)
            ->call('restaurarPrecio', 0)
            ->assertSet('items.0.precio', 2800.0)
            ->assertSet('items.0.precio_manual', false);
    }
}
