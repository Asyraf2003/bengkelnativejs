<?php

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
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'username'      => Str::lower($this->faker->unique()->userName()),
            'password_hash' => Hash::make('password'),
            'role'          => 'admin',
            'is_active'     => true,
            'remember_token'=> Str::random(10),
        ];
    }
}
