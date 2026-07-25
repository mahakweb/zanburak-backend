@echo off
setlocal EnableExtensions EnableDelayedExpansion
title Zanburak Messenger (Redis + Reverb + Scheduler)

REM Backend root = folder of this .bat
set "BACKEND=%~dp0"
set "BACKEND=%BACKEND:~0,-1%"

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

REM Resolve Redis: portable tools/ first, then winget package, then PATH
set "REDIS_DIR="
set "REDIS_SERVER="
set "REDIS_CLI="
set "REDIS_PORTABLE=%BACKEND%\..\tools\redis\bin\Redis-8.0.2-Windows-x64-msys2-with-Service"

if exist "%REDIS_PORTABLE%\redis-server.exe" (
  set "REDIS_DIR=%REDIS_PORTABLE%"
  goto :redis_resolved
)

for /d %%D in ("%LOCALAPPDATA%\Microsoft\WinGet\Packages\taizod1024.redis-windows-fork*") do (
  for /d %%R in ("%%~D\Redis-*-Windows-x64-msys2*") do (
    if exist "%%~R\redis-server.exe" (
      set "REDIS_DIR=%%~R"
      goto :redis_resolved
    )
  )
)

where redis-server >nul 2>&1
if !ERRORLEVEL!==0 (
  for /f "delims=" %%P in ('where redis-server') do (
    set "REDIS_SERVER=%%P"
    set "REDIS_DIR=%%~dpP"
    if "!REDIS_DIR:~-1!"=="\" set "REDIS_DIR=!REDIS_DIR:~0,-1!"
    goto :redis_resolved
  )
)

echo  [ERROR] redis-server.exe not found.
echo          Install with: winget install taizod1024.redis-windows-fork
echo          Or put portable Redis under:
echo          %REDIS_PORTABLE%
pause
exit /b 1

:redis_resolved
if not defined REDIS_SERVER set "REDIS_SERVER=!REDIS_DIR!\redis-server.exe"
if exist "!REDIS_DIR!\redis-cli.exe" (
  set "REDIS_CLI=!REDIS_DIR!\redis-cli.exe"
) else (
  where redis-cli >nul 2>&1
  if !ERRORLEVEL!==0 (
    for /f "delims=" %%P in ('where redis-cli') do (
      set "REDIS_CLI=%%P"
      goto :redis_ready
    )
  )
  set "REDIS_CLI=!REDIS_DIR!\redis-cli.exe"
)

:redis_ready
echo  [OK] Redis binaries: !REDIS_DIR!

REM --- Redis ---
"!REDIS_CLI!" ping >nul 2>&1
if !ERRORLEVEL!==0 (
  echo  [OK] Redis already running on 6379
) else (
  echo  [..] Starting Redis...
  start "Zanburak Redis" /D "!REDIS_DIR!" "!REDIS_SERVER!"
  timeout /t 2 /nobreak >nul
  "!REDIS_CLI!" ping >nul 2>&1
  if !ERRORLEVEL!==0 (
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
