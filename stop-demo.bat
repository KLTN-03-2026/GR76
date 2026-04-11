@echo off
title SOS System - Stop All
color 0C

echo.
echo ============================================================
echo              STOP ALL SOS SERVICES
echo ============================================================
echo.

echo Stopping all services...
echo.

taskkill /FI "WINDOWTITLE eq SOS-Backend*" /F >nul 2>&1
taskkill /FI "WINDOWTITLE eq SOS-Reverb*" /F >nul 2>&1
taskkill /FI "WINDOWTITLE eq SOS-Queue*" /F >nul 2>&1
taskkill /FI "WINDOWTITLE eq SOS-Frontend*" /F >nul 2>&1
taskkill /FI "WINDOWTITLE eq SOS-AI*" /F >nul 2>&1

for %%p in (8000 5173 8001 8080) do (
    for /f "tokens=5" %%a in ('netstat -aon ^| findstr ":%%p " ^| findstr "LISTENING" 2^>nul') do (
        echo   Killing process on port %%p (PID: %%a)
        taskkill /PID %%a /F >nul 2>&1
    )
)

echo.
echo All services stopped!
echo.
timeout /t 3
