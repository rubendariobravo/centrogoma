# CLAUDE.md — CentroGoma ERP

Guía para Claude Code al trabajar en este repositorio.

> **Al retomar (o en una PC nueva): leer primero `docs/ESTADO_PROYECTO.md`** — qué está hecho, cómo armar el
> entorno de desarrollo, producción y pendientes priorizados.

---

## Contexto del proyecto

**CentroGoma** es un ERP web desarrollado en Laravel 12 para reemplazar un sistema legacy escrito en **Delphi XE5**.  
La app es una **refactorización** — se tomó como base la app `Avicola` (desarrollada en paralelo para otro cliente), se eliminaron todos los módulos específicos de ese negocio (Produccion, Pesadas, Descartes, SubArticulos) y se adaptó para CentroGoma.

**Stack:** Laravel 12 · Livewire 3 (class-based) · Alpine.js · Tailwind CSS · MySQL · DomPDF · Spatie Permission · owen-it/laravel-auditing

| | |
|---|---|
| **URL local** | `http://centrogoma.test` |
| **Producción** | `https://centrogoma.rdbti.com` — VPS Vultr `64.176.8.172` (compartido con FrangoData, frangoadmin, isdm, mascotacenter). Instalado 26/10/2026 con datos del Delphi al corte 28/05/2026 (ensayo; se reimporta en la salida real). Actualizar: `sudo bash /var/www/centrogoma/deploy/actualizar.sh`. Guía: `docs/DESPLIEGUE_VPS.md` |
| **Admin** | `rubendario.bravo@gmail.com` (role: `admin`; la contraseña ya no es la original) |
| **Usuario testing** | `testing@centrogoma.test` (role: `admin`) — credenciales en `database/seeders/UsuarioTestingSeeder.php`; recrear con `php artisan db:seed --class=UsuarioTestingSeeder` |
| **BD nueva** | `centrogoma` — MySQL local puerto **3306** (misma instancia que la legacy desde 31/10/2026) |
| **BD legacy** | `centro_goma` — MySQL local puerto **3306** (read-only, Delphi XE5; restaurada al 31/10/2026) |
| **Sistema legacy** | `C:\desarrollo\centrogoma24` (Delphi XE5, solo lectura) |

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

# Correr tests (195 tests, 851 assertions — todos pasan)
php artisan test

# Filtrar suites específicas
php artisan test --filter="Pricing|Listas|Clientes|FormPriceEngine"
```

> ⚠️ **Los tests usan `RefreshDatabase` contra la BD `centrogoma_test`**, cuyo nombre fija
> `phpunit.xml` (sus `<env>` pisan al `.env`); puerto, usuario y contraseña salen del `.env`. No apuntarla
> nunca a `centrogoma` (borra los datos importados) ni a `avicola` (BD de la otra app, a la que apuntaba originalmente).

---

## Base de datos legacy — conexión

Configurada en `config/database.php` como `centro_goma_legacy`. Variables en `.env`:

```env
LEGACY_DB_HOST=127.0.0.1
LEGACY_DB_PORT=3306        # En desarrollo, la misma instancia que la BD nueva
LEGACY_DB_DATABASE=centro_goma
LEGACY_DB_USERNAME=ruben
LEGACY_DB_PASSWORD=          # ver .env (no versionar contraseñas)
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
| `plan_cuenta` | 28 | `categorias_gasto` | ✅ Importado (24; se omiten 25–28, que no son gastos) |
| `banco` | 10 | `bancos` | — Cubierto por los 16 bancos sembrados |
| `cccliente` | 20.005 | comprobantes de saldo inicial + `cc_clientes` | ✅ `centrogoma:importar-saldos` |
| `ccproveedor` | 20.483 | comprobantes de saldo inicial + `cc_proveedores` | ✅ `centrogoma:importar-saldos` |

### Comando `centrogoma:importar`

Implementado en `app/Console/Commands/ImportarLegacy.php`.

```bash
php artisan centrogoma:importar [tabla] [--dry-run] [--force]
# tabla: categorias | productos | clientes | proveedores | plan_cuenta  (omitir = todas, en orden)
# --dry-run : muestra qué se haría sin escribir nada
# --force   : sobrescribe registros ya importados (update por legacy_id)
```

**El saldo de la ficha (clientes/proveedores) no lo toca `--force`:** lo lleva la CC. Si lo pusiera en 0, la siguiente
corrida de `importar-saldos` le restaría el saldo inicial anterior y la ficha quedaría descuadrada.

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

### Comando `centrogoma:importar-saldos` — saldos de cuenta corriente

Implementado en `app/Console/Commands/ImportarSaldosLegacy.php`.

```bash
php artisan centrogoma:importar-saldos [--corte=YYYY-MM-DD] [--dry-run] [--solo=clientes,proveedores] [--epsilon=1]
```

- **Saldo** = `SUM(egreso) − SUM(ingreso)` hasta el corte (inclusive), igual que `qSaldoCliente`/`qSaldoProveedor`
  del Delphi. Se ignoran `estado` (marca manual de "pagada") y la columna `saldo` (saldo corrido que reescribe la pantalla).
  Positivo = el cliente nos debe / le debemos al proveedor.
- **Deuda** → comprobante de saldo inicial: venta/compra con `saldo_inicial = 1`, sin renglones, pendiente. Se cobra
  con Recibo / se paga con Orden de Pago como cualquier comprobante, pero queda fuera de totales (scope `comerciales()`).
- **Saldo a favor** → solo asiento en la CC (haber) + saldo de la ficha; se lista para revisar.
- Se excluye la cuenta de mostrador (CONSUMIDOR FINAL, legacy id 1). Los movimientos con fecha posterior al corte
  (hay un pago con fecha 2109) no se incluyen y se listan.
- **Repetible:** cada corrida deshace lo que cargó la anterior y lo vuelve a cargar, salvo cuentas cuyo saldo inicial
  ya se cobró/pagó o anuló en la app (se dejan y se avisa).

Corte de prueba 29/09/2026 (BD legacy desactualizada): 42 clientes deudores ($276.502.954,10), 43 a favor
(−$1.247.548,49), 46 proveedores acreedores ($39.875.624,80), 14 a favor nuestro (−$2.474.621,44).

#### Salida a producción (con la BD legacy actualizada a esa fecha)

En el servidor (detalle en `docs/DESPLIEGUE_VPS.md` §4):

1. Apuntar `LEGACY_DB_*` del `.env` a la BD Delphi actualizada.
2. `php artisan centrogoma:importar --force` — maestros (altas nuevas + cambios).
3. `php artisan centrogoma:importar-saldos --corte=<último día operado en Delphi> --dry-run` → revisar el reporte
   (saldos a favor, fechas posteriores al corte) con administración.
4. Mismo comando sin `--dry-run`.

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

### Compatibilidad Linux (producción)

El servidor es Linux: **las mayúsculas importan** en clases (`TipoIva`, no `Tipoiva`), vistas y rutas de archivo. En Windows/Laragon un error así pasa desapercibido.

### Frontend

Toda la UI es **Livewire 3 class-based** — sin SPA separada ni REST API.

- `app/Livewire/<Dominio>/` — clases PHP de componentes
- `resources/views/livewire/<dominio>/` — templates Blade

Patrón estándar por dominio:
- `Index` — listado con filtros + paginación
- `Form` — crear + editar (recibe `?int $id` en `mount()`)

**Alpine.js** maneja estado reactivo local (filas de items en Ventas, dropdowns, etc.).

**Selección de clientes:** nunca un `<select>` con todos los clientes (~6.6K). Usar el trait
`App\Livewire\Concerns\BuscaCliente` + `<x-buscador-cliente vacio="…" error="cliente_id" />`: búsqueda server-side
por palabras en razón social/fantasía o por CUIT/DNI (máx. 15 resultados), teclado ↑↓ Enter. El campo destino es
`cliente_id` (sobreescribir `clienteCampo()` para otro, ej. `filterCliente`); al seleccionar se dispara el hook
`updated{Campo}()` del componente. Proveedores (~250) siguen con `<select>`.

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
| `GET /dashboard` | `Dashboard` (ventas día/mes, a cobrar, a pagar, cheques a 7 días, mayores deudores; sin stock) | `dashboard` |
| **Estadísticas** (permiso `ver_informes`) | | |
| `GET /estadisticas` | `Estadisticas\Index` (ventas, compras, gastos, anticipos, resultado) | `estadisticas` |
| `GET /estadisticas/clientes` | `Estadisticas\Clientes` | `estadisticas.clientes` |
| `GET /estadisticas/proveedores` | `Estadisticas\Proveedores` | `estadisticas.proveedores` |
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
| `GET /listas-precios/{lista}/imprimir` | `ListasController::imprimirPrecios()` (HTML para imprimir/guardar PDF) | `listas-precios.imprimir` |
| **Caja y Finanzas** | | |
| `GET /cajas` | `Cajas\Index` | `cajas` |
| `GET /cajas/{caja}` | `Cajas\Show` | `cajas.show` |
| `GET /gastos` | `Gastos\Index` (+ modal ABM de categorías) | `gastos` |
| `GET /bancos` | `Bancos\Index` | `bancos` |
| `GET /cheques` | `Cheques\Index` | `cheques` |
| `GET /cc-clientes` | `CcClientes\Index` | `cc-clientes` |
| `GET /cc-proveedores` | `CcProveedores\Index` | `cc-proveedores` |
| `GET /cuenta-corriente/{tipo}/{id}/resumen` | `CuentaCorrienteController::resumen()` (`?desde&hasta&formato=pdf\|xlsx`) | `cc.resumen` |
| `GET /cuenta-corriente/{tipo}/saldos` | `CuentaCorrienteController::saldos()` (`?filtro=deudores\|a_favor\|todos`) | `cc.saldos` |
| **Logística** | | |
| `GET /remitos` | `Remitos\Index` | `remitos` |
| `GET /empleados` | `Empleados\Index` | `empleados` |
| `GET /anticipos-empleados` | `Anticipos\Index` (filtro `?empleado={id}`) | `anticipos-empleados` |
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
- Formas de pago: tabla `formas_pago` (`FormaPago::esCuentaCorriente()`, `esEfectivo()`, `activosParaSelect()`); Ventas y
  Recibos validan contra ella (no hay listas fijas). **Tarjeta:** la marca (`tarjetas`, 16 del Delphi) es obligatoria y va
  en `tarjeta_id` del pago, del recibo y del movimiento de caja; `/cajas/{id}` muestra lo cobrado por medio y marca.
- **Cuenta corriente impresa:** `App\Services\CuentaCorriente\ResumenCuenta` (saldo corrido recalculado por fecha,
  pendientes, saldos) para el PDF/Excel y para el panel de `/cc-clientes` y `/cc-proveedores`.
- **Ventas en caja:** un `MovimientoCaja` por cada forma de pago usada (+ uno con `forma_pago=cuenta_corriente`
  por el saldo a crédito). Nunca un único asiento por el total: rompe el arqueo de efectivo en pagos mixtos.

## Anulaciones — `App\Services\Anulacion\AnulacionService`

Ventas, recibos y órdenes de pago se anulan desde sus listados (botón visible solo para `admin`/`super-admin`,
trait `App\Livewire\Concerns\AnulaComprobante` + componente `<x-modal-anular>`). Nada se borra: se revierten
los efectos, se marca `estado='A'` y el motivo queda en `observacion`. Las reglas que impiden anular se
lanzan como `AnulacionException` y se muestran en el modal.

| Comprobante | Revierte | Bloquea si |
|---|---|---|
| Venta | stock (+`movimientos_articulos`), movimientos de caja, saldo CC del cliente, cheques en cartera, presupuesto origen vuelve a `A` | tiene recibos vigentes aplicados (anular primero el recibo); tiene CAE (requiere NC — AFIP pendiente); cheque rechazado/endosado/vendido |
| Recibo | saldo de las ventas cobradas (vuelven a `P`), deuda del cliente + asiento CC, caja, cheque recibido | cheque rechazado/endosado/vendido |
| Orden de Pago | saldo de las compras (vuelven a `P`), deuda del proveedor + asiento CC, egreso de caja, cheque propio | — |

Compras se anulan con `CompraService::revertir()` desde `Compras\Index` (flujo propio, avisa OPs aplicadas).

## Anticipos de sueldo — `Anticipos\Index`

Portado de avícola, **sin** liquidaciones de sueldo (CentroGoma no las tiene: `liquidado` se marca a mano). Cargar un
anticipo ES pagarlo: una línea en `anticipo_pagos` por medio de pago, cada una con SU caja abierta, y un egreso
`concepto=anticipo` por línea vía `CajaService` (contador `cajas.total_anticipos`). El cabezal denormaliza la línea de
mayor monto. Estados `pendiente` → `aprobado` → `liquidado`, o `rechazado`. Solo un pendiente se edita, rechaza o elimina,
y los tres revierten los egresos; aprobar/liquidar no mueven caja.

---

## Módulo Presupuestos

Implementado en mayo 2026.

| Ruta | Componente |
|------|-----------|
| `GET /presupuestos` | `Presupuestos\Index` |
| `GET /presupuestos/create` | `Presupuestos\Form` (`?duplicar={id}` = copia editable) |
| `GET /presupuestos/{id}/edit` | `Presupuestos\Form` |
| `GET /presupuestos/{id}/pdf` | `PresupuestoController::pdf()` |

**Estados:** `A` = Activo, `V` = Vencido (calculado por `fecha_vencimiento`, vale hasta ese día inclusive), `F` = Facturado, `N` = Anulado.

**Precios:** en pantalla el renglón es FINAL (con IVA), el que se le cotiza al cliente y que el vendedor puede pactar;
en `det_presupuestos.precio_unitario` se guarda NETO con 4 decimales, así al reabrir o pasar a venta vuelve el mismo
final. Totales con `Ventas\Form::calcularTotales()` (el presupuesto y su venta dan igual al centavo).

**Sin cliente registrado** (41% de los presupuestos del Delphi): `cliente_id` es opcional y `cliente_nombre` guarda el
nombre escrito a mano; `Presupuesto::nombre_cliente` resuelve cliente → nombre → "Consumidor final".

**Duplicar** (en el Delphi "Modificar" hacía esto): botón en el Index, cualquier estado. Abre `create?duplicar={id}`
con cabecera y renglones del original y fecha de hoy; no se graba hasta guardar. "Actualizar precios a la lista de
hoy" recotiza los renglones. El original no se toca.

**Conversión a Venta:** botón "Convertir a Venta" en el Index (estados A/V) → `ventas/create?presupuesto_id={id}`.
- `Ventas\Form::precargarDesdePresupuesto()` carga cliente, lista, cantidades y **precios del presupuesto** como
  `precio_manual` (el motor no los pisa ni al cambiar cantidades ni al registrar; `regla_aplicada = presupuesto`).
- Cada renglón trae `precio_lista` (motor de hoy) solo como referencia: "Lista hoy: $X" bajo el precio, un clic lo aplica.
- Presupuesto vencido → aviso. Sin cliente → venta a consumidor final con el nombre en la observación.
- Todo se ajusta antes de registrar; al registrar, el presupuesto pasa a `F` con `venta_id`.

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
Pricing::neto(float $final, float $iva): float   // final / (1 + iva/100), redondeado a 4 decimales
```

**Los precios en BD siempre son NETOS, con 4 decimales** (`articulo_precios.precio`, `cliente_precios.precio_especial`,
el precio unitario del motor y el que tipea el operador en Ventas). Con 2, un final de $15.000 volvía como $14.999,99.
El helper convierte solo para presentación.

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

**Regla:** el subtotal con IVA sale del precio *final* unitario redondeado a 2 decimales, no de reconvertir el
subtotal neto. El IVA es la diferencia con el neto (neto + IVA = total, sin centavos sueltos), vea o no los netos el operador.

```
✅ round(round(precio_neto × (1 + iva/100), 2) × cantidad × (1 - descuento/100), 2)
❌ round(subtotal_neto × (1 + iva/100), 2)
```

**El total que se graba lo calcula el servidor:** `Ventas\Form::calcularTotales()` desde los renglones ya validados,
con las percepciones del cliente en la BD. Alpine usa la misma fórmula solo para mostrar; el total y el `clienteData`
del payload no se graban. El IVA de cada renglón (`det_ventas.subtotal_iva`) también es final − neto, así la suma de
renglones cuadra con el cabezal. `registrarVenta()` corre en una transacción (venta, renglones, stock, caja, CC y cheques:
todo o nada).

Verificado con las 25 ventas del Delphi del 31/10/2026 (simulación con el form real y rollback): con los precios
cobrados, coinciden al centavo los totales, la caja y la cuenta corriente.

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
| `RolesAndPermissionsSeeder` | `migrate:fresh --seed` | Siempre primero. Crea el admin inicial solo si están `CENTROGOMA_ADMIN_EMAIL`/`_PASSWORD` en el `.env` (nunca una cuenta con contraseña por defecto) |
| `ListaPrecioSeeder` | `migrate:fresh --seed` | Crea lista default (Minorista) y otras |
| `DatosInicialSeeder` | `migrate:fresh --seed` | TipoIva, comprobantes AFIP, cliente "Consumidor Final" |
| `UsuarioTestingSeeder` | manual (`--class`) | Usuario `testing@centrogoma.test` con rol admin; no corre en producción |

**En producción** (datos ya existentes): nunca usar `migrate:fresh`. Solo correr migraciones incrementales.

---

## Atajos de teclado

| Pantalla | Tecla | Acción |
|----------|-------|--------|
| `/ventas` (index) | **F2** | Nueva venta |
| `/ventas/create` | **F10** | Registrar venta |
| `/presupuestos/create` | **F10** | Guardar presupuesto |
| Todos los formularios | **Enter** / **Shift+Enter** | Campo siguiente / anterior (en lugar de Tab) |

### Enter en lugar de Tab (`resources/js/enter-navegacion.js`)

Como frango-saas y el Delphi. Un manejador global en `document`:
- Enter en un input o select → siguiente campo del mismo modal (`.fixed`, `[role=dialog]`), `<form>` o página.
  Casillas y textarea no son destino (el Enter quedaría atrapado escribiendo saltos de línea).
- Al pasar el último campo → foco en el botón marcado con **`data-enter-guardar`**; otro Enter lo aprieta. En un
  `<form>` sin ese botón (login), lo envía. **Todo botón de guardar de un formulario o modal nuevo lleva el atributo.**
- No toca campos que manejan su propio Enter (`x-on:keydown.enter`, `@keydown.enter`, `wire:keydown.enter` o
  `preventDefault`). Desde Alpine se sigue de largo con `window.enterSiguiente(el)` / `window.enterGuardar(el)`.
- `data-enter-nativo` lo desactiva en un contenedor; `data-enter-saltar` saltea un campo.

**Ventas** (portado de frango-saas): artículo → cantidad → precio → descuento → renglón nuevo (solo si el actual
tiene artículo); Enter en la fila vacía salta a cobrar (quitando las filas vacías del medio); en el monto: vacío y
falta saldo → lo completa; cargado y falta → otro pago; cubierto → foco en "Registrar venta" (Esc vuelve), nunca
registra en el mismo Enter. Tarjeta → marca → monto; cheque → sus datos → monto. `guardando` evita el doble registro.
El servidor descarta los renglones sin artículo antes de validar (Ventas y Presupuestos). Un renglón ya cargado no
abre la lista de artículos al recibir el foco: un Enter de paso lo volvería a elegir y pisaría el precio.

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
tests/Feature/Listas/AjustePreciosTest.php      — 13 tests (redondeo, vista previa, historial y deshacer, filtros, por costo, precio base, grilla, tramos en el lugar, margen)
tests/Feature/Clientes/ClienteOverridesTest.php — 6 feature tests
tests/Feature/Compras/CompraServiceTest.php     — 7 feature tests (CC, contado, anulación)
tests/Feature/Caja/                             — 23 tests (CajaService, transferencias, detalle de caja, gastos, categorías, bancos)
tests/Feature/Anulaciones/AnulacionesTest.php  — 13 tests (venta/recibo/OP registradas con los forms reales y anuladas)
tests/Feature/Legacy/ImportarSaldosLegacyTest.php — 7 tests (saldo, a favor, dry-run, re-corrida, saldo ya cobrado, --force no toca la ficha)
tests/Feature/Clientes/BuscadorClienteTest.php  — 9 tests (búsqueda, selección y hooks por pantalla)
tests/Feature/Cheques/ChequeLifecycleTest.php   — 12 tests (portados de avícola: ciclo de vida, alta, filtros, orden)
tests/Feature/Empleados/AnticipoMultiPagoTest.php — 11 tests (portados de avícola: multi-medio, cuadre, editar/rechazar/eliminar revierten caja)
tests/Feature/Ventas/RegistrarVentaTotalesTest.php — 7 tests (precio redondo exacto, total recalculado en servidor, percepciones de la BD, rollback)
tests/Feature/Presupuestos/PresupuestosTest.php — 13 tests (reabrir sin perder precio, sin cliente, duplicar, pasar a venta con precio pactado y ajustes, vencido)
tests/Feature/Estadisticas/EstadisticasTest.php — 5 tests (sin saldos iniciales, anticipos como sueldos, buscador, permiso, Dashboard)
tests/Feature/Ventas/FormasPagoTarjetaTest.php  — 6 tests (marca de tarjeta en pago/recibo/caja, Mercado Pago, OC, cobrado por marca)
tests/Feature/CuentaCorriente/ResumenCuentaTest.php — 6 tests (saldo corrido en orden de fecha, pendientes, saldos, PDF/Excel)
tests/Feature/ExampleTest.php                   — 1 smoke test (GET / → redirect login)
```

**Total: 195 tests, 851 assertions — todos pasan.** Tests usan `RefreshDatabase` contra `centrogoma_test` (puerto/usuario del `.env`). La conexión legacy de los tests apunta a `centrogoma_legacy_test` (vacía; cada test arma sus tablas Delphi) — nunca a la BD Delphi real.

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
| `2026_09_29_000002_add_legacy_id_to_categorias_gasto_table` | `legacy_id` para importar `plan_cuenta` |
| `2026_09_29_000003_add_saldo_inicial_to_ventas_and_compras` | Flag de comprobante de saldo inicial (scope `comerciales()`) |
| `2026_10_31_000001..2` | `anticipo_empleados` + `anticipo_pagos` (líneas de pago por caja) |
| `2026_11_07_000002_create_ajustes_precios_tables` | Historial de ajustes masivos de precios (`ajustes_precios`, `ajuste_precio_items`) |
| `2026_11_07_000001_presupuestos_sin_cliente` | `presupuestos.cliente_id` opcional + `cliente_nombre` |
| `2026_11_08_000001_tarjetas_mercado_pago_y_orden_compra` | `tarjetas` + `tarjeta_id` en pagos/recibos/caja, `forma_pago` enum→string, Mercado Pago, `ventas.orden_compra` |
| `2026_11_08_000002_presupuestos_opciones_iva` | `presupuestos.precios_con_iva` y `leyenda_iva` |
| `2026_10_31_000003_precios_netos_con_4_decimales` | `articulo_precios.precio` y `cliente_precios.precio_especial` a `decimal(15,4)`; recupera el neto de 4 decimales de los tramos importados sin editar |

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

Al guardar, los tramos se **actualizan en el lugar** (no se borran y recrean): su id lo usan el Excel de precios, el
historial de ajustes y `det_ventas.tramo_aplicado_id`. Muestra el **precio sugerido** (costo × (1+IVA) × (1+margen),
la fórmula del Delphi), el margen real del precio actual y "Usar sugerido" (redondeado a $100).

---

## Listas de precios — herramientas de edición masiva

Desde `/listas-precios/{lista}/precios`. Lógica en `App\Services\Pricing\AjustePrecios`.

1. **Ajuste masivo** — modo *precio de venta* (± % sobre el final) o *costo* (± % sobre el costo y precio = costo ×
   IVA × margen, como el Delphi; solo lista default, omite artículos sin costo). **Redondeo del final** a $1/$10/$50/$100
   (default $100: en el Delphi el 66% de los precios son múltiplos de $100), sin que el paso supere el 10% del precio
   (un artículo de $9,60 no pasa a $0 ni a $100). Filtros: rubro, marca, la búsqueda actual y/o los artículos marcados
   en el listado. Bajar: máximo 99%. **Vista previa obligatoria** (cantidad + muestra antes/después) y **historial**
   (`ajustes_precios` + `ajuste_precio_items` con el precio anterior) con **Deshacer**, que no pisa los precios
   cambiados después. Escribe en lote (UPDATE ... CASE, sin eventos Eloquent): 7.248 precios en ~5 s.
2. **Exportar/Importar Excel** — export `.xlsx` con ID_TRAMO en col A y precio editable en col G (fondo amarillo). Import lee col[0]=ID_TRAMO, col[6]=precio; modo `'final'` convierte con `Pricing::neto()`.
3. **Grilla editable** — paginada (25 por página); lo editado se conserva al cambiar de página y `guardarGrilla()`
   graba solo los tramos que cambiaron.

**Precio base sincronizado:** `articulos.precio_unitario` = tramo base (menor "desde") de la lista default, vía
`AjustePrecios::sincronizarPrecioBase()` después de ajuste, deshacer, Excel, grilla, modal de tramo y ficha del
artículo. El motor lo usa cuando una lista no tiene tramo para el artículo: si quedaba viejo, se cobraba el precio viejo.

En el listado se ve el costo, el **margen real** (en rojo si está por debajo del configurado) y el precio sugerido.
Al registrar una compra que sube costos (`ajuste_automatico`), `/compras` avisa qué artículos quedaron con margen bajo.

---

## Pendientes / Próximas fases

Lista completa y priorizada en **`docs/ESTADO_PROYECTO.md` §4**. Resumen:

- **Antes de la salida real:** validar stock legacy · decisiones de administración sobre saldos a favor · limpiar
  datos de prueba en producción · reimportar con el Delphi actualizado (`docs/DESPLIEGUE_VPS.md` §4).
- **Seguridad:** cambiar la contraseña MySQL de desarrollo (sigue publicada en el repo de avícola).
- **Funcionalidad** (ver `docs/COMPARACION_DELPHI.md`): decidir impresora fiscal vs. todo electrónico → AFIP (FE + nota
  de crédito) → artículos en USD → libros IVA ventas/compras → mejoras chicas (tarjeta por marca, Mercado Pago…).
- **Menores:** fase 6 pricing (recargo entrega / descuento efectivo) · en Ventas, la cantidad tipeada antes de
  elegir artículo no pasa a `seleccionarArticulo` (workaround: elegir artículo primero).
