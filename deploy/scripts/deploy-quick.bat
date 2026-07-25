@echo off
chcp 65001 >nul
title Zanburak - Quick Backend Deploy

cd /d "%~dp0"

echo.
echo ========================================
echo   Quick Backend Deploy (no menu)
echo ========================================
echo.

powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0deploy-quick.ps1"
set "EXIT_CODE=%ERRORLEVEL%"

echo.
if %EXIT_CODE% neq 0 (
    echo [FAILED] Deploy stopped with error code %EXIT_CODE%.
) else (
    echo [DONE] Backend deployed to /var/www/zanburak-backend
)

echo.
pause
exit /b %EXIT_CODE%
