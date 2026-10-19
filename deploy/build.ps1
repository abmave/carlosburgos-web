param([switch]$AllowDirty)
$ErrorActionPreference='Stop'
Push-Location (Join-Path $PSScriptRoot '..')
try {
 $sha=git rev-parse HEAD
 if ($LASTEXITCODE -ne 0) { throw 'Git HEAD unavailable' }
 $dirty=git status --porcelain --untracked-files=all
 if ($dirty -and !$AllowDirty) { throw 'Commit changes first; use -AllowDirty only for a clearly marked local rehearsal.' }
 $version=$sha
 if ($dirty) { $version+='-dirty' }
 $root=(Get-Location).Path
 $out=Join-Path $root ('.local/releases/'+$version+'-'+[DateTime]::UtcNow.ToString('yyyyMMddHHmmssfff'))
 New-Item -ItemType Directory -Force $out | Out-Null
 docker run --rm --mount "type=bind,source=$root/web,target=/app" --mount type=volume,target=/app/node_modules -w /app node:22-alpine sh -c 'npm ci && npm run build'
 if ($LASTEXITCODE -ne 0) { throw 'Docker frontend build failed' }
 New-Item -ItemType Directory -Force "$out/public/api","$out/private/api","$out/private/storage" | Out-Null
 Copy-Item web/dist/* "$out/public" -Recurse
 $sourceFiles=@(git ls-files -- api/src api/bin api/public database)
 if ($AllowDirty) { $sourceFiles+=@(git ls-files --others --exclude-standard -- api/src api/bin api/public database) }
 foreach ($file in $sourceFiles | Sort-Object -Unique) {
  if ($file -notmatch '\.(php|sql)$') { continue }
  $target=Join-Path "$out/private" $file
  New-Item -ItemType Directory -Force (Split-Path $target -Parent) | Out-Null
  Copy-Item -LiteralPath $file -Destination $target
 }
 Copy-Item deploy/config.production.example.php "$out/private/api/config.example.php"
 Copy-Item deploy/public.htaccess "$out/public/.htaccess"
 Copy-Item deploy/bridge.php "$out/public/api/index.php"
 Copy-Item api/storage.htaccess "$out/private/.htaccess"
 Copy-Item deploy/INSTALL.md "$out/INSTALL.md"
 [IO.File]::WriteAllText("$out/VERSION",$version,[Text.UTF8Encoding]::new($false))
 $manifest=@(Get-ChildItem $out -File -Recurse -Force | Sort-Object FullName | ForEach-Object {
  $relative=[IO.Path]::GetRelativePath($out,$_.FullName).Replace('\','/')
  (Get-FileHash $_.FullName -Algorithm SHA256).Hash.ToLowerInvariant()+'  '+$relative
 })
 [IO.File]::WriteAllLines("$out/SHA256SUMS",$manifest,[Text.UTF8Encoding]::new($false))
 Add-Type -AssemblyName System.IO.Compression.FileSystem
 [IO.Compression.ZipFile]::CreateFromDirectory($out,"$out.zip")
 Write-Output "PACKAGE=$out"
 Write-Output "ZIP_SHA256=$((Get-FileHash "$out.zip" -Algorithm SHA256).Hash.ToLowerInvariant())"
} finally { Pop-Location }
