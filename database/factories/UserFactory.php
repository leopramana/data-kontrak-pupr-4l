<?php

namespace Database\Factories;

use App\Enums\Peran;
use App\Models\Opd;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'role' => Peran::Operator,
            'opd_id' => Opd::factory(),
            'aktif' => true,
        ];
    }

    public function admin(): static
    {
        return $this->state(['role' => Peran::Admin, 'opd_id' => null]);
    }

    public function walidata(): static
    {
        return $this->state(['role' => Peran::Walidata, 'opd_id' => null]);
    }

    public function operator(?Opd $opd = null): static
    {
        return $this->state(['role' => Peran::Operator, 'opd_id' => $opd?->id ?? Opd::factory()]);
    }

    public function nonaktif(): static
    {
        return $this->state(['aktif' => false]);
    }
}
