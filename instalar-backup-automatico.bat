@echo off
echo ============================================
echo  INSTALAR BACKUP AUTOMATICO - CONFIPETROL
echo ============================================
echo.

net session >nul 2>&1
if %errorLevel% neq 0 (
    echo [ERROR] Debe ejecutar este archivo como ADMINISTRADOR.
    echo.
    echo Clic derecho en el archivo y seleccione "Ejecutar como administrador"
    echo.
    pause
    exit /b 1
)

set PHP_EXE=C:\xampp\php\php.exe
set PROJECT_PATH=c:\Users\Desktop\Desktop\CUSTOMERS PROYECTS\Confipetrol
set TASK_NAME=ConfipetrolBackupScheduler

echo Eliminando tarea anterior si existe...
schtasks /delete /tn "%TASK_NAME%" /f >nul 2>&1

echo Registrando tarea programada...
schtasks /create /tn "%TASK_NAME%" /tr "\"%PHP_EXE%\" -f \"%PROJECT_PATH%\artisan\" schedule:run" /sc MINUTE /mo 1 /ru SYSTEM /rl HIGHEST /f

if %errorLevel% equ 0 (
    echo.
    echo ============================================
    echo  INSTALACION EXITOSA
    echo ============================================
    echo  Backup automatico activo:
    echo  - Cada dia a la 01:00 AM
    echo  - Al iniciar sesion en el sistema
    echo  - Manual desde el menu Administracion > Backups BD
    echo ============================================
    echo.
) else (
    echo.
    echo [ERROR] No se pudo registrar. Intente de nuevo como Administrador.
    echo.
)

pause
