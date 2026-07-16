# Offroad API

Backend REST de la app **Offroad / Overlanding**: garaje de vehículos, historial de mantenimiento y base para futuros módulos (talleres, rutas, grupos).

## Estado del proyecto

MVP en desarrollo. Código publicado con fines de **portafolio** y aprendizaje. No es un producto en producción aún.

La idea del producto (directorio de talleres, garaje, rutas offroad, comunidad) es original de este proyecto. El roadmap y evolución hacia producción están en curso.

## Stack


| Tecnología           | Uso                                          |
| -------------------- | -------------------------------------------- |
| Laravel 13           | Framework API                                |
| Laravel Sanctum      | Autenticación por token (Bearer)             |
| PostgreSQL + PostGIS | Base de datos (geo listo para fase talleres) |
| Redis                | Cache y colas                                |
| Docker               | Entorno local                                |


## Estado actual (MVP base)

- [x] Registro, login, logout y perfil de usuario
- [x] CRUD de vehículos (por usuario)
- [x] CRUD de registros de mantenimiento
- [x] Roles (`user`, `workshop_owner`, `admin`) y registro con `account_type`
- [x] CRUD de talleres (dueño) + cola admin + directorio publicado
- [x] Contrato OpenAPI en `docs/openapi.yaml`
- [ ] Mapa de talleres (MapLibre / PostGIS)
- [ ] Rutas offroad, grupos, valoraciones

## Requisitos

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) en ejecución
- PowerShell (Windows)

No necesitas PHP ni Composer instalados localmente; el proyecto corre en contenedores.

## Instalación rápida

```powershell
git clone https://github.com/Engelb64/offroad-api.git
cd offroad-api
.\install.ps1
```

El script crea el proyecto Laravel (si aplica), instala dependencias, levanta Postgres/Redis y ejecuta migraciones.

Guía detallada: `[SETUP.md](SETUP.md)`

## Iniciar la API

```powershell
docker compose up app
```

- API: [http://localhost:8000](http://localhost:8000)  
- Health check: [http://localhost:8000/api/v1/health](http://localhost:8000/api/v1/health)

## Endpoints principales (`/api/v1`)


| Método   | Ruta                                 | Auth | Descripción                |
| -------- | ------------------------------------ | ---- | -------------------------- |
| `GET`    | `/health`                            | No   | Estado del servicio        |
| `POST`   | `/auth/register`                     | No   | Registro (`account_type` opcional) |
| `POST`   | `/auth/login`                        | No   | Login                      |
| `POST`   | `/auth/logout`                       | Sí   | Cerrar sesión              |
| `GET`    | `/auth/me`                           | Sí   | Perfil del usuario         |
| `POST`   | `/me/become-workshop-owner`          | Sí   | Convertir user → dueño     |
| `GET`    | `/workshops`                         | Sí   | Directorio (solo published)|
| `GET`    | `/workshops/{id}`                    | Sí   | Ficha de taller            |
| `GET`    | `/my/workshops`                      | Sí*  | Talleres del dueño         |
| `POST`   | `/my/workshops`                      | Sí*  | Crear taller               |
| `PUT`    | `/my/workshops/{id}`                 | Sí*  | Editar taller              |
| `POST`   | `/my/workshops/{id}/submit`          | Sí*  | Enviar a revisión          |
| `GET`    | `/admin/workshops`                   | Admin| Lista / cola por `status`  |
| `PATCH`  | `/admin/workshops/{id}/status`       | Admin| Publicar / suspender       |
| `GET`    | `/vehicles`                          | Sí   | Listar vehículos           |
| `POST`   | `/vehicles`                          | Sí   | Crear vehículo             |
| `GET`    | `/vehicles/{id}`                     | Sí   | Ver vehículo               |
| `PUT`    | `/vehicles/{id}`                     | Sí   | Actualizar vehículo        |
| `DELETE` | `/vehicles/{id}`                     | Sí   | Eliminar vehículo          |
| `GET`    | `/vehicles/{id}/maintenance-records` | Sí   | Historial de mantenimiento |
| `POST`   | `/vehicles/{id}/maintenance-records` | Sí   | Nuevo registro             |

\* Requiere rol `workshop_owner` o `admin`.

Contrato completo: `[docs/openapi.yaml](docs/openapi.yaml)`  
Diseño del módulo: `[docs/talleres.md](docs/talleres.md)`

### Cuentas demo (después de `db:seed`)

| Email | Password | Rol |
|-------|----------|-----|
| `admin@offroad.test` | `password` | admin |
| `owner@offroad.test` | `password` | workshop_owner |
| `user@offroad.test` | `password` | user |

El seeder también crea talleres de ejemplo (publicado, en revisión y borrador).

### Autenticación

Tras `register` o `login` recibes un token Sanctum:

```json
{
  "user": { "id": 1, "name": "...", "email": "..." },
  "token": "1|abcdef..."
}
```

En rutas protegidas:

```
Authorization: Bearer 1|abcdef...
```

## Variables de entorno

Copia `.env.example` a `.env` (el instalador lo hace automáticamente). Valores por defecto en Docker:

```env
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_DATABASE=offroad
DB_USERNAME=offroad
DB_PASSWORD=secret
```

## Estructura del proyecto

```
offroad-api/
├── app/Http/Controllers/Api/V1/   # Controladores REST
├── app/Models/                    # User, Vehicle, MaintenanceRecord
├── database/migrations/
├── docs/openapi.yaml              # Contrato de la API
├── docker-compose.yml
├── install.ps1
├── SETUP.md
└── routes/api.php
```

## Repo relacionado

- **[offroad-mobile](https://github.com/Engelb64/offroad-mobile)** — App React Native (Expo) que consume esta API

## Comandos útiles

```powershell
# Migraciones
docker compose run --rm app php artisan migrate

# Datos demo (admin, dueño, usuario + talleres)
docker compose run --rm app php artisan db:seed

# Shell en el contenedor
docker compose run --rm app bash

# Detener servicios
docker compose down
```

## Licencia

Este proyecto está bajo la licencia **AGPL-3.0**. Ver [LICENSE](LICENSE).

Si deseas usar este código en un producto propietario o comercial sin cumplir los términos de la AGPL, contacta al autor para una licencia comercial.

## Autor

Desarrollado por **Engelbertg J Bracho R** — idea original del producto Offroad / Overlanding.

- GitHub: [@Engelb64](https://github.com/Engelb64)
- Contacto: [engelbracho64@gmail.com](mailto:engelbracho64@gmail.com)

