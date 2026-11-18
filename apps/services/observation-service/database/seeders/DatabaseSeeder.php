<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $species = [
            [
                'id' => $this->externalId(201),
                'name' => 'Bichofué',
                'sensitivity' => 'APPROXIMATE',
            ],
            [
                'id' => $this->externalId(202),
                'name' => 'Sirirí Común',
                'sensitivity' => 'APPROXIMATE',
            ],
            [
                'id' => $this->externalId(203),
                'name' => 'Canario Costeño',
                'sensitivity' => 'APPROXIMATE',
            ],
            [
                'id' => $this->externalId(204),
                'name' => 'Mirla Ollera',
                'sensitivity' => 'APPROXIMATE',
            ],
            [
                'id' => $this->externalId(205),
                'name' => 'Eufonia Gorgiamarilla',
                'sensitivity' => 'APPROXIMATE',
            ],
            [
                'id' => $this->externalId(206),
                'name' => 'Tangara Rastrojera',
                'sensitivity' => 'APPROXIMATE',
            ],
            [
                'id' => $this->externalId(207),
                'name' => 'Azulejo Común',
                'sensitivity' => 'APPROXIMATE',
            ],
            [
                'id' => $this->externalId(208),
                'name' => 'Carpintero Habado',
                'sensitivity' => 'APPROXIMATE',
            ],
            [
                'id' => $this->externalId(209),
                'name' => 'Píngara',
                'sensitivity' => 'APPROXIMATE',
            ],
            [
                'id' => $this->externalId(210),
                'name' => 'Tortolita Rojiza',
                'sensitivity' => 'APPROXIMATE',
            ],
            [
                'id' => $this->externalId(211),
                'name' => 'Titiribí Pechirrojo',
                'sensitivity' => 'APPROXIMATE',
            ],
            [
                'id' => $this->externalId(212),
                'name' => 'Amazilia Colirrufa',
                'sensitivity' => 'APPROXIMATE',
            ],
            [
                'id' => $this->externalId(213),
                'name' => 'Lora Real',
                'sensitivity' => 'APPROXIMATE',
            ],
            [
                'id' => $this->externalId(214),
                'name' => 'Gallinazo Negro',
                'sensitivity' => 'APPROXIMATE',
            ],
            [
                'id' => $this->externalId(215),
                'name' => 'Periquito de Anteojos',
                'sensitivity' => 'APPROXIMATE',
            ],
            [
                'id' => $this->externalId(216),
                'name' => 'Batará Carcajada',
                'sensitivity' => 'APPROXIMATE',
            ],
            [
                'id' => $this->externalId(217),
                'name' => 'Pigua',
                'sensitivity' => 'APPROXIMATE',
            ],
            [
                'id' => $this->externalId(218),
                'name' => 'Cucú Ardilla',
                'sensitivity' => 'APPROXIMATE',
            ],
        ];
        $behaviors = ['Forrajeando en el dosel', 'Vocalizando desde una percha', 'En pareja', 'En grupo pequeño', 'Alimentándose de frutos', 'En vuelo bajo', 'Explorando vegetación densa', 'Descansando en una rama expuesta'];
        $regions = ['Bosque andino', 'Humedal urbano', 'Borde de reserva', 'Sendero de niebla', 'Parque arbolado', 'Quebrada de montaña'];

        foreach (range(0, 59) as $index) {
            $speciesData = $species[$index % count($species)];
            $latitude = 4.58 + (($index * 37) % 91) / 1000;
            $longitude = -74.16 + (($index * 53) % 119) / 1000;
            $status = $index % 13 === 0 ? 'NEEDS_IDENTIFICATION' : ($index % 17 === 0 ? 'VERIFIED' : 'PUBLISHED');
            $publicLat = round($latitude / 0.05) * 0.05;
            $publicLng = round($longitude / 0.05) * 0.05;

            DB::table('sightings')->updateOrInsert(
                ['id' => $this->externalId(5001 + $index)],
                [
                    'user_id' => $this->externalId(1 + ($index % 12)),
                    'species_id' => $speciesData['id'],
                    'observed_at' => $now->copy()->subDays(($index * 3) % 120)->subMinutes(($index * 19) % 360),
                    'individuals' => 1 + ($index % 7),
                    'behavior' => $behaviors[$index % count($behaviors)],
                    'notes' => $speciesData['name'] . ' registrada durante un recorrido de observación. Se mantuvo distancia y no se alteró el comportamiento del ave.',
                    'status' => $status,
                    'sensitivity' => $speciesData['sensitivity'],
                    'private_lat' => $latitude,
                    'private_lng' => $longitude,
                    'public_lat' => $publicLat,
                    'public_lng' => $publicLng,
                    'public_region' => null,
                    'created_at' => $now->copy()->subDays(($index * 3) % 120),
                    'updated_at' => $now,
                ],
            );
        }

        $this->seedPlanningData($now);
    }

    /** Crea lugares y rutas reproducibles para que la interfaz sea utilizable tras prepare. */
    private function seedPlanningData($now): void
    {
        $places = [
            [
                'name' => 'Humedal de la sabana',
                'municipality' => 'Bogotá',
                'department' => 'Bogotá D.C.',
                'habitat' => 'Humedal urbano',
                'description' => 'Senderos tranquilos para observar garzas, patos y aves de borde.',
                'latitude' => 4.6658,
                'longitude' => -74.1056,
            ],
            [
                'name' => 'Bosque alto de Chingaza',
                'municipality' => 'La Calera',
                'department' => 'Cundinamarca',
                'habitat' => 'Bosque andino',
                'description' => 'Bosque de niebla con miradores y vegetación de montaña.',
                'latitude' => 4.7026,
                'longitude' => -73.8441,
            ],
            [
                'name' => 'Parque arbolado del sur',
                'municipality' => 'Bogotá',
                'department' => 'Bogotá D.C.',
                'habitat' => 'Parque urbano',
                'description' => 'Un espacio accesible para empezar a reconocer aves comunes.',
                'latitude' => 4.6047,
                'longitude' => -74.1012,
            ],
        ];

        foreach ($places as $index => $place) {
            DB::table('places')->updateOrInsert(
                ['id' => $this->externalId(8001 + $index)],
                $place + ['is_public' => true, 'updated_at' => $now, 'created_at' => $now],
            );
        }

        $routes = [
            [
                'place_id' => $this->externalId(8001),
                'name' => 'Mañana de humedal',
                'difficulty' => 'Fácil',
                'duration_minutes' => 90,
                'distance_km' => 2.40,
                'description' => 'Un circuito corto y llano para las primeras horas del día.',
                'start_latitude' => 4.6658,
                'start_longitude' => -74.1056,
            ],
            [
                'place_id' => $this->externalId(8002),
                'name' => 'Sendero del bosque alto',
                'difficulty' => 'Moderada',
                'duration_minutes' => 180,
                'distance_km' => 4.80,
                'description' => 'Recorrido de subida suave para reconocer estratos y cantos.',
                'start_latitude' => 4.7026,
                'start_longitude' => -73.8441,
            ],
            [
                'place_id' => $this->externalId(8003),
                'name' => 'Vuelta de parque',
                'difficulty' => 'Fácil',
                'duration_minutes' => 60,
                'distance_km' => 1.70,
                'description' => 'Ruta urbana breve, ideal para iniciar una lista personal.',
                'start_latitude' => 4.6047,
                'start_longitude' => -74.1012,
            ],
        ];

        foreach ($routes as $index => $route) {
            DB::table('birding_routes')->updateOrInsert(
                ['id' => $this->externalId(8101 + $index)],
                $route + ['is_public' => true, 'updated_at' => $now, 'created_at' => $now],
            );
        }
    }

    private function externalId(int $number): string
    {
        return '01J' . str_pad((string) $number, 23, '0', STR_PAD_LEFT);
    }
}
