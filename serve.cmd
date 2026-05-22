@echo off
REM Dedicated port so this project is not mixed with other apps on :8000
call "%~dp0artisan.cmd" serve --host=127.0.0.1 --port=8088 %*
