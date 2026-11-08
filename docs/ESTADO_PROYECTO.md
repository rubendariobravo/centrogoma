# CentroGoma — Estado del proyecto

**Última actualización:** 07/11/2026 · **Commit de referencia:** ver `git log -1` · **Tests:** 165 / 669 assertions, todos pasan

Documento para retomar el trabajo en otra PC. Las reglas técnicas y la arquitectura están en `CLAUDE.md`;
la instalación del servidor en `docs/DESPLIEGUE_VPS.md`. Acá: **qué está hecho, cómo armar el entorno y qué sigue.**

---

## 1. Dónde está cada cosa

| | |
|---|---|
| Repositorio | `https://github.com/rubendariobravo/centrogoma` — rama `master` (única) |
| Producción | `https://centrogoma.rdbti.com` — VPS Vultr `64.176.8.172`, `/var/www/centrogoma` |
| Acceso al servidor | `ssh -i ~/.ssh/frangodata_vultr ruben@64.176.8.172`, después `sudo -i` (sudo sin contraseña) |
| Admin de producción | `rubendario.bravo@gmail.com` — contraseña en `/root/credenciales-centrogoma.txt` (línea `APP admin`) |
| Secretos del servidor | `/root/credenciales-centrogoma.txt` (MySQL `centrogoma`, `centrogoma_legacy_ro`, admin) |
| Sistema legacy | Delphi XE5 en `C:\desarrollo\centrogoma24` (solo lectura) · BD `centro_goma` (copia local restaurada al 31/10/2026) |
| App base | `C:\laragon\www\avicola` (`github.com/rubendariobravo/avicola`) — de donde se portan módulos |

El servidor es **compartido** con FrangoData, frangoadmin, isdm y mascotacenter: no tocar sus sitios, bases ni crons.

---

## 2. Qué está hecho

**Base (Fases 0–2):** app limpia desde avícola · Presupuestos (con conversión a venta) · motor de precios multi-lista
con tramos y overrides por cliente · Ventas, Recibos, Compras, Órdenes de Pago, Clientes, Proveedores, Artículos.

**Portado / construido desde avícola (sept–oct 2026):**

| Área | Qué hay |
|---|---|
| Caja | `CajaService` (libro `movimiento_cajas`), cajas diaria/general/banco, transferencias, ajustes, arqueo de efectivo, detalle `/cajas/{caja}` |
| Cheques | Modelo tercero/propio con `ChequeService` (impacto en caja al acreditar/debitar, rechazo reabre deuda) · vista actual de avícola |
| Compras | `CompraService` (procesar/revertir: stock, costo, CC o caja) · anulación con motivo |
| Gastos / Bancos | `/gastos` (con ABM de categorías) y `/bancos` |
| Anticipos | `/anticipos-empleados` portado de avícola (multi-medio, un egreso por caja; sin liquidaciones de sueldo) · acceso desde cada empleado |
| Anulaciones | Venta / Recibo / OP con `AnulacionService` (revierte stock, caja, CC, cheques; bloquea casos inseguros) |
| Buscador de clientes | `BuscaCliente` + `<x-buscador-cliente>` en Ventas, Presupuestos, Recibos y Cheques (6.6K clientes) |
| Legacy | `centrogoma:importar` (rubros, artículos, clientes, proveedores, plan_cuenta) y `centrogoma:importar-saldos` (saldos de CC como comprobantes de saldo inicial) — ambos repetibles |
| Estadísticas / Dashboard | `/estadisticas` (resumen, clientes top, proveedores top) portado de avícola sin saldos iniciales, sin stock y con anticipos como sueldos · Dashboard con ventas del día/mes, a cobrar, a pagar, cheques a 7 días y mayores deudores |
| Presupuestos | Revisados contra el Delphi (07/11/2026): duplicar (copia editable, "actualizar precios a la lista de hoy"), presupuesto sin cliente registrado, pasaje a venta con cantidades y precios pactados (el motor ya no los pisa; "Lista hoy" de referencia), totales iguales a ventas. Corregidos: editar bajaba el precio un 21% por guardado, buscador de artículos roto (`$wire`), precio de lista nunca aplicado (campos inexistentes del motor) |
| Producción | Desplegado 26/10/2026, HTTPS, datos del Delphi al corte **28/05/2026** (ensayo: se reimporta en la salida real) |

**Bugs corregidos en el camino:** caja de ventas con pago mixto (un asiento por forma de pago) · percepciones IIBB/IVA
con cliente anterior al cambiar de cliente · vista de Cheques con el modelo viejo · seeder que creaba
`admin@avicola.test / password` · `Tipoiva` vs `TipoIva` (solo fallaba en Linux) · tests que apuntaban a la BD de avícola.

---

## 3. Armar el entorno en una PC nueva (Windows + Laragon)

**Requisitos:** PHP 8.3, Composer, Node 22 y MySQL 8. En la PC actual (desde 31/10/2026) la BD de la app y la legacy
están en **la misma instancia, puerto 3306** (la conexión legacy es solo host/puerto/base: no hace falta otra instancia;
en la PC anterior eran 3307 app / 3306 legacy).

```bash
git clone https://github.com/rubendariobravo/centrogoma.git C:/laragon/www/centrogoma
cd C:/laragon/www/centrogoma
composer install
npm ci && npm run build
cp .env.example .env
php artisan key:generate
```

`.env.example` es la plantilla **de producción**. Para desarrollo cambiar:

```ini
APP_ENV=local
APP_DEBUG=true
APP_URL=http://centrogoma.test
LOG_CHANNEL=stack
LOG_LEVEL=debug
SESSION_SECURE_COOKIE=false
DB_PORT=3306
DB_USERNAME=<usuario MySQL local>
DB_PASSWORD=<contraseña local>
LEGACY_DB_PORT=3306
LEGACY_DB_USERNAME=<usuario MySQL local>
LEGACY_DB_PASSWORD=<contraseña local>
```

**Bases:** `centrogoma`, `centrogoma_test` y `centrogoma_legacy_test` (las dos últimas vacías, las usan los tests).
`phpunit.xml` fija solo los **nombres** de las bases de test; puerto, usuario y contraseña los toma del `.env`, así que
las de test tienen que existir en la misma instancia que `DB_PORT` / `LEGACY_DB_PORT`:

```sql
CREATE DATABASE centrogoma_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE centrogoma_legacy_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

**Datos del Delphi** (BD `centro_goma`): restaurar un volcado del Delphi, o copiarlo del servidor (que tiene el del ensayo):

```bash
ssh -i ~/.ssh/frangodata_vultr ruben@64.176.8.172 "sudo mysqldump --single-transaction --routines centro_goma | gzip" > centro_goma.sql.gz
gunzip -c centro_goma.sql.gz | mysql -h 127.0.0.1 -P 3306 -u <usuario> -p centro_goma
```

Si el host `centrogoma.test` no resuelve, recargar Laragon (agrega la entrada al `hosts`; el vhost ya lo genera solo).

**Cargar y verificar:**

```bash
php artisan migrate --seed
php artisan centrogoma:importar
php artisan centrogoma:importar-saldos --corte=2026-05-28
php artisan db:seed --class=UsuarioTestingSeeder     # usuario de pruebas (credenciales en el seeder)
php artisan storage:link
php artisan test                                     # 165 tests
```

Virtual host `centrogoma.test` en Laragon apuntando a `public/`.

**Clave SSH del servidor:** `~/.ssh/frangodata_vultr` se copia a mano desde la PC anterior (nunca por el repo).

---

## 4. Pendientes

### Antes de la salida a producción real

**Legacy local restaurada al 31/10/2026** e importada en la BD `centrogoma` local (maestros + saldos al corte 31/10). Contra
esa legacy: maestros → 61 artículos, 100 clientes y 3 proveedores nuevos; `importar-saldos --corte=2026-10-31` → 48
clientes deudores ($146.512.590,03), 42 a favor (−$1.106.444,49), 47 proveedores acreedores ($58.551.662,81), 12 a favor
nuestro (−$2.320.059,00). Movimientos con fecha posterior al corte: proveedor 182 (16/02/2109) y **proveedor 58 ROGUANT
S.R.L. (28/11/2026, $425.539,38)** — confirmar si es error de carga o un comprobante a fecha futura.

**Simulación de ventas (31/10/2026):** las 25 ventas del Delphi de ese día se registraron con el `Ventas\Form` real dentro
de una transacción con rollback (5 en CC). Con los precios cobrados coinciden al centavo totales, caja (efectivo $378.500,
CC $5.674.300, tarjeta $15.000) y cuenta corriente. Corrigió el redondeo de precios (neto con 4 decimales), los totales
recalculados en el servidor y la transacción en `registrarVenta()`. Dejó estas decisiones:

1. **Stock: el negocio no lo lleva, por decisión propia** (confirmado 07/11/2026; en el Delphi `producto.stock` es NULL en
   los 7.230 productos). Todo se importó con stock 0 y cada venta lo deja negativo; la opción `stock_negativo` de `/config`
   no se aplica en ningún lado. Falta definir si se apaga el movimiento (`modifica_stock = false` en los importados) y se
   oculta el stock de las pantallas, o se deja correr negativo sin mirarlo.
2. **~1.258 artículos con precio de lista < $100** (capa impermeable $14, mameluco $4,60…), al parecer en dólares: en el
   Delphi el vendedor tipea el precio real (10% de los renglones de octubre). Preguntar a administración cómo se cotizan;
   si no, en el sistema nuevo se cobran $14 salvo que el vendedor corrija el precio.
3. **El corte de saldos tiene que ser el último día operado en el Delphi** y el sistema nuevo arrancar al día siguiente:
   las ventas en CC del día del corte ya están en el saldo inicial (si se superponen, la deuda se cuenta dos veces).
4. Hay dos clientes "Consumidor final": el sembrado (#1, el que usan las ventas sin cliente) y el importado del Delphi
   (legacy 1). Unificar o dar de baja el importado.
5. **Decisiones de administración sobre los saldos** (las lista el reporte de `importar-saldos`):
   - Saldos **a favor** (43 clientes, 14 proveedores): hoy se cargan solo como asiento de CC. Alternativa: no cargarlos y dejarlos en el reporte. Casos grandes: El Piave −$358.500, Full Work −$776.771, Gomazar −$650.168.
   - **CONSUMIDOR FINAL** (−$10.000) queda excluido.
   - Pago a proveedor 182 con fecha **16/02/2109**: corregir la fecha en el Delphi antes del volcado final.
6. **Limpiar en producción** lo que se cargue como prueba antes de reimportar (ventas, recibos, cajas de ensayo).
7. **Salida:** volcado actualizado del Delphi → `docs/DESPLIEGUE_VPS.md` §4 (`importar --force` + `importar-saldos` con el corte del último día operado).
8. **Revisar la ficha de clientes y proveedores en producción** antes de la salida. Hasta el 07/11/2026,
   `importar --force` ponía `saldo = 0` en la ficha y la corrida siguiente de `importar-saldos` le restaba el saldo
   inicial anterior: la ficha quedaba descuadrada respecto de su CC (pasó en local: 85 clientes y 60 proveedores,
   reparados recalculando `saldo = SUM(debe − haber)` de `cc_clientes`/`cc_proveedores`). Corregido en el comando; en
   producción solo pasa si ya se corrió `importar --force` después de los saldos. Control: no debe devolver filas
   `SELECT c.id FROM clientes c LEFT JOIN (SELECT cliente_id, SUM(debe-haber) s FROM cc_clientes GROUP BY cliente_id) x
   ON x.cliente_id = c.id WHERE ABS(c.saldo - COALESCE(x.s, 0)) > 0.009` (ídem proveedores).

### Seguridad
- **Cambiar la contraseña de MySQL** del usuario de desarrollo (`ruben`): estuvo en el historial de CentroGoma (ya limpiado) y **sigue publicada en el repo de avícola** (`phpunit.xml`), que necesita la misma limpieza.
- Cambiar la contraseña del admin de producción en el primer ingreso.
- En la PC anterior quedaron la rama local `respaldo/antes-limpieza` y `refs/original/` con el historial previo a la limpieza (con la contraseña vieja). No están en GitHub; borrarlas cuando no hagan falta.

### Funcionalidad (por prioridad)

Surge de la comparación con el Delphi y su uso real: **`docs/COMPARACION_DELPHI.md`** (07/11/2026). AFIP pasó de
"último" a bloqueante: el Delphi emite 1.628 comprobantes electrónicos al año y el 87% de las ventas sale por
impresora fiscal.

1. **Decidir impresora fiscal vs. todo electrónico** (decisión del negocio) y **AFIP**: factura electrónica A/B,
   nota de crédito A/B, FCE MiPyME, reimpresión. Requiere certificado y punto de venta electrónico.
2. **Nota de crédito de venta** (reemplaza anular en comprobantes con CAE).
3. **Artículos en dólares** y presupuestos en USD (ver punto 2 de la salida).
4. **Libros IVA Ventas e IVA Compras** (PDF + Excel); compras necesita tipo de comprobante (nota de crédito de compra),
   punto de venta y fecha contable.
5. Chicas de uso diario: tarjeta por marca y Mercado Pago como formas de pago · presupuesto con/sin IVA y sin leyenda ·
   orden de compra del cliente en la venta · imprimir/exportar CC y facturas pendientes · lista de precios en PDF.
6. **Vista de cheque vinculado** en el detalle de Recibo/OP/Venta.
7. Menores: recargo por entrega / descuento efectivo sobre el total (fase 6 pricing); cantidad tipeada antes de elegir artículo en Ventas; el detalle de un comprobante de saldo inicial (`/ventas/{id}`) se ve
como venta común (fila vacía, Totales en $0, aviso de caja); un anticipo ya aprobado no se puede corregir ni rechazar (igual que en avícola, cuya ayuda dice lo contrario): hoy solo queda un ajuste de caja.

### Para avícola (encontrado acá, no aplicado allá)
- Mismo bug de percepciones al cambiar de cliente en `Ventas\Form` (fix en CentroGoma: refrescar `clienteData` en el listener `items-actualizados`).

---

## 5. Cómo se trabajó (convenciones)

- Commits en español, en `master`, con tests verdes antes de cada commit; push a `origin`.
- Al portar de avícola: copiar componente **y** vista juntos, y verificar que coincidan (Cheques quedó con componente nuevo y vista vieja).
- Todo import legacy es **repetible** y tiene `--dry-run`: la salida real se hace con la BD del Delphi actualizada.
- Nunca versionar contraseñas; nunca `migrate:fresh` en producción; nunca `composer update` / `npm install` en el servidor.
- Actualizar producción: `ssh -i ~/.ssh/frangodata_vultr ruben@64.176.8.172 "sudo bash /var/www/centrogoma/deploy/actualizar.sh"`.
