param([Parameter(Mandatory=$true)][string]$Email, [string]$Name = "Administrador")
$ErrorActionPreference = "Stop"
$previousOutputEncoding = $OutputEncoding
$OutputEncoding = New-Object System.Text.UTF8Encoding($false)
$projectRoot = Split-Path $PSScriptRoot -Parent
$secret = Read-Host "Contraseña del administrador (12 a 72 bytes)" -AsSecureString
$pointer = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($secret)
Push-Location $projectRoot
try {
    [Runtime.InteropServices.Marshal]::PtrToStringBSTR($pointer) | docker compose exec -T api php api/bin/console.php create-admin $Email $Name
    if ($LASTEXITCODE -ne 0) { throw "No se pudo crear el administrador. Compruebe docker compose ps." }
} finally {
    Pop-Location
    [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($pointer)
    $secret.Dispose()
    $OutputEncoding = $previousOutputEncoding
}
