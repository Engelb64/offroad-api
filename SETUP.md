# Guia de instalacion — Offroad API

## Paso 1: Abrir Docker Desktop

Asegurate de que Docker Desktop este **en ejecucion** antes de continuar.

Si ves el error:
```
failed to connect to the docker API at npipe:////./pipe/dockerDesktopLinuxEngine
```
significa que Docker no esta arrancado. Abrelo y espera a que diga "Running".

---

## Paso 2: Ejecutar el instalador

Abre PowerShell en la carpeta `offroad-api`:

```powershell
cd C:\appro\front\offroad-api
.\install.ps1
```

Si PowerShell bloquea el script:

```powershell
Set-ExecutionPolicy -Scope CurrentUser RemoteSigned
.\install.ps1
```

La instalacion tarda unos minutos la primera vez (descarga Laravel, dependencias, imagenes Docker).

---

## Paso 3: Iniciar la API

```powershell
docker compose up app
```

Abre en el navegador: http://localhost:8000/api/v1/health

Deberias ver:

```json
{
  "status": "ok",
  "service": "offroad-api",
  "version": "v1"
}
```

---

## Paso 4: Probar registro y vehiculo

Usa Postman, Insomnia o curl:

### 4.1 Registro

```
POST http://localhost:8000/api/v1/auth/register
Content-Type: application/json

{
  "name": "Tu Nombre",
  "email": "tu@email.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```

Guarda el `token` de la respuesta.

### 4.2 Crear vehiculo

```
POST http://localhost:8000/api/v1/vehicles
Authorization: Bearer TU_TOKEN
Content-Type: application/json

{
  "make": "Jeep",
  "model": "Wrangler",
  "year": 2020,
  "nickname": "Rockeadora",
  "odometer": 45000
}
```

### 4.3 Registrar mantenimiento

```
POST http://localhost:8000/api/v1/vehicles/1/maintenance-records
Authorization: Bearer TU_TOKEN
Content-Type: application/json

{
  "type": "oil_change",
  "title": "Cambio de aceite y filtro",
  "performed_at": "2026-06-15",
  "odometer": 44500,
  "cost": 85.50,
  "notes": "Aceite 5W-30 sintetico"
}
```

---

## Alternativa: PHP local (sin Docker para la app)

Si prefieres instalar PHP en Windows:

1. Instala [Laragon](https://laragon.org/) o PHP 8.3 + Composer
2. En `offroad-api`, ejecuta manualmente:

```powershell
composer create-project laravel/laravel .
composer require laravel/sanctum
# Copia scaffold (o ejecuta install.ps1 que detecta composer.json)
Copy-Item -Path scaffold\* -Destination . -Recurse -Force
copy .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

Necesitaras Postgres local o cambiar a SQLite en `.env` para pruebas rapidas.

---

## Que incluye el MVP del backend

- [x] Laravel + Sanctum (auth por token)
- [x] CRUD de vehiculos (por usuario)
- [x] CRUD de registros de mantenimiento
- [x] Postgres + PostGIS (listo para geo en fase talleres)
- [x] Redis
- [x] OpenAPI en `docs/openapi.yaml`
- [ ] Talleres + mapa (siguiente fase)
- [ ] Subida de fotos S3 (siguiente fase)

---

## Problemas comunes

| Problema | Solucion |
|----------|----------|
| Docker no conecta | Abrir Docker Desktop |
| Puerto 8000 ocupado | Cambiar en `docker-compose.yml` a `"8001:8000"` |
| Puerto 5432 ocupado | Cambiar postgres a `"5433:5432"` y `DB_PORT=5433` en `.env` |
| Error de migracion | `docker compose run --rm app php artisan migrate:fresh` |

---

## Siguiente paso

Cuando la API funcione, iniciar **offroad-mobile** (Expo) y conectar `EXPO_PUBLIC_API_URL=http://TU_IP:8000` para probar en dispositivo fisico.

*En emulador Android usa `http://10.0.2.2:8000`; en iOS simulator `http://localhost:8000`.*
