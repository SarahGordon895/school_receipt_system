@echo off
setlocal
set "PHP82=C:\Users\User\AppData\Local\Microsoft\WinGet\Packages\PHP.PHP.8.2_Microsoft.Winget.Source_8wekyb3d8bbwe\php.exe"
if not exist "%PHP82%" (
    echo PHP 8.2 was not found at:
    echo %PHP82%
    exit /b 1
)
"%PHP82%" %*
