# CentroGoma ERP

ERP web de Centro Goma (Laravel 12 · Livewire 3 · Alpine.js · Tailwind · MySQL) que reemplaza el sistema
Delphi XE5. Base tomada de la app Avícola y adaptada.

- **Producción:** https://centrogoma.rdbti.com
- **Estado, entorno de desarrollo y pendientes:** [`docs/ESTADO_PROYECTO.md`](docs/ESTADO_PROYECTO.md)
- **Arquitectura y reglas del código:** [`CLAUDE.md`](CLAUDE.md)
- **Servidor (instalación y actualización):** [`docs/DESPLIEGUE_VPS.md`](docs/DESPLIEGUE_VPS.md)

```bash
composer install && npm ci && npm run build
php artisan test
```
