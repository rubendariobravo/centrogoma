<?php

namespace Tests\Feature\Caja;

use App\Livewire\Cajas\Index as CajasIndex;
use App\Models\Caja;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TransferenciaCajaTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $this->user->assignRole('super-admin');
        $this->actingAs($this->user);
    }

    private function diaria(): Caja
    {
        return Caja::create([
            'user_id' => $this->user->id, 'tipo' => 'diaria',
            'fecha_apertura' => now()->toDateString(), 'hora_apertura' => now(),
            'saldo_inicial' => 1000, 'estado' => 'abierta',
        ]);
    }

    public function test_admin_puede_crear_caja_general(): void
    {
        Livewire::test(CajasIndex::class)
            ->set('nueva_nombre', 'Caja General')
            ->set('nueva_tipo', 'general')
            ->set('nueva_saldo', 0)
            ->call('crearCajaGeneral')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('cajas', [
            'nombre' => 'Caja General', 'tipo' => 'general', 'user_id' => null, 'estado' => 'abierta',
        ]);
    }

    public function test_transferencia_mueve_saldo_entre_cajas(): void
    {
        $origen  = $this->diaria();
        $destino = Caja::create([
            'user_id' => null, 'tipo' => 'general', 'nombre' => 'Caja General',
            'fecha_apertura' => now()->toDateString(), 'hora_apertura' => now(),
            'saldo_inicial' => 0, 'estado' => 'abierta',
        ]);

        Livewire::test(CajasIndex::class)
            ->set('transferOrigen', $origen->id)
            ->set('transferDestino', $destino->id)
            ->set('transferMonto', 400)
            ->call('transferir')
            ->assertHasNoErrors();

        $this->assertEquals(600, $origen->fresh()->saldo_esperado);
        $this->assertEquals(400, $destino->fresh()->saldo_esperado);
    }

    public function test_transferencia_no_supera_saldo_disponible(): void
    {
        $origen  = $this->diaria();
        $destino = Caja::create([
            'user_id' => null, 'tipo' => 'general', 'nombre' => 'Caja General',
            'fecha_apertura' => now()->toDateString(), 'hora_apertura' => now(),
            'saldo_inicial' => 0, 'estado' => 'abierta',
        ]);

        Livewire::test(CajasIndex::class)
            ->set('transferOrigen', $origen->id)
            ->set('transferDestino', $destino->id)
            ->set('transferMonto', 5000)
            ->call('transferir')
            ->assertHasErrors(['transferMonto']);

        $this->assertEquals(1000, $origen->fresh()->saldo_esperado);
    }

    /**
     * Una caja general que recibe efectivo pero paga por cheque/transferencia
     * bancaria termina con el saldo bruto en negativo (los egresos no-efectivo
     * no tienen su ingreso registrado). Eso no debe bloquear las transferencias:
     * el techo es el efectivo disponible, no el bruto.
     */
    public function test_caja_general_con_bruto_negativo_transfiere_hasta_su_efectivo(): void
    {
        $general = Caja::create([
            'user_id' => null, 'tipo' => 'general', 'nombre' => 'Caja General',
            'fecha_apertura' => now()->toDateString(), 'hora_apertura' => now(),
            'saldo_inicial' => 0, 'estado' => 'abierta',
        ]);
        $destino = $this->diaria();

        $svc = app(\App\Services\Caja\CajaService::class);
        // Se fondea con efectivo…
        $svc->registrarMovimiento($general, 'ingreso', 'ajuste', 5000, ['forma_pago' => 'efectivo']);
        // …y paga por medios que no son efectivo → el bruto se va a negativo.
        $svc->registrarMovimiento($general, 'egreso', 'cheque', 20000, ['forma_pago' => 'cheque']);
        $svc->registrarMovimiento($general, 'egreso', 'pago_proveedor', 30000, ['forma_pago' => 'transferencia']);

        $general->refresh();
        $this->assertEquals(-45000, $general->saldo_esperado);
        $this->assertEquals(5000, $general->saldo_efectivo_esperado);
        $this->assertEquals(5000, $general->saldo_transferible);

        Livewire::test(CajasIndex::class)
            ->set('transferOrigen', $general->id)
            ->set('transferDestino', $destino->id)
            ->set('transferMonto', 4000)
            ->call('transferir')
            ->assertHasNoErrors();

        $this->assertEquals(1000, $general->fresh()->saldo_efectivo_esperado);

        // Por encima del efectivo sigue rechazando.
        Livewire::test(CajasIndex::class)
            ->set('transferOrigen', $general->id)
            ->set('transferDestino', $destino->id)
            ->set('transferMonto', 2000)
            ->call('transferir')
            ->assertHasErrors(['transferMonto']);
    }

    public function test_cerrar_caja_con_transferencia_envia_saldo_a_general(): void
    {
        $destino = Caja::create([
            'user_id' => null, 'tipo' => 'general', 'nombre' => 'Caja General',
            'fecha_apertura' => now()->toDateString(), 'hora_apertura' => now(),
            'saldo_inicial' => 0, 'estado' => 'abierta',
        ]);
        $diaria = $this->diaria();

        Livewire::test(CajasIndex::class)
            ->set('saldo_final', 1000)
            ->set('transferirAlCerrar', true)
            ->set('transferDestinoCierre', $destino->id)
            ->call('cerrarCaja')
            ->assertHasNoErrors();

        $this->assertEquals('cerrada', $diaria->fresh()->estado);
        $this->assertEquals(1000, (float) $destino->fresh()->total_transferencias_entrada);
    }
}
