<?php

namespace Tests\Feature\Caja;

use App\Livewire\Gastos\Index as GastosIndex;
use App\Models\Caja;
use App\Models\CategoriaGasto;
use App\Models\Gasto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class GastosTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Caja $caja;
    private CategoriaGasto $categoria;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        $this->caja = Caja::create([
            'user_id'        => $this->user->id,
            'tipo'           => 'diaria',
            'fecha_apertura' => now()->toDateString(),
            'hora_apertura'  => now(),
            'saldo_inicial'  => 1000,
            'estado'         => 'abierta',
        ]);

        $this->categoria = CategoriaGasto::create(['nombre' => 'Combustible']);
    }

    public function test_registrar_gasto_impacta_caja_y_crea_movimiento(): void
    {
        Livewire::test(GastosIndex::class)
            ->call('openCreate')
            ->set('categoria_gasto_id', $this->categoria->id)
            ->set('caja_id', $this->caja->id)
            ->set('monto', 350)
            ->set('forma_pago', 'efectivo')
            ->set('descripcion', 'Nafta camión')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('gastos', [
            'caja_id'            => $this->caja->id,
            'categoria_gasto_id' => $this->categoria->id,
            'monto'              => 350,
        ]);

        $this->assertDatabaseHas('movimiento_cajas', [
            'caja_id'  => $this->caja->id,
            'tipo'     => 'egreso',
            'concepto' => 'gasto',
            'monto'    => 350,
        ]);

        $this->assertEquals(350, (float) $this->caja->fresh()->total_gastos);
        // 1000 - 350 = 650
        $this->assertEquals(650, $this->caja->fresh()->saldo_esperado);
    }

    public function test_eliminar_gasto_revierte_efecto_en_caja(): void
    {
        $gasto = Gasto::create([
            'caja_id'            => $this->caja->id,
            'categoria_gasto_id' => $this->categoria->id,
            'user_id'            => $this->user->id,
            'fecha'              => now()->toDateString(),
            'monto'              => 200,
            'forma_pago'         => 'efectivo',
            'estado'             => 'A',
        ]);
        app(\App\Services\Caja\CajaService::class)->registrarMovimiento(
            $this->caja, 'egreso', 'gasto', 200,
            ['referencia_type' => Gasto::class, 'referencia_id' => $gasto->id]
        );

        $this->assertEquals(200, (float) $this->caja->fresh()->total_gastos);

        Livewire::test(GastosIndex::class)
            ->call('confirmDelete', $gasto->id)
            ->call('delete');

        $this->assertEquals(0, (float) $this->caja->fresh()->total_gastos);
        $this->assertSoftDeleted('gastos', ['id' => $gasto->id]);
    }

    public function test_no_permite_gasto_sin_categoria(): void
    {
        Livewire::test(GastosIndex::class)
            ->call('openCreate')
            ->set('caja_id', $this->caja->id)
            ->set('monto', 100)
            ->call('save')
            ->assertHasErrors(['categoria_gasto_id']);
    }
}
