# Instalación de offroad-api (requiere Docker Desktop en ejecución)
$ErrorActionPreference = "Stop"
$ProjectRoot = $PSScriptRoot

Write-Host "=== Offroad API - Instalacion ===" -ForegroundColor Cyan

# Verificar Docker
try {
    docker info | Out-Null
} catch {
    Write-Host "ERROR: Docker no esta en ejecucion. Abre Docker Desktop e intenta de nuevo." -ForegroundColor Red
    exit 1
}

Set-Location $ProjectRoot

# 1. Crear proyecto Laravel si no existe
if (-not (Test-Path "composer.json")) {
    Write-Host "`n[1/5] Creando proyecto Laravel..." -ForegroundColor Yellow

    $tempDir = Join-Path $env:TEMP "offroad-laravel-$(Get-Random)"
    New-Item -ItemType Directory -Path $tempDir -Force | Out-Null

    docker run --rm `
        -v "${tempDir}:/app" `
        -w /app `
        composer:latest `
        create-project laravel/laravel . --prefer-dist --no-interaction

    Get-ChildItem $tempDir | Move-Item -Destination $ProjectRoot -Force
    Remove-Item $tempDir -Recurse -Force

    Write-Host "Proyecto Laravel creado." -ForegroundColor Green
} else {
    Write-Host "`n[1/5] Laravel ya existe, omitiendo create-project." -ForegroundColor Gray
}

# 2. Instalar dependencias
Write-Host "`n[2/5] Instalando dependencias (Sanctum)..." -ForegroundColor Yellow
docker run --rm `
    -v "${ProjectRoot}:/app" `
    -w /app `
    composer:latest `
    require laravel/sanctum --no-interaction

# 3. Copiar archivos del scaffold
Write-Host "`n[3/5] Aplicando scaffold de la API..." -ForegroundColor Yellow
$scaffoldPath = Join-Path $ProjectRoot "scaffold"
if (Test-Path $scaffoldPath) {
    Copy-Item -Path "$scaffoldPath\*" -Destination $ProjectRoot -Recurse -Force
    Write-Host "Scaffold aplicado." -ForegroundColor Green
}

# 4. Configurar .env
Write-Host "`n[4/5] Configurando entorno..." -ForegroundColor Yellow
if (-not (Test-Path ".env")) {
    Copy-Item ".env.example" ".env"
}

# Actualizar variables clave en .env
$envContent = Get-Content ".env" -Raw
$envContent = $envContent -replace "APP_NAME=Laravel", "APP_NAME=OffroadAPI"
$envContent = $envContent -replace "DB_CONNECTION=sqlite", "DB_CONNECTION=pgsql"
$envContent = $envContent -replace "# DB_HOST=127.0.0.1", "DB_HOST=postgres"
$envContent = $envContent -replace "# DB_PORT=3306", "DB_PORT=5432"
$envContent = $envContent -replace "# DB_DATABASE=laravel", "DB_DATABASE=offroad"
$envContent = $envContent -replace "# DB_USERNAME=root", "DB_USERNAME=offroad"
$envContent = $envContent -replace "# DB_PASSWORD=", "DB_PASSWORD=secret"
Set-Content ".env" $envContent -NoNewline

# 5. Build y migrate con Docker Compose
Write-Host "`n[5/5] Construyendo contenedores y ejecutando migraciones..." -ForegroundColor Yellow
docker compose build app
docker compose up -d postgres redis

Write-Host "Esperando Postgres..." -ForegroundColor Gray
Start-Sleep -Seconds 8

docker compose run --rm app composer install --no-interaction
docker compose run --rm app php artisan key:generate --force
docker compose run --rm app php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider" --force
docker compose run --rm app php artisan migrate --force

Write-Host "`n=== Instalacion completada ===" -ForegroundColor Green
Write-Host "API disponible en: http://localhost:8000" -ForegroundColor Cyan
Write-Host "Health check:      http://localhost:8000/api/v1/health" -ForegroundColor Cyan
Write-Host "`nPara iniciar la API:" -ForegroundColor Yellow
Write-Host "  docker compose up app" -ForegroundColor White
