param(
    [switch]$NoSha
)

$ErrorActionPreference = 'Stop'

$root = Split-Path -Parent $MyInvocation.MyCommand.Path
$projectRoot = Split-Path -Parent $root
Set-Location $projectRoot

$themeName = Split-Path -Leaf $projectRoot
$styleFile = Join-Path $projectRoot 'style.css'
if (!(Test-Path $styleFile)) { throw "No se encontró style.css en $projectRoot" }

# Extraer versión desde style.css (línea 'Version: x.y.z')
$version = (Select-String -Path $styleFile -Pattern '^\s*Version:\s*(.+)$' -Encoding UTF8 | ForEach-Object { $_.Matches[0].Groups[1].Value.Trim() })
if (-not $version) { $version = (Get-Date -Format 'yyyyMMddHHmmss') }

# SHA corto del commit actual (opcional)
$shortSha = ''
if (-not $NoSha) {
  try {
    $shortSha = (git rev-parse --short HEAD).Trim()
  } catch { $shortSha = '' }
}

$releaseName = if ($shortSha) { "$themeName-v$version-$shortSha" } else { "$themeName-v$version" }

# Crear carpeta releases
$releasesDir = Join-Path $projectRoot 'releases'
if (!(Test-Path $releasesDir)) { New-Item -Path $releasesDir -ItemType Directory | Out-Null }

# Preparar staging con la carpeta del tema completa
$tempBase = Join-Path $env:TEMP "$themeName-staging"
if (Test-Path $tempBase) { Remove-Item -Recurse -Force $tempBase }
New-Item -Path $tempBase -ItemType Directory | Out-Null
$stagingThemeDir = Join-Path $tempBase $themeName
New-Item -Path $stagingThemeDir -ItemType Directory | Out-Null

# Copiar archivos, excluyendo carpetas/archivos no deseados
  $excludes = @(
    '.git', '.githooks', 'releases', 'node_modules', 'vendor', '.vscode', '.idea', '.cache', 'build', 'dist'
  )
  # Evitar empaquetar una carpeta del tema anidada dentro del proyecto (p.ej. "news-media-theme/news-media-theme")
  if ($excludes -notcontains $themeName) { $excludes += $themeName }

Get-ChildItem -Path $projectRoot -Force | ForEach-Object {
  if ($excludes -contains $_.Name) { return }
  Copy-Item -Path $_.FullName -Destination $stagingThemeDir -Recurse -Force -Exclude @('*.map','*.log','*.lock')
}
# Crear zip incluyendo la carpeta del tema
$zipPath = Join-Path $releasesDir ("$releaseName.zip")
if (Test-Path $zipPath) { Remove-Item -Force $zipPath }
Compress-Archive -Path $stagingThemeDir -DestinationPath $zipPath -Force

Write-Host "Paquete generado: $zipPath"
