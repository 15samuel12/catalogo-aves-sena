# Guía sencilla de la base de datos

Esta guía explica dónde guarda la información **Catálogo de Aves** y cómo se relacionan los datos. Está escrita para entender el proyecto sin necesidad de conocer Laravel, SQL o microservicios.

## Idea principal

La aplicación no usa una sola base de datos gigante. Usa cuatro bases más pequeñas, cada una responsable de un tema:

| Base de datos | Se encarga de | Ejemplos de información |
| --- | --- | --- |
| `catalogo_aves_accounts` | Personas y acceso | Nombre, correo, contraseña, rol y sesión. |
| `catalogo_aves_catalog` | Especies de aves | Nombre común, nombre científico, hábitat y nivel de protección. |
| `catalogo_aves_observation` | Avistamientos y sitios | Fecha de observación, especie, ubicación, lugares y rutas. |
| `catalogo_aves_community` | Comunidad | Publicaciones, comentarios, reacciones, seguimientos, reportes y notificaciones. |

Separarlas evita que una parte del sistema pueda cambiar datos que pertenecen a otra. Por ejemplo, Community puede hablar sobre un avistamiento, pero no puede editarlo ni ver su ubicación privada.

## Cómo se conectan los datos

Cada registro importante tiene un identificador único llamado **ULID**. Es un texto de 26 caracteres parecido a este:

```text
01K1T46ZG6Y69QDTSA53D2YVND
```

En lugar de unir las cuatro bases directamente, una tabla guarda el ULID de otra entidad cuando lo necesita. A esto se le llama una **referencia lógica**.

Ejemplos:

- Un avistamiento guarda el `user_id` de la persona que lo registró.
- Un avistamiento puede guardar el `species_id` de la especie identificada.
- Un comentario guarda el `post_id` de la publicación a la que responde.

Esto no significa que todas las relaciones sean claves foráneas de MySQL. La mayoría se revisan desde el código de la aplicación y las APIs. Así se mantiene la independencia entre las cuatro bases.

```text
Persona ── registra ──> Avistamiento ── puede indicar ──> Especie
   │                         │
   └── publica/comenta ──> Comunidad

Lugar ── tiene ──> Ruta de observación
```

## 1. Personas y acceso: Accounts

La base `catalogo_aves_accounts` guarda la información necesaria para crear una cuenta e ingresar.

### Tabla `users`

Guarda una fila por cada persona registrada.

| Campo importante | Explicación sencilla |
| --- | --- |
| `id` | Identificador único de la persona. |
| `name` | Nombre que se muestra en la aplicación. |
| `email` | Correo de acceso. No puede repetirse. |
| `password` | Contraseña protegida; no se guarda como texto legible. |
| `role` | Tipo de usuario: `user`, `moderator`, `admin`, `superadmin` o `curator`. |
| `bio`, `general_location`, `avatar_path` | Datos opcionales del perfil. |
| `privacy_settings`, `preferences` | Configuraciones elegidas por la persona. |

### Tabla `refresh_tokens`

Guarda los tokens que permiten renovar una sesión sin volver a escribir correo y contraseña.

- Cada token pertenece a una persona mediante `user_id`.
- El valor real del token no se guarda; se guarda una versión protegida llamada `token_hash`.
- Si se elimina una cuenta, sus tokens también se eliminan automáticamente.

### Otras tablas de Accounts

- `password_reset_tokens`: ayuda a recuperar una contraseña.
- `sessions`: guarda información técnica de sesiones de Laravel.
- `cache`, `jobs`, `failed_jobs` y similares: tablas internas para tareas y rendimiento; no son datos visibles de la aplicación.

## 2. Catálogo de aves: Catalog

La base `catalogo_aves_catalog` contiene la información editorial de las especies. Es la fuente oficial de la aplicación para saber qué especie es cada una y qué tan sensible es su ubicación.

### Tabla `species`

Cada fila representa una especie.

| Campo importante | Explicación sencilla |
| --- | --- |
| `id` | Identificador único de la especie. |
| `common_name` | Nombre común, por ejemplo: “Cóndor de los Andes”. |
| `scientific_name` | Nombre científico. No puede repetirse. |
| `taxonomy` | Clasificación biológica de la especie. |
| `description`, `habitat`, `feeding`, `distribution` | Información para aprender a reconocerla. |
| `conservation_status` | Estado de conservación. |
| `sensitivity` | Define cómo se debe proteger la ubicación de los avistamientos. |
| `is_published` | Indica si la especie ya se puede mostrar al público. |

### Protección según la especie

El campo `sensitivity` puede tener tres valores:

| Valor | Qué se muestra en el mapa |
| --- | --- |
| `EXACT` | El punto real del avistamiento. |
| `APPROXIMATE` | Un punto aproximado, no el lugar exacto. |
| `HIDDEN` | No se muestran coordenadas. |

## 3. Avistamientos y lugares: Observation

La base `catalogo_aves_observation` guarda los registros de avistamientos. También guarda lugares recomendados y rutas de observación.

### Tabla `sightings`

Cada fila es un avistamiento registrado por una persona.

| Campo importante | Explicación sencilla |
| --- | --- |
| `id` | Identificador único del avistamiento. |
| `user_id` | ULID de la persona que lo creó. Apunta de forma lógica a `users`. |
| `species_id` | ULID de la especie. Es opcional cuando aún no se ha identificado el ave. |
| `observed_at` | Fecha y hora en que se vio el ave. |
| `individuals` | Cantidad de aves observadas. |
| `behavior`, `notes` | Comportamiento y notas de campo. |
| `status` | Estado del registro: borrador, publicado, verificado o pendiente de identificación. |
| `sensitivity` | Regla de protección usada para ese registro. |

### Ubicación privada y ubicación pública

Un avistamiento guarda dos versiones de la ubicación:

| Datos | Quién puede verlos | Para qué sirven |
| --- | --- | --- |
| `private_lat`, `private_lng`, `private_region` | La persona dueña y una futura revisión autorizada | Conservan el lugar real. |
| `public_lat`, `public_lng`, `public_region` | El mapa público | Muestran una ubicación segura o ninguna, según la sensibilidad. |

Esta separación es importante para proteger nidos, especies amenazadas y zonas sensibles. El mapa público nunca usa directamente la ubicación privada.

### Tablas `places` y `birding_routes`

| Tabla | Qué guarda |
| --- | --- |
| `places` | Sitios públicos para observar aves: nombre, municipio, departamento, hábitat, descripción y coordenadas. |
| `birding_routes` | Recorridos dentro o cerca de un lugar: nombre, dificultad, duración, distancia y punto de inicio. |

Una ruta guarda `place_id`, que indica a cuál lugar pertenece. Actualmente esta relación se controla desde el código; no tiene una clave foránea en MySQL.

## 4. Comunidad: Community

La base `catalogo_aves_community` permite conversar y participar alrededor de las aves y avistamientos.

| Tabla | Qué guarda |
| --- | --- |
| `posts` | Publicaciones. Pueden ser generales o referirse a una especie, avistamiento, lugar o ruta. |
| `comments` | Comentarios hechos sobre una publicación. |
| `reactions` | Reacciones como `LIKE`, `LOVE` o `THANKS`. |
| `follows` | Quién sigue a quién. |
| `reports` | Reportes de contenido inapropiado, spam o datos incorrectos. |
| `audit_records` | Historial de acciones de moderación. |
| `notifications` | Avisos para una persona, por ejemplo, cuando alguien comenta su publicación. |

### Relaciones principales de Community

- Un `post` guarda el `author_id` de quien lo publicó.
- Un `comment` guarda el `post_id` de la publicación y el `author_id` de quien escribió.
- Una `reaction` guarda el `post_id` y el `user_id` de quien reaccionó.
- Una persona solo puede tener una reacción por publicación.
- Una fila en `follows` guarda a quien sigue (`follower_id`) y a quien es seguido (`followed_id`).
- Una `notification` pertenece a una persona mediante `user_id` y puede apuntar a una publicación mediante `reference_id`.

Community solo conserva identificadores de personas y de otras entidades. No guarda perfiles completos, coordenadas privadas ni copias de las especies.

## Ejemplo de recorrido de un dato

Imagina que Ana registra un colibrí:

1. Ana existe como una fila en `Accounts.users`.
2. El colibrí existe como una fila en `Catalog.species`.
3. Ana crea un registro en `Observation.sightings`; este guarda el ULID de Ana en `user_id` y el ULID del colibrí en `species_id`.
4. Observation consulta la sensibilidad de esa especie en Catalog.
5. Observation guarda la ubicación real de Ana de forma privada y crea una versión pública segura.
6. Si Ana crea una publicación sobre ese avistamiento, Community guarda un `post` con su `author_id` y, si corresponde, una referencia al ULID del avistamiento.
7. Otra persona puede comentar o reaccionar sin obtener la ubicación privada del avistamiento.

## Reglas importantes al cambiar la base de datos

- Cada servicio solo modifica sus propias tablas.
- No se deben crear claves foráneas entre las cuatro bases de datos.
- Para usar datos de otro servicio, se usa su API o se conserva su ULID como referencia lógica.
- Si cambias tablas, campos o respuestas de una API, actualiza también la migración, las pruebas y el archivo OpenAPI correspondiente en `contracts/openapi/`.
- Las migraciones agregan o cambian tablas sin borrar datos. El comando `fresh` sí reinicia el esquema y borra datos de un servicio.

## Documentos relacionados

- [Modelo relacional general](relational-model.md)
- [Modelo de Accounts](accounts-relational-model.md)
- [Modelo de Observation](observation-relational-model.md)
- [Modelo de Community](community-relational-model.md)
- [Arquitectura del proyecto](architecture.md)
