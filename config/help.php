<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Contenido de ayuda por página (mapeado por nombre de ruta)
    |--------------------------------------------------------------------------
    */
    'pages' => [

        'dashboard' => [
            'titulo'      => 'Dashboard',
            'descripcion' => 'Vista general del negocio con indicadores clave.',
            'tips' => [
                'El indicador "Stock Crítico" muestra artículos con stock por debajo del mínimo configurado. Hacé clic para ir directamente al artículo.',
                'El badge "Caja abierta" en el topbar indica que hay una caja del día activa. Es necesario abrir caja antes de registrar ventas.',
                'Las últimas 8 ventas del panel te permiten acceder rápidamente a cualquier comprobante reciente.',
                'Los totales de ventas se calculan en base a ventas con estado Cobrado (C) y Cuenta Corriente (CC).',
            ],
            'faq' => [
                ['q' => '¿Por qué no puedo registrar una venta?', 'a' => 'Es necesario tener una caja abierta. Ir a Caja y Finanzas → Cajas y abrir una nueva caja del día.'],
                ['q' => '¿Qué significa "Caja cerrada"?', 'a' => 'No hay caja abierta para el día. El sistema no permite registrar ventas ni cobros sin caja activa.'],
                ['q' => '¿Con qué frecuencia se actualiza el dashboard?', 'a' => 'Los datos se actualizan en tiempo real cada vez que se carga la página o se navega al dashboard.'],
            ],
        ],

        // ─── VENTAS ──────────────────────────────────────────────────────────

        'ventas' => [
            'titulo'      => 'Lista de Ventas',
            'descripcion' => 'Gestión de facturas, remitos y comprobantes de venta.',
            'tips' => [
                'Usá el filtro de fecha para buscar ventas de períodos anteriores. Por defecto muestra el mes actual.',
                'El total del período se actualiza automáticamente al cambiar los filtros.',
                'Las ventas en estado "CC" (Cuenta Corriente) tienen saldo pendiente de cobro.',
                'Podés ver el PDF de cualquier venta haciendo clic en el ícono de impresora.',
            ],
            'faq' => [
                ['q' => '¿Qué significan los estados de venta?', 'a' => '"C" = Cobrada al contado. "CC" = Cuenta corriente (pendiente). "A" = Anulada.'],
                ['q' => '¿Cómo anulo una venta?', 'a' => 'Abrí la venta y usá el botón Anular. El sistema revierte el stock y el saldo de cuenta corriente automáticamente.'],
                ['q' => '¿Por qué cambia la letra del comprobante?', 'a' => 'La letra se asigna automáticamente: "A" para clientes Responsables Inscriptos, "B" para el resto.'],
            ],
        ],

        'ventas.create' => [
            'titulo'      => 'Nueva Venta',
            'descripcion' => 'Registro de una nueva venta con cálculo automático de IVA y percepciones.',
            'tips' => [
                'Al seleccionar un artículo, el precio se completa automáticamente según el tramo de cantidad configurado.',
                'El descuento general (campo "Desc. %") se aplica sobre el subtotal de todos los ítems.',
                'Si el cliente tiene Percepción IIBB configurada, se calcula y agrega automáticamente al total.',
                'Para ventas en cuenta corriente, seleccioná "Cta. Cte." como forma de pago.',
                'Podés combinar múltiples formas de pago (ej: parte efectivo + parte transferencia).',
            ],
            'faq' => [
                ['q' => '¿Cómo sé qué precio aplica para un artículo?', 'a' => 'El sistema busca automáticamente el tramo de precio según la cantidad ingresada. Podés verlos en Artículos → editar artículo.'],
                ['q' => '¿Qué pasa con el stock al guardar?', 'a' => 'El stock del artículo se reduce automáticamente y queda registrado en el historial de movimientos.'],
                ['q' => '¿Puedo facturar electrónicamente (AFIP)?', 'a' => 'La integración con AFIP WSFEv1 está en desarrollo. Por ahora el sistema registra la venta internamente sin CAE.'],
            ],
        ],

        'ventas.show' => [
            'titulo'      => 'Detalle de Venta',
            'descripcion' => 'Vista de un comprobante de venta ya registrado.',
            'tips' => [
                'En modo lectura podés imprimir la factura o el ticket usando los botones del encabezado.',
                'Si la venta tiene saldo en cuenta corriente, podés ir a Recibos para registrar el cobro.',
            ],
            'faq' => [
                ['q' => '¿Puedo modificar una venta ya guardada?', 'a' => 'No. Las ventas son inmutables una vez guardadas. Para corregir un error usá el flujo de Anulación + Nueva Venta.'],
            ],
        ],

        'clientes' => [
            'titulo'      => 'Clientes',
            'descripcion' => 'Gestión del padrón de clientes con datos fiscales y condiciones comerciales.',
            'tips' => [
                'El filtro "Con deuda" muestra solo clientes con saldo de cuenta corriente positivo.',
                'La lista de precio (1-6) determina qué tabla de precios especiales aplica al cliente.',
                'El porcentaje de IIBB se guarda como decimal (3% = 0.03). En el formulario se ingresa como "3".',
                'Clientes marcados como Mayoristas acceden al stock mayorista de los artículos.',
            ],
            'faq' => [
                ['q' => '¿Cómo configuro percepciones para un cliente?', 'a' => 'En el formulario del cliente, activá "Percepción IVA" y completá el "% IIBB". Se aplicarán automáticamente en cada venta.'],
                ['q' => '¿Qué es el límite de crédito?', 'a' => 'Es el monto máximo de deuda en cuenta corriente permitido. El sistema avisa si se supera, pero no bloquea la venta.'],
                ['q' => '¿Puedo eliminar un cliente con ventas?', 'a' => 'El sistema usa eliminación lógica (SoftDelete). El cliente se desactiva pero sus ventas históricas se conservan.'],
            ],
        ],

        'clientes.create' => [
            'titulo'      => 'Nuevo Cliente',
            'descripcion' => 'Registro de un nuevo cliente con datos fiscales y condiciones comerciales.',
            'tips' => [
                'El tipo de IVA determina la letra del comprobante: Responsable Inscripto → Factura A.',
                'Si el cliente paga con cheques o a plazo, configurale un límite de crédito.',
                'El vendedor asignado permite filtrar ventas por vendedor y calcular comisiones.',
            ],
            'faq' => [
                ['q' => '¿Es obligatorio el CUIT?', 'a' => 'Para Responsables Inscriptos sí. Para Consumidores Finales podés usar el DNI.'],
            ],
        ],

        'clientes.edit' => [
            'titulo'      => 'Editar Cliente',
            'descripcion' => 'Modificación de datos de un cliente existente.',
            'tips' => [
                'Cambiar el tipo de IVA afecta los nuevos comprobantes, no los ya emitidos.',
                'Al cambiar la bonificación, las nuevas ventas la aplicarán como descuento general.',
            ],
            'faq' => [
                ['q' => '¿Puedo cambiar el tipo de IVA de un cliente?', 'a' => 'Sí, pero solo afecta comprobantes futuros. Los comprobantes ya emitidos mantienen la letra original.'],
            ],
        ],

        'recibos' => [
            'titulo'      => 'Recibos de Cobro',
            'descripcion' => 'Gestión de cobros a clientes con aplicación automática a facturas pendientes.',
            'tips' => [
                'El sistema aplica el cobro a las facturas más antiguas primero (FIFO).',
                'El botón "Pagar saldo completo" carga automáticamente el total adeudado del cliente.',
                'El cobro incrementa el total de la caja del día automáticamente.',
            ],
            'faq' => [
                ['q' => '¿Qué pasa si el cliente paga más de su deuda?', 'a' => 'El sistema aplica hasta cubrir el saldo. Si hay excedente, quedará como saldo a favor en la cuenta corriente.'],
                ['q' => '¿Puedo eliminar un recibo?', 'a' => 'Sí, pero revertirá los pagos aplicados y el saldo de la cuenta corriente del cliente.'],
            ],
        ],

        // ─── COMPRAS ─────────────────────────────────────────────────────────

        'compras' => [
            'titulo'      => 'Compras',
            'descripcion' => 'Registro de compras a proveedores con actualización automática de stock.',
            'tips' => [
                'Al guardar una compra, el stock de cada artículo se incrementa automáticamente.',
                'El precio costo del artículo se actualiza con el último precio de compra.',
                'Las compras en cuenta corriente quedan en estado "P" (Pendiente de pago).',
            ],
            'faq' => [
                ['q' => '¿El sistema calcula el IVA de la compra?', 'a' => 'Sí. Podés seleccionar la alícuota de IVA (21%, 10.5%, 0%) por ítem y el sistema desglosa neto e IVA.'],
                ['q' => '¿Cómo registro el pago de una compra?', 'a' => 'Ir a Compras → Órdenes de Pago para registrar el pago al proveedor y saldar la deuda.'],
            ],
        ],

        'compras.create' => [
            'titulo'      => 'Nueva Compra',
            'descripcion' => 'Registro de una nueva compra a proveedor.',
            'tips' => [
                'Seleccioná la alícuota de IVA correcta por ítem para que el desglose fiscal sea preciso.',
                'Si pagás al contado, elegí "Pago inmediato" y la compra queda saldada (estado C).',
                'El nro. de factura del proveedor es útil para conciliar con estados de cuenta.',
            ],
            'faq' => [
                ['q' => '¿Puedo comprar artículos que no están en el sistema?', 'a' => 'No. Primero debés crear el artículo en Artículos, luego registrar la compra.'],
            ],
        ],

        'proveedores' => [
            'titulo'      => 'Proveedores',
            'descripcion' => 'Padrón de proveedores con cuenta corriente y datos de contacto.',
            'tips' => [
                'Los proveedores con saldo positivo tienen facturas pendientes de pago.',
                'Podés ver el historial de compras de cada proveedor desde su ficha.',
            ],
            'faq' => [
                ['q' => '¿Cómo veo lo que le debo a un proveedor?', 'a' => 'Ir a Caja y Finanzas → CC Proveedores para ver el saldo y el detalle de movimientos de cada proveedor.'],
            ],
        ],

        'ordenes-pago' => [
            'titulo'      => 'Órdenes de Pago',
            'descripcion' => 'Pagos a proveedores con aplicación a facturas pendientes.',
            'tips' => [
                'El sistema aplica el pago a las facturas más antiguas primero (FIFO).',
                'Podés pagar con efectivo, transferencia, cheque o débito de cuenta corriente.',
                'El pago descuenta de la caja del día automáticamente.',
            ],
            'faq' => [
                ['q' => '¿Puedo pagar varias facturas de una vez?', 'a' => 'Sí. El sistema aplica el monto pagado automáticamente a las facturas pendientes más antiguas hasta agotar el importe.'],
            ],
        ],

        // ─── ARTÍCULOS ───────────────────────────────────────────────────────

        'articulos' => [
            'titulo'      => 'Artículos',
            'descripcion' => 'Catálogo de productos con control de stock y precios por tramo.',
            'tips' => [
                'El filtro "Stock crítico" muestra artículos con stock por debajo del mínimo configurado.',
                'Los artículos tipo CAJA venden unidades enteras; tipo PESO venden por kilogramo.',
                'Podés desactivar un artículo sin eliminarlo para que no aparezca en ventas.',
            ],
            'faq' => [
                ['q' => '¿Qué es un artículo tipo CAJA vs tipo PESO?', 'a' => '"CAJA" vende en unidades enteras (cajas, unidades). "PESO" vende por kilogramo y el precio se calcula por kg.'],
                ['q' => '¿Cómo configuro precios por cantidad?', 'a' => 'En la ficha del artículo, usá la tabla de Tramos de Precio. Definí rango de cantidad y precio correspondiente.'],
                ['q' => '¿El stock se actualiza solo?', 'a' => 'Sí. Las ventas descuentan stock, las compras lo incrementan. Podés ajustar manualmente con Pesadas.'],
            ],
        ],

        'articulos.create' => [
            'titulo'      => 'Nuevo Artículo',
            'descripcion' => 'Creación de un artículo nuevo en el catálogo.',
            'tips' => [
                'El código es de uso interno. Puede ser el código de barras o un código propio.',
                'Configurá el stock mínimo para recibir alertas en el dashboard cuando el stock baje.',
                'Los tramos de precio permiten dar precio mayorista automáticamente según la cantidad comprada.',
                'El margen se usa solo como referencia. El precio de venta lo definís manualmente en los tramos.',
            ],
            'faq' => [
                ['q' => '¿Puedo tener el mismo artículo con precio mayorista y minorista?', 'a' => 'Sí. Configurá tramos de precio: por ejemplo 1-9 unidades a precio minorista, 10+ a precio mayorista.'],
            ],
        ],

        'rubros' => [
            'titulo'      => 'Rubros',
            'descripcion' => 'Categorías de artículos con alícuota de IVA predeterminada.',
            'tips' => [
                'La alícuota de IVA del rubro se usa como valor predeterminado al crear artículos de ese rubro.',
                'Podés tener un rubro "Exento" (0%) para artículos sin IVA.',
            ],
            'faq' => [
                ['q' => '¿Puedo cambiar la alícuota de un rubro?', 'a' => 'Sí, pero solo afecta artículos nuevos. Los artículos existentes mantienen su alícuota individual.'],
            ],
        ],

        'marcas' => [
            'titulo'      => 'Marcas',
            'descripcion' => 'Marcas de productos vinculadas a proveedores y comisiones.',
            'tips' => [
                'La comisión de la marca se usa para calcular comisiones del vendedor (módulo en desarrollo).',
                'Vincular marca a proveedor ayuda a saber qué comprarle a cada proveedor.',
            ],
            'faq' => [
                ['q' => '¿Es obligatorio asignar una marca a cada artículo?', 'a' => 'No es obligatorio, pero es recomendable para tener mejor trazabilidad y generar informes por marca.'],
            ],
        ],

        // ─── CAJA Y FINANZAS ─────────────────────────────────────────────────

        'cajas' => [
            'titulo'      => 'Cajas',
            'descripcion' => 'Apertura y cierre diario de caja con control de efectivo.',
            'tips' => [
                'Solo puede haber una caja abierta a la vez. Cerrá la caja anterior antes de abrir una nueva.',
                'El saldo inicial es el dinero físico con el que arranca la caja del día.',
                'Al cerrar, ingresá el "Saldo Real Contado" para registrar si hay sobrante o faltante.',
                'Las ventas al contado y los cobros de cuenta corriente se suman automáticamente a la caja.',
            ],
            'faq' => [
                ['q' => '¿Qué incluye el "Total del período" al cerrar?', 'a' => 'Incluye ventas en efectivo/tarjeta/transferencia más cobros de cuenta corriente, menos pagos a proveedores.'],
                ['q' => '¿Puedo reabrir una caja cerrada?', 'a' => 'No. Una vez cerrada la caja, los datos son de solo lectura para mantener la integridad del cierre.'],
                ['q' => '¿Qué pasa si hay diferencia de caja?', 'a' => 'El sistema registra la diferencia (sobrante o faltante). Es un dato informativo para control interno.'],
            ],
        ],

        'cc-clientes' => [
            'titulo'      => 'Cuenta Corriente Clientes',
            'descripcion' => 'Saldos y movimientos de cuenta corriente por cliente.',
            'tips' => [
                'Los clientes están ordenados por mayor deuda primero para priorizar cobros.',
                'Hacé clic en un cliente para ver el detalle de todos sus movimientos de cuenta corriente.',
                'El saldo mostrado es el running balance: incluye tanto ventas como cobros realizados.',
            ],
            'faq' => [
                ['q' => '¿Cómo cobro la deuda de un cliente?', 'a' => 'Ir a Ventas → Recibos y crear un nuevo recibo. Seleccioná el cliente y el sistema mostrará su saldo y facturas pendientes.'],
                ['q' => '¿Qué significa "Debe" y "Haber"?', 'a' => '"Debe" son ventas a crédito (el cliente nos debe). "Haber" son cobros recibidos (abona su deuda).'],
            ],
        ],

        'cc-proveedores' => [
            'titulo'      => 'Cuenta Corriente Proveedores',
            'descripcion' => 'Saldos y movimientos de cuenta corriente por proveedor.',
            'tips' => [
                'Los proveedores están ordenados por mayor deuda nuestra primero para gestionar pagos.',
                'Hacé clic en un proveedor para ver el detalle completo de compras y pagos.',
            ],
            'faq' => [
                ['q' => '¿Cómo pago a un proveedor?', 'a' => 'Ir a Compras → Órdenes de Pago. El sistema aplica el pago a las facturas más antiguas primero.'],
            ],
        ],

        // ─── PRODUCCIÓN ──────────────────────────────────────────────────────

        'produccion' => [
            'titulo'      => 'Producción',
            'descripcion' => 'Registro de procesos productivos con control de merma.',
            'tips' => [
                'La merma se calcula automáticamente: (entrada - salida) / entrada × 100.',
                'Al guardar una producción, el stock del artículo de entrada se descuenta y el de salida se incrementa.',
                'Registrá producciones por turno o por lote para tener trazabilidad del proceso.',
            ],
            'faq' => [
                ['q' => '¿Qué es la merma?', 'a' => 'Es la diferencia entre lo que entra al proceso (ej: cajón vivo) y lo que sale procesado. Se expresa en kg y %.'],
                ['q' => '¿Puedo cerrar una producción parcialmente?', 'a' => 'No. Una producción se registra completa con sus entradas y salidas al momento de guardar.'],
            ],
        ],

        'remitos' => [
            'titulo'      => 'Remitos',
            'descripcion' => 'Comprobantes de despacho con detalle de peso y cajas.',
            'tips' => [
                'El remito documenta la salida física de mercadería hacia el cliente o depósito.',
                'Podés cerrar un remito cuando la entrega fue completada.',
                'Asociá un empleado al remito para saber quién realizó la entrega.',
            ],
            'faq' => [
                ['q' => '¿El remito descuenta stock?', 'a' => 'No. El remito es solo un documento de transporte. El stock se descuenta al registrar la venta correspondiente.'],
            ],
        ],

        'pesadas' => [
            'titulo'      => 'Pesadas / Ajuste de Stock',
            'descripcion' => 'Ajuste manual de stock mediante pesada física.',
            'tips' => [
                'Al seleccionar un artículo, el sistema carga automáticamente el stock actual del sistema.',
                'Ingresá el stock real (lo que contás físicamente). La diferencia se calcula sola.',
                'Al guardar, el stock del artículo se actualiza al valor real ingresado.',
                'Hacé pesadas periódicas para detectar mermas, robos o errores de carga.',
            ],
            'faq' => [
                ['q' => '¿Qué pasa si el stock real es menor al del sistema?', 'a' => 'El sistema registra la diferencia negativa y ajusta el stock. Queda un movimiento de tipo "salida" en el historial.'],
                ['q' => '¿Con qué frecuencia hacer pesadas?', 'a' => 'Se recomienda hacer pesadas semanales para artículos de alto movimiento y mensuales para el resto.'],
            ],
        ],

        'descartes' => [
            'titulo'      => 'Descartes',
            'descripcion' => 'Registro de mermas y artículos descartados.',
            'tips' => [
                'Registrá descartes por vencimiento, rotura, pérdida de frío u otros motivos.',
                'El descarte descuenta el stock automáticamente al guardar.',
                'El campo "Motivo" es importante para análisis de pérdidas.',
            ],
            'faq' => [
                ['q' => '¿En qué se diferencia un descarte de una pesada?', 'a' => 'El descarte registra una pérdida conocida y puntual (ej: "5kg vencidos"). La pesada ajusta el stock al valor real contado.'],
            ],
        ],

        'empleados' => [
            'titulo'      => 'Empleados',
            'descripcion' => 'Padrón de empleados con roles y comisiones.',
            'tips' => [
                'El rol del empleado (vendedor, repartidor, producción, etc.) se usa para filtrar en otros módulos.',
                'La comisión se guarda como decimal (3% = 0.03). Se mostrará como "3" en el formulario.',
                'Podés vincular un empleado a un usuario del sistema para trazabilidad.',
            ],
            'faq' => [
                ['q' => '¿Para qué sirve el campo "Comisión"?', 'a' => 'Es el porcentaje de comisión sobre ventas del empleado. El módulo de comisiones está en desarrollo.'],
            ],
        ],

        // ─── ADMINISTRACIÓN ──────────────────────────────────────────────────

        'usuarios' => [
            'titulo'      => 'Usuarios del Sistema',
            'descripcion' => 'Gestión de accesos con roles y permisos.',
            'tips' => [
                'Cada usuario tiene un rol que determina a qué módulos y acciones puede acceder.',
                'No podés eliminar tu propio usuario activo.',
                'Las contraseñas se guardan con hash; nadie puede verlas una vez creadas.',
            ],
            'faq' => [
                ['q' => '¿Cuáles son los roles disponibles?', 'a' => 'Admin (acceso total), Supervisor, Cajero, Vendedor, Almacén. Los permisos de cada rol se configuran en Roles.'],
                ['q' => '¿Puedo crear roles personalizados?', 'a' => 'Sí. En Administración → Roles podés crear nuevos roles y asignarles los permisos que necesites.'],
            ],
        ],

        'roles' => [
            'titulo'      => 'Roles y Permisos',
            'descripcion' => 'Configuración de permisos por rol de usuario.',
            'tips' => [
                'Los roles "admin" y "supervisor" están protegidos y no pueden eliminarse.',
                'Cambiar los permisos de un rol afecta inmediatamente a todos los usuarios con ese rol.',
                'Los permisos están agrupados por módulo para facilitar la configuración.',
            ],
            'faq' => [
                ['q' => '¿Puedo tener un usuario sin rol?', 'a' => 'Un usuario sin rol no tendrá acceso a ningún módulo del sistema, solo verá el dashboard vacío.'],
                ['q' => '¿Los cambios de permisos son inmediatos?', 'a' => 'Sí. El cambio se aplica en la próxima carga de página del usuario afectado.'],
            ],
        ],

        'config' => [
            'titulo'      => 'Configuración del Sistema',
            'descripcion' => 'Parámetros generales de la empresa y del sistema.',
            'tips' => [
                'Los datos de empresa (CUIT, razón social) se usan en la generación de comprobantes.',
                'El punto de venta AFIP es necesario para la futura integración de facturación electrónica.',
                'La opción "Stock negativo" permite guardar ventas aunque el stock sea insuficiente.',
                'Podés cargar el logo de la empresa para que aparezca en PDFs e impresiones.',
            ],
            'faq' => [
                ['q' => '¿Para qué sirven las Listas de Precio 1-6?', 'a' => 'Son listas especiales que podés asignar a clientes específicos. Permiten tener distintos precios para distintos segmentos.'],
                ['q' => '¿Qué es el ajuste automático de precio costo?', 'a' => 'Si está activo, cada compra actualiza el precio costo del artículo con el último precio de compra registrado.'],
            ],
        ],

        'auditorias' => [
            'titulo'      => 'Auditorías',
            'descripcion' => 'Registro completo de operaciones realizadas por cada usuario.',
            'tips' => [
                'Usá los filtros de módulo y usuario para acotar la búsqueda rápidamente.',
                'El botón "Ver detalle" muestra el diff campo por campo: valor anterior vs. nuevo valor.',
                'Las auditorías son de solo lectura y no pueden modificarse ni eliminarse.',
                'Las operaciones del seeder y migraciones no generan registros de auditoría.',
            ],
            'faq' => [
                ['q' => '¿Qué operaciones se registran?', 'a' => 'Creación, modificación, eliminación y restauración de todos los registros principales: ventas, compras, clientes, artículos, etc.'],
                ['q' => '¿Se puede desactivar la auditoría?', 'a' => 'Sí, temporalmente con la variable de entorno AUDITING_ENABLED=false en el .env. No recomendado en producción.'],
                ['q' => '¿Por cuánto tiempo se conservan los registros?', 'a' => 'Indefinidamente. No hay borrado automático. El administrador puede gestionar la retención si fuera necesario.'],
            ],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Guías rápidas paso a paso
    |--------------------------------------------------------------------------
    */
    'guides' => [
        [
            'titulo'   => 'Registrar una venta',
            'modulos'  => ['ventas'],
            'pasos'    => [
                'Verificar que hay una caja abierta (badge verde "Caja abierta" en el topbar).',
                'Ir a Ventas → Nueva Venta.',
                'Seleccionar el cliente. La letra del comprobante se asigna automáticamente.',
                'Agregar ítems: seleccioná el artículo, ingresá la cantidad. El precio se carga solo.',
                'Verificar el total y aplicar descuento general si corresponde.',
                'Seleccionar la forma de pago (efectivo, transferencia, cta. cte., etc.).',
                'Guardar. El stock y la caja se actualizan automáticamente.',
            ],
        ],
        [
            'titulo'   => 'Cobrar a un cliente',
            'modulos'  => ['ventas', 'cc-clientes'],
            'pasos'    => [
                'Ir a Ventas → Recibos → Nuevo Recibo.',
                'Seleccionar el cliente. Se mostrará su saldo actual y las facturas pendientes.',
                'Ingresar el monto cobrado o usar "Pagar saldo completo".',
                'Seleccionar la forma de cobro.',
                'Guardar. El sistema aplica el cobro a las facturas más antiguas primero.',
            ],
        ],
        [
            'titulo'   => 'Registrar una compra',
            'modulos'  => ['compras'],
            'pasos'    => [
                'Ir a Compras → Nueva Compra.',
                'Seleccionar el proveedor y completar el nro. de factura del proveedor.',
                'Agregar ítems con su precio costo y alícuota de IVA.',
                'Seleccionar forma de pago: "Cuenta corriente" si queda pendiente, "Pago inmediato" si pagás al momento.',
                'Guardar. El stock se incrementa y el precio costo del artículo se actualiza.',
            ],
        ],
        [
            'titulo'   => 'Ajustar stock con pesada',
            'modulos'  => ['pesadas'],
            'pasos'    => [
                'Ir a Producción → Pesadas.',
                'Crear nueva pesada.',
                'Seleccionar el artículo. El sistema carga el stock actual del sistema.',
                'Ingresar el stock real contado físicamente.',
                'La diferencia se calcula automáticamente.',
                'Guardar. El stock del artículo queda actualizado al valor real.',
            ],
        ],
        [
            'titulo'   => 'Abrir y cerrar caja',
            'modulos'  => ['cajas'],
            'pasos'    => [
                'Ir a Caja y Finanzas → Cajas.',
                'Clic en "Abrir Caja". Ingresar el saldo inicial (dinero físico disponible).',
                'Durante el día, las ventas y cobros se acumulan automáticamente.',
                'Al finalizar el día, clic en "Cerrar Caja".',
                'Ingresar el saldo real contado en efectivo.',
                'El sistema calcula la diferencia y guarda el cierre.',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Mapa de rutas a claves de página (para alias)
    |--------------------------------------------------------------------------
    */
    'route_map' => [
        'ventas.create'      => 'ventas.create',
        'ventas.show'        => 'ventas.show',
        'clientes.create'    => 'clientes.create',
        'clientes.edit'      => 'clientes.edit',
        'compras.create'     => 'compras.create',
        'compras.show'       => 'compras.show',
        'proveedores.create' => 'proveedores',
        'proveedores.edit'   => 'proveedores',
        'articulos.create'   => 'articulos.create',
        'articulos.edit'     => 'articulos.create',
        'ordenes-pago.create'=> 'ordenes-pago',
        'ordenes-pago.show'  => 'ordenes-pago',
        'recibos.create'     => 'recibos',
        'recibos.show'       => 'recibos',
    ],
];
