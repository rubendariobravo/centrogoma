# Propuesta de Migración: Centro Goma (Delphi → Laravel SaaS)

**Fecha:** 22 de Marzo de 2026  
**Proyecto:** Migración del sistema de escritorio CentroGoma a plataforma web SaaS

---

## 1. ANÁLISIS EXHAUSTIVO DE LA BASE DE DATOS `centro_goma`

### 1.1 Resumen General

| Métrica | Valor |
|---------|-------|
| **Total de tablas** | 35 |
| **Motor** | InnoDB (todas) |
| **Charset** | Mixto (latin1 / utf8) |
| **Foreign Keys** | 14 relaciones definidas |
| **Volumen total estimado** | ~500K registros transaccionales |

### 1.2 Tablas y Volúmenes (ordenadas por importancia)

#### Tablas Transaccionales (core del negocio)
| Tabla | Registros | Descripción |
|-------|-----------|-------------|
| `venta` | ~93,900 | Facturas de venta (incluye NC y ND) |
| `det_venta` | ~139,876 | Detalle de cada venta (líneas de factura) |
| `pago_v` | ~94,749 | Pagos recibidos por ventas |
| `compra` | ~11,482 | Facturas de compra a proveedores |
| `detalle_compra` | ~36,512 | Detalle de compras |
| `pago_c` | ~11,421 | Pagos realizados a proveedores |
| `cccliente` | ~18,643 | Cuenta corriente de clientes |
| `ccproveedor` | ~20,032 | Cuenta corriente de proveedores |
| `presupuesto` | ~6,232 | Presupuestos emitidos |
| `detalle_presupuesto` | ~26,688 | Detalle de presupuestos |
| `egresos` | ~230 | Egresos de caja |

#### Tablas Maestras
| Tabla | Registros | Descripción |
|-------|-----------|-------------|
| `producto` | ~7,053 | Catálogo de productos |
| `cliente` | ~6,521 | Clientes |
| `proveedor` | ~250 | Proveedores |
| `categoria` | ~201 | Categorías/rubros de productos |
| `localidades` | ~203 | Localidades geográficas |
| `provincias` | ~20 | Provincias |

#### Tablas Auxiliares/Configuración
| Tabla | Registros | Descripción |
|-------|-----------|-------------|
| `banco` | ~10 | Bancos |
| `caja` | ~29 | Apertura/cierre de cajas |
| `cajas` | 0 | Definición de cajas (sin uso) |
| `cheque` | ~45 | Cheques emitidos/recibidos |
| `comprobante` | ~19 | Tipos de comprobantes |
| `documento_fiscal` | ~27 | Tipos de documentos fiscales |
| `pago_aceptado` | ~22 | Formas de pago aceptadas |
| `plan_cuenta` | ~28 | Plan de cuentas contable |
| `tipoiva` | ~5 | Condiciones de IVA |
| `doc_cliente` | ~8 | Tipos de documento de cliente |
| `variable_entorno` | ~3 | Configuraciones del sistema |
| `agenda` | ~43 | Agenda de contactos |
| `cccliente1` | ~52 | CC cliente alternativa (legacy) |
| `orden_pago` | 0 | Órdenes de pago (sin uso) |
| `det_orden_pago` | 0 | Detalle OP (sin uso) |
| `det_pago_op` | 0 | Detalle pagos OP (sin uso) |
| `cuenta_banco` | 0 | Cuentas bancarias (sin uso) |
| `movto_cta_banco` | 0 | Movimientos bancarios (sin uso) |

### 1.3 Relaciones (Foreign Keys)

```
cliente ──< cccliente (id_cliente)
cliente ──< presupuesto (id_cliente)
proveedor ──< ccproveedor (id_proveedor)
proveedor ──< compra (id_proveedor)
compra ──< detalle_compra (id_compra)
compra ──< pago_c (id_compra)
producto ──< detalle_compra (id_producto)
producto ──< det_venta (id_producto)
producto ──< detalle_presupuesto (id_producto)
venta ──< det_venta (id_venta)
venta ──< pago_v (id_venta)
presupuesto ──< detalle_presupuesto (id_presupuesto)
pago_aceptado ──< pago_v (id_tipo)
provincias ──< localidades (id_provincia)
```

### 1.4 Estructura de Tablas Clave

#### `venta` - Estructura compleja con campos fiscales
- Soporta: Facturas (A/B/C), Notas de Crédito, Notas de Débito
- Campos CAE para facturación electrónica AFIP
- Campo `tipo` distingue tipo de operación
- Campo `estado`: P (Pagado), C (Cuenta Corriente), A (Anulada)
- Campos `cliente_vtadiaria`, `dni_vtadiaria` para ventas sin cliente registrado

#### `producto` - Catálogo de neumáticos/productos
- Campos: codigo, nombre, descripcion, articulo, metraje
- Precios: costo, venta, unitario, margen
- Control de stock básico
- IVA configurable por producto
- Flag `modifica_precio`

#### `cliente` - Con soporte AFIP
- Condición IVA, alícuota IVA, tipo documento
- Flag `c_corriente` para habilitar cuenta corriente
- Flag `cliente_nn` para consumidor final

### 1.5 Problemas Detectados en la BD

1. **Charset inconsistente**: Mezcla de `latin1`, `utf8`, `utf8_spanish_ci`
2. **Tipos numéricos mixtos**: `float(9,2)`, `float(9,3)`, `decimal(11,2)` para montos
3. **Tablas sin uso**: `cajas`, `orden_pago`, `det_orden_pago`, `det_pago_op`, `cuenta_banco`, `movto_cta_banco`
4. **Tabla legacy**: `cccliente1` parece ser una copia/backup
5. **Sin timestamps**: Ninguna tabla tiene `created_at`/`updated_at`
6. **Sin soft deletes**: No hay eliminación lógica consistente
7. **Campos estado inconsistentes**: Algunos usan `char(1)`, otros `varchar(20)`, otros `tinyint`
8. **Sin índices secundarios** en varias tablas importantes (ej: `venta` no tiene índice en `fecha`)
9. **FKs faltantes**: `venta.id_cliente` no tiene FK declarada hacia `cliente`

---

## 2. ANÁLISIS DEL SISTEMA DELPHI (CentroGoma_XE5)

### 2.1 Stack Tecnológico
- **IDE**: Delphi XE5
- **Componentes DB**: MyDAC (MyAccess) para conexión MySQL
- **Reportes**: Rave Reports
- **Facturación Electrónica**: AFIP via COM (FEAFIPLib_TLB)
- **Exportación**: QExport4 (Excel)
- **UI**: VCL con tema "Smokey Quartz Kamri"

### 2.2 Arquitectura
- **Patrón**: DataModule centralizado (`dmu.pas` - 45KB, ~1300 líneas)
- **Formularios**: ~60 formularios (ABM + Listados)
- **Formulario principal**: `frmSaltaGomas` (28KB de lógica)
- **Conexión**: Via archivo INI configurable (servidor/BD por sucursal)

### 2.3 Módulos Funcionales Identificados

| Módulo | Formularios | Descripción |
|--------|------------|-------------|
| **Ventas** | frmAmVenta, frmListadoVentas, frmAmPagoVenta, frmAmNotaCredito | Facturación completa con pago |
| **Presupuestos** | frmAmPresupuestoVenta, frmListadoPresupuesto | Cotizaciones a clientes |
| **Compras** | frmAmCompra, frmListadoCompras, frmAmPagoCompra | Registro de compras |
| **Productos** | frmAmProducto, frmListadoProductos, frmListadoProductosPrecios | Catálogo y precios |
| **Clientes** | frmAmCliente, frmListadoClientes | ABM clientes |
| **Proveedores** | frmAmProveedor, frmListadoProveedores | ABM proveedores |
| **Cta. Cte. Clientes** | frmListadoCCCliente, frmAmPagoCCCliente, frmAmPagoCtaCorriente | Gestión CC |
| **Cta. Cte. Proveedores** | frmListadoCCProveedor, frmAmPagoCCProveedor, frmAmCbteCCProveedor | Gestión CC |
| **Caja** | frmAmCaja, frmListadoCajas, frmListadoSaldoCaja, frmListadoDetalleCaja | Caja diaria |
| **Egresos** | frmAmEgreso, frmListadoEgresos | Gastos |
| **Cheques** | frmListadoCheques, frmVtaCheque | Gestión de cheques |
| **Órdenes de Pago** | frmAmOrdenPago, frmListadoOrdenPago | OP a proveedores |
| **Bancos** | frmAmBanco, frmListadoBanco, frmAmCuentaBanco, frmListadoCuentaBanco | Cuentas bancarias |
| **Fiscal** | frmImpresorFiscal, frmSeleccionFiscal, frmReporteZeta, uFacturacionElectronica | AFIP |
| **Reportes** | dmReportes (2.9MB de plantillas Rave) | Reportes impresos |
| **Categorías** | frmListadoCategoria, frmAmRubro | Rubros/categorías |
| **Plan Cuentas** | frmListadoPlanCuentas, frmAmPlanCuenta | Contabilidad básica |

### 2.4 Funcionalidades Especiales
- **Facturación electrónica AFIP** (45KB de código dedicado)
- **Impresión fiscal** (28KB - soporte para controladores fiscales)
- **Reporte Zeta** (cierre fiscal diario)
- **Venta por categoría** (filtro de venta por rubro)
- **Inicio de sesión** (deshabilitado actualmente en código)

---

## 3. ANÁLISIS DEL SISTEMA SaaS (Laravel)

### 3.1 Stack Tecnológico
- **Framework**: Laravel 10.x
- **Auth**: Jetstream + Sanctum + Livewire 3
- **Permisos**: spatie/laravel-permission
- **PDF**: DomPDF
- **QR**: endroid/qr-code + chillerlan/php-qrcode
- **Excel**: maatwebsite/excel + PHPSpreadsheet
- **Email**: Integrado en Laravel
- **Pagos**: MercadoPago SDK
- **Internacionalización**: laravel-lang
- **Frontend**: Livewire 3 + TailwindCSS + Vite

### 3.2 Módulos Existentes en el SaaS

| Módulo | Estado | Controllers/Modelos |
|--------|--------|-------------------|
| **Ventas** | ✅ Completo | VentaController, Venta, DetVenta, PagoV |
| **Notas de Crédito** | ✅ Completo | NotaCreditoController |
| **Notas de Débito** | ✅ Completo | NotaDebitoController |
| **Ventas Simplificadas** | ✅ Completo | VentaSimplificadaController |
| **Clientes** | ✅ Completo | ClienteController, Cliente, CcCliente |
| **Sucursales Cliente** | ✅ Completo | ClienteSucursal |
| **Proveedores** | ✅ Completo | ProveedorController, Proveedor, CcProveedor |
| **Compras** | ✅ Completo | CompraController, Compra, DetalleCompra |
| **Órdenes de Pago** | ✅ Completo | OrdenPagoController, OrdenPago, DetalleOrdenPago, PagoOrdenPago |
| **Artículos** | ✅ Completo | ArticuloController, Articulo (con listas de precios, USD) |
| **Rubros** | ✅ Completo | RubroController, Rubro |
| **Marcas** | ✅ Completo | MarcaController, Marca |
| **Cajas** | ✅ Avanzado | CajaController, Caja, MovimientoCaja |
| **Cuentas de Fondos** | ✅ Completo | CuentaFondoController, CuentaFondo |
| **Movimientos de Caja** | ✅ Completo | MovimientoCajaController, MovimientoCaja |
| **Recibos** | ✅ Completo | ReciboController, Recibo, ReciboDetalle, ReciboFormaPago |
| **Retenciones** | ✅ Completo | RetencionController, Retencion, RetencionDetalle |
| **Cheques** | ✅ Completo | ChequeController, Cheque, MovimientoCheque |
| **Acreditaciones** | ✅ Completo | AcreditacionController, Acreditacion |
| **Cotizaciones (USD)** | ✅ Completo | CotizacionController, Cotizacion |
| **Informes Proveedores** | ✅ Completo | InformeProveedorController |
| **Informes Clientes** | ✅ Completo | InformeClienteController |
| **Informes Ventas** | ✅ Completo | InformeVentaController |
| **Artefactos** | ✅ Completo | ArtefactoController, Artefacto |
| **Mantenimiento Extintores** | ✅ Completo | MantenimientoExtintorController |
| **Contratos** | ✅ Completo | ContratoController, Contrato |
| **Vehículos** | ✅ Completo | VehiculoController, Vehiculo |
| **Choferes** | ✅ Completo | ChoferController, Chofer |
| **Empleados** | ✅ Completo | EmpleadoController, Empleado |
| **Gastos** | ✅ Completo | GastoController, GastoGeneral, GastoObra |
| **Obras** | ✅ Completo | ObraController, Obra |
| **Tienda Online** | ✅ Completo | TiendaController, CarritoController, CheckoutController |
| **Dashboard** | ✅ Completo | DashboardController |
| **Variables Entorno** | ✅ Completo | VariableEntornoController |
| **Facturación AFIP** | ✅ Completo | AfipTestController (WSAA/WSFEV1) |
| **Envío Email Facturas** | ✅ Completo | FacturaEmailController |
| **Mov. Artículos** | ✅ Completo | MovimientoArticuloController |
| **Ubicaciones** | ✅ Completo | PaisController, ProvinciaController, DepartamentoController |

### 3.3 Mejoras del SaaS respecto al Delphi

El SaaS ya implementa funcionalidades **superiores** al sistema Delphi:

1. **Relaciones polimórficas** en CC (CcCliente usa `morphTo` para vincular con Venta, Recibo, NC)
2. **SoftDeletes** en Venta, Compra, OrdenPago
3. **Timestamps automáticos** en la mayoría de modelos
4. **Cuentas de Fondos** (modelo contable más robusto que el plan de cuentas de Delphi)
5. **Movimientos de Caja con doble partida** (cuenta_origen + cuenta_destino)
6. **Listas de precios múltiples** (6 listas + precio USD con cotización dinámica)
7. **Sistema de permisos** (spatie/laravel-permission)
8. **Tienda online** con MercadoPago
9. **Recibos y Retenciones** (no existentes en Delphi)
10. **Sucursales de cliente** para facturación
11. **Movimientos de artículos** (trazabilidad de stock)

---

## 4. MAPEO DE EQUIVALENCIAS: centro_goma → SaaS

| Tabla centro_goma | Equivalente SaaS | Acción |
|-------------------|-------------------|--------|
| `producto` | `articulo` | Migrar datos adaptando campos |
| `categoria` | `rubro` | Migrar directamente |
| `cliente` | `cliente` | Migrar adaptando estructura |
| `proveedor` | `proveedor` | Migrar adaptando estructura |
| `venta` | `venta` | Migrar con transformación de campos |
| `det_venta` | `det_venta` | Migrar adaptando FKs |
| `pago_v` | `pago_v` | Migrar con nueva estructura de formas de pago |
| `compra` | `compra` | Migrar adaptando estructura |
| `detalle_compra` | `detalle_compra` | Migrar adaptando FKs |
| `pago_c` | `pago_c` | Migrar |
| `cccliente` | `cccliente` | Migrar con sistema polimórfico |
| `ccproveedor` | `ccproveedor` | Migrar con sistema polimórfico |
| `presupuesto` | **CREAR** | No existe en SaaS, crear módulo |
| `detalle_presupuesto` | **CREAR** | No existe en SaaS, crear módulo |
| `caja` | `caja` | Migrar como histórico |
| `egresos` | `movimientos_caja` | Migrar como movimientos tipo egreso |
| `cheque` | `cheque` | Migrar adaptando |
| `banco` | `banco` | Migrar |
| `pago_aceptado` | `pago_aceptado` | Migrar/verificar equivalencias |
| `documento_fiscal` | `documento_fiscal` | Migrar |
| `comprobante` | `comprobante` | Migrar |
| `plan_cuenta` | `cuentas_fondos` | Mapear conceptualmente |
| `tipoiva` | `tipoiva` | Migrar |
| `provincias` | `provincias` | Ya existe |
| `localidades` | `departamentos` | Migrar como departamentos |
| `variable_entorno` | `variable_entorno` | Migrar |
| `agenda` | **NO MIGRAR** | Funcionalidad menor |
| `cccliente1` | **NO MIGRAR** | Tabla legacy/backup |
| `doc_cliente` | `doc_afip` | Mapear documentos AFIP |
| `cajas` | **NO MIGRAR** | Sin registros |
| `orden_pago` | `orden_pago` | Sin datos, ya existe en SaaS |
| `cuenta_banco` | `cuentas_fondos` | Sin datos, ya existe en SaaS |
| `movto_cta_banco` | `movimientos_caja` | Sin datos, ya existe en SaaS |

---

## 5. PROPUESTA DE MIGRACIÓN

### 5.1 Estrategia: Incorporar CentroGoma como "tenant" dentro del SaaS existente

**Recomendación: SÍ es viable y conveniente migrar dentro del SaaS existente.**

El SaaS ya cubre el **95% de la funcionalidad** del sistema Delphi, y lo supera en muchos aspectos. Solo falta el módulo de **Presupuestos** que deberá crearse.

### 5.2 Stack de Desarrollo Recomendado

El SaaS ya tiene un stack sólido y maduro. **No se recomienda cambiar de stack**, sino potenciarlo:

| Capa | Tecnología | Justificación |
|------|-----------|---------------|
| **Backend** | Laravel 10 → **Laravel 11** | Actualizar para LTS. Ya está establecido |
| **Frontend** | Livewire 3 + TailwindCSS | Ya funciona. Blade + Livewire es ideal para este tipo de app |
| **Auth** | Jetstream + Sanctum | Ya configurado |
| **Permisos** | spatie/laravel-permission | Ya configurado |
| **DB** | MySQL 8 | Misma BD que usa centro_goma |
| **PDF** | DomPDF | Ya funciona |
| **AFIP** | WS nativo PHP (WSAA/WSFEV1) | Ya implementado |
| **Pagos** | MercadoPago | Ya integrado |
| **Deploy** | Servidor propio / VPS | Para control total |

### 5.3 Fases de Implementación

#### FASE 1: Preparación del SaaS (1-2 semanas)
1. Crear módulo de **Presupuestos** (migraciones, modelo, controller, vistas)
2. Agregar campo `legacy_id` a tablas principales para rastrear IDs originales
3. Crear migración para columnas faltantes si es necesario
4. Preparar seeders de datos maestros (formas de pago, tipos IVA, etc.)

#### FASE 2: Migración de Datos Maestros (1 semana)
1. Migrar `categoria` → `rubro` (mapeo id_categoria → rubro_id)
2. Migrar `provincias` y `localidades`
3. Migrar `banco`, `tipoiva`, `documento_fiscal`, `pago_aceptado`, `comprobante`
4. Migrar `variable_entorno`
5. Migrar `producto` → `articulo` (requiere transformación de campos)
6. Migrar `cliente` (adaptar estructura, mapear localidades → departamentos)
7. Migrar `proveedor`

#### FASE 3: Migración de Datos Transaccionales (2-3 semanas)
1. Migrar `venta` con transformación de campos
2. Migrar `det_venta` actualizando FKs
3. Migrar `pago_v` con nueva estructura
4. Migrar `compra` y `detalle_compra`
5. Migrar `pago_c`
6. Migrar `presupuesto` y `detalle_presupuesto`
7. Migrar `cccliente` al sistema polimórfico
8. Migrar `ccproveedor` al sistema polimórfico
9. Migrar `cheque`
10. Migrar `caja` y `egresos`

#### FASE 4: Validación y Ajustes (1-2 semanas)
1. Validar saldos de cuenta corriente (clientes y proveedores)
2. Validar totales de ventas y compras
3. Validar stock de productos
4. Pruebas de facturación electrónica
5. Pruebas de caja y movimientos

#### FASE 5: Puesta en Producción (1 semana)
1. Migración final con datos actualizados
2. Capacitación de usuarios
3. Go-live con período de soporte intensivo

### 5.4 Script de Migración de Datos

Se recomienda crear un **comando Artisan** dedicado:

```
php artisan centrogoma:migrate --step=maestros
php artisan centrogoma:migrate --step=transacciones
php artisan centrogoma:migrate --step=validar
```

Cada paso debe:
- Conectarse a la BD `centro_goma` como segunda conexión
- Leer datos con paginación (hay ~500K registros)
- Transformar y mapear campos
- Insertar en la BD del SaaS
- Registrar log de migración
- Guardar `legacy_id` para trazabilidad

### 5.5 Transformaciones Clave de Datos

#### Producto → Artículo
```
producto.id_producto     → articulo.id (nuevo) + legacy_id
producto.codigo          → articulo.Codigo
producto.descripcion     → articulo.Descripcion
producto.id_categoria    → articulo.rubro_id (mapear via tabla de equivalencias)
producto.costo           → articulo.precio_costo
producto.venta           → articulo.precio_unitario
producto.margen          → articulo.margen
producto.stock           → articulo.stock
producto.iva             → articulo.alicuota_iva (int→float, 21→21.0)
producto.estado          → articulo.estado
producto.nombre          → (campo adicional o en Descripcion)
marca_id                 → Asignar marca genérica "Centro Goma"
```

#### Venta (transformación)
```
venta.id_venta           → venta.id (nuevo) + legacy_id
venta.id_cliente         → venta.cliente_id (mapear via legacy_id)
venta.tipo_factura       → venta.tipo_factura_id (mapear doc_fiscal)
venta.neto               → venta.neto
venta.total              → venta.total
venta.estado             → venta.estado (P→P, C→C, A→anulada)
venta.cae                → preservar
venta.nro_factura        → venta.nro_factura
venta.tipo               → venta.tipo_comprobante (V→factura, NC→nota_credito)
venta.nc                 → venta.factura_original_id (mapear)
```

---

## 6. MÓDULO NUEVO: PRESUPUESTOS

### 6.1 Migraciones Necesarias

```php
// create_presupuesto_table
Schema::create('presupuesto', function (Blueprint $table) {
    $table->id();
    $table->foreignId('cliente_id')->constrained('cliente');
    $table->foreignId('usuario_id')->nullable()->constrained('users');
    $table->date('fecha');
    $table->integer('dias_validez')->default(7);
    $table->decimal('descuento', 11, 2)->default(0);
    $table->decimal('neto', 13, 2)->default(0);
    $table->decimal('iva', 9, 2)->default(0);
    $table->decimal('total', 13, 2)->default(0);
    $table->char('estado', 1)->default('A'); // A=Activo, V=Vencido, F=Facturado, N=Anulado
    $table->string('condicion_pago', 50)->nullable();
    $table->string('plazo_entrega', 50)->nullable();
    $table->char('incluye_iva', 1)->default('S');
    $table->string('moneda', 3)->default('ARS');
    $table->string('leyenda_iva', 200)->nullable();
    $table->text('observaciones')->nullable();
    $table->string('legacy_id')->nullable()->index();
    $table->timestamps();
    $table->softDeletes();
});

// create_detalle_presupuesto_table
Schema::create('detalle_presupuesto', function (Blueprint $table) {
    $table->id();
    $table->foreignId('presupuesto_id')->constrained('presupuesto')->cascadeOnDelete();
    $table->foreignId('articulo_id')->constrained('articulo');
    $table->decimal('cantidad', 11, 3)->default(1);
    $table->decimal('precio_unitario', 11, 2)->default(0);
    $table->decimal('sub_total', 11, 2)->default(0);
    $table->float('alicuota_iva')->default(21);
    $table->char('estado', 1)->default('A');
    $table->integer('item')->nullable();
    $table->string('legacy_id')->nullable();
    $table->timestamps();
});
```

---

## 7. RESUMEN EJECUTIVO

### ¿Es viable la migración? **SÍ, altamente viable.**

| Aspecto | Evaluación |
|---------|-----------|
| **Cobertura funcional del SaaS** | 95% - Solo falta Presupuestos |
| **Complejidad de migración de datos** | Media - ~500K registros con transformaciones |
| **Riesgo técnico** | Bajo - Misma BD MySQL, stack probado |
| **Tiempo estimado total** | **6-8 semanas** |
| **Beneficios inmediatos** | Acceso web, multi-usuario, reportes, tienda online, email |

### Ventajas de migrar al SaaS existente
1. **No reinventar la rueda**: El 95% ya está construido y probado en producción
2. **Modelo de datos superior**: Polimorfismo, soft deletes, timestamps, cuentas de fondos
3. **UI moderna**: TailwindCSS + Livewire vs VCL de Windows
4. **Acceso remoto**: Web vs escritorio
5. **Facturación AFIP**: Ya implementada en PHP
6. **Funcionalidades extra**: Tienda online, MercadoPago, envío de facturas por email, QR
7. **Escalabilidad**: Multi-sucursal, multi-usuario con permisos
8. **Mantenibilidad**: Laravel es más accesible que Delphi XE5

### Lo que NO se debe migrar
- `cccliente1` (tabla legacy/backup)
- `cajas`, `orden_pago`, `det_orden_pago`, `det_pago_op`, `cuenta_banco`, `movto_cta_banco` (tablas vacías)
- `agenda` (datos de baja prioridad, 43 registros)
- Reportes Rave (se recrean con DomPDF/Blade en el SaaS)

### Recomendación Final
**Proceder con la migración al SaaS existente**, creando el módulo de Presupuestos y un comando Artisan robusto para la migración de datos. El sistema Delphi puede mantenerse en paralelo durante 1 mes como respaldo hasta validar completamente la operación en el SaaS.
