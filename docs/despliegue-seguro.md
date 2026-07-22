# Despliegue seguro de Confipetrol

## Configuración obligatoria

1. Copiar `.env.production.example` como `.env` en el servidor.
2. Definir una `APP_KEY` exclusiva con `php artisan key:generate`.
3. Usar un usuario MariaDB exclusivo, sin permisos globales y con acceso únicamente a la base del sistema. La restauración requiere permiso temporal para crear y eliminar la base de validación.
4. Configurar HTTPS y mantener `SESSION_SECURE_COOKIE=true`, `SESSION_ENCRYPT=true` y `APP_DEBUG=false`.
5. Ejecutar `php artisan migrate --force`, `php artisan optimize` y `php artisan storage:link` solamente si se habilitan archivos públicos.

La `APP_KEY` también protege los respaldos `.cfpbak`. Debe conservarse fuera del repositorio y dentro del plan de recuperación; perderla impide descifrar los respaldos.

## Programador de tareas

Laravel debe ejecutarse cada minuto. La aplicación genera el respaldo automático a la 1:00 a. m. y evita ejecuciones simultáneas.

En Windows, crear una tarea que ejecute `php artisan schedule:run` cada minuto, usando como directorio de trabajo la raíz del proyecto.

En Linux:

```cron
* * * * * cd /ruta/confipetrol2 && php artisan schedule:run >> /dev/null 2>&1
```

Verificación:

```bash
php artisan schedule:list
php artisan backup:database --type=automatico
```

## Respaldos

- Los respaldos nuevos se guardan cifrados y autenticados con extensión `.cfpbak`.
- Cada archivo tiene un checksum SHA-256 adicional.
- Solo un SUPER ADMIN con `descargar-backup` puede descargar la base.
- Antes de restaurar se genera un respaldo de seguridad y se valida el archivo en una base temporal.
- Si la importación falla, el sistema intenta recuperar automáticamente la copia previa.
- Si también falla la recuperación, la aplicación permanece en mantenimiento para impedir nuevas escrituras.

Para proteger respaldos SQL antiguos:

```bash
php artisan backup:encrypt-legacy
```

## Verificación posterior

```bash
composer audit --locked
npm audit --omit=dev
npm run build
php artisan test
```
