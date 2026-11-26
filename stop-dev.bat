@echo off
REM Keep window open when double-clicked
if /I not "%~1"=="__run" (
  title Starship Stop
  cmd /k ""%~f0" __run"
  exit /b
)
setlocal EnableExtensions EnableDelayedExpansion
chcp 65001 >nul
title Starship Stop

REM ============================================================
REM  Match start-dev.bat services
REM  Backend: php artisan serve   port 8000  window Starship-Backend
REM  Frontend: pnpm dev:antd      port 5666  window Starship-Frontend
REM ============================================================
set "BACKEND_PORT=8000"
set "FRONTEND_PORT=5666"

echo ========================================
echo   Starship 本地开发停止
echo ========================================
echo   将停止: 后端 :%BACKEND_PORT% / 前端 :%FRONTEND_PORT%
echo ========================================
echo.

echo 正在停止 Starship 开发服务...
echo.

call :KillPort %BACKEND_PORT% 后端
call :KillPort %FRONTEND_PORT% 前端

REM Close cmd windows started by start-dev.bat
call :KillWindow Starship-Backend 后端窗口
call :KillWindow Starship-Frontend 前端窗口

echo.
echo 完成. 若仍有残留终端, 直接关掉即可.
echo 输入 exit 后回车, 或直接点关闭, 即可关掉本窗口.
endlocal
exit /b 0

:KillPort
set "FOUND=0"
for /f "tokens=5" %%P in ('netstat -ano 2^>nul ^| findstr /C:":%~1 " ^| findstr "LISTENING"') do (
  if not "%%P"=="0" if not "%%P"=="" (
    echo [停止] %~2  端口 %~1  PID %%P
    taskkill /F /T /PID %%P >nul 2>&1
    set "FOUND=1"
  )
)
if "!FOUND!"=="0" echo [跳过] %~2  端口 %~1  未在监听
exit /b 0

:KillWindow
REM Close leftover cmd /k windows by title
taskkill /F /T /FI "WINDOWTITLE eq %~1*" >nul 2>&1
if not errorlevel 1 (
  echo [停止] %~2  窗口 [%~1]
) else (
  echo [跳过] %~2  未找到窗口 [%~1]
)
exit /b 0
