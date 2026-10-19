param(
    [ValidatePattern('^[a-z0-9][a-z0-9_-]*$')][string]$ProjectName = 'carlosburgos',
    [ValidateRange(1024,65535)][int]$Port = 5180
)
$ErrorActionPreference = 'Stop'
Push-Location (Join-Path $PSScriptRoot '..')
$previousPort = $env:CB_WEB_PORT
try {
    docker info --format '{{.OSType}}' | Out-Null
    if ($LASTEXITCODE -ne 0) { throw 'Abra Docker Desktop con contenedores Linux y vuelva a intentar.' }
    $engine = docker info --format '{{.OSType}}'
    if ($engine -ne 'linux') { throw 'Se requieren contenedores Linux.' }
    if (!(Test-Path -LiteralPath '.env')) {
        $volumes = @(docker volume ls --filter "label=com.docker.compose.project=$ProjectName" --format '{{.Name}}')
        if ($LASTEXITCODE -ne 0) { throw 'No se pudo comprobar los volúmenes existentes.' }
        if ($volumes.Count -gt 0) {
            throw 'Existen volúmenes de este proyecto pero falta .env. Recupere su configuración privada; no se generarán credenciales incompatibles.'
        }
        if ($env:CB_DB_PASSWORD -or $env:CB_DB_ROOT_PASSWORD) {
            throw 'Hay credenciales en variables del proceso. Configure .env explícitamente o retire esas variables antes de inicializar una instalación nueva.'
        }
        $random = [System.Security.Cryptography.RandomNumberGenerator]::Create()
        try {
            $bytes = New-Object byte[] 32
            $random.GetBytes($bytes)
            $databasePassword = [BitConverter]::ToString($bytes).Replace('-', '').ToLowerInvariant()
            $random.GetBytes($bytes)
            $rootPassword = [BitConverter]::ToString($bytes).Replace('-', '').ToLowerInvariant()
            $configText = "CB_WEB_PORT=$Port`nCB_DB_PASSWORD=$databasePassword`nCB_DB_ROOT_PASSWORD=$rootPassword`n"
            [System.IO.File]::WriteAllText((Join-Path (Get-Location) '.env'), $configText, [System.Text.UTF8Encoding]::new($false))
        } finally {
            $random.Dispose()
            $databasePassword = $null
            $rootPassword = $null
            $configText = $null
        }
        Write-Output 'Configuración privada local creada; secretos no impresos.'
    }
    $env:CB_WEB_PORT = "$Port"
    docker compose -p $ProjectName config --quiet
    if ($LASTEXITCODE -ne 0) { throw 'Configuración Compose inválida. Revise .env sin publicar su contenido.' }
    docker compose -p $ProjectName up -d --build --wait --wait-timeout 300
    if ($LASTEXITCODE -ne 0) { throw 'No se completó el arranque. Consulte los logs de este proyecto.' }
    docker compose -p $ProjectName ps -a
    if ($LASTEXITCODE -ne 0) { throw 'No se pudo consultar el estado de los servicios.' }
    Write-Output "Web: http://localhost:$Port | Panel: http://localhost:$Port/admin"
    Write-Output 'Una base nueva no contiene cuentas ni contenido privado de otra PC. Consulte docs/CONTINUIDAD-CASA.md.'
} finally {
    $env:CB_WEB_PORT = $previousPort
    Pop-Location
}
