# Despliegue de CentroGoma en el VPS (centrogoma.rdbti.com)

VPS Vultr compartido con FrangoData, frangoadmin, isdm y mascotacenter (Ubuntu 24.04, Apache 2 + PHP 8.3,
MySQL 8.0 solo local, Node 22). La instalación base del servidor está en `frangodata/docs/INSTALACION_VPS.md`;
acá solo lo propio de CentroGoma. **No tocar las otras apps ni sus bases.**

| | |
|---|---|
| Servidor | `64.176.8.172` — `ssh -i ~/.ssh/frangodata_vultr ruben@64.176.8.172`, después `sudo -i` |
| URL | `https://centrogoma.rdbti.com` |
| Código | `/var/www/centrogoma` (dueño `root:www-data`; `storage` y `bootstrap/cache` de `www-data`) |
| Repo | `git@github-centrogoma:rubendariobravo/centrogoma.git` (deploy key `/root/.ssh/centrogoma_deploy`, solo lectura) |
| BD app | `centrogoma` — usuario MySQL `centrogoma` |
| BD legacy | `centro_goma` (volcado del Delphi) — usuario `centrogoma_legacy_ro`, **solo SELECT** |
| Secretos | `/root/credenciales-centrogoma.txt` (solo root). Nunca en el repo |
| Logs | `/var/www/centrogoma/storage/logs/laravel-*.log`, `/var/log/apache2/centrogoma_error.log` |

---

## 1. Clave de despliegue (una por repo)

```bash
ssh-keygen -t ed25519 -f /root/.ssh/centrogoma_deploy -N "" -C "centrogoma-vps"
cat >> /root/.ssh/config <<'EOF'

Host github-centrogoma
    HostName github.com
    User git
    IdentityFile /root/.ssh/centrogoma_deploy
    IdentitiesOnly yes
EOF
cat /root/.ssh/centrogoma_deploy.pub
```

GitHub → **rubendariobravo/centrogoma → Settings → Deploy keys → Add deploy key**, pegar la clave **sin**
"Allow write access". Probar: `ssh -T git@github-centrogoma`.

## 2. Bases y usuarios MySQL

Las contraseñas se generan en el servidor y van directo a `/root/credenciales-centrogoma.txt`:

```bash
genpass() { openssl rand -base64 24 | tr -d '/+=' | cut -c1-24; }
P_APP=$(genpass); P_RO=$(genpass)
mysql <<SQL
CREATE DATABASE IF NOT EXISTS centrogoma  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS centro_goma CHARACTER SET latin1;
CREATE USER IF NOT EXISTS 'centrogoma'@'localhost'           IDENTIFIED BY '${P_APP}';
CREATE USER IF NOT EXISTS 'centrogoma_legacy_ro'@'localhost' IDENTIFIED BY '${P_RO}';
GRANT ALL PRIVILEGES ON centrogoma.* TO 'centrogoma'@'localhost';
GRANT SELECT ON centro_goma.* TO 'centrogoma_legacy_ro'@'localhost';
SQL
umask 077; cat >> /root/credenciales-centrogoma.txt <<EOF
MYSQL centrogoma            ${P_APP}
MYSQL centrogoma_legacy_ro  ${P_RO}
EOF
```

## 3. Código, dependencias y `.env`

```bash
git clone git@github-centrogoma:rubendariobravo/centrogoma.git /var/www/centrogoma
cd /var/www/centrogoma
composer install --no-dev --optimize-autoloader
npm ci && npm run build
cp .env.example .env            # plantilla de producción: completar contraseñas y admin
php artisan key:generate --force
```

En `.env`: `DB_PASSWORD`, `LEGACY_DB_PASSWORD` (de credenciales), `CENTROGOMA_ADMIN_EMAIL` y
`CENTROGOMA_ADMIN_PASSWORD` (generar y anotar en credenciales como `APP admin`).

```bash
chown -R root:www-data /var/www/centrogoma
chown -R www-data:www-data storage bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache
chmod 640 .env && chown root:www-data .env
sudo -u www-data php artisan migrate --force --seed      # roles, listas, datos AFIP, admin inicial
sudo -u www-data php artisan storage:link
```

## 4. Datos del Delphi

Volcado desde la PC de desarrollo (la BD `centro_goma` del puerto 3306) y copia al servidor:

```bash
# PC de desarrollo
mysqldump -h 127.0.0.1 -P 3306 -u ruben -p --single-transaction --routines centro_goma | gzip > centro_goma.sql.gz
scp -i ~/.ssh/frangodata_vultr centro_goma.sql.gz ruben@64.176.8.172:/tmp/
```

```bash
# Servidor (root)
gunzip -c /tmp/centro_goma.sql.gz | mysql centro_goma && rm /tmp/centro_goma.sql.gz
cd /var/www/centrogoma
sudo -u www-data php artisan centrogoma:importar                     # rubros, artículos, clientes, proveedores, plan_cuenta
sudo -u www-data php artisan centrogoma:importar-saldos --corte=AAAA-MM-DD --dry-run
sudo -u www-data php artisan centrogoma:importar-saldos --corte=AAAA-MM-DD
```

Para la **salida a producción** se repite este paso con el volcado actualizado del Delphi: reemplazar la
base (`DROP DATABASE centro_goma; CREATE DATABASE centro_goma CHARACTER SET latin1;` y restaurar), después
`centrogoma:importar --force` e `importar-saldos` con el corte del último día operado (ver CLAUDE.md).

## 5. Apache + HTTPS

```bash
cat > /etc/apache2/sites-available/centrogoma.conf <<'EOF'
<VirtualHost *:80>
    ServerName centrogoma.rdbti.com
    DocumentRoot /var/www/centrogoma/public
    <Directory /var/www/centrogoma/public>
        AllowOverride All
        Require all granted
    </Directory>

    # Límites de PHP solo para esta app (PDFs, Excel de listas, grilla de precios)
    php_admin_value memory_limit 512M
    php_admin_value upload_max_filesize 20M
    php_admin_value post_max_size 20M
    php_admin_value max_input_vars 5000

    # ERP privado: que los buscadores no lo indexen
    Header always set X-Robots-Tag "noindex, nofollow"

    ErrorLog ${APACHE_LOG_DIR}/centrogoma_error.log
    CustomLog ${APACHE_LOG_DIR}/centrogoma_access.log combined
</VirtualHost>
EOF
a2ensite centrogoma && apache2ctl configtest && systemctl reload apache2
certbot --apache -n --redirect -d centrogoma.rdbti.com
```

## 6. Cachés y verificación

```bash
cd /var/www/centrogoma
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache
curl -sI https://centrogoma.rdbti.com/login | head -1       # HTTP/1.1 200 OK
```

No hay tareas programadas ni colas (QUEUE_CONNECTION=sync): no hace falta cron.

## 7. Actualizar más adelante

`deploy/actualizar.sh` (como root, desde `/var/www/centrogoma`). Reglas: **nunca** `composer update` ni
`npm install` en el servidor; si al terminar `git status` no está limpio, algo se ejecutó mal.
