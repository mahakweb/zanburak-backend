@echo off
chcp 65001 >nul
title Zanburak - Backend Deploy Manager

cd /d "%~dp0"

powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0scripts\menu.ps1"
set "EXIT_CODE=%ERRORLEVEL%"

exit /b %EXIT_CODE%
