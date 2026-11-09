<?php

namespace Tests\Feature\CuentaCorriente;

use App\Livewire\CcClientes\Index as CcClientesIndex;
use App\Models\CcCliente;
use App\Models\CcProveedor;
use App\Models\Cliente;
use App\Models\Compra;
use App\Models\Proveedor;
use App\Models\User;
use App\Models\Venta;
use App\Services\CuentaCorriente\ResumenCuenta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** Resumen de cuenta corriente y listado de saldos con pendientes, en PDF y Excel (clientes y proveedores). */
class ResumenCuentaTest extends TestCase
{
    use RefreshDatabase;

    private Cliente $cliente;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());

        $this->cliente = Cliente::create(['razon_social' => 'Taller Norte', 'cuit' => '20123456789', 'estado' => 'A', 'saldo' => 7000]);
        Cliente::create(['razon_social' => 'Al día SRL', 'estado' => 'A', 'saldo' => 0]);
        Cliente::create(['razon_social' => 'Con crédito SA', 'estado' => 'A', 'saldo' => -500]);

        // Cargados fuera de orden: la factura del 05/10 se grabó después del recibo.
        $this->asiento('2026-09-20', 'Saldo inicial', 2000, 0);
        $this->asiento('2026-10-10', 'Recibo #3', 0, 3000);
        $this->asiento('2026-10-05', 'Venta #10', 8000, 0);

        $this->venta(10, '2026-10-05', 8000, 5000);
        $this->venta(11, '2026-09-20', 2000, 2000, saldoInicial: true);
        $this->venta(12, '2026-10-01', 1000, 0, estado: 'C');
    }

    private function asiento(string $fecha, string $concepto, float $debe, float $haber): void
    {
        CcCliente::create(['cliente_id' => $this->cliente->id, 'fecha' => $fecha, 'concepto' => $concepto,
            'debe' => $debe, 'haber' => $haber, 'saldo' => 0]);
    }

    private function venta(int $nro, string $fecha, float $total, float $saldo, bool $saldoInicial = false, string $estado = 'P'): void
    {
        Venta::create(['cliente_id' => $this->cliente->id, 'nro_venta' => $nro, 'fecha' => $fecha, 'total' => $total,
            'saldo' => $saldo, 'estado' => $estado, 'letra_comprobante' => 'B', 'saldo_inicial' => $saldoInicial,
            'tipo_comprobante' => 'factura']);
    }

    public function test_saldo_corrido_en_orden_de_fecha_y_saldo_anterior(): void
    {
        $rc = new ResumenCuenta('clientes');

        $todo = $rc->movimientos($this->cliente->id);
        $this->assertSame([2000.0, 10000.0, 7000.0], $todo['movimientos']->pluck('saldo')->all());
        $this->assertEquals(7000.0, $todo['saldo']);   // = ficha

        $octubre = $rc->movimientos($this->cliente->id, '2026-10-01', '2026-10-31');
        $this->assertEquals(2000.0, $octubre['saldo_anterior']);
        $this->assertCount(2, $octubre['movimientos']);
        $this->assertEquals(8000.0, $octubre['debe']);
        $this->assertEquals(3000.0, $octubre['haber']);
        $this->assertEquals(7000.0, $octubre['saldo']);
    }

    public function test_pendientes_y_listado_de_saldos(): void
    {
        $rc = new ResumenCuenta('clientes');

        $pend = $rc->pendientes([$this->cliente->id]);
        $this->assertSame(['Saldo inicial', 'Venta B #10'], $pend->pluck('comprobante')->all());
        $this->assertEquals(7000.0, $pend->sum('saldo'));

        $this->assertSame(['Taller Norte'], $rc->saldos('deudores')->pluck('nombre')->all());
        $this->assertSame(['Con crédito SA'], $rc->saldos('a_favor')->pluck('nombre')->all());
        $this->assertCount(2, $rc->saldos('todos'));
    }

    public function test_pdf_y_excel_del_resumen_y_de_los_saldos(): void
    {
        $this->get(route('cc.resumen', ['tipo' => 'clientes', 'id' => $this->cliente->id, 'desde' => '2026-10-01']))
            ->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->get(route('cc.resumen', ['tipo' => 'clientes', 'id' => $this->cliente->id, 'formato' => 'xlsx']))
            ->assertOk()->assertDownload();
        $this->get(route('cc.saldos', ['tipo' => 'clientes', 'formato' => 'pdf']))
            ->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->get(route('cc.saldos', ['tipo' => 'clientes', 'filtro' => 'todos', 'formato' => 'xlsx']))
            ->assertOk()->assertDownload();

        $this->get('/cuenta-corriente/empleados/saldos')->assertNotFound();
        $this->get(route('cc.resumen', ['tipo' => 'clientes', 'id' => $this->cliente->id, 'desde' => '2026-10-10', 'hasta' => '2026-10-01']))
            ->assertSessionHasErrors('hasta');
    }

    public function test_el_resumen_muestra_los_movimientos_y_los_pendientes(): void
    {
        $rc  = new ResumenCuenta('clientes');
        $html = view('cuenta-corriente.resumen', [
            'esClientes' => true, 'cuenta' => $this->cliente, 'periodo' => 'todos', 'desde' => null,
            'mov' => $rc->movimientos($this->cliente->id), 'pendientes' => $rc->pendientes([$this->cliente->id]),
            'empresa' => ['nombre' => 'CentroGoma', 'cuit' => '', 'domicilio' => '', 'telefono' => ''],
        ])->render();

        $this->assertStringContainsString('Taller Norte', $html);
        $this->assertStringContainsString('$10.000,00', $html);   // saldo corrido después de la venta
        $this->assertStringContainsString('Comprobantes pendientes', $html);
        $this->assertStringContainsString('Venta B #10', $html);
    }

    public function test_proveedores(): void
    {
        $prov = Proveedor::create(['razon_social' => 'Distribuidora Sur', 'estado' => 'A', 'saldo' => 4000]);
        CcProveedor::create(['proveedor_id' => $prov->id, 'fecha' => '2026-10-02', 'concepto' => 'Compra FC A 0001-123',
            'debe' => 4000, 'haber' => 0, 'saldo' => 4000]);
        Compra::create(['proveedor_id' => $prov->id, 'fecha' => '2026-10-02', 'nro_factura' => '0001-123', 'letra' => 'A',
            'total' => 4000, 'saldo' => 4000, 'estado' => 'P', 'forma_pago' => 'cuenta_corriente']);

        $rc = new ResumenCuenta('proveedores');
        $this->assertEquals(4000.0, $rc->movimientos($prov->id)['saldo']);
        $this->assertSame('Factura A 0001-123', $rc->saldos()->first()['pendientes']->first()['comprobante']);
        $this->get(route('cc.resumen', ['tipo' => 'proveedores', 'id' => $prov->id]))->assertOk();
    }

    public function test_la_pantalla_ofrece_imprimir_y_exportar(): void
    {
        Livewire::test(CcClientesIndex::class)
            ->assertSee('Listado de saldos')
            ->call('seleccionarCliente', $this->cliente->id)
            ->assertSee('Resumen de cuenta')
            ->assertSee(route('cc.resumen', ['tipo' => 'clientes', 'id' => $this->cliente->id]), false);
    }
}
