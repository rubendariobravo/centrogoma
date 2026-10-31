# CentroGoma — Estado del proyecto

**Última actualización:** 31/10/2026 · **Commit de referencia:** ver `git log -1` · **Tests:** 133 / 492 assertions, todos pasan

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
| Sistema legacy | Delphi XE5 en `H:\Desarrollo\centrogoma24` (solo lectura) · BD `centro_goma` |
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
| Anulaciones | Venta / Recibo / OP con `AnulacionService` (revierte stock, caja, CC, cheques; bloquea casos inseguros) |
| Buscador de clientes | `BuscaCliente` + `<x-buscador-cliente>` en Ventas, Presupuestos, Recibos y Cheques (6.6K clientes) |
| Legacy | `centrogoma:importar` (rubros, artículos, clientes, proveedores, plan_cuenta) y `centrogoma:importar-saldos` (saldos de CC como comprobantes de saldo inicial) — ambos repetibles |
| Producción | Desplegado 26/10/2026, HTTPS, datos del Delphi al corte **28/05/2026** (ensayo: se reimporta en la salida real) |

**Bugs corregidos en el camino:** caja de ventas con pago mixto (un asiento por forma de pago) · percepciones IIBB/IVA
con cliente anterior al cambiar de cliente · vista de Cheques con el modelo viejo · seeder que creaba
`admin@avicola.test / password` · `Tipoiva` vs `TipoIva` (solo fallaba en Linux) · tests que apuntaban a la BD de avícola.

---

## 3. Armar el entorno en una PC nueva (Windows + Laragon)

**Requisitos:** PHP 8.3, Composer, Node 22, MySQL 8 (app, puerto **3307**) y un MySQL para la BD legacy (puerto **3306**;
la original es un MySQL 5.x).

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
DB_PORT=3307
DB_USERNAME=<usuario MySQL local>
DB_PASSWORD=<contraseña local>
LEGACY_DB_PORT=3306
LEGACY_DB_USERNAME=<usuario MySQL local>
LEGACY_DB_PASSWORD=<contraseña local>
```

**Bases** (en el MySQL 3307): `centrogoma`, `centrogoma_test` y `centrogoma_legacy_test` (las dos últimas las usan los
tests; `phpunit.xml` toma usuario/contraseña del `.env`).

**Datos del Delphi** (BD `centro_goma` en el MySQL 3306): copiarlos del servidor, que tiene el volcado restaurado:

```bash
ssh -i ~/.ssh/frangodata_vultr ruben@64.176.8.172 "sudo mysqldump --single-transaction --routines centro_goma | gzip" > centro_goma.sql.gz
gunzip -c centro_goma.sql.gz | mysql -h 127.0.0.1 -P 3306 -u <usuario> -p centro_goma
```

**Cargar y verificar:**

```bash
php artisan migrate --seed
php artisan centrogoma:importar
php artisan centrogoma:importar-saldos --corte=2026-05-28
php artisan db:seed --class=UsuarioTestingSeeder     # usuario de pruebas (credenciales en el seeder)
php artisan storage:link
php artisan test                                     # 133 tests
```

Virtual host `centrogoma.test` en Laragon apuntando a `public/`.

**Clave SSH del servidor:** `~/.ssh/frangodata_vultr` se copia a mano desde la PC anterior (nunca por el repo).

---

## 4. Pendientes

### Antes de la salida a producción real
1. **Validar el stock importado** contra el Delphi (`articulos.stock` sale de `producto.stock`, que puede estar desactualizado).
2. **Decisiones de administración sobre los saldos** (las lista el reporte de `importar-saldos`):
   - Saldos **a favor** (43 clientes, 14 proveedores): hoy se cargan solo como asiento de CC. Alternativa: no cargarlos y dejarlos en el reporte. Casos grandes: El Piave −$358.500, Full Work −$776.771, Gomazar −$650.168.
   - **CONSUMIDOR FINAL** (−$10.000) queda excluido.
   - Pago a proveedor 182 con fecha **16/02/2109**: corregir la fecha en el Delphi antes del volcado final.
3. **Limpiar en producción** lo que se cargue como prueba antes de reimportar (ventas, recibos, cajas de ensayo).
4. **Salida:** volcado actualizado del Delphi → `docs/DESPLIEGUE_VPS.md` §4 (`importar --force` + `importar-saldos` con el corte del último día operado).

### Seguridad
- **Cambiar la contraseña de MySQL** del usuario de desarrollo (`ruben`): estuvo en el historial de CentroGoma (ya limpiado) y **sigue publicada en el repo de avícola** (`phpunit.xml`), que necesita la misma limpieza.
- Cambiar la contraseña del admin de producción en el primer ingreso.
- En la PC anterior quedaron la rama local `respaldo/antes-limpieza` y `refs/original/` con el historial previo a la limpieza (con la contraseña vieja). No están en GitHub; borrarlas cuando no hagan falta.

### Funcionalidad (por prioridad)
1. **Anticipos de empleados** (`/anticipos-empleados`, egreso de caja concepto `anticipo`) — portar de avícola.
2. **Estadísticas** — avícola tiene `/estadisticas` y un plan en `avicola/docs/PLAN_ESTADISTICAS_COMERCIALES.md`.
3. **Dashboard con KPIs reales** (ventas del día, cheques por vencer, caja, saldos CC).
4. **Vista de cheque vinculado** en el detalle de Recibo/OP/Venta.
5. **AFIP** (facturación electrónica, nota de crédito para anular ventas con CAE) — último; requiere certificados.
6. Menores: recargo por entrega / descuento efectivo sobre el total (fase 6 pricing); cantidad tipeada antes de elegir artículo en Ventas.

### Para avícola (encontrado acá, no aplicado allá)
- Mismo bug de percepciones al cambiar de cliente en `Ventas\Form` (fix en CentroGoma: refrescar `clienteData` en el listener `items-actualizados`).

---

## 5. Cómo se trabajó (convenciones)

- Commits en español, en `master`, con tests verdes antes de cada commit; push a `origin`.
- Al portar de avícola: copiar componente **y** vista juntos, y verificar que coincidan (Cheques quedó con componente nuevo y vista vieja).
- Todo import legacy es **repetible** y tiene `--dry-run`: la salida real se hace con la BD del Delphi actualizada.
- Nunca versionar contraseñas; nunca `migrate:fresh` en producción; nunca `composer update` / `npm install` en el servidor.
- Actualizar producción: `ssh -i ~/.ssh/frangodata_vultr ruben@64.176.8.172 "sudo bash /var/www/centrogoma/deploy/actualizar.sh"`.
