# CLAUDE.md — CentroGoma ERP

Guía para Claude Code al trabajar en este repositorio.

---

## Contexto del proyecto

**CentroGoma** es un ERP web desarrollado en Laravel 12 para reemplazar un sistema legacy escrito en **Delphi XE5**.  
La app es una **refactorización** — se tomó como base la app `Avicola` (desarrollada en paralelo para otro cliente), se eliminaron todos los módulos específicos de ese negocio (Produccion, Pesadas, Descartes, SubArticulos) y se adaptó para CentroGoma.

**Stack:** Laravel 12 · Livewire 3 (class-based) · Alpine.js · Tailwind CSS · MySQL · DomPDF · Spatie Permission · owen-it/laravel-auditing

| | |
|---|---|
| **URL local** | `http://centrogoma.test` |
| **Admin** | `rubendario.bravo@gmail.com` / `12345678` (role: `super-admin`) |
| **BD nueva** | `centrogoma` — MySQL puerto **3307** |
| **BD legacy** | `centro_goma` — MySQL puerto **3306** (read-only, Delphi XE5) |
| **Sistema legacy** | `H:\Desarrollo\centrogoma24` (Delphi XE5, solo lectura) |

---

## Comandos de desarrollo

```bash
# Fresh DB + seeders base (borra todo)
php artisan migrate:fresh --seed

# Recrear usuario admin (luego de RefreshDatabase)
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

# Symlink de storage (una vez por entorno)
php artisan storage:link

# Correr tests (84 tests, 267 assertions — todos pasan)
php artisan test

# Filtrar suites específicas
php artisan test --filter="Pricing|Listas|Clientes|FormPriceEngine"
```

> ⚠️ **Los tests usan `RefreshDatabase` contra la BD `centrogoma_test`** (puerto 3307), definida en
> `phpunit.xml` (sus `<env>` pisan al `.env`). No apuntarla nunca a `centrogoma` (borra los datos
> importados) ni a `avicola` (BD de la otra app, a la que apuntaba originalmente).

---

## Base de datos legacy — conexión

Configurada en `config/database.php` como `centro_goma_legacy`. Variables en `.env`:

```env
LEGACY_DB_HOST=127.0.0.1
LEGACY_DB_PORT=3306        # Puerto estándar MySQL — instancia distinta de la nueva
LEGACY_DB_DATABASE=centro_goma
LEGACY_DB_USERNAME=ruben
LEGACY_DB_PASSWORD=REDACTED
```

**Uso:**
```php
// Leer de la BD legacy (solo lectura)
DB::connection('centro_goma_legacy')->table('cliente')->get();

// En un modelo dedicado
class LegacyCliente extends Model {
    protected $connection = 'centro_goma_legacy';
    protected $table = 'cliente';
    public $timestamps = false;
}
```

---

## Plan de migración de datos legacy

Tablas relevantes de `centro_goma` y su mapeo a la BD nueva:

| Tabla legacy | Registros | Tabla nueva | Estado |
|---|---|---|---|
| `categoria` | 201 | `rubros` | ✅ Importado |
| `producto` | 7.187 | `articulos` + `articulo_precios` | ✅ Importado |
| `cliente` | 6.647 | `clientes` | ✅ Importado |
| `proveedor` | 251 | `proveedores` | ✅ Importado |
| `cccliente` | ~500K | `cc_clientes` (saldo inicial por cliente) | ⏳ Pendiente |
| `ccproveedor` | — | `cc_proveedores` (saldo inicial por proveedor) | ⏳ Pendiente |

### Comando `centrogoma:importar`

Implementado en `app/Console/Commands/ImportarLegacy.php`.

```bash
php artisan centrogoma:importar [tabla] [--dry-run] [--force]
# tabla: categorias | productos | clientes | proveedores  (omitir = todas, en orden)
# --dry-run : muestra qué se haría sin escribir nada
# --force   : sobrescribe registros ya importados (update por legacy_id)
```

**Idempotencia:** cada tabla nueva tiene una columna `legacy_id` (UNSIGNED INT, UNIQUE, nullable)
agregada por la migración `2026_05_31_000001_add_legacy_id_to_main_tables`. El comando busca por
`legacy_id`; sin `--force` salta los ya importados, con `--force` los actualiza.

**Mapeos internos del comando:**
- `tipoivaMap` — `id_tipoiva` legacy → `tipoiva.id` nuevo, resuelto por `codigo_afip` (robusto ante IDs distintos)
- `rubroMap` — `id_categoria` legacy → `rubros.id` nuevo (vía `legacy_id`)
- `localidadMap` — `id_localidad` legacy → texto de localidad (tabla `localidades`)

**Criterios aplicados:**
- Los precios de `producto` en Delphi son **FINALES (con IVA)** → se convierten a neto: `neto = venta / (1 + iva/100)` (4 decimales). Se crea un tramo único en la lista **default** (Minorista) en `articulo_precios`.
- IVA del producto se normaliza a `{0, 10.5, 21}` (default 21 si es inválido).
- Código de artículo vacío → `LEG-{id_producto}`.
- **CUIT inválido** (clientes y proveedores) → se guarda `null` cuando: largo < 7 ó > 13, o todos los caracteres iguales (`00000…`, `IIIII…`). Regex: `/^(.)\1+$/`.
- **CUIT duplicado** en proveedores (error de carga legacy: mismo CUIT en varias razones sociales) → se captura `QueryException 1062` y se reintenta guardando con CUIT `null` (warning, no error).

### ⚠ Charset legacy — caracteres corruptos en origen

La BD `centro_goma` declara `DEFAULT CHARSET=latin1`, pero **los caracteres especiales ya están
corruptos en disco**: la Ñ/tildes fueron guardadas por el conector Delphi/MySQL antiguo como el byte
`0x3F` (`?` ASCII). Ej: `CAÑOS` está almacenado como `CA?OS` (verificado con `HEX(descripcion)`).

No hay charset de conexión que recupere un dato que nunca se guardó. La conexión `centro_goma_legacy`
usa `charset: 'utf8'` (no `utf8mb4`) en `config/database.php`, que es lo correcto para latin1, pero
los `?` ya presentes no se pueden revertir automáticamente. **Se importan tal cual; corregir
manualmente** desde `/rubros`, `/articulos`, `/clientes` cuando se detecten.

---

## Arquitectura

### Frontend

Toda la UI es **Livewire 3 class-based** — sin SPA separada ni REST API.

- `app/Livewire/<Dominio>/` — clases PHP de componentes
- `resources/views/livewire/<dominio>/` — templates Blade

Patrón estándar por dominio:
- `Index` — listado con filtros + paginación
- `Form` — crear + editar (recibe `?int $id` en `mount()`)

**Alpine.js** maneja estado reactivo local (filas de items en Ventas, dropdowns, etc.).

**Regla crítica Alpine↔Livewire:** `addItem()` / `removeItem()` DEBEN llamar `await this.$wire.addItem()` / `await this.$wire.removeItem(index)` **antes** de `syncItems()`, si no el array `$items` de Livewire se desincroniza y pierde filas silenciosamente.

### Base de datos

**MySQL** en todos los entornos. Migraciones en `database/migrations/`.  
Variables de configuración en tabla `variable_entorno`, leídas vía `VariableEntorno::get($key, $default)`.

### Permisos

`spatie/laravel-permission` — roles/permisos sembrados por `RolesAndPermissionsSeeder`.

### PDF / Impresión

- `barryvdh/laravel-dompdf` para generación de PDF
- Vistas: `resources/views/ventas/pdf.blade.php` (A4), `resources/views/ventas/ticket.blade.php` (ticket 72mm)
- `VentaController::imprimir()` renderiza el template como HTML e inyecta `window.print()` + redirect `onafterprint`

---

## Mapa de rutas completo

Todas las rutas bajo `middleware(['auth', 'verified'])`.

| Ruta | Componente / Controlador | Nombre |
|------|--------------------------|--------|
| `GET /dashboard` | `Dashboard` | `dashboard` |
| **Presupuestos** | | |
| `GET /presupuestos` | `Presupuestos\Index` | `presupuestos` |
| `GET /presupuestos/create` | `Presupuestos\Form` | `presupuestos.create` |
| `GET /presupuestos/{presupuesto}/edit` | `Presupuestos\Form` | `presupuestos.edit` |
| `GET /presupuestos/{presupuesto}/pdf` | `PresupuestoController::pdf()` | `presupuestos.pdf` |
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
| `GET /cajas/{caja}` | `Cajas\Show` | `cajas.show` |
| `GET /cheques` | `Cheques\Index` | `cheques` |
| `GET /cc-clientes` | `CcClientes\Index` | `cc-clientes` |
| `GET /cc-proveedores` | `CcProveedores\Index` | `cc-proveedores` |
| **Logística** | | |
| `GET /remitos` | `Remitos\Index` | `remitos` |
| `GET /empleados` | `Empleados\Index` | `empleados` |
| **Administración** | | |
| `GET /usuarios` | `Usuarios\Index` | `usuarios` |
| `GET /roles` | `Roles\Index` | `roles` |
| `GET /config` | `Config\Index` | `config` |
| `GET /auditorias` | `Auditorias\Index` | `auditorias` |
| `GET /ayuda` | `Ayuda\Index` | `ayuda` |

---

## Módulo Cheques

Modelo portado de avícola (migración `2026_07_01_000002_reconciliar_cheques_modelo_avicola`, que
descartó las tablas viejas recibido/emitido — estaban vacías). Lógica centralizada en
`App\Services\Cheque\ChequeService`.

### Tablas

| Tabla | Descripción |
|-------|-------------|
| `cheques` | `tipo` (`tercero`/`propio`), estado, banco (`bancos`), librador, `es_echeq`, `es_diferido`, `caja_id` (banco), `caja_origen_id`, FKs blandas a `recibo_id` / `venta_id` / `orden_pago_id` |
| `cheque_movimientos` | Audit trail (`ChequeMovimiento`) de cada transición |

### Estados — `Cheque::TRANSICIONES` es la fuente de verdad

| Tipo | Inicial | Transiciones |
|------|---------|--------------|
| Tercero | `cartera` | → `depositado` → `acreditado` / `rechazado`; → `aplicado` (endoso); → `vendido`; → `anulado`; `rechazado` → `cartera` |
| Propio | `emitido` | → `debitado` / `rechazado` / `anulado`; `rechazado` → `emitido` |

### Impacto en caja (ChequeService)

- `registrarRecibido()` / `registrarEmitido()` — alta + movimiento `recepcion`/`emision`. **No tocan caja.**
- `acreditar()` — egreso en `caja_origen` + ingreso en caja banco (la plata aparece recién acá).
- `debitar()` — egreso de la caja banco (cheque propio).
- `rechazar()` — revierte caja si estaba acreditado y **reabre la deuda del cliente** (CC).
- `anular()` — revierte caja si estaba acreditado/debitado.

### Integración automática (`forma_pago = 'cheque'`)

| Documento | Crea | Vínculo |
|-----------|------|---------|
| `Recibos\Form` | tercero / `cartera` | `recibo_id` |
| `Ventas\Form` (pago Alpine) | tercero / `cartera` | `venta_id` |
| `OrdenPago\Form` | propio / `emitido` (sin egreso de caja hasta el débito) | `orden_pago_id` |

Compras **no** admite cheque como forma de pago: se carga en CC y se paga con una Orden de Pago.

---

## Caja y Compras — services

- `App\Services\Caja\CajaService` — único punto de escritura del libro `movimiento_cajas`:
  `registrarMovimiento()` (asienta + incrementa contador cacheado en `cajas`), `revertirMovimientosDe()`,
  `transferir()`. Usado por Ventas, Recibos, Órdenes de Pago, Compras y ChequeService.
- `App\Services\Compra\CompraService` — `procesar()` aplica stock (+`movimientos_articulos`),
  `precio_costo` (si `ajuste_automatico`) y CC proveedor, o egreso de caja si es de contado;
  `revertir()` lo deshace (no revierte costo). `Compras\Form` registra y procesa en una transacción;
  `Compras\Index` anula con motivo (avisa si hay OPs aplicadas: lo pagado queda como crédito).
- Formas de pago: tabla `formas_pago` (`FormaPago::esCuentaCorriente()`, `esEfectivo()`, `activosParaSelect()`).

---

## Módulo Presupuestos

Implementado en mayo 2026.

| Ruta | Componente |
|------|-----------|
| `GET /presupuestos` | `Presupuestos\Index` |
| `GET /presupuestos/create` | `Presupuestos\Form` |
| `GET /presupuestos/{id}/edit` | `Presupuestos\Form` |
| `GET /presupuestos/{id}/pdf` | `PresupuestoController::pdf()` |

**Estados:** `A` = Activo, `V` = Vencido (calculado por `fecha_vencimiento`), `F` = Facturado, `N` = Anulado.

**Conversión a Venta:** botón "Convertir a Venta" en el Index (solo estados A/V).  
- Redirige a `ventas/create?presupuesto_id={id}`
- `Ventas\Form::mount()` detecta el query param y pre-carga cliente, lista e ítems
- Al registrar la venta, el presupuesto pasa automáticamente a estado `F`

El Form usa el **PriceEngine** igual que Ventas, con las mismas reglas de pricing.

---

## Motor de precios (PriceEngine)

Sistema multi-lista con overrides por cliente y precios por tramos de cantidad.

### Archivos clave

| Archivo | Propósito |
|---------|-----------|
| `app/Services/Pricing/PriceEngine.php` | Motor principal — singleton via AppServiceProvider |
| `app/Services/Pricing/CotizacionRequest.php` | DTO de entrada |
| `app/Services/Pricing/CotizacionResultado.php` | DTO de salida con audit trail |
| `app/Support/Pricing.php` | Helper estático: `Pricing::final($neto, $iva)` / `Pricing::neto($final, $iva)` |
| `app/Models/ListaPrecio.php` | Lista de precios (`es_default`, `recargo_no_efectivo`, `descuento_efectivo`) |
| `app/Models/ArticuloPrecio.php` | Precios por tramos (lista + rango de cantidad) |
| `app/Models/ClientePrecio.php` | Overrides por cliente (precio fijo, bonif, lista por rubro) |

### Helper `App\Support\Pricing`

```php
Pricing::final(float $neto, float $iva): float   // neto × (1 + iva/100), redondeado a 2 decimales
Pricing::neto(float $final, float $iva): float   // final / (1 + iva/100), redondeado a 2 decimales
```

**Los precios en BD siempre son NETOS.** El helper convierte solo para presentación.

### Pipeline de resolución de precio

1. Cargar overrides del cliente (`precio_articulo` → `bonif_articulo` → `lista_rubro`)
2. Resolver lista: `forzarListaId` (operador) → override rubro → lista del cliente → default del sistema
3. Buscar tramo: usa `cantidadTramo` (total grupal) si es mayor que `cantidad` individual
4. Aplicar bonificaciones y recargo por forma de pago
5. Sumar IVA al final

### Precios grupales por tramos

Cuando hay múltiples artículos del mismo **rubro + unidad_medida** en el carrito, sus cantidades se suman para determinar el tramo aplicable a todos.

- `calcularCantidadGrupo(rubroId, unidad)` — suma cantidades del grupo
- `recotizarGrupo(rubroId, unidad)` — re-cotiza todos los items no-manuales del grupo

### Overrides de cliente (`cliente_precios`)

| `tipo` | Efecto |
|--------|--------|
| `precio_articulo` | Precio fijo para un artículo específico |
| `bonif_articulo` | % de descuento para un artículo específico |
| `lista_rubro` | Usar otra lista para todos los artículos de un rubro |

### Selector de lista en Ventas

Permite al operador forzar una lista distinta a la del cliente para una venta puntual.

| Rol | Comportamiento |
|-----|---------------|
| `super-admin`, `admin` | `<select>` editable + badge "Cambiada" + botón Restaurar |
| Otros roles | Solo lectura + ícono candado |

---

## Ventas\Form — estructura de estado

```php
// Item (stored in $this->items[])
[
    'articulo_id'       => int,
    'rubro_id'          => ?int,      // clave agrupación tramos grupales
    'descripcion'       => string,
    'tipo_venta'        => 'CAJA'|'PESO',
    'unidad_medida'     => string,    // segunda clave agrupación
    'cantidad'          => float,
    'precio'            => float,     // neto en BD
    'alicuota_iva'      => float,     // 0, 10.5 o 21
    'descuento'         => float,     // %
    'subtotal'          => float,
    'precio_manual'     => bool,      // true = no re-cotizar
    'lista_precio_id'   => ?int,      // auditoría pricing
    'tramo_aplicado_id' => ?int,
    'regla_aplicada'    => ?string,
    'precio_motor'      => ?float,
]

// Pago Alpine (stored in this.pagos[])
{ forma_pago: string, monto: float, _autoMonto: bool,
  cheque: null | { banco, serie, nro, titular, cuit, fecha_emision, fecha_cobro } }

// Lista forzada
public ?int  $listaIdForzada  = null;   // null → motor resuelve
public bool  $listaModificada = false;  // badge "Cambiada"

// Presupuesto origen (si viene de convertirAVenta)
public ?int $presupuestoOrigenId = null;
```

---

## Patrones Alpine↔Livewire

### addItem / removeItem — DEBEN ser async

```js
async addItem() {
    await this.$wire.addItem();   // wire-side primero
    this.syncItems();             // después sync Alpine
},
async removeItem(index) {
    if (this.items.length > 1) {
        await this.$wire.removeItem(index);
        this.syncItems();
    }
},
```

### Reemplazo de array (para re-inicializar `_precioDisplay`)

```js
// ❌ Mal: Alpine no re-inicializa _precioDisplay
Object.assign(this.items[0], { precio: 50 });

// ✅ Bien: reemplazar array entero
this.items = this.items.map((item, i) => i === index ? { ...item, precio: 50, _precioDisplay: ... } : item);
```

---

## Precisión de cálculos

**Regla:** los subtotales siempre se calculan desde el precio *mostrado* (display), no reconvirtiendo desde neto.

```
✅ item._precioDisplay × cantidad × (1 - descuento/100)
❌ (item.precio_neto × (1 + iva/100)) × cantidad × (1 - descuento/100)
```

---

## VentaController

| Método | Ruta | Descripción |
|--------|------|-------------|
| `imprimir(Venta)` | `ventas.imprimir` | Renderiza template + inyecta `window.print()` y redirect `onafterprint`. 30s fallback. |
| `generarPdf(Venta)` | `ventas.pdf` | DomPDF stream, papel A4 |
| `generarTicket(Venta)` | `ventas.ticket` | DomPDF stream, 72mm dinámico |

---

## Configuración de empresa (`/config`)

Todas las variables en tabla `variable_entorno` vía `VariableEntorno::get/set`.

| Clave | Tipo | Default | Descripción |
|-------|------|---------|-------------|
| `empresa_nombre` | string | — | Nombre comercial |
| `empresa_razon_social` | string | — | Razón social legal |
| `empresa_cuit` | string | — | CUIT (formato: 20-12345678-9) |
| `empresa_domicilio` | string | — | Domicilio |
| `empresa_localidad` | string | — | Localidad |
| `empresa_telefono` | string | — | Teléfono |
| `empresa_email` | string | — | Email |
| `empresa_logo_url` | string | — | `/storage/logos/archivo.png` o URL externa |
| `punto_venta` | string | `0001` | Punto de venta AFIP |
| `iibb_tipo` | string | `multilateral` | `multilateral` / `local` / `directo` |
| `iibb_numero` | string | — | Nro. inscripción IIBB |
| `stock_negativo` | bool | `0` | Permitir stock negativo |
| `ajuste_automatico` | bool | `1` | Actualizar costo en compra |
| `pide_vendedor` | bool | `1` | Requiere vendedor en venta |
| `listas_precio` | int | `3` | Nro. de listas visibles en dropdowns (1–6) |
| `impresion_formato` | string | `ticket` | `ticket` / `pdf` |
| `impresion_auto_venta` | bool | `0` | Auto-imprimir al registrar venta |
| `impresora_nombre` | string | — | Solo referencia (browsers no pueden elegir impresora) |

---

## Seeders

| Seeder | Cuándo | Notas |
|--------|--------|-------|
| `RolesAndPermissionsSeeder` | `migrate:fresh --seed` | Siempre primero |
| `ListaPrecioSeeder` | `migrate:fresh --seed` | Crea lista default (Minorista) y otras |
| `DatosInicialSeeder` | `migrate:fresh --seed` | TipoIva, comprobantes AFIP, cliente "Consumidor Final" |

**En producción** (datos ya existentes): nunca usar `migrate:fresh`. Solo correr migraciones incrementales.

---

## Atajos de teclado

| Pantalla | Tecla | Acción |
|----------|-------|--------|
| `/ventas` (index) | **F2** | Nueva venta |
| `/ventas/create` | **F10** | Registrar venta |

---

## Códigos TipoIVA (AFIP)

| codigo_afip | Descripción |
|-------------|-------------|
| `01` | Responsable Inscripto → letra A, IVA discriminado |
| `05` | Consumidor Final → letra B |
| `06` | Exento |
| `99` | Default/desconocido (tratado como Consumidor Final) |

---

## Suite de tests

```
tests/Unit/Pricing/PriceEngineTest.php          — 15 unit tests
tests/Feature/Ventas/FormPriceEngineTest.php    — 7 feature tests
tests/Feature/Listas/ListasCrudTest.php         — 9 feature tests
tests/Feature/Clientes/ClienteOverridesTest.php — 6 feature tests
tests/Feature/Compras/CompraServiceTest.php     — 7 feature tests (CC, contado, anulación)
tests/Feature/Caja/                             — 14 tests (CajaService, transferencias, detalle de caja)
tests/Feature/ExampleTest.php                   — 1 smoke test (GET / → redirect login)
```

**Total: 84 tests, 267 assertions — todos pasan.** Tests usan `RefreshDatabase` contra `centrogoma_test` (puerto 3307).

---

## Migraciones clave

| Archivo | Qué hace |
|---------|---------|
| `2026_05_08_000001_create_listas_precios_table` | Crea `listas_precios`, inserta fila MINORISTA default |
| `2026_05_08_000002_add_lista_precio_id_to_articulo_precios_table` | FK + backfill + `unidad_tramo` |
| `2026_05_08_000003_add_lista_precio_id_to_cliente_precios_table` | FK nullable para overrides por rubro |
| `2026_05_08_000004_change_clientes_lista_precio_to_lista_precio_id` | Reemplaza int `lista_precio` con FK |
| `2026_05_08_000005_add_pricing_metadata_to_det_ventas_table` | Auditoría de pricing en det_ventas |
| `2026_05_18_000001_create_presupuestos_table` | Módulo Presupuestos |
| `2026_05_18_000002_create_det_presupuestos_table` | Detalles de presupuesto |
| `2026_05_18_000003_create_cheques_table` | Módulo Cheques |
| `2026_05_18_000004_update_cheques_add_relations` | Agrega `serie`, estado `pendiente`, FKs a docs origen |
| `2026_05_18_000005_create_movimientos_cheque_table` | Audit trail de cheques |
| `2026_05_18_000006_add_cheque_id_to_pago_ventas` | FK inversa PagoVenta → Cheque |
| `2026_05_31_000001_add_legacy_id_to_main_tables` | `legacy_id` (UNIQUE) en `rubros`, `articulos`, `clientes`, `proveedores` para importación idempotente |
| `2026_06_29_000001..6` | Libro de caja: columnas en `cajas` (tipo, contadores), `formas_pago`, `movimiento_cajas`, `transferencias_caja`, `categorias_gasto`, `gastos` |
| `2026_07_01_000001_create_bancos_table` | Catálogo de bancos |
| `2026_07_01_000002_reconciliar_cheques_modelo_avicola` | Recrea `cheques` + `cheque_movimientos` con el modelo de avícola; quita `pago_ventas.cheque_id` |
| `2026_09_29_000001_add_procesada_to_compras_table` | `forma_pago`, `procesada`, `procesada_at`, `procesada_por` en `compras` |

---

## Modelo Venta — campos clave

```php
'cliente_id'         // nullable FK → clientes
'caja_id'            // FK → cajas (requerida para registrar)
'nro_venta'          // número secuencial interno
'neto_21'            // base imponible 21%
'neto_10_5'          // base imponible 10.5%
'monto_iva21'        // monto IVA 21%
'monto_iva10_5'      // monto IVA 10.5%
'total'              // total final
'saldo'              // saldo impago
'percepcion'         // percepción IIBB
'percepiva21/10_5'   // percepción IVA
'cae'                // CAE AFIP (null = comprobante manual)
'tipo_comprobante'   // 'factura' | 'nota_credito' | 'nota_debito'
'letra_comprobante'  // 'A' | 'B' | 'C'
'estado'             // 'P'=Pendiente, 'C'=Cobrada, 'A'=Anulada
// SoftDeletes + owen-it Auditable
```

---

## Artículos\Form — Tramos

El form de artículos solo edita tramos de la **lista default**. Para otras listas usar `/listas-precios/{lista}/precios`.

`$verNetosTramos` (bool, default `false`): toggle neto/final en la grilla de tramos del form.

---

## Listas de precios — herramientas de edición masiva

Desde `/listas-precios/{lista}/precios`:

1. **Ajuste % bulk** — sube/baja todos los precios (o solo un rubro) por un porcentaje. `wire:confirm` antes de ejecutar.
2. **Exportar/Importar Excel** — export `.xlsx` con ID_TRAMO en col A y precio editable en col G (fondo amarillo). Import lee col[0]=ID_TRAMO, col[6]=precio; modo `'final'` convierte con `Pricing::neto()`.
3. **Grilla editable inline** — desactiva paginación, muestra inputs amarillos por tramo. `guardarGrilla()` guarda en batch.

---

## Pendientes / Próximas fases

### Inmediatos
- **Saldos CC legacy** — importar `cccliente` / `ccproveedor` como saldo inicial en `cc_clientes` / `cc_proveedores` (extender `centrogoma:importar`)

### Funcionalidades a portar desde `avicola` (ver `docs/ANALISIS_AVICOLA.md`)
App base `C:\laragon\www\avicola`. Módulos/servicios presentes allí y aún ausentes en CentroGoma:
- ✅ CajaService, ChequeService, CompraService (portados)
- ✅ UI de caja — `Cajas\Index` (apertura con cierre anterior, cajas general/banco, transferencias, ajustes, cierre con transferencia) + `Cajas\Show` (libro con saldo corrido en efectivo, pestañas por documento)
- **Gastos** (`/gastos`, modelos ya creados), **Bancos** (`/bancos`, tabla ya sembrada), **Anticipos**, **Estadisticas**
- **Anular venta / recibo / OP** revirtiendo caja y CC vía `CajaService::revertirMovimientosDe()`
- **Services/Afip** — facturación electrónica (Wsaa, Wsfev1, AfipService) — último

### Migración datos legacy — ✅ completado
- `categorias` (201), `productos` (7.187), `clientes` (6.647), `proveedores` (251) importados vía `centrogoma:importar`

### Backlog técnico
- **Vista readOnly con cheque vinculado** — en detalle de Recibo/OP/Venta, mostrar enlace al cheque creado y su estado actual
- **Dashboard con KPIs reales** — ventas del día, cheques por vencer, estado de caja, saldo CC
- **Fase 6 pricing** — recargo por entrega, descuento por pago en efectivo sobre el total
- **Pendiente menor Alpine** — si el usuario tipea cantidad antes de seleccionar artículo, la cantidad no pasa a `seleccionarArticulo`. Workaround: siempre seleccionar artículo primero.
