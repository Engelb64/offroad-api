# Módulo: Talleres (directorio)

Documento de diseño y paso a paso **antes de implementar código**.

**Repos afectados:** `offroad-api` · `offroad-mobile`  
**Estado:** diseño (sin código aún)  
**Fecha:** julio 2026  
**Última revisión:** alta self-service del dueño en registro + administración de su taller

---

## 1. Objetivo

Permitir que los usuarios encuentren talleres mecánicos útiles para offroad / overlanding, y que:

1. Un **usuario** se registre como persona normal **o** indicando que tiene un taller.
2. Un **dueño de taller** cree y administre la información de **su** taller.
3. Un **admin** modere el directorio (aprobar, editar, publicar, suspender).
4. Cualquier usuario autenticado consulte el directorio (listado / ficha; mapa más adelante).

---

## 2. Roles

| Rol | Código | Quién es |
|-----|--------|----------|
| Usuario | `user` | Dueño de vehículos, consulta talleres |
| Dueño de taller | `workshop_owner` | Persona/negocio que crea y administra su(s) taller(es) |
| Admin | `admin` | Moderador del directorio (nosotros / equipo) |

### Notas

- Un usuario tiene **un solo rol** en el MVP (campo `users.role`).
- Nadie se auto-asigna `admin`.
- `workshop_owner` se obtiene de dos formas:
  1. **Al registrarse**, marcando que tiene / quiere registrar un taller.
  2. **Después**, desde el perfil: “Registrar mi taller” (un `user` pasa a `workshop_owner`).
- Un `workshop_owner` **también** puede tener vehículos (garaje). El rol no le quita funciones de usuario.

---

## 3. Matriz de permisos (MVP)

| Acción | `user` | `workshop_owner` | `admin` |
|--------|:------:|:----------------:|:-------:|
| Ver talleres publicados | ✅ | ✅ | ✅ |
| Ver ficha de taller | ✅ | ✅ | ✅ |
| Registrarse como dueño de taller | ✅* | — | — |
| Convertirse en dueño (desde perfil) | ✅ | — | — |
| Crear **su** taller | ❌ | ✅ | ✅ |
| Editar **su** taller | ❌ | ✅ | ✅ |
| Enviar taller a revisión | ❌ | ✅ | ✅ |
| Editar cualquier taller | ❌ | ❌ | ✅ |
| Publicar / suspender taller | ❌ | ❌ | ✅ |
| Eliminar taller | ❌ | ❌ | ✅ |
| Ver cola de pendientes | ❌ | ❌ | ✅ |
| Cambiar rol de un usuario | ❌ | ❌ | ✅ |
| Valorar taller | Fase 2 | Fase 2 | Fase 2 |

\* En el formulario de registro elige el tipo de cuenta; si elige taller, el sistema le asigna `workshop_owner`.

---

## 4. Flujos de negocio

### 4.1 Registro con opción de taller

```
Pantalla Registro
  ├─ Nombre, email, password
  └─ Tipo de cuenta (elige uno):
        ○ Usuario (garaje / rutas)
        ○ Tengo un taller / negocio mecánico

Si elige "Usuario":
  → role = user
  → entra al garaje (flujo actual)

Si elige "Tengo un taller":
  → role = workshop_owner
  → onboarding: "Completa los datos de tu taller"
  → crea workshop con status = pending_review
  → pantalla: "Tu taller está en revisión. Mientras tanto puedes editar los datos."
  → (opcional) también puede usar el garaje
```

### 4.2 Usuario ya registrado que quiere añadir taller

```
Perfil / Ajustes → "Registrar mi taller"
  → confirma
  → role pasa de user a workshop_owner
  → mismo onboarding de crear taller (pending_review)
```

### 4.3 Moderación del admin

```
Dueño crea/edita taller → status = pending_review (o draft si aún no envía)
Admin revisa cola de pendientes
  ├─ Aprueba → status = published (visible en directorio)
  └─ Rechaza / suspende → status = suspended (+ motivo opcional más adelante)
```

### 4.4 Día a día del dueño

```
Dueño entra → "Mi taller"
  → ve sus talleres y el estado (borrador / en revisión / publicado / suspendido)
  → edita datos (nombre, teléfono, dirección, servicios, etc.)
  → si estaba published y cambia datos críticos, opcional:
       MVP simple: se mantiene published (confiar + admin puede suspender)
       Alternativa: volver a pending_review (más estricto; fase 2 si hace falta)
```

**Decisión MVP:** editar un taller **ya publicado** no lo baja a revisión automáticamente. El admin puede suspender si hay abuso.

### Estados del taller

| Estado | Significado | Visible en directorio | Quién lo pone |
|--------|-------------|------------------------|---------------|
| `draft` | Borrador, aún no enviado | No | Dueño |
| `pending_review` | Esperando aprobación | No | Dueño (al enviar) / sistema al crear desde registro |
| `published` | Publicado | Sí | Admin |
| `suspended` | Suspendido | No | Admin |

---

## 5. Modelo de datos

### 5.1 Cambio en `users`

```
users
  + role: string  default 'user'   # user | workshop_owner | admin
```

### 5.2 Tabla `workshops`

```
workshops
  id
  owner_id          required → users.id   # dueño (nullable solo si admin crea sin dueño)
  name              string
  slug              string unique nullable
  description       text nullable
  phone             string nullable
  email             string nullable
  website           string nullable
  address           string nullable
  city              string nullable
  state             string nullable
  country           string nullable default 'VE'
  latitude          decimal(10,7) nullable
  longitude         decimal(10,7) nullable
  services          json nullable         # ["aceite","suspension","4x4",...]
  schedule          json nullable
  status            string default 'draft'
  verified          boolean default false   # badge; solo admin
  photo_path        string nullable
  created_at
  updated_at

  indexes: status, owner_id
```

### 5.3 Relación

```
User 1 ─── N Workshop (como owner)
```

Un dueño puede tener **varios** talleres (varios locales).

---

## 6. API (borrador de endpoints)

Prefijo: `/api/v1`

### Auth / registro

| Método | Ruta | Descripción |
|--------|------|-------------|
| `POST` | `/auth/register` | Body incluye `account_type`: `user` \| `workshop_owner` |

Si `account_type = workshop_owner` → `role = workshop_owner`.  
Si omitido → `user` (compatibilidad).

### Lectura directorio

| Método | Ruta | Quién | Descripción |
|--------|------|-------|-------------|
| `GET` | `/workshops` | Auth | Solo `published` (filtros: city, service, q) |
| `GET` | `/workshops/{id}` | Auth | Ficha (published; dueño/admin pueden ver el suyo aunque no esté published) |

### Dueño (`workshop_owner`)

| Método | Ruta | Descripción |
|--------|------|-------------|
| `GET` | `/my/workshops` | Mis talleres (todos los status) |
| `POST` | `/my/workshops` | Crear taller (`draft` o `pending_review`) |
| `GET` | `/my/workshops/{id}` | Detalle (solo si soy owner) |
| `PUT` | `/my/workshops/{id}` | Actualizar campos permitidos |
| `POST` | `/my/workshops/{id}/submit` | Enviar a revisión (`draft` → `pending_review`) |

**Campos que el dueño SÍ puede editar:**  
`name`, `description`, `phone`, `email`, `website`, `address`, `city`, `state`, `country`, `latitude`, `longitude`, `schedule`, `services`, `photo_path`

**Campos que el dueño NO puede editar:**  
`status` (salvo vía `submit`), `verified`, `owner_id`

### Usuario → dueño

| Método | Ruta | Descripción |
|--------|------|-------------|
| `POST` | `/me/become-workshop-owner` | `user` → `workshop_owner` (idempotente si ya lo es) |

### Admin

| Método | Ruta | Descripción |
|--------|------|-------------|
| `GET` | `/admin/workshops` | Todos / filtro por status |
| `GET` | `/admin/workshops?status=pending_review` | Cola de revisión |
| `POST` | `/admin/workshops` | Crear (opcional `owner_id`) |
| `PUT` | `/admin/workshops/{id}` | Editar todo |
| `PATCH` | `/admin/workshops/{id}/status` | `published` / `suspended` / etc. |
| `DELETE` | `/admin/workshops/{id}` | Eliminar |
| `PATCH` | `/admin/users/{id}/role` | Asignar rol |

Middleware:

```
auth:sanctum
role:admin                         → /admin/*
role:workshop_owner|admin          → /my/workshops*
```

---

## 7. App móvil — pantallas

### Registro

| UI | Descripción |
|----|-------------|
| Selector **Tipo de cuenta** | “Usuario” / “Tengo un taller” |
| Si elige taller | Tras registro → wizard “Datos de tu taller” (nombre, ciudad, teléfono mínimo) → envío a revisión |

### Usuario (`user`)

| Pantalla | Descripción |
|----------|-------------|
| **Talleres** | Lista de publicados |
| **Ficha taller** | Contacto, servicios, verificado |
| **Perfil** | Botón “Registrar mi taller” |

### Dueño (`workshop_owner`)

| Pantalla | Descripción |
|----------|-------------|
| **Mi taller** | Lista + badge de estado |
| **Crear / Editar taller** | Formulario completo permitido |
| **Enviar a revisión** | CTA si está en `draft` |
| Aviso si `pending_review` | “En revisión; puedes seguir editando” |
| Aviso si `suspended` | “Suspendido; contacta soporte / admin” |

### Admin (`admin`)

**Recomendación MVP:** opción **C** — sección Admin en la app:

| Pantalla | Descripción |
|----------|-------------|
| Cola pendientes | Lista `pending_review` |
| Detalle + Aprobar / Suspender | Cambia status |
| (Opcional) Todos los talleres | Búsqueda |

Mapa MapLibre: **después** de listado + ficha.

---

## 8. Paso a paso de implementación

Cada paso debe quedar **usable y testeable** antes del siguiente.

### Paso 0 — Acordar este documento

- [x] Dueño puede registrarse y crear/administrar su taller
- [x] Admin aprueba antes de publicar (`pending_review` → `published`)
- [x] Admin en app (opción C — sección mínima)
- [x] País por defecto: `VE`
- [x] Editar publicado **no** vuelve a revisión (fase posterior)

### Paso 1 — Backend: roles + registro

1. Migración `users.role`
2. `RegisterRequest`: `account_type` opcional (`user` \| `workshop_owner`)
3. `UserResource` expone `role`
4. Middleware `EnsureUserHasRole`
5. `POST /me/become-workshop-owner`
6. Seeder admin local

**Estado:** implementado (julio 2026)  
**Criterio de listo:** registro como `workshop_owner` y `/auth/me` devuelve el rol.

### Paso 2 — Backend: modelo talleres + CRUD dueño

1. Migración `workshops`
2. Model, Requests, Resources
3. `MyWorkshopController` (CRUD + submit)
4. Policy: solo el owner

**Criterio de listo:** dueño crea taller `pending_review` y lo edita por API.

### Paso 3 — Backend: admin + directorio

1. Admin workshops + cambio de status
2. `GET /workshops` solo published
3. OpenAPI actualizado

**Criterio de listo:** admin publica → aparece en directorio.

### Paso 4 — Mobile: registro con tipo de cuenta

1. Selector en RegisterScreen
2. Si taller → pantalla/onboarding crear taller
3. Guardar `role` en auth store

**Criterio de listo:** flujo registro → crear taller → “en revisión” en la app.

### Paso 5 — Mobile: directorio + Mi taller

1. Lista / ficha de publicados
2. Sección Mi taller (crear, editar, estado)
3. Perfil: “Registrar mi taller” para `user`

**Criterio de listo:** demo user + dueño sin Postman.

### Paso 6 — Mobile: admin mínimo

1. Cola pendientes + aprobar/suspender

**Criterio de listo:** ciclo completo en la app.

### Paso 7 — Seeders + docs + push

1. Datos de prueba
2. README / OpenAPI
3. Commit cuando esté estable

---

## 9. Fuera de alcance (esta fase)

- Mapa MapLibre
- Valoraciones y comentarios
- Reservas / citas / pagos
- Chat con el taller
- Re-moderación automática al editar un publicado
- Motivo de rechazo con notificaciones (se puede añadir pronto)
- Fotos múltiples (1 foto opcional sí)
- PostGIS / “cerca de mí”
- Panel web separado

---

## 10. Decisiones

| # | Pregunta | Decisión | Estado |
|---|----------|----------|--------|
| 1 | ¿Dueño puede crear taller solo? | **Sí** (self-service) | ✅ Acordado |
| 2 | ¿Opción en el registro? | **Sí** (`account_type`) | ✅ Acordado |
| 3 | ¿User existente puede volverse dueño? | **Sí** (`/me/become-workshop-owner`) | ✅ Acordado |
| 4 | ¿Admin debe aprobar antes de publicar? | **Sí** (`pending_review` → `published`) | ✅ Acordado |
| 5 | ¿Admin en la app móvil? | **Sí**, sección mínima (opción C) | ✅ Acordado |
| 6 | ¿Directorio requiere login? | **Sí** por ahora | ✅ Acordado |
| 7 | ¿Un dueño, varios talleres? | **Sí** | ✅ Acordado |
| 8 | ¿País por defecto? | **`VE`** | ✅ Acordado |
| 9 | ¿Editar publicado vuelve a revisión? | **No en MVP** (más adelante) | ✅ Acordado |

---

## 11. Criterio de “módulo talleres listo” (MVP)

1. Alguien se **registra como dueño de taller** (o se convierte desde el perfil).
2. **Crea y edita** su taller.
3. El taller queda **en revisión** hasta que un **admin lo publica**.
4. Un **usuario** lo ve en el directorio de la app.

Sin mapa. Sin reseñas. Con alta self-service + moderación.

---

## 12. Orden de trabajo inmediato

Decisiones del §10 cerradas. Siguiente:

1. **Paso 1** — roles + registro con `account_type`
2. **Pasos 2–3** — talleres API (dueño + admin + listado)
3. **Pasos 4–6** — mobile (incluye sección admin)

---

## Relacionado

- [`ROADMAP.md`](../ROADMAP.md) — visión general
- [`offroad-api/docs/openapi.yaml`](../offroad-api/docs/openapi.yaml) — contrato API
- Próximo doc sugerido: `docs/mapa-maplibre.md`

---

*Documento vivo: actualizar conforme avance el desarrollo.*
