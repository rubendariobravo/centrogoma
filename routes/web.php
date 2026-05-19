<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Dashboard;
use App\Livewire\Articulos\Index as ArticulosIndex;
use App\Livewire\Articulos\Form as ArticulosForm;
use App\Livewire\Clientes\Index as ClientesIndex;
use App\Livewire\Clientes\Form as ClientesForm;
use App\Livewire\Proveedores\Form as ProveedoresForm;
use App\Livewire\Ventas\Index as VentasIndex;
use App\Livewire\Ventas\Form as VentasForm;
use App\Livewire\Compras\Index as ComprasIndex;
use App\Livewire\Compras\Form as ComprasForm;
use App\Livewire\OrdenPago\Index as OrdenPagoIndex;
use App\Livewire\OrdenPago\Form as OrdenPagoForm;
use App\Livewire\Recibos\Form as RecibosForm;
use App\Livewire\Proveedores\Index as ProveedoresIndex;
use App\Livewire\Cajas\Index as CajasIndex;
use App\Livewire\Recibos\Index as RecibosIndex;
use App\Livewire\Rubros\Index as RubrosIndex;
use App\Livewire\Marcas\Index as MarcasIndex;
use App\Livewire\Listas\Index as ListasIndex;
use App\Livewire\Listas\Form as ListasForm;
use App\Livewire\Listas\Precios as ListasPrecios;
use App\Livewire\Empleados\Index as EmpleadosIndex;
use App\Livewire\Remitos\Index as RemitosIndex;
use App\Livewire\CcClientes\Index as CcClientesIndex;
use App\Livewire\CcProveedores\Index as CcProveedoresIndex;
use App\Livewire\Usuarios\Index as UsuariosIndex;
use App\Livewire\Roles\Index as RolesIndex;
use App\Livewire\Config\Index as ConfigIndex;
use App\Http\Controllers\VentaController;
use App\Http\Controllers\ListasController;
use App\Http\Controllers\PresupuestoController;
use App\Livewire\Presupuestos\Index as PresupuestosIndex;
use App\Livewire\Presupuestos\Form as PresupuestosForm;
use App\Livewire\Cheques\Index as ChequesIndex;
use App\Livewire\Auditorias\Index as AuditoriasIndex;
use App\Livewire\Ayuda\Index as AyudaIndex;

Route::redirect('/', 'login');

Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('dashboard', Dashboard::class)->name('dashboard');

    // Presupuestos
    Route::get('presupuestos', PresupuestosIndex::class)->name('presupuestos');
    Route::get('presupuestos/create', PresupuestosForm::class)->name('presupuestos.create');
    Route::get('presupuestos/{presupuesto}/edit', PresupuestosForm::class)->name('presupuestos.edit');
    Route::get('presupuestos/{presupuesto}/pdf', [PresupuestoController::class, 'pdf'])->name('presupuestos.pdf');

    // Ventas
    Route::get('ventas', VentasIndex::class)->name('ventas');
    Route::get('ventas/create', VentasForm::class)->name('ventas.create');
    Route::get('ventas/{venta}/pdf', [VentaController::class, 'generarPdf'])->name('ventas.pdf');
    Route::get('ventas/{venta}/ticket', [VentaController::class, 'generarTicket'])->name('ventas.ticket');
    Route::get('ventas/{venta}/imprimir', [VentaController::class, 'imprimir'])->name('ventas.imprimir');
    Route::get('ventas/{venta}', VentasForm::class)->name('ventas.show');
    Route::get('clientes', ClientesIndex::class)->name('clientes');
    Route::get('clientes/create', ClientesForm::class)->name('clientes.create');
    Route::get('clientes/{cliente}/edit', ClientesForm::class)->name('clientes.edit');
    Route::get('recibos', RecibosIndex::class)->name('recibos');
    Route::get('recibos/create', RecibosForm::class)->name('recibos.create');
    Route::get('recibos/{recibo}', RecibosForm::class)->name('recibos.show');

    // Compras
    Route::get('compras', ComprasIndex::class)->name('compras');
    Route::get('compras/create', ComprasForm::class)->name('compras.create');
    Route::get('compras/{compra}', ComprasForm::class)->name('compras.show');
    Route::get('proveedores', ProveedoresIndex::class)->name('proveedores');
    Route::get('proveedores/create', ProveedoresForm::class)->name('proveedores.create');
    Route::get('proveedores/{proveedor}/edit', ProveedoresForm::class)->name('proveedores.edit');
    Route::get('ordenes-pago', OrdenPagoIndex::class)->name('ordenes-pago');
    Route::get('ordenes-pago/create', OrdenPagoForm::class)->name('ordenes-pago.create');
    Route::get('ordenes-pago/{ordenPago}', OrdenPagoForm::class)->name('ordenes-pago.show');

    // Artículos
    Route::get('articulos', ArticulosIndex::class)->name('articulos');
    Route::get('articulos/create', ArticulosForm::class)->name('articulos.create');
    Route::get('articulos/{articulo}/edit', ArticulosForm::class)->name('articulos.edit');
    Route::get('rubros', RubrosIndex::class)->name('rubros');
    Route::get('marcas', MarcasIndex::class)->name('marcas');
    Route::get('listas-precios', ListasIndex::class)->name('listas-precios');
    Route::get('listas-precios/create', ListasForm::class)->name('listas-precios.create');
    Route::get('listas-precios/{lista}/edit', ListasForm::class)->name('listas-precios.edit');
    Route::get('listas-precios/{lista}/precios', ListasPrecios::class)->name('listas-precios.precios');
    Route::get('listas-precios/{lista}/exportar-precios', [ListasController::class, 'exportarPrecios'])->name('listas-precios.exportar');

    // Caja y Finanzas
    Route::get('cajas', CajasIndex::class)->name('cajas');
    Route::get('cheques', ChequesIndex::class)->name('cheques');
    Route::get('cc-clientes', CcClientesIndex::class)->name('cc-clientes');
    Route::get('cc-proveedores', CcProveedoresIndex::class)->name('cc-proveedores');

    // Logística
    Route::get('remitos', RemitosIndex::class)->name('remitos');
    Route::get('empleados', EmpleadosIndex::class)->name('empleados');

    // Administración
    Route::get('usuarios', UsuariosIndex::class)->name('usuarios');
    Route::get('roles', RolesIndex::class)->name('roles');
    Route::get('config', ConfigIndex::class)->name('config');
    Route::get('auditorias', AuditoriasIndex::class)->name('auditorias');
    Route::get('ayuda', AyudaIndex::class)->name('ayuda');

});

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__.'/auth.php';
