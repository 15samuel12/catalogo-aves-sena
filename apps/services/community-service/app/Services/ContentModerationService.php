<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Applies Community's configurable vocabulary without persisting or returning the submitted text.
 * It deliberately compares complete terms by default; ROOT matching is an explicit, reviewed exception.
 */
class ContentModerationService
{
    /** Returns true when text matches at least one active Community moderation term. */
    public function containsActiveBlockedTerm(string $text): bool
    {
        return $this->containsBlockedTerm($text, $this->activeTerms());
    }

    /**
     * Tests text against an explicit vocabulary. This keeps matching deterministic and unit-testable.
     *
     * @param  iterable<array{normalized_term: string, match_type?: string}>  $terms
     */
    public function containsBlockedTerm(string $text, iterable $terms): bool
    {
        $candidates = self::candidates($text);
        if ($candidates === []) {
            return false;
        }

        foreach ($terms as $term) {
            $normalizedTerm = self::normalize($term['normalized_term'] ?? '');
            if ($normalizedTerm === '') {
                continue;
            }

            $matchType = $term['match_type'] ?? 'EXACT';
            foreach ($candidates as $candidate) {
                if ($matchType === 'ROOT') {
                    if (self::containsRoot($candidate, $normalizedTerm)) {
                        return true;
                    }

                    continue;
                }

                if (str_contains(' '.$candidate.' ', ' '.$normalizedTerm.' ')) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Builds a stable comparison string: lowercase, compatible Unicode, accents removed and n-tilde retained.
     * The fallback supports the Spanish diacritics required by the service when ext-intl is unavailable.
     */
    public static function normalize(string $text): string
    {
        $text = mb_strtolower(trim($text), 'UTF-8');
        if ($text === '') {
            return '';
        }

        if (class_exists(\Normalizer::class)) {
            $text = \Normalizer::normalize($text, \Normalizer::FORM_KD) ?: $text;
        } else {
            $text = strtr($text, [
                'á' => 'a', 'à' => 'a', 'ä' => 'a', 'â' => 'a', 'ã' => 'a',
                'é' => 'e', 'è' => 'e', 'ë' => 'e', 'ê' => 'e',
                'í' => 'i', 'ì' => 'i', 'ï' => 'i', 'î' => 'i',
                'ó' => 'o', 'ò' => 'o', 'ö' => 'o', 'ô' => 'o', 'õ' => 'o',
                'ú' => 'u', 'ù' => 'u', 'ü' => 'u', 'û' => 'u',
            ]);
        }

        // Restore n + combining tilde before generic combining marks are removed.
        $text = str_replace("n\u{0303}", 'ñ', $text);
        $text = preg_replace('/\p{Mn}+/u', '', $text) ?? $text;

        // Common leetspeak substitutions are safe only as a comparison representation; original text is unchanged.
        $text = strtr($text, ['@' => 'a', '$' => 's', '0' => 'o', '1' => 'i', '3' => 'e', '4' => 'a', '5' => 's', '7' => 't']);
        $text = preg_replace('/(\p{L})\1+/u', '$1', $text) ?? $text;
        $text = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $text) ?? '';

        return trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
    }

    /** @return list<array{normalized_term: string, match_type: string}> */
    private function activeTerms(): array
    {
        return DB::table('blocked_terms')
            ->where('is_active', true)
            ->orderByDesc('normalized_term')
            ->get(['normalized_term', 'match_type'])
            ->map(static fn (object $term) => [
                'normalized_term' => $term->normalized_term,
                'match_type' => $term->match_type,
            ])
            ->all();
    }

    /** @return list<string> */
    private static function candidates(string $text): array
    {
        $base = self::normalize($text);
        if ($base === '') {
            return [];
        }

        // Also inspect obfuscation such as "m.i.e.r.d.a" without joining ordinary whitespace-separated words.
        $joinedPunctuation = preg_replace('/(?<=\p{L})[.\-_·\'’|\\/,]+(?=\p{L})/u', '', $text) ?? $text;
        $joined = self::normalize($joinedPunctuation);
        $singleLetterRuns = self::joinSingleLetterRuns($base);

        return array_values(array_unique([$base, $joined, $singleLetterRuns]));
    }

    private static function containsRoot(string $text, string $root): bool
    {
        // ROOT rules are seeded only for long, reviewed terms; match within one token, never across words.
        if (mb_strlen($root, 'UTF-8') < 6) {
            return false;
        }

        foreach (explode(' ', $text) as $token) {
            if (str_contains($token, $root)) {
                return true;
            }
        }

        return false;
    }

    private static function joinSingleLetterRuns(string $text): string
    {
        $result = [];
        $run = [];

        foreach ([...explode(' ', $text), ''] as $token) {
            if (mb_strlen($token, 'UTF-8') === 1) {
                $run[] = $token;

                continue;
            }

            if (count($run) >= 3) {
                $result[] = implode('', $run);
            }
            $run = [];
        }

        return $result === [] ? $text : $text.' '.implode(' ', $result);
    }
}
