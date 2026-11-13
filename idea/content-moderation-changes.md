# Cambios realizados al filtro de lenguaje no permitido

## Objetivo del cambio

El proyecto recibió como referencia un filtro basado en listas de términos escritas directamente en código. La implementación final conserva las ideas útiles de ese enfoque —normalización y validación del servidor—, pero cambia la fuente del vocabulario por una tabla administrable dentro de Community.

El objetivo es que la moderación pueda activar, desactivar o agregar reglas sin modificar listas PHP ni desplegar de nuevo la aplicación por cada cambio de vocabulario.

## Resumen arquitectónico

```text
Enfoque original del patch
  ProfanityFilter estático
  + listas EXACT_WORDS y ROOT_WORDS en PHP
  + regla NoProfanity

Implementación final
  tabla blocked_terms
  + ContentModerationService
  + regla NoProfanity
  + validación de posts y comentarios
```

La regla `NoProfanity` sigue existiendo, pero ya no decide con una lista rígida. Delega la decisión a `ContentModerationService`, que consulta los términos activos en `blocked_terms`.

## Archivos nuevos

| Archivo | Qué se creó | Por qué se creó |
| --- | --- | --- |
| `apps/services/community-service/database/migrations/2026_11_08_000100_create_blocked_terms_table.php` | Tabla `blocked_terms`. | Separar el vocabulario de moderación del código. |
| `apps/services/community-service/database/seeders/BlockedTermSeeder.php` | Datos iniciales de vocabulario. | Contar con reglas de ejemplo repetibles en desarrollo. |
| `apps/services/community-service/app/Services/ContentModerationService.php` | Servicio que normaliza y compara texto. | Mantener la lógica de moderación fuera del controlador y de la regla HTTP. |
| `apps/services/community-service/app/Rules/NoProfanity.php` | Regla de validación Laravel reutilizable. | Poder proteger cualquier campo con `new NoProfanity`. |
| `apps/services/community-service/tests/Unit/ContentModerationServiceTest.php` | Pruebas unitarias. | Evitar que cambios futuros rompan detección, normalización o falsos positivos. |
| `docs/content-moderation-guide.md` | Guía operativa y técnica completa. | Explicar funcionamiento, operación y límites del filtro. |

## Archivos modificados

| Archivo | Cambio de código | Razón lógica |
| --- | --- | --- |
| `CommunityController.php` | Se añadió `new NoProfanity` a `posts.body` y `comments.body`. | Validar antes de insertar una publicación o comentario. |
| `DatabaseSeeder.php` | Ejecuta `BlockedTermSeeder`. | Preparar Community carga también las reglas de moderación. |
| `contracts/openapi/community.yaml` | Documenta respuesta `422` en posts y comentarios. | El contrato HTTP debe reflejar que contenido no permitido puede rechazarse. |
| `docs/codebase-guide.md` | Describe la nueva responsabilidad de `ContentModerationService`. | Mantener actualizada la guía de lectura del proyecto. |
| `README.md` | Enlaza la guía del filtro. | Facilitar el descubrimiento de la documentación. |

## Cambios principales de código

### 1. De listas fijas a una tabla configurable

El patch original tenía estructuras como estas:

```php
private const EXACT_WORDS = [...];
private const ROOT_WORDS = [...];
```

Eso significa que agregar, corregir o desactivar un término exige editar PHP, ejecutar pruebas y desplegar el servicio.

La implementación final guarda cada regla en `blocked_terms`:

| Columna | Función |
| --- | --- |
| `term` | Regla administrada por moderación. |
| `normalized_term` | Forma lista para comparar. |
| `match_type` | Define `EXACT`, `PHRASE` o `ROOT`. |
| `is_active` | Habilita o deshabilita la regla sin eliminarla. |

`ContentModerationService::activeTerms()` consulta únicamente reglas activas. Esta decisión evita que la lista en código y la lista en base de datos se contradigan.

### 2. `ContentModerationService` reemplaza el filtro estático

El patch original usaba una clase estática parecida a esta:

```php
ProfanityFilter::contains($value)
```

La versión actual usa un servicio:

```php
$moderation->containsActiveBlockedTerm($value)
```

Esto se hizo por estas razones:

- El servicio puede leer la base de datos de Community.
- Las pruebas pueden pasar un vocabulario explícito sin crear tablas.
- La lógica no queda atada a constantes de PHP.
- La regla Laravel y futuros casos de uso comparten el mismo comportamiento.

### 3. Separación entre política de contenido y validación HTTP

`ContentModerationService` responde una pregunta de dominio:

```text
¿Este texto coincide con una regla activa?
```

`NoProfanity` responde una pregunta de transporte HTTP:

```text
¿Debe Laravel aceptar este valor durante la validación del request?
```

Por eso `NoProfanity` no contiene listas ni algoritmos de normalización. Sólo llama al servicio y usa `$fail(...)` si se detecta una coincidencia. Laravel transforma ese fallo en una respuesta `422` y el controlador no continúa hacia la inserción.

### 4. Tipos de coincidencia en datos, no en constantes

La lógica original tenía dos listas separadas: una de palabras completas y otra de raíces. La implementación final expresa esa decisión en `match_type`:

| Tipo | Implementación | Motivo |
| --- | --- | --- |
| `EXACT` | Busca el término completo rodeado por límites de palabra. | Evitar coincidencias accidentales dentro de otra palabra. |
| `PHRASE` | Busca varias palabras consecutivas. | Bloquear expresiones compuestas. |
| `ROOT` | Busca una raíz dentro de un único token. | Detectar variantes derivadas sin cruzar palabras. |

`containsRoot()` exige raíces de seis o más caracteres. Esa restricción reduce falsos positivos por raíces demasiado cortas. Una regla `ROOT` debe revisarse cuidadosamente antes de activarla.

### 5. Normalización Unicode y de evasiones comunes

`normalize()` genera una representación temporal. El texto original aprobado se conserva tal como lo escribió la persona.

La función hace lo siguiente:

1. Convierte a minúsculas UTF-8 con `mb_strtolower()`.
2. Si existe `ext-intl`, utiliza `Normalizer::FORM_KD` para unificar variantes Unicode.
3. Conserva la `ñ` si llega como `n` más una tilde Unicode separada.
4. Quita tildes y marcas diacríticas para comparar variantes equivalentes.
5. Usa un respaldo de vocales españolas si el entorno no tiene `intl`.
6. Sustituye formas comunes de leetspeak, como símbolos o números usados como letras, sólo en la copia de comparación.
7. Reduce repeticiones de letras para impedir una evasión mediante letras exageradamente repetidas.
8. Convierte puntuación y espacios consecutivos en un espacio único.

El patch original incluía una tabla grande de mapeos llamada `ACCENTS`, además de homoglifos griegos y cirílicos. No se copió completa porque aumenta la complejidad y los posibles falsos positivos. `Normalizer` cubre de forma más mantenible las variaciones Unicode habituales; la detección de homoglifos avanzados se puede añadir después con pruebas específicas si llega a ser necesaria.

### 6. Candidatos adicionales para texto ofuscado

Además del texto normalizado normal, `candidates()` construye versiones adicionales:

- Una versión que une puntuación entre letras.
- Una versión que une secuencias de tres o más letras individuales separadas por espacios.

Esto cubre evasiones simples sin unir palabras normales de una frase. La decisión evita convertir texto inocente con espacios en una palabra distinta por accidente.

### 7. Detección sin revelar el término

El servicio devuelve sólo `true` o `false`. No retorna la palabra ni la raíz que produjo el bloqueo.

La respuesta al usuario es genérica:

```text
El contenido contiene lenguaje no permitido. Reformúlalo con respeto.
```

No revelar el término exacto reduce información para una persona que quiera probar variaciones hasta evadir el filtro.

## Cambios que no se implementaron del patch original

| Elemento del patch | Motivo de no incluirlo ahora |
| --- | --- |
| Listas PHP y TypeScript duplicadas en cada servicio | Crean varias fuentes de verdad y exigen despliegue para cada cambio de vocabulario. |
| Validación Angular en tiempo real | Mejora experiencia, pero no ofrece seguridad; el backend ya es la barrera obligatoria. |
| Registro de intentos bloqueados | Es útil para auditoría, pero necesita definir retención y monitoreo antes de almacenar metadatos. |
| Límite de reincidencias con `RateLimiter` | Es una protección útil contra pruebas automatizadas, pero requiere decidir almacenamiento de caché y política de bloqueo. |
| Mapeo extenso de homoglifos extranjeros | Debe añadirse sólo con pruebas que demuestren que no bloquea nombres, lugares o términos legítimos. |

## Pruebas añadidas

`ContentModerationServiceTest` cubre:

- Una frase bloqueada con números usados como letras.
- Una raíz bloqueada escrita con puntos y letras repetidas.
- Un caso permitido donde una coincidencia corta está contenida en una palabra no relacionada.
- Tildes y `ñ` en forma Unicode compuesta y descompuesta.
- El uso de `NoProfanity` cuando el servicio detecta una coincidencia.

Las pruebas se ejecutan con:

```bash
cd apps/services/community-service
php artisan test tests/Unit
```

## Consecuencia para futuros módulos

El filtro actual pertenece a Community. Si Accounts necesita validar el nombre de registro, no debe consultar `catalogo_aves_community` directamente.

La alternativa inmediata es repetir el patrón dentro de Accounts:

```text
accounts-service
  -> blocked_terms propia
  -> ContentModerationService propio
  -> NoProfanity propio
```

Una lista única para todos los microservicios requeriría una decisión posterior: crear un servicio de moderación independiente con API, disponibilidad, autorización y auditoría propias.

## Referencias

- [Guía técnica y operativa](content-moderation-guide.md).
- [Arquitectura del proyecto](architecture.md).
- [Límites entre servicios](service-boundaries.md).
