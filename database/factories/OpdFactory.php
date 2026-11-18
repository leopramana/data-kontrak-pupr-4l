<?php

namespace Database\Factories;

use App\Models\Opd;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Opd>
 */
class OpdFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nama' => 'Dinas '.fake()->unique()->word(),
            'singkatan' => strtoupper(fake()->unique()->lexify('???')),
        ];
    }
}
