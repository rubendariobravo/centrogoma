# Sistema Avícola — Estado del Proyecto

**Fecha:** 26 de abril de 2026  
**Versión:** 0.8 (desarrollo activo)  
**URL local:** http://avicola.test  
**Repositorio:** C:\laragon\www\avicola

---

## Stack Tecnológico

| Componente | Versión | Notas |
|---|---|---|
| PHP | 8.3.28 | |
| Laravel | 12.57.0 | |
| Livewire | 3.7.15 | Full-page components + Volt (auth/nav) |
| Alpine.js | bundled con Livewire | Cálculos client-side sin round-trips |
| Tailwind CSS | 3.x (via Vite) | |
| MySQL | Laragon, puerto **3307** | DB: `avicola`, user: `ruben` |
| Spatie Permission | latest | 5 roles: admin, supervisor, cajero, vendedor, almacen |

**NO usa Filament** — descartado por lentitud del Repeater (400-900ms). Alpine.js maneja todos los cálculos en tiempo real.

---

## Credenciales de desarrollo

- **URL:** http://avicola.test  
- **Admin:** admin@avicola.test / password  
- **Vendedor:** vendedor@avicola.test / password  
- **DB:** DB_DATABASE=avicola, DB_PORT=3307, DB_USERNAME=ruben, DB_PASSWORD=REDACTED

---

## Módulos completados ✅

### Infraestructura
- [x] Proyecto Laravel 12 creado y configurado
- [x] Vhost Apache (avicola.test) funcionando
- [x] TALL stack instalado (Livewire 3, Alpine.js, Tailwind)
- [x] Spatie Laravel Permission instalado y configurado
- [x] Layout principal: `resources/views/components/layouts/app.blade.php`
  - Sidebar fijo (desktop) / drawer (móvil) con overlay
  - Topbar con badge caja abierta/cerrada (tiempo real)
  - Usuario logueado con avatar inicial
- [x] Navegación lateral: `resources/views/livewire/layout/navigation.blade.php`
  - 6 grupos colapsables: Ventas, Compras, Artículos, Caja y Finanzas, Producción, Administración
  - Detección de link activo con highlight
- [x] Autenticación completa (Laravel Breeze / Volt)
- [x] FakeDataSeeder: datos de prueba (rubros, marcas, proveedores, clientes, artículos, cajas, ventas)

### Base de datos (31 migraciones ejecutadas)

| Tabla | Descripción |
|---|---|
| `tipoiva` | Tipos IVA con código AFIP (RI, CF, Exento, etc.) |
| `doc_afip` | Tipos de comprobante AFIP (FA, FB, FC, ND, NC) |
| `variable_entorno` | Configuraciones: pto_vta, cuit_emisor, afip_ambiente, etc. |
| `rubros` | Categorías de artículos con alícuota IVA |
| `marcas` | Marcas con proveedor FK |
| `proveedores` | Proveedores con SoftDeletes |
| `empleados` | Personal con roles: vendedor/repartidor/produccion/administrativo/otro |
| `articulos` | Artículos tipo CAJA o PESO, dual stock (mayorista/minorista), SoftDeletes |
| `articulo_precios` | Precios por rango de cantidad (cantidad_desde, cantidad_hasta NULL=ilimitado, precio, activo, orden) |
| `clientes` | Clientes con tipoiva_id, porcentaje_iibb, percepcion_iva, es_mayorista, SoftDeletes |
| `cajas` | Apertura/cierre caja diaria por usuario |
| `ventas` | Ventas con campos AFIP completos, SoftDeletes |
| `det_ventas` | Ítems de venta con alicuota_iva, descuento, subtotal, subtotal_iva |
| `pago_ventas` | Pagos: efectivo/transferencia/cheque/tarjeta/cta_cte/otro |
| `movimientos_articulos` | Stock: entrada/salida/ajuste/produccion/descarte/pesada (morphable) |
| `cc_clientes` | Cuenta corriente clientes (debe/haber/saldo running, referencia morphable) |
| `cc_proveedores` | Cuenta corriente proveedores (misma estructura) |
| `compras` | Compras a proveedores con IVA desglosado |
| `det_compras` | Ítems de compra |
| `producciones` | Transformación cajones → cortes/subproductos con merma |
| `remitos` | Remitos de entrega (peso y cajas) |
| `pesadas` | Pesadas físicas de stock con ajuste automático |
| `descartes` | Mermas/descartes con motivo |
| `recibos` | Recibos de cobro a clientes |
| `ordenes_pago` | Órdenes de pago a proveedores |
| `detalle_ordenes_pago` | Facturas cubiertas por cada orden de pago |
| `cotizaciones` | Cotización USD/EUR |
| `sub_articulos` | Relación padre-hijo para producciones |
| `cliente_precios` | Precios especiales por cliente |

### Dashboard ✅
- Stats: ventas hoy/semana, clientes activos, artículos activos, stock crítico, caja abierta
- Tabla últimas 8 ventas + panel top 6 alertas stock crítico

### Artículos ✅ (CRUD completo con precios por tramo)
- Index con búsqueda, filtros rubro/tipo/estado, paginación
- Form: identificación, clasificación, tipo CAJA/PESO, stock, IVA, costos
- Tabla de tramos de precio con Alpine.js @entangle
- Preview calculadora: cantidad → precio aplicado en tiempo real (sin round-trips)

### Rubros ✅ · Marcas ✅ · Empleados ✅ (CRUD inline modal)

### Clientes ✅ (CRUD full-page)
- Filtros: búsqueda, tipo IVA, mayorista/minorista, estado
- CUIT/DNI, tipo IVA, vendedor asignado, lista precio (1-6), bonificación, límite crédito
- Percepción IIBB (%), Percepción IVA toggle
- `porcentaje_iibb` guardado como decimal 0-1, ingresado como % (dividido en save)

### Proveedores ✅ (CRUD full-page)
- Razón social, CUIT, contacto, teléfonos, email, domicilio
- `protected $table = 'proveedores'` (Laravel pluraliza mal: proveedors)

### Cajas ✅ (Apertura/Cierre diaria)
- Modal apertura: saldo inicial + observación
- Modal cierre: resumen breakdown (ventas/cobros/gastos), saldo real contado, diferencia
- Historial paginado con indicadores diferencia
- `hora_apertura/cierre`: columnas `dateTime` en DB, cast `datetime` en modelo → `format('H:i')`

### Ventas ✅ (Index + Form con Alpine.js)
- Filtros: búsqueda, rango fechas (default mes actual), estado, cliente
- Total del período
- Form Alpine.js sin round-trips:
  - `articulosData` JSON pre-cargado con tramos de precio
  - Al seleccionar artículo: auto-rellena tipo_venta, unidad_medida, alícuota IVA
  - Al cambiar cantidad: busca tramo correspondiente → actualiza precio
  - Cálculo en tiempo real: neto_21/10_5, IVA, percepciones IIBB/IVA, descuento general, total
  - `updatedClienteId`: carga datos de percepciones + determina letra (A si RI, B si no)
  - Al guardar (`save(array $totales)`): crea Venta, DetVenta, decrementa stock, MovimientoArticulo, PagoVenta, incrementa caja
  - Cuenta corriente: actualiza `Cliente.saldo`, crea `CcCliente` (debe)
- Modo lectura (`$readOnly`) para ventas ya guardadas

### Compras ✅ (Index + Form)
- Index: filtros proveedor/período/estado, total período
- Form Alpine.js: ítems con precio costo, IVA seleccionable, subtotales en tiempo real
- Al guardar: crea DetCompra, **incrementa stock**, MovimientoArticulo (entrada), actualiza precio_costo del artículo
- Cuenta corriente: actualiza `Proveedor.saldo`, crea `CcProveedor` (debe)
- Forma de pago: cuenta corriente (queda P) o pago inmediato (queda C)

### Recibos ✅ (Index + Form)
- Lista de cobros con filtros período/cliente/estado
- Form: selección de cliente → muestra saldo actual + facturas pendientes (más antiguas primero)
- Al guardar: crea Recibo, aplica pago a ventas pendientes (FIFO), actualiza `Cliente.saldo`, crea `CcCliente` (haber), incrementa `caja.total_cobros`
- Botón "Pagar saldo completo" para cobro total de un clic

### CC Clientes ✅ · CC Proveedores ✅ · Órdenes de Pago ✅
- Lista de clientes/proveedores con saldo, ordenados por deuda mayor primero
- Filtros: con deuda / todos / saldados
- Click en cliente → panel derecho con movimientos (últimos 50)
- Muestra debe/haber/saldo running por movimiento

### Producción ✅ (inline modal)
- Registro de procesos productivos: entrada, salida, merma calculada automáticamente
- Merma % auto-calculada en base a entrada/salida

### Remitos ✅ (inline modal)
- Registro de despachos: fecha, empleado, total_peso (kg), total_cajas
- Estados: abierto/cerrado/anulado

### Pesadas ✅ (inline modal)
- Stock sistema auto-cargado al seleccionar artículo
- Diferencia calculada: stock_real - stock_sistema
- Al guardar: ajusta `articulos.stock` al stock real + crea `MovimientoArticulo` (entrada o salida)

### Descartes ✅ (inline modal)
- Fecha, artículo, empleado, cantidad, motivo
- Al guardar: decrementa `articulos.stock` + crea `MovimientoArticulo` (salida)

### Usuarios ✅ (CRUD inline modal, admin)
- Crear/editar usuarios con hash de password
- Asignación de rol via Spatie (syncRoles)
- No permite eliminar el propio usuario

### Roles ✅ (gestión de permisos, admin)
- Lista de roles con permisos agrupados por módulo
- Checkbox grid para asignar/quitar permisos
- Protege roles admin y supervisor de eliminación

### Configuración ✅
- Datos de la empresa (nombre, razón social, CUIT, domicilio, teléfono, email, logo)
- Facturación AFIP (punto de venta, IIBB tipo/número)
- Opciones stock: stock negativo, ajuste automático precio costo, pide vendedor
- Listas de precio (1-6)
- Usa `VariableEntorno::set/get` para persistir

---

## Módulos pendientes ❌ (Post-MVP)

| Módulo | Descripción |
|---|---|
| **AFIP WSFEv1** | Facturación electrónica: CAE, vto_cae via SOAP/JSON |
| **Informes** | Ventas por período/cliente/artículo, stock, comisiones, libro IVA |
| **Dashboard mejorado** | Gráficos Chart.js (ventas por día, top artículos, evolución stock) |
| **Comisiones empleados** | Calcular y registrar comisiones por ventas del vendedor asignado |
| **Cotizaciones** | Registrar cotización USD/EUR, soporte venta en moneda extranjera |
| **Sub-artículos** | Despiece: un cajón → cortes/subproductos (SubArticulo model existe) |
| **Precios por cliente** | ClientePrecio: precio especial por artículo para un cliente específico |
| **Notificaciones** | Alertas stock crítico, vencimiento facturas, etc. |
| **Impresión** | PDF de facturas, recibos, remitos |

---

## Estructura de archivos clave

```
app/
├── Livewire/
│   ├── Articulos/Index.php + Form.php    ✅
│   ├── Cajas/Index.php                   ✅
│   ├── CcClientes/Index.php              ✅ Saldo + movimientos expandibles
│   ├── CcProveedores/Index.php           ✅ Saldo + movimientos expandibles
│   ├── Clientes/Index.php + Form.php     ✅
│   ├── Compras/Index.php + Form.php      ✅ Alpine.js + stock + CC
│   ├── Config/Index.php                  ✅ VariableEntorno
│   ├── Descartes/Index.php               ✅ Inline modal + stock
│   ├── Empleados/Index.php               ✅ CRUD inline modal
│   ├── Marcas/Index.php                  ✅ CRUD inline modal
│   ├── Pesadas/Index.php                 ✅ Ajuste stock automático
│   ├── Produccion/Index.php              ✅ Merma calculada
│   ├── Proveedores/Index.php + Form.php  ✅
│   ├── OrdenPago/Index.php + Form.php    ✅ Pagos FIFO + CC
│   ├── Recibos/Index.php + Form.php      ✅ Cobros FIFO + CC
│   ├── Remitos/Index.php                 ✅ Inline modal
│   ├── Roles/Index.php                   ✅ Permisos checkbox grid
│   ├── Rubros/Index.php                  ✅ CRUD inline modal
│   ├── Usuarios/Index.php                ✅ CRUD + Spatie roles
│   ├── Ventas/Index.php + Form.php       ✅ Alpine.js + percepciones + CC
│   └── Dashboard.php                     ✅

app/Models/
├── Articulo.php       ✅ (SoftDeletes, preciosActivos, getPrecioPorCantidad)
├── ArticuloPrecio.php ✅
├── Caja.php           ✅ (datetime casts hora_apertura/cierre)
├── CcCliente.php      ✅ (table=cc_clientes, morphable referencia)
├── CcProveedor.php    ✅ (table=cc_proveedores, morphable referencia)
├── Cliente.php        ✅ (SoftDeletes, tipoIva, cuentaCorriente)
├── Compra.php         ✅ (SoftDeletes, proveedor, detalles)
├── DetCompra.php      ✅ (table=det_compras)
├── DetVenta.php       ✅
├── Descarte.php       ✅
├── Empleado.php       ✅
├── Marca.php          ✅
├── MovimientoArticulo.php ✅ (morphable referencia)
├── PagoVenta.php      ✅
├── Pesada.php         ✅
├── Produccion.php     ✅ (SoftDeletes)
├── Proveedor.php      ✅ (table='proveedores', SoftDeletes)
├── OrdenPago.php      ✅ (SoftDeletes, proveedor, detalles)
├── DetalleOrdenPago.php ✅ (table=detalle_ordenes_pago)
├── Recibo.php         ✅ (SoftDeletes)
├── Remito.php         ✅ (SoftDeletes)
├── Rubro.php          ✅
├── TipoIva.php        ✅
├── User.php           ✅ (HasRoles via Spatie)
├── VariableEntorno.php ✅ (get/set estáticos)
└── Venta.php          ✅ (SoftDeletes, detalles, pagos)
```

---

## Rutas registradas

```php
// Ventas
GET  ventas              → VentasIndex
GET  ventas/create       → VentasForm
GET  ventas/{venta}      → VentasForm (readOnly)
GET  clientes            → ClientesIndex
GET  clientes/create     → ClientesForm
GET  clientes/{id}/edit  → ClientesForm
GET  recibos             → RecibosIndex
GET  recibos/create      → RecibosForm
GET  recibos/{recibo}    → RecibosForm (readOnly)

// Compras
GET  compras             → ComprasIndex
GET  compras/create      → ComprasForm
GET  compras/{compra}    → ComprasForm (readOnly)
GET  proveedores         → ProveedoresIndex
GET  proveedores/create  → ProveedoresForm
GET  proveedores/{id}/edit → ProveedoresForm

// Artículos
GET  articulos           → ArticulosIndex
GET  articulos/create    → ArticulosForm
GET  articulos/{id}/edit → ArticulosForm
GET  rubros              → RubrosIndex
GET  marcas              → MarcasIndex

// Caja y Finanzas
GET  cajas               → CajasIndex
GET  cc-clientes         → CcClientesIndex
GET  cc-proveedores      → CcProveedoresIndex
GET  ordenes-pago        → OrdenPagoIndex
GET  ordenes-pago/create → OrdenPagoForm
GET  ordenes-pago/{op}   → OrdenPagoForm (readOnly)

// Producción
GET  produccion          → ProduccionIndex
GET  remitos             → RemitosIndex
GET  pesadas             → PesadasIndex
GET  descartes           → DescartesIndex
GET  empleados           → EmpleadosIndex

// Administración
GET  usuarios            → UsuariosIndex
GET  roles               → RolesIndex
GET  config              → ConfigIndex
```

---

## Decisiones de diseño importantes

### Convenciones de nomenclatura
- `Proveedor` → `protected $table = 'proveedores'` (pluralización incorrecta de Laravel)
- `CcCliente` → `protected $table = 'cc_clientes'`
- `CcProveedor` → `protected $table = 'cc_proveedores'`
- `DetCompra` → `protected $table = 'det_compras'`

### Porcentajes guardados como decimales
- `porcentaje_iibb`: guardado 0-1 (0.03 = 3%), mostrado/ingresado como % (3)
- `comision` en Marcas: guardado 0-1, mostrado ×100
- Al cargar en Form: ×100 para display, dividir /100 en save

### Cajas y dateTime
- `hora_apertura` / `hora_cierre` son columnas `DATETIME` (no `TIME`)
- Modelo: `'hora_apertura' => 'datetime'`
- Vista: `$caja->hora_apertura->format('H:i')` (no substr)

### Alpine.js en formularios complejos
- Ventas y Compras: articulosData pre-cargado como JSON via `@json()`
- `$wire.entangle('items')` para binding bidireccional
- `$wire.save(totales)` para enviar totales calculados por Alpine a Livewire
- Sin round-trips durante la edición de ítems

### CC (Cuenta Corriente)
- **Ventas cuenta corriente**: `Cliente.saldo += total`, crea `CcCliente` (debe)
- **Recibos**: aplica a ventas más antiguas primero (FIFO), `Cliente.saldo -= monto`, crea `CcCliente` (haber)
- **Compras cuenta corriente**: `Proveedor.saldo += total`, crea `CcProveedor` (debe)
- **Órdenes de Pago**: aplica FIFO a compras más antiguas, `Proveedor.saldo -= monto`, crea `CcProveedor` (haber), crea `DetalleOrdenPago`
- `saldo` en `cc_clientes/cc_proveedores` es el saldo **después** del movimiento

### Stock
- Ventas → `articulo.stock -= cantidad` + `MovimientoArticulo` (salida)
- Compras → `articulo.stock += cantidad` + `MovimientoArticulo` (entrada)
- Descartes → `articulo.stock -= cantidad` + `MovimientoArticulo` (salida)
- Pesadas → `articulo.stock = stock_real` + `MovimientoArticulo` (entrada o salida)
- Compras también actualizan `articulo.precio_costo` con el precio de la última compra

---

## Seeders

### Para poblar la BD con datos de prueba:
```bash
php artisan db:seed --class=RolesAndPermissionsSeeder
php artisan db:seed --class=FakeDataSeeder
```

### Datos creados por FakeDataSeeder:
- 8 rubros, 4 proveedores, 5 marcas, 5 empleados
- 8 clientes (RI mayoristas, monotributo, CF minoristas) con IIBB/percepción
- 13 artículos CAJA/PESO con 42 tramos de precio
- 8 cajas (1 abierta hoy + 7 historial)
- 38 ventas últimos 7 días con DetVenta + PagoVenta

### Si hay errores al correr el seeder:
```sql
SET FOREIGN_KEY_CHECKS=0;
TRUNCATE TABLE ventas; TRUNCATE TABLE det_ventas; TRUNCATE TABLE pago_ventas;
TRUNCATE TABLE articulos; TRUNCATE TABLE articulo_precios;
TRUNCATE TABLE clientes; TRUNCATE TABLE proveedores;
TRUNCATE TABLE marcas; TRUNCATE TABLE rubros;
TRUNCATE TABLE empleados; TRUNCATE TABLE cajas;
TRUNCATE TABLE movimientos_articulos;
SET FOREIGN_KEY_CHECKS=1;
```
Luego volver a correr `RolesAndPermissionsSeeder` antes de `FakeDataSeeder`.
