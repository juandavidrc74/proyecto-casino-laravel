<?php

namespace Database\Factories;

use App\Enums\Rol;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<User> */
class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'username' => fake()->unique()->userName(),
            'password' => hash('sha256', 'password'),
            'rol' => Rol::Jugador,
        ];
    }

    public function administrador(): static
    {
        return $this->state(fn () => ['rol' => Rol::Administrador]);
    }

    public function crupier(): static
    {
        return $this->state(fn () => ['rol' => Rol::Crupier]);
    }

    public function supervisor(): static
    {
        return $this->state(fn () => ['rol' => Rol::Supervisor]);
    }

    public function jugador(): static
    {
        return $this->state(fn () => ['rol' => Rol::Jugador]);
    }
}
