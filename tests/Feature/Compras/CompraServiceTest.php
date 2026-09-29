<?php

namespace Tests\Feature\Compras;

use App\Livewire\Compras\Form;
use App\Livewire\Compras\Index;
use App\Models\Articulo;
use App\Models\Caja;
use App\Models\CcProveedor;
use App\Models\Compra;
use App\Models\MovimientoCaja;
use App\Models\Proveedor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Compras\Form + CompraService: registrar procesa stock/costo y CC o caja;
 * anular desde el Index revierte esos efectos.
 */
class CompraServiceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Proveedor $proveedor;
    private Articulo $articulo;
    private Caja $caja;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        $this->proveedor = Proveedor::create(['razon_social' => 'Gomas SA', 'estado' => 'A', 'saldo' => 0]);

        $this->articulo = Articulo::create([
            'codigo' => 'MAN001', 'descripcion' => 'Manguera 1/2',
            'tipo_venta' => 'CAJA', 'unidad_medida' => 'unidad',
            'precio_costo' => 100, 'precio_unitario' => 200, 'precio_unitario_caja' => 0,
            'alicuota_iva' => 21, 'estado' => 'A', 'modifica_stock' => true, 'stock' => 10,
        ]);

        $this->caja = Caja::create([
            'user_id' => $this->user->id, 'tipo' => 'diaria', 'estado' => 'abierta',
            'fecha_apertura' => now()->toDateString(), 'saldo_inicial' => 0,
        ]);
    }

    private function registrar(string $formaPago): Compra
    {
        Livewire::test(Form::class)
            ->set('proveedor_id', $this->proveedor->id)
            ->set('forma_pago', $formaPago)
            ->set('cajaId', $this->caja->id)
            ->call('save', [
                'items' => [[
                    'articulo_id' => $this->articulo->id, 'descripcion' => 'Manguera 1/2',
                    'unidad_medida' => 'unidad', 'cantidad' => 5, 'precio' => 120,
                    'alicuota_iva' => 21, 'subtotal' => 600,
                ]],
                'neto_21' => 600, 'iva_21' => 126, 'total' => 726,
            ])
            ->assertHasNoErrors();

        return Compra::latest('id')->firstOrFail();
    }

    public function test_compra_en_cuenta_corriente_suma_stock_costo_y_deuda(): void
    {
        $compra = $this->registrar('cuenta_corriente');

        $this->assertTrue($compra->procesada);
        $this->assertSame('P', $compra->estado);
        $this->assertEquals(726, (float) $compra->saldo);
        $this->assertEquals(15, (float) $this->articulo->fresh()->stock);
        $this->assertEquals(120, (float) $this->articulo->fresh()->precio_costo);
        $this->assertEquals(726, (float) $this->proveedor->fresh()->saldo);
        $this->assertSame(1, CcProveedor::where('referencia_id', $compra->id)->count());
        $this->assertSame(0, MovimientoCaja::count());
    }

    public function test_compra_de_contado_egresa_de_caja(): void
    {
        $compra = $this->registrar('efectivo');

        $this->assertSame('C', $compra->estado);
        $this->assertEquals(0, (float) $compra->saldo);
        $this->assertSame($this->caja->id, $compra->caja_id);
        $this->assertEquals(0, (float) $this->proveedor->fresh()->saldo);

        $mov = MovimientoCaja::sole();
        $this->assertSame('egreso', $mov->tipo);
        $this->assertSame('pago_proveedor', $mov->concepto);
        $this->assertEquals(726, (float) $mov->monto);
        $this->assertEquals(726, (float) $this->caja->fresh()->total_pagos);
    }

    public function test_contado_sin_caja_no_registra(): void
    {
        Livewire::test(Form::class)
            ->set('proveedor_id', $this->proveedor->id)
            ->set('forma_pago', 'efectivo')
            ->set('cajaId', null)
            ->call('save', [
                'items' => [[
                    'articulo_id' => $this->articulo->id, 'cantidad' => 1, 'precio' => 10, 'alicuota_iva' => 21,
                ]],
                'neto_21' => 10, 'iva_21' => 2.1, 'total' => 12.1,
            ])
            ->assertHasErrors('cajaId');

        $this->assertSame(0, Compra::count());
    }

    public function test_cheque_no_es_forma_de_pago_de_compra(): void
    {
        Livewire::test(Form::class)
            ->set('proveedor_id', $this->proveedor->id)
            ->set('forma_pago', 'cheque')
            ->call('save', [
                'items' => [[
                    'articulo_id' => $this->articulo->id, 'cantidad' => 1, 'precio' => 10, 'alicuota_iva' => 21,
                ]],
                'total' => 12.1,
            ])
            ->assertHasErrors('forma_pago');
    }

    public function test_anular_compra_cc_revierte_stock_y_deuda(): void
    {
        $compra = $this->registrar('cuenta_corriente');

        Livewire::test(Index::class)
            ->call('confirmarAnular', $compra->id)
            ->set('motivoAnulacion', 'Factura duplicada')
            ->call('anular')
            ->assertHasNoErrors();

        $compra->refresh();
        $this->assertSame('A', $compra->estado);
        $this->assertFalse($compra->procesada);
        $this->assertEquals(10, (float) $this->articulo->fresh()->stock);
        $this->assertEquals(0, (float) $this->proveedor->fresh()->saldo);
        $this->assertSame(2, CcProveedor::where('referencia_id', $compra->id)->count());
    }

    public function test_anular_compra_contado_revierte_caja(): void
    {
        $compra = $this->registrar('efectivo');

        Livewire::test(Index::class)
            ->call('confirmarAnular', $compra->id)
            ->set('motivoAnulacion', 'Error de carga')
            ->call('anular');

        $this->assertSame(0, MovimientoCaja::count());
        $this->assertEquals(0, (float) $this->caja->fresh()->total_pagos);
        $this->assertEquals(10, (float) $this->articulo->fresh()->stock);
    }

    public function test_anular_requiere_motivo(): void
    {
        $compra = $this->registrar('cuenta_corriente');

        Livewire::test(Index::class)
            ->call('confirmarAnular', $compra->id)
            ->call('anular')
            ->assertHasErrors('motivoAnulacion');

        $this->assertSame('P', $compra->fresh()->estado);
    }
}
