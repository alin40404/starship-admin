@echo off
REM 双击时资源管理器会用“跑完就关”的方式打开本文件，所以先换到 /k，窗口才会留下来
if /I not "%~1"=="__run" (
  title Starship Dev
  cmd /k ""%~f0" __run"
  exit /b
)
setlocal EnableExtensions EnableDelayedExpansion
cd /d "%~dp0"
title Starship Dev
chcp 65001 >nul

REM ============================================================
REM  开发启动命令（与下方实际执行保持一致）
REM  后端:  php artisan serve --host=127.0.0.1 --port=8000
REM  前端:  pnpm dev:antd          （目录: apps\admin，端口 5666）
REM ============================================================
set "BACKEND_HOST=127.0.0.1"
set "BACKEND_PORT=8000"
set "FRONTEND_PORT=5666"
set "FRONTEND_CMD=pnpm dev:antd"
set "BACKEND_URL=http://%BACKEND_HOST%:%BACKEND_PORT%"
set "FRONTEND_URL=http://localhost:%FRONTEND_PORT%"

echo ========================================
echo   Starship 本地开发启动
echo ========================================
echo   后端命令: php artisan serve --host=%BACKEND_HOST% --port=%BACKEND_PORT%
echo   前端命令: %FRONTEND_CMD%
echo   后端地址: %BACKEND_URL%
echo   前端地址: %FRONTEND_URL%
echo ========================================
echo.

REM ---------- PHP 8.3+ ----------
if not defined PHP_BIN set "PHP_BIN="
if defined PHP_BIN if exist "%PHP_BIN%" goto :PhpReady
set "PHP_BIN="
for /f "delims=" %%P in ('where php 2^>nul') do (
  if not defined PHP_BIN (
    "%%P" -r "exit(PHP_VERSION_ID>=80300?0:1);" >nul 2>&1
    if not errorlevel 1 set "PHP_BIN=%%P"
  )
)
if not defined PHP_BIN (
  echo [错误] 未找到 PHP 8.3+。当前 PATH 中的 php 版本过低或不存在。
  echo        可设置环境变量 PHP_BIN 为 php8.3 的 php.exe 完整路径后重试。
  pause
  exit /b 1
)
:PhpReady
"%PHP_BIN%" -r "exit(PHP_VERSION_ID>=80300?0:1);" >nul 2>&1
if errorlevel 1 (
  echo [错误] 需要 PHP 8.3+，当前为: %PHP_BIN%
  pause
  exit /b 1
)
set "PHP_VER="
"%PHP_BIN%" -r "echo PHP_VERSION;" > "%TEMP%\starship-php-ver.txt" 2>nul
set /p PHP_VER=<"%TEMP%\starship-php-ver.txt"
del "%TEMP%\starship-php-ver.txt" >nul 2>&1
if not defined PHP_VER set "PHP_VER=unknown"
echo [OK] PHP %PHP_VER%
echo      %PHP_BIN%

REM ---------- Node / pnpm ----------
where node >nul 2>&1
if errorlevel 1 (
  echo [错误] 未找到 Node.js。请安装 Node.js 20+ 并加入 PATH。
  pause
  exit /b 1
)
for /f "delims=" %%V in ('node -v 2^>nul') do set "NODE_VER=%%V"
echo [OK] Node %NODE_VER%

where pnpm >nul 2>&1
if errorlevel 1 (
  echo [错误] 未找到 pnpm。请执行: npm install -g pnpm
  pause
  exit /b 1
)
for /f "delims=" %%V in ('pnpm -v 2^>nul') do set "PNPM_VER=%%V"
echo [OK] pnpm %PNPM_VER%
echo.

REM ---------- 目录 / 依赖 ----------
if not exist "%~dp0backend\artisan" (
  echo [错误] 未找到 backend\artisan。请把本文件放在仓库根目录。
  pause
  exit /b 1
)
if not exist "%~dp0backend\vendor\autoload.php" (
  echo [错误] 后端依赖未安装。请先在 backend 目录执行: composer install
  pause
  exit /b 1
)
if not exist "%~dp0apps\admin\package.json" (
  echo [错误] 未找到 apps\admin\package.json。
  pause
  exit /b 1
)
if not exist "%~dp0apps\admin\node_modules\" (
  echo [错误] 前端依赖未安装。请先在 apps\admin 目录执行: pnpm install
  pause
  exit /b 1
)

if exist "%~dp0backend\.env" goto :EnvOk
if not exist "%~dp0backend\.env.example" (
  echo [错误] 未找到 backend/.env 与 backend/.env.example。
  pause
  exit /b 1
)
copy /Y "%~dp0backend\.env.example" "%~dp0backend\.env" >nul
echo [提示] 未找到 backend/.env，已从 .env.example 复制。
echo        请先配置数据库，再执行下列命令。
echo          php artisan key:generate
echo          php artisan migrate --seed
echo.
pause
:EnvOk

REM ---------- 启动后端 ----------
call :PortListening %BACKEND_PORT%
if "!PORT_UP!"=="1" (
  echo [跳过] 后端已在运行: %BACKEND_URL%
) else (
  echo [启动] 后端
  echo        命令: "%PHP_BIN%" artisan serve --host=%BACKEND_HOST% --port=%BACKEND_PORT%
  echo        目录: %~dp0backend
  start "Starship-Backend" /D "%~dp0backend" cmd /k ""%PHP_BIN%" artisan serve --host=%BACKEND_HOST% --port=%BACKEND_PORT%"
)

REM ---------- 启动前端 ----------
call :PortListening %FRONTEND_PORT%
if "!PORT_UP!"=="1" (
  echo [跳过] 前端已在运行: %FRONTEND_URL%
) else (
  echo [启动] 前端
  echo        命令: %FRONTEND_CMD%
  echo        目录: %~dp0apps\admin
  start "Starship-Frontend" /D "%~dp0apps\admin" cmd /k "%FRONTEND_CMD%"
)

echo.
echo 前后端在另外两个窗口里跑 [Starship-Backend / Starship-Frontend]
echo 关掉本窗口不会停它们
echo 要停止请双击 stop-dev.bat
echo.
echo 等待服务就绪...
call :WaitPort %BACKEND_PORT% 30
if "!PORT_UP!"=="1" (
  echo [就绪] 后端  %BACKEND_URL%
) else (
  echo [超时] 后端尚未监听 %BACKEND_PORT%
  echo        请查看 Starship-Backend 窗口
)
call :WaitPort %FRONTEND_PORT% 90
if "!PORT_UP!"=="1" (
  echo [就绪] 前端  %FRONTEND_URL%
  start "" "%FRONTEND_URL%"
) else (
  echo [超时] 前端尚未监听 %FRONTEND_PORT%
  echo        请查看 Starship-Frontend 窗口
  echo        手动打开: %FRONTEND_URL%
)

echo.
echo 启动步骤已结束. 此窗口会保留, 避免一闪而过.
echo 输入 exit 后回车, 或直接点关闭, 即可关掉本窗口.
endlocal
exit /b 0

:PortListening
set "PORT_UP=0"
netstat -ano | findstr /C:":%~1 " | findstr "LISTENING" >nul 2>&1
if not errorlevel 1 set "PORT_UP=1"
exit /b 0

:WaitPort
REM %1=port  %2=max seconds
set "PORT_UP=0"
set /a "_left=%~2"
:WaitPortLoop
call :PortListening %~1
if "!PORT_UP!"=="1" exit /b 0
if !_left! LEQ 0 exit /b 1
timeout /t 1 /nobreak >nul
set /a "_left-=1"
goto :WaitPortLoop
