# Modelo relacional de Observation

El servicio Observation es dueño de los avistamientos, los lugares y las rutas de observación. Su esquema es `catalogo_aves_observation`.

No tiene foreign keys hacia otros servicios. `user_id` y `species_id` son ULID opacos y sus reglas se aplican desde el código y las APIs.

```mermaid
erDiagram
    USERS ..o{ SIGHTINGS : "user_id lógico desde Accounts"
    SPECIES ..o{ SIGHTINGS : "species_id lógico desde Catalog"
    PLACES ..o{ BIRDING_ROUTES : "place_id lógico local"

    USERS {
        char_26 id PK
    }
    SPECIES {
        char_26 id PK
        varchar sensitivity
    }
    SIGHTINGS {
        char_26 id PK
        char_26 user_id "logical ref"
        char_26 species_id "logical ref, nullable"
        timestamp observed_at
        integer individuals
        varchar behavior
        text notes
        varchar status
        varchar sensitivity
        decimal private_lat
        decimal private_lng
        varchar private_region
        decimal public_lat
        decimal public_lng
        varchar public_region
        timestamp created_at
        timestamp updated_at
    }
    PLACES {
        char_26 id PK
        varchar name
        varchar municipality
        varchar department
        varchar habitat
        text description
        decimal latitude
        decimal longitude
        boolean is_public
    }
    BIRDING_ROUTES {
        char_26 id PK
        char_26 place_id "logical ref"
        varchar name
        varchar difficulty
        integer duration_minutes
        decimal distance_km
        text description
        decimal start_latitude
        decimal start_longitude
        boolean is_public
    }
```

Las líneas punteadas representan referencias lógicas; no existen como claves foráneas de MySQL.

## Referencias y aplicación en código

| Campo | Entidad destino | Tipo | Regla implementada |
| --- | --- | --- | --- |
| `sightings.user_id` | `Accounts.users.id` | Lógica externa | El controlador toma el sujeto del JWT al crear, listar, editar o eliminar los avistamientos propios. |
| `sightings.species_id` | `Catalog.species.id` | Lógica externa, nullable | Al publicar una especie identificada, `CreateSighting` llama a Catalog para obtener su `sensitivity`. |
| `birding_routes.place_id` | `places.id` | Lógica local | `PlanningController` filtra las rutas por `place_id`; la migración no impone FK. |

## Privacidad geográfica de `sightings`

Cada avistamiento guarda dos representaciones de ubicación:

| Datos | Visibilidad | Uso |
| --- | --- | --- |
| `private_lat`, `private_lng`, `private_region` | Sólo dueño o revisión autorizada | Registro fuente y bitácora privada. |
| `public_lat`, `public_lng`, `public_region` | Mapa y consumidores públicos | Proyección persistida al crear o actualizar. |

La sensibilidad de Catalog determina la proyección pública:

| Sensibilidad | Resultado almacenado en la proyección pública |
| --- | --- |
| `EXACT` | Las coordenadas originales. |
| `APPROXIMATE` | Centro de una celda aproximada de 5,5 km. |
| `HIDDEN` | Coordenadas nulas y región protegida. |

Si Catalog no está disponible al publicar un avistamiento con especie, Observation falla de forma cerrada: no lo publica, pero permite guardarlo como borrador.

## Estados del avistamiento

- `DRAFT`: no genera ubicación pública.
- `PUBLISHED`: publicación con especie identificada y sensibilidad resuelta.
- `NEEDS_IDENTIFICATION`: publicación sin especie identificada.
- `VERIFIED`: estado habilitado para la proyección pública del mapa.

El mapa consulta sólo los estados publicados, con `public_lat` no nulo; nunca calcula ni serializa valores a partir de `private_lat` o `private_lng`.
