param([int]$Port = 5190)
$ErrorActionPreference = 'Stop'
$project = 'cb-cms002-test-' + (Get-Date -Format 'yyyyMMddHHmmss')
$previousPort = $env:CB_WEB_PORT
$env:CB_WEB_PORT = "$Port"
Push-Location (Join-Path $PSScriptRoot '..')
function Compose {
    docker compose -p $project -f compose.yaml -f compose.test.yaml @args
    if ($LASTEXITCODE -ne 0) { throw "Compose failed: $($args[0])" }
}
try {
    Compose config --quiet
    Compose up -d --build --wait --wait-timeout 300
    Compose exec -T api sh -c 'find api -name "*.php" -not -path "*/storage/*" -exec php -l {} \;'
    Compose exec -T web npm run build
    Compose exec -T api php /tests/seed.php
    Compose run --rm runner
    Compose exec -T api php /tests/control.php clear-rate
    Compose exec -T api php /tests/expired-fixtures.php
    Compose run --rm runner node supplemental.mjs
    Compose exec -T api php /tests/password_reset.php
    Compose run --rm browser
    Compose exec -T api php api/bin/console.php migrate
    Compose down
    Compose up -d --wait --wait-timeout 300
    Compose exec -T api php /tests/control.php persist
    Compose ps -a
    Write-Output "PASS acceptance: $project. Volumes retained; no user database modified."
} finally {
    docker compose -p $project -f compose.yaml -f compose.test.yaml down
    $env:CB_WEB_PORT = $previousPort
    Pop-Location
}
