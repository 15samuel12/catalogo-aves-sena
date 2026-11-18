<?php

namespace Database\Seeders;

use App\Services\ContentModerationService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Seeds Community's reviewed vocabulary. Existing database terms are preserved:
 * this seeder adds or updates only the entries declared below.
 */
class BlockedTermSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $terms = [
            [
                'term' => 'pirobo',
                'match_type' => 'EXACT',
                'is_active' => true,
            ],
            [
                'term' => 'piroba',
                'match_type' => 'EXACT',
                'is_active' => true,
            ],
            [
                'term' => 'jueputa',
                'match_type' => 'EXACT',
                'is_active' => true,
            ],
            [
                'term' => 'juepuchas',
                'match_type' => 'EXACT',
                'is_active' => true,
            ],
            [
                'term' => 'juepucha',
                'match_type' => 'EXACT',
                'is_active' => true,
            ],
            [
                'term' => 'jueputas',
                'match_type' => 'EXACT',
                'is_active' => true,
            ],
            [
                'term' => 'malparido',
                'match_type' => 'EXACT',
                'is_active' => true,
            ],
            [
                'term' => 'malparida',
                'match_type' => 'EXACT',
                'is_active' => true,
            ],
            [
                'term' => 'malparidos',
                'match_type' => 'EXACT',
                'is_active' => true,
            ],
            [
                'term' => 'mierda',
                'match_type' => 'EXACT',
                'is_active' => true,
            ],
            [
                'term' => 'mierdas',
                'match_type' => 'EXACT',
                'is_active' => true,
            ],
            [
                'term' => 'puta',
                'match_type' => 'EXACT',
                'is_active' => true,
            ],
            [
                'term' => 'putas',
                'match_type' => 'EXACT',
                'is_active' => true,
            ],
            [
                'term' => 'puto',
                'match_type' => 'EXACT',
                'is_active' => true,
            ],
            [
                'term' => 'putos',
                'match_type' => 'EXACT',
                'is_active' => true,
            ],
            [
                'term' => 'perra',
                'match_type' => 'EXACT',
                'is_active' => true,
            ],
            [
                'term' => 'perras',
                'match_type' => 'EXACT',
                'is_active' => true,
            ],
            [
                'term' => 'cabron',
                'match_type' => 'EXACT',
                'is_active' => true,
            ],
            [
                'term' => 'cabrona',
                'match_type' => 'EXACT',
                'is_active' => true,
            ],
            [
                'term' => 'cabrones',
                'match_type' => 'EXACT',
                'is_active' => true,
            ],
            [
                'term' => 'gonorrea',
                'match_type' => 'EXACT',
                'is_active' => true,
            ],
            [
                'term' => 'culero',
                'match_type' => 'EXACT',
                'is_active' => true,
            ],
            [
                'term' => 'culera',
                'match_type' => 'EXACT',
                'is_active' => true,
            ],
            [
                'term' => 'marica',
                'match_type' => 'EXACT',
                'is_active' => true,
            ],
            [
                'term' => 'maricon',
                'match_type' => 'EXACT',
                'is_active' => true,
            ],
            [
                'term' => 'maricones',
                'match_type' => 'EXACT',
                'is_active' => true,
            ],
            [
                'term' => 'carajo',
                'match_type' => 'EXACT',
                'is_active' => true,
            ],
            [
                'term' => 'carajos',
                'match_type' => 'EXACT',
                'is_active' => true,
            ],
            [
                'term' => 'verga',
                'match_type' => 'EXACT',
                'is_active' => true,
            ],
            [
                'term' => 'vergas',
                'match_type' => 'EXACT',
                'is_active' => true,
            ],
            [
                'term' => 'pendejo',
                'match_type' => 'EXACT',
                'is_active' => true,
            ],
            [
                'term' => 'pendeja',
                'match_type' => 'EXACT',
                'is_active' => true,
            ],
            [
                'term' => 'pendejos',
                'match_type' => 'EXACT',
                'is_active' => true,
            ],
            [
                'term' => 'pendejas',
                'match_type' => 'EXACT',
                'is_active' => true,
            ],
            [
                'term' => 'gueva',
                'match_type' => 'EXACT',
                'is_active' => true,
            ],
            [
                'term' => 'guevas',
                'match_type' => 'EXACT',
                'is_active' => true,
            ],
            [
                'term' => 'guevon',
                'match_type' => 'EXACT',
                'is_active' => true,
            ],
            [
                'term' => 'boludo',
                'match_type' => 'EXACT',
                'is_active' => true,
            ],
            [
                'term' => 'boludos',
                'match_type' => 'EXACT',
                'is_active' => true,
            ],
            [
                'term' => 'gilipollas',
                'match_type' => 'EXACT',
                'is_active' => true,
            ],
            [
                'term' => 'chimbo',
                'match_type' => 'EXACT',
                'is_active' => true,
            ],
            [
                'term' => 'chimbos',
                'match_type' => 'EXACT',
                'is_active' => true,
            ],
            [
                'term' => 'pinche',
                'match_type' => 'EXACT',
                'is_active' => true,
            ],
            [
                'term' => 'pinches',
                'match_type' => 'EXACT',
                'is_active' => true,
            ],
            [
                'term' => 'mamon',
                'match_type' => 'EXACT',
                'is_active' => true,
            ],
            [
                'term' => 'mamona',
                'match_type' => 'EXACT',
                'is_active' => true,
            ],
            [
                'term' => 'mamones',
                'match_type' => 'EXACT',
                'is_active' => true,
            ],
            [
                'term' => 'conchetumare',
                'match_type' => 'EXACT',
                'is_active' => true,
            ],
            [
                'term' => 'hdlgp',
                'match_type' => 'EXACT',
                'is_active' => true,
            ],
            [
                'term' => 'hpta',
                'match_type' => 'EXACT',
                'is_active' => true,
            ],
            [
                'term' => 'hp',
                'match_type' => 'EXACT',
                'is_active' => true,
            ],
            [
                'term' => 'hdp',
                'match_type' => 'EXACT',
                'is_active' => true,
            ],
            [
                'term' => 'ptm',
                'match_type' => 'EXACT',
                'is_active' => true,
            ],
            [
                'term' => 'mrd',
                'match_type' => 'EXACT',
                'is_active' => true,
            ],
            [
                'term' => 'ctm',
                'match_type' => 'EXACT',
                'is_active' => true,
            ],
            [
                'term' => 'csm',
                'match_type' => 'EXACT',
                'is_active' => true,
            ],
            [
                'term' => 'hijo de puta',
                'match_type' => 'PHRASE',
                'is_active' => true,
            ],
            [
                'term' => 'hijos de puta',
                'match_type' => 'PHRASE',
                'is_active' => true,
            ],
            [
                'term' => 'hijo de perra',
                'match_type' => 'PHRASE',
                'is_active' => true,
            ],
            [
                'term' => 'hijo de la gran puta',
                'match_type' => 'PHRASE',
                'is_active' => true,
            ],
            [
                'term' => 'puta madre',
                'match_type' => 'PHRASE',
                'is_active' => true,
            ],
            [
                'term' => 'concha su madre',
                'match_type' => 'PHRASE',
                'is_active' => true,
            ],
            [
                'term' => 'concha tu madre',
                'match_type' => 'PHRASE',
                'is_active' => true,
            ],
            [
                'term' => 'jueput',
                'match_type' => 'ROOT',
                'is_active' => true,
            ],
            [
                'term' => 'malparid',
                'match_type' => 'ROOT',
                'is_active' => true,
            ],
            [
                'term' => 'pendej',
                'match_type' => 'ROOT',
                'is_active' => true,
            ],
            [
                'term' => 'gonorre',
                'match_type' => 'ROOT',
                'is_active' => true,
            ],
            [
                'term' => 'conchetumar',
                'match_type' => 'ROOT',
                'is_active' => true,
            ],
            [
                'term' => 'fuck',
                'match_type' => 'EXACT',
                'is_active' => true,
            ],
            [
                'term' => 'fucks',
                'match_type' => 'EXACT',
                'is_active' => true,
            ],
            [
                'term' => 'fucked',
                'match_type' => 'EXACT',
                'is_active' => true,
            ],
            [
                'term' => 'fucker',
                'match_type' => 'EXACT',
                'is_active' => true,
            ],
            [
                'term' => 'fuckers',
                'match_type' => 'EXACT',
                'is_active' => true,
            ],
            [
                'term' => 'fucking',
                'match_type' => 'EXACT',
                'is_active' => true,
            ],
            [
                'term' => 'shit',
                'match_type' => 'EXACT',
                'is_active' => true,
            ],
            [
                'term' => 'shits',
                'match_type' => 'EXACT',
                'is_active' => true,
            ],
            [
                'term' => 'shitty',
                'match_type' => 'EXACT',
                'is_active' => true,
            ],
            [
                'term' => 'bullshit',
                'match_type' => 'EXACT',
                'is_active' => true,
            ],
            [
                'term' => 'bitch',
                'match_type' => 'EXACT',
                'is_active' => true,
            ],
            [
                'term' => 'bitches',
                'match_type' => 'EXACT',
                'is_active' => true,
            ],
            [
                'term' => 'bastard',
                'match_type' => 'EXACT',
                'is_active' => true,
            ],
            [
                'term' => 'bastards',
                'match_type' => 'EXACT',
                'is_active' => true,
            ],
            [
                'term' => 'asshole',
                'match_type' => 'EXACT',
                'is_active' => true,
            ],
            [
                'term' => 'assholes',
                'match_type' => 'EXACT',
                'is_active' => true,
            ],
            [
                'term' => 'dick',
                'match_type' => 'EXACT',
                'is_active' => true,
            ],
            [
                'term' => 'dicks',
                'match_type' => 'EXACT',
                'is_active' => true,
            ],
            [
                'term' => 'pussy',
                'match_type' => 'EXACT',
                'is_active' => true,
            ],
            [
                'term' => 'pussies',
                'match_type' => 'EXACT',
                'is_active' => true,
            ],
            [
                'term' => 'cunt',
                'match_type' => 'EXACT',
                'is_active' => true,
            ],
            [
                'term' => 'cunts',
                'match_type' => 'EXACT',
                'is_active' => true,
            ],
            [
                'term' => 'whore',
                'match_type' => 'EXACT',
                'is_active' => true,
            ],
            [
                'term' => 'whores',
                'match_type' => 'EXACT',
                'is_active' => true,
            ],
            [
                'term' => 'slut',
                'match_type' => 'EXACT',
                'is_active' => true,
            ],
            [
                'term' => 'sluts',
                'match_type' => 'EXACT',
                'is_active' => true,
            ],
            [
                'term' => 'motherfucker',
                'match_type' => 'EXACT',
                'is_active' => true,
            ],
            [
                'term' => 'motherfuckers',
                'match_type' => 'EXACT',
                'is_active' => true,
            ],
            [
                'term' => 'dickhead',
                'match_type' => 'EXACT',
                'is_active' => true,
            ],
            [
                'term' => 'retard',
                'match_type' => 'EXACT',
                'is_active' => true,
            ],
            [
                'term' => 'retarded',
                'match_type' => 'EXACT',
                'is_active' => true,
            ],
            [
                'term' => 'son of a bitch',
                'match_type' => 'PHRASE',
                'is_active' => true,
            ],
            [
                'term' => 'piece of shit',
                'match_type' => 'PHRASE',
                'is_active' => true,
            ],
            [
                'term' => 'go fuck yourself',
                'match_type' => 'PHRASE',
                'is_active' => true,
            ],
            [
                'term' => 'fuck off',
                'match_type' => 'PHRASE',
                'is_active' => true,
            ],
            [
                'term' => 'kiss my ass',
                'match_type' => 'PHRASE',
                'is_active' => true,
            ],
            [
                'term' => 'fuck you',
                'match_type' => 'PHRASE',
                'is_active' => true,
            ],
            [
                'term' => 'eat shit',
                'match_type' => 'PHRASE',
                'is_active' => true,
            ],
            [
                'term' => 'wtf',
                'match_type' => 'EXACT',
                'is_active' => true,
            ],
            [
                'term' => 'stfu',
                'match_type' => 'EXACT',
                'is_active' => true,
            ],
            [
                'term' => 'gtfo',
                'match_type' => 'EXACT',
                'is_active' => true,
            ],
            [
                'term' => 'fk',
                'match_type' => 'EXACT',
                'is_active' => true,
            ],
            [
                'term' => 'fck',
                'match_type' => 'EXACT',
                'is_active' => true,
            ],
            [
                'term' => 'fuk',
                'match_type' => 'EXACT',
                'is_active' => true,
            ],
            [
                'term' => 'fuc',
                'match_type' => 'EXACT',
                'is_active' => true,
            ],
            [
                'term' => 'fuq',
                'match_type' => 'EXACT',
                'is_active' => true,
            ],
            [
                'term' => 'sht',
                'match_type' => 'EXACT',
                'is_active' => true,
            ],
            [
                'term' => 'btch',
                'match_type' => 'EXACT',
                'is_active' => true,
            ],
            [
                'term' => 'mf',
                'match_type' => 'EXACT',
                'is_active' => true,
            ],
            [
                'term' => 'mofo',
                'match_type' => 'EXACT',
                'is_active' => true,
            ],
            [
                'term' => 'dafuq',
                'match_type' => 'EXACT',
                'is_active' => true,
            ],
            [
                'term' => 'wth',
                'match_type' => 'EXACT',
                'is_active' => true,
            ],
            [
                'term' => 'motherfuck',
                'match_type' => 'ROOT',
                'is_active' => true,
            ],
            [
                'term' => 'hijueputa',
                'match_type' => 'EXACT',
                'is_active' => true,
            ],
        ];

        $rows = array_map(static fn (array $entry) => [
            'id' => (string) Str::ulid(),
            'term' => $entry['term'],
            'normalized_term' => ContentModerationService::normalize($entry['term']),
            'match_type' => $entry['match_type'],
            'is_active' => $entry['is_active'],
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
