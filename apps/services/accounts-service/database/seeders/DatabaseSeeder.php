<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/** Siembra datos repetibles de demostración sin convertirlos en cuentas de producción. */
class DatabaseSeeder extends Seeder
{
    /** Actualiza perfiles de ejemplo y, sólo localmente, el superadministrador documentado. */
    public function run(): void
    {
        $now = now();
        $people = [
            [
                'name' => 'Mariana Torres',
                'email' => 'mariana.torres@catalogo-aves.local',
                'role' => 'user',
                'location' => 'Bogotá, Colombia',
                'bio' => 'Observadora de bosque andino y aprendiz de fotografía de naturaleza.',
            ],
            [
                'name' => 'Tomás Rojas',
                'email' => 'tomas.rojas@catalogo-aves.local',
                'role' => 'user',
                'location' => 'Chingaza, Colombia',
                'bio' => 'Guía local interesado en aves altoandinas y restauración ecológica.',
            ],
            [
                'name' => 'Elena Cárdenas',
                'email' => 'elena.cardenas@catalogo-aves.local',
                'role' => 'curator',
                'location' => 'Medellín, Colombia',
                'bio' => 'Bióloga y curadora de registros para la comunidad de Catálogo de Aves.',
            ],
            [
                'name' => 'Samuel Pardo',
                'email' => 'samuel.pardo@catalogo-aves.local',
                'role' => 'moderator',
                'location' => 'Cali, Colombia',
                'bio' => 'Moderador comunitario y observador de humedales urbanos.',
            ],
            [
                'name' => 'Lucía Herrera',
                'email' => 'lucia.herrera@catalogo-aves.local',
                'role' => 'user',
                'location' => 'Manizales, Colombia',
                'bio' => 'Exploro senderos de niebla, especialmente al amanecer.',
            ],
            [
                'name' => 'David Salazar',
                'email' => 'david.salazar@catalogo-aves.local',
                'role' => 'user',
                'location' => 'Pereira, Colombia',
                'bio' => 'Aficionado a los colibríes y a registrar cantos de aves.',
            ],
            [
                'name' => 'Natalia Vélez',
                'email' => 'natalia.velez@catalogo-aves.local',
                'role' => 'user',
                'location' => 'Ibagué, Colombia',
                'bio' => 'Naturalista de fin de semana y amante de las caminatas lentas.',
            ],
            [
                'name' => 'Camilo Duarte',
                'email' => 'camilo.duarte@catalogo-aves.local',
                'role' => 'user',
                'location' => 'Tunja, Colombia',
                'bio' => 'Registro aves de páramo y participo en salidas de ciencia ciudadana.',
            ],
            [
                'name' => 'Sara Molina',
                'email' => 'sara.molina@catalogo-aves.local',
                'role' => 'user',
                'location' => 'Villa de Leyva, Colombia',
                'bio' => 'Interesada en migratorias y conservación de paisajes secos.',
            ],
            [
                'name' => 'Juan Esteban Gil',
                'email' => 'juan.gil@catalogo-aves.local',
                'role' => 'admin',
                'location' => 'Bogotá, Colombia',
                'bio' => 'Administrador de la comunidad y responsable de operaciones.',
            ],
            [
                'name' => 'Valeria Muñoz',
                'email' => 'valeria.munoz@catalogo-aves.local',
                'role' => 'user',
                'location' => 'Santander, Colombia',
                'bio' => 'Aves, ilustración naturalista y recorridos de río.',
            ],
            [
                'name' => 'Andrés Nieto',
                'email' => 'andres.nieto@catalogo-aves.local',
                'role' => 'user',
                'location' => 'Leticia, Colombia',
                'bio' => 'Observador amazónico y defensor de la observación responsable.',
            ],
        ];

        foreach ($people as $index => $personData) {
            ['name' => $name, 'email' => $email, 'role' => $role, 'location' => $location, 'bio' => $bio] = $personData;
            $id = $this->externalId($index + 1);

            /*
             * El identificador estable representa a la persona de demostración. Buscar por
             * correo O por id permite renombrar el dominio del proyecto sin que una segunda
             * ejecución intente insertar el mismo id primario con un correo nuevo.
             */
            $person = User::query()
                ->where('email', $email)
                ->orWhere('id', $id)
                ->firstOrNew();

            $person->fill([
                'id' => $id,
                'name' => $name,
                'email' => $email,
                'password' => Hash::make('CatalogoAvesPass2026!'),
                'role' => $role,
                'bio' => $bio,
                'general_location' => $location,
                'privacy_settings' => ['show_general_location' => true, 'share_activity' => true],
                'preferences' => ['language' => 'es-CO', 'units' => 'metric', 'weekly_digest' => true],
                'email_verified_at' => $now,
            ]);
            $person->save();
        }

        // Una cuenta predecible permite usar un clon local nuevo inmediatamente. Este seeder
        // de desarrollo nunca la aprovisiona fuera de APP_ENV=local; las credenciales de
        // producción deben administrarse explícitamente.
        if (app()->environment('local')) {
            $superadmin = config('catalogo_aves.local_superadmin');
            User::updateOrCreate(
                ['email' => $superadmin['email']],
                [
                    'name' => $superadmin['name'],
                    'password' => Hash::make($superadmin['password']),
                    'role' => 'superadmin',
                    'email_verified_at' => $now,
                ],
            );
        }
    }

    /** Devuelve el identificador determinista de cada perfil de demostración. */
    private function externalId(int $number): string
    {
        return '01J' . str_pad((string) $number, 23, '0', STR_PAD_LEFT);
    }
}
