<?php

namespace Tests\Unit;

use App\Rules\NoProfanity;
use App\Services\ContentModerationService;
use PHPUnit\Framework\TestCase;

class ContentModerationServiceTest extends TestCase
{
    private ContentModerationService $moderation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->moderation = new ContentModerationService;
    }

    public function test_detects_exact_phrase_and_root_terms_after_normalization(): void
    {
        $terms = [
            ['normalized_term' => 'hijo de puta', 'match_type' => 'PHRASE'],
            ['normalized_term' => 'mierda', 'match_type' => 'ROOT'],
        ];

        $this->assertTrue($this->moderation->containsBlockedTerm('Ese comentario es un h1j0 d3 put4.', $terms));
        $this->assertTrue($this->moderation->containsBlockedTerm('M.i.e.e.e.r.d.a', $terms));
    }

    public function test_does_not_match_a_term_inside_an_unrelated_word(): void
    {
        $terms = [['normalized_term' => 'puta', 'match_type' => 'EXACT']];

        $this->assertFalse($this->moderation->containsBlockedTerm('La computadora registra la observación.', $terms));
    }

    public function test_normalization_removes_accents_and_preserves_enye(): void
    {
        $this->assertSame('arbol cañon', ContentModerationService::normalize("Árbol can\u{0303}ón"));
    }

    public function test_validation_rule_returns_a_field_error_when_the_service_blocks_text(): void
    {
        $service = $this->createMock(ContentModerationService::class);
        $service->expects($this->once())
            ->method('containsActiveBlockedTerm')
            ->with('texto de prueba')
            ->willReturn(true);

        $errors = [];
        (new NoProfanity($service))->validate(
            'body',
            'texto de prueba',
            static function (string $message) use (&$errors): void {
                $errors[] = $message;
            },
        );

        $this->assertSame(['El contenido contiene lenguaje no cumple con las norma de la comunidad. Reformúlalo con respeto.'], $errors);
    }
}
