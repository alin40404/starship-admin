@echo off
REM 双击时保留窗口，方便看停止结果
if /I not "%~1"=="__run" (
  title Starship Stop
  cmd /k ""%~f0" __run"
  exit /b
)
setlocal EnableExtensions EnableDelayedExpansion
chcp 65001 >nul
title Starship Stop

REM ============================================================
REM  对应 start-dev.bat 启动的服务
REM  后端:  php artisan serve          端口 8000  窗口 Starship-Backend
REM  前端:  pnpm dev:antd              端口 5666  窗口 Starship-Frontend
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

REM 关掉 start-dev 拉起的 cmd 窗口（端口进程杀掉后窗口可能还在）
call :KillWindow Starship-Backend 后端窗口
call :KillWindow Starship-Frontend 前端窗口

echo.
echo 完成。若仍有残留终端，直接关掉即可。
echo 输入 exit 后回车，或直接点关闭，即可关掉本窗口。
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
REM 按窗口标题收尾（端口进程杀掉后，cmd /k 窗口可能还在）
taskkill /F /T /FI "WINDOWTITLE eq %~1*" >nul 2>&1
if not errorlevel 1 (
  echo [停止] %~2  窗口 "%~1"
) else (
  echo [跳过] %~2  未找到窗口 "%~1"
)
exit /b 0
