@echo off
title SOS System - Stop All Services
color 0C
chcp 65001 >nul 2>&1

echo.
echo ============================================================
echo           STOP ALL SOS SERVICES
echo ============================================================
echo.

echo Terminating service windows...

taskkill /FI "WINDOWTITLE eq SOS-Backend*"  /F >nul 2>&1 && echo   [stopped] Backend
taskkill /FI "WINDOWTITLE eq SOS-Reverb*"   /F >nul 2>&1 && echo   [stopped] Reverb
taskkill /FI "WINDOWTITLE eq SOS-Queue*"    /F >nul 2>&1 && echo   [stopped] Queue
taskkill /FI "WINDOWTITLE eq SOS-Frontend*" /F >nul 2>&1 && echo   [stopped] Frontend
taskkill /FI "WINDOWTITLE eq SOS-AI*"       /F >nul 2>&1 && echo   [stopped] AI Module

echo.
echo Force-clearing ports 8000 5173 8001 8080...

for %%p in (8000 5173 8001 8080) do (
    for /f "tokens=5" %%a in ('netstat -aon ^| findstr ":%%p " ^| findstr "LISTENING" 2^>nul') do (
        echo   Killing PID %%a on port %%p
        taskkill /PID %%a /F >nul 2>&1
    )
)

echo.
echo All services stopped!
echo.
timeout /t 3
