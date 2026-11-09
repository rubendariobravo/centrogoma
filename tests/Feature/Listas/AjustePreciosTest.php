<?php

namespace Tests\Feature\Listas;

use App\Livewire\Articulos\Form as ArticuloForm;
use App\Livewire\Listas\Precios;
use App\Models\AjustePrecio;
use App\Models\Articulo;
use App\Models\ArticuloPrecio;
use App\Models\ListaPrecio;
use App\Models\Marca;
use App\Models\Rubro;
use App\Models\User;
use App\Services\Pricing\AjustePrecios;
use App\Services\Pricing\CotizacionRequest;
use App\Services\Pricing\PriceEngine;
use App\Support\Pricing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Gestión masiva de precios: redondeo, filtros, vista previa, historial reversible, ajuste por costo
 * y margen, precio base sincronizado con la lista por defecto y grilla que guarda solo lo cambiado.
 */
class AjustePreciosTest extends TestCase
{
    use RefreshDatabase;

    private ListaPrecio $minorista;
    private ListaPrecio $mayorista;
    private Rubro $mangueras;
    private Rubro $guantes;
    private Marca $fana;
    private Articulo $manguera;   // $1.500 final
    private Articulo $acople;     // $1.400 final
    private Articulo $guante;     // $9,60 final (de los "en dólares")

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());

        $this->minorista = ListaPrecio::firstOrCreate(['codigo' => 'MINORISTA'],
            ['nombre' => 'Minorista', 'tipo' => 'minorista', 'es_default' => true, 'activo' => true, 'orden' => 1]);
        $this->mayorista = ListaPrecio::create(['codigo' => 'MAYORISTA', 'nombre' => 'Mayorista', 'tipo' => 'mayorista',
            'es_default' => false, 'activo' => true, 'orden' => 2]);

        $this->mangueras = Rubro::create(['descripcion' => 'Mangueras', 'estado' => 'A']);
        $this->guantes   = Rubro::create(['descripcion' => 'Guantes', 'estado' => 'A']);
        $this->fana      = Marca::create(['descripcion' => 'Fana', 'estado' => 'A']);

        $this->manguera = $this->articulo('M1', 'Manguera 1/2', 1500, $this->mangueras, costo: 700, margen: 80, marca: $this->fana);
        $this->acople   = $this->articulo('A1', 'Acople rapido', 1400, $this->mangueras);
        $this->guante   = $this->articulo('G1', 'Guante latex', 9.60, $this->guantes);
    }

    private function articulo(string $codigo, string $desc, float $final, Rubro $rubro, float $costo = 0, float $margen = 0, ?Marca $marca = null): Articulo
    {
        $neto = Pricing::neto($final, 21);
        $art = Articulo::create([
            'codigo' => $codigo, 'descripcion' => $desc, 'rubro_id' => $rubro->id, 'marca_id' => $marca?->id,
            'tipo_venta' => 'CAJA', 'unidad_medida' => 'unidad', 'precio_costo' => $costo, 'margen' => $margen,
            'precio_unitario' => $neto, 'precio_unitario_caja' => 0, 'alicuota_iva' => 21,
            'estado' => 'A', 'modifica_stock' => false, 'stock' => 0,
        ]);
        ArticuloPrecio::create(['articulo_id' => $art->id, 'lista_precio_id' => $this->minorista->id,
            'cantidad_desde' => 1, 'cantidad_hasta' => null, 'precio' => $neto, 'activo' => true, 'orden' => 0]);

        return $art;
    }

    private function final(Articulo $a): float
    {
        return Pricing::final((float) $a->precios()->where('lista_precio_id', $this->minorista->id)->value('precio'), 21);
    }

    private function precios()
    {
        return Livewire::test(Precios::class, ['lista' => $this->minorista->id]);
    }

    public function test_redondeo_del_precio_final_sin_pasarse_en_precios_chicos(): void
    {
        $this->assertSame(1600.0, AjustePrecios::redondear(1627.5, 100));
        $this->assertSame(1519.0, AjustePrecios::redondear(1519.0, 1));
        $this->assertSame(950.0, AjustePrecios::redondear(950.0, 100));   // < $1.000: baja a $50
        $this->assertSame(45.0, AjustePrecios::redondear(45.4, 100));     // < $100: baja a $1
        $this->assertSame(10.0, AjustePrecios::redondear(10.416, 100));   // $10 a $100: a $1
        $this->assertSame(9.62, AjustePrecios::redondear(9.6153, 100));   // < $10: centavos, nunca $0
    }

    public function test_ajuste_con_vista_previa_redondeo_e_historial(): void
    {
        $this->precios()
            ->set('ajustePct', '8.5')
            ->set('ajusteRedondeo', '100')
            ->call('aplicarAjuste')
            ->assertHasErrors('ajustePct')                       // sin vista previa no se aplica
            ->call('previsualizarAjuste')
            ->assertSet('ajustePreview.cantidad', 3)
            ->assertSet('ajustePreview.ejemplos.0.final_despues', 1600.0)   // 1.500 → 1.627,50 → 1.600
            ->call('aplicarAjuste')
            ->assertHasNoErrors();

        $this->assertSame(1600.0, $this->final($this->manguera));   // 1.627,50 → 1.600
        $this->assertSame(1500.0, $this->final($this->acople));
        $this->assertSame(10.0, $this->final($this->guante));      // $10,42 → $10: no pasa a $0 ni a $100

        $ajuste = AjustePrecio::sole();
        $this->assertSame(3, $ajuste->cantidad);
        $this->assertStringContainsString('+8,5%', $ajuste->descripcion);
        $this->assertStringContainsString('redondeo a $100', $ajuste->descripcion);
    }

    public function test_el_precio_base_queda_sincronizado_y_otra_lista_no_cobra_el_viejo(): void
    {
        $this->precios()->set('ajustePct', '10')->set('ajusteRedondeo', '0')
            ->call('previsualizarAjuste')->call('aplicarAjuste');

        $nuevoNeto = (float) $this->manguera->precios()->value('precio');
        $this->assertEqualsWithDelta($nuevoNeto, (float) $this->manguera->fresh()->precio_unitario, 0.0001);

        // La lista Mayorista no tiene tramo: el motor usa el precio base, que ya es el nuevo.
        $cot = app(PriceEngine::class)->cotizar(new CotizacionRequest(
            articuloId: $this->manguera->id, cantidad: 1, forzarListaId: $this->mayorista->id,
        ));
        $this->assertEqualsWithDelta($nuevoNeto, $cot->precioUnitario, 0.0001);
    }

    public function test_deshacer_vuelve_al_precio_anterior_salvo_lo_cambiado_despues(): void
    {
        $this->precios()->set('ajustePct', '20')->call('previsualizarAjuste')->call('aplicarAjuste');
        $ajuste = AjustePrecio::sole();

        // Después del ajuste alguien cambia a mano el acople: deshacer no lo pisa.
        $this->acople->precios()->update(['precio' => Pricing::neto(2000, 21)]);

        $this->precios()->call('revertirAjuste', $ajuste->id)
            ->assertSee('2 precios volvieron')
            ->assertSee('1 se dejaron como estaban');

        $this->assertSame(1500.0, $this->final($this->manguera));
        $this->assertSame(9.6, $this->final($this->guante));
        $this->assertSame(2000.0, $this->final($this->acople));
        $this->assertNotNull($ajuste->fresh()->revertido_at);
        $this->assertEqualsWithDelta(Pricing::neto(1500, 21), (float) $this->manguera->fresh()->precio_unitario, 0.0001);
    }

    public function test_bajar_100_por_ciento_o_mas_no_se_permite(): void
    {
        $this->precios()->set('ajusteTipo', 'bajar')->set('ajustePct', '100')
            ->call('previsualizarAjuste')
            ->assertHasErrors('ajustePct');
    }

    public function test_filtros_por_rubro_marca_busqueda_y_seleccion(): void
    {
        $previa = fn (array $sets) => tap($this->precios(), function ($lw) use ($sets) {
            foreach ($sets as $k => $v) {
                $lw->set($k, $v);
            }
            $lw->set('ajustePct', '10')->call('previsualizarAjuste');
        })->get('ajustePreview.cantidad');

        $this->assertSame(2, $previa(['ajusteRubroFiltro' => (string) $this->mangueras->id]));
        $this->assertSame(1, $previa(['ajusteMarcaFiltro' => (string) $this->fana->id]));
        $this->assertSame(1, $previa(['search' => 'acople', 'ajusteSoloBusqueda' => true]));
        $this->assertSame(2, $previa(['seleccion' => [(string) $this->acople->id, (string) $this->guante->id], 'ajusteSoloSeleccion' => true]));
    }

    public function test_ajuste_por_costo_recalcula_con_el_margen_y_se_puede_deshacer(): void
    {
        // Manguera: costo 700 +10% = 770 → 770 × 1,21 × 1,80 = 1.677,06 → redondeo $100 = 1.700.
        $this->precios()
            ->set('ajusteModo', 'costo')->set('ajustePct', '10')->set('ajusteRedondeo', '100')
            ->call('previsualizarAjuste')
            ->assertSet('ajustePreview.cantidad', 1)
            ->assertSet('ajustePreview.omitidos', 2)                 // sin costo: quedan afuera
            ->call('aplicarAjuste');

        $this->assertSame(1700.0, $this->final($this->manguera));
        $this->assertEquals(770.0, (float) $this->manguera->fresh()->precio_costo);
        $this->assertSame(1400.0, $this->final($this->acople));

        $this->precios()->call('revertirAjuste', AjustePrecio::sole()->id);
        $this->assertSame(1500.0, $this->final($this->manguera));
        $this->assertEquals(700.0, (float) $this->manguera->fresh()->precio_costo);
    }

    public function test_ajuste_por_costo_solo_en_la_lista_por_defecto(): void
    {
        Livewire::test(Precios::class, ['lista' => $this->mayorista->id])
            ->set('ajusteModo', 'costo')->set('ajustePct', '10')
            ->call('previsualizarAjuste')
            ->assertHasErrors('ajusteModo');
    }

    public function test_la_grilla_guarda_solo_lo_que_cambio(): void
    {
        $tramoAcople = $this->acople->precios()->value('id');

        $lw = $this->precios()->call('activarModoGrilla');
        $this->assertCount(3, $lw->get('grillaOriginal'));

        $lw->set("grillaPrecios.{$tramoAcople}", '1450')
           ->assertSee('Guardar grilla (1)')
           ->call('guardarGrilla')
           ->assertSee('Grilla guardada: 1 precios actualizados.');

        $this->assertSame(1450.0, $this->final($this->acople));
        $this->assertSame(1500.0, $this->final($this->manguera));
        $this->assertEqualsWithDelta(Pricing::neto(1450, 21), (float) $this->acople->fresh()->precio_unitario, 0.0001);
    }

    public function test_guardar_el_articulo_conserva_el_id_de_sus_tramos(): void
    {
        $id = $this->manguera->precios()->value('id');

        Livewire::test(ArticuloForm::class, ['articulo' => $this->manguera->id])
            ->set('tiers.0.precio', 1800)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame([$id], $this->manguera->precios()->pluck('id')->all());
        $this->assertSame(1800.0, $this->final($this->manguera));
        $this->assertEqualsWithDelta(Pricing::neto(1800, 21), (float) $this->manguera->fresh()->precio_unitario, 0.0001);
    }

    public function test_precio_sugerido_y_margen_bajo(): void
    {
        // 700 × 1,21 × 1,80 = 1.524,60; con precio 1.500 el margen real es 77,1% < 80%.
        Livewire::test(ArticuloForm::class, ['articulo' => $this->manguera->id])
            ->assertSee('1.524,60')
            ->assertSee('77,1%')
            ->call('usarPrecioSugerido')
            ->assertSet('tiers.0.precio', 1500.0);   // redondeado a $100

        $bajos = AjustePrecios::conMargenBajo([$this->manguera->id, $this->acople->id]);
        $this->assertCount(1, $bajos);
        $this->assertSame(77.1, $bajos->first()['margen_real']);
    }

    public function test_lista_imprimible_por_rubro_con_filtros_y_tramos(): void
    {
        ArticuloPrecio::create(['articulo_id' => $this->manguera->id, 'lista_precio_id' => $this->minorista->id,
            'cantidad_desde' => 10, 'cantidad_hasta' => null, 'precio' => Pricing::neto(1350, 21), 'activo' => true, 'orden' => 1]);

        $this->get(route('listas-precios.imprimir', ['lista' => $this->minorista->id]))
            ->assertOk()
            ->assertSeeInOrder(['Guantes', 'Guante latex', '$9,60', 'Mangueras', 'Acople rapido', '$1.400,00', 'Manguera 1/2', 'desde 10: $1.350,00', '$1.500,00'])
            ->assertSee('precios finales, con IVA');

        $this->get(route('listas-precios.imprimir', ['lista' => $this->minorista->id, 'rubro' => $this->guantes->id, 'verNetos' => 1]))
            ->assertOk()->assertSee('Guante latex')->assertDontSee('Acople rapido')
            ->assertSee('$7,93');   // 9,60 / 1,21

        // Mayorista sin tramos: imprime el precio base, que es el que cobra el motor.
        $this->get(route('listas-precios.imprimir', ['lista' => $this->mayorista->id, 'ids' => $this->acople->id]))
            ->assertOk()->assertSee('$1.400,00')->assertDontSee('Guante latex')->assertSee('1 artículos seleccionados');
    }

    public function test_agregar_texto_a_la_descripcion_de_los_seleccionados(): void
    {
        $this->precios()
            ->set('seleccion', [(string) $this->manguera->id, (string) $this->acople->id])
            ->set('textoDescripcion', '  FANA ')
            ->call('agregarTextoDescripcion')
            ->assertSee('Descripción actualizada en 2 artículos.');

        // Como el Delphi: después de la primera palabra.
        $this->assertSame('Manguera FANA 1/2', $this->manguera->fresh()->descripcion);
        $this->assertSame('Acople FANA rapido', $this->acople->fresh()->descripcion);
        $this->assertSame('Guante latex', $this->guante->fresh()->descripcion);

        $this->precios()->set('seleccion', [(string) $this->guante->id])
            ->set('textoDescripcion', 'x100')->set('textoPosicion', 'final')->call('agregarTextoDescripcion');
        $this->assertSame('Guante latex x100', $this->guante->fresh()->descripcion);

        $this->precios()->set('seleccion', [(string) $this->guante->id])->set('textoDescripcion', ' ')
            ->call('agregarTextoDescripcion')->assertHasErrors('textoDescripcion');
    }
}
