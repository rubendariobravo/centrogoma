# Delphi vs. sistema nuevo — inventario de funciones

Relevado el 07/11/2026 sobre el código del Delphi (`C:\desarrollo\centrogoma24`: menú y botones de
`frmSaltaGomasU`, los 69 formularios y sus acciones) y sobre **el uso real** en la BD legacy restaurada al
31/10/2026 (últimos 12 meses: 01/11/2025 – 31/10/2026). Lo que el Delphi tiene pero nadie usa no se cuenta como
brecha.

**Referencias:** ✅ cubierto · 🟡 parcial · ❌ falta · ➖ no hace falta (sin uso, o reemplazado)

---

## 1. Cómo se usa hoy el Delphi (últimos 12 meses)

| Área | Uso | Comentario |
|---|---|---|
| Ventas | **12.127 comprobantes** | 10.487 por **impresora fiscal** (controlador 715) · 1.628 **factura electrónica con CAE** (A, B, NC A/B, 1 FCE MiPyME A) |
| Notas de crédito | 97 | Por impresora fiscal y electrónicas |
| Formas de pago | efectivo 9.564 · CC 963 · tarjetas 1.193 (Visa débito 446, Visa 390, Naranja 270, Master 48…) · transferencia 228 · Mercado Pago 201 | Las tarjetas se registran **por marca** |
| Presupuestos | 1.779 (7.306 renglones) | Ninguno se convirtió en venta: son cotizaciones. 51 marcados en otra moneda (USD) |
| Compras | ~480 por año | Con IVA compras por período |
| CC clientes | 1.421 movimientos | Facturas, pagos y ajustes |
| CC proveedores | 849 movimientos | El pago se registra en la CC (la tabla `orden_pago` está vacía) |
| Cheques | 3 en dos años | Prácticamente sin uso |
| Caja / egresos | **sin uso desde 02/2022** | `caja` y `egresos` dejaron de usarse; `cuentas_banco`, `orden_pago` nunca se usaron |
| Stock | **no se lleva** (decisión del negocio) | `producto.stock` vacío en todos |

---

## 2. Comparación por módulo

### Ventas y facturación

| Función del Delphi | Uso | Sistema nuevo | Estado |
|---|---|---|---|
| Venta (alta, búsqueda de artículo, cantidad, precio) | diario | `Ventas\Form` con motor de precios, tramos, listas | ✅ |
| Precio modificable en la venta (flag por producto `modifica_precio`, 1.568 artículos) | diario | Precio editable en el renglón (`precio_manual`) para todos | ✅ (sin flag por artículo) |
| Venta por unidad / por metro (`unitario`, `metraje`) | medio | `tipo_venta` CAJA/PESO + `unidad_medida` | 🟡 verificar con los artículos por metro |
| Pagos mixtos por venta | diario | Pagos múltiples por venta, CC por el saldo | ✅ |
| Tarjeta **por marca** (Visa, Naranja, Master, débito…) | 1.193/año | Una sola forma "Tarjeta" | 🟡 falta la marca (sirve para conciliar liquidaciones) |
| Mercado Pago como forma de pago | 201/año | No está en `formas_pago` (sembrar) | 🟡 |
| **Impresora fiscal** (ticket/factura, Reporte X y Z, cancelar ticket, datos de configuración) | 10.487/año | No existe; un navegador no maneja el controlador fiscal | ❌ ver §3 |
| **Factura electrónica AFIP** (CAE, A/B, FCE MiPyME, reimprimir FE) | 1.628/año | Pendiente (pantalla preparada, `cae` en ventas) | ❌ **crítico** |
| **Nota de crédito** (fiscal y electrónica) | 97/año | No existe: hoy se anula la venta | ❌ va con AFIP |
| Orden de compra del cliente en la factura | 206/año | No hay campo | ❌ menor (agregar campo) |
| Listado de ventas, impresión detallada / totales | diario | `/ventas` + PDF A4 / ticket 72 mm | ✅ |
| "Suspender factura" (marca la venta como pendiente de facturar) | ? | No existe | ❓ preguntar si se usa |
| Cambiar la forma de pago de una venta ya hecha | ? | No existe (hay que anular y rehacer) | 🟡 preguntar frecuencia |
| Listado de facturas del día / Reporte Z | diario | Detalle de caja (`/cajas/{id}`) | 🟡 falta el cierre Z fiscal |
| Libro **IVA Ventas** (PDF y Excel por período) | mensual | No existe | ❌ |

### Presupuestos

| Función del Delphi | Uso | Sistema nuevo | Estado |
|---|---|---|---|
| Alta/edición de presupuesto, validez, condición de pago, plazo de entrega | 1.779/año | `Presupuestos\Form` (mismos campos) | ✅ |
| Imprimir / PDF | diario | `PresupuestoController::pdf()` | ✅ |
| "Incluye IVA" / "Sin leyenda" (cómo se muestran los precios) | 276 sin IVA | No existe la opción | ❌ |
| **Expresar en USD** | 51/año (la marca del Delphi está invertida: verificar) | No existe moneda | ❌ va con el tema de artículos en dólares |
| Convertir en venta | 0 | Existe (`ventas/create?presupuesto_id=`) | ✅ (mejora) |

### Artículos y precios

| Función del Delphi | Uso | Sistema nuevo | Estado |
|---|---|---|---|
| ABM de productos y rubros, búsqueda por descripción/código | diario | `/articulos`, `/rubros`, `/marcas` | ✅ |
| Costo + margen → precio | diario | `precio_costo`, `margen`, tramos por lista | ✅ |
| Modificar precios de la selección (%) | periódico | Ajuste % masivo por lista/rubro + Excel | ✅ |
| Agregar texto a la descripción de la selección | ocasional | No existe | ❌ menor |
| Imprimir lista de precios | periódico | Exportar Excel por lista | 🟡 sin PDF imprimible |
| Búsqueda por código de barras | ? | No hay campo de barras | ❓ preguntar |
| Artículos en dólares (~1.258 con precio < $100) | diario | Se cobran como pesos | ❌ **decidir** (cotización o moneda por artículo) |
| Stock | no se lleva | Se descuenta y queda negativo | ➖ apagar o esconder (decisión pendiente) |

### Clientes y cuenta corriente

| Función del Delphi | Uso | Sistema nuevo | Estado |
|---|---|---|---|
| ABM de clientes, condición de IVA, tipo de documento | diario | `/clientes` + buscador server-side | ✅ |
| Cuenta corriente por cliente, imprimir cuenta | diario | `/cc-clientes` | 🟡 falta imprimir/exportar el resumen |
| Registrar pagos (efectivo, cheque, "caja blanca") | diario | Recibos con imputación a facturas | ✅ |
| "Factura pagada" (marca manual) | diario | El recibo cancela la factura | ✅ (reemplazado) |
| Listado de saldos de CC / facturas pendientes por cliente | periódico | Saldos en el listado; Dashboard "A cobrar" y mayores deudores | 🟡 falta el listado imprimible de pendientes |
| Saldo inicial | una vez | `centrogoma:importar-saldos` | ✅ |

### Compras y proveedores

| Función del Delphi | Uso | Sistema nuevo | Estado |
|---|---|---|---|
| ABM de proveedores | periódico | `/proveedores` | ✅ |
| Factura de compra con detalle | ~40/mes | `Compras\Form` (CC o contado) | ✅ |
| **Nota de crédito de compra** | 17/año | No hay tipo de comprobante en compras | ❌ |
| Libro **IVA Compras** + "fecha contable" (período de imputación) | mensual | No existe; compras no guarda punto de venta, percepciones ni período | ❌ |
| Modificar tipo de factura | ocasional | Se edita la letra | ✅ |
| CC proveedores, registrar pagos, exportar a Excel | semanal | `/cc-proveedores` + Órdenes de pago | 🟡 falta exportar |

### Caja, bancos y cheques

| Función del Delphi | Uso | Sistema nuevo | Estado |
|---|---|---|---|
| Apertura/cierre de caja, planilla por forma de pago, movimientos, plan de cuentas | sin uso desde 2022 | Libro de caja completo, gastos con categorías, transferencias | ✅ (más que el Delphi) |
| Cheques: cartera, depositar, cobrar, vender | casi nulo | Módulo de cheques completo | ✅ |
| Cuentas bancarias y movimientos | nunca usado | Cajas tipo banco | ➖ |
| Permisos por caja | — | Roles y permisos (Spatie) | ✅ |

### Otros

| Función del Delphi | Uso | Sistema nuevo | Estado |
|---|---|---|---|
| Agenda de contactos (43 registros) | mínimo | — | ➖ |
| Usuarios / inicio de sesión | diario | Usuarios, roles, auditoría | ✅ |
| Estadísticas | — (no tenía) | `/estadisticas` + Dashboard | ✅ (nuevo) |
| Remitos, empleados, anticipos | — (no tenía) | Módulos nuevos | ✅ (nuevo) |

---

## 3. Brechas, en orden de prioridad

Bloquean la salida (sin esto no se puede facturar como hoy):

1. **Factura electrónica AFIP**: CAE para Factura A/B, Nota de Crédito A/B y FCE MiPyME, reimpresión.
   1.628 comprobantes al año ya salen así. Requiere certificado y punto de venta electrónico.
2. **Qué pasa con la impresora fiscal** (87% de las ventas). Un navegador no puede manejar el controlador
   fiscal. Alternativas: (a) pasar todo a factura electrónica, incluido el consumidor final, que es lo que AFIP
   viene empujando; (b) un agente local que reciba el comprobante e imprima en el controlador. **Decisión del
   negocio**, a tomar antes de construir el punto 1.
3. **Nota de crédito de venta** (reemplaza "anular" en comprobantes con CAE).
4. **Artículos en dólares**: cómo se cotizan (≈1.258 artículos) y presupuestos en USD.

Necesarias para el primer cierre de mes:

5. **Libro IVA Ventas** (PDF + Excel) por período.
6. **Libro IVA Compras** con fecha contable, y **nota de crédito de compra** (agrega a compras: tipo de
   comprobante, punto de venta y período).

Mejoras de uso diario (chicas):

7. Tarjeta por marca y Mercado Pago como formas de pago.
8. Presupuesto: "precios con/sin IVA" y "sin leyenda".
9. Orden de compra del cliente en la venta.
10. Imprimir/exportar la cuenta corriente (clientes y proveedores) y el listado de facturas pendientes.
11. Lista de precios imprimible (PDF) y "agregar texto a la descripción" de la selección.

A confirmar con el negocio: "suspender factura", cambiar la forma de pago de una venta ya hecha, código de
barras, venta por metro.
