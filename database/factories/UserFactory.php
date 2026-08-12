<?php

declare(strict_types=1);

namespace Database\Factories;

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
        $mobile = '09'.fake()->unique()->numerify('#########');

        return [
            'name' => fake()->name(),
            'mobile' => $mobile,
            'username' => 'user_'.Str::lower(fake()->unique()->lexify('??????')),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password123!'),
            'status' => 'active',
            'is_system_admin' => false,
            'must_change_password' => false,
            'remember_token' => Str::random(10),
        ];
    }

    public function systemAdmin(): static
    {
        return $this->state(fn (): array => [
            'is_system_admin' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => [
            'status' => 'inactive',
        ]);
    }
}
