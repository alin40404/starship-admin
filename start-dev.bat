@echo off
setlocal EnableExtensions
cd /d "%~dp0"
chcp 65001 >nul

REM 可在运行前设置 PHP_BIN 指向指定 php.exe；否则从 PATH 里挑选 8.3+
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
  echo [错误] 未找到 PHP 8.3+。当前 PATH 中的 php 版本过低。
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
for /f "delims=" %%V in ('"%PHP_BIN%" -r "echo PHP_VERSION;"') do set "PHP_VER=%%V"
echo [PHP] %PHP_VER%
echo       %PHP_BIN%
echo.

where pnpm >nul 2>&1
if errorlevel 1 (
  echo [错误] 未找到 pnpm。请安装 Node.js 20+ 与 pnpm，并加入 PATH。
  pause
  exit /b 1
)

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

if not exist "%~dp0apps\admin\node_modules\" (
  echo [错误] 前端依赖未安装。请先在 apps\admin 目录执行: pnpm install
  pause
  exit /b 1
)

if exist "%~dp0backend\.env" goto :EnvOk
if not exist "%~dp0backend\.env.example" (
  echo [错误] 未找到 backend\.env 与 backend\.env.example。
  pause
  exit /b 1
)
copy /Y "%~dp0backend\.env.example" "%~dp0backend\.env" >nul
echo [提示] 未找到 backend\.env，已从 .env.example 复制。
echo        请先配置数据库，再执行:
echo          php artisan key:generate
echo          php artisan migrate --seed
echo.
pause
:EnvOk

call :PortListening 8000
if "%PORT_UP%"=="1" (
  echo [跳过] 后端已在运行: http://127.0.0.1:8000
) else (
  echo [启动] 后端  http://127.0.0.1:8000
  start "Starship-Backend" /D "%~dp0backend" cmd /k ""%PHP_BIN%" artisan serve --host=127.0.0.1 --port=8000"
)

call :PortListening 5666
if "%PORT_UP%"=="1" (
  echo [跳过] 前端已在运行: http://127.0.0.1:5666
) else (
  echo [启动] 前端  http://127.0.0.1:5666
  start "Starship-Frontend" /D "%~dp0apps\admin" cmd /k pnpm dev:antd
)

echo.
echo 关闭标题为 Starship-Backend / Starship-Frontend 的窗口即可停止。
echo 也可双击 stop-dev.bat 结束这两个端口上的进程。
echo.
echo 等待前端就绪后打开浏览器...
timeout /t 8 /nobreak >nul
start "" "http://127.0.0.1:5666"
endlocal
exit /b 0

:PortListening
set "PORT_UP=0"
netstat -ano | findstr /C:":%~1 " | findstr "LISTENING" >nul 2>&1
if not errorlevel 1 set "PORT_UP=1"
exit /b 0
