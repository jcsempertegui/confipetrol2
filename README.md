## Instalar Dependencias de Composer. (activar la extensión zip en php.ini)

Primero, asegúrate de tener Composer instalado. Luego, ejecuta el siguiente comando para instalar las dependencias del proyecto:

-   composer install
-   cp .env.example .env
-   DB_CONNECTION=mysql
    DB_HOST=127.0.0.1
    DB_PORT=3306
    DB_DATABASE=bd
    DB_USERNAME=root
    DB_PASSWORD=
-   php artisan key:generate
-   php artisan migrate --seed
