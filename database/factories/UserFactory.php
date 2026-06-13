<?php

namespace Database\Factories;

use App\Enums\Role as RoleEnum;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'document' => fake()->unique()->numerify('##########'),
            'phone' => fake()->numerify('3#########'),
            'address' => fake()->address(),
            'avatar_path' => null,
            'notes' => null,
            'is_active' => true,
            'last_login_at' => null,
        ];
    }

    public function admin(): static
    {
        return $this->afterCreating(function (User $user) {
            $user->assignRole(RoleEnum::Admin->value);
        });
    }

    public function vendedor(): static
    {
        return $this->afterCreating(function (User $user) {
            $user->assignRole(RoleEnum::Vendedor->value);
        });
    }
}
