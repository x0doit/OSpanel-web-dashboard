@echo off
chcp 65001 >nul 2>&1
title OSPanel Dashboard — Installer

echo.
echo   OSPanel Dashboard Installer
echo   by x0doit (github.com/x0doit)
echo.

set "PHP="

for %%D in (C D E F G) do (
    for %%V in (8.5 8.4 8.3 8.2 8.1 8.0 7.4) do (
        if exist "%%D:\OSPanel\modules\PHP-%%V\php.exe" (
            set "PHP=%%D:\OSPanel\modules\PHP-%%V\php.exe"
            goto :found
        )
    )
)

echo   PHP not found in OSPanel.
echo   Enter full path to php.exe:
set /p PHP="   > "
if not exist "%PHP%" (
    echo   Error: file not found
    pause
    exit /b 1
)

:found
echo   PHP: %PHP%
echo.
echo   Opening http://127.0.0.1:9801/install.html
echo   Close this window to stop the installer.
echo.

start "" "http://127.0.0.1:9801/install.html"
"%PHP%" -S 127.0.0.1:9801 -t "%~dp0" >nul 2>&1
