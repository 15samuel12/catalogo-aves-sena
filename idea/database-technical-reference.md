# Referencia técnica de bases de datos

## Alcance y convenciones

Esta referencia describe el esquema definido por las migraciones de los cuatro servicios Laravel. Cada servicio es dueño de una base MySQL lógica independiente y ejecuta sus migraciones por separado.

| Servicio | Esquema | Dueño de datos |
| --- | --- | --- |
| Accounts | `catalogo_aves_accounts` | Identidad, perfil, roles y ciclo de vida de tokens. |
| Catalog | `catalogo_aves_catalog` | Especies y sensibilidad editorial. |
| Observation | `catalogo_aves_observation` | Avistamientos, privacidad geográfica, lugares y rutas. |
| Community | `catalogo_aves_community` | Contenido, interacción, moderación y notificaciones. |

Convenciones compartidas:

- Los IDs de entidades de dominio son `ULID` (`CHAR(26)` en MySQL) salvo `reactions` y `follows`, que usan `BIGINT UNSIGNED AUTO_INCREMENT`.
- `created_at` y `updated_at` son `TIMESTAMP NULL` creados por `$table->timestamps()`.
- `FK` significa clave foránea física; `REF` significa referencia lógica gestionada por código o API.
- No existen foreign keys, joins ni cascadas entre esquemas distintos.
- Las tablas `cache`, `cache_locks`, `jobs`, `job_batches` y `failed_jobs` son infraestructura estándar Laravel. Se incluyen por completitud, pero no pertenecen al modelo funcional.

---

## 1. Accounts: `catalogo_aves_accounts`

### 1.1 `users`

Fuente de verdad para cuentas, perfiles y roles.

| Columna | Tipo | Nulo | Clave / índice | Descripción |
| --- | --- | :---: | --- | --- |
| `id` | `CHAR(26)` | No | PK | ULID del usuario. |
| `name` | `VARCHAR(255)` | No | — | Nombre visible. |
| `email` | `VARCHAR(255)` | No | UNIQUE | Correo de inicio de sesión. |
| `email_verified_at` | `TIMESTAMP` | Sí | — | Fecha de validación de correo. |
| `password` | `VARCHAR(255)` | No | — | Hash de contraseña; nunca texto plano. |
| `remember_token` | `VARCHAR(100)` | Sí | — | Token estándar Laravel. |
| `role` | `VARCHAR(255)` | No | DEFAULT `user` | Rol usado para emitir permisos JWT. |
| `bio` | `TEXT` | Sí | — | Biografía del perfil. |
| `general_location` | `VARCHAR(255)` | Sí | — | Ubicación general del perfil, no coordenada privada. |
| `privacy_settings` | `JSON` | Sí | — | Ajustes de privacidad. |
| `preferences` | `JSON` | Sí | — | Preferencias de usuario. |
| `avatar_path` | `VARCHAR(255)` | Sí | — | Ruta de avatar en almacenamiento privado. |
| `created_at`, `updated_at` | `TIMESTAMP` | Sí | — | Auditoría temporal. |

Roles soportados por el código: `user`, `curator`, `moderator`, `admin` y `superadmin`.

### 1.2 `refresh_tokens`

Persistencia de refresh tokens revocables y rotables.

| Columna | Tipo | Nulo | Clave / índice | Descripción |
| --- | --- | :---: | --- | --- |
| `id` | `CHAR(26)` | No | PK | ULID del refresh token. |
| `user_id` | `CHAR(26)` | No | FK → `users.id` | Propietario del token. `ON DELETE CASCADE`. |
| `token_hash` | `VARCHAR(64)` | No | UNIQUE | Hash SHA-256 del token; no se persiste el valor bruto. |
| `expires_at` | `TIMESTAMP` | No | — | Expiración del token. |
| `revoked_at` | `TIMESTAMP` | Sí | — | Momento de revocación. |
| `created_at`, `updated_at` | `TIMESTAMP` | Sí | — | Auditoría temporal. |

Relación física: `users (1) ──< refresh_tokens (N)`.

### 1.3 Tablas auxiliares de Accounts

| Tabla | Clave e índices | Propósito |
| --- | --- | --- |
| `password_reset_tokens` | PK `email` | Tokens de restablecimiento de contraseña. |
| `sessions` | PK `id`; índice en `user_id` y `last_activity` | Sesiones Laravel. `user_id` no tiene FK explícita. |
| `cache` | PK `key` | Caché persistente. |
| `cache_locks` | PK `key` | Bloqueos de caché. |
| `jobs` | PK incremental; índice `queue` | Cola de trabajos. |
| `job_batches` | PK `id` | Agrupaciones de trabajos. |
| `failed_jobs` | PK incremental; UNIQUE `uuid` | Trabajos fallidos. |

### 1.4 Referencias salientes y entrantes

Accounts no persiste IDs de entidades externas. Expone el ULID de `users.id` como `sub` en JWT RSA. Catalog, Observation y Community verifican ese token con la clave pública y usan ese ULID como referencia lógica; no realizan consultas a esta base por petición.

---

## 2. Catalog: `catalogo_aves_catalog`

### 2.1 `species`

Fuente de verdad editorial de especies y de su política de sensibilidad geográfica.

| Columna | Tipo | Nulo | Clave / índice | Descripción |
| --- | --- | :---: | --- | --- |
| `id` | `CHAR(26)` | No | PK | ULID de especie. |
| `common_name` | `VARCHAR(255)` | No | — | Nombre común. |
| `scientific_name` | `VARCHAR(255)` | No | UNIQUE | Nombre científico. |
| `taxonomy` | `JSON` | Sí | — | Clasificación taxonómica. |
| `description` | `TEXT` | Sí | — | Descripción editorial. |
| `size` | `VARCHAR(255)` | Sí | — | Tamaño. |
| `habitat`, `feeding`, `distribution` | `TEXT` | Sí | — | Hábitat, alimentación y distribución. |
| `typical_hours`, `seasons`, `similar_species` | `JSON` | Sí | — | Datos de comportamiento y apoyo a identificación. |
| `distinguishing_features` | `TEXT` | Sí | — | Rasgos distintivos. |
| `conservation_status` | `VARCHAR(255)` | Sí | — | Estado de conservación. |
| `sensitivity` | `VARCHAR(255)` | No | DEFAULT `EXACT` | Política geográfica: `EXACT`, `APPROXIMATE` o `HIDDEN`. |
| `is_published` | `TINYINT(1)` | No | DEFAULT `1` | Control de publicación editorial. |
| `created_at`, `updated_at` | `TIMESTAMP` | Sí | — | Auditoría temporal. |

Índices explícitos: PK `id`, UNIQUE `scientific_name`.

### 2.2 Tablas auxiliares de Catalog

`cache`, `cache_locks`, `jobs`, `job_batches` y `failed_jobs`, con la misma estructura y propósito descritos para Accounts.

### 2.3 Integración con Observation

`Observation.sightings.species_id` es una REF hacia `Catalog.species.id`. No es una FK. Al crear o actualizar un avistamiento publicado con especie, `Observation` llama por REST a Catalog para leer `sensitivity`. Si Catalog no responde, Observation rechaza la publicación con `503 CATALOG_UNAVAILABLE`; los borradores no requieren esa consulta exitosa.

---

## 3. Observation: `catalogo_aves_observation`

### 3.1 `sightings`

Registro fuente de un avistamiento. Separa la ubicación exacta de la proyección que se puede divulgar.

| Columna | Tipo | Nulo | Clave / índice | Descripción |
| --- | --- | :---: | --- | --- |
| `id` | `CHAR(26)` | No | PK | ULID del avistamiento. |
| `user_id` | `CHAR(26)` | No | INDEX; REF → Accounts | Dueño del avistamiento, extraído del JWT. |
| `species_id` | `CHAR(26)` | Sí | INDEX; REF → Catalog | Especie identificada; puede ser nula. |
| `observed_at` | `TIMESTAMP` | No | INDEX | Fecha y hora observada. |
| `individuals` | `INT UNSIGNED` | No | DEFAULT `1` | Cantidad de individuos. |
| `behavior` | `VARCHAR(255)` | Sí | — | Comportamiento observado. |
| `notes` | `TEXT` | Sí | — | Notas privadas del dueño. |
| `status` | `VARCHAR(255)` | No | INDEX | `DRAFT`, `PUBLISHED`, `NEEDS_IDENTIFICATION` o `VERIFIED`. |
| `sensitivity` | `VARCHAR(255)` | No | DEFAULT `HIDDEN` | Sensibilidad aplicada en la publicación. |
| `private_lat`, `private_lng` | `DECIMAL(10,7)` | No | — | Coordenadas exactas, no públicas. |
| `private_region` | `VARCHAR(255)` | Sí | — | Referencia humana privada del lugar. |
| `public_lat`, `public_lng` | `DECIMAL(10,7)` | Sí | INDEX individual | Coordenadas permitidas para el mapa. |
| `public_region` | `VARCHAR(255)` | Sí | — | Región que se puede publicar. |
| `created_at`, `updated_at` | `TIMESTAMP` | Sí | — | Auditoría temporal. |

Índices explícitos: PK `id`; índices en `user_id`, `species_id`, `observed_at`, `status`, `public_lat` y `public_lng`.

Reglas de persistencia:

- `DRAFT`: `public_lat`, `public_lng` y `public_region` se guardan en `NULL`.
- `PUBLISHED` con especie: recibe la sensibilidad de Catalog antes de persistir.
- `NEEDS_IDENTIFICATION`: publicación sin `species_id`; usa sensibilidad `HIDDEN`.
- `EXACT`: la proyección pública conserva las coordenadas originales.
- `APPROXIMATE`: se persiste el centro de una cuadrícula de 0,05° (aprox. 5,5 km).
- `HIDDEN`: coordenadas públicas nulas y región pública protegida.

### 3.2 `places`

Inventario de sitios públicos de observación.

| Columna | Tipo | Nulo | Clave / índice | Descripción |
| --- | --- | :---: | --- | --- |
| `id` | `CHAR(26)` | No | PK | ULID del lugar. |
| `name`, `municipality`, `department`, `habitat` | `VARCHAR(255)` | No | — | Datos de localización y hábitat. |
| `description` | `TEXT` | No | — | Descripción del lugar. |
| `latitude`, `longitude` | `DECIMAL(10,7)` | No | — | Coordenadas públicas del lugar. |
| `is_public` | `TINYINT(1)` | No | INDEX; DEFAULT `1` | Visibilidad del lugar. |
| `created_at`, `updated_at` | `TIMESTAMP` | Sí | — | Auditoría temporal. |

### 3.3 `birding_routes`

Rutas públicas asociadas de manera lógica a un lugar.

| Columna | Tipo | Nulo | Clave / índice | Descripción |
| --- | --- | :---: | --- | --- |
| `id` | `CHAR(26)` | No | PK | ULID de ruta. |
| `place_id` | `CHAR(26)` | No | INDEX; REF → `places.id` | Lugar al que pertenece la ruta; sin FK. |
| `name`, `difficulty` | `VARCHAR(255)` | No | — | Nombre y dificultad. |
| `duration_minutes` | `SMALLINT UNSIGNED` | No | — | Duración estimada. |
| `distance_km` | `DECIMAL(5,2)` | No | — | Distancia. |
| `description` | `TEXT` | No | — | Descripción de la ruta. |
| `start_latitude`, `start_longitude` | `DECIMAL(10,7)` | No | — | Punto de inicio público. |
| `is_public` | `TINYINT(1)` | No | INDEX; DEFAULT `1` | Visibilidad de la ruta. |
| `created_at`, `updated_at` | `TIMESTAMP` | Sí | — | Auditoría temporal. |

`PlanningController` materializa la relación filtrando `birding_routes` por `place_id`; no usa `belongsTo`, `hasMany` ni foreign key.

### 3.4 Tablas auxiliares de Observation

`cache`, `cache_locks`, `jobs`, `job_batches` y `failed_jobs`, con la estructura estándar Laravel indicada en Accounts.

---

## 4. Community: `catalogo_aves_community`

### 4.1 `posts`

| Columna | Tipo | Nulo | Clave / índice | Descripción |
| --- | --- | :---: | --- | --- |
| `id` | `CHAR(26)` | No | PK | ULID de publicación. |
| `author_id` | `CHAR(26)` | No | INDEX | REF → `Accounts.users.id`. |
| `body` | `TEXT` | Sí | — | Texto de la publicación. |
| `reference_type` | `VARCHAR(255)` | No | — | `sighting`, `route`, `trip`, `place`, `species` o `general`. |
| `reference_id` | `CHAR(26)` | No | — | ID polimórfico de la entidad referida. |
| `visibility` | `VARCHAR(255)` | No | DEFAULT `PUBLIC` | `PUBLIC`, `FOLLOWERS` o `PRIVATE`. |
| `created_at`, `updated_at` | `TIMESTAMP` | Sí | — | Auditoría temporal. |

`reference_id` para una publicación general usa el ULID marcador `01J00000000000000000000000`. Community no valida ni consulta la entidad externa referida.

### 4.2 `comments`

| Columna | Tipo | Nulo | Clave / índice | Descripción |
| --- | --- | :---: | --- | --- |
| `id` | `CHAR(26)` | No | PK | ULID del comentario. |
| `post_id` | `CHAR(26)` | No | INDEX | REF local → `posts.id`; sin FK. |
| `author_id` | `CHAR(26)` | No | INDEX | REF → `Accounts.users.id`. |
| `body` | `TEXT` | No | — | Texto del comentario. |
| `created_at`, `updated_at` | `TIMESTAMP` | Sí | — | Auditoría temporal. |

### 4.3 `reactions`

| Columna | Tipo | Nulo | Clave / índice | Descripción |
| --- | --- | :---: | --- | --- |
| `id` | `BIGINT UNSIGNED` | No | PK, autoincrement | Identificador interno. |
| `post_id` | `CHAR(26)` | No | Parte de UNIQUE | REF local → `posts.id`. |
| `user_id` | `CHAR(26)` | No | Parte de UNIQUE | REF → `Accounts.users.id`. |
| `reaction` | `VARCHAR(255)` | No | — | `LIKE`, `LOVE` o `THANKS`. |
| `created_at`, `updated_at` | `TIMESTAMP` | Sí | — | Auditoría temporal. |

Restricción: `UNIQUE(post_id, user_id)`. El controlador aplica la operación idempotente `updateOrInsert`.

### 4.4 `follows`

| Columna | Tipo | Nulo | Clave / índice | Descripción |
| --- | --- | :---: | --- | --- |
| `id` | `BIGINT UNSIGNED` | No | PK, autoincrement | Identificador interno. |
| `follower_id` | `CHAR(26)` | No | Parte de UNIQUE | REF → `Accounts.users.id`: quien sigue. |
| `followed_id` | `CHAR(26)` | No | Parte de UNIQUE | REF → `Accounts.users.id`: quien es seguido. |
| `created_at`, `updated_at` | `TIMESTAMP` | Sí | — | Auditoría temporal. |

Restricción: `UNIQUE(follower_id, followed_id)`.

### 4.5 `reports`

| Columna | Tipo | Nulo | Clave / índice | Descripción |
| --- | --- | :---: | --- | --- |
| `id` | `CHAR(26)` | No | PK | ULID del reporte. |
| `reporter_id` | `CHAR(26)` | No | — | REF → `Accounts.users.id`. |
| `target_type` | `VARCHAR(255)` | No | — | `post`, `comment`, `sighting` o `identification`. |
| `target_id` | `CHAR(26)` | No | — | Identificador polimórfico del objetivo. |
| `reason` | `VARCHAR(255)` | No | — | Motivo normalizado del reporte. |
| `detail` | `TEXT` | Sí | — | Explicación opcional. |
| `status` | `VARCHAR(255)` | No | — | Inicia en `OPEN`; moderación lo cambia a `RESOLVED`. |
| `created_at`, `updated_at` | `TIMESTAMP` | Sí | — | Auditoría temporal. |

### 4.6 `audit_records`

| Columna | Tipo | Nulo | Clave / índice | Descripción |
| --- | --- | :---: | --- | --- |
| `id` | `CHAR(26)` | No | PK | ULID del registro de auditoría. |
| `actor_id` | `CHAR(26)` | No | — | REF → `Accounts.users.id`; moderador que actuó. |
| `target_type` | `VARCHAR(255)` | No | — | Tipo de objetivo moderado. |
| `target_id` | `CHAR(26)` | No | — | ID lógico del objetivo. |
| `action` | `VARCHAR(255)` | No | — | `APPROVE`, `HIDE`, `REJECT` o `REQUEST_EVIDENCE`. |
| `reason` | `TEXT` | No | — | Justificación de la acción. |
| `created_at`, `updated_at` | `TIMESTAMP` | Sí | — | Auditoría temporal. |

### 4.7 `notifications`

| Columna | Tipo | Nulo | Clave / índice | Descripción |
| --- | --- | :---: | --- | --- |
| `id` | `CHAR(26)` | No | PK | ULID de notificación. |
| `user_id` | `CHAR(26)` | No | INDEX | REF → `Accounts.users.id`; persona destinataria. |
| `type` | `VARCHAR(255)` | No | — | Tipo de evento, actualmente `COMMENT`. |
| `reference_id` | `CHAR(26)` | Sí | — | REF lógica, para comentario apunta a `posts.id`. |
| `body` | `TEXT` | No | — | Mensaje visible. |
| `read_at` | `TIMESTAMP` | Sí | — | Marca de lectura. |
| `created_at`, `updated_at` | `TIMESTAMP` | Sí | — | Auditoría temporal. |

Los endpoints de notificaciones filtran siempre por `user_id = JWT.sub` antes de listar o actualizar un registro.

### 4.8 Tablas auxiliares de Community

`cache`, `cache_locks`, `jobs`, `job_batches` y `failed_jobs`, con la estructura estándar Laravel indicada en Accounts.

---

## 5. Matriz de referencias entre bases

| Origen | Campo | Destino | Mecanismo de integridad |
| --- | --- | --- | --- |
| Observation | `sightings.user_id` | Accounts `users.id` | JWT y controles de propiedad en `SightingController`. |
| Observation | `sightings.species_id` | Catalog `species.id` | Consulta REST de sensibilidad en `CreateSighting`. |
| Community | `posts.author_id`, `comments.author_id`, `reactions.user_id`, `follows.*`, `reports.reporter_id`, `audit_records.actor_id`, `notifications.user_id` | Accounts `users.id` | ULID del JWT; sin consulta remota de Accounts. |
| Community | `posts.reference_id` | Observation o Catalog según `reference_type` | Referencia polimórfica opaca; sin consulta ni FK. |
| Community | `reports.target_id` | Community, Observation u otro destino según `target_type` | Referencia polimórfica opaca; sin FK. |

## 6. Reglas operativas para cambios de esquema

1. Una migración sólo puede modificar tablas de su propio servicio.
2. No se agregan foreign keys hacia otra base lógica ni se ejecutan joins entre bases.
3. Si una relación externa requiere validación de dominio, se consume el endpoint del servicio dueño; no su tabla.
4. Todo cambio de endpoint, payload o respuesta debe actualizar el OpenAPI correspondiente en `contracts/openapi/`.
5. Las migraciones deben ser aditivas y reversibles cuando sea posible. `bash bin/catalogo-aves prepare` migra y siembra sin borrar datos; `fresh` borra y reconstruye el esquema del servicio indicado.

## Referencias relacionadas

- [Arquitectura](architecture.md)
- [Límites de servicio](service-boundaries.md)
- [Guía de base de datos en lenguaje sencillo](database-guide.md)
- [Modelo relacional global](relational-model.md)
