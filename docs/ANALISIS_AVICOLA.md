# Análisis app `avicola` → funcionalidades a portar a CentroGoma

> App base: `C:\laragon\www\avicola` (Laravel 12 · Livewire 3 · misma arquitectura).
> CentroGoma se derivó de ella eliminando los módulos avícolas (Produccion, Pesadas,
> Descartes, SubArticulos). En ese recorte también quedaron afuera módulos y servicios
> **financieros transversales** que CentroGoma necesita. Este documento mapea esa brecha.

Fecha de análisis: 2026-06-29.

---

## Resumen ejecutivo

La diferencia de fondo es **arquitectónica**, no solo de features:

- **Avícola** centraliza la lógica financiera en **services** (`CajaService`, `ChequeService`,
  `CompraService`) que mantienen un **libro de movimientos** (`movimiento_cajas`,
  `cheque_movimientos`) reversible y auditable. Cada hecho económico se asienta como un
  movimiento; los contadores cacheados en `cajas` son una vista derivada.
- **CentroGoma** hace la misma lógica **inline en los componentes Livewire**, incrementando
  contadores cacheados de `cajas` (`total_ventas`, `total_cobros`, `total_gastos`) sin un libro
  de movimientos detrás. No hay servicios; no hay reversión; los cheques no impactan la caja.

Portar avícola = adoptar los services + las tablas de movimientos + los módulos faltantes.

---

## 1. Módulos / servicios ausentes en CentroGoma

| Pieza (avícola) | Tipo | Estado en CentroGoma |
|---|---|---|
| `Services/Caja/CajaService` | Service | ❌ Ausente (lógica inline) |
| `Services/Cheque/ChequeService` | Service | ❌ Ausente (cheques reimplementados inline) |
| `Services/Compra/CompraService` | Service | ❌ Ausente (lógica inline) |
| `Services/Afip/{AfipService,Wsaa,Wsfev1}` | Service | ❌ Ausente (sin facturación electrónica) |
| `MovimientoCaja` + tabla `movimiento_cajas` | Modelo+tabla | ❌ Ausente |
| `TransferenciaCaja` + tabla | Modelo+tabla | ❌ Ausente |
| `Banco` + módulo `/bancos` | Modelo+UI | ❌ Ausente |
| `Gasto` + `CategoriaGasto` + módulo `/gastos` | Modelo+UI | ❌ Ausente |
| `AnticipoEmpleado` + `/anticipos-empleados` | Modelo+UI | ❌ Ausente |
| `FormaPago` + tabla `formas_pago` | Modelo+tabla | ❌ Ausente (formas hardcodeadas) |
| `CostoFinanciero` | Modelo | ❌ Ausente |
| `Estadisticas` (`/estadisticas`) | UI | ❌ Ausente |
| `CajasShow` (`/cajas/{caja}`) | UI | ❌ Ausente (no hay detalle de caja) |

Rutas presentes en avícola y faltantes en CentroGoma:
`estadisticas`, `gastos`, `bancos`, `anticipos-empleados`, `cajas/{caja}`,
`ventas/exportar`, `ventas/{venta}/solicitar-cae`, `ordenes-pago/{ordenPago}/pdf`,
`listas-precios/{lista}/exportar-excel`, `listas-precios/{lista}/exportar-pdf`,
`compras/{compra}/edit`.

---

## 2. Caja — libro de movimientos + arqueo

### Qué tiene avícola que CentroGoma no

**`Caja` (avícola)** es multi-tipo (`diaria` | `general` | `banco`) y tiene **11 contadores**
(`total_ventas, total_cobros, total_pagos, total_gastos, total_anticipos,
total_ajustes_ingreso/egreso, total_transferencias_entrada/salida,
total_cheques_entrada/salida`). CentroGoma solo tiene 3 (`total_ventas, total_cobros, total_gastos`).

Métodos del modelo avícola que CentroGoma no tiene:
- `getSaldoEsperadoAttribute()` — saldo según todos los contadores.
- `resumenMovimientos()` — una pasada sobre `movimiento_cajas` que **distingue efectivo físico
  de otros medios** (transferencia bancaria / tarjeta / cheque / cuenta corriente). Clave: una
  venta con `forma_pago='transferencia'` es bancaria (no efectivo); una transferencia **entre
  cajas** sí mueve efectivo.
- `getSaldoEfectivoEsperadoAttribute()` — arqueo de efectivo real.
- `getTotalCobradoSinCcAttribute()` — cobrado del día por cualquier medio salvo CC.
- `ventasPorMedio()` — desglose de ventas por forma de pago.
- `getSaldoTransferibleAttribute()` — efectivo movible (diaria) vs saldo total (general/banco).

### `CajaService` (`app/Services/Caja/CajaService.php`)

Punto único de escritura del libro:
- `registrarMovimiento(Caja, tipo, concepto, monto, opts)` — crea `MovimientoCaja` e incrementa
  el contador cacheado correspondiente (`columnaContador()` mapea concepto→columna).
  Conceptos: `venta|cobro|pago_proveedor|gasto|anticipo|transferencia|ajuste|cheque`.
- `revertirMovimientosDe(referenciaType, referenciaId)` — borra los movimientos de una referencia
  y decrementa contadores (usado al anular venta/compra/gasto).
- `transferir(origen, destino, monto, ...)` — comprobante `TransferenciaCaja` + egreso/ingreso
  atómicos (`DB::transaction`).

`MovimientoCaja` (tabla `movimiento_cajas`): `caja_id, fecha, tipo (ingreso|egreso), concepto,
monto, forma_pago, referencia_type/id (morphTo), user_id, categoria_gasto_id, cuenta_contable_id,
descripcion`. El campo `cuenta_contable_id` queda preparado para un futuro plan de cuentas.

---

## 3. Cheques — divergencia importante

⚠ **CentroGoma reimplementó cheques con otro modelo de datos y estados.** Portar avícola implica
**migrar o reconciliar** lo ya hecho, no solo agregar.

| | Avícola | CentroGoma (actual) |
|---|---|---|
| Tipos | `tercero` / `propio` | `recibido` / `emitido` |
| Estados terceros | `cartera → depositado → acreditado/rechazado`; `aplicado` (endoso); `vendido` | `en_cartera → depositado → cobrado/rechazado`; `endosado` |
| Estados propios | `emitido → debitado/rechazado/anulado` | `pendiente → cobrado/rechazado/anulado` |
| Historial | `ChequeMovimiento` (`cheque_movimientos`) | `MovimientoCheque` (`movimientos_cheque`) |
| Lógica | `ChequeService` centralizado | Inline en `Cheques\Index` |
| **Impacto en caja** | ✅ Sí — la plata entra/sale al **acreditar/debitar** | ❌ No — solo cambia estado |
| Rechazo | ✅ Reabre deuda del cliente (CcCliente + saldo) y revierte caja | ❌ No |
| Campos extra | `caja_origen_id, es_echeq, es_diferido, librador_*, serie, sucursal` | `serie` (parcial) |

### `ChequeService` (`app/Services/Cheque/ChequeService.php`)

Modelo mental: **para cheques de tercero la plata real recién aparece al ACREDITAR** (se mueve de
`caja_origen` → caja banco). Métodos:
- `registrarRecibido(datos)` — cheque tercero en `cartera` + log `recepcion` (usado en Ventas/Recibos).
- `registrarEmitido(datos)` — cheque propio `emitido` + log `emision` (usado en Órdenes de Pago).
- `depositar()` — `cartera → depositado` (no mueve plata).
- `acreditar()` — `depositado → acreditado`: egreso en `caja_origen` + ingreso en caja banco.
- `debitar()` — propio `emitido → debitado`: egreso de la caja banco.
- `rechazar()` — revierte caja si estaba acreditado y **reabre la deuda del cliente** (`reabrirDeudaCliente`).
- `anular()` — revierte caja si estaba acreditado/debitado.
- `assertTransicion()` valida contra `Cheque::TRANSICIONES` (fuente de verdad en el modelo).

El modelo `Cheque` (avícola) tiene `puedeTransicionarA()`, `getEstadoVencimientoAttribute()`
(`vencido|proximo|normal`), scopes `terceros()`/`propios()`, y labels constantes.

---

## 4. Ventas — integración financiera

Al registrar venta, avícola (`Ventas\Form::registrarVenta`) hace, en orden:
1. `venta->pagos()->create([...])` por cada pago **no-CC**.
2. Si el pago es cheque → `ChequeService::registrarRecibido(... caja_origen_id, venta_id ...)`
   (entra a cartera, **sin** tocar caja todavía).
3. `CajaService::registrarMovimiento(caja, 'ingreso', 'venta', total, ...)` — libro + contador.
4. Si hay saldo en CC → actualiza `cliente->saldo` + crea `CcCliente` (debe).
5. Opcional: **solicitud de CAE AFIP** inline (`AfipService::solicitarCaeVenta`); si falla, la venta
   queda sin CAE y se reintenta desde el listado (`ventas.solicitar-cae`).

CentroGoma ya hace 1, 2 (cheque inline sin service), 4. **Le falta:** 3 (libro de movimientos —
hoy solo incrementa `total_ventas`), el impacto diferido de caja del cheque, y 5 (AFIP).

`FormaPago::esCuentaCorriente()` / `esEfectivo()` son **consultas a la tabla `formas_pago`**
(configurable). CentroGoma hardcodea las formas; portar esto permite administrarlas desde la app.

---

## 5. Compras — `CompraService` (procesar/revertir)

`CompraService` separa **registrar** (procesada=false, no toca nada) de **procesar** (aplica
efectos). `procesar(Compra, ?detIdsActualizan)`:
- Suma stock + `MovimientoArticulo` por renglón (si `modifica_stock`).
- Actualiza `precio_costo` y, si `ajuste_automatico`, **propaga a las listas** según
  `ajuste_tipo` del tramo (`porcentual|fijo`, saltea `manual`). Selección por renglón.
- Si forma de pago es CC → `proveedor->saldo` + `CcProveedor` (debe), estado `P`.
- Si es contado → `CajaService::registrarMovimiento(egreso, 'pago_proveedor')`, estado `C`.

`revertir(Compra)` deshace stock y CC/caja (no revierte `precio_costo`, no es confiable). Usado
por anular y por editar una compra ya procesada.

---

## 6. Módulos de UI faltantes

- **`/gastos`** (`Gasto` + `CategoriaGasto`): egreso de caja categorizado → `CajaService` concepto `gasto`.
- **`/bancos`** (`Banco`): catálogo de bancos, FK en cheques.
- **`/anticipos-empleados`** (`AnticipoEmpleado`): adelantos → caja concepto `anticipo`.
- **`/estadisticas`**: dashboards de ventas/caja/CC.
- **`/cajas/{caja}` (CajasShow)**: detalle de una caja con su libro de movimientos y arqueo.
- **AFIP** (`Services/Afip`): WSAA (auth con certificado) + WSFEv1 (CAE), `solicitar-cae`.

---

## 7. Orden de adopción sugerido

1. **Tablas + modelos base**: `formas_pago`, `movimiento_cajas`, `transferencias_caja`, columnas
   extra en `cajas`, `bancos`. (Migraciones incrementales — no `migrate:fresh` en producción.)
2. **`CajaService`** + refactor de Ventas/Recibos/Compras/OrdenPago para usarlo (reemplaza los
   `increment()` inline). Habilita arqueo y reversión.
3. **Reconciliar cheques**: decidir si se migra el modelo de CentroGoma al de avícola
   (`tercero/propio`, estados con impacto en caja) o se mantiene el actual. **Recomendado migrar**
   al de avícola por el impacto en caja y la reapertura de deuda en rechazo.
4. **`CompraService`** (procesar/revertir) + módulo `/gastos`.
5. **Bancos, Anticipos, Estadísticas, CajasShow.**
6. **AFIP** (último — requiere certificados y datos fiscales reales).

> Nota: las tablas `cc_clientes` / `cc_proveedores` ya tienen **esquema idéntico** en ambas apps
> (`debe/haber/saldo` + `referencia` morphTo), así que la CC no requiere cambios de esquema, solo
> que los services escriban en ella de forma consistente y reversible.
