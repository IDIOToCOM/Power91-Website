@echo off
setlocal
cd /d "%~dp0"

php -v >nul 2>&1
if errorlevel 1 goto :try_docker
php -r "exit(extension_loaded('mbstring') ? 0 : 1);" >nul 2>&1
if errorlevel 1 goto :try_docker
php -r "exit(extension_loaded('pdo_sqlite') ? 0 : 1);" >nul 2>&1
if errorlevel 1 goto :try_docker
composer -V >nul 2>&1
if errorlevel 1 goto :try_docker

echo Installing PHP packages (one-time after clone)...
composer install --no-interaction
if errorlevel 1 goto :fail
php bin/console doctrine:migrations:migrate --no-interaction --all-or-nothing
if errorlevel 1 goto :fail
php bin/console app:seed-admin --no-interaction
if errorlevel 1 goto :fail
echo.
echo Site: http://127.0.0.1:8080
echo Keep this window open. Close it to stop the site.
php -S 127.0.0.1:8080 -t public public/dev-server.php
exit /b %ERRORLEVEL%

:try_docker
docker info >nul 2>&1
if errorlevel 1 goto :need


:docker
echo Starting Power 91 with Docker. The first launch can take several minutes.
echo When it is ready, open http://localhost:8080
echo Keep this window open. Close it or press Ctrl+C to stop.
echo.
docker compose up --build
exit /b %ERRORLEVEL%

:need
echo This PC still needs one of these, then double-click start.bat again:
echo   1. Docker Desktop  https://www.docker.com/products/docker-desktop/
echo   2. Or PHP 8.4+ and Composer, if you already develop on this machine.
echo.
pause
exit /b 1

:fail
echo Setup did not finish. Check the messages above.
pause
exit /b 1
