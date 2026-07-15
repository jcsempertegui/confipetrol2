# Confipetrol 2

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
