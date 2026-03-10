<?php

namespace Database\Seeders;

use App\Models\Seguradora;
use Illuminate\Database\Seeder;

class SeguradoraSeeder extends Seeder
{
    public function run(): void
    {
        $seguradoras = [
            [
                'name' => 'Bradesco Seguros',
                'system_url' => 'https://wwws.bradescoseguros.com.br',
                'prompt_instructions' => 'Acessar o portal Bradesco Seguros e realizar cotação de seguro auto.',
                'is_active' => true,
            ],
            [
                'name' => 'Porto Seguro',
                'system_url' => 'https://www.portoseguro.com.br/corretor',
                'prompt_instructions' => 'Acessar o portal do corretor Porto Seguro e realizar cotação.',
                'is_active' => true,
            ],
            [
                'name' => 'Tokio Marine',
                'system_url' => 'https://www.tokiomarine.com.br/corretor',
                'prompt_instructions' => 'Acessar o portal Tokio Marine para corretor e realizar cotação.',
                'is_active' => true,
            ],
        ];

        foreach ($seguradoras as $seguradora) {
            Seguradora::firstOrCreate(
                ['name' => $seguradora['name']],
                $seguradora
            );
        }
    }
}
