# Filtro de lenguaje no permitido

## Propósito y alcance

Este documento explica el filtro de lenguaje no permitido que pertenece a `community-service`. Su objetivo es impedir que una publicación o comentario se guarde cuando coincide con un término activo definido por moderación.

La protección es de servidor: se ejecuta aunque alguien envíe una petición sin usar la SPA Angular. El filtro no modifica el texto aceptado ni conserva el texto rechazado; sólo crea una representación temporal para compararlo.

Actualmente protege:

- `posts.body`, con un máximo de 4.000 caracteres.
- `comments.body`, con un máximo de 2.000 caracteres.

No protege todavía los campos de Accounts u Observation. Cada microservicio es independiente y no debe consultar directamente la base de datos de Community.

Para conocer qué se cambió respecto al enfoque original basado en listas rígidas, consulta el [registro de cambios del filtro](content-moderation-changes.md).

## Mapa de archivos

| Archivo | Responsabilidad |
| --- | --- |
| `apps/services/community-service/database/migrations/2026_11_08_000100_create_blocked_terms_table.php` | Crea la tabla editable de términos bloqueados. |
| `apps/services/community-service/database/seeders/BlockedTermSeeder.php` | Carga o actualiza el vocabulario inicial. |
| `apps/services/community-service/database/seeders/DatabaseSeeder.php` | Llama al seeder de términos durante la preparación de Community. |
| `apps/services/community-service/app/Services/ContentModerationService.php` | Normaliza el texto y realiza las coincidencias. |
| `apps/services/community-service/app/Rules/NoProfanity.php` | Adapta el servicio a la validación estándar de Laravel. |
| `apps/services/community-service/app/Http/Controllers/CommunityController.php` | Declara qué campos HTTP están protegidos. |
| `apps/services/community-service/tests/Unit/ContentModerationServiceTest.php` | Prueba normalización, coincidencias, falsos positivos y la regla. |
| `contracts/openapi/community.yaml` | Declara que crear publicaciones y comentarios puede responder `422`. |

## Flujo completo de una petición

```text
POST /api/community/v1/posts/{post}/comments
                 |
                 v
CommunityController::comment()
  $request->validate([body => new NoProfanity])
                 |
                 v
NoProfanity::validate()
                 |
                 v
ContentModerationService::containsActiveBlockedTerm()
  consulta blocked_terms activos
  normaliza el texto y los términos
  compara según EXACT, PHRASE o ROOT
                 |
       +---------+---------+
       |                   |
    coincidencia        sin coincidencia
       |                   |
 HTTP 422              inserta en comments
 no se guarda          respuesta 201
```

La validación ocurre antes de generar el ULID e insertar el comentario o publicación. Por ello, un texto bloqueado no crea un registro parcial ni una notificación.

## Tabla `blocked_terms`

La migración crea esta tabla dentro de la base lógica `catalogo_aves_community`:

| Columna | Función |
| --- | --- |
| `id` | ULID que identifica la regla. |
| `term` | Valor administrado por moderación. Para `ROOT`, es una raíz de comparación, no necesariamente una palabra completa. |
| `normalized_term` | Versión lista para comparar. Tiene restricción `UNIQUE` para evitar reglas duplicadas. |
| `match_type` | Estrategia de coincidencia: `EXACT`, `PHRASE` o `ROOT`. |
| `is_active` | Si es `false`, la regla existe pero no se aplica. |
| `created_at`, `updated_at` | Auditoría temporal de Laravel. |

### Tipos de coincidencia

| Tipo | Qué compara | Cuándo usarlo |
| --- | --- | --- |
| `EXACT` | Un término completo delimitado por espacios. | Para una sola palabra que no debe coincidir dentro de otra. |
| `PHRASE` | Varias palabras consecutivas. | Para expresiones compuestas. |
| `ROOT` | Una raíz dentro de un único token o palabra. | Sólo para derivados revisados; la raíz debe tener al menos seis caracteres. |

`ROOT` es más agresivo. Una raíz muy corta puede bloquear nombres de personas, especies o lugares válidos. Por ese motivo, `containsRoot()` rechaza reglas `ROOT` de menos de seis caracteres y el seeder usa sólo raíces revisadas.

## `ContentModerationService`

Ubicación: `apps/services/community-service/app/Services/ContentModerationService.php`.

Esta es la clase que contiene la política de comparación. No es un controlador y no conoce detalles de HTTP como códigos de respuesta, rutas o JSON.

### `containsActiveBlockedTerm(string $text): bool`

Es el punto de entrada usado en producción. Primero llama a `activeTerms()` y después delega en `containsBlockedTerm()`.

Devuelve sólo `true` o `false`: no devuelve el término detectado. Así el mensaje HTTP no revela qué regla se activó ni facilita probar variantes contra el vocabulario.

### `activeTerms(): array`

Hace esta consulta lógica:

```text
SELECT normalized_term, match_type
FROM blocked_terms
WHERE is_active = true
ORDER BY normalized_term DESC
```

No se usa caché en la primera versión. La lista inicial es pequeña y así activar o desactivar un término surte efecto en la siguiente petición. Si el vocabulario crece mucho, se puede añadir caché con una invalidación explícita cuando se edite una regla.

### `containsBlockedTerm(string $text, iterable $terms): bool`

Es la función de comparación pura. Recibe términos explícitos para poder probar el algoritmo sin base de datos.

1. Obtiene varias representaciones del texto con `candidates()`.
2. Normaliza cada término.
3. Si la regla es `ROOT`, llama a `containsRoot()`.
4. Para `EXACT` y `PHRASE`, busca el término rodeado por espacios. Esa técnica evita encontrar una palabra bloqueada dentro de otra no relacionada.

El término no se busca con una coincidencia de subcadena sin límites. Eso reduce falsos positivos, por ejemplo cuando un término corto aparece accidentalmente dentro de una palabra legítima.

### `normalize(string $text): string`

Convierte una entrada a una forma estable sólo para comparar. El contenido original jamás se reemplaza por esta versión.

Realiza, en orden:

1. `mb_strtolower()` convierte a minúsculas sin dañar caracteres UTF-8.
2. Si la extensión PHP `intl` está disponible, `Normalizer::FORM_KD` unifica distintas representaciones Unicode, incluidas letras de ancho completo.
3. Conserva explícitamente `ñ` cuando llega como `n` más una marca Unicode de tilde.
4. Elimina marcas diacríticas para que las variantes con acento se comparen igual.
5. Si `intl` no está instalada, usa un respaldo para vocales acentuadas del español y sigue eliminando marcas Unicode.
6. Convierte sustituciones comunes de leetspeak, como `@` y números usados como letras, sólo en la representación de comparación.
7. Reduce repeticiones de una misma letra; esto cubre intentos de evadir con letras exageradamente repetidas.
8. Convierte puntuación y espacios repetidos en un único espacio.

Ejemplos conceptuales:

```text
MAYÚSCULAS y tildes        -> minusculas sin tildes
letras repetidas           -> una sola letra para comparar
separadores entre letras   -> se revisan también como una palabra unida
```

El respaldo existe porque el entorno local actual tiene `mbstring`, pero puede no tener la extensión `intl`. En producción es recomendable instalar `ext-intl` para una normalización Unicode más completa.

### `candidates(string $text): array`

Una persona puede separar letras con puntuación o espacios. Esta función prepara tres candidatos:

1. El texto normalizado normal.
2. Una versión que une puntuación entre letras, como letras separadas por puntos o guiones.
3. Una versión que une secuencias de tres o más letras individuales separadas por espacios.

No une palabras normales separadas por espacios. Esto evita transformar frases inocentes en otra palabra accidentalmente.

### `containsRoot(string $text, string $root): bool`

Divide cada candidato en palabras y busca la raíz dentro de una sola palabra. Nunca cruza límites de palabras. También exige que la raíz tenga seis o más caracteres.

## `NoProfanity`

Ubicación: `apps/services/community-service/app/Rules/NoProfanity.php`.

`NoProfanity` implementa `ValidationRule`, la interfaz de Laravel para reglas personalizadas. No guarda palabras ni normaliza por sí misma: le pide a `ContentModerationService` que decida.

El controlador puede declarar la regla junto con las demás reglas del campo:

```php
'body' => ['required', 'string', 'max:2000', new NoProfanity],
```

La línea aparentemente especial es:

```php
$moderation = $this->moderation ?? app(ContentModerationService::class);
```

`app(...)` consulta el contenedor de dependencias de Laravel y crea o reutiliza la instancia del servicio. El parámetro opcional del constructor permite inyectar un servicio falso durante pruebas unitarias sin iniciar una base de datos.

Cuando hay coincidencia, `$fail(...)` agrega un error de validación al campo actual. Laravel detiene el flujo normal y responde `422 Unprocessable Content` con el mensaje de validación.

## Uso en `CommunityController`

Ubicación: `apps/services/community-service/app/Http/Controllers/CommunityController.php`.

El controlador importa la regla con:

```php
use App\Rules\NoProfanity;
```

Después la incluye en la validación de:

```php
// post()
'body' => ['nullable', 'string', 'max:4000', new NoProfanity],

// comment()
'body' => ['required', 'string', 'max:2000', new NoProfanity],
```

`nullable` permite una publicación sin texto. Si llega un texto, la regla sí se ejecuta. El comentario sigue siendo obligatorio.

## Seeder y operación

`BlockedTermSeeder` define el vocabulario inicial. Para cada regla genera un ULID, calcula `normalized_term` con la misma función usada en producción y ejecuta `upsert` por `normalized_term`.

`upsert` es importante: permite ejecutar el seeder varias veces sin crear duplicados. Si una regla ya existe, actualiza su tipo, texto y estado activo.

Para crear la tabla y cargar el vocabulario local:

```bash
bash bin/catalogo-aves prepare community
```

Para desactivar temporalmente una regla sin borrarla:

```sql
UPDATE blocked_terms
SET is_active = false
WHERE normalized_term = 'termino normalizado';
```

Al agregar una regla nueva, su `normalized_term` debe calcularse con `ContentModerationService::normalize()`. No se deben insertar a mano valores sin normalizar.

## Pruebas

Ubicación: `apps/services/community-service/tests/Unit/ContentModerationServiceTest.php`.

Las pruebas cubren:

- Frases y raíces tras normalización.
- Sustituciones simples, puntuación entre letras y letras repetidas.
- Un falso positivo: una coincidencia no debe ocurrir dentro de una palabra legítima no relacionada.
- Acentos, Unicode compuesto y `ñ`.
- La integración de `NoProfanity` con el servicio mediante un doble de prueba.

Ejecuta las pruebas del servicio con:

```bash
cd apps/services/community-service
php artisan test tests/Unit
```

Antes de modificar vocabulario o normalización, agrega siempre un caso bloqueado y, si existe riesgo, un caso permitido.

## Límites y decisiones futuras

- El filtro de palabras no entiende contexto ni intención. No sustituye reportes, moderación humana ni revisión de apelaciones.
- El mensaje de error es genérico y no revela qué término generó el bloqueo.
- No existe todavía una interfaz administrativa ni endpoints para editar `blocked_terms`; los cambios pueden hacerse mediante seeder o una operación controlada de base de datos.
- No hay aviso en tiempo real en Angular. El servidor es la barrera obligatoria; un aviso cliente puede añadirse después como mejora de experiencia, nunca como única protección.
- Si Accounts u Observation necesitan el filtro, deben implementar su propia tabla, servicio y regla dentro de su contexto. No deben leer la base de Community. Una centralización futura requeriría un servicio de moderación explícito y una decisión arquitectónica.
