### 20.6 Funcionalidades del Sistema Delphi (Frango-25)

Del análisis de los formularios Delphi identificados:

| Formulario | Funcionalidad |
|---|---|
| `frmFacturacionU` | Punto de venta / facturación |
| `frmPrincipalCajeroU` | Pantalla principal del cajero |
| `frmProduccionU` | Producción (apertura de cajones → subproductos) |
| `frmAmSubArticuloU` | ABM de sub-artículos (productos de producción) |
| `frmEdicionRepartoU` | Gestión de rutas de reparto |
| `frmDetPesadoU` | Detalle de pesada de inventario |
| `frmListadoPesadoU` | Listado de pesadas |
| `frmDescarteU` | Registro de descartes/mermas |
| `frmListadoDescartesU` | Listado de descartes |
| `frmListadoMovArticuloU` | Movimientos de stock mayorista |
| `frmListadoMovArticuloMinoristaU` | Movimientos de stock minorista |
| `frmListadoProduccionesU` | Listado de producciones |
| `frmListadoMovCajaU/EditU/GeneralU` | Movimientos de caja (3 vistas) |
| `frmListadoMovTesoreriaU` | Movimientos de tesorería |
| `frmListadoMovVendedorU` | Movimientos por vendedor |
| `frmListadoStockVendedorU` | Stock por vendedor |
| `frmListadoLiquidoRemitoU` | Liquidación de remitos de reparto |
| `frmListadoRemitoCompraU` | Remitos de compra |
| `frmRemitoCompraU` | ABM remito de compra |
| `frmMovStockClienteU` | Movimiento de stock de clientes (modifica_stock) |
| `frmMovStockCompraU` | Movimiento de stock por compras |
| `frmPedidoProveedorU` | Pedidos a proveedores |
| `frmListadoPedidosProvU` | Listado de pedidos a proveedores |
| `frmListadoPedidosU` | Pedidos de clientes |
| `frmPresupuestoU` | Presupuestos a clientes |
| `frmRetencionU` | Retenciones |
| `frmPercepcionU` | Percepciones AFIP |
| `frmVtaChequeU` | Venta de cheques |
| `frmListadoChequesU` | Gestión de cheques |
| `frmControlComprasU` | Control de compras |
| `frmControlMovimientosU` | Control de movimientos |
| `frmEstadisticaVentaArticuloU` | Estadísticas de ventas por artículo |
| `frmCambiarLimiteVentasU` | Cambiar límite de ventas por caja |
| `frmNroFacturaU` | Gestión de numeración de facturas |
| `frmImpresorFiscalU` | Integración con impresora fiscal Hasar |
| `frmHistorialActualizacionU` | Historial de actualizaciones del sistema |
| `frmMovEgresoU` / `frmMovIngresoU` | Movimientos manuales de caja |
| `frmVueltoU` | Cálculo de vuelto |
| `frmAutenticacionU` | Autenticación para operaciones protegidas |
| `frmInicioSesionU` | Login de usuarios |
| `frmSetupU` | Configuración del sistema |

### 20.7 Lógica del Módulo de Producción (desde Delphi)

Del análisis del código `frmProduccionU.pas`:

**Tipos de producción:**
1. **Tipo 0 - Troceo:** Fórmula promedio = `Σ(costo ingreso) / Σ(cantidad producto)`. Ganancia = diferencia de costo por kg.
2. **Tipo 1 - Milanesas:** Porcentaje sobre precio de filet (códigos especiales 11, 111, 211...).
3. **Tipo 2 - Otras:** Diferencia simple de totales.

**Flujo de transacción de producción:**
1. `CALL nueva_variable('nro_produccion')` → obtiene siguiente número
2. `CALL nueva_produccion(...)` tipo='E' → crea cabecera de egreso
3. Para cada artículo de SALIDA → `CALL ActualizarStock(tipo='V')` + registra movimiento artículo + movimiento vendedor
4. `CALL nueva_produccion(...)` tipo='I' → crea cabecera de ingreso
5. Para cada artículo de ENTRADA → `CALL ActualizarStock(tipo='C')` + actualiza precio costo del artículo
6. Imprime reporte de producción con promedio y ganancia
7. Todo en una única transacción con rollback si falla

**Stored Procedures usados:**
- `nueva_variable` — autoincremento controlado
- `nueva_produccion` — alta de artículo en tránsito
- `ActualizarStock(idarticulo, tipo, cantidad)` — tipos: V=venta, C=compra
- `acc_articulo` — movimiento de stock de artículo
- `acc_vendedor` — movimiento de stock del vendedor
- `acc_usuario` — auditoría de movimiento por usuario
- `ActualizarPrecioU` — actualiza precio costo del artículo ingresado

### 20.8 Lógica de Precios Mayoristas en Rubros

El campo `mayoristaN` del rubro es la **cantidad mínima para ese nivel de descuento**:
- Si la cantidad vendida >= `mayorista1` → aplicar `descuento1`%
- Si la cantidad vendida >= `mayorista2` → aplicar `descuento2`%
- etc.

Por ejemplo, rubro POLLOS (id=1):
- mayorista1=3 → descuento1=0% (no descuento hasta 3 cajas)
- mayorista2=20 → descuento2=0%
- mayorista3=50 → descuento3=0%

### 20.9 Sistema de Facturas por Usuario

Cada usuario tiene su propio secuenciador de facturas almacenado en `variable_entorno` como `nro_factura_a_NOMBRE` y `nro_factura_b_NOMBRE`. Esto implica que la impresora fiscal o el sistema AFIP está configurado por usuario/sucursal y cada uno emite en su propio punto de venta. La nueva aplicación web debe replicar este comportamiento asignando punto de venta AFIP por usuario o por sucursal.

---

## 21. MÓDULOS A DESARROLLAR (REVISIÓN COMPLETA)

### 21.1 Módulos CONSERVAR (del SaaS base, con adaptaciones menores)
- Autenticación y roles (agregar rol: cajero, repartidor, vendedor)
- Artículos / Marcas / Rubros (con campos avícolas)
- Clientes con cuenta corriente
- Proveedores con cuenta corriente
- Ventas y facturación electrónica AFIP
- Compras / Notas de crédito / Débito
- Caja y Movimientos de Caja
- Cuentas de Fondos y Tesorería
- Cheques
- Recibos de cobro
- Órdenes de Pago
- Informes de clientes y proveedores
- Empleados + Departamentos + Anticipos (**MANTENER según requerimiento del cliente**)
- Tienda web + Carrito + MercadoPago (canal minorista online)
- Variables de entorno / configuración

### 21.2 Módulos ADAPTAR (cambios estructurales significativos)

#### Artículos — Campos adicionales
```sql
ALTER TABLE articulo ADD COLUMN art_mayorista TINYINT(1) DEFAULT 1;
ALTER TABLE articulo ADD COLUMN perdida FLOAT DEFAULT 3;
ALTER TABLE articulo ADD COLUMN acepta_subrubro TINYINT(1) DEFAULT 0;
ALTER TABLE articulo ADD COLUMN caja TINYINT(1) DEFAULT 0;
ALTER TABLE articulo ADD COLUMN stock_minorista FLOAT(9,3) DEFAULT 0;
ALTER TABLE articulo ADD COLUMN descuento1..5 FLOAT(9,4) DEFAULT 0;
ALTER TABLE articulo ADD COLUMN mayorista1..5 FLOAT(9,2) DEFAULT 0;
ALTER TABLE articulo ADD COLUMN oferta TINYINT(1) DEFAULT 0;
-- Ampliar de 6 a 21 listas de precios (o las que el cliente necesite)
```

#### Rubros — Precios mayoristas escalonados
```sql
ALTER TABLE rubro ADD COLUMN mayorista1..5 FLOAT(9,2) DEFAULT 0;
ALTER TABLE rubro ADD COLUMN descuento1..5 FLOAT(9,2) DEFAULT 0;
```

#### Clientes — Campos adicionales
```sql
ALTER TABLE cliente ADD COLUMN modifica_stock TINYINT(1) DEFAULT 1;
ALTER TABLE cliente ADD COLUMN oferta TINYINT(1) DEFAULT 0;
ALTER TABLE cliente ADD COLUMN excepcion1 INT NULL;
ALTER TABLE cliente ADD COLUMN excepcion2 INT NULL;
ALTER TABLE cliente ADD COLUMN precio_excepcion1 FLOAT(9,2) NULL;
ALTER TABLE cliente ADD COLUMN precio_excepcion2 FLOAT(9,2) NULL;
ALTER TABLE cliente ADD COLUMN tipo_cliente ENUM('mayorista','minorista') DEFAULT 'minorista';
```

#### Vendedores — Campos de reparto
```sql
-- La tabla vendedor/usuario debe incluir:
-- reparto: si hace reparto
-- nro_reparto: número de ruta
-- id_sucursal: sucursal asignada
-- saldo: saldo del vendedor
```

### 21.3 Módulos NUEVOS a Crear

#### A. Sub-artículos (productos de producción)
Nueva tabla `sub_articulo` (análoga a `articulo_sub` de calzada):
- Artículo padre (el que se "abre")
- Sus propias listas de precios
- Stock independiente
- Precios mayoristas escalonados (5 niveles)

#### B. Producción / Transformación de Artículos
Nueva tabla `produccion` + `det_produccion`:
```
produccion:
  - nro_produccion
  - fecha
  - tipo (troceo / milanesas / otras)
  - id_usuario
  - id_vendedor
  - promedio (costo promedio resultante)
  - ganancia
  - estado

det_produccion:
  - id_produccion
  - id_articulo
  - cantidad
  - precio_costo
  - subtotal
  - tipo ('E'=egreso/insumo, 'I'=ingreso/producto)
  - presentacion
  - cantidad_por_caja
```
- Lógica: descuenta artículos de entrada → acredita artículos de salida
- Actualiza precio costo de artículos producidos
- Registra movimiento en `movimiento_articulo` con tipo 'P' (producción)
- Imprime reporte de producción (PDF)

#### C. Dos canales de stock
- `stock_mayorista` + `stock_minorista` en artículo
- `movimiento_articulo` distingue canal (flag o tabla separada)

#### D. Remitos y Reparto
Nueva tabla `remito` + `det_remito`:
- Remito previo a factura para reparto
- Asignación a vendedor/repartidor
- Estado: pendiente → entregado → facturado
- Liquidación de remitos pendientes

#### E. Pedidos de Clientes
Nueva tabla `pedido` + `det_pedido`:
- Para canal mayorista que hace pedido anticipado
- Estado: pendiente → en preparación → despachado

#### F. Descarte / Merma
Nueva tabla `descarte`:
- Registro de mermas y descartes
- Por artículo, fecha, cantidad, motivo, usuario
- Impacta en stock (reduce)
- Reportes de pérdidas

#### G. Inventario / Pesada
Nueva tabla `pesada` + `det_pesada`:
- Inventario físico periódico
- Por artículo: stock_contable vs peso_real → diferencia
- Campo `perdida_porcentaje` por artículo
- Cierre de pesada ajusta stock

#### H. Precios por Cantidad (Escalón)
Lógica de aplicación automática de descuento por volumen según tabla `rubro.mayoristaN / descuentoN`.

#### I. Precios Especiales por Cliente / Artículo
Nueva tabla `cliente_precio` (equivalente a calzada):
- cliente_id, rubro_id, articulo_id, lista, nivel_descuento

#### J. Numeración de Factura por Usuario/Sucursal
- Sistema de secuencias en `variable_entorno` o tabla dedicada
- `nro_factura_A_{usuario}` / `nro_factura_B_{usuario}`

---

## 22. PLAN DE MIGRACIÓN DE DATOS (calzada → avícola)

Cuando el cliente decida migrar datos históricos de calzada, los mapeos serían:

| Tabla calzada | Tabla nueva | Notas |
|---|---|---|
| `articulo` | `articulo` | Mapear 21 listas, campos avícolas |
| `articulo_sub` | `sub_articulo` | Nueva entidad |
| `rubro` | `rubro` | Agregar columnas mayoristaN/descuentoN |
| `marca` | `marca` | Directa |
| `cliente` | `cliente` | Mapear campos adicionales |
| `proveedor` | `proveedor` | Directa |
| `venta` | `venta` | Mapear campos (letra_comprobante → tipo_factura) |
| `det_venta` | `det_venta` | Directa |
| `pago_v` | `pago_v` | Directa |
| `compra` | `compra` | Directa |
| `detalle_compra` | `detalle_compra` | Directa |
| `cccliente` | `cccliente` | Directa |
| `ccproveedor` | `ccproveedor` | Directa |
| `caja` | `caja` | Directa |
| `egresos` | `movimientos_caja` | Mapear tipo/categoría |
| `articulos_transito` | `produccion` | Filtrar tipo='produccion' |
| `det_at` | `det_produccion` | Filtrar |
| `vendedor` | `vendedor`/`empleado` | Evaluar unificación |
| `sucursales` | `sucursales` | Directa |
| `usuario` | `users` | Mapear a Jetstream |

---

## 23. INVENTARIO COMPLETO DE MÓDULOS DELPHI (Frango-25)

Todos los archivos `.pas` en `H:\Desarrollo\Frango-25\` y su función:

### DataModules (núcleo de datos)
| Archivo | Función |
|---|---|
| `dmU.pas` | DataModule principal — 3367 líneas, todas las tablas/queries/stored procs |
| `dmReportesU.pas` | DataModule de reportes — queries para FastReport |
| `DMBaseU.pas` | Clase base del DataModule |

### Módulo de Facturación
| Archivo | Función |
|---|---|
| `frmFacturacionU.pas` | Pantalla principal de venta — grid, formas de pago, AFIP, ARBA |
| `uFacturacionElectronica.pas` | Lógica AFIP WSFEv1 — autorización CAE, impresión PDF |
| `uFacturacionElectronicaAda.pas` | Variante facturación Ada (segunda empresa?) |
| `frmNroFacturaU.pas` | Dialog para ingresar número de factura manual |
| `frmPercepcionU.pas` | Dialog de percepción IIBB manual (monto + nro cbte) |
| `frmImpresorFiscalU.pas` | Impresora fiscal Hasar Argentina |
| `frmPrincipalCajeroU.pas` | Interface principal del cajero |
| `frmPresupuestoU.pas` | Módulo de presupuestos |
| `frmVueltoU.pas` | Cálculo de vuelto |

### Módulo de Remitos / Reparto
| Archivo | Función |
|---|---|
| `frmAmRemitoU.pas` | ABM de remitos de compra a proveedor |
| `frmAmTransitoU.pas` | ABM de artículos en tránsito (remitos de reparto) |
| `frmAmArtTransitoU.pas` | ABM detalle de art. en tránsito |
| `frmArticuloTransitoU.pas` | Gestión de artículos en tránsito |
| `frmEdicionRepartoU.pas` | Edición de remito de reparto post-envío |
| `frmListadoArtTransitoU.pas` | Listado de artículos en tránsito |
| `frmListadoLiquidoRemitoU.pas` | Liquidación de remitos de reparto |
| `frmAmDetalleRemitoCU.pas` | Detalle de remito de compra |
| `frmRemitoCompraU.pas` | Remito de compra a proveedor |
| `frmListadoRemitoCompraU.pas` | Listado de remitos de compra |

### Módulo de Producción / Inventario
| Archivo | Función |
|---|---|
| `frmProduccionU.pas` | Troceo / apertura de cajones → sub-artículos |
| `frmDescarteU.pas` | Registro de descartes y mermas |
| `frmDetPesadoU.pas` | Inventario físico / pesada por artículo |
| `frmListadoPesadoU.pas` | Listado histórico de pesadas |
| `frmListadoProduccionesU.pas` | Listado de producciones |
| `frmListadoDescartesU.pas` | Listado de descartes |
| `frmAmSubArticuloU.pas` | ABM de sub-artículos |
| `frmListadoSubArticulosU.pas` | Listado de sub-artículos |

### Módulo de Clientes / Ventas
| Archivo | Función |
|---|---|
| `frmAmClienteU.pas` | ABM de clientes |
| `frmAmClienteFinalU.pas` | ABM cliente consumidor final |
| `frmBuscarClienteU.pas` | Búsqueda de clientes |
| `frmAmVentaU.pas` | ABM/edición de ventas existentes |
| `frmListadoVentasU.pas` | Listado de ventas |
| `frmListadoVentasGeneralU.pas` | Listado general de ventas con filtros |
| `frmListadoVentasFacturadasU.pas` | Ventas facturadas electrónicamente |
| `frmListadoVentasMinU.pas` | Ventas minoristas |
| `frmFechaVentasU.pas` | Selector fecha para ventas |
| `frmEstadisticaVentaArticuloU.pas` | Estadísticas de venta por artículo |
| `frmListadoCCClienteU.pas` | Cuenta corriente de clientes |
| `frmCambiarLimiteVentasU.pas` | Cambiar límite de crédito de clientes |

### Módulo de Artículos / Precios
| Archivo | Función |
|---|---|
| `frmAmArticuloU.pas` | ABM de artículos (principal) |
| `frmListadoArticulosU.pas` | Listado de artículos con stock |
| `frmlistadoArticulosListaPrecioU.pas` | Listado de artículos con listas de precio |
| `frmAmRubroU.pas` | ABM de rubros |
| `frmListadoRubrosU.pas` | Listado de rubros |
| `frmAmMarcaU.pas` | ABM de marcas |
| `frmListadoMarcaU.pas` | Listado de marcas |
| `frmListadoMovArticuloU.pas` | Movimientos de stock mayorista |
| `frmListadoMovArticuloMinoristaU.pas` | Movimientos de stock minorista |
| `frmListadoMovStockU.pas` | Movimientos de stock por vendedor |
| `frmMovStockClienteU.pas` | Stock por cliente |
| `frmHistorialActualizacionU.pas` | Historial de actualizaciones de precios |
| `frmBuscarU.pas` | Búsqueda genérica de artículos |

### Módulo de Compras / Proveedores
| Archivo | Función |
|---|---|
| `frmAmCompraU.pas` | ABM de compras |
| `frmAmCompra2U.pas` | ABM compras variante 2 |
| `frmAmProveedorU.pas` | ABM de proveedores |
| `frmListadoProveedorU.pas` | Listado de proveedores |
| `frmListadoCompraU.pas` | Listado de compras |
| `frmListadoCCProveedorU.pas` | Cuenta corriente proveedores |
| `frmAmPagoCompraU.pas` | Pago de compras |
| `frmListadoPagoCpraU.pas` | Listado de pagos de compras |
| `frmPedidoProveedorU.pas` | Pedido a proveedor |
| `frmListadoPedidosProvU.pas` | Listado de pedidos a proveedores |
| `frmMovStockCompraU.pas` | Stock desde compras |
| `frmMovProveedorU.pas` | Movimientos de proveedor |
| `frmControlComprasU.pas` | Control de compras |
| `frmAmOrdendePagoU.pas` | Órdenes de pago a proveedores |
| `frmListadoOrdenCompraU.pas` | Listado de órdenes de compra |

### Módulo de Caja / Tesorería
| Archivo | Función |
|---|---|
| `frmAmCajaU.pas` | ABM de cajas |
| `frmAmCajaPrincipalU.pas` | Caja principal (apertura/cierre) |
| `frmAmCieCajaU.pas` | Cierre de caja |
| `frmListadoCajasU.pas` | Listado de cajas |
| `frmListadoMovCajaU.pas` | Movimientos de caja |
| `frmListadoMovCajaGeneralU.pas` | Movimientos caja general |
| `frmListadoMovCajaEditU.pas` | Edición de movimientos de caja |
| `frmListadoMovTesoreriaU.pas` | Movimientos de tesorería |
| `frmListadoBancoU.pas` | Listado de bancos |
| `frmListadoChequesU.pas` | Listado de cheques |
| `frmVtaChequeU.pas` | Venta de cheques |
| `frmListadoGastosU.pas` | Listado de gastos |
| `frmAmPagoCCU.pas` | Pago de cuenta corriente cliente |
| `frmAmPagoCtaCorrienteU.pas` | Gestión cuenta corriente |
| `frmAmCbteCCU.pas` | Comprobante de cuenta corriente |
| `frmAmCbteCCPU.pas` | Comprobante CC proveedor |
| `frmListadoPlanDeCuentaU.pas` | Plan de cuentas |
| `frmAmPlanDeCuentaU.pas` | ABM plan de cuentas |
| `frmRetencionU.pas` | Retenciones impositivas |

### Módulo de Vendedores / Empleados
| Archivo | Función |
|---|---|
| `frmAmVendedorU.pas` | ABM de vendedores |
| `frmListadoVendedorU.pas` | Listado de vendedores |
| `frmVendedorU.pas` | Reportes por vendedor (saldos, datos, ventas, premios) |
| `frmListadoMovVendedorU.pas` | Movimientos de vendedor |
| `frmListadoStockVendedorU.pas` | Stock por vendedor (reparto) |
| `frmAmResponsableU.pas` | ABM responsables |
| `frmListadoResponsableU.pas` | Listado responsables |
| `frmAmTratanteU.pas` | ABM tratantes |
| `frmListadoTratanteU.pas` | Listado tratantes |
| `frmAmUsuarioU.pas` | ABM usuarios del sistema |
| `frmListadoUsuariosU.pas` | Listado usuarios |
| `frmListadoMovUsuarioU.pas` | Movimientos por usuario |
| `frmAutenticacionU.pas` | Dialog de autenticación (nivel encargado) |

### Módulo de Informes / Reportes
| Archivo | Función |
|---|---|
| `frmListadoU.pas` | Listado genérico base |
| `frmListadoVentasGeneralU.pas` | Informe general de ventas |
| `frmListadoPedidosU.pas` | Listado de pedidos |
| `frmControlMovimientosU.pas` | Control general de movimientos |
| `frmMovEgresoU.pas` | Movimientos de egreso |
| `frmMovIngresoU.pas` | Movimientos de ingreso |
| `frmDesdeHastaU.pas` | Selector de rango de fechas |
| `frmFechaVentasU.pas` | Selector de fecha para ventas |

### Módulo de Localidades / Configuración
| Archivo | Función |
|---|---|
| `frmAmLocalidadU.pas` | ABM de localidades |
| `frmListadoLocalidadesU.pas` | Listado de localidades |
| `frmListadoVariablesEntornoU.pas` | Listado de variables de entorno |
| `frmSetupU.pas` | Configuración del sistema |
| `frmInicioSesionU.pas` | Login del sistema |
| `frmPrincipalU.pas` | Ventana principal (menú, navegación) — 3877 líneas |

### Módulo de Servicio Técnico (no aplica a avícola)
| Archivo | Función |
|---|---|
| `frmAmServicioTecnicoU.pas` | ABM servicio técnico |
| `frmAmEquipoU.pas` | ABM equipos |
| `frmListadoServiciosU.pas` | Listado servicios |
| `frmListadoEquiposU.pas` | Listado equipos |
| `frmlistadoHistoriaServU.pas` | Historial servicios |
| `frmListadoMovEquipoU.pas` | Movimientos equipos |
| `frmListadoEstadoServicioU.pas` | Estado de servicios |
| `frmListadoTipoServicioU.pas` | Tipos de servicio |

---

## 24. ANÁLISIS PROFUNDO DEL DATA MODULE (dmU.pas)

El `dmU.pas` es el archivo más crítico de toda la aplicación — 3367 líneas. Contiene **todos** los datasets, queries, stored procedures y tablas usadas por todos los formularios.

### Conexiones a base de datos
- `conexion: TMyConnection` — conexión principal a MySQL (MyDAC)
- `conexion2: TMyConnection` — segunda conexión (probablemente para sucursal remota o backup)

### Stored Procedures declarados
- `spActulizaStock: TMyStoredProc` — wraps `ActualizarStock`
- `spPorcentajeLista: TMyStoredProc` — wraps `porcentaje_lista`
- `MyStoredProc1: TMyStoredProc` — SP genérico adicional
- `qSp: TMyQuery` — query genérica para ejecutar cualquier SP/SQL inline

### Datasets clave y sus tablas
| Dataset | Tabla MySQL | Descripción |
|---|---|---|
| `tArticulo` / `qArticulo` | `articulo` | Artículos con 21 listas + 5 escalones mayorista |
| `tArticuloSuc2` | `articulo` (conexion2) | Artículos de sucursal remota |
| `tVenta` | `venta` | Ventas — 632K registros |
| `tDetalleVenta` | `det_venta` | Detalle ventas — 1.8M registros |
| `tDetallePago` | `detalle_pago` | Formas de pago por venta |
| `tCliente` | `cliente` | Clientes con saldo dólar, excepcion1/2 |
| `tArticuloTransito` | `articulos_transito` | Remitos/producciones (cabecera) |
| `tDet_at` | `det_at` | Detalle remitos/producciones |
| `tDet_prod` | `det_at` (filtrado) | Solo registros de producción |
| `tMovArticulo` | `movimiento_articulo` | Movimientos stock mayorista |
| `tMovArticuloMin` | `movimiento_articulo_minorista` | Movimientos stock minorista |
| `tMovVendedor` | `mov_vendedor` | Movimientos de stock de vendedor |
| `tCCcliente` | `cccliente` | Cuenta corriente clientes |
| `tCCProveedor` | `ccproveedor` | Cuenta corriente proveedores |
| `tCaja` | `caja` | Cajas con apertura/cierre |
| `tEgreso` | `egresos` | Egresos e ingresos de caja |
| `tCompra` | `compra` | Compras a proveedores |
| `tDetCompra` | `detalle_compra` | Detalle de compras |
| `tVendedor` | `vendedor` | Vendedores/empleados con reparto, sucursal2 |
| `tRemitoC` + `tDetRemitoC` | `remito_c` + `det_remito_c` | Remitos de compra a proveedor |
| `tPagoCompra` | `pago_compra` | Pagos de compras |
| `tListaCliente` | `cliente_precio` | Precios especiales por cliente/rubro |
| `tServicioTec` | `servicio_tecnico` | Servicio técnico (no aplica a avícola) |
| `tEquipo` | `equipo` | Equipos (no aplica) |
| `tCheque` | `cheque` | Cheques |
| `tPedProv` + `tdetPedProv` | `pedido_prov` + `det_pedido_prov` | Pedidos a proveedores |

### Campos artículo confirmados en dmU.pas
```
id_articulo, id_rubro, Codigo, Descripcion, presentacion, presentacion_caja,
cantidad_caja, precio_costo, margen, precio_unitario, precio_unitario_caja,
stock, stock_minimo, stock_inicial, stock_minorista, cantidad_por_caja,
alicuota_iva, modifica_stock, bonificacion, estado,
precio_unitario_iva, precio_caja_iva,
Lista2, Lista3, Lista4, lista5, lista6, lista7, lista8, lista9, lista10,
lista11, lista12, lista13, lista14, lista15, lista16, lista17, lista18,
lista19, lista20, lista21,
mayorista1, mayorista2, mayorista3, mayorista4, mayorista5,
descuento1, descuento2, descuento3, descuento4, descuento5,
art_mayorista, perdida, acepta_subrubro, oferta, caja, precio_kike, precio_kike2
```

### Sistema de doble stock confirmado
- `tArticulostock` → stock mayorista (remitos de reparto)
- `tArticulostock_minorista` → stock minorista (tienda/local)
- `tMovArticulo` / `tMovArticuloMin` → dos tablas separadas de movimientos

### Campos cliente confirmados
```
id_cliente, nombre, contacto, direccion, localidad, telefono, celular,
id_tipoiva, cuit, web, mail, saldo, saldodolar, observaciones, fax,
limitectacte, listaprecios, descuento, cumple, id_departamento,
c_corrriente, razon_social, condicionFical, id_vendedor, nombrevendedor,
id_doc_cliente, estado, reparto, codigo (número de cliente),
excepcion1, excepcion2, oferta
```
- `excepcion1`, `excepcion2`: IDs de artículos con precio especial fijo
- `oferta`: flag de cliente con descuentos de oferta activos

### Campos vendedor confirmados
```
id_vendedor, apellidonombre, domicilio, telefono, celular, saldo,
tipo_mov, codigo, estado, acepta_deposito, acepta_imputar,
id_sucursal, reparto, sucursal2
```
- `reparto`: es vendedor de reparto (sale con camión)
- `sucursal2`: tiene stock propio como segunda sucursal
- `acepta_deposito`, `acepta_imputar`: flags de gestión de CC

### qArticulo — campos adicionales en query
Además de los campos del artículo, la query principal incluye:
- `saldo`: saldo actual calculado (sum del movimiento)
- `rubro`, `rubros`: nombre del rubro
- `codigos`, `codigofin`: código con sufijo rubro
- Lista completa lista1-lista21 + mayorista1-5 + descuento1-5

### VentaB — Query separada para lista de ventas
Tiene campos: `sucursal`, `vta_sin_rem`, `vta_con_fact`, `cae`, `vto`,
`neto_21`, `neto_10_5`, `monto_iva21`, `monto_iva10_5`, `totalanulado`,
`percepcion`, `usuario`, `codigo`, `tipo_cbte`, `pto_vta`

### Tabla `tListaCliente` (cliente_precio)
Campos: `id_cliente_precio`, `id_cliente`, `id_rubro`, `id_articulo`, `oferta`, `lista`, `rubro`
- Por rubro: asigna lista de precio específica para ese cliente
- Por artículo: precio especial por artículo específico
- `oferta`: si el artículo está en oferta para ese cliente

### Exports a Excel
- `ArticuloToExcel: TQExport4XLS` — exporta artículos
- `DetallePesado: TQExport4Xlsx` — exporta detalle de pesada
- `MovStockCliente: TQExport4Xlsx` — exporta movimientos por cliente
- `VentasFacturadas: TQExport4Xlsx` — exporta ventas facturadas
- `QExportarDetAt: TQExport4SQL` — exporta det_at a SQL

### Queries de análisis y reportes
- `qFiletMila`: totales de filet, milanesas, rellenas, hamburguesas, picada (producción)
- `qSaldoClienteVendedor` / `qSaldoClienteVendedor2`: saldos clientes por vendedor
- `qPagosClienteVendedor`: pagos de clientes por vendedor con fecha y detalle
- `qCodigosClientesVendedor`: códigos de clientes por vendedor
- `qResumenCajero`: resumen de caja por cajero
- `qVentasFacturadas`: ventas con CAE para informes AFIP
- `qDescartes`: descartes con precio_costo y subtotal
- `qGeneraPesado` / `qPesado`: generación e historial de pesadas
- `qGenPesCompra*`: cálculo de compras para pesada (COMPRAS, DEVCOMPRAS, DEVOLUCIONESSUCURSAL, INGRESOPRODUCCIONES)

### Variables de entorno relevantes
Consultadas en `uFacturacionElectronica.pas`:
- `limite_efectivo` — monto máximo para CF sin DNI
- `limite_debito` — monto máximo para CF débito sin DNI
- `limite_ventas` — límite general de ventas

### Actualizaciones de precio (a_articulo / actualizacion)
- `tListaCliente` + `qDetalleActualizacion` + `qHistorialActualizacion`: sistema de actualización masiva de precios con historial
- `qArticulosActualizados`: artículos que cambiaron en última actualización
- `qUltActPrecios`: fecha de última actualización

---

## 25. MÓDULO AFIP — FACTURACIÓN ELECTRÓNICA (uFacturacionElectronica.pas)

### Endpoints de producción
```pascal
URLWSAA = 'https://wsaa.afip.gov.ar/ws/services/LoginCms';
URLWSW = 'https://servicios1.afip.gov.ar/wsfev1/service.asmx';
```

### Configuración por sucursal (rdb.ini)
```ini
[datosempresa]
cuit = ...
RazonSocial = ...
Direccion = ...
SituacionFiscal = ...
fechainicio = ...

[Sucursal]
certificadop = frango.crt   ; certificado PKCS#12 por sucursal
clavep = clave.key
```

### Procedimientos principales
1. `factura(PuntoVenta, TipoCbte, TipoDoc, idventa)` — facturación completa
2. `autorizar_comprobante(tipo_factura, TipoCbte)` — solo autorización (controlador fiscal)
3. `nro_factura_ccc(numerofactura, idventa)` — graba nro en cuenta corriente
4. `reimpresion_factura(PuntoVenta, TipoCbte, TipoDoc, idventa)` — reimpresión

### Tipos de comprobante AFIP
| Código | Tipo |
|---|---|
| 1 | Factura A |
| 2 | Nota de Débito A |
| 3 | Nota de Crédito A |
| 6 | Factura B |
| 7 | Nota de Débito B |
| 8 | Nota de Crédito B |
| 11 | Factura C |
| 12 | Nota de Débito C |
| 13 | Nota de Crédito C |

### Tipos de documento de cliente
| Código AFIP | Tipo |
|---|---|
| 80 | CUIT (válido) |
| 96 | DNI |
| 99 | Sin datos / CF |

### Lógica de documento para Factura B
```
total > limite_efectivo:
  cliente = CF (id 1113):
    CUIT válido → doc 80, cuitcliente
    CUIT inválido → doc 96, dni_vtadiaria
  otros clientes:
    total > limite_debito:
      CF débito (id 1111 o 1122):
        CUIT válido → doc 80
        inválido → doc 96, dni
      otros:
        CUIT válido → doc 80
        inválido → doc 99 (sin datos)
    total <= limite_debito:
      CUIT válido → doc 80
      inválido → doc 99

total <= limite_efectivo:
  CUIT válido → doc 80
  inválido → doc 99
```

### Stored Procedures llamados durante facturación
```sql
-- 1. Guarda CAE y datos en venta
CALL datos_factura(:ptoventa, :nrofactura, :tipocbte, :idventa, :cae, :vto, :vtaconfact, :nronotacredito)

-- 2. Guarda detalle (vincula det_venta con remito de compra)
CALL datos_detalle_factura(:param_id_detalle, :param_id_remito, :param_id_talle, :param_precio_factura, :param_sub_total_factura)

-- 3. Actualiza IVA calculado en la factura
CALL datos_factura_iva(:iva_21, :iva_10_5, :neto_21, :neto_10_5, :texto_remito, :id_venta)

-- 4. Inserta ajuste de consignación
CALL insertar_det_venta(:param_id_detalle, :param_id_venta, :param_id_articulo, :param_id_remito, :param_cantidad, :param_precio, :param_sub_total, :param_estado, :param_id_talle, :param_dcto, :param_presentacion, :param_lista, :param_codigo, :param_precio_costo, :param_id_tabla, :param_bultos, :param_precio_factura, :param_sub_total_factura)

-- 5. Graba nro de factura en cuentacorriente  
CALL nro_factura_ccc(:nrofactura, :idventa, :idcliente)

-- 6. Validación CUIT
SELECT fn_ValidarCUIT(:cuit) AS validado

-- 7. Variables de entorno
SELECT valor FROM variable_entorno WHERE descripcion = 'limite_efectivo'
SELECT valor FROM variable_entorno WHERE descripcion = 'limite_debito'
SELECT valor FROM variable_entorno WHERE descripcion = 'limite_ventas'
```

### Lógica de consignación (venta de mercadería en consignación)
Para facturas A, B tipo 1/6/11 (no notas de crédito/débito):
- Recorre cada det_venta buscando `qUltimoRemitoFecha` (último remito de compra del artículo)
- Si la marca tiene comisión (`comision != 0`), calcula:
  ```
  consignacion = precio_compra × cantidad × comision
  diferencia_consignacion = sub_total_venta - consignacion
  ```
- Por rubro especial (id_proveedor=1148) aplica 1.10 fijo
- Al final inserta líneas de ajuste en det_venta:
  - Artículo 1121 (ajuste IVA 21%) con `sumaconsignacion21`
  - Artículo 1105 (ajuste IVA 10.5%) con `sumaconsignacion10_5`

### IVA split en tiempo de facturación
```pascal
// Para cada detalle:
if alicuota_iva = 21 then
  neto21 += sub_total / 1.21
  iva21 += sub_total - (sub_total / 1.21)
else  // 10.5
  neto10_5 += sub_total / 1.105
  iva10_5 += sub_total - (sub_total / 1.105)
```

### Variables AFIP en reporte FastReport
```
ncbte, emision, cae, vto, cuit, condicion, razonsocial, domicilio,
razonsocialemisor, domicilioemisor, situacionfiscal, ib, fechainicio,
CompAsoc, ncbteasoc
```

### Percepción IVA en AFIP
Se envía como tributo separado:
```pascal
// Tributo tipo 2 = Percepciones IIBB (ARBA)
AgregaTributo(2, 'Percepciones IIBB', total, porcpercep, percepcion)
// Tributo tipo 1 = Percepciones IVA 21%
AgregaTributo(1, 'Percepciones IVA 21', total, 3, percepiva21)
// Tributo tipo 1 = Percepciones IVA 10.5%
AgregaTributo(1, 'Percepciones IVA 10,5', total, 1.5, percepiva10_5)
```

---

## 26. INVENTARIO COMPLETO DE STORED PROCEDURES

Recopilación de todos los SPs y funciones encontrados en el código Delphi:

### SPs de movimiento de stock
| SP | Propósito |
|---|---|
| `nueva_variable('nro_produccion')` | Genera nuevo número de producción |
| `nueva_produccion(...)` | Crea cabecera de producción en `articulos_transito` |
| `ActualizarStock(id_articulo, tipo, cantidad)` | Actualiza stock ('V'=venta, 'C'=compra, etc.) |
| `ActualizarPrecioU(id_articulo, precio_unitario, precio_costo)` | Actualiza precios del artículo |
| `porcentaje_lista(...)` | Calcula precio por lista de precio |

### SPs de movimientos contables
| SP | Propósito |
|---|---|
| `acc_articulo(...)` | Registra movimiento en `movimiento_articulo` |
| `acc_vendedor(...)` | Registra movimiento en `mov_vendedor` |
| `acc_usuario(...)` | Registra movimiento en tabla usuario |

### SPs de facturación AFIP
| SP | Propósito |
|---|---|
| `datos_factura(...)` | Guarda CAE, nro_factura, tipo_cbte en venta |
| `datos_detalle_factura(...)` | Vincula det_venta con remito de compra |
| `datos_factura_iva(...)` | Actualiza IVA calculado (split 21/10.5) |
| `insertar_det_venta(...)` | Inserta línea de ajuste de consignación |
| `nro_factura_ccc(...)` | Graba nro factura en cuenta corriente |

### Funciones SQL
| Función | Propósito |
|---|---|
| `fn_ValidarCUIT(:cuit)` | Valida dígito verificador del CUIT, retorna 0/1 |

### Queries especiales
- `qUltimoRemitoFecha`: busca el remito de compra más reciente para un artículo y cantidad, usado en consignación
- `qGenPesCompra`: calcula compras del período para generar la pesada (COMPRAS + DEV_COMPRAS + DEV_SUCURSAL + INGRESO_PRODUCCIONES)
- `qFiletMila`: totales de producción agrupados por tipo (filet, milanesas, rellenas, hamburguesas, picada)

---

## 27. NOTAS CRÍTICAS PARA LA IMPLEMENTACIÓN LARAVEL

### A. Estructura de la base de datos avícola (diferencias clave vs SaaS)

#### Tabla `articulo` — campos adicionales requeridos
```sql
-- Stock dual
stock_minorista FLOAT DEFAULT 0,
cantidad_por_caja FLOAT DEFAULT 1,

-- 21 listas de precio
lista1 FLOAT,  -- = precio_unitario
lista2 FLOAT, lista3 FLOAT, lista4 FLOAT, lista5 FLOAT, lista6 FLOAT,
lista7 FLOAT, lista8 FLOAT, lista9 FLOAT, lista10 FLOAT, lista11 FLOAT,
lista12 FLOAT, lista13 FLOAT, lista14 FLOAT, lista15 FLOAT, lista16 FLOAT,
lista17 FLOAT, lista18 FLOAT, lista19 FLOAT, lista20 FLOAT, lista21 FLOAT,

-- Escalones de volumen mayorista
mayorista1 FLOAT, mayorista2 FLOAT, mayorista3 FLOAT, mayorista4 FLOAT, mayorista5 FLOAT,
descuento1 FLOAT, descuento2 FLOAT, descuento3 FLOAT, descuento4 FLOAT, descuento5 FLOAT,

-- Flags avícola
art_mayorista TINYINT(1) DEFAULT 0,
perdida FLOAT DEFAULT 3.0,        -- % de merma esperado
acepta_subrubro TINYINT(1) DEFAULT 0,  -- puede tener sub-artículos
oferta TINYINT(1) DEFAULT 0,
caja TINYINT(1) DEFAULT 0,        -- se vende solo en caja
```

#### Tabla `cliente` — campos adicionales
```sql
saldo_dolar FLOAT DEFAULT 0,      -- saldo en dólares
id_doc_cliente INT,               -- DNI para CF
excepcion1 INT,                   -- id_articulo con precio especial 1
excepcion2 INT,                   -- id_articulo con precio especial 2
precio_excepcion1 FLOAT,
precio_excepcion2 FLOAT,
reparto TINYINT(1) DEFAULT 0,    -- es cliente de reparto
codigo INT UNIQUE,                -- código numérico visible en pantalla
oferta TINYINT(1) DEFAULT 0,
id_vendedor INT,                  -- vendedor asignado
```

#### Tabla `vendedor` — campos adicionales
```sql
id_sucursal INT,
reparto TINYINT(1) DEFAULT 0,    -- sale con camión
sucursal2 TINYINT(1) DEFAULT 0,  -- tiene stock propio
acepta_deposito TINYINT(1) DEFAULT 0,
acepta_imputar TINYINT(1) DEFAULT 0,
saldo FLOAT DEFAULT 0,
```

#### Tabla `venta` — campos adicionales vs SaaS
```sql
nro_venta INT,                   -- número correlativo de venta (≠ nro_factura)
letra_comprobante CHAR(1),       -- A, B, C
tipo_factura INT,                -- código AFIP (1,6,11...)
tipo_cbte INT,                   -- alias de tipo_factura
pto_vta INT,                     -- punto de venta AFIP
cae VARCHAR(14),
vto VARCHAR(8),                  -- vencimiento CAE
neto_21 FLOAT DEFAULT 0,
neto_10_5 FLOAT DEFAULT 0,
monto_iva21 FLOAT DEFAULT 0,
monto_iva10_5 FLOAT DEFAULT 0,
percepcion FLOAT DEFAULT 0,      -- percepción IIBB (ARBA)
percepiva21 FLOAT DEFAULT 0,     -- percepción IVA 21%
percepiva10_5 FLOAT DEFAULT 0,   -- percepción IVA 10.5%
porcpercep FLOAT DEFAULT 0,      -- tasa de percepción IIBB aplicada
mayorista TINYINT(1) DEFAULT 0,
id_sucursal INT,
id_vendedor INT,                  -- vendedor/reparto que realizó la venta
cliente_vtadiaria VARCHAR(100),  -- nombre CF en venta diaria
dni_vtadiaria VARCHAR(20),       -- DNI CF en venta diaria
domicilio_vtadiaria VARCHAR(200),
vta_sin_rem TINYINT(1),
vta_con_fact TINYINT(1),
totalanulado FLOAT DEFAULT 0,
tipo CHAR(1),                    -- P=pendiente, F=facturada, A=anulada
```

#### Tabla `det_venta` — campos adicionales
```sql
lista INT,                       -- lista de precio usada (1-21)
presentacion VARCHAR(20),        -- KILO / CAJA
precio_factura FLOAT,            -- precio en la factura (puede diferir de precio)
sub_total_factura FLOAT,         -- subtotal en la factura
id_remito INT,                   -- remito de compra origen (consignación)
cajas FLOAT,                     -- cantidad de cajas
bultos FLOAT,
cantidad_por_caja FLOAT,
id_rubro INT,                    -- para cálculo de IVA split
alicuota_iva FLOAT,
art_mayorista TINYINT(1),
precio_costo FLOAT,              -- costo al momento de la venta
```

#### Nueva tabla `movimiento_articulo_minorista`
Idéntica a `movimiento_articulo` pero para stock minorista independiente.

#### Nueva tabla `cliente_precio`
```sql
id_cliente_precio INT PRIMARY KEY AUTO_INCREMENT,
id_cliente INT NOT NULL,
id_rubro INT,                    -- NULL = aplica a artículo específico
id_articulo INT,                 -- NULL = aplica a todo el rubro
lista INT,                       -- número de lista (1-21)
oferta TINYINT(1) DEFAULT 0,
```

#### Nueva tabla `sub_articulo`
```sql
id_sub_articulo INT PRIMARY KEY AUTO_INCREMENT,
id_articulo INT NOT NULL,        -- artículo padre (cajón/paleta)
id_articulo_resultado INT,       -- artículo hijo resultante
descripcion VARCHAR(200),
rendimiento FLOAT,               -- kg resultantes por kg de entrada
lista1..lista21 FLOAT,           -- propios precios
mayorista1..5 FLOAT,
descuento1..5 FLOAT,
```

### B. Módulos en orden de implementación sugerido

**Fase 1 — Base**
1. Migraciones con todos los campos correctos
2. Models con relaciones
3. Seeders (rubros, marcas, artículos)
4. Autenticación Jetstream + roles (admin, cajero, vendedor, supervisor, almacen)

**Fase 2 — Compras**
5. Proveedores + CUIT validation
6. Compras + detalle (entrada de cajones)
7. MovimientoArticulo (stock mayorista sube)

**Fase 3 — Producción**
8. Producción (apertura cajones → sub-artículos)
9. Descarte/Merma
10. Pesada/Inventario físico

**Fase 4 — Ventas**
11. Clientes con todos los campos + ClientePrecio
12. Venta mayorista (canal reparto) con:
    - 21 listas de precio
    - Escalones de volumen
    - Presentación KILO/CAJA
    - Formas de pago (Efectivo, Débito, Transferencia, Dólares)
    - IVA split (21% vs 10.5%)
13. Percepción IIBB (ARBA) para RI + umbral $3000 percepción IVA
14. AFIP WSFEv1 (usando package Laravel + FEAFIPLib o API wrapper)

**Fase 5 — Remitos de reparto**
15. ArticulosTransito + DetAt (sistema de reparto)
16. Facturación desde remito (tipo 2)

**Fase 6 — Caja**
17. Caja (apertura/cierre)
18. Egresos/Ingresos de caja
19. Cierre con resumen por forma de pago

**Fase 7 — Informes**
20. Ventas por período, por vendedor, por artículo
21. Stock actual (mayorista + minorista)
22. CuentaCorriente clientes
23. Pesadas históricas

### C. Decisiones de arquitectura recomendadas

1. **Los SPs de MySQL deben recrearse**: `ActualizarStock`, `acc_articulo`, `acc_vendedor`, `acc_usuario`, `nueva_produccion`, `nueva_variable`, `datos_factura`, `fn_ValidarCUIT`, etc. Son la lógica de negocio más crítica y están probados en producción.

2. **No intentar eliminar los SPs y reescribirlos en PHP**: el riesgo de bugs es alto. Llamarlos desde Laravel con `DB::statement('CALL sp_name(...)')` o `DB::select`.

3. **La percepción ARBA** requiere el servicio web `lwsPadron2.ConsultaAlicuota` — en Laravel se debe consumir el webservice XML de ARBA directamente (no existe un package PHP popular, hay que implementarlo).

4. **La validación CUIT** ya está en `fn_ValidarCUIT` como función MySQL. También implementar en PHP para validación en tiempo real en frontend.

5. **21 listas de precio** — en lugar de columnas separadas (lista1..lista21), considerar una tabla pivote `articulo_lista_precio` (articulo_id, lista_numero, precio). Más flexible pero requiere JOIN.

6. **`variable_entorno`** — tabla crítica. Toda la configuración del sistema (secuencias de facturas, límites, credenciales AFIP por sucursal) vive aquí.

7. **Modo offline**: el Delphi opera con MySQL local y sincroniza. En Laravel, si hay múltiples sucursales, evaluar si usar un único servidor central o replicación.

8. **FastReport → DomPDF**: los reportes en Delphi usan `.fr3` templates. En Laravel usar DomPDF + Blade templates para las facturas. Los campos del template AFIP están mapeados en la sección 25.

9. **FEAFIPLib_TLB**: la librería COM de AFIP no está disponible en PHP. Usar el package `afipphp/sdk` o la librería `iutlabs/afip` que implementa WSFEv1 en PHP nativo.

10. **Variables de entorno del rdb.ini** → migrar a `variable_entorno` en BD o a `.env` de Laravel según si es por-sucursal o global.

---

*Documento generado el 2026-04-25. Versión 3.0 — incluye análisis completo de dmU.pas (DataModule), uFacturacionElectronica.pas (AFIP), inventario de 100+ módulos Delphi, y notas de implementación Laravel.*