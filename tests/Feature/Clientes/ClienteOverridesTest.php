<?php

namespace Tests\Feature\Clientes;

use App\Livewire\Clientes\Form as ClienteForm;
use App\Models\Articulo;
use App\Models\Cliente;
use App\Models\ClientePrecio;
use App\Models\ListaPrecio;
use App\Models\Rubro;
use App\Models\TipoIva;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ClienteOverridesTest extends TestCase
{
    use RefreshDatabase;

    private TipoIva $tiva;
    private ListaPrecio $minorista;
    private ListaPrecio $mayorista;
    private Rubro $rubro;
    private Articulo $art;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());

        $this->tiva = TipoIva::firstOrCreate(['codigo_afip' => '05'], ['descripcion' => 'CF']);
        $this->minorista = ListaPrecio::firstOrCreate(
            ['codigo' => 'MINORISTA'],
            ['nombre' => 'Minorista', 'tipo' => 'minorista', 'es_default' => true, 'activo' => true, 'orden' => 1]
        );
        $this->mayorista = ListaPrecio::firstOrCreate(
            ['codigo' => 'MAYORISTA'],
            ['nombre' => 'Mayorista', 'tipo' => 'mayorista', 'es_default' => false, 'activo' => true, 'orden' => 2]
        );
        $this->rubro = Rubro::create(['descripcion' => 'Pollos', 'estado' => 'A']);
        $this->art = Articulo::create([
            'codigo' => 'X1', 'descripcion' => 'X1', 'rubro_id' => $this->rubro->id,
            'tipo_venta' => 'PESO', 'unidad_medida' => 'kg',
            'precio_costo' => 100, 'precio_unitario' => 200, 'precio_unitario_caja' => 0,
            'alicuota_iva' => 21, 'estado' => 'A',
        ]);
    }

    public function test_crear_cliente_con_override_precio_fijo(): void
    {
        // El form trabaja con precios FINALES (con IVA). El DB guarda NETO.
        // 1500 final con IVA 21% = 1239.67 neto.
        Livewire::test(ClienteForm::class)
            ->set('razon_social', 'Cliente A')
            ->set('tipoiva_id', $this->tiva->id)
            ->set('overrides', [[
                'id' => null, 'tipo' => 'precio_articulo',
                'articulo_id' => $this->art->id, 'rubro_id' => null, 'lista_precio_id' => null,
                'precio_especial' => 1500, 'bonificacion' => 0, 'activo' => true,
            ]])
            ->call('save');

        $cliente = Cliente::where('razon_social', 'Cliente A')->first();
        $this->assertNotNull($cliente);
        $this->assertDatabaseHas('cliente_precios', [
            'cliente_id' => $cliente->id,
            'articulo_id' => $this->art->id,
            'rubro_id' => null,
            'precio_especial' => 1239.67,
            'activo' => 1,
        ]);
    }

    public function test_override_lista_por_rubro(): void
    {
        Livewire::test(ClienteForm::class)
            ->set('razon_social', 'B')
            ->set('tipoiva_id', $this->tiva->id)
            ->set('overrides', [[
                'id' => null, 'tipo' => 'lista_rubro',
                'articulo_id' => null, 'rubro_id' => $this->rubro->id, 'lista_precio_id' => $this->mayorista->id,
                'precio_especial' => null, 'bonificacion' => 0, 'activo' => true,
            ]])
            ->call('save');

        $cli = Cliente::where('razon_social', 'B')->first();
        $this->assertDatabaseHas('cliente_precios', [
            'cliente_id'      => $cli->id,
            'rubro_id'        => $this->rubro->id,
            'lista_precio_id' => $this->mayorista->id,
            'articulo_id'     => null,
            'precio_especial' => null,
        ]);
    }

    public function test_overrides_incompletos_se_descartan(): void
    {
        Livewire::test(ClienteForm::class)
            ->set('razon_social', 'C')
            ->set('tipoiva_id', $this->tiva->id)
            ->set('overrides', [
                // 1) Precio fijo SIN articulo_id => descartar
                ['id' => null, 'tipo' => 'precio_articulo', 'articulo_id' => null, 'rubro_id' => null, 'lista_precio_id' => null, 'precio_especial' => 100, 'bonificacion' => 0, 'activo' => true],
                // 2) Bonificacion sin valor => descartar
                ['id' => null, 'tipo' => 'bonif_articulo', 'articulo_id' => $this->art->id, 'rubro_id' => null, 'lista_precio_id' => null, 'precio_especial' => null, 'bonificacion' => 0, 'activo' => true],
                // 3) Lista por rubro VALIDO
                ['id' => null, 'tipo' => 'lista_rubro', 'articulo_id' => null, 'rubro_id' => $this->rubro->id, 'lista_precio_id' => $this->mayorista->id, 'precio_especial' => null, 'bonificacion' => 0, 'activo' => true],
            ])
            ->call('save');

        $cli = Cliente::where('razon_social', 'C')->first();
        $this->assertEquals(1, ClientePrecio::where('cliente_id', $cli->id)->count(), 'Solo debe quedar el override valido');
    }

    public function test_editar_cliente_actualiza_overrides_existentes(): void
    {
        // Cliente con override previo (neto 1000 en DB).
        $cli = Cliente::create(['razon_social' => 'D', 'tipoiva_id' => $this->tiva->id, 'estado' => 'A']);
        $cp = ClientePrecio::create([
            'cliente_id' => $cli->id, 'articulo_id' => $this->art->id,
            'precio_especial' => 1000, 'activo' => true,
        ]);

        // El form carga el override como FINAL (1000 * 1.21 = 1210).
        // El usuario ingresa un nuevo final = 1234, que se guarda como neto.
        // 1234 / 1.21 = 1019.83.
        Livewire::test(ClienteForm::class, ['cliente' => $cli->id])
            ->set('overrides.0.precio_especial', 1234)
            ->call('save');

        $this->assertDatabaseHas('cliente_precios', ['id' => $cp->id, 'precio_especial' => 1019.83]);
        // Sigue habiendo solo 1 override (no se duplico)
        $this->assertEquals(1, ClientePrecio::where('cliente_id', $cli->id)->count());
    }

    public function test_form_carga_override_existente_como_final_con_iva(): void
    {
        // DB tiene neto 1000; el form lo muestra como final = 1210.
        $cli = Cliente::create(['razon_social' => 'F', 'tipoiva_id' => $this->tiva->id, 'estado' => 'A']);
        ClientePrecio::create([
            'cliente_id' => $cli->id, 'articulo_id' => $this->art->id,
            'precio_especial' => 1000, 'activo' => true,
        ]);

        $c = Livewire::test(ClienteForm::class, ['cliente' => $cli->id]);
        $c->assertSet('overrides.0.precio_especial', 1210.0);
    }

    public function test_eliminar_override_lo_borra_al_guardar(): void
    {
        $cli = Cliente::create(['razon_social' => 'E', 'tipoiva_id' => $this->tiva->id, 'estado' => 'A']);
        ClientePrecio::create([
            'cliente_id' => $cli->id, 'articulo_id' => $this->art->id,
            'precio_especial' => 999, 'activo' => true,
        ]);

        Livewire::test(ClienteForm::class, ['cliente' => $cli->id])
            ->call('removeOverride', 0)
            ->call('save');

        $this->assertEquals(0, ClientePrecio::where('cliente_id', $cli->id)->count());
    }
}
