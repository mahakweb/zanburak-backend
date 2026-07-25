@echo off
setlocal EnableExtensions
title Zanburak Messenger (Redis + Reverb + Scheduler)

REM Backend root = folder of this .bat
set "BACKEND=%~dp0"
set "BACKEND=%BACKEND:~0,-1%"

REM Portable Redis (Windows) — installed under repo tools/
set "REDIS_DIR=%BACKEND%\..\tools\redis\bin\Redis-8.0.2-Windows-x64-msys2-with-Service"
set "REDIS_SERVER=%REDIS_DIR%\redis-server.exe"
set "REDIS_CLI=%REDIS_DIR%\redis-cli.exe"

cd /d "%BACKEND%"

echo.
echo  ========================================
echo   Zanburak Messenger local runtime
echo  ========================================
echo   Backend: %BACKEND%
echo.

if not exist "%BACKEND%\artisan" (
  echo  [ERROR] artisan not found in %BACKEND%
  pause
  exit /b 1
)

if not exist "%REDIS_SERVER%" (
  echo  [ERROR] redis-server.exe not found:
  echo          %REDIS_SERVER%
  pause
  exit /b 1
)

REM --- Redis ---
"%REDIS_CLI%" ping >nul 2>&1
if %ERRORLEVEL%==0 (
  echo  [OK] Redis already running on 6379
) else (
  echo  [..] Starting Redis...
  start "Zanburak Redis" /D "%REDIS_DIR%" "%REDIS_SERVER%"
  timeout /t 2 /nobreak >nul
  "%REDIS_CLI%" ping >nul 2>&1
  if %ERRORLEVEL%==0 (
    echo  [OK] Redis started
  ) else (
    echo  [WARN] Redis ping failed — check the Redis window
  )
)

REM --- Reverb (WebSocket) ---
echo  [..] Starting Reverb...
start "Zanburak Reverb" cmd /k "cd /d "%BACKEND%" && title Zanburak Reverb && php artisan reverb:start"

REM --- Scheduler (outbox flush) ---
echo  [..] Starting Scheduler (schedule:work)...
start "Zanburak Scheduler" cmd /k "cd /d "%BACKEND%" && title Zanburak Scheduler && php artisan schedule:work"

echo.
echo  ========================================
echo   Started:
echo     - Redis
echo     - Reverb      (WebSocket)
echo     - Scheduler   (messenger:flush-outbox)
echo.
echo   Keep those windows open while testing messenger.
echo   API/frontend: run separately (serve / npm / herd).
echo  ========================================
echo.
pause
endlocal
