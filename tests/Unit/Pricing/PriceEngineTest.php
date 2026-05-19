<?php

namespace Tests\Unit\Pricing;

use App\Models\Articulo;
use App\Models\ArticuloPrecio;
use App\Models\Cliente;
use App\Models\ClientePrecio;
use App\Models\ListaPrecio;
use App\Models\Rubro;
use App\Models\TipoIva;
use App\Services\Pricing\CotizacionRequest;
use App\Services\Pricing\PriceEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use RuntimeException;
use Tests\TestCase;

class PriceEngineTest extends TestCase
{
    use RefreshDatabase;

    private PriceEngine $engine;

    private ListaPrecio $minorista;
    private ListaPrecio $mayorista;
    private Rubro $rubroPollos;
    private Rubro $rubroCerdo;
    private Articulo $pollo;
    private Articulo $cerdo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->engine = app(PriceEngine::class);

        // Usamos firstOrCreate porque la migracion 2026_05_08_000001 ya inserta MINORISTA default.
        // Eso permite que los tests sean idempotentes sin importar si la migracion la cargo o no.
        $this->minorista = ListaPrecio::firstOrCreate(
            ['codigo' => 'MINORISTA'],
            [
                'nombre' => 'Minorista', 'tipo' => 'minorista',
                'es_default' => true, 'activo' => true, 'orden' => 1,
            ]
        );
        $this->mayorista = ListaPrecio::firstOrCreate(
            ['codigo' => 'MAYORISTA'],
            [
                'nombre' => 'Mayorista', 'tipo' => 'mayorista',
                'es_default' => false, 'activo' => true, 'orden' => 2,
                'recargo_no_efectivo' => 5.0,
            ]
        );

        $this->rubroPollos = Rubro::create(['descripcion' => 'Pollos', 'estado' => 'A']);
        $this->rubroCerdo  = Rubro::create(['descripcion' => 'Cerdo', 'estado' => 'A']);

        $this->pollo = Articulo::create([
            'codigo' => 'POL001', 'descripcion' => 'Pollo entero',
            'rubro_id' => $this->rubroPollos->id,
            'tipo_venta' => 'PESO', 'unidad_medida' => 'kg',
            'precio_costo' => 2000, 'precio_unitario' => 3000, 'precio_unitario_caja' => 0,
            'alicuota_iva' => 10.5, 'estado' => 'A',
        ]);

        $this->cerdo = Articulo::create([
            'codigo' => 'CER001', 'descripcion' => 'Bondiola',
            'rubro_id' => $this->rubroCerdo->id,
            'tipo_venta' => 'PESO', 'unidad_medida' => 'kg',
            'precio_costo' => 5000, 'precio_unitario' => 7500, 'precio_unitario_caja' => 0,
            'alicuota_iva' => 10.5, 'estado' => 'A',
        ]);

        // Tramos del pollo en MINORISTA: 1-4kg=$3000, 5-14kg=$2800, 15+=$2600
        $this->crearTramos($this->pollo, $this->minorista, [
            [1, 4, 3000], [5, 14, 2800], [15, null, 2600],
        ]);
        // Tramos del pollo en MAYORISTA: 1-4=$2700, 5-14=$2500, 15+=$2300
        $this->crearTramos($this->pollo, $this->mayorista, [
            [1, 4, 2700], [5, 14, 2500], [15, null, 2300],
        ]);
        // Tramos del cerdo en MINORISTA: 1+=$7500
        $this->crearTramos($this->cerdo, $this->minorista, [[1, null, 7500]]);
    }

    private function crearTramos(Articulo $articulo, ListaPrecio $lista, array $tramos): void
    {
        foreach ($tramos as $i => [$desde, $hasta, $precio]) {
            ArticuloPrecio::create([
                'articulo_id'     => $articulo->id,
                'lista_precio_id' => $lista->id,
                'cantidad_desde'  => $desde,
                'cantidad_hasta'  => $hasta,
                'precio'          => $precio,
                'activo'          => true,
                'orden'           => $i,
            ]);
        }
    }

    private function crearCliente(?ListaPrecio $lista = null, float $bonif = 0): Cliente
    {
        $tiva = TipoIva::firstOrCreate(['codigo_afip' => '05'], ['descripcion' => 'CF']);
        return Cliente::create([
            'razon_social'    => 'Cliente Test',
            'tipoiva_id'      => $tiva->id,
            'lista_precio_id' => $lista?->id,
            'bonificacion'    => $bonif,
            'estado'          => 'A',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Tests de pipeline
    // ─────────────────────────────────────────────────────────────────────

    public function test_sin_cliente_usa_lista_default(): void
    {
        $r = $this->engine->cotizar(new CotizacionRequest(
            articuloId: $this->pollo->id,
            cantidad: 2,
        ));

        $this->assertSame($this->minorista->id, $r->listaPrecioId);
        $this->assertSame('tramo_lista', $r->reglaAplicada);
        $this->assertSame(3000.0, $r->precioUnitario);
        $this->assertSame(6000.0, $r->subtotal);
    }

    public function test_cliente_sin_lista_asignada_usa_default(): void
    {
        $cliente = $this->crearCliente(null);

        $r = $this->engine->cotizar(new CotizacionRequest(
            articuloId: $this->pollo->id,
            cantidad: 10,
            clienteId: $cliente->id,
        ));

        $this->assertSame($this->minorista->id, $r->listaPrecioId);
        $this->assertSame(2800.0, $r->precioUnitario);
    }

    public function test_cliente_con_lista_mayorista_usa_esa_lista(): void
    {
        $cliente = $this->crearCliente($this->mayorista);

        $r = $this->engine->cotizar(new CotizacionRequest(
            articuloId: $this->pollo->id,
            cantidad: 10,
            clienteId: $cliente->id,
        ));

        $this->assertSame($this->mayorista->id, $r->listaPrecioId);
        $this->assertSame(2500.0, $r->precioUnitario);
    }

    public function test_tramos_por_cantidad_caso_bajo_medio_alto(): void
    {
        $cliente = $this->crearCliente($this->minorista);

        $bajo = $this->engine->cotizar(new CotizacionRequest($this->pollo->id, 2, $cliente->id));
        $medio = $this->engine->cotizar(new CotizacionRequest($this->pollo->id, 8, $cliente->id));
        $alto = $this->engine->cotizar(new CotizacionRequest($this->pollo->id, 25, $cliente->id));

        $this->assertSame(3000.0, $bajo->precioUnitario);
        $this->assertSame(2800.0, $medio->precioUnitario);
        $this->assertSame(2600.0, $alto->precioUnitario);
    }

    public function test_override_por_rubro_redirige_a_otra_lista(): void
    {
        // Cliente con lista minorista por default, pero override "para rubro pollos use mayorista"
        $cliente = $this->crearCliente($this->minorista);
        ClientePrecio::create([
            'cliente_id'      => $cliente->id,
            'rubro_id'        => $this->rubroPollos->id,
            'lista_precio_id' => $this->mayorista->id,
            'activo'          => true,
        ]);

        $rPollo = $this->engine->cotizar(new CotizacionRequest($this->pollo->id, 10, $cliente->id));
        $rCerdo = $this->engine->cotizar(new CotizacionRequest($this->cerdo->id, 1, $cliente->id));

        $this->assertSame($this->mayorista->id, $rPollo->listaPrecioId, 'pollo debe usar mayorista por override');
        $this->assertSame(2500.0, $rPollo->precioUnitario);

        $this->assertSame($this->minorista->id, $rCerdo->listaPrecioId, 'cerdo no esta overrideado');
        $this->assertSame(7500.0, $rCerdo->precioUnitario);
    }

    public function test_precio_especial_fijo_ignora_tramos(): void
    {
        $cliente = $this->crearCliente($this->minorista);
        ClientePrecio::create([
            'cliente_id'      => $cliente->id,
            'articulo_id'     => $this->pollo->id,
            'precio_especial' => 1500,
            'activo'          => true,
        ]);

        $r = $this->engine->cotizar(new CotizacionRequest($this->pollo->id, 25, $cliente->id));

        $this->assertSame('precio_especial', $r->reglaAplicada);
        $this->assertSame(1500.0, $r->precioUnitario);
        $this->assertNull($r->tramoAplicadoId);
    }

    public function test_sin_tramo_aplicable_cae_en_precio_unitario_articulo(): void
    {
        // Articulo nuevo solo con tramo 1-4 en minorista
        $art = Articulo::create([
            'codigo' => 'X', 'descripcion' => 'X', 'rubro_id' => $this->rubroPollos->id,
            'tipo_venta' => 'PESO', 'unidad_medida' => 'kg',
            'precio_costo' => 100, 'precio_unitario' => 999, 'precio_unitario_caja' => 0,
            'alicuota_iva' => 21, 'estado' => 'A',
        ]);
        ArticuloPrecio::create([
            'articulo_id' => $art->id, 'lista_precio_id' => $this->minorista->id,
            'cantidad_desde' => 1, 'cantidad_hasta' => 4, 'precio' => 100,
            'activo' => true, 'orden' => 0,
        ]);

        $r = $this->engine->cotizar(new CotizacionRequest($art->id, 50));

        $this->assertSame('fallback_articulo', $r->reglaAplicada);
        $this->assertSame(999.0, $r->precioUnitario);
        $this->assertNull($r->tramoAplicadoId);
    }

    public function test_bonificacion_cliente_se_aplica(): void
    {
        $cliente = $this->crearCliente($this->minorista, bonif: 10);

        $r = $this->engine->cotizar(new CotizacionRequest($this->pollo->id, 2, $cliente->id));

        // 3000 * (1 - 0.10) = 2700
        $this->assertSame(2700.0, $r->precioUnitario);
        $this->assertSame(10.0, $r->bonificacionAplicada);
    }

    public function test_bonificacion_cliente_y_override_de_rubro_se_acumulan(): void
    {
        $cliente = $this->crearCliente($this->minorista, bonif: 5);
        ClientePrecio::create([
            'cliente_id'   => $cliente->id,
            'rubro_id'     => $this->rubroPollos->id,
            'bonificacion' => 5,
            'activo'       => true,
        ]);

        $r = $this->engine->cotizar(new CotizacionRequest($this->pollo->id, 2, $cliente->id));

        // 3000 * (1 - 0.10) = 2700
        $this->assertSame(10.0, $r->bonificacionAplicada);
        $this->assertSame(2700.0, $r->precioUnitario);
    }

    public function test_recargo_no_efectivo_solo_aplica_a_pago_no_efectivo(): void
    {
        $cliente = $this->crearCliente($this->mayorista); // mayorista tiene recargo 5%

        $efectivo = $this->engine->cotizar(new CotizacionRequest(
            articuloId: $this->pollo->id,
            cantidad: 10,
            clienteId: $cliente->id,
            formaPago: 'efectivo',
        ));
        $tarjeta = $this->engine->cotizar(new CotizacionRequest(
            articuloId: $this->pollo->id,
            cantidad: 10,
            clienteId: $cliente->id,
            formaPago: 'tarjeta',
        ));

        $this->assertSame(2500.0, $efectivo->precioUnitario);
        $this->assertSame(0.0, $efectivo->recargoAplicado);

        // 2500 * 1.05 = 2625
        $this->assertSame(2625.0, $tarjeta->precioUnitario);
        $this->assertSame(5.0, $tarjeta->recargoAplicado);
    }

    public function test_iva_y_total_se_calculan_correctamente(): void
    {
        $r = $this->engine->cotizar(new CotizacionRequest($this->pollo->id, 10));

        $this->assertSame(2800.0, $r->precioUnitario);
        $this->assertSame(28000.0, $r->subtotal);
        $this->assertSame(10.5, $r->alicuotaIva);
        $this->assertSame(2940.0, $r->montoIva);   // 28000 * 0.105
        $this->assertSame(30940.0, $r->total);     // 28000 + 2940
    }

    public function test_articulo_inexistente_lanza_excepcion(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->engine->cotizar(new CotizacionRequest(99999, 1));
    }

    public function test_cantidad_cero_o_negativa_lanza_excepcion(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->engine->cotizar(new CotizacionRequest($this->pollo->id, 0));
    }

    public function test_si_no_hay_lista_default_lanza_excepcion(): void
    {
        // Quitamos el flag default de cualquier lista (puede haber otras creadas por migracion).
        ListaPrecio::query()->update(['es_default' => false]);

        $this->expectException(RuntimeException::class);
        $this->engine->cotizar(new CotizacionRequest($this->pollo->id, 1));
    }

    public function test_desglose_contiene_los_pasos_principales(): void
    {
        $cliente = $this->crearCliente($this->mayorista, bonif: 10);

        $r = $this->engine->cotizar(new CotizacionRequest(
            articuloId: $this->pollo->id,
            cantidad: 10,
            clienteId: $cliente->id,
            formaPago: 'tarjeta',
        ));

        $pasos = array_map(fn ($p) => $p->paso, $r->desglose);

        $this->assertContains('lista_resuelta', $pasos);
        $this->assertContains('tramo', $pasos);
        $this->assertContains('bonificacion', $pasos);
        $this->assertContains('recargo_pago', $pasos);
        $this->assertContains('iva', $pasos);
    }
}
