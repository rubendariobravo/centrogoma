# Análisis Completo de la Aplicación SaaS
## Base para refactorización hacia sistema de gestión avícola

**Fecha de análisis:** 2026-04-25  
**Ruta fuente:** `C:\laragon\www\saas`  
**Destino del proyecto:** `C:\laragon\www\avicola`

---

## 1. STACK TECNOLÓGICO

| Componente | Tecnología | Versión |
|---|---|---|
| Framework Backend | Laravel | ^10.10 |
| Lenguaje | PHP | ^8.1 |
| Autenticación | Laravel Jetstream + Sanctum | ^4.3 / ^3.3 |
| Permisos | Spatie Laravel Permission | ^6.18 |
| Frontend Reactivo | Livewire | ^3.0 |
| JS Alpine | Alpine.js | ^3.14.9 |
| CSS Framework | Tailwind CSS | ^3.1.0 |
| Build Tool | Vite | ^5.0.0 |
| PDF | barryvdh/laravel-dompdf | ^3.1 |
| QR Codes | chillerlan/php-qrcode + endroid/qr-code | ^5.0 |
| Excel Export | phpoffice/phpspreadsheet | ^4.3 |
| CSV | league/csv | ^9.23 |
| Pagos Online | mercadopago/dx-php | 2.5 |
| Log Viewer | rap2hpoutre/laravel-log-viewer | ^2.5 |
| HTTP Client | guzzlehttp/guzzle | ^7.2 |
| Idioma | laravel-lang/common | ^6.4 |
| BD ORM | Eloquent (incluido en Laravel) | — |

---

## 2. NEGOCIO ORIGINAL: CentroGoma

La aplicación fue desarrollada originalmente para una empresa llamada **CentroGoma**, que por las entidades de datos y módulos se dedica a:
- Venta de productos (artículos con múltiples listas de precios, en ARS y USD)
- Compra a proveedores
- Contratos de servicio con clientes
- Gestión de vehículos y choferes (flota propia)
- Artefactos y mantenimiento de extintores (servicio a clientes)
- Obras y centro de costos de construcción
- Tienda web con carrito y MercadoPago
- Facturación electrónica AFIP (Argentina)

---

## 3. ARQUITECTURA Y ESTRUCTURA DE DIRECTORIOS

```
saas/
├── app/
│   ├── Actions/              # Acciones Jetstream (login, registro, equipos)
│   ├── Console/              # Comandos Artisan
│   ├── Exceptions/           # Handler de excepciones
│   ├── Exports/              # Clases de exportación Excel
│   ├── Helpers/              # Funciones helper globales
│   ├── Http/
│   │   ├── Controllers/      # 65+ controladores (ver sección 6)
│   │   │   ├── Api/          # 2 controladores de API REST
│   │   │   └── Auth/         # Controladores de autenticación
│   │   └── Middleware/       # 15 middlewares personalizados
│   ├── Listeners/            # Event Listeners
│   ├── Mail/                 # Clases de email
│   ├── Models/               # 60+ modelos Eloquent (ver sección 5)
│   ├── Notifications/        # Notificaciones
│   ├── Providers/            # Service Providers
│   ├── Services/
│   │   ├── Afip/             # Servicio AFIP (AfipService, Wsaa, Wsfev1)
│   │   ├── MovimientoArticuloService.php
│   │   └── SalidaInstalacionService.php
│   └── View/                 # View Composers
├── bootstrap/
├── config/
├── database/
│   ├── migrations/           # 135+ migraciones
│   ├── seeders/              # 28 seeders
│   └── factories/
├── docs/                     # Documentación interna
├── lang/                     # Traducciones
├── public/
├── resources/
│   └── views/               # 50+ carpetas de vistas Blade
├── routes/
│   ├── web.php              # 200+ rutas web
│   ├── api.php              # 8 rutas API
│   ├── tienda_routes.php    # Rutas tienda
│   ├── channels.php
│   └── console.php
├── storage/
├── tests/
└── vendor/
```

---

## 4. BASE DE DATOS — MODELOS Y ENTIDADES

### 4.1 Entidades Principales

#### VENTAS Y COMPROBANTES
| Tabla | Propósito |
|---|---|
| `venta` | Facturas de venta, notas de crédito y débito (polimórfica) |
| `det_venta` | Líneas de detalle de cada venta |
| `pago_v` | Formas de pago registradas en ventas |
| `comprobante` | Tipos de comprobantes genéricos |
| `documento_fiscal` | Tipos de documentos AFIP (Factura A/B/C, NC, ND) |
| `doc_afip` | Registro de documentos AFIP emitidos |
| `recibos` | Recibos de cobro a clientes |
| `recibo_detalles` | Detalle de facturas cubiertas por cada recibo |
| `recibo_formas_pago` | Formas de pago de recibos |

#### CLIENTES
| Tabla | Propósito |
|---|---|
| `cliente` | Datos maestros de clientes |
| `cliente_sucursales` | Sucursales/direcciones de entrega de clientes |
| `cccliente` | Cuenta corriente de clientes (polimórfica) |
| `retenciones` | Retenciones impositivas de clientes |
| `retencion_detalle` | Detalles de retenciones por venta |

#### ARTÍCULOS / PRODUCTOS
| Tabla | Propósito |
|---|---|
| `articulo` | Catálogo de artículos con hasta 6 listas de precios, stock, IVA |
| `marca` | Marcas de productos |
| `rubro` | Categorías/rubros de productos |
| `stock` | Control de stock (tabla separada) |
| `movimientos_articulos` | Historial de movimientos de stock |
| `articulos_transito` | Artículos en tránsito |

#### COMPRAS Y PROVEEDORES
| Tabla | Propósito |
|---|---|
| `proveedor` | Datos maestros de proveedores |
| `compra` | Facturas de compra recibidas |
| `detalle_compra` | Líneas de detalle de compras |
| `pago_c` | Pagos a proveedores |
| `ccproveedor` | Cuenta corriente de proveedores (polimórfica) |
| `ordenes_pago` | Órdenes de pago a proveedores |
| `detalle_orden_pago` | Facturas incluidas en cada OP |
| `pago_orden_pago` | Formas de pago de las OP |

#### CAJA Y TESORERÍA
| Tabla | Propósito |
|---|---|
| `caja` | Sesiones de caja (apertura/cierre) |
| `movimientos_caja` | Movimientos individuales de caja |
| `cuentas_fondos` | Cuentas de fondos (efectivo, banco, etc.) |
| `cheque` | Cheques recibidos/emitidos |
| `movimiento_cheque` | Historial de estados de cheques |
| `pago_aceptado` | Pagos aceptados (tarjetas, transferencias) |
| `acreditaciones` | Acreditaciones bancarias |
| `banco` | Bancos |
| `plan_cuenta` | Plan de cuentas contable |

#### UBICACIONES GEOGRÁFICAS
| Tabla | Propósito |
|---|---|
| `pais` | Países |
| `provincias` | Provincias (incluye todas las de Argentina) |
| `departamentos` | Departamentos/partidos |

#### MÓDULOS ESPECÍFICOS (CentroGoma)
| Tabla | Propósito |
|---|---|
| `artefactos` | Extintores y equipos de clientes |
| `mantenimiento_extintores` | Historial de mantenimientos |
| `contratos` | Contratos de servicio con clientes |
| `vehiculos` | Flota vehicular |
| `choferes` | Conductores |
| `asignaciones_vehiculos` | Asignaciones de choferes a vehículos |
| `gastos` | Gastos de vehículos |
| `tipos_gastos` | Tipos de gastos de flota |
| `obras` | Proyectos de construcción |
| `gastos_obra` | Gastos por obra |
| `categorias_gastos_obra` | Categorías de gastos de obra |
| `salidas_instalacion` | Salidas de artículos para instalación |
| `detalle_salida_instalacion` | Detalle de artículos por salida |
| `empleados` | Empleados |
| `departamentos_empresa` | Departamentos de la empresa |
| `anticipo_empleados` | Anticipos de sueldo |
| `gastos_generales` | Gastos generales de la empresa |
| `categorias_gasto_general` | Categorías de gastos generales |

#### E-COMMERCE
| Tabla | Propósito |
|---|---|
| `carritos` | Carritos de compra de la tienda web |
| `carrito_items` | Items del carrito |

#### CONFIGURACIÓN
| Tabla | Propósito |
|---|---|
| `variable_entorno` | Variables de configuración del sistema |
| `tipoiva` | Tipos de IVA (Responsable Inscripto, Monotributista, etc.) |
| `cotizaciones` | Cotizaciones de moneda (USD/ARS) |
| `puntos` | Puntos de venta AFIP |
| `deposito` | Depósitos/almacenes |

---

## 5. MODELOS ELOQUENT — RELACIONES CLAVE

### Modelo `Venta`
- `hasMany` DetVenta (detalles)
- `hasMany` PagoV (pagos)
- `belongsTo` User (vendedor)
- `belongsTo` Cliente
- `belongsTo` DocumentoFiscal (tipo factura)
- `belongsTo` ClienteSucursal (sucursal de entrega)
- `morphMany` CcCliente (polimórfica: origen en cuenta corriente)
- `belongsToMany` Recibo (pivot: recibo_detalles)
- `belongsTo` Venta (factura original — para NC/ND)
- `hasMany` Venta (notas de crédito)
- `belongsToMany` Retencion (pivot: retencion_detalle)

### Modelo `Articulo`
- `belongsTo` Marca
- `belongsTo` Rubro
- `hasMany` CarritoItem
- `hasMany` MovimientoArticulo
- Lógica de precios: 6 listas + soporte ARS/USD con cotización dinámica
- Cálculo: `precio_costo → margen → flete → bonificación → IVA`
- Para USD: `precio_base_usd × cotización → dcto → flete → ganancia → IVA`

### Modelo `Cliente`
- `belongsTo` TipoIva
- `belongsTo` Departamento
- `hasOneThrough` Provincia (via Departamento)
- `belongsTo` DocAfip (tipo documento)
- `hasMany` CcCliente
- `hasOne` User (usuario de tienda)
- `hasMany` ClienteSucursal
- `hasMany` Artefacto
- `hasMany` Retencion

### Modelo `User`
- Usa: HasApiTokens, HasProfilePhoto, TwoFactorAuthenticatable, HasRoles (Spatie)
- `hasMany` Carrito
- `hasOne` Cliente
- Al crear: asigna automáticamente rol `cliente`

### Modelo `Caja`
- `belongsTo` User
- `hasMany` PagoV
- `hasMany` MovimientoCaja
- `belongsTo` CuentaFondo
- Atributos calculados: saldo_actual, saldo_total, total_ingresos, total_egresos, total_ingresos_bancarios

### Modelo `Compra`
- `belongsTo` Proveedor
- `belongsTo` DocumentoFiscal (tipo factura)
- `belongsTo` Caja
- `hasMany` DetalleCompra
- `hasMany` PagoC
- `morphMany` CcProveedor
- Atributo calculado: total_pendiente, esta_pagada

---

## 6. CONTROLADORES — FUNCIONALIDADES

### Ventas y Facturación
| Controlador | Funciones principales |
|---|---|
| `VentaController` | CRUD ventas, búsqueda artículos/clientes, precios por lista, CAE AFIP, PDF factura, ticket, email, export Excel |
| `VentaSimplificadaController` | Venta rápida simplificada |
| `NotaCreditoController` | Emisión NC con CAE AFIP |
| `NotaDebitoController` | Emisión ND con CAE AFIP |
| `FacturaController` | Generación de facturas AFIP vía API |
| `FacturaEmailController` | Envío de facturas por email |
| `ReciboController` | CRUD recibos, PDF, estado de cuenta |
| `RetencionController` | Retenciones impositivas |
| `AcreditacionController` | Acreditaciones bancarias de tarjetas |

### Clientes
| Controlador | Funciones |
|---|---|
| `ClienteController` | CRUD, cuenta corriente (4 vistas), exportar PDF/Excel, facturas pendientes, búsqueda |
| `ClienteSucursalController` | API de sucursales de cliente |
| `InformeClienteController` | Facturas pendientes/vencidas, PDF/Excel |

### Artículos
| Controlador | Funciones |
|---|---|
| `ArticuloController` | CRUD, actualización masiva de precios, búsqueda |
| `MovimientoArticuloController` | Historial de stock, ajuste, verificar/corregir consistencia, exportar |
| `RubroController` | CRUD rubros, filtrar por marca |
| `MarcaController` | CRUD marcas |
| `CotizacionController` | Gestión de cotizaciones USD/ARS |

### Caja y Tesorería
| Controlador | Funciones |
|---|---|
| `CajaController` | CRUD cajas, apertura/cierre con arqueo, PDF resumen |
| `MovimientoCajaController` | Registrar ingresos/egresos manuales |
| `CuentaFondoController` | Libro mayor, exportar Excel, actualizar saldo |
| `ChequeController` | Gestión de cheques, cambio de estado, venta |

### Compras y Proveedores
| Controlador | Funciones |
|---|---|
| `ProveedorController` | CRUD, importar CSV, cuenta corriente, test |
| `CompraController` | CRUD compras, búsqueda, verificar duplicados, registrar pago, corregir forma pago |
| `OrdenPagoController` | CRUD OP, buscar facturas pendientes, registrar pago, PDF, email |
| `InformeProveedorController` | Facturas pendientes/vencidas, PDF/Excel/Email |

### Módulos Específicos CentroGoma (a eliminar/reemplazar)
| Controlador | Propósito |
|---|---|
| `ArtefactoController` | Extintores de clientes |
| `MantenimientoExtintorController` | Mantenimientos de extintores |
| `ContratoController` | Contratos de servicio |
| `VehiculoController` | Flota vehicular |
| `ChoferController` | Conductores |
| `AsignacionVehiculoController` | Asignaciones flota |
| `GastoController` | Gastos de vehículos |
| `ObraController` | Proyectos de construcción |
| `GastoObraController` | Gastos por obra |
| `CategoriaGastoObraController` | Categorías de obra |
| `ReporteCentroCostosController` | Reportes de centro de costos |
| `RentabilidadContratosController` | Rentabilidad por contrato |
| `SalidaInstalacionController` | Salidas de artículos para instalación |
| `EmpleadoController` | Empleados |
| `DepartamentoEmpresaController` | Departamentos empresa |
| `AnticipoEmpleadoController` | Anticipos de sueldo |
| `GastoGeneralController` | Gastos generales |

### E-Commerce / Tienda
| Controlador | Funciones |
|---|---|
| `TiendaController` | Catálogo público, filtro por marca/rubro, búsqueda |
| `CarritoController` | Agregar/actualizar/eliminar ítems, mini-carrito |
| `CheckoutController` | Proceso de compra, historial, detalle |
| `MercadoPagoController` | Integración MercadoPago |
| `PerfilController` | Perfil usuario tienda, gestión cliente |

### Administración
| Controlador | Funciones |
|---|---|
| `DashboardController` | Panel de control |
| `UsuarioController` | CRUD usuarios |
| `RolController` | CRUD roles |
| `PermisoController` | CRUD permisos |
| `VariableEntornoController` | Variables de configuración |
| `DepartamentoController` | Localidades |
| `ProvinciaController` | Provincias |
| `PaisController` | Países + provincias |

---

## 7. SISTEMA DE AUTENTICACIÓN Y ROLES

### Tecnología
- **Laravel Jetstream** + **Laravel Fortify** para autenticación
- **Laravel Sanctum** para tokens API
- **Spatie Laravel Permission** para roles y permisos
- Autenticación de 2 factores disponible (Fortify)

### Roles predefinidos
| Rol | Descripción |
|---|---|
| `admin` | Acceso total al sistema (todos los permisos) |
| `cliente` | Solo acceso a la tienda web |

### Permisos del sistema
```
Dashboard:        access_dashboard
Clientes:         ver/crear/editar/eliminar_clientes
Artículos:        ver/crear/editar/eliminar_articulos
Ventas:           ver/crear/editar/eliminar/facturar_ventas
Cajas:            ver/crear/editar/cerrar_cajas
Proveedores:      ver/crear/editar/eliminar_proveedores
Compras:          ver/crear/editar/eliminar_compras
Órdenes Pago:     ver/crear/editar/eliminar_ordenes_pago
Informes:         ver_informes / exportar_informes
Usuarios:         ver/crear/editar/eliminar_usuarios
Roles:            ver/crear/editar/eliminar_roles
Tienda:           access_store / make_purchases / view_own_purchases
                  acceso_tienda / acceso_carrito / realizar_compras
```

### Middleware de acceso
| Middleware | Función |
|---|---|
| `CheckDashboardAccess` | Verifica `access_dashboard` para el panel admin |
| `CheckRole` | Verifica rol específico |
| `CheckClientRole` | Verifica rol de cliente |
| `CheckShopEnabled` | Controla si la tienda está habilitada |
| `RestrictShopAccess` | Restricción de acceso a tienda |
| `CheckSessionTimeout` | Timeout de sesión |

---

## 8. INTEGRACIÓN AFIP (FACTURACIÓN ELECTRÓNICA)

### Servicio AFIP
- **Ruta:** `app/Services/Afip/`
- **Componentes:**
  - `AfipService.php` — Orquestador principal
  - `Wsaa.php` — Web Service de Autenticación y Autorización
  - `Wsfev1.php` — Web Service de Facturación Electrónica V1

### Funcionalidades
- Obtención de Token y Sign (TA) vía WSAA
- Autorización de comprobantes (CAE) vía WSFEv1
- Soporte: Facturas A, B, C — Notas de Crédito — Notas de Débito
- Punto de venta configurable por variable de entorno
- Tabla `doc_afip` registra cada comprobante autorizado
- Rutas de prueba: `/afip/test-wsaa`, `/afip/test-ultimo-comp`

---

## 9. MÓDULO DE TIENDA WEB (E-COMMERCE)

### Arquitectura
- Acceso público en `/tienda`
- Middleware `restrict.shop` y `CheckShopEnabled` controlan visibilidad
- Carrito persistido en BD (tabla `carritos` + `carrito_items`)
- Checkout con integración MercadoPago

### Flujo del cliente
1. Explorar catálogo → filtrar por marca/rubro → buscar
2. Agregar al carrito (autenticado o sesión)
3. Checkout → pago por MercadoPago
4. Confirmación y historial de compras
5. Perfil: asociar cliente existente o crear nuevo

---

## 10. VARIABLES DE ENTORNO Y CONFIGURACIÓN

La tabla `variable_entorno` permite configurar el sistema dinámicamente:
- Datos de la empresa (razón social, CUIT, domicilio)
- Configuración AFIP (punto de venta, certificados)
- Habilitación/deshabilitación de módulos
- Parámetros de MercadoPago

---

## 11. EXPORTACIÓN E INFORMES

| Módulo | Formatos disponibles |
|---|---|
| Ventas | Excel (export) |
| Cuenta corriente cliente | PDF, Excel (4 variantes de vista) |
| Informes clientes | PDF, Excel |
| Informes proveedores | PDF, Excel, Email |
| Informes ventas | Ranking artículos: PDF, Excel |
| Gastos vehículos | PDF, Excel |
| Gastos de obra | PDF, Excel |
| Cajas | PDF resumen |
| Recibos | PDF |
| Órdenes de pago | PDF, Email |
| Libro mayor cuentas | Excel |
| Movimientos artículo | Export |

---

## 12. MÓDULOS A CONSERVAR PARA LA AVÍCOLA

Los siguientes módulos son directamente aprovechables (con adaptaciones):

| Módulo | Estado | Notas |
|---|---|---|
| Autenticación y roles | ✅ Conservar | Agregar roles: vendedor, depositero, etc. |
| Artículos/productos | ✅ Adaptar | Renombrar: pollos, cortes, subproductos, etc. |
| Marcas y Rubros | ✅ Adaptar | Rubros: Ave entera, cortes, derivados, etc. |
| Clientes (mayoristas/minoristas) | ✅ Adaptar | Distinguir tipo: mayorista / minorista |
| Proveedores | ✅ Conservar | Proveedores de insumos, granja, etc. |
| Ventas y facturación | ✅ Adaptar | Añadir unidades de venta: kg, unidad, docena, cajón |
| Compras | ✅ Conservar | Compra de aves en pie, insumos |
| Notas de Crédito/Débito | ✅ Conservar | — |
| Recibos de cobro | ✅ Conservar | — |
| Órdenes de Pago | ✅ Conservar | — |
| Caja y Movimientos | ✅ Conservar | — |
| Cuentas de Fondos | ✅ Conservar | — |
| Cheques | ✅ Conservar | — |
| Facturación AFIP | ✅ Conservar | — |
| Tienda web | ✅ Adaptar | Venta minorista online |
| Carrito y Checkout | ✅ Adaptar | — |
| MercadoPago | ✅ Conservar | — |
| Variables de entorno | ✅ Conservar | — |
| Listas de precios (6) | ✅ Adaptar | Lista mayorista, minorista, distribuidores |
| Cotizaciones USD | ✅ Conservar | — |
| Informes y exportaciones | ✅ Ampliar | — |
| Sucursales de cliente | ✅ Conservar | Útil para puntos de entrega |
| Dashboard | ✅ Rediseñar | Métricas avícolas |

---

## 13. MÓDULOS A ELIMINAR (Específicos de CentroGoma)

Los siguientes módulos no aplican al negocio avícola y deben ser eliminados o reemplazados:

| Módulo | Razón |
|---|---|
| Artefactos / Extintores | Servicio técnico de extintores — no aplica |
| Mantenimiento Extintores | Idem |
| Contratos de servicio | Contratos de limpieza/seguridad — no aplica |
| Vehículos y Choferes | Gestión de flota de reparto — reemplazar por módulo de despacho avícola |
| Asignaciones de Vehículos | Idem |
| Gastos de Vehículos | Idem |
| Obras y Gastos de Obra | Centro de costos de construcción — no aplica |
| Categorías Gastos de Obra | Idem |
| Salidas de Instalación | Salidas técnicas — no aplica tal cual |
| Reportes Centro de Costos (contratos/obras) | No aplica |
| Rentabilidad de Contratos | No aplica |
| Empleados y Anticipos | Puede adaptarse a empleados de planta avícola |

---

## 14. MÓDULOS NUEVOS A DESARROLLAR PARA LA AVÍCOLA

### 14.1 Gestión de Producción Avícola
- **Lotes de aves:** registro de ingresos de pollos en pie (cantidad, peso vivo, proveedor)
- **Faena:** registro de faena diaria (aves faenadas, rendimiento, merma)
- **Subproductos:** menudencias, patas, carcasas, etc.
- **Rendimiento:** % conversión aves vivas → productos finales

### 14.2 Productos con Unidades Variables
- Venta por **kg** (peso real al momento de venta)
- Venta por **unidad** (pollos enteros)
- Venta por **docena/cajón** (para mayoristas)
- **Balanza integrada** o ingreso de peso manual
- Etiquetas con peso/precio para minoristas

### 14.3 Canal Mayorista
- Clientes con condición `mayorista` y lista de precios específica
- Pedidos anticipados (presupuestos/preventas)
- Despacho por rutas de reparto
- Notas de peso y remitos de envío

### 14.4 Canal Minorista / Tienda
- Tienda física: punto de venta simplificado (adaptar VentaSimplificada)
- Tienda web: catálogo de productos con precios para consumidor final
- Integración MercadoPago existente (ya está)

### 14.5 Control de Stock Avícola
- Stock de aves vivas (lotes en galpón)
- Stock de productos faenados (cámara frigorífica)
- Alertas de stock mínimo por temperatura/vencimiento
- Trazabilidad lote → faena → venta

### 14.6 Cámara Frigorífica / Depósitos
- Depósitos diferenciados: cámara de frío, exhibición, congelados
- Movimientos entre depósitos
- Temperatura de almacenamiento

### 14.7 Rutas de Reparto
- Planificación de rutas de entrega
- Asignación de pedidos a vehículo/chofer (adaptar módulo vehicular)
- Remitos de entrega
- Confirmación de entrega

### 14.8 Gestión de Granja / Proveedores Avícolas
- Proveedores de pollos en pie
- Historial de compras por proveedor (kg, cabezas, precio por kg vivo)
- Control de calidad de ingreso

---

## 15. PROBLEMAS TÉCNICOS IDENTIFICADOS

### 15.1 Deuda técnica visible
- Archivos backup en controladores: `OrdenPagoController_backup.php`, `OrdenPagoController_fixed.php`, `OrdenPagoController_nuevo.php` — deben eliminarse
- Archivo `.bak` y `.clean` en InformeProveedorController — limpiar
- 20+ scripts PHP sueltos en raíz del proyecto (diagnóstico, correcciones) — eliminar
- Múltiples documentos `.md` de correcciones ad-hoc en raíz — limpiar

### 15.2 Inconsistencias en naming
- Modelo `Articulo` usa columnas con mayúsculas (`Codigo`, `Descripcion`, `Lista2`, `Lista3`, etc.) — normalizar
- Mezcla de idiomas en nombres de columnas (inglés/español) — unificar en español

### 15.3 Listas de precios
- Listas 2–6 almacenadas **CON IVA** en BD pero devueltas **SIN IVA** por el modelo — documentar y unificar criterio
- Nombres de listas no descriptivos (Lista2, Lista3, lista5) — renombrar a: lista_mayorista, lista_distribuidor, etc.

### 15.4 Módulo de artículos
- Campo `precio_kike2` con nombre de persona — renombrar
- `id_proveedor` en artículo sin relación Eloquent definida — completar

---

## 16. CONFIGURACIÓN DE FRONTEND

### Tailwind CSS
- **Config:** `tailwind.config.js`
- Plugins: `@tailwindcss/forms`, `@tailwindcss/typography`
- PostCSS con autoprefixer

### Alpine.js
- Usado para interactividad ligera en Blade views
- Complementado con Livewire para componentes reactivos

### Vite
- Build tool moderno (v5)
- Plugin: `laravel-vite-plugin`

---

## 17. PLAN DE REFACTORIZACIÓN — RESUMEN EJECUTIVO

### Fase 1 — Limpieza y preparación (1–2 días)
1. Eliminar archivos backup y scripts de diagnóstico de raíz
2. Crear nuevo repositorio `avicola`
3. Actualizar datos de empresa en seeders y variables de entorno
4. Actualizar emails de admin (reemplazar centrogoma.com)

### Fase 2 — Adaptación del dominio (3–5 días)
1. Renombrar módulo "Artículos" → conceptos avícolas (pollos, cortes, etc.)
2. Adaptar listas de precios: mayorista / minorista / distribuidor / especial
3. Modificar modelo Cliente: agregar campo `tipo_cliente` (mayorista/minorista)
4. Agregar campo `unidad_medida` en artículos (kg, unidad, docena, cajón)
5. Actualizar seeders de Rubros y Tipos de IVA para avícola
6. Adaptar dashboard con métricas avícolas

### Fase 3 — Módulos nuevos básicos (5–10 días)
1. Módulo de Lotes de aves (ingreso, peso, proveedor)
2. Módulo de Faena (registro diario)
3. Punto de venta simplificado con peso (balanza)
4. Control de cámara frigorífica / depósitos

### Fase 4 — Módulos avanzados (10–20 días)
1. Planificación y rutas de reparto
2. Portal mayorista (pedidos anticipados)
3. Trazabilidad lote→producto→venta
4. Reportes específicos avícolas

### Fase 5 — Limpieza final
1. Eliminar módulos de CentroGoma no utilizados
2. Migrar/adaptar tienda web con productos avícolas
3. Testing completo

---

## 18. RESUMEN DE MÉTRICAS DE LA APP

| Métrica | Cantidad |
|---|---|
| Migraciones | ~135 |
| Modelos Eloquent | 60+ |
| Controladores | 65+ |
| Rutas web | 200+ |
| Rutas API | 8 |
| Seeders | 28 |
| Servicios | 5 (inc. 3 AFIP) |
| Vistas (carpetas) | 50+ |
| Middlewares | 15 |
| Permisos | 45+ |
| Roles | 2 (admin, cliente) |

---

---

## 19. REFINAMIENTO DEL MODELO DE NEGOCIO AVÍCOLA

### 19.1 Corrección: Gestión de Empleados (MANTENER)
El módulo de empleados, departamentos de empresa y anticipos de sueldo **se conserva completo**, ya que el cliente lo requiere para gestionar su personal de planta, vendedores, repartidores, etc.

### 19.2 Modelo de Negocio Real de la Avícola

La avícola **no faena**. Su operación es:

```
PROVEEDOR (Granja/Faenador)
      ↓
 Compra: Cajones de pollo en pallets
      ↓
 ┌─────────────────────────────────────────┐
 │         STOCK MAYORISTA (cajones)       │
 └──────────────────┬──────────────────────┘
                    │
        ┌───────────┴────────────┐
        │                        │
  VENTA DIRECTA           PRODUCCIÓN INTERNA
  (cajones sin abrir)     (apertura de cajas)
        │                        │
        │                 Subproductos:
        │                 - Bifes de pollo
        │                 - Pata muslo trozado
        │                 - Alitas separadas
        │                 - Otros artículos definidos
        │                        │
        └───────────┬────────────┘
                    │
        ┌───────────┴────────────────────────┐
        │                                     │
   CANAL MAYORISTA                    CANAL MINORISTA
   ─────────────                      ─────────────────
   - Retiro en sucursal                - Tienda física
   - Reparto a domicilio               - Tienda web
   - Precio mayorista                  - Precio minorista
   - Factura A o B                     - Ticket / Factura B
   - Pedidos anticipados               - Venta mostrador
```

### 19.3 Canales de Venta

| Canal | Descripción | Características |
|---|---|---|
| **Mayorista - Retiro** | Cliente mayorista retira en la sucursal | Lista de precios mayorista, factura A/B |
| **Mayorista - Reparto** | Vendedor/repartidor lleva pedido al cliente | Remito previo, liquidación posterior |
| **Minorista - Tienda física** | Venta mostrador en sucursal | Puede ser por peso (balanza), ticket fiscal |
| **Minorista - Web** | E-commerce con catálogo | MercadoPago, carrito, lista precios minorista |

### 19.4 Módulo de Producción (apertura de cajones)

El módulo de producción es una transformación interna:
- **Entrada (Egreso de stock):** Cajones de pollo entero (artículo origen)
- **Salida (Ingreso de stock):** Subproductos resultantes (artículos destino)
- Tipos de producción: Troceo, Milanesas, Otras
- Se registra: promedio de costo, ganancia, nro. de producción
- Se actualiza stock de todos los artículos involucrados

---

## 20. ANÁLISIS DE LA BASE DE DATOS CALZADA (Frango)

### 20.1 Contexto
Base de datos productiva de la avícola **Frango** con años de operación real. Contiene datos históricos con volumen significativo:

| Tabla | Registros reales |
|---|---|
| det_venta | 1.838.485 |
| movimiento_articulo | 1.719.193 |
| mov_vendedor | 1.704.483 |
| movimiento_usuario | 953.445 |
| venta | 632.116 |
| egresos | 600.027 |
| cccliente | 270.484 |
| pago_v | 208.252 |
| det_at (art. tránsito) | 124.211 |
| gastosvendedor | 107.522 |

### 20.2 Sucursales reales de Frango

La empresa opera con **11 sucursales/puntos de venta**:

| ID | Nombre |
|---|---|
| 2 | CALZADA (principal) |
| 1000 | LA PLATA 2 |
| 1015 | VARELA |
| 1300 | BERAZATEGUI |
| 1600 | LONGCHAMPS |
| 2504 | BELGRANO |
| 2505 | SOLANO |
| 2507 | LA PLATA |
| 2508 | LA PLATA NINI |
| 2509 | SOLANO_M |
| 4546 | PARQUE INDUSTRIAL |

→ Implica que el sistema **multi-sucursal es crítico**, con vendedores/cajeros asignados por sucursal y números de factura independientes por usuario/sucursal.

### 20.3 Rubros de Productos (Catálogo Real)

Los rubros revelan el catálogo completo de productos que maneja Frango:

| ID | Rubro | Tipo |
|---|---|---|
| 1 | POLLOS | Enteros por kg |
| 2 | HUEVOS | Por docena/unidad |
| 3 | TROCEO DE POLLO | Cortes sueltos |
| 4 | EMBUTIDO | Chorizos, morcilla, salchicha |
| 5 | TROCEO DE CERDO | Cortes de cerdo |
| 6 | CORDERO | Entero |
| 7 | VARIOS | Artículos varios |
| 8 | OTROS | Bolsas, materiales |
| 9 | LECHONES | Enteros |
| 12 | CHIVITO | Enteros |
| 13 | GALLINAS | Por kg |
| 15 | PAVITA | Por kg |
| 20 | MILANESAS | Preparados |
| 32 | TROCEO DE CERDO X CAJA | Por caja |
| 33 | TROCEO DE POLLO X CAJA | Por caja |
| 35 | REBOZADORES Y PAN | Insumos |
| 36 | CONDIMENTOS | Insumos |
| 37 | CONGELADOS | Productos congelados |
| 38 | ALITAS X CAJA | Por caja |
| 39 | PATA Y MUSLO C/CZA x C | Por caja |
| 40 | FILET x CAJA | Por caja |
| 42 | PECHUGA C. HUESO x CAJA | Por caja |
| 43 | CARCAZA x CAJA | Por caja |
| 46 | MUSLOS X CAJA | Por caja |
| 47 | BONDIOLA X CAJA | Por caja |
| 48 | PATAS de POLLO X CAJA | Por caja |
| 49 | SALCHICHA X6 | Por pack |
| 54 | CORDERO X CAJA | Por caja |

→ El sistema diferencia claramente **venta por kilo** (cantidad_por_caja = 1) vs **venta por caja** (cantidad_por_caja > 1).

### 20.4 Proveedores con Marca Asociada

| Marca | Proveedor Real |
|---|---|
| WADE S.A. | Proveedor #54 |
| SANTA ISABEL | Proveedor #37 |
| QUILMEÑO S.A. | Proveedor #13 |
| GRANJA TRES ARROYOS S.A. | Proveedor #9 |
| GRUPOLAR S.R.L. | Proveedor #35 |
| PALADINI S.A. | Proveedor #40 |
| BARRACAS AL SUR | Proveedor #7 |
| BONNIN HNOS S.A. | Proveedor #18 |

→ La marca tiene **comisión asociada**, indicando que algunos proveedores pagan comisión al vendedor.

### 20.5 Diferencias Clave entre BD Calzada y App SaaS

#### A. Artículos: calzada tiene 21 listas de precios (app SaaS solo 6)
La tabla `articulo` en calzada tiene `lista2` a `lista21` — **21 listas de precios**. Además:
- `art_mayorista`: flag que indica si el artículo es de canal mayorista
- `perdida`: porcentaje de merma (default 3%) — usado en costeo de producción
- `acepta_subrubro`: si el artículo tiene sub-artículos (para producción)
- `caja`: flag que indica si se vende por caja
- `descuento1..5`: descuentos escalonados por volumen (5 niveles)
- `mayorista1..5`: cantidades mínimas para precios mayoristas (5 escalonados)
- `oferta`: flag de artículo en oferta

#### B. Sub-artículos (`articulo_sub`)
Tabla específica para artículos producidos internamente (subproductos). Tiene:
- Relación con artículo padre (`id_articulo`)
- Sus propias listas de precios (2–11)
- Precios mayoristas escalonados: `cant_may1..5` / `precio_may1..5` (5 escalones)
- Stock independiente

#### C. Rubros con precios mayoristas escalonados
La tabla `rubro` tiene 5 pares de columnas `mayoristaN / descuentoN` (N=1..5), que definen:
- `mayoristaN`: cantidad mínima para aplicar ese nivel de descuento
- `descuentoN`: porcentaje de descuento al alcanzar esa cantidad

→ Sistema de **descuentos por volumen por rubro**, no solo por cliente.

#### D. Números de factura por usuario (Variable Entorno)
La tabla `variable_entorno` almacena el número de factura **por usuario**:
- `nro_factura_a_HECTOR`, `nro_factura_b_HECTOR`, etc.
- 30+ usuarios con sus propias secuencias de numeración
- Implica que cada **cajero/vendedor tiene su propio numerador** de factura

#### E. Vendedores/Repartidores como entidad separada
La tabla `vendedor` es una entidad propia (distinta de usuario) con:
- `reparto`: flag si el vendedor hace reparto
- `sucursal2`: si tiene segunda sucursal
- `nro_reparto`: número de ruta de reparto
- `tratante`: si es un tratante/intermediario
- `acepta_deposito`, `acepta_imputar`: flags de operación
- `saldo`: saldo propio del vendedor (como cuenta interna)
- Tiene movimientos propios en `mov_vendedor`
- Tiene gastos propios en `gastosvendedor`

#### F. Sistema de pesado (`pesado` + `det_pesado`)
Sistema de **inventario físico por pesada**:
- Permite registrar el peso real de cada artículo en un período
- Compara contra stock contable: `stock` vs `pesado` → `diferencia` y `perdida`
- Campos: `precio_costo`, `compras`, `costeo`, `sobrante_anterior`, `ventas`
- Usado para cuadrar stock y calcular mermas reales

#### G. Artículos en Tránsito (`articulos_transito` + `det_at`)
Sistema de movimiento interno entre sucursales y producciones:
- `tipo_movimiento`: E=Egreso, I=Ingreso
- `suc_origen` / `suc_destino`: para transferencias entre sucursales
- `produccion`: número de producción (cuando es una transformación)
- `ganancia` / `promedio`: rentabilidad calculada de la producción
- `ejecucion_stock`: S/N si ya impactó en stock
- `nro_remito`: remito asociado a la transferencia

#### H. Movimientos duales (Mayorista + Minorista)
- `movimiento_articulo`: stock del canal **mayorista**
- `movimiento_articulo_minorista`: stock del canal **minorista**
- Dos stocks separados para el mismo artículo: `stock` (mayorista) vs `stock_minorista`

#### I. Cliente con excepciones de precio
La tabla `cliente` tiene:
- `excepcion1` / `excepcion2`: IDs de artículos con precio especial
- `precio_excepcion1` / `precio_excepcion2`: precios excepcionales por artículo
- `listaprecios`: lista asignada (1-21)
- `modifica_stock`: flag si las ventas a este cliente modifican stock (Consumidor Final no modifica)
- `oferta`: flag si el cliente ve los artículos en oferta
- `id_pago_aceptado`: forma de pago habitual del cliente

La tabla `cliente_precio` amplía esto con:
- Por rubro, artículo: lista específica o descuento de nivel `1..5`

#### J. Imputación de pagos (`imputo`)
Tabla para categorías de imputación de egresos de caja — permite categorizar gastos internos.

#### K. Compras con campos específicos avícolas
`compra` tiene:
- `retencion` + `porc_retencion`: retenciones en compras
- `asignado` / `liquidado`: flags de estado de pago
- `id_vendedor`: vendedor/repartidor asociado a la compra (recepciona)


