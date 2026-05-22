$php82 = 'C:\Users\User\AppData\Local\Microsoft\WinGet\Packages\PHP.PHP.8.2_Microsoft.Winget.Source_8wekyb3d8bbwe'
$php82Exe = Join-Path $php82 'php.exe'
$projectBin = $PSScriptRoot

$env:Path = "$projectBin;$php82;$env:Path"

function global:php {
    & $php82Exe @args
}
