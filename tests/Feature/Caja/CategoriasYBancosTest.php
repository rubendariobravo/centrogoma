<?php

namespace Tests\Feature\Caja;

use App\Livewire\Bancos\Index as BancosIndex;
use App\Livewire\Gastos\Index as GastosIndex;
use App\Models\Banco;
use App\Models\CategoriaGasto;
use App\Models\Cheque;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * ABM de categorías de gasto (modal dentro de /gastos) y de bancos (/bancos).
 */
class CategoriasYBancosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    public function test_crear_y_renombrar_categoria(): void
    {
        $lw = Livewire::test(GastosIndex::class)
            ->call('abrirCategorias')
            ->set('categoriaNombre', '  Fletes  ')
            ->call('guardarCategoria')
            ->assertHasNoErrors()
            ->assertSee('Fletes');

        $cat = CategoriaGasto::where('nombre', 'Fletes')->firstOrFail();

        $lw->call('editarCategoria', $cat->id)
            ->set('categoriaNombre', 'Fletes y acarreos')
            ->call('guardarCategoria')
            ->assertHasNoErrors();

        $this->assertSame('Fletes y acarreos', $cat->fresh()->nombre);
    }

    public function test_categoria_duplicada_se_rechaza(): void
    {
        CategoriaGasto::create(['nombre' => 'Limpieza', 'activo' => true]);

        Livewire::test(GastosIndex::class)
            ->call('abrirCategorias')
            ->set('categoriaNombre', 'Limpieza')
            ->call('guardarCategoria')
            ->assertHasErrors('categoriaNombre');

        $this->assertSame(1, CategoriaGasto::count());
    }

    public function test_desactivar_categoria_la_saca_del_selector(): void
    {
        $cat = CategoriaGasto::create(['nombre' => 'Refrigerio', 'activo' => true]);

        Livewire::test(GastosIndex::class)
            ->call('toggleCategoria', $cat->id);

        $this->assertFalse($cat->fresh()->activo);
        $this->assertSame(0, CategoriaGasto::activas()->count());
    }

    public function test_crear_y_editar_banco(): void
    {
        Livewire::test(BancosIndex::class)
            ->call('openCreate')
            ->set('descripcion', 'Banco Masventas')
            ->set('cuit', '30500000000')
            ->call('save')
            ->assertHasNoErrors();

        $banco = Banco::where('descripcion', 'Banco Masventas')->firstOrFail();

        Livewire::test(BancosIndex::class)
            ->call('openEdit', $banco->id)
            ->set('descripcion', 'Banco Masventas SA')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Banco Masventas SA', $banco->fresh()->descripcion);
    }

    public function test_banco_con_cheques_se_desactiva_en_lugar_de_borrarse(): void
    {
        $banco = Banco::create(['descripcion' => 'Banco con cheques', 'activo' => true]);
        Cheque::create(['tipo' => 'tercero', 'estado' => 'cartera', 'banco_id' => $banco->id, 'monto' => 100]);
        $libre = Banco::create(['descripcion' => 'Banco libre', 'activo' => true]);

        Livewire::test(BancosIndex::class)
            ->call('confirmDelete', $banco->id)->call('delete')
            ->call('confirmDelete', $libre->id)->call('delete');

        $this->assertFalse($banco->fresh()->activo);
        $this->assertNull(Banco::find($libre->id));
    }

    public function test_pantallas_responden(): void
    {
        $this->get(route('gastos'))->assertOk();
        $this->get(route('bancos'))->assertOk();
    }
}
