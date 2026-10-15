param([Parameter(Mandatory=$true)][string]$Email, [string]$Name = "Administrador")
$ErrorActionPreference = "Stop"
$previousOutputEncoding = $OutputEncoding
$OutputEncoding = New-Object System.Text.UTF8Encoding($false)
$secret = Read-Host "Contraseña del administrador (12 a 72 bytes)" -AsSecureString
$pointer = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($secret)
try {
    [Runtime.InteropServices.Marshal]::PtrToStringBSTR($pointer) | php "$PSScriptRoot/console.php" create-admin $Email $Name
    if ($LASTEXITCODE -ne 0) { throw "No se pudo crear el administrador." }
} finally {
    [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($pointer)
    $secret.Dispose()
    $OutputEncoding = $previousOutputEncoding
}
