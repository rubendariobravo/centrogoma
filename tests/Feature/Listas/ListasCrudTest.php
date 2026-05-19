<?php

namespace Tests\Feature\Listas;

use App\Livewire\Listas\Form as ListaForm;
use App\Livewire\Listas\Index as ListaIndex;
use App\Livewire\Listas\Precios as ListaPrecios;
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

class ListasCrudTest extends TestCase
{
    use RefreshDatabase;

    private ListaPrecio $minorista;

    protected function setUp(): void
    {
        parent::setUp();
        $user = User::factory()->create();
        $this->actingAs($user);

        // La migracion 2026_05_08_000001 ya inserto MINORISTA default
        $this->minorista = ListaPrecio::firstOrCreate(
            ['codigo' => 'MINORISTA'],
            ['nombre' => 'Minorista', 'tipo' => 'minorista', 'es_default' => true, 'activo' => true, 'orden' => 1]
        );
    }

    public function test_index_lista_las_listas(): void
    {
        ListaPrecio::create([
            'codigo' => 'MAYORISTA', 'nombre' => 'Mayorista', 'tipo' => 'mayorista',
            'es_default' => false, 'activo' => true, 'orden' => 2,
        ]);

        Livewire::test(ListaIndex::class)
            ->assertSee('Minorista')
            ->assertSee('Mayorista');
    }

    public function test_form_crea_lista(): void
    {
        Livewire::test(ListaForm::class)
            ->set('codigo', 'ESPECIAL')
            ->set('nombre', 'Lista especial')
            ->set('tipo', 'especial')
            ->set('orden', 5)
            ->call('save');

        $this->assertDatabaseHas('listas_precios', [
            'codigo' => 'ESPECIAL',
            'nombre' => 'Lista especial',
            'tipo'   => 'especial',
        ]);
    }

    public function test_marcar_default_desmarca_las_otras(): void
    {
        $otra = ListaPrecio::create([
            'codigo' => 'MAYORISTA', 'nombre' => 'Mayorista', 'tipo' => 'mayorista',
            'es_default' => false, 'activo' => true, 'orden' => 2,
        ]);

        Livewire::test(ListaForm::class, ['lista' => $otra->id])
            ->set('es_default', true)
            ->call('save');

        $this->assertDatabaseHas('listas_precios', ['id' => $otra->id, 'es_default' => 1]);
        $this->assertDatabaseHas('listas_precios', ['id' => $this->minorista->id, 'es_default' => 0]);
    }

    public function test_no_permite_eliminar_lista_default(): void
    {
        Livewire::test(ListaIndex::class)
            ->call('confirmDelete', $this->minorista->id)
            ->call('delete');

        // La lista default sigue existiendo (no se borro)
        $this->assertDatabaseHas('listas_precios', ['id' => $this->minorista->id, 'es_default' => 1]);
    }

    public function test_no_permite_eliminar_lista_con_clientes(): void
    {
        $lista = ListaPrecio::create([
            'codigo' => 'MAYORISTA', 'nombre' => 'Mayorista', 'tipo' => 'mayorista',
            'es_default' => false, 'activo' => true, 'orden' => 2,
        ]);
        $tiva = TipoIva::firstOrCreate(['codigo_afip' => '05'], ['descripcion' => 'CF']);
        Cliente::create([
            'razon_social' => 'X', 'tipoiva_id' => $tiva->id,
            'lista_precio_id' => $lista->id, 'estado' => 'A',
        ]);

        Livewire::test(ListaIndex::class)
            ->call('confirmDelete', $lista->id)
            ->call('delete');

        $this->assertDatabaseHas('listas_precios', ['id' => $lista->id]);
    }

    public function test_precios_crear_y_eliminar_tramo(): void
    {
        $rubro = Rubro::create(['descripcion' => 'R', 'estado' => 'A']);
        $art = Articulo::create([
            'codigo' => 'A1', 'descripcion' => 'A1', 'rubro_id' => $rubro->id,
            'tipo_venta' => 'PESO', 'unidad_medida' => 'kg',
            'precio_costo' => 100, 'precio_unitario' => 200, 'precio_unitario_caja' => 0,
            'alicuota_iva' => 21, 'estado' => 'A',
        ]);

        // El form trabaja en FINAL (con IVA) por default y guarda NETO en DB.
        // 150 final / 1.21 = 123.97 neto.
        $c = Livewire::test(ListaPrecios::class, ['lista' => $this->minorista->id])
            ->call('abrirModalNuevo', $art->id)
            ->set('tramoDesde', '1')
            ->set('tramoHasta', '10')
            ->set('tramoPrecio', '150')
            ->call('guardarTramo');

        $this->assertDatabaseHas('articulo_precios', [
            'articulo_id'     => $art->id,
            'lista_precio_id' => $this->minorista->id,
            'cantidad_desde'  => 1,
            'cantidad_hasta'  => 10,
            'precio'          => 123.97,
        ]);

        $tramo = ArticuloPrecio::where('articulo_id', $art->id)->first();

        Livewire::test(ListaPrecios::class, ['lista' => $this->minorista->id])
            ->call('confirmDeleteTramo', $tramo->id)
            ->call('deleteTramo');

        $this->assertDatabaseMissing('articulo_precios', ['id' => $tramo->id]);
    }

    public function test_precios_valida_que_cantidad_hasta_sea_mayor_que_desde(): void
    {
        $rubro = Rubro::create(['descripcion' => 'R', 'estado' => 'A']);
        $art = Articulo::create([
            'codigo' => 'A2', 'descripcion' => 'A2', 'rubro_id' => $rubro->id,
            'tipo_venta' => 'PESO', 'unidad_medida' => 'kg',
            'precio_costo' => 100, 'precio_unitario' => 200, 'precio_unitario_caja' => 0,
            'alicuota_iva' => 21, 'estado' => 'A',
        ]);

        Livewire::test(ListaPrecios::class, ['lista' => $this->minorista->id])
            ->call('abrirModalNuevo', $art->id)
            ->set('tramoDesde', '10')
            ->set('tramoHasta', '5')
            ->set('tramoPrecio', '100')
            ->call('guardarTramo')
            ->assertHasErrors(['tramoHasta']);
    }

    public function test_precios_toggle_ver_netos_guarda_neto_sin_conversion(): void
    {
        $rubro = Rubro::create(['descripcion' => 'R', 'estado' => 'A']);
        $art = Articulo::create([
            'codigo' => 'A3', 'descripcion' => 'A3', 'rubro_id' => $rubro->id,
            'tipo_venta' => 'PESO', 'unidad_medida' => 'kg',
            'precio_costo' => 100, 'precio_unitario' => 200, 'precio_unitario_caja' => 0,
            'alicuota_iva' => 21, 'estado' => 'A',
        ]);

        // Con toggle verNetos = true, el usuario ingresa neto directamente.
        Livewire::test(ListaPrecios::class, ['lista' => $this->minorista->id])
            ->call('toggleVerNetos')
            ->call('abrirModalNuevo', $art->id)
            ->set('tramoDesde', '1')
            ->set('tramoHasta', '10')
            ->set('tramoPrecio', '123.97')
            ->call('guardarTramo');

        $this->assertDatabaseHas('articulo_precios', [
            'articulo_id' => $art->id,
            'precio'      => 123.97,
        ]);
    }

    public function test_precios_editar_tramo_existente_carga_final_y_guarda_neto(): void
    {
        $rubro = Rubro::create(['descripcion' => 'R', 'estado' => 'A']);
        $art = Articulo::create([
            'codigo' => 'A4', 'descripcion' => 'A4', 'rubro_id' => $rubro->id,
            'tipo_venta' => 'PESO', 'unidad_medida' => 'kg',
            'precio_costo' => 100, 'precio_unitario' => 200, 'precio_unitario_caja' => 0,
            'alicuota_iva' => 10.5, 'estado' => 'A',
        ]);
        $tramo = ArticuloPrecio::create([
            'articulo_id' => $art->id, 'lista_precio_id' => $this->minorista->id,
            'cantidad_desde' => 1, 'cantidad_hasta' => null,
            'precio' => 100, 'activo' => true, 'orden' => 0,
        ]);

        $c = Livewire::test(ListaPrecios::class, ['lista' => $this->minorista->id])
            ->call('abrirModalEditar', $tramo->id);

        // Al abrir, el input debe traer el FINAL: 100 * 1.105 = 110.50
        $c->assertSet('tramoPrecio', '110.5');

        // Si guarda sin tocar nada, en DB debe quedar 100 (round-trip).
        $c->call('guardarTramo');
        $this->assertDatabaseHas('articulo_precios', [
            'id' => $tramo->id, 'precio' => 100,
        ]);
    }
}
