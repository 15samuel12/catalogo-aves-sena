<?php

namespace Database\Seeders;

use App\Services\ContentModerationService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Seeds a small, high-confidence vocabulary; moderation staff may manage its active state in the database. */
class BlockedTermSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $terms = [
            ['term' => 'mierda', 'match_type' => 'ROOT'],
            ['term' => 'hijueputa', 'match_type' => 'ROOT'],
            ['term' => 'hijo de puta', 'match_type' => 'PHRASE'],
            ['term' => 'malparid', 'match_type' => 'ROOT'],
            ['term' => 'gonorrea', 'match_type' => 'ROOT'],
            ['term' => 'pendej', 'match_type' => 'ROOT'],
        ];

        $rows = array_map(static fn (array $entry) => [
            'id' => (string) Str::ulid(),
            'term' => $entry['term'],
            'normalized_term' => ContentModerationService::normalize($entry['term']),
            'match_type' => $entry['match_type'],
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ], $terms);

        DB::table('blocked_terms')->upsert(
            $rows,
            ['normalized_term'],
            ['term', 'match_type', 'is_active', 'updated_at'],
        );
    }
}
