@echo off
title SOS System - Demo Launcher
color 0A

echo.
echo ============================================================
echo           SOS SYSTEM - DEMO LAUNCHER
echo ============================================================
echo.
echo   Backend   : http://localhost:8000
echo   Frontend  : http://localhost:5173
echo   AI Module : http://localhost:8001
echo   Reverb    : ws://localhost:8080
echo.
echo ============================================================
echo.

:: ── Paths ────────────────────────────────────────────────────────
set PROJECT_DIR=%~dp0
set BACKEND_DIR=%PROJECT_DIR%backend
set FRONTEND_DIR=%PROJECT_DIR%frontend
set AI_DIR=%PROJECT_DIR%ai_module

:: ── Step 1: Check prerequisites ──────────────────────────────────
echo [1/6] Checking prerequisites...

where php >nul 2>&1
if errorlevel 1 (
    echo   [X] PHP not found. Install XAMPP and add PHP to PATH.
    pause
    exit /b 1
)
echo   [OK] PHP found

where node >nul 2>&1
if errorlevel 1 (
    echo   [X] Node.js not found. Get it at https://nodejs.org
    pause
    exit /b 1
)
echo   [OK] Node.js found

where python >nul 2>&1
if errorlevel 1 (
    echo   [X] Python not found. Get it at https://python.org
    pause
    exit /b 1
)
echo   [OK] Python found
echo.

:: ── Step 2: Backend ──────────────────────────────────────────────
echo [2/6] Preparing Backend (Laravel)...

if not exist "%BACKEND_DIR%\.env" (
    echo   Creating .env...
    copy "%BACKEND_DIR%\.env.example" "%BACKEND_DIR%\.env" >nul
)

findstr /C:"APP_KEY=base64" "%BACKEND_DIR%\.env" >nul 2>&1
if errorlevel 1 (
    echo   Generating APP_KEY...
    pushd "%BACKEND_DIR%"
    php artisan key:generate --ansi
    popd
)

if not exist "%BACKEND_DIR%\vendor" (
    echo   Installing Composer dependencies...
    pushd "%BACKEND_DIR%"
    composer install --no-interaction --prefer-dist --quiet
    popd
)

pushd "%BACKEND_DIR%"
php artisan migrate --force >nul 2>&1
php artisan storage:link >nul 2>&1
if not exist ".seeded" (
    echo   Seeding database...
    php artisan db:seed --force >nul 2>&1
    echo seeded > .seeded
)
popd

echo   [OK] Backend ready!
echo.

:: ── Step 3: Frontend ─────────────────────────────────────────────
echo [3/6] Preparing Frontend (Vue + Vite)...

if not exist "%FRONTEND_DIR%\node_modules" (
    echo   Installing npm dependencies...
    pushd "%FRONTEND_DIR%"
    npm install --silent
    popd
)
echo   [OK] Frontend ready!
echo.

:: ── Step 4: AI Module ────────────────────────────────────────────
echo [4/6] Preparing AI Module (Python)...

if not exist "%AI_DIR%\venv" (
    echo   Creating virtual environment...
    python -m venv "%AI_DIR%\venv"
)

if not exist "%AI_DIR%\.env" (
    echo   Creating AI .env...
    copy "%AI_DIR%\.env.example" "%AI_DIR%\.env" >nul
)

echo   Installing AI dependencies...
"%AI_DIR%\venv\Scripts\pip.exe" install -r "%AI_DIR%\requirements.txt" -q --disable-pip-version-check

echo   [OK] AI Module ready!
echo.

:: ── Step 5: Clear ports ──────────────────────────────────────────
echo [5/6] Clearing ports...

for %%p in (8000 5173 8001 8080) do (
    for /f "tokens=5" %%a in ('netstat -aon ^| findstr ":%%p " ^| findstr "LISTENING" 2^>nul') do (
        taskkill /PID %%a /F >nul 2>&1
    )
)
echo   [OK] Ports cleared!
echo.

:: ── Step 6: Launch services ──────────────────────────────────────
echo [6/6] Starting all services...
echo.

echo   Starting Backend...
start "SOS-Backend"  cmd /k "title SOS-Backend & cd /d %BACKEND_DIR% & php artisan serve --port=8000"

timeout /t 2 /nobreak >nul

echo   Starting Reverb...
start "SOS-Reverb"   cmd /k "title SOS-Reverb & cd /d %BACKEND_DIR% & php artisan reverb:start --port=8080"

echo   Starting Queue Worker...
start "SOS-Queue"    cmd /k "title SOS-Queue & cd /d %BACKEND_DIR% & php artisan queue:work --tries=3 --timeout=60"

echo   Starting Scheduler (auto-close incidents)...
start "SOS-Scheduler" cmd /k "title SOS-Scheduler & cd /d %BACKEND_DIR% & php artisan schedule:work"

echo   Starting Frontend...
start "SOS-Frontend" cmd /k "title SOS-Frontend & cd /d %FRONTEND_DIR% & npm run dev"

echo   Starting AI Module...
start "SOS-AI"       cmd /k "title SOS-AI & cd /d %AI_DIR% & venv\Scripts\python.exe api.py"

:: ── Wait then open browser ───────────────────────────────────────
echo.
echo   Waiting for services to start (10 seconds)...
timeout /t 10 /nobreak >nul

echo.
echo ============================================================
echo.
echo   ALL SERVICES STARTED!
echo.
echo   Frontend : http://localhost:5173
echo   Backend  : http://localhost:8000/api
echo   AI Docs  : http://localhost:8001/docs
echo.
echo   Press any key to STOP all services.
echo.
echo ============================================================
echo.

start http://localhost:5173

pause >nul

:: ── Cleanup ──────────────────────────────────────────────────────
echo.
echo Stopping all services...

taskkill /FI "WINDOWTITLE eq SOS-Backend*"  /F >nul 2>&1
taskkill /FI "WINDOWTITLE eq SOS-Reverb*"   /F >nul 2>&1
taskkill /FI "WINDOWTITLE eq SOS-Queue*"    /F >nul 2>&1
taskkill /FI "WINDOWTITLE eq SOS-Frontend*" /F >nul 2>&1
taskkill /FI "WINDOWTITLE eq SOS-Scheduler*" /F >nul 2>&1
taskkill /FI "WINDOWTITLE eq SOS-AI*"       /F >nul 2>&1

for %%p in (8000 5173 8001 8080) do (
    for /f "tokens=5" %%a in ('netstat -aon ^| findstr ":%%p " ^| findstr "LISTENING" 2^>nul') do (
        taskkill /PID %%a /F >nul 2>&1
    )
)

echo Done. All services stopped.
echo.
pause
