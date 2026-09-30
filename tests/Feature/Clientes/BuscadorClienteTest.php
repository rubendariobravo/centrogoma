<?php

namespace Tests\Feature\Clientes;

use App\Livewire\Cheques\Index as ChequesIndex;
use App\Livewire\Presupuestos\Form as PresupuestoForm;
use App\Livewire\Recibos\Form as ReciboForm;
use App\Livewire\Recibos\Index as RecibosIndex;
use App\Livewire\Ventas\Form as VentaForm;
use App\Models\Cliente;
use App\Models\ListaPrecio;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Buscador de clientes server-side (trait BuscaCliente + <x-buscador-cliente>), que reemplaza
 * los <select> con todos los clientes.
 */
class BuscadorClienteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());

        Cliente::create(['razon_social' => 'POSCO ARGENTINA S.A.U.', 'cuit' => '30-71234567-8', 'estado' => 'A']);
        Cliente::create(['razon_social' => 'TRANSPORTE EL INCA S.R.L.', 'nombre_fantasia' => 'El Inca', 'cuit' => '30-60000000-1', 'estado' => 'A']);
        Cliente::create(['razon_social' => 'PEREZ JUAN', 'dni' => '28123456', 'estado' => 'A']);
        Cliente::create(['razon_social' => 'POSCO VIEJO (BAJA)', 'estado' => 'I']);
    }

    private function buscar(string $texto): array
    {
        return Livewire::test(VentaForm::class)
            ->set('clienteBuscar', $texto)
            ->instance()->clientesEncontrados()->pluck('razon_social')->all();
    }

    public function test_menos_de_dos_caracteres_no_busca(): void
    {
        $this->assertSame([], $this->buscar('P'));
    }

    public function test_busca_por_palabras_en_razon_social_y_fantasia(): void
    {
        $this->assertSame(['POSCO ARGENTINA S.A.U.'], $this->buscar('posco'));
        $this->assertSame(['TRANSPORTE EL INCA S.R.L.'], $this->buscar('inca transporte'));
        $this->assertSame(['TRANSPORTE EL INCA S.R.L.'], $this->buscar('el inca'));
    }

    public function test_busca_por_cuit_sin_guiones_y_por_dni(): void
    {
        $this->assertSame(['POSCO ARGENTINA S.A.U.'], $this->buscar('3071234'));
        $this->assertSame(['POSCO ARGENTINA S.A.U.'], $this->buscar('30-7123'));
        $this->assertSame(['PEREZ JUAN'], $this->buscar('28123'));
    }

    public function test_clientes_inactivos_no_aparecen(): void
    {
        $this->assertNotContains('POSCO VIEJO (BAJA)', $this->buscar('posco'));
    }

    public function test_limita_la_cantidad_de_resultados(): void
    {
        foreach (range(1, 20) as $i) {
            Cliente::create(['razon_social' => "GOMERIA {$i}", 'estado' => 'A']);
        }
        $this->assertCount(15, $this->buscar('gomeria'));
    }

    public function test_seleccionar_en_ventas_dispara_el_cambio_de_cliente(): void
    {
        $mayorista = ListaPrecio::create(['codigo' => 'MAY', 'nombre' => 'Mayorista', 'tipo' => 'mayorista', 'es_default' => false, 'activo' => true, 'orden' => 2]);
        $cliente   = Cliente::create(['razon_social' => 'MAYORISTA SA', 'estado' => 'A', 'lista_precio_id' => $mayorista->id, 'porcentaje_iibb' => 3]);

        Livewire::test(VentaForm::class)
            ->set('clienteBuscar', 'mayorista')
            ->call('seleccionarCliente', $cliente->id)
            ->assertSet('cliente_id', $cliente->id)
            ->assertSet('clienteBuscar', '')
            ->assertSet('listaIdForzada', $mayorista->id)
            ->assertSet('clienteData.razon_social', 'MAYORISTA SA')
            ->assertDispatched('items-actualizados')
            ->call('seleccionarCliente', null)
            ->assertSet('cliente_id', null)
            ->assertSet('clienteData.razon_social', '');
    }

    public function test_seleccionar_en_recibo_carga_las_facturas_pendientes(): void
    {
        $cliente = Cliente::where('razon_social', 'PEREZ JUAN')->first();
        Venta::create(['cliente_id' => $cliente->id, 'nro_venta' => 1, 'fecha' => now()->toDateString(),
            'total' => 500, 'saldo' => 500, 'estado' => 'P']);

        Livewire::test(ReciboForm::class)
            ->call('seleccionarCliente', $cliente->id)
            ->assertSet('cliente_id', $cliente->id)
            ->assertCount('ventasPendientes', 1)
            ->assertSee('PEREZ JUAN');
    }

    public function test_filtro_de_recibos_usa_filter_cliente(): void
    {
        $cliente = Cliente::first();

        Livewire::test(RecibosIndex::class)
            ->call('seleccionarCliente', $cliente->id)
            ->assertSet('filterCliente', $cliente->id)
            ->call('seleccionarCliente', null)
            ->assertSet('filterCliente', null);
    }

    public function test_las_pantallas_ya_no_cargan_todos_los_clientes(): void
    {
        foreach ([VentaForm::class, PresupuestoForm::class, ReciboForm::class, RecibosIndex::class, ChequesIndex::class] as $componente) {
            Livewire::test($componente)->assertOk()->assertViewMissing('clientes');
        }
    }
}
