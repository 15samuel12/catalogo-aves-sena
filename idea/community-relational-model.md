# Modelo relacional de Community

El servicio Community es dueño de publicaciones, conversación, reacciones, seguimientos, reportes, auditoría y notificaciones. Su esquema es `catalogo_aves_community`.

Las relaciones que se muestran como punteadas son referencias lógicas. No hay foreign keys en las tablas de Community, ni consultas SQL a Accounts, Observation o Catalog.

```mermaid
erDiagram
    USERS ..o{ POSTS : "author_id lógico"
    POSTS ||..o{ COMMENTS : "post_id lógico local"
    USERS ..o{ COMMENTS : "author_id lógico"
    POSTS ||..o{ REACTIONS : "post_id lógico local"
    USERS ..o{ REACTIONS : "user_id lógico"
    USERS ..o{ FOLLOWS : "follower_id / followed_id lógico"
    USERS ..o{ REPORTS : "reporter_id lógico"
    USERS ..o{ AUDIT_RECORDS : "actor_id lógico"
    USERS ..o{ NOTIFICATIONS : "user_id lógico"
    POSTS ||..o{ NOTIFICATIONS : "reference_id para COMMENT"

    SIGHTINGS ..o{ POSTS : "reference_type=sighting"
    SPECIES ..o{ POSTS : "reference_type=species"
    PLACES ..o{ POSTS : "reference_type=place"
    BIRDING_ROUTES ..o{ POSTS : "reference_type=route"
    POSTS ..o{ REPORTS : "target_type=post"
    COMMENTS ..o{ REPORTS : "target_type=comment"
    SIGHTINGS ..o{ REPORTS : "target_type=sighting"

    USERS {
        char_26 id PK
    }
    POSTS {
        char_26 id PK
        char_26 author_id "logical ref"
        text body
        varchar reference_type
        char_26 reference_id "polymorphic logical ref"
        varchar visibility
        timestamp created_at
        timestamp updated_at
    }
    COMMENTS {
        char_26 id PK
        char_26 post_id "local logical ref"
        char_26 author_id "logical ref"
        text body
        timestamp created_at
        timestamp updated_at
    }
    REACTIONS {
        bigint id PK
        char_26 post_id "local logical ref"
        char_26 user_id "logical ref"
        varchar reaction
        timestamp created_at
        timestamp updated_at
    }
    FOLLOWS {
        bigint id PK
        char_26 follower_id "logical ref"
        char_26 followed_id "logical ref"
        timestamp created_at
        timestamp updated_at
    }
    REPORTS {
        char_26 id PK
        char_26 reporter_id "logical ref"
        varchar target_type
        char_26 target_id "polymorphic logical ref"
        varchar reason
        text detail
        varchar status
        timestamp created_at
        timestamp updated_at
    }
    AUDIT_RECORDS {
        char_26 id PK
        char_26 actor_id "logical ref"
        varchar target_type
        char_26 target_id "logical ref"
        varchar action
        text reason
        timestamp created_at
        timestamp updated_at
    }
    NOTIFICATIONS {
        char_26 id PK
        char_26 user_id "logical ref"
        varchar type
        char_26 reference_id "logical ref, nullable"
        text body
        timestamp read_at
        timestamp created_at
        timestamp updated_at
    }
```

## Referencias y aplicación en código

| Campo | Entidad destino | Tipo | Regla implementada |
| --- | --- | --- | --- |
| `posts.author_id` | `Accounts.users.id` | Lógica externa | Se asigna desde el sujeto del JWT al crear una publicación. |
| `comments.post_id` | `posts.id` | Lógica local | Se usa para listar comentarios; el código verifica que el post sea público antes de exponerlos. |
| `comments.author_id` | `Accounts.users.id` | Lógica externa | Se asigna desde el JWT. |
| `reactions.post_id` | `posts.id` | Lógica local | Se cuentan y actualizan reacciones por publicación. |
| `reactions.user_id` | `Accounts.users.id` | Lógica externa | Identifica a la persona que reacciona. |
| `follows.follower_id`, `follows.followed_id` | `Accounts.users.id` | Lógica externa | Modela el seguimiento entre dos personas por sus ULID. |
| `notifications.user_id` | `Accounts.users.id` | Lógica externa | Limita la lectura y el marcado de alertas a la persona destinataria. |
| `notifications.reference_id` | `posts.id` | Lógica local | Un comentario sobre un post ajeno genera una notificación de tipo `COMMENT` que apunta al post. |
| `posts.reference_id` | Avistamiento, especie, lugar o ruta | Lógica polimórfica externa | `reference_type` define el tipo de entidad; Community no consulta ni modifica el destino. |
| `reports.target_id` | Post, comentario, avistamiento o identificación | Lógica polimórfica | `target_type` define el objetivo del reporte. |
| `audit_records.target_id` | Reporte u otro objetivo | Lógica local/polimórfica | Registra la acción de moderación y su motivo. |

## Restricciones de integridad implementadas

| Tabla | Regla |
| --- | --- |
| `reactions` | `UNIQUE(post_id, user_id)`: una persona puede conservar una sola reacción por publicación. |
| `follows` | `UNIQUE(follower_id, followed_id)`: evita repetir un seguimiento. |
| `posts` | `reference_type` permite `sighting`, `route`, `trip`, `place`, `species` o `general`. |
| `reports` | `target_type` permite `post`, `comment`, `sighting` o `identification`; el estado inicia en `OPEN`. |
| `notifications` | Las operaciones de lectura siempre filtran por el `user_id` del JWT. |

## Flujo de comentario y notificación

1. Una persona autenticada crea un `comment` con su `author_id` y el `post_id` indicado.
2. Community obtiene localmente el `author_id` del post.
3. Si no es la misma persona, crea una `notification` para el autor original.
4. La notificación usa `reference_id = post.id`, de modo que el cliente puede abrir la conversación correspondiente.

Los identificadores de Accounts, Observation y Catalog no se validan con joins ni claves foráneas. Cuando se necesita una política de otro dominio, se debe usar la API del servicio dueño.
