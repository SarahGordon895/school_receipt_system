@echo off
cd /d "%~dp0"
call "%~dp0bin\php.cmd" artisan ftrs:install %*
