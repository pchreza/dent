@echo off
setlocal EnableExtensions
cd /d "%~dp0"

echo ==============================================
echo Dent Dental SaaS - Laragon Windows Installer
echo ==============================================

where composer >nul 2>&1
if errorlevel 1 (
    echo [ERROR] Composer در PATH پيدا نشد. Composer را نصب و Laragon را restart کنيد.
    exit /b 1
)

if not exist "composer.json" (
    echo [ERROR] اين فايل بايد داخل ريشه پروژه، کنار composer.json اجرا شود.
    exit /b 1
)

if not exist "vendor\laravel\framework\src\Illuminate\Foundation\Application.php" (
    echo [INFO] vendor ناقص است؛ وابستگي‌هاي کامل Laravel نصب مي‌شوند.
    if exist "vendor" rmdir /s /q "vendor"
    composer install --no-interaction --prefer-dist --no-scripts
    if errorlevel 1 (
        echo [ERROR] نصب وابستگي‌ها شکست خورد.
        exit /b 1
    )
)

if not exist ".env" (
    copy /y ".env.example" ".env" >nul
    echo [INFO] .env از .env.example ساخته شد.
)

if not exist "database\database.sqlite" type nul > "database\database.sqlite"

rem ابتدا بدون script autoload را بازسازي مي‌کنيم تا در نصب ناقص، Composer script زودهنگام اجرا نشود.
composer dump-autoload --optimize --no-scripts
if errorlevel 1 (
    echo [ERROR] ساخت autoload شکست خورد.
    exit /b 1
)

php artisan key:generate --force
if errorlevel 1 exit /b 1
php artisan package:discover --ansi
if errorlevel 1 exit /b 1
php artisan optimize:clear
if errorlevel 1 exit /b 1
php artisan migrate --force
if errorlevel 1 exit /b 1

 echo.
echo [OK] نصب هسته کامل شد.
echo [NEXT] در مرورگر http://dent.test/install يا آدرس دامنه را باز کنيد.
echo [NOTE] براي توليد، APP_DEBUG=false و DB_CONNECTION=mysql را در .env تنظيم کنيد.
endlocal
exit /b 0
