# CLAUDE.md

This file provides guidance to Claude Code when working with this repository.

## Project Overview

**Avicola** is a Laravel 12 ERP for a poultry/meat wholesale business (client: Avisur).  
Stack: Laravel 12 · Livewire 3 (class-based) · Alpine.js · Tailwind CSS · MySQL · DomPDF · Spatie Permission · owen-it/laravel-auditing.

**Local URL:** `http://localhost/avicola`  
**Admin user:** rubendario.bravo@gmail.com / 12345678 (role: super-admin)

---

## Development Commands

```bash
# Fresh DB + all seeders (wipes data)
php artisan migrate:fresh --seed

# Seed only real article/price data (after migrate:fresh --seed)
php artisan db:seed --class=PreciosFrangoExcelSeeder

# Seed clients (production-safe, idempotent)
php artisan db:seed --class=ClientesAvisurSeeder

# Recreate admin user (after RefreshDatabase wipes it)
php artisan tinker --execute="
\$u = App\Models\User::firstOrCreate(
    ['email' => 'rubendario.bravo@gmail.com'],
    ['name' => 'Ruben Bravo', 'password' => bcrypt('12345678')]
);
\$u->password = bcrypt('12345678'); \$u->save();
\$r = Spatie\Permission\Models\Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
if (!\$u->hasRole('super-admin')) \$u->assignRole(\$r);
echo 'OK: #'.\$u->id;
"

# Create public symlink for logo uploads (run once per environment)
php artisan storage:link

# Run tests
php artisan test

# Target specific test suites
php artisan test --filter="Pricing|Listas|Clientes|FormPriceEngine"
```

> ⚠️ **Tests use `RefreshDatabase` against MySQL.** `phpunit.xml` is configured with `DB_CONNECTION=mysql` / port 3307.  
> Every time the full suite runs it wipes the DB. Re-run the seeders above to restore data.

---

## Architecture

### Frontend

All UI is **Livewire 3 class-based** components — no separate SPA or REST API.

- `app/Livewire/<Domain>/` — component PHP classes
- `resources/views/livewire/<domain>/` — Blade templates

Standard component pattern per domain:
- `Index` — list/search with filters
- `Form` — create + edit (receives `?int $id` in `mount()`)

**Alpine.js** handles local reactive state (item rows in Ventas, dropdowns, etc.).  
**Critical Alpine↔Livewire sync rule:** Alpine `addItem()` / `removeItem()` MUST call `await this.$wire.addItem()` / `await this.$wire.removeItem(index)` before `syncItems()`, otherwise Livewire's `$items` array gets out of sync and the next wire call silently drops rows.

### Database

**MySQL** in all environments. Migrations in `database/migrations/`.  
Config variables stored in `variable_entorno` table, read via `VariableEntorno::get($key, $default)`.

### Permissions

`spatie/laravel-permission` — roles/permissions seeded via `RolesAndPermissionsSeeder`.

### PDF / Print

- `barryvdh/laravel-dompdf` for PDF generation
- Views: `resources/views/ventas/pdf.blade.php` (A4), `resources/views/ventas/ticket.blade.php` (72mm thermal)
- `VentaController::imprimir()` renders the selected template as HTML and injects `window.print()` + `onafterprint` redirect for browser-native printing

---

## Full Route Map

All routes are under `middleware(['auth', 'verified'])`.

| Route | Component / Controller | Name |
|-------|----------------------|------|
| `GET /dashboard` | `Dashboard` | `dashboard` |
| **Ventas** | | |
| `GET /ventas` | `Ventas\Index` | `ventas` |
| `GET /ventas/create` | `Ventas\Form` | `ventas.create` |
| `GET /ventas/{venta}` | `Ventas\Form` (readOnly) | `ventas.show` |
| `GET /ventas/{venta}/pdf` | `VentaController::generarPdf()` | `ventas.pdf` |
| `GET /ventas/{venta}/ticket` | `VentaController::generarTicket()` | `ventas.ticket` |
| `GET /ventas/{venta}/imprimir` | `VentaController::imprimir()` | `ventas.imprimir` |
| **Clientes** | | |
| `GET /clientes` | `Clientes\Index` | `clientes` |
| `GET /clientes/create` | `Clientes\Form` | `clientes.create` |
| `GET /clientes/{cliente}/edit` | `Clientes\Form` | `clientes.edit` |
| **Recibos** | | |
| `GET /recibos` | `Recibos\Index` | `recibos` |
| `GET /recibos/create` | `Recibos\Form` | `recibos.create` |
| `GET /recibos/{recibo}` | `Recibos\Form` (readOnly) | `recibos.show` |
| **Compras** | | |
| `GET /compras` | `Compras\Index` | `compras` |
| `GET /compras/create` | `Compras\Form` | `compras.create` |
| `GET /compras/{compra}` | `Compras\Form` (readOnly) | `compras.show` |
| **Proveedores** | | |
| `GET /proveedores` | `Proveedores\Index` | `proveedores` |
| `GET /proveedores/create` | `Proveedores\Form` | `proveedores.create` |
| `GET /proveedores/{proveedor}/edit` | `Proveedores\Form` | `proveedores.edit` |
| **Órdenes de Pago** | | |
| `GET /ordenes-pago` | `OrdenPago\Index` | `ordenes-pago` |
| `GET /ordenes-pago/create` | `OrdenPago\Form` | `ordenes-pago.create` |
| `GET /ordenes-pago/{ordenPago}` | `OrdenPago\Form` (readOnly) | `ordenes-pago.show` |
| **Artículos** | | |
| `GET /articulos` | `Articulos\Index` | `articulos` |
| `GET /articulos/create` | `Articulos\Form` | `articulos.create` |
| `GET /articulos/{articulo}/edit` | `Articulos\Form` | `articulos.edit` |
| `GET /rubros` | `Rubros\Index` | `rubros` |
| `GET /marcas` | `Marcas\Index` | `marcas` |
| **Listas de Precios** | | |
| `GET /listas-precios` | `Listas\Index` | `listas-precios` |
| `GET /listas-precios/create` | `Listas\Form` | `listas-precios.create` |
| `GET /listas-precios/{lista}/edit` | `Listas\Form` | `listas-precios.edit` |
| `GET /listas-precios/{lista}/precios` | `Listas\Precios` | `listas-precios.precios` |
| `GET /listas-precios/{lista}/exportar-precios` | `ListasController::exportarPrecios()` | `listas-precios.exportar` |
| **Caja y Finanzas** | | |
| `GET /cajas` | `Cajas\Index` | `cajas` |
| `GET /cc-clientes` | `CcClientes\Index` | `cc-clientes` |
| `GET /cc-proveedores` | `CcProveedores\Index` | `cc-proveedores` |
| **Producción** | | |
| `GET /produccion` | `Produccion\Index` | `produccion` |
| `GET /produccion/{produccion}` | `Produccion\Detalle` | `produccion.detalle` |
| `GET /produccion/{produccion}/pdf` | `ProduccionController::pdf()` | `produccion.pdf` |
| `GET /remitos` | `Remitos\Index` | `remitos` |
| `GET /pesadas` | `Pesadas\Index` | `pesadas` |
| `GET /descartes` | `Descartes\Index` | `descartes` |
| `GET /empleados` | `Empleados\Index` | `empleados` |
| **Administración** | | |
| `GET /usuarios` | `Usuarios\Index` | `usuarios` |
| `GET /roles` | `Roles\Index` | `roles` |
| `GET /config` | `Config\Index` | `config` |
| `GET /auditorias` | `Auditorias\Index` | `auditorias` |
| `GET /ayuda` | `Ayuda\Index` | `ayuda` |

---

## Pricing Engine (implemented May 2026)

### Overview

Multi-list pricing system with per-client overrides and tiered quantities (tramos).

### Key files

| File | Purpose |
|------|---------|
| `app/Services/Pricing/PriceEngine.php` | Main engine — singleton via AppServiceProvider |
| `app/Services/Pricing/CotizacionRequest.php` | Input DTO (incluye `cantidadTramo` para tramos grupales) |
| `app/Services/Pricing/CotizacionResultado.php` | Output DTO with full audit trail |
| `app/Services/Pricing/PasoDesglose.php` | Per-step audit entry |
| `app/Support/Pricing.php` | Static helper: `Pricing::final($neto, $iva)` / `Pricing::neto($final, $iva)` |
| `app/Models/ListaPrecio.php` | Price list model (`es_default`, `recargo_no_efectivo`, `descuento_efectivo`) |
| `app/Models/ArticuloPrecio.php` | Tiered prices per article+list (`cantidad_desde`, `cantidad_hasta`, `precio`) |
| `app/Models/ClientePrecio.php` | Per-client overrides (price, bonification, or list-by-rubro) |

### `App\Support\Pricing` helper

```php
// Neto → Final (con IVA)
Pricing::final(float $neto, float $iva): float   // round($neto * (1 + $iva/100), 2)

// Final → Neto (sin IVA)
Pricing::neto(float $final, float $iva): float   // round($final / (1 + $iva/100), 2)
```

Used in: `Articulos\Form`, `Listas\Precios`, `Clientes\Form` for display/edit conversion.  
**DB always stores NET prices. This helper converts for presentation only.**

### Price resolution pipeline

1. Load client overrides (`precio_articulo` → `bonif_articulo` → `lista_rubro`)
2. Resolve list: **`forzarListaId`** (operator override) → client rubro override → client's assigned list → system default
3. Find matching tramo — uses `cantidadTramo` (group total) if provided, otherwise item's own `cantidad`
4. Apply bonifications and payment surcharge
5. Add IVA (stored prices are **net**; engine adds IVA at close)

### Group tier pricing (implemented May 2026)

When multiple articles of the same **rubro + unidad_medida** are in the cart, their quantities are summed to determine the applicable tier for all of them. Each item's subtotal is still calculated from its own `cantidad`.

**Example:** POL-BN-CJ (4 cajones) + POL-CR-CJ (3 cajones) → group total = 7 cajones → both use the 5+ tier price.

**Grouping key:** `rubro_id + unidad_medida` (separates kg from cajones even within the same rubro).

**`CotizacionRequest` field:** `cantidadTramo` — if > `cantidad`, PriceEngine adds a `tramo_grupo` step to the desglose audit trail.

**`CotizacionRequest` field:** `forzarListaId` — if set, bypasses all list resolution (rubro override, client list, default) and uses this list directly. Bonifications still apply.

**`Ventas\Form` helpers:**
- `calcularCantidadGrupo(int $rubroId, string $unidad): float` — sums quantities across all items with same key
- `recotizarGrupo(int $rubroId, string $unidad): void` — re-prices all non-manual items in the group

All item events (article selection, quantity change, item removal, client change) trigger group re-pricing automatically.

### Conventions

- **Prices in DB are NET** (without IVA). Engine adds IVA at the end.
- IVA rates per article rubro: Pollo/Cerdo → 10.5%, rest → 21%
- Excel prices from client were **final (with IVA)** — the seeder divides by `(1 + iva/100)` before inserting
- Tramos for by-kg products use kg as unit; tramos for by-box products use box count

### Livewire Ventas\Form key methods

- `seleccionarArticulo(int $index, int $articuloId, ?float $cantidad)` — loads article (including `rubro_id`), detects duplicates (sums qty), calls `recotizarGrupo()`
- `actualizarCantidad(int $index, ?float $cantidad, bool $forzarRecotizar)` — re-prices group on qty change
- `cotizarItem(int $index)` — calls PriceEngine with `cantidadTramo` + `forzarListaId` from component state
- `calcularCantidadGrupo(int $rubroId, string $unidad): float` — sums quantities for group tier lookup
- `recotizarGrupo(int $rubroId, string $unidad): void` — re-prices all non-manual items in the group
- `marcarPrecioManual(int $index, float $precio)` — flags manual override, skips re-pricing
- `restaurarPrecio(int $index)` — clears manual flag, re-prices via engine
- `cambiarLista(int $listaId)` — sets `$listaIdForzada`, `$listaModificada = true`, re-prices all items; role-gated (super-admin, admin)
- `restaurarLista()` — resets `$listaIdForzada` to client's assigned list (or system default), `$listaModificada = false`
- `registrarVenta(array $payload)` — validates, re-prices all items with group totals + forced list (backend authority), persists
- `updatedClienteId()` — resets `$listaIdForzada` to new client's list, re-prices all non-manual items
- `addItem()` / `removeItem(int $index)` — wire-side array management; `removeItem` re-cotizes remaining group members (Alpine must await these before `syncItems()`)

### Ventas\Form state structure

```php
// Item array structure (stored in $this->items[])
[
    'articulo_id'    => int,
    'rubro_id'       => ?int,      // clave de agrupación para tramos grupales
    'descripcion'    => string,
    'tipo_venta'     => 'CAJA'|'PESO',
    'unidad_medida'  => string,    // segunda clave de agrupación (e.g. 'cajón', 'kg')
    'cantidad'       => float,
    'precio'         => float,     // precio neto en DB
    'alicuota_iva'   => float,     // 0, 10.5 o 21
    'descuento'      => float,     // %
    'subtotal'       => float,
    'precio_manual'  => bool,      // true = no re-cotizar
]

// Selector de lista (encabezado del formulario)
public ?int  $listaIdForzada  = null;  // null → motor resuelve; valor → lista forzada por operador
public bool  $listaModificada = false; // badge visual "Cambiada"
// Inicializado en mount() a la lista default; reset en updatedClienteId() a la lista del cliente.

// clienteData structure
[
    'razon_social'    => string,
    'percepcion_iva'  => bool,
    'porcentaje_iibb' => float,    // ya en %, ej. 1.5
    'es_mayorista'    => bool,
    'tipoiva_codigo'  => string,   // '01'=RI, '05'=CF, '99'=default
]
```

### Selector de lista en el formulario de venta (implemented May 2026)

Permite al operador usar una lista distinta a la asignada al cliente en una venta puntual.

**UX:** debajo del grid de campos del comprobante, siempre visible en modo edición.

| Rol | Comportamiento |
|-----|---------------|
| `super-admin`, `admin` | Selector `<select>` editable; badge "Cambiada" + botón "Restaurar" si fue modificada |
| Cualquier otro rol | Texto de solo lectura + ícono de candado |

**Comportamiento:**
- En `mount()`: inicializa a la lista default del sistema
- Al seleccionar cliente (`updatedClienteId()`): auto-resetea a la lista del cliente (sin badge "Cambiada")
- `cambiarLista(int $listaId)`: fuerza la lista, re-cotiza todos los items no-manuales
- `restaurarLista()`: vuelve a la lista natural del cliente / default; limpia el badge
- La lista forzada se pasa como `forzarListaId` en cada `CotizacionRequest` (tanto en `cotizarItem()` como en la re-validación de `registrarVenta()`)

**Futura extensión de roles:** agregar el permiso en `/roles` (Spatie Permission) — los métodos `cambiarLista()` y `restaurarLista()` hacen `auth()->user()->hasRole([...])`.

### Caja requirement

`Ventas\Form` requiere una **Caja abierta** del usuario autenticado. Si no hay caja, `$sinCaja = true` y el formulario queda en modo lectura (no permite registrar). Abrir caja desde `/cajas`.

---

## Listas de Precios ABM (implemented May 2026)

Routes:
```
GET /listas-precios                           → Listas\Index
GET /listas-precios/create                    → Listas\Form
GET /listas-precios/{lista}/edit              → Listas\Form
GET /listas-precios/{lista}/precios           → Listas\Precios   ← tramo grid per list
GET /listas-precios/{lista}/exportar-precios  → ListasController::exportarPrecios()
```

`Listas\Precios` uses `public int $listaId` + `#[Computed] lista()` (NOT `public ListaPrecio $lista` — Livewire can't serialize models).

### Herramientas de edición masiva de precios (implemented May 2026)

Tres herramientas disponibles desde la barra de acciones en `/listas-precios/{lista}/precios`:

#### 1. Ajuste % bulk
- Sube o baja todos los precios de la lista en un porcentaje dado
- Filtro opcional por rubro (solo afecta artículos del rubro seleccionado)
- Livewire: `aplicarAjuste()` — aplica factor `(1 ± pct/100)` a todos los tramos via `ArticuloPrecio::where('lista_precio_id', ...)->get()`
- Requiere confirmación (`wire:confirm`) antes de ejecutar

#### 2. Exportar / Importar Excel (round-trip)

**Export** (`app/Exports/ListaPreciosExport.php`):
- `GET /listas-precios/{lista}/exportar-precios?verNetos=0&rubro=&search=`
- Genera `.xlsx` con 9 columnas: ID_TRAMO (col A, gris), Código, Descripción, Rubro, Cant.Desde, Cant.Hasta, **Precio editable** (col G, fondo amarillo `FFF3CD`), IVA%, Precio referencia
- Respeta filtros activos (`verNetos`, `rubro`, `search`) al momento de exportar
- Implementa `FromCollection`, `WithHeadings`, `WithStyles`, `WithColumnWidths`

**Import** (`app/Imports/ListaPreciosImport.php`):
- Lee col[0]=`ID_TRAMO`, col[6]=precio editable; salta fila 0 (encabezado)
- Parámetro `$modo`: `'final'` (convierte a neto via `Pricing::neto()`) o `'neto'` (guarda directo)
- Valida que cada `ID_TRAMO` pertenezca a `$listaId` (seguridad)
- Expone `$actualizados`, `$omitidos`, `$errores[]` tras ejecutar
- Livewire: `importarExcel()` usa `WithFileUploads`, llama `Excel::import(new ListaPreciosImport(...), $file->path())`

**Controller** (`app/Http/Controllers/ListasController.php`):
- `exportarPrecios(int $lista)` — lee `verNetos`, `rubro`, `search` del request, devuelve `Excel::download()`

#### 3. Grilla editable inline
- Activa con `activarModoGrilla()`: carga todos los tramos en `$grillaPrecios[tramoId => precioDisplay]`
- En modo grilla: paginación desactivada (usa `->get()` en lugar de `->paginate(25)`)
- Cada celda de tramo muestra un `<input wire:model="grillaPrecios.{tramoId}">` con fondo amarillo
- `guardarGrilla()`: itera `$grillaPrecios`, convierte display→neto según `$verNetos`, actualiza en batch
- `desactivarModoGrilla()`: limpia `$grillaPrecios` y `$grillaIvas` sin guardar
- Cambiar `toggleVerNetos()` mientras grilla activa recalcula todos los inputs via `cargarGrilla()`

---

## Client Price Overrides

`cliente_precios` table with `tipo` column:
- `precio_articulo` — fixed price for a specific article
- `bonif_articulo` — % discount for a specific article
- `lista_rubro` — use a different list for all articles of a rubro

Managed from `Clientes\Form` via `public array $overrides`. Incomplete overrides are silently discarded in `normalizarOverride()`.

---

## Articulos\Form — Tramos

The article form (`Articulos\Form`) only edits tramos for the **default list** (MINORISTA). It filters by `lista_precio_id = listaDefault->id` on load and only deletes/recreates those tramos on save. Other lists' tramos are untouched. Use `/listas-precios/{lista}/precios` to manage other lists.

**`#[Computed] listaDefault()`** resolves: `es_default = true`, fallback to first active list by `orden`.

**`$verNetosTramos`** (bool, default `false`): toggle in Articulos\Form to show/edit tramo prices as NET or FINAL. The `$tiers[*]['precio']` array holds whatever the user is viewing; conversion to neto happens in `save()`.

---

## Sales Form Pricing Display Toggle

**Feature:** Users can view/edit NET prices (for accountants) or FINAL (with VAT) prices (default).

**Implementation:**
- Livewire property: `public bool $verNetos = false`
- Alpine toggle: `wire:click="toggleVerNetos"`
- Per-item display state: `item._precioDisplay` (local Alpine state, not persisted)
- Conversion on toggle: `toggleVerNetos()` recomputes `_precioDisplay` for all items using `Pricing::final()` / `Pricing::neto()`

**On save:** display value converted back to neto via `Pricing::neto($precioDisplay, $iva)` before persisting.

---

## Ventas Form Performance — Alpine/Livewire patterns

**Problem (solved):** Each price input using `x-effect` created/destroyed Alpine reactivity on every array replacement → slowness.

**Solution:**
1. Use `x-model.number` for price inputs (Alpine's optimized binding)
2. Store display value in `item._precioDisplay` (separate from stored neto)
3. Use `@computed` for read-only getters

**Array replacement pattern:**
```php
// ❌ Wrong: mutates in-place, Alpine doesn't re-initialize _precioDisplay
Object.assign($items[0], {precio: 50});

// ✅ Correct: replace full array to trigger Alpine re-initialization
$this->items = array_map(fn($item) => [
    ...$item,
    '_precioDisplay' => Pricing::final($item['precio_neto'], $item['iva'])
], $merged);
```

**Alpine addItem/removeItem — MUST be async:**
```js
async addItem() {
    await this.$wire.addItem();   // wire-side first
    this.syncItems();             // then sync Alpine
},
async removeItem(index) {
    if (this.items.length > 1) {
        await this.$wire.removeItem(index);
        this.syncItems();
    }
},
async addItemAndFocus() {
    await this.addItem();
    const newIndex = this.items.length - 1;
    this.$nextTick(() => {
        const el = document.querySelector(`[data-row="${newIndex}"][data-field="articulo"]`);
        if (el) el.focus();
    });
},
```

---

## Rounding & Calculation Precision

**Rule:** Always compute subtotals from the displayed final price, not by re-converting from neto.

```
✅ Correct: item._precioDisplay × cantidad × (1 - descuento/100)
❌ Wrong:   (item.precio_neto × (1 + iva/100)) × cantidad × (1 - descuento/100)
```

**Totals panel:**
- `iva21Total` = sum of (final_subtotal - neto_subtotal) for 21% items
- `iva105Total` = sum of (final_subtotal - neto_subtotal) for 10.5% items
- `totalVenta` = neto_subtotal + iva21_total + iva105_total

---

## VentaController

`app/Http/Controllers/VentaController.php`

| Method | Route | Description |
|--------|-------|-------------|
| `imprimir(Venta $venta)` | `ventas.imprimir` | Renders ticket or PDF template as HTML, injects `window.print()` + `onafterprint` redirect to `/ventas`. Format read from `impresion_formato` variable. 30s fallback redirect. |
| `generarPdf(Venta $venta)` | `ventas.pdf` | DomPDF stream, A4 paper |
| `generarTicket(Venta $venta)` | `ventas.ticket` | DomPDF stream, dynamic 72mm height (`520 + detalles×30 + pagos×15`, max 1400pt) |
| `calcularSaldoAnterior(Venta)` | private | Looks up `CcCliente` movement for the sale; falls back to last CC balance before sale date |
| `nombreArchivo(Venta)` | private | Returns filename: `Factura-B-0001-00000001.pdf` (with CAE) or `Recibo-00000001.pdf` |

**`imprimir()` JS injection:**
```js
function irAVentas() { window.location.href = '{{ route("ventas") }}'; }
window.onafterprint = irAVentas;
window.onload = function () {
    window.print();
    setTimeout(irAVentas, 30000);  // fallback if onafterprint doesn't fire
};
```

---

## Company Configuration (`/config`)

Route: `GET /config → Config\Index`  
Component: `app/Livewire/Config/Index.php` (uses `WithFileUploads`)

**All settings stored in `variable_entorno` table via `VariableEntorno::set($key, $value)`.**

### VariableEntorno model

```php
VariableEntorno::get(string $clave, mixed $default = null): mixed
// Auto-casts by $tipo column: 'int', 'float', 'bool', default string

VariableEntorno::set(string $clave, mixed $valor): void
// updateOrCreate by clave; always stores as string
```

### All configuration keys

| Key | Type | Default | Description |
|-----|------|---------|-------------|
| `empresa_nombre` | string | — | Trade name |
| `empresa_razon_social` | string | — | Legal name |
| `empresa_cuit` | string | — | CUIT (format: 20-12345678-9) |
| `empresa_domicilio` | string | — | Address |
| `empresa_localidad` | string | — | City |
| `empresa_telefono` | string | — | Phone |
| `empresa_email` | string | — | Email |
| `empresa_logo_url` | string | — | `/storage/logos/filename.png` or external URL |
| `punto_venta` | string | `0001` | AFIP sales point |
| `iibb_tipo` | string | `multilateral` | `multilateral` / `local` / `directo` |
| `iibb_numero` | string | — | IIBB registration number |
| `stock_negativo` | bool | `0` | Allow negative stock |
| `ajuste_automatico` | bool | `1` | Auto-update cost price on purchase |
| `pide_vendedor` | bool | `1` | Require seller selection on sales |
| `listas_precio` | int | `3` | Number of price lists (1–6) — **actualizar a 5 en `/config`** para mostrar todas las listas |
| `impresion_formato` | string | `ticket` | `ticket` / `pdf` |
| `impresion_auto_venta` | bool | `0` | Auto-print on sale register |
| `impresora_nombre` | string | — | Reference only (browsers can't select printers) |

### Logo Upload

```php
// Storage path: storage/app/public/logos/
// Public URL:   /storage/logos/filename.ext
// Requires:     php artisan storage:link

// Validation: nullable|image|mimes:png,jpg,jpeg,gif,webp,svg|max:2048
// removeLogo(): deletes file from disk, clears empresa_logo_url in DB
```

---

## Print Configuration

When `impresion_auto_venta = 1`, registering a sale redirects to `/ventas/{id}/imprimir` (full page load, **no SPA** — `navigate: false`) which auto-fires `window.print()` and redirects back to `/ventas` on `onafterprint`.

Browsers cannot enumerate installed printers via JS — user must set the target printer as OS default (Windows: Panel de control → Dispositivos e impresoras).

---

## Seeders (run order matters)

| Seeder | When | Notes |
|--------|------|-------|
| `RolesAndPermissionsSeeder` | `migrate:fresh --seed` | Always first |
| `ListaPrecioSeeder` | `migrate:fresh --seed` | Creates 5 lists: MINORISTA (default), MAYORISTA Retiro Local, MAY-MEDIANO, MAY-GRANDE, REPARTO |
| `DatosInicialSeeder` | `migrate:fresh --seed` | TipoIva, AFIP docs, Consumidor Final client |
| `PreciosFrangoExcelSeeder` | Manual after fresh seed | 90 articles — MIN: 134 tramos \| MAY-RETIRO: 172 \| MAY-MED: 52 \| MAY-GDE: 52 \| REPARTO: 172 |
| `ClientesAvisurSeeder` | Manual / production-safe | 32 clients from Excel, idempotent by razon_social |

**In production** (data already exists): only run `ClientesAvisurSeeder` (or `PreciosFrangoExcelSeeder`) — never `migrate:fresh`.

### PreciosFrangoExcelSeeder conventions

- Source file: `H:\Desarrollo\Documentacion\Avisur\LISTA DE PRECIOS SISTEMA NUEVOO.xlsx` (5 sheets, one per list)
- All Excel prices are **FINAL (with IVA)**; seeder converts: `round($final / (1 + $iva/100), 4)`
- IVA rates: Pollo/Cerdo → 10.5%, **HUEVOS → 21%, EMBUTIDOS → 21%**, rest → 21%
- Closure used: `$finalAneto = fn(float $final, float $iva) => round($final / (1 + $iva / 100), 4)`
- Each article row contains 5 tramo arrays (one per list); deletion covers all 5 lists before re-insert

### ClientesAvisurSeeder

- 32 clients, all `Consumidor Final` (TipoIva `codigo_afip = '05'`)
- Idempotent via `firstOrCreate(['razon_social' => $name])`
- Fields: `razon_social`, `domicilio`, `localidad`, `provincia='Buenos Aires'`, `celular`, `observacion`, `tipoiva_id`, `estado='A'`, `saldo=0`
- Safe for production — does NOT wipe existing data

---

## Keyboard Shortcuts

| Screen | Key | Action |
|--------|-----|--------|
| `/ventas` (index) | **F2** | New sale (clicks `#btn-nueva-venta` with `wire:navigate`) |
| `/ventas/create` | **F10** | Register sale (calls Alpine `guardar($wire)`) |

Implementation notes:
- F2: `x-on:keydown.window` on root div, `document.getElementById('btn-nueva-venta').click()` — reuses `wire:navigate` link directly
- F10: `x-on:keydown.window` conditional on `!$readOnly && !$sinCaja`

---

## TipoIva codes (sistema AFIP)

| codigo_afip | Description |
|-------------|-------------|
| `01` | Responsable Inscripto → letra A, IVA discriminado |
| `05` | Consumidor Final → letra B |
| `06` | Exento |
| `99` | Default/unknown (treated as Consumidor Final) |

---

## Test Suite

```
tests/Unit/Pricing/PriceEngineTest.php          — 15 unit tests
tests/Feature/Ventas/FormPriceEngineTest.php    — 7 feature tests
tests/Feature/Listas/ListasCrudTest.php         — 9 feature tests
tests/Feature/Clientes/ClienteOverridesTest.php — 6 feature tests
```

All 37 tests pass. Tests use `RefreshDatabase` against MySQL (port 3307) + `firstOrCreate` for seed rows inserted by migrations (e.g., MINORISTA list from migration `2026_05_08_000001`).

---

## Key Migrations (pricing system, May 2026)

| File | What it does |
|------|-------------|
| `2026_05_08_000001_create_listas_precios_table.php` | Creates `listas_precios`, inserts MINORISTA default row |
| `2026_05_08_000002_add_lista_precio_id_to_articulo_precios_table.php` | Adds FK + backfill + `unidad_tramo` |
| `2026_05_08_000003_add_lista_precio_id_to_cliente_precios_table.php` | Nullable FK for rubro-level overrides |
| `2026_05_08_000004_change_clientes_lista_precio_to_lista_precio_id.php` | Replaces int `lista_precio` with FK |
| `2026_05_08_000005_add_pricing_metadata_to_det_ventas_table.php` | Adds `lista_precio_id`, `tramo_aplicado_id`, `regla_aplicada`, `precio_motor`, `precio_manual` |

---

## Venta Model — Key fields

```php
// ventas table key columns
'cliente_id'         // nullable FK → clientes
'caja_id'            // FK → cajas (required to register)
'nro_venta'          // sequential internal number
'neto_21'            // taxable base at 21%
'neto_10_5'          // taxable base at 10.5%
'monto_iva21'        // IVA amount at 21%
'monto_iva10_5'      // IVA amount at 10.5%
'total'              // grand total
'saldo'              // unpaid balance
'percepcion'         // IIBB perception
'percepiva21/10_5'   // IVA perception (21% / 10.5%)
'cae'                // AFIP CAE code (nullable, manual invoices have null)
'tipo_comprobante'   // 'factura' | 'nota_credito' | 'nota_debito'
'letra_comprobante'  // 'A' | 'B' | 'C'
'estado'             // 'P'=Pendiente, 'C'=Cobrada, 'A'=Anulada
// Soft deletes: SoftDeletes trait
// Auditing: implements Auditable (owen-it)
```

---

## Pending / Next Phases

- **Phase 6**: Special rules + full auditing — delivery surcharge, cash discount on total, enable Auditing on pricing models
- **Config listas_precio**: Update to `5` at `/config` so all 5 lists appear in UI dropdowns (currently defaults to `3`)
- **DB test isolation**: Consider `.env.testing` with a separate MySQL DB to prevent RefreshDatabase from wiping development data
- **Pendiente menor**: When user types qty in an empty row *before* selecting article, Alpine qty isn't passed to `seleccionarArticulo`. Workaround: always select article first, then adjust qty.
