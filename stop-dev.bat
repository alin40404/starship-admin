@echo off
setlocal EnableExtensions
chcp 65001 >nul

echo 正在停止 Starship 开发服务...
call :KillPort 8000 后端
call :KillPort 5666 前端
echo.
echo 完成。若窗口仍开着，直接关掉即可。
pause
endlocal
exit /b 0

:KillPort
set "FOUND=0"
for /f "tokens=5" %%P in ('netstat -ano ^| findstr /C:":%~1 " ^| findstr "LISTENING"') do (
  echo [停止] %~2  端口 %~1  PID %%P
  taskkill /F /PID %%P >nul 2>&1
  set "FOUND=1"
)
if "%FOUND%"=="0" echo [跳过] %~2  端口 %~1  未在监听
exit /b 0
