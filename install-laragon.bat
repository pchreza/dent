@echo off
setlocal EnableExtensions DisableDelayedExpansion
cd /d "%~dp0"

set "LOCAL_URL=http://dent.test"
set "INSTALL_LOCK=storage\app\private\installed.lock"
set "BUILD_MANIFEST=public\build\manifest.json"

echo ==============================================================
echo Dent Dental SaaS - Laragon Local Bootstrap
echo ==============================================================
echo Project: %CD%
echo.

if not exist "composer.json" (
    echo [ERROR] Run this file from the project root beside composer.json.
    goto :failure
)

if not exist "artisan" (
    echo [ERROR] artisan was not found. The project files are incomplete.
    goto :failure
)

call :require_command php PHP
if errorlevel 1 goto :failure
call :require_command composer Composer
if errorlevel 1 goto :failure

rem Create all Laravel writable runtime paths before any Artisan command.
for %%D in (
    "bootstrap\cache"
    "storage\logs"
    "storage\framework\cache"
    "storage\framework\sessions"
    "storage\framework\views"
    "storage\framework\testing"
    "storage\app\private"
) do (
    call :ensure_directory "%%~D"
    if errorlevel 1 goto :failure
)

rem A local environment file is required for APP_KEY and SQLite configuration.
if not exist ".env" (
    if not exist ".env.example" (
        echo [ERROR] .env.example was not found.
        goto :failure
    )

    copy /y ".env.example" ".env" >nul
    if errorlevel 1 (
        echo [ERROR] Could not create .env from .env.example.
        goto :failure
    )

    echo [INFO] Created local .env from .env.example.
)

if not exist "database\database.sqlite" (
    type nul > "database\database.sqlite"
    if errorlevel 1 (
        echo [ERROR] Could not create database\database.sqlite.
        goto :failure
    )

    echo [INFO] Created local SQLite database file.
)

rem Always honor composer.lock so first install and dependency updates are both safe.
call :run composer install --no-interaction --prefer-dist --no-scripts
if errorlevel 1 goto :failure
call :run composer dump-autoload --optimize --no-scripts
if errorlevel 1 goto :failure

rem Generate an application key only when the existing .env does not have one.
findstr /r /c:"^APP_KEY=base64:." ".env" >nul
if errorlevel 1 (
    call :run php artisan key:generate --force
    if errorlevel 1 goto :failure
    echo [INFO] Generated APP_KEY for this local environment.
) else (
    echo [INFO] APP_KEY is already present; keeping the existing key.
)

call :run php artisan package:discover --ansi
if errorlevel 1 goto :failure

rem Run migrations before cache clearing because this project uses database cache/session locally.
call :run php artisan migrate --force
if errorlevel 1 goto :failure

rem A Git clone rebuilds Vite assets. A clean Release already ships public/build.
if exist "package.json" (
    call :ensure_pnpm
    if errorlevel 1 goto :failure
    call :run pnpm install --frozen-lockfile
    if errorlevel 1 goto :failure
    call :run pnpm run build
    if errorlevel 1 goto :failure
) else (
    if exist "%BUILD_MANIFEST%" (
        echo [INFO] Using prebuilt Vite assets from the clean Release.
    ) else (
        echo [ERROR] Vite assets are missing and package.json is unavailable.
        echo [ERROR] Download a complete Release ZIP or clone the complete Git repository.
        goto :failure
    )
)

if not exist "%BUILD_MANIFEST%" (
    echo [ERROR] Vite manifest was not created at %BUILD_MANIFEST%.
    goto :failure
)

call :run php artisan optimize:clear
if errorlevel 1 goto :failure
call :run php artisan view:cache
if errorlevel 1 goto :failure

if exist "%INSTALL_LOCK%" (
    set "TARGET_PATH=/login"
    echo [OK] Installation already exists. Opening the login page.
) else (
    set "TARGET_PATH=/install"
    echo [OK] Environment is ready. Opening the installation wizard.
)

echo.
echo [OPEN] %LOCAL_URL%%TARGET_PATH%
start "Dent" "%LOCAL_URL%%TARGET_PATH%"
echo.
echo You can run this script again safely after updates.
goto :success

:require_command
where %~1 >nul 2>&1
if errorlevel 1 (
    echo [ERROR] %~2 was not found in PATH.
    exit /b 1
)
exit /b 0

:ensure_directory
if not exist "%~1" mkdir "%~1"
if not exist "%~1" (
    echo [ERROR] Could not create required directory: %~1
    exit /b 1
)
exit /b 0

:ensure_pnpm
where pnpm >nul 2>&1
if not errorlevel 1 exit /b 0

where corepack >nul 2>&1
if not errorlevel 1 (
    echo [INFO] pnpm was not found; enabling it through Corepack.
    call corepack enable >nul 2>&1
)

where pnpm >nul 2>&1
if errorlevel 1 (
    echo [ERROR] pnpm was not found. Install Node.js LTS with Corepack enabled, then run this script again.
    exit /b 1
)
exit /b 0

:run
echo [RUN] %*
call %*
if errorlevel 1 (
    echo [ERROR] Command failed: %*
    exit /b 1
)
exit /b 0

:failure
echo.
echo [FAILED] The local environment was not prepared. Read the first error above and fix only that prerequisite.
pause
endlocal
exit /b 1

:success
echo.
pause
endlocal
exit /b 0
