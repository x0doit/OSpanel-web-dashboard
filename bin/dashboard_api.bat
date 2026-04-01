@echo off
title OSPanel Dashboard API
echo Starting Dashboard API on http://127.0.0.1:9800
echo Press Ctrl+C to stop
"%~dp0..\modules\PHP-8.3\php.exe" -S 127.0.0.1:9800 -t "%~dp0..\system\public_html\api" "%~dp0..\system\public_html\api\backend.php"
pause
