<?php

namespace App\Rules;

use App\Services\ContentModerationService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/** Reusable Laravel adapter: field validation stays declarative while matching remains in the domain service. */
final class NoProfanity implements ValidationRule
{
    public function __construct(private ?ContentModerationService $moderation = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            return;
        }

        $moderation = $this->moderation ?? app(ContentModerationService::class);
        if ($moderation->containsActiveBlockedTerm($value)) {
            $fail('El contenido contiene lenguaje no cumple con las norma de la comunidad. Reformúlalo con respeto.');
        }
    }
}
