$ErrorActionPreference = 'Stop'
Push-Location (Join-Path $PSScriptRoot '..')
try {
    New-Item -ItemType Directory -Force '.local/backups' | Out-Null
    $stamp = Get-Date -Format 'yyyyMMdd-HHmmss'
    docker compose exec -T db sh -c 'MYSQL_PWD="$MARIADB_ROOT_PASSWORD" mariadb-dump -u root --single-transaction --routines --triggers "$MARIADB_DATABASE" > /tmp/cb-backup.sql'
    if ($LASTEXITCODE -ne 0) { throw 'Database backup failed' }
    docker compose cp db:/tmp/cb-backup.sql ".local/backups/database-$stamp.sql"
    if ($LASTEXITCODE -ne 0) { throw 'Database backup copy failed' }
    docker compose cp api:/storage ".local/backups/storage-$stamp"
    if ($LASTEXITCODE -ne 0) { throw 'Storage backup copy failed' }
    Write-Output "Backup privado creado en .local/backups ($stamp)."
} finally { Pop-Location }
