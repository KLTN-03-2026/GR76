@echo off
title SOS System - Demo Launcher
color 0A

echo.
echo ============================================================
echo           SOS SYSTEM - DEMO LAUNCHER
echo ============================================================
echo.
echo   Backend  (Laravel)   - http://localhost:8000
echo   Frontend (Vite)      - http://localhost:5173
echo   AI Module (FastAPI)  - http://localhost:8001
echo   Reverb  (WebSocket)  - ws://localhost:8080
echo.
echo ============================================================
echo.

:: Configuration
set "PROJECT_DIR=%~dp0"
set "BACKEND_DIR=%PROJECT_DIR%backend"
set "FRONTEND_DIR=%PROJECT_DIR%frontend"
set "AI_DIR=%PROJECT_DIR%ai_module"

:: Step 1: Check prerequisites
echo [1/6] Checking prerequisites...
echo.

where php >nul 2>&1
if %errorlevel% neq 0 (
    echo   [X] PHP not found. Install XAMPP and add PHP to PATH.
    pause
    exit /b 1
)
echo   [OK] PHP ready

where node >nul 2>&1
if %errorlevel% neq 0 (
    echo   [X] Node.js not found. Download from https://nodejs.org
    pause
    exit /b 1
)
echo   [OK] Node.js ready

where python >nul 2>&1
if %errorlevel% neq 0 (
    echo   [X] Python not found. Download from https://python.org
    pause
    exit /b 1
)
echo   [OK] Python ready
echo.

:: Step 2: Backend setup
echo [2/6] Preparing Backend (Laravel)...

if not exist "%BACKEND_DIR%\.env" (
    echo   Creating .env from .env.example...
    copy "%BACKEND_DIR%\.env.example" "%BACKEND_DIR%\.env" >nul
)

findstr /C:"APP_KEY=base64" "%BACKEND_DIR%\.env" >nul 2>&1
if %errorlevel% neq 0 (
    echo   Generating APP_KEY...
    pushd "%BACKEND_DIR%"
    php artisan key:generate --ansi
    popd
)

if not exist "%BACKEND_DIR%\vendor" (
    echo   Installing composer dependencies...
    pushd "%BACKEND_DIR%"
    composer install --no-interaction --prefer-dist
    popd
)

echo   Running database migrations...
pushd "%BACKEND_DIR%"
php artisan migrate --force 2>nul
php artisan storage:link 2>nul
popd

echo   [OK] Backend ready!
echo.

:: Step 3: Frontend setup
echo [3/6] Preparing Frontend (Vue + Vite)...

if not exist "%FRONTEND_DIR%\node_modules" (
    echo   Installing npm dependencies...
    pushd "%FRONTEND_DIR%"
    npm install --silent
    popd
)
echo   [OK] Frontend ready!
echo.

:: Step 4: AI Module setup
echo [4/6] Preparing AI Module (FastAPI)...

if not exist "%AI_DIR%\venv" (
    echo   Creating virtual environment...
    pushd "%AI_DIR%"
    python -m venv venv
    popd
)
echo   [OK] AI Module ready!
echo.

:: Step 5: Kill existing processes on our ports
echo [5/6] Cleaning up ports...

for %%p in (8000 5173 8001 8080) do (
    for /f "tokens=5" %%a in ('netstat -aon ^| findstr ":%%p " ^| findstr "LISTENING" 2^>nul') do (
        taskkill /PID %%a /F >nul 2>&1
    )
)
echo   [OK] Ports cleared!
echo.

:: Step 6: Launch all services
echo [6/6] Starting all services...
echo.

echo   [1] Starting Backend - http://localhost:8000
start "SOS-Backend" cmd /k "title SOS-Backend && cd /d %BACKEND_DIR% && php artisan serve --port=8000"

timeout /t 2 /nobreak >nul

echo   [2] Starting Reverb WebSocket - ws://localhost:8080
start "SOS-Reverb" cmd /k "title SOS-Reverb && cd /d %BACKEND_DIR% && php artisan reverb:start --port=8080"

timeout /t 1 /nobreak >nul

echo   [3] Starting Queue Worker
start "SOS-Queue" cmd /k "title SOS-Queue && cd /d %BACKEND_DIR% && php artisan queue:work --tries=3 --timeout=60"

echo   [4] Starting Frontend - http://localhost:5173
start "SOS-Frontend" cmd /k "title SOS-Frontend && cd /d %FRONTEND_DIR% && npm run dev"

timeout /t 1 /nobreak >nul

echo   [5] Starting AI Module - http://localhost:8001
start "SOS-AI" cmd /k "title SOS-AI && cd /d %AI_DIR% && venv\Scripts\activate && pip install -r requirements.txt -q && python api.py"

echo.
echo ============================================================
echo.
echo   ALL SERVICES STARTED!
echo.
echo   Open browser: http://localhost:5173
echo.
echo   Backend  : http://localhost:8000/api
echo   Frontend : http://localhost:5173
echo   AI Docs  : http://localhost:8001/docs
echo   Reverb   : ws://localhost:8080
echo.
echo   Press any key to STOP all services.
echo.
echo ============================================================
echo.

timeout /t 3 /nobreak >nul
start http://localhost:5173

pause >nul

:: Cleanup
echo.
echo Stopping all services...

taskkill /FI "WINDOWTITLE eq SOS-Backend*" /F >nul 2>&1
taskkill /FI "WINDOWTITLE eq SOS-Reverb*" /F >nul 2>&1
taskkill /FI "WINDOWTITLE eq SOS-Queue*" /F >nul 2>&1
taskkill /FI "WINDOWTITLE eq SOS-Frontend*" /F >nul 2>&1
taskkill /FI "WINDOWTITLE eq SOS-AI*" /F >nul 2>&1

for %%p in (8000 5173 8001 8080) do (
    for /f "tokens=5" %%a in ('netstat -aon ^| findstr ":%%p " ^| findstr "LISTENING" 2^>nul') do (
        taskkill /PID %%a /F >nul 2>&1
    )
)

echo All services stopped!
echo.
pause
