# Estrategia de Migración Segura: CentroGoma → SaaS
## Sin afectar las 3 instancias en producción

**Fecha:** 22 de Marzo de 2026

---

## 1. ARQUITECTURA ACTUAL DEL SaaS EN PRODUCCIÓN

```
┌──────────────────────────────────────────────────────┐
│                  REPOSITORIO GIT (saas)               │
│              (código fuente compartido)               │
└───────┬──────────────┬──────────────┬────────────────┘
        │              │              │
   git pull        git pull       git pull
   (manual)        (manual)       (manual)
        │              │              │
   ┌────▼────┐    ┌────▼────┐    ┌────▼────┐
   │ Cliente1 │    │ Cliente2 │    │ Cliente3 │
   │  .env    │    │  .env    │    │  .env    │
   │  BD: X   │    │  BD: Y   │    │  BD: Z   │
   │  AFIP:.. │    │  AFIP:.. │    │  AFIP:.. │
   │  Logo:.. │    │  Logo:.. │    │  Logo:.. │
   └──────────┘    └──────────┘    └──────────┘
```

**Modelo actual:** Instancia-por-cliente (pseudo-SaaS). Mismo código, distinta config/BD.

**Riesgo:** Cualquier cambio en el código (nuevas migraciones, nuevos campos, nuevas rutas) 
se propaga a los 3 clientes cuando se hace `git pull` + `php artisan migrate`.

---

## 2. ESTRATEGIA: RAMA DEDICADA + BD NUEVA + INSTANCIA AISLADA

### Principio fundamental: **CERO impacto en producción**

```
┌──────────────────────────────────────────────────────────────┐
│                     REPOSITORIO GIT (saas)                    │
├──────────────────────────────────────────────────────────────┤
│                                                              │
│  main ─────────────────────────────────────────────────►     │
│    │     (los 3 clientes actuales siguen en main)            │
│    │                                                         │
│    └── feature/centrogoma ──────────────────────────►        │
│           (desarrollo CentroGoma en rama aislada)            │
│           (migraciones propias, módulo presupuestos)         │
│           (comando de migración de datos)                    │
│                                                              │
└──────────────────────────────────────────────────────────────┘

                    DESARROLLO LOCAL
                    ┌─────────────────┐
                    │ C:\laragon\www\ │
                    │   saas/         │  ← rama feature/centrogoma
                    │   .env          │  ← DB_DATABASE=centrogoma_saas
                    │                 │
                    │ Conexión extra: │
                    │   centro_goma   │  ← BD origen (Delphi, solo lectura)
                    │   centrogoma_   │
                    │   saas          │  ← BD destino (nueva, limpia)
                    └─────────────────┘
```

---

## 3. PLAN PASO A PASO

### PASO 1: Crear la rama de desarrollo
```bash
cd C:\laragon\www\saas
git checkout -b feature/centrogoma
```

### PASO 2: Crear la base de datos nueva (vacía)
```sql
CREATE DATABASE centrogoma_saas CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON centrogoma_saas.* TO 'ruben'@'localhost';
```

### PASO 3: Configurar .env local para CentroGoma
```env
# Cambiar solo estas líneas en .env local:
APP_NAME=CentroGoma
DB_DATABASE=centrogoma_saas

COMPANY_LOGO=logo_centrogoma.png
COMPANY_NAME="Centro Goma"
COMPANY_ALT_TEXT="Centro Goma Logo"

AFIP_CUIT=XXXXXXX  # CUIT de Centro Goma
AFIP_PUNTO_VENTA=X
AFIP_MODE=homo      # Empezar en homologación

# Conexión a la BD origen (solo para migración de datos)
LEGACY_DB_HOST=127.0.0.1
LEGACY_DB_PORT=3306
LEGACY_DB_DATABASE=centro_goma
LEGACY_DB_USERNAME=ruben
LEGACY_DB_PASSWORD=          # ver .env (no versionar contraseñas)
```

### PASO 4: Agregar conexión legacy en config/database.php
```php
'centro_goma_legacy' => [
    'driver' => 'mysql',
    'host' => env('LEGACY_DB_HOST', '127.0.0.1'),
    'port' => env('LEGACY_DB_PORT', '3306'),
    'database' => env('LEGACY_DB_DATABASE', 'centro_goma'),
    'username' => env('LEGACY_DB_USERNAME', 'ruben'),
    'password' => env('LEGACY_DB_PASSWORD', ''),
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => '',
    'strict' => false,  // false para tolerar datos legacy
],
```

### PASO 5: Ejecutar migraciones del SaaS en la BD nueva
```bash
php artisan migrate
# Esto crea TODAS las tablas del SaaS en centrogoma_saas
# Sin tocar ninguna BD de producción
```

### PASO 6: Crear migraciones adicionales (solo en rama feature/centrogoma)

#### 6a. Migración: Módulo Presupuestos (no existe en SaaS)
- `create_presupuesto_table`
- `create_detalle_presupuesto_table` (si la existente no cubre la estructura)

#### 6b. Migración: Campos legacy_id para trazabilidad
- `add_legacy_id_to_tables` → agrega `legacy_id` nullable a: articulo, cliente, proveedor, venta, compra, etc.

#### 6c. Migración: Ajustes específicos CentroGoma
- Campos que el SaaS no tenga pero CentroGoma necesite (ej: `metraje` en artículo)

### PASO 7: Crear comando Artisan de migración de datos
```
app/Console/Commands/MigrarCentroGoma.php
```
Este comando:
1. Lee de `centro_goma_legacy` (BD Delphi, **solo lectura**)
2. Escribe en `centrogoma_saas` (BD nueva)
3. Mapea IDs viejos → nuevos via `legacy_id`
4. Se ejecuta las veces que sea necesario (idempotente)
5. Genera reporte de validación al final

### PASO 8: Validar datos migrados
```bash
php artisan centrogoma:migrate --validate
# Compara totales, saldos, conteos entre ambas BDs
```

### PASO 9: Merge controlado a main (cuando esté listo)
```bash
# Solo cuando CentroGoma esté validado y listo para producción:
git checkout main
git merge feature/centrogoma

# Las migraciones nuevas se agregan al final
# Los 3 clientes existentes: al hacer migrate, se agregan las tablas/campos nuevos
# pero como son campos nullable o tablas nuevas, NO rompen nada
```

---

## 4. ¿QUÉ PASA CON LOS 3 CLIENTES AL HACER MERGE?

### Migraciones que se agregarían:

| Migración | Impacto en clientes existentes |
|-----------|-------------------------------|
| `add_legacy_id_to_tables` | Agrega columna nullable → **CERO impacto** (queda NULL) |
| `create_presupuesto_table` | Crea tabla nueva → **CERO impacto** (tabla vacía) |
| `create_detalle_presupuesto_table` | Crea tabla nueva → **CERO impacto** |
| Campos extras CentroGoma | Columnas nullable → **CERO impacto** |

**Regla de oro:** Toda migración nueva DEBE ser aditiva (agregar, nunca modificar/eliminar).

### ¿Y si un cliente existente quiere usar Presupuestos?
¡Lo puede hacer! Ya tendría la tabla y solo necesitaría la ruta y el controller, 
que también estarían en el código mergeado.

---

## 5. FLUJO DE TRABAJO COMPLETO

```
Semana 1-2: PREPARACIÓN
├── Crear rama feature/centrogoma
├── Crear BD centrogoma_saas
├── Crear migraciones nuevas (presupuestos, legacy_id, etc.)
├── php artisan migrate (en centrogoma_saas)
└── Crear comando MigrarCentroGoma (estructura base)

Semana 3-4: MIGRACIÓN DE DATOS
├── Implementar migración de maestros (productos, clientes, proveedores, etc.)
├── Implementar migración de transacciones (ventas, compras, CC, etc.)
├── Ejecutar y depurar migración
└── Validar datos (saldos, totales, conteos)

Semana 5: AJUSTES Y PERSONALIZACIÓN
├── Config CentroGoma (logo, AFIP, .env)
├── Ajustes de UI si se requieren
├── Módulo presupuestos (controller + vistas)
└── Pruebas funcionales completas

Semana 6: MERGE Y DEPLOY
├── Merge feature/centrogoma → main
├── Deploy en los 3 clientes: git pull + php artisan migrate (migraciones aditivas)
├── Deploy instancia CentroGoma (nueva) con su .env y BD propia
└── Migración final de datos (con datos actualizados de centro_goma)

Semana 7: GO-LIVE
├── CentroGoma en producción
├── Período de soporte intensivo
├── Sistema Delphi como respaldo (solo lectura)
└── Baja definitiva del Delphi tras validación completa
```

---

## 6. ARQUITECTURA FINAL (POST-MIGRACIÓN)

```
┌──────────────────────────────────────────────────────┐
│                  REPOSITORIO GIT (saas)               │
│                   rama: main                          │
│  (incluye módulo presupuestos + campos legacy_id)    │
└──┬──────────┬──────────┬──────────┬──────────────────┘
   │          │          │          │
   ▼          ▼          ▼          ▼
┌──────┐  ┌──────┐  ┌──────┐  ┌────────────┐
│ Cli1 │  │ Cli2 │  │ Cli3 │  │ CentroGoma │  ← NUEVO
│ BD:X │  │ BD:Y │  │ BD:Z │  │ BD:centro  │
│ .env │  │ .env │  │ .env │  │    goma_saas│
└──────┘  └──────┘  └──────┘  │ .env       │
                               │ Logo propio│
                               │ AFIP propio│
                               └────────────┘
```

**4 instancias, mismo código, cada una con su BD y configuración.**

---

## 7. CHECKLIST DE SEGURIDAD

- [ ] **NUNCA** ejecutar migraciones de la rama feature/centrogoma en las BDs de producción
- [ ] **NUNCA** apuntar .env a una BD de producción durante desarrollo
- [ ] La conexión `centro_goma_legacy` es **SOLO LECTURA** (no hace INSERT/UPDATE/DELETE en la BD Delphi)
- [ ] Todas las migraciones nuevas son **ADITIVAS** (nullable, tablas nuevas)
- [ ] El comando de migración es **IDEMPOTENTE** (puede re-ejecutarse sin duplicar datos)
- [ ] Antes del merge: revisar que ninguna migración altere tablas existentes de forma destructiva
- [ ] Después del merge: los 3 clientes ejecutan `php artisan migrate` normalmente
- [ ] La BD `centro_goma` (Delphi) se mantiene intacta como respaldo

---

## 8. PRÓXIMOS PASOS INMEDIATOS

1. **Crear la rama** `feature/centrogoma` desde `main`
2. **Crear la BD** `centrogoma_saas` vacía en MySQL
3. **Configurar .env** apuntando a `centrogoma_saas`
4. **Agregar conexión** `centro_goma_legacy` en `config/database.php`
5. **Ejecutar** `php artisan migrate` para crear estructura del SaaS
6. **Crear** las migraciones adicionales (presupuestos, legacy_id)
7. **Crear** el comando `php artisan centrogoma:migrate`
8. **Empezar** la migración de datos maestros

¿Procedemos con el paso 1?
