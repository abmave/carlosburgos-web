param([Parameter(Mandatory=$true)][string]$PackagePath)
$ErrorActionPreference='Stop'
Push-Location (Join-Path $PSScriptRoot '..')
$project='cb-deploy-test-'+[DateTime]::UtcNow.ToString('yyyyMMddHHmmss')
$previous=@{}
foreach($key in @('CB_DB_PASSWORD','CB_DB_ROOT_PASSWORD','CB_RELEASE_PATH','CB_PDF_UPLOADS')) { $previous[$key]=[Environment]::GetEnvironmentVariable($key,'Process') }
function Compose { docker compose -p $project -f deploy/compose.yaml @args; if($LASTEXITCODE -ne 0){throw "Docker test failed: $($args[0])"} }
try {
 $packageRoot=(Resolve-Path -LiteralPath $PackagePath).Path
 foreach ($line in Get-Content (Join-Path $packageRoot 'SHA256SUMS')) {
  if ($line -notmatch '^([a-f0-9]{64})  (.+)$') { throw 'Invalid manifest' }
  $expected=$Matches[1]; $relative=$Matches[2]
  $target=[IO.Path]::GetFullPath((Join-Path $packageRoot $relative))
  if (!$target.StartsWith($packageRoot+[IO.Path]::DirectorySeparatorChar,[StringComparison]::OrdinalIgnoreCase)) { throw 'Manifest path escapes package' }
  if ((Get-FileHash -LiteralPath $target -Algorithm SHA256).Hash.ToLowerInvariant() -ne $expected) { throw "Checksum mismatch: $relative" }
 }
 $forbidden=Get-ChildItem $packageRoot -Recurse -Force -File | Where-Object {
  $_.Name -match '^(\.env(?:\..*)?|config\.local\.php|admin-activation\.html)$' -or
  $_.FullName -match '[/\\](node_modules|\.git|tests|backups|sessions)[/\\]'
 }
 if ($forbidden) { throw 'Forbidden private/dependency file in package' }
 Write-Output 'PASS package checksums and excluded private files.'
 $env:CB_RELEASE_PATH=(Resolve-Path -LiteralPath $PackagePath).Path
 $env:CB_DB_PASSWORD=[Guid]::NewGuid().ToString('N')+[Guid]::NewGuid().ToString('N')
 $env:CB_DB_ROOT_PASSWORD=[Guid]::NewGuid().ToString('N')+[Guid]::NewGuid().ToString('N')
 $env:CB_PDF_UPLOADS='0'
 Compose up -d --build --wait --wait-timeout 300
 Compose exec -T apache sh -c 'chown www-data:www-data /storage && chmod 700 /storage'
 Compose exec -T apache php /app/api/bin/console.php migrate
 Compose exec -T apache php /app/api/bin/preflight.php
 Compose exec -T apache php /tests/seed.php
 Compose exec -T apache php /app/api/bin/invite-admin.php deployment-invite@example.invalid 'Invitación de prueba'
 Compose exec -T apache php /checks/config-test.php
 Compose run --rm browser
 $env:CB_PDF_UPLOADS='1'
 Compose up -d --wait
 Compose run --rm browser
 Compose exec -T apache php /app/api/bin/console.php migrate
 Compose ps -a
 Write-Output 'PASS isolated Apache package rehearsal, PDF disabled and validated.'
} finally {
 docker compose -p $project -f deploy/compose.yaml down
 foreach($key in $previous.Keys) { [Environment]::SetEnvironmentVariable($key,$previous[$key],'Process') }
 Pop-Location
}
