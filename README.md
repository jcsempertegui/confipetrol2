# Confipetrol 2

## Puesta en producción

1. Copiar `.env.production.example` como `.env` y completar URL, base de datos y credenciales.
2. Generar una clave exclusiva con `php artisan key:generate`.
3. Mantener `APP_ENV=production`, `APP_DEBUG=false`, `SESSION_ENCRYPT=true` y `SESSION_SECURE_COOKIE=true` bajo HTTPS.
4. Ejecutar `composer install --no-dev --optimize-autoloader`, `npm ci` y `npm run build`.
5. Ejecutar `php artisan migrate --force`, `php artisan storage:link` y `php artisan optimize`.
6. Configurar el trabajador de colas y una tarea programada para `php artisan schedule:run`.
7. Crear un respaldo, verificar su archivo `.sha256` y probar periódicamente la restauración en una base aislada.

Los respaldos y las restauraciones requieren acceso autorizado; la restauración se limita al SUPER ADMIN y la pantalla exige confirmación reciente de contraseña.

Base administrativa construida con Laravel 12, Livewire 3 y MySQL.

## Módulos disponibles

- Autenticación y recuperación de contraseña
- Panel inicial
- Usuarios
- Roles y permisos
- Logs de auditoría
- Backups de MySQL
- Categorías y atributos configurables
- Productos con codificación automática y variantes

## Instalación

1. Ejecutar `composer install`.
2. Copiar `.env.example` a `.env` y configurar MySQL.
3. Definir `ADMIN_LOGIN`, `ADMIN_EMAIL` y `ADMIN_PASSWORD` en `.env`.
4. Ejecutar `php artisan key:generate`.
5. Ejecutar `php artisan migrate --seed`.
6. Ejecutar `npm install` y `npm run build`.

Las pruebas usan exclusivamente la base MySQL `confipetrol_testing`, configurada en `phpunit.xml`.
