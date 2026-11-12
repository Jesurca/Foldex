# Despliega Foldex en Azure App Service desde Windows (VS Code / PowerShell).
#   .\deploy\azure\desplegar.ps1                      # usa los valores por defecto
#   .\deploy\azure\desplegar.ps1 -App foldex-unab -Grupo Foldex
#
# Qué hace: toma el último commit (git archive, así no se cuelan .env ni archivos sin versionar),
# instala dependencias de producción de Composer, compila el frontend con Vite, arma un .zip
# y lo sube con `az webapp deploy`. Al reiniciar, deploy/azure/startup.sh migra y siembra la BD.
# Requisitos: az login hecho, PHP 8.3+, Composer, Node 20+ y git.
param(
    [string]$App = 'foldex-unab',
    [string]$Grupo = 'Foldex'
)

$ErrorActionPreference = 'Stop'
$raiz = (git rev-parse --show-toplevel).Trim()
$trabajo = Join-Path $env:TEMP 'foldex-despliegue'
$zip = Join-Path $env:TEMP 'foldex-despliegue.zip'

# PHP 8.4 de winget primero en el PATH si existe (XAMPP trae 8.2 y Laravel 13 pide 8.3+).
$php84 = Get-ChildItem "$env:LOCALAPPDATA\Microsoft\WinGet\Packages" -Directory -Filter 'PHP.PHP.NTS.8.4*' -ErrorAction SilentlyContinue | Select-Object -First 1
if ($php84) { $env:Path = "$($php84.FullName);$env:Path" }

function Paso([string]$texto) { Write-Host "`n==> $texto" -ForegroundColor Cyan }
# Composer, npm y az escriben progreso en stderr: en PowerShell 5.1 eso no debe cortar el script,
# así que se decide solo por el código de salida.
function Ejecutar([scriptblock]$bloque) {
    $anterior = $ErrorActionPreference
    $ErrorActionPreference = 'Continue'
    try { & $bloque 2>&1 | ForEach-Object { "$_" } } finally { $ErrorActionPreference = $anterior }
    if ($LASTEXITCODE -ne 0) { throw "Falló: $bloque" }
}

Paso 'Copia limpia del último commit'
if (Test-Path $trabajo) { Remove-Item -Recurse -Force $trabajo }
New-Item -ItemType Directory $trabajo | Out-Null
Ejecutar { git -C $raiz archive --format=tar -o "$trabajo.tar" HEAD }
Ejecutar { tar -xf "$trabajo.tar" -C $trabajo }
Remove-Item "$trabajo.tar"

Push-Location $trabajo
try {
    Paso 'Composer (sin dependencias de desarrollo)'
    Ejecutar { composer install --no-dev --optimize-autoloader --no-interaction --no-progress }

    Paso 'Frontend (npm ci + vite build)'
    Copy-Item .env.example .env   # solo para las variables VITE_* públicas; no se sube
    Ejecutar { npm ci --no-audit --no-fund }
    Ejecutar { npm run build }

    Paso 'Limpieza'
    Remove-Item -Recurse -Force node_modules, tests, docs -ErrorAction SilentlyContinue
    Remove-Item -Force .env, bootstrap/cache/config.php, bootstrap/cache/routes-*.php -ErrorAction SilentlyContinue

    Paso 'Empaquetando'
    if (Test-Path $zip) { Remove-Item -Force $zip }
    $entradas = Get-ChildItem -Force -Name   # PowerShell no expande comodines para programas externos
    Ejecutar { tar -a -c -f $zip @entradas }
}
finally {
    Pop-Location
}

Paso "Subiendo a $App ($Grupo)"
Ejecutar { az webapp deploy -g $Grupo -n $App --src-path $zip --type zip --clean false --restart true --timeout 900000 }

Write-Host "`nListo: https://$App.azurewebsites.net" -ForegroundColor Green
