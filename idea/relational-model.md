# Modelo relacional y referencias lógicas

Este documento representa el modelo de datos del MVP tal como se implementa en las migraciones y en el código. El sistema usa cuatro bases MySQL lógicas, una por servicio. Por diseño no hay *foreign keys* ni *joins* entre ellas.

Las líneas continuas (`--`) representan relaciones físicas dentro de una base. Las líneas punteadas (`..`) son referencias lógicas: se persiste un ULID y el código le atribuye significado, pero MySQL no puede validar que el registro exista en otro servicio.

```mermaid
erDiagram
    USERS ||--o{ REFRESH_TOKENS : "FK user_id"

    USERS ..o{ SIGHTINGS : "ULID lógico: user_id"
    SPECIES ..o{ SIGHTINGS : "ULID lógico: species_id"
    PLACES ..o{ BIRDING_ROUTES : "ULID lógico: place_id"

    USERS ..o{ POSTS : "ULID lógico: author_id"
    POSTS ||..o{ COMMENTS : "post_id usado por código"
    USERS ..o{ COMMENTS : "ULID lógico: author_id"
    POSTS ||..o{ REACTIONS : "post_id usado por código"
    USERS ..o{ REACTIONS : "ULID lógico: user_id"
    USERS ..o{ FOLLOWS : "follower_id / followed_id"
    USERS ..o{ REPORTS : "ULID lógico: reporter_id"
    USERS ..o{ AUDIT_RECORDS : "ULID lógico: actor_id"
    USERS ..o{ NOTIFICATIONS : "ULID lógico: user_id"
    POSTS ||..o{ NOTIFICATIONS : "reference_id cuando type=COMMENT"

    SIGHTINGS ..o{ POSTS : "reference_type=sighting"
    SPECIES ..o{ POSTS : "reference_type=species"
    PLACES ..o{ POSTS : "reference_type=place"
    BIRDING_ROUTES ..o{ POSTS : "reference_type=route"

    USERS {
        char_26 id PK
        varchar email UK
        varchar role
    }
    REFRESH_TOKENS {
        char_26 id PK
        char_26 user_id FK
        varchar token_hash UK
        timestamp expires_at
        timestamp revoked_at
    }
    SPECIES {
        char_26 id PK
        varchar scientific_name UK
        varchar sensitivity
        boolean is_published
    }
    SIGHTINGS {
        char_26 id PK
        char_26 user_id "logical ref"
        char_26 species_id "logical ref, nullable"
        varchar status
        varchar sensitivity
        decimal private_lat
        decimal private_lng
        decimal public_lat "nullable"
        decimal public_lng "nullable"
    }
    PLACES {
        char_26 id PK
        varchar name
        boolean is_public
    }
    BIRDING_ROUTES {
        char_26 id PK
        char_26 place_id "logical ref"
        varchar name
        boolean is_public
    }
    POSTS {
        char_26 id PK
        char_26 author_id "logical ref"
        varchar reference_type
        char_26 reference_id "polymorphic logical ref"
        varchar visibility
    }
    COMMENTS {
        char_26 id PK
        char_26 post_id "local logical ref"
        char_26 author_id "logical ref"
    }
    REACTIONS {
        bigint id PK
        char_26 post_id "local logical ref"
        char_26 user_id "logical ref"
        varchar reaction
    }
    FOLLOWS {
        bigint id PK
        char_26 follower_id "logical ref"
        char_26 followed_id "logical ref"
    }
    REPORTS {
        char_26 id PK
        char_26 reporter_id "logical ref"
        varchar target_type
        char_26 target_id "polymorphic logical ref"
        varchar status
    }
    AUDIT_RECORDS {
        char_26 id PK
        char_26 actor_id "logical ref"
        varchar target_type
        char_26 target_id "logical ref"
    }
    NOTIFICATIONS {
        char_26 id PK
        char_26 user_id "logical ref"
        char_26 reference_id "logical ref, nullable"
        varchar type
        timestamp read_at
    }
```

## Esquemas y relaciones

| Esquema | Relación o referencia | Tipo | Cómo se aplica |
| --- | --- | --- | --- |
| `catalogo_aves_accounts` | `refresh_tokens.user_id → users.id` | Física | Foreign key con `cascadeOnDelete`. |
| `catalogo_aves_observation` → Accounts | `sightings.user_id → users.id` | Lógica externa | `SightingController` toma el sujeto del JWT y filtra por `user_id`. |
| `catalogo_aves_observation` → Catalog | `sightings.species_id → species.id` | Lógica externa | `CreateSighting` consulta a Catalog por REST al publicar para obtener `sensitivity`. |
| `catalogo_aves_observation` | `birding_routes.place_id → places.id` | Lógica local | `PlanningController` filtra rutas por `place_id`; no tiene foreign key. |
| `catalogo_aves_community` | `comments.post_id → posts.id` | Lógica local | Community consulta comentarios por `post_id` y valida el post antes de mostrarlos. |
| `catalogo_aves_community` | `reactions.post_id → posts.id` | Lógica local | Cuenta y elimina reacciones por `post_id`; la restricción única es `post_id + user_id`. |
| `catalogo_aves_community` → Accounts | IDs de autor, seguidor, reportante, actor y destinatario | Lógica externa | Se reciben del JWT o de la URL; no se consulta Accounts. |
| `catalogo_aves_community` → otros dominios | `posts.reference_id` y `reports.target_id` | Lógica polimórfica | El tipo determina la intención (`sighting`, `species`, `route`, `place`, etc.); Community no lee ni modifica el registro destino. |
| `catalogo_aves_community` | `notifications.reference_id → posts.id` | Lógica local | Al comentar en un post ajeno se crea una notificación `COMMENT` que referencia dicho post. |

## Reglas de integridad implementadas

- Todos los identificadores de dominio son ULID de 26 caracteres, excepto los IDs incrementales de `reactions` y `follows`.
- `users.email`, `species.scientific_name` y `refresh_tokens.token_hash` son únicos.
- `reactions(post_id, user_id)` y `follows(follower_id, followed_id)` son únicos: el código usa operaciones idempotentes para conservar esa regla.
- Una publicación de Community puede no tener entidad externa: usa `reference_type = general` y un ULID marcador.
- `sightings.species_id` es nullable; una observación sin especie publicada pasa a `NEEDS_IDENTIFICATION`.
- No deben añadirse claves foráneas entre esquemas. Si se necesita validar o mutar un dato ajeno, debe hacerse mediante la API del servicio dueño.

## Flujo que materializa la relación más sensible

Al publicar un avistamiento con especie, `Observation` conserva `species_id` y llama a Catalog. Catalog devuelve la sensibilidad editorial; Observation persiste tanto la ubicación privada como la proyección pública resultante. Por ello, el mapa sólo consulta `public_lat` y `public_lng`; nunca deriva una respuesta desde las coordenadas privadas.
