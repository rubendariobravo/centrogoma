<?php

namespace Tests\Feature\Anulaciones;

use App\Livewire\OrdenPago\Form as OrdenPagoForm;
use App\Livewire\OrdenPago\Index as OrdenPagoIndex;
use App\Livewire\Recibos\Form as ReciboForm;
use App\Livewire\Recibos\Index as RecibosIndex;
use App\Livewire\Ventas\Form as VentaForm;
use App\Livewire\Ventas\Index as VentasIndex;
use App\Models\Articulo;
use App\Models\ArticuloPrecio;
use App\Models\Banco;
use App\Models\Caja;
use App\Models\CcCliente;
use App\Models\Cheque;
use App\Models\Cliente;
use App\Models\Compra;
use App\Models\ListaPrecio;
use App\Models\MovimientoCaja;
use App\Models\OrdenPago;
use App\Models\Presupuesto;
use App\Models\Proveedor;
use App\Models\Recibo;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Anulación de ventas, recibos y órdenes de pago: se registran con los formularios reales
 * y se verifica que la anulación deshaga exactamente lo que el alta aplicó.
 */
class AnulacionesTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Caja $caja;
    private Cliente $cliente;
    private Articulo $articulo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->user->assignRole(Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']));
        $this->actingAs($this->user);

        $this->caja = Caja::create([
            'user_id' => $this->user->id, 'tipo' => 'diaria', 'estado' => 'abierta',
            'fecha_apertura' => now()->toDateString(), 'hora_apertura' => now(), 'saldo_inicial' => 0,
        ]);

        $this->cliente = Cliente::create(['razon_social' => 'Cliente CC', 'estado' => 'A', 'saldo' => 0]);

        $lista = ListaPrecio::firstOrCreate(
            ['codigo' => 'MINORISTA'],
            ['nombre' => 'Minorista', 'tipo' => 'minorista', 'es_default' => true, 'activo' => true, 'orden' => 1]
        );
        $this->articulo = Articulo::create([
            'codigo' => 'MAN001', 'descripcion' => 'Manguera', 'tipo_venta' => 'CAJA', 'unidad_medida' => 'unidad',
            'precio_costo' => 50, 'precio_unitario' => 100, 'precio_unitario_caja' => 0,
            'alicuota_iva' => 21, 'estado' => 'A', 'modifica_stock' => true, 'stock' => 20,
        ]);
        ArticuloPrecio::create([
            'articulo_id' => $this->articulo->id, 'lista_precio_id' => $lista->id,
            'cantidad_desde' => 1, 'cantidad_hasta' => null, 'precio' => 100, 'activo' => true, 'orden' => 0,
        ]);
    }

    // ── helpers ─────────────────────────────────────────────────────

    /** Registra una venta de 10 unidades a $100 + IVA 21% = $1.210 con los pagos indicados. */
    private function vender(array $pagos, ?int $presupuestoId = null): Venta
    {
        $lw = Livewire::test(VentaForm::class)->set('cliente_id', $this->cliente->id);
        if ($presupuestoId) {
            $lw->set('presupuestoOrigenId', $presupuestoId);
        }

        $lw->call('registrarVenta', [
            'items' => [[
                'articulo_id' => $this->articulo->id, 'descripcion' => 'Manguera', 'tipo_venta' => 'CAJA',
                'unidad_medida' => 'unidad', 'cantidad' => 10, 'precio' => 100, 'alicuota_iva' => 21,
                'descuento' => 0, 'subtotal' => 1000,
            ]],
            'pagos'    => $pagos,
            'neto_21'  => 1000,
            'iva_21'   => 210,
            'total'    => 1210,
        ])->assertHasNoErrors();

        return Venta::latest('id')->firstOrFail();
    }

    private function cobrar(Venta $venta, float $monto, string $forma = 'efectivo', array $cheque = []): Recibo
    {
        $lw = Livewire::test(ReciboForm::class)
            ->set('cliente_id', $this->cliente->id)
            ->set('forma_pago', $forma);
        foreach ($cheque as $k => $v) {
            $lw->set($k, $v);
        }
        $lw->call('save', [['venta_id' => $venta->id, 'monto_aplicar' => $monto]])->assertHasNoErrors();

        return Recibo::latest('id')->firstOrFail();
    }

    private function anular(string $index, int $id, string $motivo = 'Error de carga'): \Livewire\Features\SupportTesting\Testable
    {
        return Livewire::test($index)
            ->call('confirmarAnular', $id)
            ->set('motivoAnulacion', $motivo)
            ->call('anular');
    }

    // ── ventas ──────────────────────────────────────────────────────

    public function test_venta_mixta_asienta_un_movimiento_por_forma_de_pago(): void
    {
        $this->vender([
            ['forma_pago' => 'efectivo', 'monto' => 710],
            ['forma_pago' => 'tarjeta', 'monto' => 500],
        ]);

        $movs = MovimientoCaja::orderBy('id')->get();
        $this->assertSame(['efectivo', 'tarjeta'], $movs->pluck('forma_pago')->all());
        $caja = $this->caja->fresh();
        $this->assertEquals(1210, (float) $caja->total_ventas);
        $this->assertEquals(710, $caja->saldo_efectivo_esperado);
    }

    public function test_anular_venta_en_cuenta_corriente_revierte_todo(): void
    {
        $venta = $this->vender([
            ['forma_pago' => 'efectivo', 'monto' => 210],
            ['forma_pago' => 'cuenta_corriente', 'monto' => 1000],
        ]);

        $this->assertEquals(10, (float) $this->articulo->fresh()->stock);
        $this->assertEquals(1000, (float) $this->cliente->fresh()->saldo);
        $this->assertEquals(1210, (float) $this->caja->fresh()->total_ventas);

        $this->anular(VentasIndex::class, $venta->id)->assertHasNoErrors();

        $venta->refresh();
        $this->assertSame('A', $venta->estado);
        $this->assertEquals(0, (float) $venta->saldo);
        $this->assertEquals(1210, (float) $venta->totalanulado);
        $this->assertStringContainsString('Error de carga', $venta->observacion);
        $this->assertEquals(20, (float) $this->articulo->fresh()->stock);
        $this->assertEquals(0, (float) $this->cliente->fresh()->saldo);
        $this->assertEquals(0, (float) $this->caja->fresh()->total_ventas);
        $this->assertSame(0, MovimientoCaja::count());
        $this->assertEquals(1000, (float) CcCliente::where('referencia_id', $venta->id)->where('haber', '>', 0)->value('haber'));
    }

    public function test_venta_con_recibo_aplicado_no_se_anula_hasta_anular_el_recibo(): void
    {
        $venta  = $this->vender([['forma_pago' => 'cuenta_corriente', 'monto' => 1210]]);
        $recibo = $this->cobrar($venta, 400);

        $this->anular(VentasIndex::class, $venta->id)->assertHasErrors('motivoAnulacion');
        $this->assertSame('P', $venta->fresh()->estado);

        $this->anular(RecibosIndex::class, $recibo->id)->assertHasNoErrors();
        $this->anular(VentasIndex::class, $venta->id)->assertHasNoErrors();

        $this->assertSame('A', $venta->fresh()->estado);
        $this->assertEquals(0, (float) $this->cliente->fresh()->saldo);
        $this->assertSame(0, MovimientoCaja::count());
    }

    public function test_anular_venta_anula_cheque_en_cartera(): void
    {
        $banco = Banco::create(['descripcion' => 'Banco Test', 'activo' => true]);
        $venta = $this->vender([[
            'forma_pago' => 'cheque', 'monto' => 1210,
            'cheque' => ['banco_id' => $banco->id, 'nro' => '123', 'fecha_pago' => now()->addDays(30)->toDateString()],
        ]]);
        $cheque = Cheque::where('venta_id', $venta->id)->sole();
        $this->assertSame('cartera', $cheque->estado);

        $this->anular(VentasIndex::class, $venta->id)->assertHasNoErrors();

        $this->assertSame('anulado', $cheque->fresh()->estado);
    }

    public function test_venta_con_cheque_rechazado_no_se_anula(): void
    {
        $banco = Banco::create(['descripcion' => 'Banco Test', 'activo' => true]);
        $venta = $this->vender([[
            'forma_pago' => 'cheque', 'monto' => 1210,
            'cheque' => ['banco_id' => $banco->id, 'nro' => '999', 'fecha_pago' => now()->toDateString()],
        ]]);
        Cheque::where('venta_id', $venta->id)->update(['estado' => 'rechazado']);

        $this->anular(VentasIndex::class, $venta->id)->assertHasErrors('motivoAnulacion');
        $this->assertSame('C', $venta->fresh()->estado);
    }

    public function test_anular_venta_reabre_el_presupuesto_de_origen(): void
    {
        $presupuesto = Presupuesto::create([
            'cliente_id' => $this->cliente->id, 'user_id' => $this->user->id, 'nro_presupuesto' => 1,
            'fecha' => now()->toDateString(), 'fecha_vencimiento' => now()->addDays(15)->toDateString(),
            'total' => 1210, 'estado' => 'A',
        ]);

        $venta = $this->vender([['forma_pago' => 'efectivo', 'monto' => 1210]], $presupuesto->id);
        $this->assertSame('F', $presupuesto->fresh()->estado);
        $this->assertSame($venta->id, $presupuesto->fresh()->venta_id);

        $this->anular(VentasIndex::class, $venta->id)->assertHasNoErrors();

        $this->assertSame('A', $presupuesto->fresh()->estado);
        $this->assertNull($presupuesto->fresh()->venta_id);
    }

    public function test_venta_con_cae_no_se_anula(): void
    {
        $venta = $this->vender([['forma_pago' => 'efectivo', 'monto' => 1210]]);
        $venta->update(['cae' => '12345678901234']);

        $this->anular(VentasIndex::class, $venta->id)->assertHasErrors('motivoAnulacion');
        $this->assertSame('C', $venta->fresh()->estado);
    }

    // ── recibos ─────────────────────────────────────────────────────

    public function test_anular_recibo_reabre_venta_deuda_y_caja(): void
    {
        $venta  = $this->vender([['forma_pago' => 'cuenta_corriente', 'monto' => 1210]]);
        $recibo = $this->cobrar($venta, 1210);

        $this->assertSame('C', $venta->fresh()->estado);
        $this->assertEquals(0, (float) $this->cliente->fresh()->saldo);
        $this->assertEquals(1210, (float) $this->caja->fresh()->total_cobros);

        $this->anular(RecibosIndex::class, $recibo->id)->assertHasNoErrors();

        $this->assertSame('A', $recibo->fresh()->estado);
        $this->assertSame('P', $venta->fresh()->estado);
        $this->assertEquals(1210, (float) $venta->fresh()->saldo);
        $this->assertEquals(1210, (float) $this->cliente->fresh()->saldo);
        $this->assertEquals(0, (float) $this->caja->fresh()->total_cobros);
    }

    public function test_anular_recibo_con_cheque_anula_el_cheque(): void
    {
        $banco  = Banco::create(['descripcion' => 'Banco Test', 'activo' => true]);
        $venta  = $this->vender([['forma_pago' => 'cuenta_corriente', 'monto' => 1210]]);
        $recibo = $this->cobrar($venta, 1210, 'cheque', [
            'cheque_banco_id' => $banco->id, 'cheque_nro' => '555',
            'cheque_librador_nombre' => 'Librador SA', 'cheque_fecha_pago' => now()->addDays(10)->toDateString(),
        ]);

        $this->anular(RecibosIndex::class, $recibo->id)->assertHasNoErrors();

        $this->assertSame('anulado', Cheque::where('recibo_id', $recibo->id)->sole()->estado);
    }

    // ── órdenes de pago ─────────────────────────────────────────────

    private function compraEnCc(Proveedor $proveedor, float $total): Compra
    {
        $proveedor->increment('saldo', $total);

        return Compra::create([
            'proveedor_id' => $proveedor->id, 'user_id' => $this->user->id, 'fecha' => now()->toDateString(),
            'letra' => 'A', 'nro_factura' => '0001-00000001', 'forma_pago' => 'cuenta_corriente',
            'total' => $total, 'saldo' => $total, 'estado' => 'P', 'procesada' => true,
        ]);
    }

    public function test_anular_orden_de_pago_reabre_compra_deuda_y_caja(): void
    {
        $proveedor = Proveedor::create(['razon_social' => 'Prov', 'estado' => 'A', 'saldo' => 0]);
        $compra    = $this->compraEnCc($proveedor, 800);

        Livewire::test(OrdenPagoForm::class)
            ->set('proveedor_id', $proveedor->id)
            ->set('forma_pago', 'efectivo')
            ->call('save', [['compra_id' => $compra->id, 'monto_aplicar' => 800]])
            ->assertHasNoErrors();
        $orden = OrdenPago::sole();

        $this->assertSame('C', $compra->fresh()->estado);
        $this->assertEquals(0, (float) $proveedor->fresh()->saldo);
        $this->assertEquals(800, (float) $this->caja->fresh()->total_pagos);

        $this->anular(OrdenPagoIndex::class, $orden->id)->assertHasNoErrors();

        $this->assertSame('A', $orden->fresh()->estado);
        $this->assertSame('P', $compra->fresh()->estado);
        $this->assertEquals(800, (float) $compra->fresh()->saldo);
        $this->assertEquals(800, (float) $proveedor->fresh()->saldo);
        $this->assertEquals(0, (float) $this->caja->fresh()->total_pagos);
    }

    public function test_anular_orden_de_pago_con_cheque_propio_anula_el_cheque(): void
    {
        $banco     = Banco::create(['descripcion' => 'Banco Test', 'activo' => true]);
        $proveedor = Proveedor::create(['razon_social' => 'Prov', 'estado' => 'A', 'saldo' => 0]);
        $compra    = $this->compraEnCc($proveedor, 500);

        Livewire::test(OrdenPagoForm::class)
            ->set('proveedor_id', $proveedor->id)
            ->set('forma_pago', 'cheque')
            ->set('cheque_banco_id', $banco->id)
            ->set('cheque_nro', '777')
            ->set('cheque_fecha_pago', now()->addDays(30)->toDateString())
            ->call('save', [['compra_id' => $compra->id, 'monto_aplicar' => 500]])
            ->assertHasNoErrors();
        $orden = OrdenPago::sole();

        $this->anular(OrdenPagoIndex::class, $orden->id)->assertHasNoErrors();

        $this->assertSame('anulado', Cheque::where('orden_pago_id', $orden->id)->sole()->estado);
        $this->assertEquals(500, (float) $proveedor->fresh()->saldo);
    }

    // ── reglas generales ────────────────────────────────────────────

    public function test_motivo_corto_es_rechazado(): void
    {
        $venta = $this->vender([['forma_pago' => 'efectivo', 'monto' => 1210]]);

        $this->anular(VentasIndex::class, $venta->id, 'err')->assertHasErrors('motivoAnulacion');
        $this->assertSame('C', $venta->fresh()->estado);
    }

    public function test_usuario_sin_rol_admin_no_puede_anular(): void
    {
        $venta = $this->vender([['forma_pago' => 'efectivo', 'monto' => 1210]]);

        $this->actingAs(User::factory()->create());
        $this->anular(VentasIndex::class, $venta->id)->assertHasErrors('motivoAnulacion');

        $this->assertSame('C', $venta->fresh()->estado);
    }
}
